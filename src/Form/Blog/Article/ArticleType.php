<?php

declare(strict_types=1);

namespace App\Form\Blog\Article;

use App\Entity\Blog\Article\Article;
use App\Entity\Blog\Article\Category;
use App\Entity\Blog\Article\Tag;
use App\Service\TranslationService;
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
use Symfony\Component\HttpFoundation\RequestStack;

class ArticleType extends AbstractType
{
    public function __construct(
        private TranslationService $translationService,
        private RequestStack $requestStack
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $currentLocale = $this->requestStack->getCurrentRequest()?->getLocale() ?? 'en';

        $builder
            ->add('title', TextType::class, [
                'label' => 'form.title_label',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'form.title_placeholder',
                ],
            ])
            ->add('content', TextareaType::class, [
                'label' => 'form.content_label',
                'attr' => [
                    'class' => 'form-control article-content-editor',
                    'rows' => 12,
                    'placeholder' => 'form.content_placeholder',
                ],
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => function (Category $category) use ($currentLocale) {
                    return $this->translationService->translate($category->getName(), $currentLocale);
                },
                'label' => 'form.category_label',
                'required' => false,
                'placeholder' => 'form.category_placeholder',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('tags', EntityType::class, [
                'class' => Tag::class,
                'choice_label' => function (Tag $tag) use ($currentLocale) {
                    return $this->translationService->translate($tag->getName(), $currentLocale);
                },
                'label' => 'form.tags_label',
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'attr' => ['class' => 'form-select', 'size' => 5],
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'form.image_file_label',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'form-control', 'accept' => 'image/*'],
            ])
            ->add('image', TextType::class, [
                'label' => 'form.image_url_label',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'https://example.com/image.jpg',
                ],
            ])
            ->add('publishMode', ChoiceType::class, [
                'label' => 'form.publication_label',
                'mapped' => false,
                'choices' => [
                    'form.save_draft' => 'draft',
                    'form.publish_now' => 'publish_now',
                    'form.schedule_pub' => 'schedule',
                ],
                'attr' => ['class' => 'form-select'],
            ])
            ->add('scheduledAt', DateTimeType::class, [
                'label' => 'form.publish_at',
                'mapped' => false,
                'required' => false,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('seoTitle', TextType::class, [
                'label' => 'form.seo_title_label',
                'required' => false,
                'help' => 'form.seo_title_help',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'form.seo_title_placeholder',
                ],
            ])
            ->add('seoDescription', TextareaType::class, [
                'label' => 'form.seo_description_label',
                'required' => false,
                'help' => 'form.seo_description_help',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'form.seo_description_placeholder',
                ],
            ])
            ->add('seoKeywords', TextType::class, [
                'label' => 'form.seo_keywords_label',
                'required' => false,
                'help' => 'form.seo_keywords_help',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'form.seo_keywords_placeholder',
                ],
            ]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
            $form = $event->getForm();
            $mode = $form->get('publishMode')->getData();
            $scheduledAt = $form->get('scheduledAt')->getData();
            if ($mode === 'schedule' && $scheduledAt instanceof \DateTimeInterface && $scheduledAt < new \DateTimeImmutable()) {
                $form->get('scheduledAt')->addError(new \Symfony\Component\Form\FormError('form.error_past_date'));
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
