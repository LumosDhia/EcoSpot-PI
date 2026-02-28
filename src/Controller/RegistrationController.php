<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\LoginFormAuthenticator;
use App\Service\RecaptchaVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    public function __construct(
        private readonly RecaptchaVerifier $recaptcha,
    ) {
    }

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        Security $security,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('home');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Verify reCAPTCHA
            $captchaToken = $request->request->getString('g-recaptcha-response', '');
            if (!$this->recaptcha->verify($captchaToken)) {
                $this->addFlash('danger', 'Please complete the CAPTCHA verification.');
                return $this->render('registration/register.html.twig', [
                    'registrationForm' => $form,
                ]);
            }

            $user->setPassword(
                $userPasswordHasher->hashPassword($user, $form->get('plainPassword')->getData())
            );

            // Check if face enrollment was successful
            if ($request->request->get('face_enrolled') === '1') {
                $user->setFaceEnrolled(true);
            }

            $entityManager->persist($user);
            $entityManager->flush();

            return $security->login($user, LoginFormAuthenticator::class, 'main') ?: $this->redirectToRoute('home');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
