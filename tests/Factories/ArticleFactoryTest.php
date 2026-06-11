<?php

namespace FCNPressespiegel\Tests\Factories;

use DateTime;
use FCNPressespiegel\Factories\ArticleFactory;
use FCNPressespiegel\Tests\TestCase;

class ArticleFactoryTest extends TestCase
{
    public function testCreateBuildsArticleFromUrlParts(): void
    {
        $created = new DateTime('2026-06-10 10:00:00');

        $article = ArticleFactory::create(
            'Club siegt',
            'https://www.example.com/artikel-1',
            'Ein Auszug',
            $created,
        );

        $this->assertSame('Club siegt | example.com', $article->getDisplayTitle());
        $this->assertSame('Club siegt | www.example.com', $article->getOriginalTitle());
        $this->assertSame('https://www.example.com/artikel-1', $article->getUrl());
        $this->assertSame('www.example.com', $article->getHost());
        $this->assertSame('Ein Auszug', $article->getExcerpt());
        $this->assertSame($created, $article->getCreated());
        $this->assertSame('', $article->getSourceUrl());
    }
}
