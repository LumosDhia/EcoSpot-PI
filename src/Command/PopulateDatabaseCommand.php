<?php

namespace App\Command;

use App\Entity\Evenement;
use App\Entity\Ticket;
use App\Entity\User;
use App\Enum\TicketStatus;
use App\Enum\TicketPriority;
use App\Enum\ActionDomain;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:populate-database',
    description: 'Populate database with demo Users, Events and Tickets'
)]
class PopulateDatabaseCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Populating database...');

        // ===============================
        // 1️⃣ USERS
        // ===============================
        $io->section('Creating Users');

        $users = [];
        $userData = [
            ['email' => 'admin@ecospot.local', 'role' => 'ROLE_ADMIN', 'password' => 'admin123'],
            ['email' => 'ngo@ecospot.local', 'role' => 'ROLE_NGO', 'password' => 'ngo123'],
            ['email' => 'user@ecospot.local', 'role' => 'ROLE_USER', 'password' => 'user123'],
            ['email' => 'alice@ecospot.local', 'role' => 'ROLE_USER', 'password' => 'password'],
            ['email' => 'bob@ecospot.local', 'role' => 'ROLE_USER', 'password' => 'password'],
        ];

        foreach ($userData as $data) {
            $user = $this->entityManager->getRepository(User::class)
                ->findOneBy(['emailAddress.email' => $data['email']]);

            if (!$user) {
                $user = new User();
                $user->setEmail($data['email']);
                $user->setRoles([$data['role']]);
                $user->setPassword(
                    $this->passwordHasher->hashPassword($user, $data['password'])
                );
                $user->setFirstname(ucfirst(explode('@', $data['email'])[0]));
                $user->setLastname('User');

                $this->entityManager->persist($user);
                $io->text("Created user: {$data['email']}");
            } else {
                $io->text("User already exists: {$data['email']}");
            }

            $users[$data['email']] = $user;
        }

        $this->entityManager->flush();

        // ===============================
        // 2️⃣ EVENTS
        // ===============================
        $io->section('Creating Events');

        $eventData = [
            [
                'nom' => 'Beach Cleanup 2024',
                'desc' => 'Join us for a massive beach cleanup event.',
                'lieu' => 'Sunny Beach',
                'date' => new \DateTime('+1 week'),
                'cap' => 100,
                'img' => 'beach_cleanup.jpg',
                'lat' => 48.6452,
                'lng' => 2.0255
            ],
            [
                'nom' => 'Tree Planting',
                'desc' => 'Help us plant 500 trees.',
                'lieu' => 'Central Park',
                'date' => new \DateTime('+2 weeks'),
                'cap' => 50,
                'img' => 'tree_planting.jpg',
                'lat' => 48.8566,
                'lng' => 2.3522
            ],
        ];

        foreach ($eventData as $data) {
            $event = $this->entityManager->getRepository(Evenement::class)
                ->findOneBy(['nom' => $data['nom']]);

            if (!$event) {
                $event = new Evenement();
                $event->setNom($data['nom']);
                $event->setDescription($data['desc']);
                $event->setLieu($data['lieu']);
                $event->updateDateDebut($data['date']);
                $event->setCapacite($data['cap']);
                $event->setImage($data['img']);
                $event->setLatitude($data['lat']);
                $event->setLongitude($data['lng']);

                $event->setSlug(strtolower(str_replace(' ', '-', $data['nom'])));

                $this->entityManager->persist($event);
                $io->text("Created event: {$data['nom']}");
            }
        }

        $this->entityManager->flush();

        // ===============================
        // 3️⃣ TICKETS
        // ===============================
        $io->section('Creating Tickets');

        $ticketData = [
            [
                'title' => 'Overflowing Trash Can',
                'desc' => 'The trash can at the corner of Main St is overflowing.',
                'status' => TicketStatus::PENDING,
                'priority' => TicketPriority::HIGH,
                'lat' => 48.8566,
                'lon' => 2.3522,
                'user' => $users['user@ecospot.local']
            ],
            [
                'title' => 'Graffiti on Wall',
                'desc' => 'Offensive graffiti on the library wall.',
                'status' => TicketStatus::PUBLISHED,
                'priority' => TicketPriority::MEDIUM,
                'lat' => 48.8606,
                'lon' => 2.3376,
                'user' => $users['alice@ecospot.local']
            ],
            [
                'title' => 'Broken Bench',
                'desc' => 'Park bench is broken and dangerous.',
                'status' => TicketStatus::PUBLISHED,
                'priority' => TicketPriority::LOW,
                'lat' => 48.8529,
                'lon' => 2.3499,
                'user' => $users['bob@ecospot.local']
            ],
            [
                'title' => 'Illegal Dumping',
                'desc' => 'Someone dumped construction waste here.',
                'status' => TicketStatus::REFUSED,
                'priority' => TicketPriority::URGENT,
                'lat' => 48.8584,
                'lon' => 2.2945,
                'user' => $users['user@ecospot.local']
            ],
            [
                'title' => 'Waste in the City Garden',
                'desc' => 'There is a lot of waste dumped in the city garden near the fountain. It looks very bad.',
                'status' => TicketStatus::PUBLISHED,
                'priority' => TicketPriority::MEDIUM,
                'domain' => ActionDomain::GREEN_SPACES,
                'lat' => 48.8166,
                'lon' => 2.3122,
                'user' => $users['user@ecospot.local']
            ],
            [
                'title' => 'Old tires in the River',
                'desc' => 'I saw several old tires dumped in the river under the bridge. They are polluting the water.',
                'status' => TicketStatus::PUBLISHED,
                'priority' => TicketPriority::HIGH,
                'domain' => ActionDomain::WATER,
                'lat' => 48.8266,
                'lon' => 2.3222,
                'user' => $users['alice@ecospot.local']
            ],
            [
                'title' => 'Dead Fish in the Pond',
                'desc' => 'There are dead fish floating in the pond. This is very concerning for the ecosystem.',
                'status' => TicketStatus::PUBLISHED,
                'priority' => TicketPriority::URGENT,
                'domain' => ActionDomain::WATER,
                'lat' => 48.8366,
                'lon' => 2.3322,
                'user' => $users['bob@ecospot.local']
            ],
            [
                'title' => 'Bad smell near Factory',
                'desc' => 'There is a very bad chemical smell coming from the factory area since yesterday.',
                'status' => TicketStatus::PUBLISHED,
                'priority' => TicketPriority::HIGH,
                'domain' => ActionDomain::AIR,
                'lat' => 48.8466,
                'lon' => 2.3422,
                'user' => $users['user@ecospot.local']
            ],
            [
                'title' => 'Plastic bags on Tree',
                'desc' => 'Many plastic bags are stuck on the branches of the trees in the local park.',
                'status' => TicketStatus::PUBLISHED,
                'priority' => TicketPriority::LOW,
                'domain' => ActionDomain::GREEN_SPACES,
                'lat' => 48.8566,
                'lon' => 2.3522,
                'user' => $users['alice@ecospot.local']
            ],
        ];

        foreach ($ticketData as $data) {
            $existing = $this->entityManager->getRepository(Ticket::class)
                ->findOneBy(['title' => $data['title']]);

            if (!$existing) {
                $ticket = new Ticket();
                $ticket->setTitle($data['title']);
                $ticket->setDescription($data['desc']);
                $ticket->setStatus($data['status']);
                $ticket->setPriority($data['priority']);
                $ticket->setDomain($data['domain'] ?? ActionDomain::WASTE);
                $ticket->setUser($data['user']);
                $ticket->setLatitude($data['lat']);
                $ticket->setLongitude($data['lon']);
                $ticket->setLocation('Local Area ' . rand(1, 10)); // Added missing location since entity requires it
                $ticket->setIsSpam(false);

                $this->entityManager->persist($ticket);
                $io->text("Created ticket: {$data['title']}");
            }
        }

        $this->entityManager->flush();

        $io->success('Database populated successfully!');

        return Command::SUCCESS;
    }
}
