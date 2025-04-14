<?php

namespace FCNPressespiegel\Manager;

use Carbon\Carbon;
use FCNPressespiegel\Enum\PostType;
use FCNPressespiegel\Enum\PressreviewMeta;
use FCNPressespiegel\Exceptions\DuplicatePressreviewPostException;
use FCNPressespiegel\Factories\PressreviewItemFactory;
use FCNPressespiegel\Models\PressreviewItem;
use FCNPressespiegel\Models\PressreviewSource;
use FCNPressespiegel\Models\PressreviewSourceFilter;
use FCNPressespiegel\Posts\Pressreview;
use Exception;
use Laminas\Feed\Reader\Reader;
use Ozh\Bookmarkletgen\Bookmarkletgen;
use WP_Query;

class PressreviewManager
{
    /**
     * @param string $url
     * @return bool
     */
    private static function itemExists(string $url): bool
    {
        $url = trim($url);
        $query = new WP_Query([
            'post_type' => PostType::PRESSREVIEW,
            'post_status' => 'publish',
            'meta_key' => PressreviewMeta::PRESSREVIEW_URL,
            'meta_value' => $url,
        ]);
        return $query->found_posts > 0;
    }

    /**
     * @param string $title
     * @param string $description
     * @param string $url
     * @param array $tags
     *
     * @return Pressreview
     * @throws DuplicatePressreviewPostException
     */
    public static function addPressreviewItem(
        string $title,
        string $description,
        string $url,
        array $tags
    ): Pressreview {
        if (self::itemExists($url)) {
            throw new DuplicatePressreviewPostException();
        }

        $post_array = [
            'comment_status' => 'closed',
            'post_title' => $title,
            'post_content' => $description,
            'post_status' => 'publish',
            'ping_status' => 'closed',
            'post_type' => PostType::PRESSREVIEW,
        ];
        $post_id = wp_insert_post($post_array);
        wp_set_object_terms($post_id, $tags, 'post_tag');
        update_post_meta($post_id, PressreviewMeta::PRESSREVIEW_URL, $url);

        return Pressreview::createFromPostId($post_id);
    }

    /**
     * @return string
     */
    public static function getBookmarkletLink(): string
    {
        !
        $bookmarklet_gen = new Bookmarkletgen();
        $js_template_content = self::getBookmarkletJavascript();
        return $bookmarklet_gen->crunch($js_template_content);
    }

    /**
     * @return string
     */
    public static function getBookmarkletJavascript(): string
    {
        $js_template_path =
            FCNP_PLUGIN_DIR . '/templates/pressreview-bookmarklet.js';
        $js_template_content = file_get_contents($js_template_path);
        $url = str_replace('/', '\/', self::getPressreviewAddUrl());
        $js_template_content = str_replace(
            '###url###',
            $url,
            $js_template_content,
        );
        return $js_template_content;
    }

    public static function getPressreviewAddUrl(): string
    {
        return home_url('pressreview_this');
    }

    /**
     * @return Pressreview[]
     */
    public static function doPressreviewAutoImport(): array
    {
        $pressreviewItems = self::getPressreviewItems();
        $importedPressreviews = [];
        foreach ($pressreviewItems as $pressreviewItem) {
            /* @var PressreviewItem $pressreviewItem */
            try {
                $importedPressreviews[] = self::addPressreviewItem(
                    $pressreviewItem->getDisplayTitle(),
                    '',
                    $pressreviewItem->getUrl(),
                    [],
                );
            } catch (DuplicatePressreviewPostException $e) {
                continue;
            } catch (Exception $e) {
                //TODO capture exception to your favorite error tracking tool
                continue;
            }
        }

        return $importedPressreviews;
    }

    /**
     * @return PressreviewItem[]
     */
    public static function getPressreviewItems(): array
    {
        $sources = self::getPressreviewSources();
        $pressreviewItems = [];
        foreach ($sources as $source) {
            try {
                $feed = Reader::import($source->getUrl());
            } catch (Exception $exception) {
                /** @noinspection ForgottenDebugOutputInspection */
                //TODO capture exception to your favorite error tracking tool
                error_log($exception->getMessage());
                continue;
            }

            foreach ($feed as $entry) {
                /* @var $entry \Laminas\Feed\Reader\Entry\Rss */
                $dateCreated = $entry->getDateCreated() ?? Carbon::now();

                if (!self::itemExists($entry->getLink())) {
                    if ($source->getFilter() !== null) {
                        $contains = $source->getFilter()->getContains();
                        switch ($source->getFilter()->getField()) {
                            case 'textContent':
                                $content = $entry->getElement()->textContent;

                                break;
                            case 'category':
                                $content = implode(
                                    ' ',
                                    $entry->getCategories()->getValues(),
                                );

                                break;
                            default:
                                $content = '';
                        }

                        if (
                            string($content)->contains(
                                $source->getFilter()->getContains(),
                            )
                        ) {
                            $pressreviewItems[] = PressreviewItemFactory::create(
                                $entry->getTitle(),
                                $entry->getLink(),
                                string($entry->getContent())->tease(300),
                                $dateCreated,
                            );
                        }
                    } else {
                        $pressreviewItems[] = PressreviewItemFactory::create(
                            $entry->getTitle(),
                            $entry->getLink(),
                            string($entry->getContent())->tease(300),
                            $dateCreated,
                        );
                    }
                }
            }
        }

        $pressreviewUrls = array_unique(array_map(static function (PressreviewItem $item) {
            return $item->getUrl();
        }, $pressreviewItems));


        $pressreviewItems = array_filter(
            $pressreviewItems,
            static function (PressreviewItem $item) use ($pressreviewUrls) {
                return in_array($item->getUrl(), $pressreviewUrls, true);
            }
        );


        usort($pressreviewItems, static function (PressreviewItem $a, PressreviewItem $b) {
            if ($a->getCreated()->getTimestamp() === $b->getCreated()->getTimestamp()) {
                return 0;
            }
            return ($a->getCreated()->getTimestamp() > $b->getCreated()->getTimestamp()) ? -1 : 1;
        });

        $pressreviewItems = array_filter(
            $pressreviewItems,
            static function (PressreviewItem $item) {
                $timestampAgo = Carbon::now()
                    ->subHours(18)
                    ->getTimestamp();
                return $item->getCreated()->getTimestamp() > $timestampAgo;
            }
        );

        return $pressreviewItems;
    }

    /**
     * @return PressreviewSource[]
     */
    private static function getPressreviewSources(): array
    {
        $sources = [];
        $sources[] = new PressreviewSource(
            'https://www.bild.de/feed/nuernberg.xml'
        );
        $sources[] = new PressreviewSource(
            'https://www.nordbayern.de/sport/1-fc-nuernberg?isRss=true',
        );
        $sources[] = new PressreviewSource(
            'http://rss.kicker.de/team/1fcnuernberg',
        );
        $sources[] = new PressreviewSource(
            'https://www.n-town.de/glubbblog/index.php/feed',
        );
        $sources[] = new PressreviewSource('http://www.fcn.de/rss.xml');
        $sources[] = new PressreviewSource(
            'https://www.youtube.com/feeds/videos.xml?channel_id=UCFWLmp622TIINSPFiv1ivsQ',
        );
        $sources[] = new PressreviewSource(
            'https://www.frankenfernsehen.tv/mediathek/kategorie/sport/1-fc-nuernberg/feed/',
        );
        $sources[] = new PressreviewSource(
            'https://www.youtube.com/feeds/videos.xml?channel_id=UCRFsyeKu07-LnHDG44O6uCA',
            new PressreviewSourceFilter('textContent', '#1FCNürnberg'),
        );
        $sources[] = new PressreviewSource('https://clubfokus.de/feed/');

        return $sources;
    }
}
