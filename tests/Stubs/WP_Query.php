<?php

/**
 * Minimal stand-in for the WordPress WP_Query class.
 *
 * Tests assign a closure to WP_Query::$handler that receives the query args
 * and returns ['found_posts' => int, 'posts' => array]; the base TestCase
 * resets it between tests.
 */
class WP_Query
{
    public static ?Closure $handler = null;

    public int $found_posts = 0;

    public array $posts = [];

    public function __construct(public array $query = [])
    {
        if (self::$handler === null) {
            return;
        }

        $result = (self::$handler)($query);
        $this->found_posts = $result['found_posts'] ?? 0;
        $this->posts = $result['posts'] ?? [];
    }
}
