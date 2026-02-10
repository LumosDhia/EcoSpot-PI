<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Optional;
use Symfony\Component\Validator\Constraints\Regex;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'attr' => ['class' => 'form-control'],
                'label' => 'Email',
                'constraints' => [
                    new NotBlank(['message' => 'Please enter your email.']),
                    new Email(['message' => 'Please enter a valid email address.']),
                ],
            ])
            ->add('firstname', TextType::class, [
                'attr' => ['class' => 'form-control'],
                'label' => 'First name',
                'constraints' => [
                    new NotBlank(['message' => 'Please enter your first name.']),
                    new Length(['min' => 1, 'max' => 100, 'maxMessage' => 'First name cannot be longer than {{ limit }} characters.']),
                    new Regex(['pattern' => '/^[\p{L}\s\'-]+$/u', 'message' => 'First name can only contain letters, spaces, hyphens and apostrophes.']),
                ],
            ])
            ->add('lastname', TextType::class, [
                'attr' => ['class' => 'form-control'],
                'label' => 'Last name',
                'constraints' => [
                    new NotBlank(['message' => 'Please enter your last name.']),
                    new Length(['min' => 1, 'max' => 100, 'maxMessage' => 'Last name cannot be longer than {{ limit }} characters.']),
                    new Regex(['pattern' => '/^[\p{L}\s\'-]+$/u', 'message' => 'Last name can only contain letters, spaces, hyphens and apostrophes.']),
                ],
            ])
            ->add('address', TextType::class, [
                'attr' => ['class' => 'form-control'],
                'label' => 'Address',
                'required' => false,
                'constraints' => [
                    new Length(['max' => 255, 'maxMessage' => 'Address cannot be longer than {{ limit }} characters.']),
                ],
            ])
            ->add('zipcode', TextType::class, [
                'attr' => ['class' => 'form-control', 'placeholder' => 'e.g. 75001'],
                'label' => 'Postal code',
                'required' => false,
                'constraints' => [
                    new Optional([
                        new Length(['max' => 5]),
                        new Regex(['pattern' => '/^\d{5}$/', 'message' => 'Postal code must be exactly 5 digits.']),
                    ]),
                ],
            ])
            ->add('city', TextType::class, [
                'attr' => ['class' => 'form-control'],
                'label' => 'City',
                'required' => false,
                'constraints' => [
                    new Optional([
                        new Length(['max' => 150, 'maxMessage' => 'City cannot be longer than {{ limit }} characters.']),
                        new Regex(['pattern' => '/^[\p{L}\s\'-]+$/u', 'message' => 'City can only contain letters, spaces, hyphens and apostrophes.']),
                    ]),
                ],
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [
                    new IsTrue(['message' => 'You should agree to our terms.']),
                ],
                'label' => 'I agree to the terms',
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options' => [
                    'attr' => ['autocomplete' => 'new-password', 'class' => 'form-control'],
                    'label' => 'Password',
                ],
                'second_options' => [
                    'attr' => ['autocomplete' => 'new-password', 'class' => 'form-control'],
                    'label' => 'Repeat password',
                ],
                'invalid_message' => 'The password fields must match.',
                'constraints' => [
                    new NotBlank(['message' => 'Please enter a password.']),
                    new Length(['min' => 6, 'minMessage' => 'Your password should be at least {{ limit }} characters.', 'max' => 4096]),
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
