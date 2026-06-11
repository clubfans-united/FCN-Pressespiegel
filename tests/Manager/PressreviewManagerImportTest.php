<?php

namespace FCNPressespiegel\Tests\Manager;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Exception;
use FCNPressespiegel\Enum\PostType;
use FCNPressespiegel\Manager\PressreviewManager;
use FCNPressespiegel\Models\Source;
use FCNPressespiegel\Tests\TestCase;
use Laminas\Feed\Reader\Entry\EntryInterface;

class PressreviewManagerImportTest extends TestCase
{
    private const FEED_URL = 'https://example.com/feed';

    public function testImportsNewArticlesFromFeed(): void
    {
        $this->stubImportEnvironment([
            self::FEED_URL => $this->rss([
                ['title' => 'Artikel 1', 'link' => 'https://example.com/artikel-1', 'pubDate' => 'Wed, 10 Jun 2026 10:00:00 +0000'],
                ['title' => 'Artikel 2', 'link' => 'https://example.com/artikel-2', 'pubDate' => 'Wed, 10 Jun 2026 11:00:00 +0000'],
            ]),
        ]);
        Filters\expectApplied('fcnp_sources')->andReturn([new Source(self::FEED_URL)]);

        Functions\expect('wp_insert_post')->twice()->andReturn(101, 102);
        Actions\expectDone('fcnp_after_import_article')->twice();

        $result = (new PressreviewManager())->import();

        $this->assertFalse($result->hasErrors());
        $this->assertCount(2, $result->articles);
        $this->assertSame('https://example.com/artikel-1', $result->articles[0]->getUrl());
        $this->assertSame(self::FEED_URL, $result->articles[0]->getSourceUrl());
    }

    public function testSkipsItemsWithoutDate(): void
    {
        $this->stubImportEnvironment([
            self::FEED_URL => $this->rss([
                ['title' => 'Ohne Datum', 'link' => 'https://example.com/undatiert'],
            ]),
        ]);
        Filters\expectApplied('fcnp_sources')->andReturn([new Source(self::FEED_URL)]);

        Functions\expect('wp_insert_post')->never();

        $result = (new PressreviewManager())->import();

        $this->assertFalse($result->hasErrors());
        $this->assertCount(0, $result->articles);
    }

    public function testSkipsItemsOlderThanNewestExistingArticle(): void
    {
        $this->stubImportEnvironment([
            self::FEED_URL => $this->rss([
                ['title' => 'Alt', 'link' => 'https://example.com/alt', 'pubDate' => 'Tue, 09 Jun 2026 10:00:00 +0000'],
                ['title' => 'Neu', 'link' => 'https://example.com/neu', 'pubDate' => 'Thu, 11 Jun 2026 10:00:00 +0000'],
            ]),
        ]);
        Filters\expectApplied('fcnp_sources')->andReturn([new Source(self::FEED_URL)]);

        // Newest existing pressreview post is from June 10th.
        \WP_Query::$handler = static function (array $query): array {
            if (isset($query['meta_key'])) {
                return ['found_posts' => 0];
            }

            return ['posts' => [7]];
        };
        Functions\when('get_post_field')->justReturn('2026-06-10 12:00:00');

        Functions\expect('wp_insert_post')->once()->andReturn(101);

        $result = (new PressreviewManager())->import();

        $this->assertCount(1, $result->articles);
        $this->assertSame('https://example.com/neu', $result->articles[0]->getUrl());
    }

    public function testSkipsArticlesThatAlreadyExist(): void
    {
        $this->stubImportEnvironment([
            self::FEED_URL => $this->rss([
                ['title' => 'Bekannt', 'link' => 'https://example.com/bekannt', 'pubDate' => 'Wed, 10 Jun 2026 10:00:00 +0000'],
            ]),
        ]);
        Filters\expectApplied('fcnp_sources')->andReturn([new Source(self::FEED_URL)]);

        \WP_Query::$handler = static function (array $query): array {
            if (isset($query['meta_key'])) {
                return ['found_posts' => $query['meta_value'] === 'https://example.com/bekannt' ? 1 : 0];
            }

            return ['posts' => []];
        };

        Functions\expect('wp_insert_post')->never();

        $result = (new PressreviewManager())->import();

        $this->assertCount(0, $result->articles);
    }

    public function testDeduplicatesItemsAcrossFeeds(): void
    {
        $item = ['title' => 'Doppelt', 'link' => 'https://example.com/doppelt', 'pubDate' => 'Wed, 10 Jun 2026 10:00:00 +0000'];
        $this->stubImportEnvironment([
            'https://example.com/feed-a' => $this->rss([$item]),
            'https://example.com/feed-b' => $this->rss([$item]),
        ]);
        Filters\expectApplied('fcnp_sources')->andReturn([
            new Source('https://example.com/feed-a'),
            new Source('https://example.com/feed-b'),
        ]);

        Functions\expect('wp_insert_post')->once()->andReturn(101);

        $result = (new PressreviewManager())->import();

        $this->assertCount(1, $result->articles);
        $this->assertSame('https://example.com/feed-a', $result->articles[0]->getSourceUrl());
    }

    public function testAppliesSourceFilter(): void
    {
        $this->stubImportEnvironment([
            self::FEED_URL => $this->rss([
                ['title' => '1. FC Nürnberg: Sieg im Derby', 'link' => 'https://example.com/fcn', 'pubDate' => 'Wed, 10 Jun 2026 10:00:00 +0000'],
                ['title' => 'Hertha BSC: Trainerwechsel', 'link' => 'https://example.com/hertha', 'pubDate' => 'Wed, 10 Jun 2026 11:00:00 +0000'],
            ]),
        ]);
        Filters\expectApplied('fcnp_sources')->andReturn([
            new Source(
                self::FEED_URL,
                static fn(EntryInterface $item): bool => str_starts_with(trim($item->getTitle()), '1. FC Nürnberg'),
            ),
        ]);

        Functions\expect('wp_insert_post')->once()->andReturn(101);

        $result = (new PressreviewManager())->import();

        $this->assertCount(1, $result->articles);
        $this->assertSame('https://example.com/fcn', $result->articles[0]->getUrl());
    }

    public function testCollectsFeedErrors(): void
    {
        $this->stubImportEnvironment([
            self::FEED_URL => 'kein gültiges XML',
        ]);
        Filters\expectApplied('fcnp_sources')->andReturn([new Source(self::FEED_URL)]);

        Actions\expectDone('fcnp_feed_exception')->once();
        Actions\expectDone('fcnp_import_failed')->once();
        $this->expectErrorLog();

        $result = (new PressreviewManager())->import();

        $this->assertTrue($result->hasFeedErrors());
        $this->assertArrayHasKey(self::FEED_URL, $result->feedErrors);
        $this->assertCount(0, $result->articles);
    }

    public function testCollectsArticleErrorsWhenInsertFails(): void
    {
        $this->stubImportEnvironment([
            self::FEED_URL => $this->rss([
                ['title' => 'Artikel', 'link' => 'https://example.com/artikel', 'pubDate' => 'Wed, 10 Jun 2026 10:00:00 +0000'],
            ]),
        ]);
        Filters\expectApplied('fcnp_sources')->andReturn([new Source(self::FEED_URL)]);

        Functions\when('wp_insert_post')->alias(static function (): never {
            throw new Exception('insert failed');
        });

        Actions\expectDone('fcnp_import_article_failed')->once();
        $this->expectErrorLog();

        $result = (new PressreviewManager())->import();

        $this->assertTrue($result->hasArticleErrors());
        $this->assertSame('insert failed', $result->articleErrors['https://example.com/artikel']);
    }

    public function testSilentlySkipsArticlesInsertedConcurrently(): void
    {
        $this->stubImportEnvironment([
            self::FEED_URL => $this->rss([
                ['title' => 'Race', 'link' => 'https://example.com/race', 'pubDate' => 'Wed, 10 Jun 2026 10:00:00 +0000'],
            ]),
        ]);
        Filters\expectApplied('fcnp_sources')->andReturn([new Source(self::FEED_URL)]);

        // First existence check (feed scan) finds nothing, the second one
        // (inside importArticle) finds the post – as if a concurrent request
        // inserted it in between.
        $existsChecks = 0;
        \WP_Query::$handler = static function (array $query) use (&$existsChecks): array {
            if (isset($query['meta_key'])) {
                $existsChecks++;
                return ['found_posts' => $existsChecks > 1 ? 1 : 0];
            }

            return ['posts' => []];
        };

        Functions\expect('wp_insert_post')->never();

        $result = (new PressreviewManager())->import();

        $this->assertFalse($result->hasErrors());
    }

    /**
     * Stubs everything import() needs from WordPress; feed bodies are served
     * from the given url => xml map.
     */
    private function stubImportEnvironment(array $feeds): void
    {
        Functions\when('get_file_data')->justReturn(['Version' => '1.0.0']);
        Functions\when('wp_cache_get')->justReturn(false);
        Functions\when('wp_cache_set')->justReturn(true);
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('wp_remote_get')->alias(static fn(string $url): array => ['body' => $feeds[$url] ?? '']);
        Functions\when('wp_remote_retrieve_body')->alias(static fn(array $response): string => $response['body']);
        Functions\when('update_post_meta')->justReturn(true);
        Functions\when('update_option')->justReturn(true);
        Functions\when('wp_timezone')->alias(static fn(): \DateTimeZone => new \DateTimeZone('UTC'));
        Functions\when('get_post_status')->justReturn('publish');
        Functions\when('get_post')->alias(
            static fn(int $id): \WP_Post => new \WP_Post(['ID' => $id, 'post_type' => PostType::PRESSREVIEW]),
        );
        Functions\when('get_post_type')->justReturn(PostType::PRESSREVIEW);
        Functions\when('get_post_meta')->justReturn('');
    }

    private function rss(array $items): string
    {
        $itemsXml = '';

        foreach ($items as $item) {
            $itemsXml .= '<item>';
            $itemsXml .= '<title>' . htmlspecialchars($item['title']) . '</title>';
            $itemsXml .= '<link>' . htmlspecialchars($item['link']) . '</link>';

            if (isset($item['pubDate'])) {
                $itemsXml .= '<pubDate>' . $item['pubDate'] . '</pubDate>';
            }

            $itemsXml .= '</item>';
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<rss version="2.0"><channel>'
            . '<title>Testfeed</title><link>https://example.com</link><description>Test</description>'
            . $itemsXml
            . '</channel></rss>';
    }
}
