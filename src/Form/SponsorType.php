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
                'label' => 'form.sponsor.name',
                'attr' => ['class' => 'form-control', 'placeholder' => 'form.sponsor.name_placeholder'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'form.sponsor.description',
                'attr' => ['class' => 'form-control', 'rows' => 4, 'placeholder' => 'form.sponsor.description_placeholder'],
            ])
            ->add('secteur', TextType::class, [
                'label' => 'form.sponsor.sector',
                'attr' => ['class' => 'form-control', 'placeholder' => 'form.sponsor.sector_placeholder'],
            ])
            ->add('localisation', TextType::class, [
                'label' => 'form.sponsor.location',
                'attr' => ['class' => 'form-control', 'placeholder' => 'form.sponsor.location_placeholder'],
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'form.sponsor.image',
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
