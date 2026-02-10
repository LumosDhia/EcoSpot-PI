<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class AdminUserCreateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(),
                    new Email(),
                ],
            ])
            ->add('firstname', TextType::class, [
                'label' => 'First name',
                'attr' => ['class' => 'form-control'],
                'constraints' => [new NotBlank(), new Length(['max' => 100])],
            ])
            ->add('lastname', TextType::class, [
                'label' => 'Last name',
                'attr' => ['class' => 'form-control'],
                'constraints' => [new NotBlank(), new Length(['max' => 100])],
            ])
            ->add('userType', ChoiceType::class, [
                'label' => 'User type',
                'mapped' => false,
                'choices' => [
                    'Normal user' => UserRoleType::TYPE_NORMAL,
                    'NGO' => UserRoleType::TYPE_NGO,
                    'Administrator' => UserRoleType::TYPE_ADMIN,
                ],
                'attr' => ['class' => 'form-select'],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options' => [
                    'label' => 'Password',
                    'attr' => ['class' => 'form-control', 'autocomplete' => 'new-password'],
                ],
                'second_options' => [
                    'label' => 'Repeat password',
                    'attr' => ['class' => 'form-control', 'autocomplete' => 'new-password'],
                ],
                'invalid_message' => 'The password fields must match.',
                'constraints' => [
                    new NotBlank(),
                    new Length(['min' => 6, 'max' => 4096]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
