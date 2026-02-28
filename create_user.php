<?php

use App\Kernel;
use App\Entity\User;
use Symfony\Component\Dotenv\Dotenv;

require __DIR__.'/vendor/autoload.php';

if (file_exists(__DIR__.'/.env')) {
    (new Dotenv())->bootEnv(__DIR__.'/.env');
}

$kernel = new Kernel($_SERVER['APP_ENV'] ?? 'dev', (bool) ($_SERVER['APP_DEBUG'] ?? true));
$kernel->boot();

$container = $kernel->getContainer()->get('test.service_container'); // Or direct container if standard
if (!$container->has('doctrine.orm.entity_manager')) {
    $container = $kernel->getContainer(); // Use the standard one
}

$em = $container->get('doctrine.orm.entity_manager');
$hasher = $container->get('security.user_password_hasher');

$user = $em->getRepository(User::class)->findOneBy(['emailAddress.email' => 'user@ecospot.local']);

if (!$user) {
    $user = new User();
    $user->setEmail('user@ecospot.local');
    $user->setFirstname('Normal');
    $user->setLastname('User');
    $user->setRoles(['ROLE_USER']);
    $user->setPassword(
        $hasher->hashPassword($user, '123')
    );

    $em->persist($user);
    $em->flush();
    echo "User created successfully.\n";
} else {
    echo "User already exists.\n";
}
