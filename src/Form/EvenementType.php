<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Evenement;
use App\Entity\Sponsor;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class EvenementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'form.event.name',
                'attr' => ['class' => 'form-control', 'placeholder' => 'form.event.name_placeholder'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'form.event.description',
                'attr' => ['class' => 'form-control', 'rows' => 5, 'placeholder' => 'form.event.description_placeholder'],
            ])
            ->add('capacite', IntegerType::class, [
                'label' => 'form.event.capacity',
                'attr' => ['class' => 'form-control', 'min' => 1],
            ])
            ->add('lieu', TextType::class, [
                'label' => 'form.event.location',
                'attr' => ['class' => 'form-control', 'placeholder' => 'form.event.location_placeholder'],
            ])
            ->add('dateDebut', DateTimeType::class, [
                'label' => 'form.event.start',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('dateFin', DateTimeType::class, [
                'label' => 'form.event.end',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'form.event.image',
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
            ])
            ->add('latitude', HiddenType::class, [
                'required' => false,
              ])
            ->add('longitude', HiddenType::class, [
                'required' => false,
            ])
            ->add('sponsors', EntityType::class, [
                'label' => 'form.event.sponsors',
                'class' => Sponsor::class,
                'choice_label' => 'nom',
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'query_builder' => fn ($repo) => $repo->createQueryBuilder('s')->orderBy('s.nom', 'ASC'),
                'placeholder' => false,
                'attr' => ['class' => 'form-check-input'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Evenement::class,
        ]);
    }
}
