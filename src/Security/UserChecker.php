<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if ($user->isTimedOut()) {
            throw new CustomUserMessageAuthenticationException(
                'Your account is temporarily disabled due to multiple failed attempts or administrative action.'
            );
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
    }
}
