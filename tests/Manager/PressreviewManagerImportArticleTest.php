<?php

namespace FCNPressespiegel\Tests\Manager;

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use DateTime;
use FCNPressespiegel\Enum\PostType;
use FCNPressespiegel\Enum\PressreviewMeta;
use FCNPressespiegel\Exceptions\DuplicatePressreviewPostException;
use FCNPressespiegel\Factories\ArticleFactory;
use FCNPressespiegel\Manager\PressreviewManager;
use FCNPressespiegel\Tests\TestCase;
use Mockery;

class PressreviewManagerImportArticleTest extends TestCase
{
    public function testThrowsOnDuplicateArticle(): void
    {
        $this->stubArticleExistsCache();
        \WP_Query::$handler = static fn(array $query): array => ['found_posts' => 1];

        $article = ArticleFactory::create('Titel', 'https://example.com/a', '', new DateTime());

        $this->expectException(DuplicatePressreviewPostException::class);

        (new PressreviewManager())->importArticle($article);
    }

    public function testImportsBookmarkletArticleWithTagsAndWithoutSourceUrl(): void
    {
        $this->stubArticleExistsCache();
        $this->stubPressreviewLoad(42, 'https://example.com/a');

        $article = ArticleFactory::create(
            'Titel',
            'https://example.com/a',
            'Ein Auszug',
            new DateTime('2026-06-10 09:30:00'),
        );

        Functions\expect('wp_insert_post')
            ->once()
            ->with(Mockery::subset([
                'post_title' => 'Titel | example.com',
                'post_content' => 'Ein Auszug',
                'post_type' => PostType::PRESSREVIEW,
                'post_date' => '2026-06-10 09:30:00',
            ]))
            ->andReturn(42);

        Functions\expect('wp_set_object_terms')
            ->once()
            ->with(42, ['fcn'], 'post_tag');

        // Only ARTICLE_URL may be written – a SOURCE_URL call would not match
        // this expectation and fail the test.
        Functions\expect('update_post_meta')
            ->once()
            ->with(42, PressreviewMeta::ARTICLE_URL->value, 'https://example.com/a');

        Actions\expectDone('fcnp_after_import_article')
            ->once()
            ->with($article, 42);

        $post = (new PressreviewManager())->importArticle($article, ['fcn']);

        $this->assertSame(42, $post->getPostId());
        $this->assertSame('https://example.com/a', $post->getUrl());
    }

    public function testImportsFeedArticleWithSourceUrlAndWithoutTags(): void
    {
        $this->stubArticleExistsCache();
        $this->stubPressreviewLoad(43, 'https://example.com/b');

        $article = ArticleFactory::create('Titel', 'https://example.com/b', '', new DateTime())
            ->setSourceUrl('https://example.com/feed');

        Functions\expect('wp_insert_post')->once()->andReturn(43);
        Functions\expect('wp_set_object_terms')->never();

        Functions\expect('update_post_meta')
            ->once()
            ->with(43, PressreviewMeta::ARTICLE_URL->value, 'https://example.com/b');
        Functions\expect('update_post_meta')
            ->once()
            ->with(43, PressreviewMeta::SOURCE_URL->value, 'https://example.com/feed');

        Actions\expectDone('fcnp_after_import_article')->once();

        (new PressreviewManager())->importArticle($article);
    }

    private function stubArticleExistsCache(): void
    {
        Functions\when('wp_cache_get')->justReturn(false);
        Functions\when('wp_cache_set')->justReturn(true);
    }

    private function stubPressreviewLoad(int $postId, string $url): void
    {
        Functions\when('get_post_status')->justReturn('publish');
        Functions\when('get_post')->alias(
            static fn(int $id): \WP_Post => new \WP_Post(['ID' => $id, 'post_type' => PostType::PRESSREVIEW]),
        );
        Functions\when('get_post_type')->justReturn(PostType::PRESSREVIEW);
        Functions\when('get_post_meta')->justReturn($url);
    }
}
