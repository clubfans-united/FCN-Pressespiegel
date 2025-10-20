<?php

namespace FCNPressespiegel\Controller;

use FCNPressespiegel\Enum\PostType;
use FCNPressespiegel\Manager\PressreviewManager;

class SettingsController
{
    use Controller;

    private const OPTION_CRONJOB_ENABLED = '_fcnp_cronjob_enabled';

    private const SETTINGS_GROUP = 'fcnp_settings';

    private const SETTINGS_SECTION = 'fcnp_main';

    private const MENU_SLUG = 'fcn-pressespiegel-settings';

    private function __construct()
    {
        add_action('admin_menu', $this->registerMenu(...));
        add_action('admin_init', $this->registerSettings(...));
    }

    private function registerMenu(): void
    {
        $parent_slug = 'edit.php?post_type=' . PostType::PRESSREVIEW;
        $capability = 'manage_options';
        add_submenu_page(
            $parent_slug,
            __('Pressespiegel Einstellungen', 'fcn-pressespiegel'),
            __('Einstellungen', 'fcn-pressespiegel'),
            $capability,
            self::MENU_SLUG,
            $this->renderPage(...)
        );
    }

    private function registerSettings(): void
    {
        register_setting(
            self::SETTINGS_GROUP,
            self::OPTION_CRONJOB_ENABLED,
            [
                'type' => 'boolean',
                'sanitize_callback' => function ($value) {
                    return $value ? '1' : '0';
                },
                'default' => '1',
            ]
        );

        add_settings_section(
            self::SETTINGS_SECTION,
            '',
            '__return_false',
            self::MENU_SLUG
        );

        add_settings_field(
            'fcnp_cronjob_enabled',
            __('Automatisch importieren', 'fcn-pressespiegel'),
            $this->renderCronjobEnabledField(...),
            self::MENU_SLUG,
            self::SETTINGS_SECTION
        );

        add_settings_section(
            'fcnp_bookmarklet',
            __('Bookmarklet', 'fcn-pressespiegel'),
            $this->renderBookmarkletSection(...),
            self::MENU_SLUG
        );

        add_settings_field(
            'fcnp_bookmarklet_button',
            __('Button', 'fcn-pressespiegel'),
            $this->renderBookmarkletButtonField(...),
            self::MENU_SLUG,
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
        settings_fields(self::SETTINGS_GROUP);
        do_settings_sections(self::MENU_SLUG);
        submit_button();
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

    private function renderCronjobEnabledField(): void
    {
        $value = get_option(self::OPTION_CRONJOB_ENABLED, '1');
        $checked = checked('1', $value, false);
        $id = esc_attr(self::OPTION_CRONJOB_ENABLED);
        $description = esc_html(__('Aktiviere diese Option, um den Artikel automatisch zu importieren.', 'fcn-pressespiegel'));

        echo <<<HTML
            <label for="$id">
                <input type="checkbox" id="$id" name="$id" value="1" $checked />
                $description
            </label>
        HTML;
    }
    private function renderBookmarkletSection(): void
    {
        echo '<p>' . esc_html(__('Diesen Link/Button in die Browser-Bookmarkleiste ziehen:', 'fcn-pressespiegel')) . '</p>';
    }

    private function renderBookmarkletButtonField(): void
    {
        $link = PressreviewManager::getBookmarkletLink();
        $script =  PressreviewManager::getBookmarkletJavascript();
        $label = __('Zum Pressespiegel hinzufügen', 'fcn-pressespiegel');
        $button = sprintf(
            '<a class="button-primary" href="%s">%s</a>',
            $link,
            esc_html($label)
        );

        echo <<<HTML
            <p>$button</p>
            <p><textarea readonly rows="5" cols="80" class="large-text code">$script</textarea></p>
        HTML;
    }
}
