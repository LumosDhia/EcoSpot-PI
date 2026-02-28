<?php

namespace App\Tests\Entity\Blog\Article;

use App\Entity\Blog\Article\Category;
use App\Entity\Blog\Article\Article;
use PHPUnit\Framework\TestCase;

class CategoryTest extends TestCase
{
    public function testInstantiation(): void
    {
        $category = new Category();
        $this->assertInstanceOf(Category::class, $category);
        $this->assertCount(0, $category->getArticles());
    }

    public function testSetGetName(): void
    {
        $category = new Category();
        $name = 'Nature';
        $category->setName($name);
        $this->assertEquals($name, $category->getName());
    }

    public function testToString(): void
    {
        $category = new Category();
        $name = 'Environnement';
        $category->setName($name);
        $this->assertEquals($name, (string) $category);
    }
}
