<?php

namespace App\Tests\Entity\Blog\Comment;

use App\Entity\Blog\Comment\Comment;
use App\Entity\Blog\Article\Article;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class CommentTest extends TestCase
{
    public function testInstantiation(): void
    {
        $user = new User();
        $comment = new Comment($user);
        $this->assertInstanceOf(Comment::class, $comment);
        $this->assertSame($user, $comment->getAuthorUser());
        $this->assertInstanceOf(\DateTimeImmutable::class, $comment->getCreatedAt());
        $this->assertFalse($comment->isFlagged());
    }

    public function testSetGetAuthor(): void
    {
        $comment = new Comment();
        $author = 'Alice';
        $comment->setAuthor($author);
        $this->assertEquals($author, $comment->getAuthor());
    }

    public function testSetGetContent(): void
    {
        $comment = new Comment();
        $content = 'Excellent post, thanks for sharing!';
        $comment->setContent($content);
        $this->assertEquals($content, $comment->getContent());
    }

    public function testSetGetArticle(): void
    {
        $comment = new Comment();
        $article = new Article();
        $comment->setArticle($article);
        $this->assertSame($article, $comment->getArticle());
    }

    public function testFlagged(): void
    {
        $comment = new Comment();
        $this->assertFalse($comment->isFlagged());
        $comment->setFlagged(true);
        $this->assertTrue($comment->isFlagged());
    }
}
