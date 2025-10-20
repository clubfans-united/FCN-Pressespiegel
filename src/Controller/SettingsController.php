<?php

namespace FCNPressespiegel\Controller;

use FCNPressespiegel\Enum\Option;
use FCNPressespiegel\Enum\PostType;
use FCNPressespiegel\Manager\PressreviewManager;

class SettingsController
{
    use Controller;

    private const SETTINGS_GROUP = 'fcnp_settings';

    private const SETTINGS_SECTION = 'fcnp_main';

    private const MENU_SLUG = 'fcn-pressespiegel-settings';

    private PressreviewManager $pressreviewManager;

    private function __construct()
    {
        $this->pressreviewManager = new PressreviewManager();
        add_action('admin_menu', $this->registerMenu(...));
        add_action('admin_init', $this->registerSettings(...));
    }

    private function registerMenu(): void
    {
        $parentSlug = 'edit.php?post_type=' . PostType::PRESSREVIEW;
        $capability = 'manage_options';
        add_submenu_page(
            $parentSlug,
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
            Option::CRONJOB_ENABLED->value,
            [
                'type' => 'boolean',
                'sanitize_callback' => function ($value) {
                    return $value ? '1' : '0';
                },
                'default' => '1',
            ]
        );

        register_setting(
            self::SETTINGS_GROUP,
            Option::HIDE_OLDER_THEN_DAYS->value,
            [
                'type' => 'integer',
                'sanitize_callback' => 'absint',
                'default' => 9,
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

        add_settings_field(
            Option::HIDE_OLDER_THEN_DAYS->value,
            __('Ausblenden', 'fcn-pressespiegel'),
            $this->renderHideOlderThenDays(...),
            self::MENU_SLUG,
            self::SETTINGS_SECTION
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
        $value = get_option(Option::CRONJOB_ENABLED->value, '1');
        $checked = checked('1', $value, false);
        $id = esc_attr(Option::CRONJOB_ENABLED->value);
        $description = esc_html(__('Aktiviere diese Option, um den Artikel automatisch zu importieren.', 'fcn-pressespiegel'));

        echo <<<HTML
            <label for="$id">
                <input type="checkbox" id="$id" name="$id" value="1" $checked />
                <p>
                    $description
                </p>
            </label>
        HTML;
    }

    private function renderHideOlderThenDays(): void
    {
        $value = get_option(Option::HIDE_OLDER_THEN_DAYS->value, 9);
        $id = esc_attr(Option::HIDE_OLDER_THEN_DAYS->value);
        $description = esc_html(__('Tage nachdem Pressespiegel Artikel ausgeblendet werden', 'fcn-pressespiegel'));

        echo <<<HTML
            <label for="$id">
                <input type="number" id="$id" name="$id" min="1" max="100" value="$value" />
                <p>
                    $description
                </p>
            </label>
        HTML;
    }
}
