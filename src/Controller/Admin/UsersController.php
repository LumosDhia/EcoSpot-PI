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
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    #[Route('', name: 'admin_users_index', methods: ['GET'])]
    public function index(): Response
    {
        $users = $this->userRepository->findBy([], ['email' => 'ASC']);

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

    #[Route('/{id}', name: 'admin_user_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, User $user): Response
    {
        if ($user->getId() === $this->getUser()->getId()) {
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

    #[Route('/{id}/role', name: 'admin_user_edit_role', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
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

    #[Route('/{id}/timeout', name: 'admin_user_timeout', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function timeout(Request $request, User $user): Response
    {
        if ($user->getId() === $this->getUser()->getId()) {
            $this->addFlash('error', 'You cannot put yourself in timeout.');
            return $this->redirectToRoute('admin_users_index');
        }

        if ($this->isCsrfTokenValid('timeout' . $user->getId(), (string) $request->request->get('_token'))) {
            $user->setTimeoutUntil(new \DateTimeImmutable('+24 hours'));
            $this->entityManager->flush();
            $this->addFlash('success', 'User put in 24-hour timeout.');
        }

        return $this->redirectToRoute('admin_users_index');
    }

    #[Route('/{id}/remove-timeout', name: 'admin_user_remove_timeout', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function removeTimeout(Request $request, User $user): Response
    {
        if ($this->isCsrfTokenValid('remove-timeout' . $user->getId(), (string) $request->request->get('_token'))) {
            $user->setTimeoutUntil(null);
            $this->entityManager->flush();
            $this->addFlash('success', 'Timeout removed for this user.');
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

    private function getRolesFromType(string $type): array
    {
        return match ($type) {
            UserRoleType::TYPE_ADMIN => ['ROLE_ADMIN'],
            UserRoleType::TYPE_NGO => ['ROLE_NGO'],
            default => [],
        };
    }
}
