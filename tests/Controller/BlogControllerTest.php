<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BlogControllerTest extends WebTestCase
{
    public function testBlogIndex(): void
    {
        $client = static::createClient();
        $client->request('GET', '/blog');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.container'); 
    }

    public function testBlogSearch(): void
    {
        $client = static::createClient();
        $client->request('GET', '/blog?search=nature');

        $this->assertResponseIsSuccessful();
    }
}
