<?php

namespace FCNPressespiegel\Tests;

use Brain\Monkey;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

abstract class TestCase extends PHPUnitTestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Monkey\Functions\stubTranslationFunctions();
        \WP_Query::$handler = static fn(array $query): array => ['found_posts' => 0, 'posts' => []];
    }

    protected function tearDown(): void
    {
        \WP_Query::$handler = null;
        Monkey\tearDown();
        parent::tearDown();
    }
}
