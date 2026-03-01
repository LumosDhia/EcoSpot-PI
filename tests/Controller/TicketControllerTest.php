<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TicketControllerTest extends WebTestCase
{
    public function testPublicTicketsPage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/tickets');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h3.display-3', 'Community tickets');
    }

    public function testAchievementsPage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/achievements');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.container'); 
    }
}
