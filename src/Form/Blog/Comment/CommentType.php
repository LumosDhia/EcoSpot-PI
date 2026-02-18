<?php

declare(strict_types=1);

namespace App\Form\Blog\Comment;

use App\Entity\Blog\Comment\Comment;
use App\Entity\Blog\Article\Article;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('author', TextType::class, [
                'label' => 'form.comment_author',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'form.comment_author_placeholder',
                ],
            ])
            ->add('content', TextareaType::class, [
                'label' => 'form.comment_content',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'form.comment_placeholder',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Comment::class,
        ]);
    }
}
