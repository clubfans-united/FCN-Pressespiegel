<?php

namespace FCNPressespiegel\Manager;

use Carbon\Carbon;
use FCNPressespiegel\Enum\Option;
use FCNPressespiegel\Enum\PostType;
use FCNPressespiegel\Enum\PressreviewMeta;
use FCNPressespiegel\Exceptions\DuplicatePressreviewPostException;
use FCNPressespiegel\Exceptions\InvalidPostTypeException;
use FCNPressespiegel\Exceptions\PostNotFoundException;
use FCNPressespiegel\Factories\ArticleFactory;
use FCNPressespiegel\Models\Article;
use FCNPressespiegel\Models\ImportResult;
use FCNPressespiegel\Models\Source;
use FCNPressespiegel\Posts\Pressreview;
use Exception;
use Laminas\Feed\Reader\Entry\EntryInterface;
use Laminas\Feed\Reader\Reader;
use WP_Query;

class PressreviewManager
{
    public function import(): ImportResult
    {

        $tease = fn(string $text, int $length) => mb_substr($text, 0, $length) . (mb_strlen($text) > $length ? '...' : '');
        $sources = $this->getSources();
        $feedErrors = [];
        $articles = [];
        $articleErrors = [];

        $version = get_file_data(FCNP_PLUGIN_FILE, ['Version' => 'Version'])['Version'] ?: 'dev';
        $userAgent = apply_filters('fcnp_feed_user_agent', 'FCN-Pressespiegel/' . $version);
        $importSince = $this->latestArticleTimestamp();

        do_action('fcnp_import_feeds_total', count($sources));

        $forceIpv4 = static function ($handle): void {
            curl_setopt($handle, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        };

        foreach ($sources as $source) {
            try {
                add_action('http_api_curl', $forceIpv4);

                $feedResponse = wp_remote_get(
                    $source->getUrl(),
                    [
                        'timeout' => 15,
                        'user-agent' => $userAgent,
                    ],
                );

                if (is_wp_error($feedResponse)) {
                    throw new Exception($feedResponse->get_error_message());
                }

                $feedData = wp_remote_retrieve_body($feedResponse);
                $feed = Reader::importString($feedData);

                foreach ($feed as $item) {
                    if (in_array($item->getLink(), array_map(fn(Article $article) => $article->getUrl(), $articles))) {
                        continue;
                    }

                    if ($this->articleExists($item->getLink())) {
                        continue;
                    }

                    $timestampEntry = $item->getDateCreated()?->getTimestamp() ?? 0;

                    // Entries without a usable date are skipped (otherwise they
                    // would get a 1970 post_date).
                    if ($timestampEntry <= 0) {
                        continue;
                    }

                    if ($importSince !== null && $timestampEntry < $importSince) {
                        continue;
                    }

                    if ($source->getFilter() !== null && !call_user_func($source->getFilter(), $item)) {
                        continue;
                    }

                    $dateCreated = Carbon::createFromTimestamp($timestampEntry);

                    $articles[] = ArticleFactory::create(
                        $item->getTitle(),
                        $item->getLink(),
                        '',
                        $dateCreated,
                    )->setSourceUrl($source->getUrl());
                }
            } catch (Exception $exception) {
                do_action('fcnp_feed_exception', $exception);
                $feedErrors[$source->getUrl()] = $exception->getMessage();
            } finally {
                remove_action('http_api_curl', $forceIpv4);
                do_action('fcnp_import_feed_done', $source->getUrl());
            }
        }

        foreach ($articles as $article) {
            try {
                $postData = [
                    'comment_status' => 'closed',
                    'post_title' => $article->getDisplayTitle(),
                    'post_content' => '',
                    'post_status' => 'publish',
                    'ping_status' => 'closed',
                    'post_type' => PostType::PRESSREVIEW,
                    'post_date' => $article->getCreated()->format('Y-m-d H:i:s'),
                ];

                $postId = wp_insert_post($postData);

                update_post_meta($postId, PressreviewMeta::ARTICLE_URL->value, $article->getUrl());
                update_post_meta($postId, PressreviewMeta::SOURCE_URL->value, $article->getSourceUrl());
            } catch (Exception $e) {
                $articleErrors[$article->getUrl()] = $e->getMessage();
            }
        }


        $importResult = new ImportResult($articles, $feedErrors, $articleErrors);

        if ($importResult->hasErrors()) {
            do_action('fcnp_import_failed', $importResult);

            foreach ($importResult->articleErrors as $url => $message) {
                do_action('fcnp_import_article_failed', $url, $message);
                error_log(sprintf('FCN-Pressespiegel: error importing article %s: %s', $url, $message));

            }

            foreach ($importResult->feedErrors as $url => $message) {
                do_action('fcnp_import_feed_failed', $url, $message);
                error_log(sprintf('FCN-Pressespiegel: error importing feed %s: %s', $url, $message));
            }
        }

        update_option(
            Option::IMPORT_ERORRS->value,
            [
                'time'          => $importResult->getTimestamp(),
                'dismissed' => false,
                'datetime' => $importResult->getDateTime(),
                'feedErrors'    => $importResult->feedErrors,
                'articleErrors' => $importResult->articleErrors,
            ],
            false,
        );

        do_action('fcnp_import_done', $importResult);
        return $importResult;
    }

    /**
     * @throws PostNotFoundException
     * @throws InvalidPostTypeException|DuplicatePressreviewPostException
     */
    public function importArticle(Article $article, array $tags = []): Pressreview
    {
        if ($this->articleExists($article->getUrl())) {
            throw new DuplicatePressreviewPostException();
        }

        $postData = [
            'comment_status' => 'closed',
            'post_title' => $article->getDisplayTitle(),
            'post_content' => $article->getExcerpt(),
            'post_status' => 'publish',
            'ping_status' => 'closed',
            'post_type' => PostType::PRESSREVIEW,
            'post_date' => $article->getCreated()->format('Y-m-d H:i:s'),
        ];

        $postId = wp_insert_post($postData);
        wp_set_object_terms($postId, $tags, 'post_tag');
        update_post_meta($postId, PressreviewMeta::ARTICLE_URL->value, $article->getUrl());

        do_action('fcnp_after_import_article', $article, $postId);

        return Pressreview::createFromPostId($postId);
    }

    private function articleExists(string $url): bool
    {
        $key = 'fcnp_article_exists_' . md5($url);
        $exists = wp_cache_get($key, 'fcnp');

        if ($exists) {
            return $exists === '1';
        }

        $url = trim($url);
        $query = new WP_Query(
            [
                'post_type' => PostType::PRESSREVIEW,
                'post_status' => 'publish',
                'meta_key' => PressreviewMeta::ARTICLE_URL->value,
                'meta_value' => $url,
            ],
        );

        wp_cache_set($key, $query->found_posts > 0 ? '1' : '0', 'fcnp');

        return $query->found_posts > 0;
    }

    /**
     * Unix timestamp (UTC) of the newest existing article, or null when no
     * articles exist yet. Used as the moving lower bound for the import.
     *
     * post_date is read back and parsed as UTC to match how it is written in
     * import() (Carbon::createFromTimestamp(...)->format('Y-m-d H:i:s')).
     */
    private function latestArticleTimestamp(): ?int
    {
        $latest = new WP_Query(
            [
                'post_type' => PostType::PRESSREVIEW,
                'post_status' => 'publish',
                'posts_per_page' => 1,
                'orderby' => 'date',
                'order' => 'DESC',
                'no_found_rows' => true,
                'fields' => 'ids',
            ],
        );

        if (empty($latest->posts)) {
            return null;
        }

        $postDate = get_post_field('post_date', $latest->posts[0]);

        return Carbon::parse($postDate, 'UTC')->getTimestamp();
    }

    /**
     * @return Source[]
     */
    private function getSources(): array
    {
        $sources = [];
        $sources[] = new Source('https://www.frankenfernsehen.tv/mediathek/kategorie/sport/1-fc-nuernberg/feed/');
        $sources[] = new Source('https://www.bild.de/feed/nuernberg.xml');
        $sources[] = new Source('https://www.nordbayern.de/sport/1-fc-nuernberg?isRss=true');
        $sources[] = new Source('https://www.nn.de/sport/1-fc-nuernberg?isRss=true');
        $sources[] = new Source('https://newsfeed.kicker.de/team/1fcnuernberg');
        $sources[] = new Source('https://www.n-town.de/glubbblog/index.php/feed');
        $sources[] = new Source('https://www.fcn.de/rss.xml');
        $sources[] = new Source('https://www.fcn.de/rss_press_review.xml');
        $sources[] = new Source('https://www.youtube.com/feeds/videos.xml?channel_id=UCFWLmp622TIINSPFiv1ivsQ');
        $sources[] = new Source('https://clubfokus.de/feed/');
        $sources[] = new Source(
            'https://www.youtube.com/feeds/videos.xml?channel_id=UCRFsyeKu07-LnHDG44O6uCA',
            fn(EntryInterface $item) => str_contains($this->mediaDescription($item), '#1FCNürnberg'),
        );
        // Liga-Zwei.de tags every entry as "Allgemein", so categories are
        // useless for filtering. Article titles follow a "Verein: Schlagzeile"
        // scheme, so the club is matched via the title prefix instead.
        $sources[] = new Source(
            'https://www.liga-zwei.de/feed/',
            fn(EntryInterface $item) => str_starts_with(trim($item->getTitle()), '1. FC Nürnberg'),
        );

        return apply_filters('fcnp_sources', $sources);
    }

    /**
     * Reads the Media RSS description (media:group/media:description) from a
     * feed entry's raw DOM. Laminas' getContent()/getDescription() return an
     * empty string for media feeds such as YouTube, so the description has to
     * be read from the Media RSS namespace directly.
     */
    private function mediaDescription(EntryInterface $item): string
    {
        $mediaNamespace = 'http://search.yahoo.com/mrss/';
        $media = simplexml_import_dom($item->getElement())->children($mediaNamespace);

        if (!isset($media->group)) {
            return '';
        }

        return (string) $media->group->children($mediaNamespace)->description;
    }
}
