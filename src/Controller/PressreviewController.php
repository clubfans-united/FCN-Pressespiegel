<?php

namespace FCNPressespiegel\Controller;

use FCNPressespiegel\Commands\PressreviewCommand;
use FCNPressespiegel\Enum\PostType;
use FCNPressespiegel\Enum\PressreviewMeta;
use WP_Query;

class PressreviewController
{
    use Controller;

    private function __construct()
    {
        add_action('init', $this->registerPostType(...));
        add_action('template_redirect', $this->redirectToUrl(...));
        add_action('pre_get_posts', $this->postsPerPage(...));
        add_filter('post_type_link', $this->pressreviewLink(...), 99, 2);
        add_filter('posts_where', $this->whereNotOlderThan(...), 10, 2);
        add_filter('query_vars', $this->addQueryVars(...));
        add_filter('wpseo_sitemap_exclude_post_type', $this->excludeFromSitemap(...), 10, 2,);

        if (class_exists('WP_CLI')) {
            \WP_CLI::add_command('pressreview', PressreviewCommand::class);
        }
    }

    private function registerPostType(): void
    {
        $show_ui = current_user_can('manage_options');

        $posttype_args = [
            'public' => true,
            'exclude_from_search' => true,
            'rewrite' => ['slug' => 'pressespiegel', 'with_front' => true],
            'supports' => ['title', 'editor', 'custom-fields'],
            'menu_icon' => 'dashicons-media-document',
            'has_archive' => true,
            'taxonomies' => ['post_tag'],
            'show_ui' => $show_ui,
            'labels' => [
                'name' => 'Pressespiegel',
                'all_items' => 'Presseartikel',
                'singular_name' => 'Presseartikel',
                'add_new' => 'Presseartikel hinzufügen',
                'add_new_item' => 'Presseartikel hinzufügen',
                'edit_item' => 'Presseartikel ändern',
                'new_item' => 'Neuer Presseartikel',
                'view_item' => 'Presseartikel ansehen',
                'search_items' => 'Presseartikel suchen',
                'not_found' => 'Kein Presseartikel gefunden',
            ],
        ];

        register_post_type(PostType::PRESSREVIEW, $posttype_args);
    }

    private function redirectToUrl(): void
    {
        if (is_singular(PostType::PRESSREVIEW)) {
            $pressreviewUrl = get_post_meta(
                get_the_ID(),
                PressreviewMeta::PRESSREVIEW_URL,
                true,
            );

            if (empty($pressreviewUrl)) {
                $pressreviewUrl = home_url();
            }

            wp_redirect($pressreviewUrl, 301);
            exit();
        }
    }

    private function whereNotOlderThan($where, WP_Query $query): string
    {
        if (is_admin()) {
            return $where;
        }

        if (!is_post_type_archive(PostType::PRESSREVIEW)) {
            return $where;
        }

        if (!$query->is_main_query()) {
            return $where;
        }

        $where .=
            " AND post_date > '" . date('Y-m-d', strtotime('-9 days')) . "'";

        return $where;
    }

    private function postsPerPage(WP_Query $query): void
    {
        if (is_admin()) {
            return;
        }

        if (!is_post_type_archive(PostType::PRESSREVIEW)) {
            return;
        }

        if (!$query->is_main_query()) {
            return;
        }

        $query->set('posts_per_page', 30);
    }

    private function pressreviewLink(string $url, $post)
    {
        if (PostType::PRESSREVIEW === get_post_type($post)) {
            $pressreviewUrl = get_post_meta(
                $post->ID,
                PressreviewMeta::PRESSREVIEW_URL,
                true,
            );

            if (empty($pressreviewUrl)) {
                $pressreviewUrl = get_home_url();
            }

            $url = $pressreviewUrl;
        }

        return $url;
    }

    private function excludeFromSitemap(bool $exclude, string $post_type): bool
    {
        if ($post_type === PostType::PRESSREVIEW) {
            return true;
        }

        return $exclude;
    }

    private function addQueryVars(array $query_vars): array
    {
        $query_vars[] = 'fcnp-action';

        return $query_vars;
    }
}
