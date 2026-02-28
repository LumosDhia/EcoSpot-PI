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
        $this->assertEquals($user, $comment->getAuthorUser());
        $this->assertFalse($comment->isFlagged());
    }

    public function testSetGetContent(): void
    {
        $comment = new Comment(new User());
        $content = 'Excellent article !';
        $comment->setContent($content);
        $this->assertEquals($content, $comment->getContent());
    }

    public function testSetGetAuthor(): void
    {
        $comment = new Comment(new User());
        $author = 'John Doe';
        $comment->setAuthor($author);
        $this->assertEquals($author, $comment->getAuthor());
    }

    public function testSetGetArticle(): void
    {
        $comment = new Comment(new User());
        $article = new Article();
        $comment->setArticle($article);
        $this->assertEquals($article, $comment->getArticle());
    }

    public function testSetGetFlagged(): void
    {
        $comment = new Comment(new User());
        $this->assertFalse($comment->isFlagged());
        
        $comment->setFlagged(true);
        $this->assertTrue($comment->isFlagged());
    }
}
