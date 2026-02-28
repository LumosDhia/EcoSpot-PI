<?php

namespace App\Tests\Entity\Blog\Article;

use App\Entity\Blog\Article\Tag;
use PHPUnit\Framework\TestCase;

class TagTest extends TestCase
{
    public function testInstantiation(): void
    {
        $tag = new Tag();
        $this->assertInstanceOf(Tag::class, $tag);
        $this->assertCount(0, $tag->getArticles());
    }

    public function testSetGetName(): void
    {
        $tag = new Tag();
        $name = 'Recyclage';
        $tag->setName($name);
        $this->assertEquals($name, $tag->getName());
    }

    public function testToString(): void
    {
        $tag = new Tag();
        $name = 'Planète';
        $tag->setName($name);
        $this->assertEquals($name, (string) $tag);
    }
}
