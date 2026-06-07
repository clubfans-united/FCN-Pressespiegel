<?php

namespace FCNPressespiegel\Controller;

use FCNPressespiegel\Enum\Option;
use FCNPressespiegel\Models\ImportResult;

class ImportController
{
    use Controller;

    private function __construct()
    {
        add_action('admin_notices', $this->validateImportResult(...));
    }

    private function validateImportResult(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $importErrors = wp_parse_args(
            get_option(Option::IMPORT_ERORRS->value),
            [
                'time' => 0,
                'dismissed' => false,
                'imported' => 0,
                'feedErrors' => [],
                'articleErrors' => [],
            ],
        );

        $feedErrors = (array) $importErrors['feedErrors'];
        $articleErrors = (array) $importErrors['articleErrors'];

        if (empty($feedErrors) && empty($articleErrors)) {
            return;
        }

        $section = static function (string $heading, array $errors): string {
            if (empty($errors)) {
                return '';
            }

            $items = '';
            foreach ($errors as $url => $message) {
                $items .= sprintf(
                    '<li><code>%s</code> — %s</li>',
                    esc_html((string) $url),
                    esc_html((string) $message),
                );
            }

            return sprintf(
                '<p><strong>%s</strong></p><ul style="list-style:disc;margin-left:2em;">%s</ul>',
                esc_html($heading),
                $items,
            );
        };

        $headline = sprintf(
            '<p><strong>%s</strong>%s</p>',
            esc_html__('FCN Pressespiegel: Beim letzten Import sind Fehler aufgetreten.', 'fcn-pressespiegel'),
            empty($importErrors['time'])
                ? ''
                : ' <em>(' . esc_html(wp_date('d.m.Y H:i', (int) $importErrors['time'])) . ')</em>',
        );

        $message = $headline
            . $section(__('Fehlgeschlagene Feeds:', 'fcn-pressespiegel'), $feedErrors)
            . $section(__('Fehlgeschlagene Artikel:', 'fcn-pressespiegel'), $articleErrors);

        wp_admin_notice(
            $message,
            [
                'type' => 'warning',
                'dismissible' => true,
                'paragraph_wrap' => false,
            ],
        );
    }
}
