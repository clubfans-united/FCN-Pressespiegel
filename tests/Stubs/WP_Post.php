<?php

/**
 * Minimal stand-in for the WordPress WP_Post class.
 */
class WP_Post
{
    public int $ID = 0;

    public string $post_type = '';

    public string $post_date = '';

    public function __construct(array $props = [])
    {
        foreach ($props as $key => $value) {
            $this->$key = $value;
        }
    }
}
