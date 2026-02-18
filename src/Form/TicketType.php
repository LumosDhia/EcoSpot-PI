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
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TicketType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'form.ticket.title',
                'attr' => ['class' => 'form-control', 'placeholder' => 'form.ticket.title_placeholder'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'form.ticket.description',
                'attr' => ['class' => 'form-control', 'rows' => 5, 'placeholder' => 'form.ticket.description_placeholder'],
            ])
            ->add('location', TextType::class, [
                'label' => 'form.ticket.location',
                'attr' => ['class' => 'form-control', 'placeholder' => 'form.ticket.location_placeholder', 'autocomplete' => 'off'],
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'form.ticket.picture',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'form-control', 'accept' => 'image/*'],
            ])
            ->add('latitude', HiddenType::class, ['required' => false])
            ->add('longitude', HiddenType::class, ['required' => false])
            ->add('priority', EnumType::class, [
                'class' => TicketPriority::class,
                'label' => 'form.ticket.priority',
                'choice_label' => fn (TicketPriority $p) => $p->getLabel(),
                'attr' => ['class' => 'form-select'],
            ])
            ->add('domain', EnumType::class, [
                'class' => ActionDomain::class,
                'label' => 'form.ticket.domain',
                'choice_label' => fn (ActionDomain $d) => $d->getLabel(),
                'attr' => ['class' => 'form-select'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ticket::class,
        ]);
    }
}
