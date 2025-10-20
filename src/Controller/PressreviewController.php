<?php

namespace FCNPressespiegel\Controller;

use FCNPressespiegel\Commands\PressreviewCommand;
use FCNPressespiegel\Enum\Option;
use FCNPressespiegel\Enum\PostType;
use FCNPressespiegel\Enum\PressreviewMeta;
use FCNPressespiegel\Manager\PressreviewManager;
use WP_CLI;
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
        add_filter('wpseo_sitemap_exclude_post_type', $this->excludeFromSitemap(...), 10, 2);
        add_action('admin_enqueue_scripts', $this->enqueueScripts(...));
        add_action('wp_ajax_fcnp_import', $this->import(...));

        if (class_exists('WP_CLI')) {
            WP_CLI::add_command('pressreview', PressreviewCommand::class);
        }
    }

    private function enqueueScripts($hook): void
    {
        if ($hook !== 'edit.php') {
            return;
        }

        global $typenow;

        if ($typenow !== PostType::PRESSREVIEW) {
            return;
        }

        $assets = FCNP_PLUGIN_DIR . 'dist/wp/pressreview-edit.asset.php';

        if (file_exists($assets)) {
            $assets = include $assets;
        } else {
            $assets = ['dependencies' => [], 'version' => time()];
        }

        wp_enqueue_script(
            'pressreview-edit',
            FCNP_PLUGIN_URL . 'dist/wp/pressreview-edit.js',
            $assets['dependencies'],
            $assets['version'],
        );

        wp_localize_script('pressreview-edit', 'pressreviewEdit', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'ajaxNonce' => wp_create_nonce('fcnp_import'),
        ]);
    }

    private function addButton($views): array
    {
        if (!current_user_can('manage_options')) {
            return $views;
        }

        $url = admin_url('admin.php?action=custom_pressreview_action');
        $button = '<a href="' . esc_url($url) . '" class="page-title-action">Custom Button</a>';

        echo $button;

        return $views;
    }

    private function registerPostType(): void
    {

        $posttype_args = [
            'public' => true,
            'exclude_from_search' => true,
            'rewrite' => ['slug' => 'pressespiegel', 'with_front' => true],
            'supports' => ['title', 'editor', 'custom-fields'],
            'menu_icon' => 'dashicons-media-document',
            'has_archive' => true,
            'taxonomies' => ['post_tag'],
            'show_ui' => true,
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

        $hideOlderThenDays = get_option(Option::HIDE_OLDER_THEN_DAYS->value, 9);

        $where .=
            " AND post_date > '" . date('Y-m-d', strtotime("-$hideOlderThenDays days")) . "'";

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

    private function import(): void
    {

        if (!wp_verify_nonce($_GET['_ajax_nonce'], 'fcnp_import')) {
            wp_send_json_error('Invalid nonce');
        }

        $pressreviewManager = new PressreviewManager();
        $importResult = $pressreviewManager->import();
        wp_send_json_success($importResult);
        ;
    }
}
