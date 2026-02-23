<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-test-user',
    description: 'Creates a hardcoded test user account',
)]
class CreateTestUserCommand extends Command
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

        $userData = [
            'email' => 'user@ecospot.local',
            'roles' => ['ROLE_USER'],
            'password' => 'user123',
            'firstname' => 'Test',
            'lastname' => 'User'
        ];

        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $userData['email']]);
        if ($existingUser) {
            $io->warning(sprintf('User with email "%s" already exists.', $userData['email']));
            return Command::SUCCESS;
        }

        $user = new User();
        $user->setEmail($userData['email']);
        $user->setRoles($userData['roles']);
        $user->setFirstname($userData['firstname']);
        $user->setLastname($userData['lastname']);
        
        // Some users might have other required fields depending on the Entity definition
        // Let's check User entity fields if needed, but usually these are the basics.

        $hashedPassword = $this->passwordHasher->hashPassword($user, $userData['password']);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('User %s created successfully with password: %s', $userData['email'], $userData['password']));

        return Command::SUCCESS;
    }
}
