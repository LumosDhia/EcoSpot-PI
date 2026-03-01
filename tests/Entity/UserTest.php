<?php

namespace App\Tests\Entity;

use App\Entity\User;
use App\Entity\Ticket;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

class UserTest extends TestCase
{
    public function testInstantiation(): void
    {
        $user = new User();
        $this->assertInstanceOf(User::class, $user);
        $this->assertInstanceOf(UuidV7::class, $user->getId());
        $this->assertContains('ROLE_USER', $user->getRoles());
        $this->assertCount(0, $user->getTickets());
        $this->assertCount(0, $user->getArticles());
        $this->assertCount(0, $user->getNotifications());
        $this->assertFalse($user->isFaceEnrolled());
    }

    public function testSetGetEmail(): void
    {
        $user = new User();
        $email = 'test@example.com';
        $user->setEmail($email);
        $this->assertEquals($email, $user->getEmail());
        $this->assertEquals($email, $user->getUserIdentifier());
    }

    public function testSetGetRoles(): void
    {
        $user = new User();
        $roles = ['ROLE_ADMIN', 'ROLE_NGO'];
        $user->setRoles($roles);
        
        $returnedRoles = $user->getRoles();
        $this->assertContains('ROLE_ADMIN', $returnedRoles);
        $this->assertContains('ROLE_NGO', $returnedRoles);
        $this->assertContains('ROLE_USER', $returnedRoles);
    }

    public function testSetGetPassword(): void
    {
        $user = new User();
        $password = 'hashed_password';
        $user->setPassword($password);
        $this->assertEquals($password, $user->getPassword());
    }

    public function testSetGetNames(): void
    {
        $user = new User();
        $user->setFirstname('John');
        $user->setLastname('Doe');
        
        $this->assertEquals('John', $user->getFirstname());
        $this->assertEquals('Doe', $user->getLastname());
    }

    public function testLocationFields(): void
    {
        $user = new User();
        $user->setAddress('123 Street');
        $user->setZipcode('12345');
        $user->setCity('Paris');
        
        $this->assertEquals('123 Street', $user->getAddress());
        $this->assertEquals('12345', $user->getZipcode());
        $this->assertEquals('Paris', $user->getCity());
    }

    public function testTicketsCollection(): void
    {
        $user = new User();
        $ticket = new Ticket();
        
        $user->addTicket($ticket);
        $this->assertCount(1, $user->getTickets());
        $this->assertSame($user, $ticket->getUser());
        
        $user->removeTicket($ticket);
        $this->assertCount(0, $user->getTickets());
    }
}
