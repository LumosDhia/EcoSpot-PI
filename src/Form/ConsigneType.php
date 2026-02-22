<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Consigne;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConsigneType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('description', TextType::class, [
                'label' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Task description...',
                ],
            ])
            ->add('difficulty', \Symfony\Component\Form\Extension\Core\Type\EnumType::class, [
                'class' => \App\Enum\TaskDifficulty::class,
                'label' => false,
                'attr' => ['class' => 'form-select form-select-sm difficulty-select'],
                'choice_label' => fn($choice) => $choice->getLabel(),
            ])
            ->add('position', HiddenType::class, [
                'attr' => ['class' => 'consigne-position'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Consigne::class,
        ]);
    }
}
