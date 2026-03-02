<?php

use App\Kernel;
use App\Entity\Evenement;
use App\Service\LocationService;
use Symfony\Component\Dotenv\Dotenv;

require __DIR__.'/vendor/autoload.php';

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();

$em = $container->get('doctrine')->getManager();
$locationService = $container->get(LocationService::class);

echo "Checking Events coordinates...\n";
$events = $em->getRepository(Evenement::class)->findAll();
foreach ($events as $event) {
    echo sprintf("Event: %s, Lat: %s, Lng: %s\n", 
        $event->getNom(), 
        $event->getLatitude() ?? 'NULL', 
        $event->getLongitude() ?? 'NULL'
    );
}

echo "\nTesting geocoding...\n";
$testAddress = "Tunis, Tunisia";
echo "Geocoding '$testAddress'...\n";
$coords = $locationService->geocode($testAddress);
if ($coords) {
    echo sprintf("Result: Lat: %f, Lng: %f\n", $coords['lat'], $coords['lng']);
} else {
    echo "Geocoding failed for '$testAddress'\n";
}

$testAddress2 = "Sousse, Tunisia";
echo "Geocoding '$testAddress2'...\n";
$coords2 = $locationService->geocode($testAddress2);
if ($coords2) {
    echo sprintf("Result: Lat: %f, Lng: %f\n", $coords2['lat'], $coords2['lng']);
} else {
    echo "Geocoding failed for '$testAddress2'\n";
}

if ($coords && $coords2) {
    $distance = $locationService->calculateDistance($coords['lat'], $coords['lng'], $coords2['lat'], $coords2['lng']);
    echo sprintf("Distance between %s and %s: %f km\n", $testAddress, $testAddress2, $distance);
}
