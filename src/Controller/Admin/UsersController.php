<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\AdminUserCreateType;
use App\Form\UserRoleType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
class UsersController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly \App\Service\NotificationService $notificationService
    ) {
    }

    #[Route('', name: 'admin_users_index', methods: ['GET'])]
    public function index(): Response
    {
        $users = $this->userRepository->findBy([], ['emailAddress.email' => 'ASC']);

        return $this->render('admin/users/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/new', name: 'admin_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(AdminUserCreateType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword(
                $this->passwordHasher->hashPassword($user, $form->get('plainPassword')->getData())
            );
            $type = $form->get('userType')->getData();
            $user->setRoles($this->getRolesFromType($type));
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            $this->addFlash('success', 'User created successfully.');
            return $this->redirectToRoute('admin_users_index');
        }

        return $this->render('admin/users/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user): Response
    {
        /** @var \App\Entity\User $currentUser */
        $currentUser = $this->getUser();
        if ($user->getId() === $currentUser->getId()) {
            $this->addFlash('error', 'You cannot delete your own account.');
            return $this->redirectToRoute('admin_users_index');
        }
        $token = $request->request->getString('_token');
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $token)) {
            $this->entityManager->remove($user);
            $this->entityManager->flush();
            $this->addFlash('success', 'User removed.');
        }
        return $this->redirectToRoute('admin_users_index');
    }

    #[Route('/{id}/role', name: 'admin_user_edit_role', methods: ['GET', 'POST'])]
    public function editRole(Request $request, User $user): Response
    {
        $currentType = $this->getUserTypeFromRoles($user->getRoles());
        $form = $this->createForm(UserRoleType::class, ['userType' => $currentType]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $type = $form->get('userType')->getData();
            $user->setRoles($this->getRolesFromType($type));
            $this->entityManager->flush();
            $this->addFlash('success', 'User type updated.');
            return $this->redirectToRoute('admin_users_index');
        }

        return $this->render('admin/users/edit_role.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/timeout', name: 'admin_user_timeout', methods: ['POST'])]
    public function timeout(Request $request, User $user): Response
    {
        /** @var \App\Entity\User $currentUser */
        $currentUser = $this->getUser();
        if ($user->getId() === $currentUser->getId()) {
            $this->addFlash('error', 'You cannot put yourself in timeout.');
            return $this->redirectToRoute('admin_users_index');
        }

        if ($this->isCsrfTokenValid('timeout' . $user->getId(), (string) $request->request->get('_token'))) {
            $user->setTimeoutUntil(new \DateTimeImmutable('+24 hours'));
            $this->entityManager->flush();

            $this->notificationService->notify(
                $user,
                'Your account has been put in a 24-hour timeout by an administrator.',
                'danger'
            );

            $this->addFlash('success', 'User put in 24-hour timeout.');
        }

        return $this->redirectToRoute('admin_users_index');
    }

    #[Route('/{id}/remove-timeout', name: 'admin_user_remove_timeout', methods: ['POST'])]
    public function removeTimeout(Request $request, User $user): Response
    {
        if ($this->isCsrfTokenValid('remove-timeout' . $user->getId(), (string) $request->request->get('_token'))) {
            $user->setTimeoutUntil(null);
            $this->entityManager->flush();

            $this->notificationService->notify(
                $user,
                'Your account restriction has been removed by an administrator.',
                'success'
            );

            $this->addFlash('success', 'Timeout removed for this user.');
        }

        return $this->redirectToRoute('admin_users_index');
    }

    #[Route('/{id}/edit-ngo-description', name: 'admin_user_edit_ngo_description', methods: ['POST'])]
    public function editNgoDescription(Request $request, User $user): Response
    {
        if (!in_array('ROLE_NGO', $user->getRoles(), true)) {
            $this->addFlash('error', 'Only NGOs can have a description.');
            return $this->redirectToRoute('admin_users_index');
        }

        if ($this->isCsrfTokenValid('edit_ngo_desc' . $user->getId(), (string) $request->request->get('_token'))) {
            $description = $request->request->getString('ngo_description', '');
            $user->setNgoDescription($description !== '' ? $description : null);
            $this->entityManager->flush();
            $this->addFlash('success', 'NGO description updated.');
        }

        return $this->redirectToRoute('admin_users_index');
    }

    /** @param list<string> $roles */
    private function getUserTypeFromRoles(array $roles): string
    {
        if (\in_array('ROLE_ADMIN', $roles, true)) {
            return UserRoleType::TYPE_ADMIN;
        }
        if (\in_array('ROLE_NGO', $roles, true)) {
            return UserRoleType::TYPE_NGO;
        }
        return UserRoleType::TYPE_NORMAL;
    }

    /** @return list<string> */
    private function getRolesFromType(string $type): array
    {
        return match ($type) {
            UserRoleType::TYPE_ADMIN => ['ROLE_ADMIN'],
            UserRoleType::TYPE_NGO => ['ROLE_NGO'],
            default => [],
        };
    }
}
