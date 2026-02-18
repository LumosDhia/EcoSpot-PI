<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

class TicketCompletionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('message', TextareaType::class, [
                'label' => 'form.completion.message',
                'attr' => ['class' => 'form-control', 'rows' => 3, 'placeholder' => 'form.completion.message_placeholder'],
                'constraints' => [new NotBlank(['message' => 'form.completion.not_blank'])],
            ])
            ->add('image', FileType::class, [
                'label' => 'form.completion.image',
                'mapped' => false,
                'required' => true,
                'attr' => ['class' => 'form-control', 'accept' => 'image/*'],
                'constraints' => [
                    new NotBlank(['message' => 'form.completion.image_not_blank']),
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
                        'mimeTypesMessage' => 'Please upload a valid image (JPEG, PNG, GIF or WebP).',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
