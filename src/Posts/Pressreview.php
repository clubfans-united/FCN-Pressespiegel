<?php

namespace FCNPressespiegel\Posts;

use Exception;
use FCNPressespiegel\Enum\PostType;
use FCNPressespiegel\Enum\PressreviewMeta;

class Pressreview
{
    private $post_id;
    private $post;
    private $url;

    public function __construct(\WP_Post $post)
    {
        if (get_post_type($post) !== PostType::PRESSREVIEW) {
            throw new Exception('Invalid Post Type');
        }

        $this->post = $post;
        $this->post_id = $post->ID;
        $this->url = get_post_meta(
            $this->post_id,
            PressreviewMeta::PRESSREVIEW_URL,
            true,
        );
    }

    public static function createFromPostId(int $post_id): Pressreview
    {
        if (false === get_post_status($post_id)) {
            throw new Exception('Post not found. ID: ' . $post_id);
        }

        return new Pressreview(get_post($post_id));
    }

    /**
     * @return int
     */
    public function getPostId(): int
    {
        return $this->post_id;
    }

    /**
     * @return \WP_Post
     */
    public function getPost(): \WP_Post
    {
        return $this->post;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }
}
