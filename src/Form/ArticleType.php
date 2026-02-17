<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\Tag;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ArticleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Title',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Article title',
                ],
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Content (rich text)',
                'attr' => [
                    'class' => 'form-control article-content-editor',
                    'rows' => 12,
                    'placeholder' => 'Write your article...',
                ],
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'label' => 'Category',
                'required' => false,
                'placeholder' => 'Choose a category',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('tags', EntityType::class, [
                'class' => Tag::class,
                'choice_label' => 'name',
                'label' => 'Tags',
                'multiple' => true,
                'expanded' => false, // Set to true for checkboxes
                'required' => false,
                'attr' => ['class' => 'form-select', 'size' => 5],
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Thumbnail / hero image (upload)',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'form-control', 'accept' => 'image/*'],
            ])
            ->add('image', TextType::class, [
                'label' => 'Or image URL',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'https://example.com/image.jpg',
                ],
            ])
            ->add('publishMode', ChoiceType::class, [
                'label' => 'Publication',
                'mapped' => false,
                'choices' => [
                    'Save as draft' => 'draft',
                    'Publish now' => 'publish_now',
                    'Schedule publication' => 'schedule',
                ],
                'attr' => ['class' => 'form-select'],
            ])
            ->add('scheduledAt', DateTimeType::class, [
                'label' => 'Publish date & time',
                'mapped' => false,
                'required' => false,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
            $form = $event->getForm();
            $mode = $form->get('publishMode')->getData();
            $scheduledAt = $form->get('scheduledAt')->getData();
            if ($mode === 'schedule' && $scheduledAt instanceof \DateTimeInterface && $scheduledAt < new \DateTimeImmutable()) {
                $form->get('scheduledAt')->addError(new \Symfony\Component\Form\FormError('Scheduled date and time cannot be in the past.'));
            }
        });

        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event): void {
            $article = $event->getData();
            if (!$article instanceof Article) {
                return;
            }
            $form = $event->getForm();
            $status = $article->getPublicationStatus();
            $form->get('publishMode')->setData($status === 'scheduled' ? 'schedule' : ($status === 'published' ? 'publish_now' : 'draft'));
            if ($article->getPublishedAt() !== null) {
                $form->get('scheduledAt')->setData(\DateTime::createFromImmutable($article->getPublishedAt()));
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Article::class,
        ]);
    }
}
