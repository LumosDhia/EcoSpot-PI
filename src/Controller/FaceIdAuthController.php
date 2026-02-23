<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\FaceRecognitionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class FaceIdAuthController extends AbstractController
{
    private $faceService;
    private $entityManager;
    private $security;

    public function __construct(
        FaceRecognitionService $faceService,
        EntityManagerInterface $entityManager,
        Security $security
    ) {
        $this->faceService = $faceService;
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    #[Route(path: '/face-login', name: 'face_login', methods: ['GET'])]
    public function faceLogin(): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('home');
        }
        return $this->render('security/face_login.html.twig');
    }

    #[Route(path: '/face-login/verify', name: 'face_verify', methods: ['POST'])]
    public function verify(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $imageBase64 = $data['image'] ?? null;

        if (!$imageBase64) {
            return new JsonResponse(['status' => 'error', 'message' => 'No image provided'], 400);
        }

        $userId = $this->faceService->recognizeFace($imageBase64);

        if (!$userId) {
            return new JsonResponse(['status' => 'error', 'message' => 'Face not recognized'], 401);
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $userId]);
        
        if (!$user) {
            return new JsonResponse(['status' => 'error', 'message' => 'User not found'], 404);
        }

        $request->attributes->set('face_user_id', $userId);
        
        try {
            $this->security->login($user, 'App\Security\FaceAuthenticator', 'main');
            return new JsonResponse(['status' => 'success', 'redirect' => $this->generateUrl('home')]);
        } catch (\Exception $e) {
            return new JsonResponse(['status' => 'error', 'message' => 'Authentication failed: ' . $e->getMessage()], 500);
        }
    }

    #[Route(path: '/face-enroll', name: 'face_enroll_page', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function faceEnrollPage(): Response
    {
        return $this->render('security/face_enroll.html.twig');
    }

    #[Route(path: '/face-enroll/save', name: 'face_enroll_save', methods: ['POST'])]
    public function enrollSave(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $imageBase64 = $data['image'] ?? null;
        $email = $data['email'] ?? null;

        $user = $this->getUser();
        
        // If logged in, use the user's email
        if ($user instanceof User) {
            $email = $user->getEmail();
        }

        if (!$imageBase64) {
            return new JsonResponse(['status' => 'error', 'message' => 'No image provided'], 400);
        }

        if (!$email) {
            return new JsonResponse(['status' => 'error', 'message' => 'Email is required for enrollment'], 400);
        }

        $result = $this->faceService->enrollFace($imageBase64, $email);

        if (isset($result['status']) && $result['status'] === 'success') {
            // If user exists in DB, mark them as enrolled
            $dbUser = $user instanceof User ? $user : $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($dbUser) {
                $dbUser->setFaceEnrolled(true);
                $this->entityManager->flush();
            }
            return new JsonResponse(['status' => 'success', 'message' => 'Face enrolled successfully']);
        }

        return new JsonResponse(['status' => 'error', 'message' => $result['message'] ?? 'Enrollment failed'], 500);
    }
}
