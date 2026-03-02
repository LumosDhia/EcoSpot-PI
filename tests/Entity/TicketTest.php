<?php

namespace App\Tests\Entity;

use App\Entity\Ticket;
use App\Entity\User;
use App\Enum\TicketStatus;
use App\Enum\TicketPriority;
use App\Enum\ActionDomain;
use PHPUnit\Framework\TestCase;

class TicketTest extends TestCase
{
    public function testInstantiation(): void
    {
        $ticket = new Ticket();
        $this->assertInstanceOf(Ticket::class, $ticket);
        $this->assertEquals(TicketStatus::PENDING, $ticket->getStatus());
        $this->assertCount(0, $ticket->getConsignes());
        $this->assertFalse($ticket->isAchieved());
        $this->assertFalse($ticket->isSpam());
    }

    public function testSetGetTitle(): void
    {
        $ticket = new Ticket();
        $ticket->setTitle('Waste detected');
        $this->assertEquals('Waste detected', $ticket->getTitle());
    }

    public function testSetGetDescription(): void
    {
        $ticket = new Ticket();
        $ticket->setDescription('There is a lot of waste near the park.');
        $this->assertEquals('There is a lot of waste near the park.', $ticket->getDescription());
    }

    public function testSetGetLocation(): void
    {
        $ticket = new Ticket();
        $ticket->setLocation('Central Park');
        $this->assertEquals('Central Park', $ticket->getLocation());
    }

    public function testCoordinates(): void
    {
        $ticket = new Ticket();
        $ticket->setLatitude(48.8566);
        $ticket->setLongitude(2.3522);
        
        $this->assertEquals(48.8566, $ticket->getLatitude());
        $this->assertEquals(2.3522, $ticket->getLongitude());
    }

    public function testEnums(): void
    {
        $ticket = new Ticket();
        
        $ticket->setStatus(TicketStatus::PUBLISHED);
        $this->assertEquals(TicketStatus::PUBLISHED, $ticket->getStatus());
        
        $ticket->setPriority(TicketPriority::HIGH);
        $this->assertEquals(TicketPriority::HIGH, $ticket->getPriority());
        
        $ticket->setDomain(ActionDomain::WASTE);
        $this->assertEquals(ActionDomain::WASTE, $ticket->getDomain());
    }

    public function testUserRelation(): void
    {
        $ticket = new Ticket();
        $user = new User();
        
        $ticket->setUser($user);
        $this->assertSame($user, $ticket->getUser());
    }

    public function testAssignment(): void
    {
        $ticket = new Ticket();
        $ngo = new User();
        
        $ticket->setAssignedNgo($ngo);
        $this->assertSame($ngo, $ticket->getAssignedNgo());
    }

    public function testSpamFlag(): void
    {
        $ticket = new Ticket();
        $this->assertFalse($ticket->isSpam());
        
        $ticket->setIsSpam(true);
        $this->assertTrue($ticket->isSpam());
    }
    public function testSubmitCompletion(): void
    {
        $ticket = new Ticket();
        $user = new User();
        $message = 'I cleaned it up!';
        $image = 'proof.jpg';
        
        $this->assertFalse($ticket->hasCompletionSubmitted());
        $ticket->submitCompletion($user, $message, $image);
        
        $this->assertTrue($ticket->hasCompletionSubmitted());
        $this->assertSame($user, $ticket->getCompletedBy());
        $this->assertEquals($message, $ticket->getCompletionMessage());
        $this->assertEquals($image, $ticket->getCompletionImage());
        $this->assertInstanceOf(\DateTimeImmutable::class, $ticket->getCompletionSubmittedAt());
    }

    public function testMarkAsAchieved(): void
    {
        $ticket = new Ticket();
        $ticket->setStatus(TicketStatus::PUBLISHED);
        
        $this->assertFalse($ticket->isAchieved());
        $ticket->markAsAchieved();
        
        $this->assertTrue($ticket->isAchieved());
        $this->assertEquals(TicketStatus::COMPLETED, $ticket->getStatus());
        $this->assertInstanceOf(\DateTimeImmutable::class, $ticket->getAchievedAt());
    }
}


