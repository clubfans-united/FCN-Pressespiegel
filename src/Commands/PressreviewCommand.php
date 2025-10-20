<?php

namespace FCNPressespiegel\Commands;

use FCNPressespiegel\Enum\PressreviewMeta;
use FCNPressespiegel\Manager\PressreviewManager;
use WP_CLI;
use WP_CLI_Command;

class PressreviewCommand extends WP_CLI_Command
{
    private PressreviewManager $pressreviewManager;

    public function __construct()
    {
        parent::__construct();
        $this->pressreviewManager = new PressreviewManager();
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
        WP_CLI::line('Import Pressreview Items');

        $posts = $this->pressreviewManager->import();

        foreach ($posts as $post) {
            WP_CLI::line($post->getPost()->post_title);
            WP_CLI::line(get_post_meta($post->getPost()->ID, PressreviewMeta::PRESSREVIEW_URL, true));
            WP_CLI::line('------------------------------------------------------------------');
        }

        WP_CLI::line('Imported ' . count($posts) . ' Pressreview Items');
    }
}
