<?php

namespace FCNPressespiegel\Manager;

use Carbon\Carbon;
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
use Laminas\Feed\Reader\Reader;
use SimplePie\Item;
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

        foreach ($sources as $source) {
            try {
                $feedResponse = wp_remote_get($source->getUrl());

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

                    $timestampAgo = Carbon::now()->subHours(24)->getTimestamp();
                    $timestampEntry = $item->getDateCreated()?->getTimestamp() ?? 0;

                    if ($timestampEntry < $timestampAgo) {
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
                    );
                }
            } catch (Exception $exception) {
                do_action('fcnp_feed_exception', $exception);
                $feedErrors[$source->getUrl()] = $exception->getMessage();
                error_log($exception->getMessage());
                continue;
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

                update_post_meta($postId, PressreviewMeta::PRESSREVIEW_URL, $article->getUrl());
            } catch (Exception $e) {
                $articleErrors[$article->getUrl()] = $e->getMessage();
            }
        }

        return new ImportResult($articles, $feedErrors, $articleErrors);
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
        update_post_meta($postId, PressreviewMeta::PRESSREVIEW_URL, $article->getUrl());

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
        $query = new WP_Query([
            'post_type' => PostType::PRESSREVIEW,
            'post_status' => 'publish',
            'meta_key' => PressreviewMeta::PRESSREVIEW_URL,
            'meta_value' => $url,
        ]);

        wp_cache_set($key, $query->found_posts > 0 ? '1' : '0', 'fcnp');

        return $query->found_posts > 0;
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
        $sources[] = new Source('https://rss.kicker.de/team/1fcnuernberg');
        $sources[] = new Source('https://www.n-town.de/glubbblog/index.php/feed');
        $sources[] = new Source('https://www.fcn.de/rss.xml');
        $sources[] = new Source('https://www.youtube.com/feeds/videos.xml?channel_id=UCFWLmp622TIINSPFiv1ivsQ');
        $sources[] = new Source('https://www.youtube.com/feeds/videos.xml?channel_id=UCRFsyeKu07-LnHDG44O6uCA');
        $sources[] = new Source('https://clubfokus.de/feed/');
        $sources[] = new Source(
            'https://www.youtube.com/feeds/videos.xml?channel_id=UCRFsyeKu07-LnHDG44O6uCA',
            fn(Item $item) => str_contains($item->get_content() ?? '', '#1FCNürnberg')
        );

        return apply_filters('fcnp_sources', $sources);
    }
}
