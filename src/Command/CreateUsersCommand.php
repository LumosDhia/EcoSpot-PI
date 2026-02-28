<?php

declare(strict_types=1);

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
    name: 'app:create-users',
    description: 'Create hardcoded admin and NGO users if they do not exist',
)]
class CreateUsersCommand extends Command
{
    private const HARDCODED_ADMIN_EMAIL = 'admin@ecospot.local';
    private const HARDCODED_ADMIN_PASSWORD = 'admin123';
    private const HARDCODED_NGO_EMAIL = 'ngo@ecospot.local';
    private const HARDCODED_NGO_PASSWORD = 'ngo123';
    private const HARDCODED_NORMAL_EMAIL = 'user@ecospot.local';
    private const HARDCODED_NORMAL_PASSWORD = '123';

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $hasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $repo = $this->em->getRepository(User::class);
        $created = [];

        if ($repo->findOneBy(['emailAddress.email' => self::HARDCODED_ADMIN_EMAIL]) === null) {
            $admin = new User();
            $admin->setEmail(self::HARDCODED_ADMIN_EMAIL);
            $admin->setPassword($this->hasher->hashPassword($admin, self::HARDCODED_ADMIN_PASSWORD));
            $admin->setFirstname('Admin');
            $admin->setLastname('User');
            $admin->setRoles(['ROLE_ADMIN']);
            $admin->setCreatedBy($admin);
            $this->em->persist($admin);
            $created[] = 'Admin: ' . self::HARDCODED_ADMIN_EMAIL . ' / ' . self::HARDCODED_ADMIN_PASSWORD;
        }

        if ($repo->findOneBy(['emailAddress.email' => self::HARDCODED_NGO_EMAIL]) === null) {
            $ngo = new User();
            $ngo->setEmail(self::HARDCODED_NGO_EMAIL);
            $ngo->setPassword($this->hasher->hashPassword($ngo, self::HARDCODED_NGO_PASSWORD));
            $ngo->setFirstname('NGO');
            $ngo->setLastname('User');
            $ngo->setRoles(['ROLE_NGO']);
            $ngo->setCreatedBy($ngo);
            $this->em->persist($ngo);
            $created[] = 'NGO: ' . self::HARDCODED_NGO_EMAIL . ' / ' . self::HARDCODED_NGO_PASSWORD;
        }

        if ($repo->findOneBy(['emailAddress.email' => self::HARDCODED_NORMAL_EMAIL]) === null) {
            $normal = new User();
            $normal->setEmail(self::HARDCODED_NORMAL_EMAIL);
            $normal->setPassword($this->hasher->hashPassword($normal, self::HARDCODED_NORMAL_PASSWORD));
            $normal->setFirstname('Normal');
            $normal->setLastname('User');
            $normal->setRoles(['ROLE_USER']);
            $normal->setCreatedBy($normal);
            $this->em->persist($normal);
            $created[] = 'Normal: ' . self::HARDCODED_NORMAL_EMAIL . ' / ' . self::HARDCODED_NORMAL_PASSWORD;
        }

        $this->em->flush();

        if ($created === []) {
            $io->note('Admin and NGO users already exist. Use the credentials below to log in.');
        } else {
            $io->success('Created: ' . implode(' — ', $created));
        }

        $io->table(
            ['Role', 'Email', 'Password'],
            [
                ['Admin', self::HARDCODED_ADMIN_EMAIL, self::HARDCODED_ADMIN_PASSWORD],
                ['NGO', self::HARDCODED_NGO_EMAIL, self::HARDCODED_NGO_PASSWORD],
                ['Normal', self::HARDCODED_NORMAL_EMAIL, self::HARDCODED_NORMAL_PASSWORD],
            ]
        );
        return Command::SUCCESS;
    }
}
