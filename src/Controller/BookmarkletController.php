<?php

namespace FCNPressespiegel\Controller;

use FCNPressespiegel\Enum\Action;
use FCNPressespiegel\Enum\PostType;
use FCNPressespiegel\Exceptions\DuplicatePressreviewPostException;
use FCNPressespiegel\Factories\ArticleFactory;
use FCNPressespiegel\Manager\PressreviewManager;
use FCNPressespiegel\Models\Article;
use Ozh\Bookmarkletgen\Bookmarkletgen;
use WP_REST_Request;
use WP_REST_Response;

class BookmarkletController
{
    use Controller;

    private function __construct()
    {
        add_action('init', $this->addRewriteRule(...));
        add_action('wp_print_styles', $this->removeStyles(...));
        add_action('wp_enqueue_scripts', $this->enqueueScripts(...));
        add_action('rest_api_init', $this->registerRestRoute(...));
        add_filter('redirect_canonical', $this->disableRedirectCanoncial(...));
        add_filter('template_include', $this->templateInclude(...));
        add_filter('show_admin_bar', $this->hideAdminBar(...));
        add_action('admin_menu', $this->registerMenu(...));
        add_action('admin_init', $this->registerSettings(...));
    }

    private function addRewriteRule(): void
    {
        add_rewrite_rule(
            'pressreview_this',
            'index.php?fcnp-action=' . Action::PRESSREVIEW_THIS_SHOW->value,
            'top',
        );
    }

    private function disableRedirectCanoncial($requestUrl)
    {
        if ($this->isPressreviewAdd()) {
            return false;
        }

        return $requestUrl;
    }

    private function hideAdminBar(bool $showAdminBar): bool
    {
        if ($this->isPressreviewAdd()) {
            $showAdminBar = false;
        }

        return $showAdminBar;
    }

    private function enqueueScripts(): void
    {
        if (!$this->isPressreviewAdd()) {
            return;
        }

        $assets = include FCNP_PLUGIN_DIR . DIRECTORY_SEPARATOR . '/dist/wp/PressreviewThis.asset.php';
        $pluginDir = plugin_dir_url(FCNP_PLUGIN_FILE);

        wp_enqueue_script(
            'fcnp-pressreview-this',
            $pluginDir . 'dist/wp/PressreviewThis.js',
            $assets['dependencies'],
            $assets['version'],
            false,
        );

        wp_enqueue_style(
            'fcnp-pressreview-this',
            $pluginDir . 'dist/wp/style-PressreviewThis.css',
        );
    }

    private function removeStyles(): void
    {
        if (!$this->isPressreviewAdd()) {
            return;
        }

        global $wp_styles;

        $wp_styles->queue = array_filter(
            $wp_styles->queue,
            fn($style) => str_contains($style, 'fcnp-pressreview-this')
        );
    }

    private function templateInclude($template): string
    {
        if (!$this->isPressreviewAdd()) {
            return $template;
        }

        if (!is_user_logged_in()) {
            auth_redirect();
        }

        if (!current_user_can('edit_posts')) {
            /** @noinspection ForgottenDebugOutputInspection */
            wp_die(__('Kommst Du aus der Westvorstadt?', 'fcn-pressespiegel'));
        }

        return  FCNP_PLUGIN_DIR . '/templates/pressreview-this.php';
    }

    private function registerRestRoute(): void
    {
        register_rest_route('fcnpressespiegel/v1', '/pressreview/add', [
            'methods' => 'POST',
            'callback' => static function (WP_REST_Request $request): WP_REST_Response {
                $response = new WP_REST_Response();

                if (!current_user_can('edit_posts')) {
                    $response->set_status(401);
                    return $response;
                }

                $title = $request->get_param('title');
                $description = $request->get_param('description');
                $url = $request->get_param('url');
                $tags = $request->get_param('tags');

                $pressreviewManager = new PressreviewManager();

                try {
                    $article = ArticleFactory::create($title, $url, $description, new \DateTime());
                    $post = $pressreviewManager->importArticle($article, $tags);
                    $response->set_data($post);
                } catch (DuplicatePressreviewPostException $exception) {
                    $response->set_status(409);
                    $response->set_data([
                        'message' => $exception->getMessage(),
                    ]);
                } catch (\Exception $exception) {
                    $response->set_status(500);
                    $response->set_data([
                        'message' => $exception->getMessage(),
                    ]);
                }

                return $response;
            },
            'permission_callback' => function () {
                return current_user_can('manage_options') ||
                    current_user_can('edit_posts');
            },
            'args' => [
                'title' => [
                    'validate_callback' => function ($param) {
                        return !empty($param);
                    },
                    'required' => true,
                    'description' => 'the tile',
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'description' => [
                    'validate_callback' => function ($param) {
                        return !empty($param);
                    },
                    'required' => true,
                    'description' => 'the title',
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'url' => [
                    'validate_callback' => function ($param) {
                        return !(
                            filter_var($param, FILTER_VALIDATE_URL) === false
                        );
                    },
                    'required' => true,
                    'description' => 'the url',
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'tags' => [
                    'required' => false,
                    'description' => 'the tags',
                    'type' => 'array',
                ],
            ],
        ]);
    }

    private function isPressreviewAdd(): bool
    {
        return Action::getCurrentAction(true) === Action::PRESSREVIEW_THIS_SHOW;
    }

    private function registerMenu(): void
    {
        $parentSlug = 'edit.php?post_type=' . PostType::PRESSREVIEW;
        $capability = 'manage_options';
        add_submenu_page(
            $parentSlug,
            __('FCN Pressespiegel Bookmarklet', 'fcn-pressespiegel'),
            __('Bookmarklet', 'fcn-pressespiegel'),
            $capability,
            'fcnp-bookmarklet',
            $this->renderPage(...)
        );
    }

    private function registerSettings(): void
    {
        add_settings_section(
            'fcnp_bookmarklet',
            __('Bookmarklet', 'fcn-pressespiegel'),
            static function () {
                echo '<p>' . esc_html(__('Diesen Link/Button in die Browser-Bookmarkleiste ziehen:', 'fcn-pressespiegel')) . '</p>';
            },
            'fcnp-bookmarklet'
        );

        add_settings_field(
            'fcnp_bookmarklet_button',
            __('Button', 'fcn-pressespiegel'),
            static function () {

                $templatePath = FCNP_PLUGIN_DIR . '/templates/pressreview-bookmarklet.js';
                $templateContent = file_get_contents($templatePath);
                $url = str_replace('/', '\/', home_url('pressreview_this'));
                $script = str_replace('###url###', $url, $templateContent);

                $bookmarkletGen = new Bookmarkletgen();
                $link = $bookmarkletGen->crunch($script);

                $label = __('Zum Pressespiegel hinzufügen', 'fcn-pressespiegel');
                $button = sprintf('<a class="button-primary" href="%s">%s</a>', $link, esc_html($label));

                echo <<<HTML
                    <p>$button</p>
                    <p><textarea readonly rows="5" cols="80" class="large-text code">$script</textarea></p>
                HTML;
            },
            'fcnp-bookmarklet',
            'fcnp_bookmarklet'
        );
    }

    private function renderPage(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $headline = __('Pressespiegel Einstellungen', 'fcn-pressespiegel');

        ob_start();
        settings_errors();
        $errors = ob_get_clean();

        ob_start();
        do_settings_sections('fcnp-bookmarklet');

        $form = ob_get_clean();

        echo <<<HTML
            <div class="wrap">
                <h1>{$headline}</h1>
                $errors
                <form method="post" action="options.php">
                    $form
                </form>
            </div>
        HTML;
    }
}
