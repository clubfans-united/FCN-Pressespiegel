<?php

namespace FCNPressespiegel\Posts;

use Exception;
use FCNPressespiegel\Enum\PostType;
use FCNPressespiegel\Enum\PressreviewMeta;
use FCNPressespiegel\Exceptions\InvalidPostTypeException;
use FCNPressespiegel\Exceptions\PostNotFoundException;

class Pressreview
{
    private $post_id;
    private $post;
    private $url;

    public function __construct(\WP_Post $post)
    {
        if (get_post_type($post) !== PostType::PRESSREVIEW) {
            throw new InvalidPostTypeException();
        }

        $this->post = $post;
        $this->post_id = $post->ID;
        $this->url = get_post_meta(
            $this->post_id,
            PressreviewMeta::PRESSREVIEW_URL,
            true,
        );
    }

    /**
     * @throws PostNotFoundException
     * @throws InvalidPostTypeException
     */
    public static function createFromPostId(int $postId): Pressreview
    {
        if (false === get_post_status($postId)) {
            throw new PostNotFoundException('Post not found. ID: ' . $postId);
        }

        return new Pressreview(get_post($postId));
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
