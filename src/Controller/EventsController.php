<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Evenement;
use App\Repository\EvenementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EventsController extends AbstractController
{
    public function __construct(
        private readonly EvenementRepository $evenementRepository,
        private readonly \Knp\Component\Pager\PaginatorInterface $paginator,
        private readonly \Symfony\Component\Mailer\MailerInterface $mailer,
        private readonly \App\Service\LocationService $locationService
    ) {
    }

    #[Route('/events/nearby', name: 'events_nearby', methods: ['GET'], priority: 15)]
    public function nearby(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            $this->addFlash('error', 'You must be logged in to see nearby events.');
            return $this->redirectToRoute('app_login');
        }

        if (!$user->getAddress() || !$user->getCity()) {
            $this->addFlash('warning', 'Please complete your address in your profile to use this feature.');
            return $this->redirectToRoute('events_index');
        }

        $address = sprintf('%s, %s %s', $user->getAddress(), $user->getZipcode() ?? '', $user->getCity());
        $userCoords = $this->locationService->geocode($address);

        if (!$userCoords) {
            $this->addFlash('warning', 'Could not determine your location from your profile address.');
            return $this->redirectToRoute('events_index');
        }

        $allEvents = $this->evenementRepository->findAll();
        $nearbyEvents = [];

        foreach ($allEvents as $event) {
            if ($event->getLatitude() !== null && $event->getLongitude() !== null) {
                $distance = $this->locationService->calculateDistance(
                    $userCoords['lat'],
                    $userCoords['lng'],
                    (float) $event->getLatitude(),
                    (float) $event->getLongitude()
                );
                
                // Let's include all events but sort by distance, maybe show ones within 100km specifically highlighted
                $nearbyEvents[] = [
                    'event' => $event,
                    'distance' => round($distance, 1)
                ];
            }
        }

        // Sort by distance
        usort($nearbyEvents, fn($a, $b) => $a['distance'] <=> $b['distance']);

        return $this->render('events/nearby.html.twig', [
            'nearbyEvents' => $nearbyEvents,
            'userAddress' => $address,
        ]);
    }


    #[Route('/events', name: 'events_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search = $request->query->getString('q', '');
        $search = $search === '' ? null : trim($search);

        $order = $request->query->getString('order', 'DESC');
        if (!in_array($order, ['ASC', 'DESC'], true)) {
            $order = 'DESC';
        }

        $query = $this->evenementRepository->getQuerySearchOrderedByDate($search);
        
        // Apply order if we want to expose it to the user like in the blog
        // For now, searchOrderedByDate is DESC only, let's keep it consistent or fix it later.
        // Actually, let's just paginate the current query.

        $pagination = $this->paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            4 // Events per page

        );

        return $this->render('events/index.html.twig', [
            'pagination' => $pagination,
            'search' => $search ?? '',
            'order' => $order,
        ]);
    }

    #[Route('/events/search', name: 'events_search', methods: ['GET'], priority: 10)]
    public function search(Request $request): JsonResponse
    {
        $q = $request->query->get('q');
        $q = $q === null ? null : trim((string) $q);
        $q = $q === '' ? null : $q;

        $events = $this->evenementRepository->searchOrderedByDate($q);

        $data = array_map(function (Evenement $e) {
            $desc = $e->getDescription();
            $description = \mb_strlen($desc) > 120 ? \mb_substr($desc, 0, 120) . '...' : $desc;
            return [
                'id' => $e->getId(),
                'nom' => $e->getNom(),
                'description' => $description,
                'lieu' => $e->getLieu(),
                'dateDebut' => $e->getDateDebut()?->format('d M Y') ?? '—',
                'capacite' => $e->getCapacite(),
                'image' => $e->getImage(),
                'latitude' => $e->getLatitude(),
                'longitude' => $e->getLongitude(),
                'sponsorsCount' => $e->getSponsors()->count(),
                'showUrl' => $this->generateUrl('events_show', ['slug' => $e->getSlug()]),
            ];
        }, $events);


        return new JsonResponse(['events' => $data]);
    }

    #[Route('/events/{slug}', name: 'events_show', methods: ['GET'])]
    public function show(Evenement $event): Response
    {
        $nearbyEvents = [];
        $user = $this->getUser();
        
        if ($user instanceof \App\Entity\User && $user->getAddress() && $user->getCity()) {
            $address = sprintf('%s, %s %s', $user->getAddress(), $user->getZipcode() ?? '', $user->getCity());
            $userCoords = $this->locationService->geocode($address);
            
            if ($userCoords) {
                $allEvents = $this->evenementRepository->findAll();
                foreach ($allEvents as $otherEvent) {
                    if ($otherEvent->getId() !== $event->getId() && $otherEvent->getLatitude() !== null) {
                        $distance = $this->locationService->calculateDistance(
                            $userCoords['lat'],
                            $userCoords['lng'],
                            (float) $otherEvent->getLatitude(),
                            (float) $otherEvent->getLongitude()
                        );
                        
                        // Limit to top 3 nearby events within 100km
                        if ($distance <= 100) {
                            $nearbyEvents[] = [
                                'event' => $otherEvent,
                                'distance' => round($distance, 1)
                            ];
                        }
                    }
                }
                usort($nearbyEvents, fn($a, $b) => $a['distance'] <=> $b['distance']);
                $nearbyEvents = array_slice($nearbyEvents, 0, 3);
            }
        }

        return $this->render('events/show.html.twig', [
            'event' => $event,
            'isParticipating' => $this->getUser() ? $event->getParticipants()->contains($this->getUser()) : false,
            'nearbyEvents' => $nearbyEvents,
        ]);
    }


    #[Route('/events/{slug}/participate', name: 'events_participate', methods: ['POST'])]
    public function participate(Evenement $event): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            $this->addFlash('error', 'You must be logged in to participate.');
            return $this->redirectToRoute('app_login');
        }

        if ($event->getParticipants()->contains($user)) {
            $this->addFlash('info', 'You are already participating in this event.');
            return $this->redirectToRoute('events_show', ['slug' => $event->getSlug()]);
        }

        if ($event->getCapacite() <= 0) {
            $this->addFlash('error', 'Sorry, this event is already full.');
            return $this->redirectToRoute('events_show', ['slug' => $event->getSlug()]);
        }

        $event->addParticipant($user);
        $event->setCapacite($event->getCapacite() - 1);
        
        $this->evenementRepository->save($event, true);

        // Send confirmation email
        try {
            $email = (new \Symfony\Bridge\Twig\Mime\TemplatedEmail())
                ->from(new \Symfony\Component\Mime\Address('ecospot076@gmail.com', 'EcoSpot Team'))
                ->to($user->getEmail())

                ->subject('Confirmation: You\'re attending ' . $event->getNom())
                ->htmlTemplate('emails/event_participation_confirmation.html.twig')
                ->context([
                    'user' => $user,
                    'event' => $event,
                ]);

            $this->mailer->send($email);
        } catch (\Exception $e) {
            $this->addFlash('warning', 'Participation saved, but we couldn\'t send the confirmation email at this moment.');
        }


        $this->addFlash('success', 'You are now participating in this event! A confirmation email has been sent.');
        return $this->redirectToRoute('events_show', ['slug' => $event->getSlug()]);

    }

    #[Route('/events/{slug}/unparticipate', name: 'events_unparticipate', methods: ['POST'])]
    public function unparticipate(Evenement $event): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            return $this->redirectToRoute('app_login');
        }

        if (!$event->getParticipants()->contains($user)) {
            return $this->redirectToRoute('events_show', ['slug' => $event->getSlug()]);
        }

        $event->removeParticipant($user);
        $event->setCapacite($event->getCapacite() + 1);
        
        $this->evenementRepository->save($event, true);

        $this->addFlash('success', 'You have withdrawn your participation.');
        return $this->redirectToRoute('events_show', ['slug' => $event->getSlug()]);
    }
}

