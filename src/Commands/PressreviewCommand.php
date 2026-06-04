<?php

namespace FCNPressespiegel\Commands;

use FCNPressespiegel\Enum\PostType;
use FCNPressespiegel\Enum\PressreviewMeta;
use FCNPressespiegel\Manager\PressreviewManager;
use WP_CLI;
use WP_CLI_Command;
use WP_Query;

class PressreviewCommand extends WP_CLI_Command
{
    private PressreviewManager $pressreviewManager;

    public function __construct()
    {
        parent::__construct();
        $this->pressreviewManager = new PressreviewManager();
    }

    /**
     * Imports new articles from all configured feeds.
     *
     * Fetches every configured source feed, skips duplicates and entries older
     * than 24 hours, and creates a Pressreview post for each new article.
     *
     * ## EXAMPLES
     *
     *     # Import new articles from all feeds
     *     $ wp pressreview import
     *
     * @when after_init
     */
    public function import(): void
    {
        WP_CLI::line('Import Pressreview Items');

        $posts = $this->pressreviewManager->import();

        foreach ($posts as $post) {
            WP_CLI::line($post->getPost()->post_title);
            WP_CLI::line(get_post_meta($post->getPost()->ID, PressreviewMeta::ARTICLE_URL->value, true));
            WP_CLI::line('------------------------------------------------------------------');
        }

        WP_CLI::line('Imported ' . count($posts) . ' Pressreview Items');
    }

    /**
     * Deletes all Pressreview articles.
     *
     * Permanently removes every post of the Pressreview post type, bypassing the
     * trash. Prompts for confirmation unless --yes is passed.
     *
     * ## OPTIONS
     *
     * [--yes]
     * : Skip the confirmation prompt.
     *
     * ## EXAMPLES
     *
     *     # Delete all articles (asks for confirmation)
     *     $ wp pressreview delete
     *
     *     # Delete all articles without confirmation
     *     $ wp pressreview delete --yes
     *
     * @when after_init
     */
    public function delete(array $args, array $assocArgs): void
    {
        $ids = (new WP_Query([
            'post_type'      => PostType::PRESSREVIEW,
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ]))->posts;

        $count = count($ids);

        if ($count === 0) {
            WP_CLI::success('No Pressreview Items to delete.');
            return;
        }

        WP_CLI::confirm(
            sprintf('Permanently delete %d Pressreview Item(s)? This cannot be undone.', $count),
            $assocArgs,
        );

        $deleted = 0;

        foreach ($ids as $id) {
            if (wp_delete_post($id, true)) {
                $deleted++;
            }
        }

        WP_CLI::success(sprintf('Deleted %d of %d Pressreview Item(s).', $deleted, $count));
    }
}
