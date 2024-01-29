<?php

namespace FCNPressespiegel\Commands;

use FCNPressespiegel\Enum\PressreviewMeta;
use FCNPressespiegel\Manager\PressreviewManager;

class PressreviewCommand
{
    /**
     * List Pressreview Items
     *
     * ## EXAMPLES
     *
     *     wp pressreview list
     *
     * @when after_init
     */
    public function list(): void
    {
        \WP_CLI::line('Pressreview Items');

        $pressreviewItems = PressreviewManager::getPressreviewItems();

        foreach ($pressreviewItems as $pressreviewItem) {
            \WP_CLI::line($pressreviewItem->getDisplayTitle());
        }
    }

    /**
     * Import Pressreview Items
     *
     * ## EXAMPLES
     *
     *     wp pressreview import
     *
     * @when after_init
     */
    public function import(): void
    {
        \WP_CLI::line('Import Pressreview Items');

        $pressreviewPosts = PressreviewManager::doPressreviewAutoImport();

        foreach ($pressreviewPosts as $pressreviewPost) {
            \WP_CLI::line($pressreviewPost->getPost()->post_title);
            \WP_CLI::line(get_post_meta($pressreviewPost->getPost()->ID, PressreviewMeta::PRESSREVIEW_URL, true));
            \WP_CLI::line('------------------------------------------------------------------');
        }

        \WP_CLI::line('Imported ' . count($pressreviewPosts) . ' Pressreview Items');
    }
}
