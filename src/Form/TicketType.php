<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Ticket;
use App\Enum\ActionDomain;
use App\Enum\TicketPriority;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TicketType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Title',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Short title (min 5 characters)'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['class' => 'form-control', 'rows' => 5, 'placeholder' => 'Describe the issue in detail (min 20 characters)'],
            ])
            ->add('location', TextType::class, [
                'label' => 'Location',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Search for a place or type an address', 'autocomplete' => 'off'],
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Picture (optional)',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'form-control', 'accept' => 'image/*'],
            ])
            ->add('latitude', HiddenType::class, ['required' => false])
            ->add('longitude', HiddenType::class, ['required' => false])
            ->add('priority', EnumType::class, [
                'class' => TicketPriority::class,
                'label' => 'Priority',
                'choice_label' => fn (TicketPriority $p) => $p->getLabel(),
                'attr' => ['class' => 'form-select'],
            ])
            ->add('domain', EnumType::class, [
                'class' => ActionDomain::class,
                'label' => 'Domain',
                'choice_label' => fn (ActionDomain $d) => $d->getLabel(),
                'attr' => ['class' => 'form-select'],
            ])
            ->add('consignes', CollectionType::class, [
                'entry_type' => ConsigneType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false,
            ]);

        if ($options['is_admin']) {
            $builder
                ->add('status', EnumType::class, [
                    'class' => \App\Enum\TicketStatus::class,
                    'label' => 'Status',
                    'choice_label' => fn($s) => $s->getLabel(),
                    'attr' => ['class' => 'form-select']
                ])
                ->add('adminNotes', TextareaType::class, [
                    'label' => 'Admin Notes',
                    'required' => false,
                    'attr' => ['class' => 'form-control', 'rows' => 3]
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ticket::class,
            'is_admin' => false
        ]);
    }
}
