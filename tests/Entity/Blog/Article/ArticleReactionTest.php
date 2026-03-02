<?php

namespace App\Tests\Entity\Blog\Article;

use App\Entity\Blog\Article\ArticleReaction;
use App\Entity\Blog\Article\Article;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class ArticleReactionTest extends TestCase
{
    public function testInstantiation(): void
    {
        $reaction = new ArticleReaction();
        $this->assertInstanceOf(ArticleReaction::class, $reaction);
    }

    public function testSetUser(): void
    {
        $reaction = new ArticleReaction();
        $user = new User();
        $reaction->setUser($user);
        $this->assertSame($user, $reaction->getUser());
    }

    public function testSetArticle(): void
    {
        $reaction = new ArticleReaction();
        $article = new Article();
        $reaction->setArticle($article);
        $this->assertSame($article, $reaction->getArticle());
    }

    public function testSetType(): void
    {
        $reaction = new ArticleReaction();
        $reaction->setType(ArticleReaction::TYPE_LIKE);
        $this->assertEquals(ArticleReaction::TYPE_LIKE, $reaction->getType());

        $reaction->setType(ArticleReaction::TYPE_DISLIKE);
        $this->assertEquals(ArticleReaction::TYPE_DISLIKE, $reaction->getType());
    }

    public function testInvalidType(): void
    {
        $reaction = new ArticleReaction();
        $this->expectException(\InvalidArgumentException::class);
        $reaction->setType('invalid');
    }

    public function testGetIdIsNullByDefault(): void
    {
        $reaction = new ArticleReaction();
        $this->assertNull($reaction->getId());
    }

    public function testTypeConstants(): void
    {
        $this->assertEquals('like', ArticleReaction::TYPE_LIKE);
        $this->assertEquals('dislike', ArticleReaction::TYPE_DISLIKE);
    }
}
