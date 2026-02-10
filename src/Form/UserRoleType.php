<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserRoleType extends AbstractType
{
    public const TYPE_NORMAL = 'normal';
    public const TYPE_NGO = 'ngo';
    public const TYPE_ADMIN = 'admin';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('userType', ChoiceType::class, [
                'label' => 'User type',
                'choices' => [
                    'Normal user' => self::TYPE_NORMAL,
                    'NGO' => self::TYPE_NGO,
                    'Administrator' => self::TYPE_ADMIN,
                ],
                'attr' => ['class' => 'form-select'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
