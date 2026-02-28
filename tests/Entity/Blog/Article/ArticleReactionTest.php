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

    public function testSetGetType(): void
    {
        $reaction = new ArticleReaction();
        $type = 'like';
        
        $reaction->setType($type);
        $this->assertEquals($type, $reaction->getType());
    }

    public function testSetGetArticle(): void
    {
        $reaction = new ArticleReaction();
        $article = new Article();
        
        $reaction->setArticle($article);
        $this->assertEquals($article, $reaction->getArticle());
    }

    public function testSetGetUser(): void
    {
        $reaction = new ArticleReaction();
        $user = new User();
        
        $reaction->setUser($user);
        $this->assertEquals($user, $reaction->getUser());
    }
}
