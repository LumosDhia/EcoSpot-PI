<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class EventsControllerTest extends WebTestCase
{
    public function testIndexPage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/events');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.container-fluid.blog h1', 'EcoSpot Events');
    }

    public function testSearchJson(): void
    {
        $client = static::createClient();
        $client->request('GET', '/events/search?q=test');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
    }
}
