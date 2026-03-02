<?php

namespace App\Tests\Entity;

use App\Entity\Evenement;
use App\Entity\User;
use App\Entity\Sponsor;
use PHPUnit\Framework\TestCase;

class EvenementTest extends TestCase
{
    public function testInstantiation(): void
    {
        $event = new Evenement();
        $this->assertInstanceOf(Evenement::class, $event);
        $this->assertCount(0, $event->getSponsors());
        $this->assertCount(0, $event->getParticipants());
    }

    public function testSetGetBasicFields(): void
    {
        $event = new Evenement();
        
        $event->setNom('Clean Up Day');
        $this->assertEquals('Clean Up Day', $event->getNom());
        
        $event->setDescription('Cleaning the city park.');
        $this->assertEquals('Cleaning the city park.', $event->getDescription());
        
        $event->setCapacite(50);
        $this->assertEquals(50, $event->getCapacite());
        
        $event->setLieu('City Park');
        $this->assertEquals('City Park', $event->getLieu());
    }

    public function testDateMethods(): void
    {
        $event = new Evenement();
        $start = new \DateTimeImmutable('+1 day');
        $end = new \DateTimeImmutable('+2 days');
        
        // Using the public update methods we created for integrity
        $event->updateDateDebut($start);
        $event->updateDateFin($end);
        
        $this->assertEquals($start, $event->getDateDebut());
        $this->assertEquals($end, $event->getDateFin());
    }

    public function testSponsorCollection(): void
    {
        $event = new Evenement();
        $sponsor = new Sponsor();
        $sponsor->setNom('EcoCorp');
        
        $event->addSponsor($sponsor);
        $this->assertCount(1, $event->getSponsors());
        $this->assertTrue($event->getSponsors()->contains($sponsor));
        
        $event->removeSponsor($sponsor);
        $this->assertCount(0, $event->getSponsors());
    }

    public function testParticipantsCollection(): void
    {
        $event = new Evenement();
        $user = new User();
        
        $event->addParticipant($user);
        $this->assertCount(1, $event->getParticipants());
        $this->assertTrue($event->getParticipants()->contains($user));
        
        $event->removeParticipant($user);
        $this->assertCount(0, $event->getParticipants());
    }

    public function testCoordinates(): void
    {
        $event = new Evenement();
        $event->setLatitude(45.0);
        $event->setLongitude(5.0);
        
        $this->assertEquals(45.0, $event->getLatitude());
        $this->assertEquals(5.0, $event->getLongitude());
    }

    public function testSetGetSlug(): void
    {
        $event = new Evenement();
        $slug = 'clean-up-day';
        $event->setSlug($slug);
        $this->assertEquals($slug, $event->getSlug());
    }

    public function testSetGetImage(): void
    {
        $event = new Evenement();
        $image = 'event.jpg';
        $event->setImage($image);
        $this->assertEquals($image, $event->getImage());
    }

    public function testIdIsNullByDefault(): void
    {
        $event = new Evenement();
        $this->assertNull($event->getId());
    }

    public function testRemoveParticipantNotContains(): void
    {
        $event = new Evenement();
        $user = new User();
        // Just verify it doesn't crash when removing a user that isn't there
        $event->removeParticipant($user);
        $this->assertCount(0, $event->getParticipants());
    }
}
