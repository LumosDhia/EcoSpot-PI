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

    public function testReadingTime(): void
    {
        $article = new Article();
        $article->setContent('One two three four five'); // 5 words
        // wordCount / 200 = 0.025, ceil = 1
        $this->assertEquals(1, $article->getReadingTime());

        $longContent = str_repeat('word ', 401);
        $article->setContent($longContent);
        // 401 words / 200 = 2.005, ceil = 3
        $this->assertEquals(3, $article->getReadingTime());
    }

    public function testPublicationStatus(): void
    {
        $article = new Article();
        $this->assertEquals('draft', $article->getPublicationStatus());
        $this->assertFalse($article->isPublished());

        $future = new \DateTimeImmutable('+1 day');
        $article->publishAt($future);
        $this->assertEquals('scheduled', $article->getPublicationStatus());
        $this->assertFalse($article->isPublished());

        $past = new \DateTimeImmutable('-1 day');
        $article->publishAt($past);
        $this->assertEquals('published', $article->getPublicationStatus());
        $this->assertTrue($article->isPublished());
    }

    public function testSeoFields(): void
    {
        $article = new Article();
        $article->setSeoTitle('SEO Title');
        $article->setSeoDescription('SEO Desc');
        $article->setSeoKeywords('SEO, Keywords');

        $this->assertEquals('SEO Title', $article->getSeoTitle());
        $this->assertEquals('SEO Desc', $article->getSeoDescription());
        $this->assertEquals('SEO, Keywords', $article->getSeoKeywords());
    }

    public function testIsWrittenByNgo(): void
    {
        $article = new Article();
        $this->assertFalse($article->isWrittenByNgo());

        $user = new User();
        $user->setRoles(['ROLE_USER']);
        $article->setWriter($user);
        $this->assertFalse($article->isWrittenByNgo());

        $ngo = new User();
        $ngo->setRoles(['ROLE_NGO']);
        $article->setWriter($ngo);
        $this->assertTrue($article->isWrittenByNgo());
    }

    public function testSetViews(): void
    {
        $article = new Article();
        $article->setViews(100);
        $this->assertEquals(100, $article->getViews());
    }
}

