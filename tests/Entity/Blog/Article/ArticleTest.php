<?php

namespace App\Tests\Entity\Blog\Article;

use App\Entity\Blog\Article\Article;
use App\Entity\Blog\Article\Category;
use App\Entity\Blog\Article\Tag;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class ArticleTest extends TestCase
{
    public function testInstantiation(): void
    {
        $article = new Article();
        $this->assertInstanceOf(Article::class, $article);
        $this->assertEquals(0, $article->getViews());
        $this->assertCount(0, $article->getTags());
        $this->assertCount(0, $article->getComments());
        $this->assertCount(0, $article->getReactions());
        $this->assertFalse($article->isPublished());
    }

    public function testSetGetTitle(): void
    {
        $article = new Article();
        $title = 'Mon super Article';
        $article->setTitle($title);
        $this->assertEquals($title, $article->getTitle());
    }

    public function testSetGetContent(): void
    {
        $article = new Article();
        $content = 'Voici le contenu de mon article de blog.';
        $article->setContent($content);
        $this->assertEquals($content, $article->getContent());
    }

    public function testAddRemoveTag(): void
    {
        $article = new Article();
        $tag = new Tag();
        $tag->setName('Ecologie');

        $article->addTag($tag);
        $this->assertCount(1, $article->getTags());
        $this->assertTrue($article->getTags()->contains($tag));

        $article->removeTag($tag);
        $this->assertCount(0, $article->getTags());
        $this->assertFalse($article->getTags()->contains($tag));
    }

    public function testSetGetCategory(): void
    {
        $article = new Article();
        $category = new Category();
        $category->setName('News');

        $article->setCategory($category);
        $this->assertEquals($category, $article->getCategory());
    }

    public function testSetGetWriter(): void
    {
        $article = new Article();
        $user = new User();
        
        $article->setWriter($user);
        $this->assertEquals($user, $article->getWriter());
    }

    public function testIncrementViews(): void
    {
        $article = new Article();
        $this->assertEquals(0, $article->getViews());
        
        $article->incrementViews();
        $this->assertEquals(1, $article->getViews());
    }

    public function testCommentRelationship(): void
    {
        $article = new Article();
        $comment = new \App\Entity\Blog\Comment\Comment();
        $comment->setContent('Good article!');

        $article->addComment($comment);
        $this->assertCount(1, $article->getComments());
        $this->assertSame($article, $comment->getArticle());

        $article->removeComment($comment);
        $this->assertCount(0, $article->getComments());
    }

    public function testReactions(): void
    {
        $article = new Article();
        $reaction = new \App\Entity\Blog\Article\ArticleReaction();
        $user = new User();
        $reaction->setUser($user);
        $reaction->setType(\App\Entity\Blog\Article\ArticleReaction::TYPE_LIKE);

        $article->getReactions()->add($reaction);
        $reaction->setArticle($article);
        
        $this->assertCount(1, $article->getReactions());
        $this->assertEquals(1, $article->getLikesCount());
        $this->assertEquals(0, $article->getDislikesCount());
        $this->assertSame($reaction, $article->getUserReaction($user));
    }
}
