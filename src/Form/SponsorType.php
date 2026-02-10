<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Sponsor;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class SponsorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Name',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Sponsor name'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['class' => 'form-control', 'rows' => 4, 'placeholder' => 'Description'],
            ])
            ->add('secteur', TextType::class, [
                'label' => 'Sector',
                'attr' => ['class' => 'form-control', 'placeholder' => 'e.g. Energy, Recycling'],
            ])
            ->add('localisation', TextType::class, [
                'label' => 'Location',
                'attr' => ['class' => 'form-control', 'placeholder' => 'City or region'],
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Logo / Image',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
                        'mimeTypesMessage' => 'Please upload a valid image (JPEG, PNG, GIF or WebP).',
                    ]),
                ],
                'attr' => ['class' => 'form-control'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Sponsor::class,
        ]);
    }
}
