<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Blog\Article\Article;
use App\Service\AiSeoService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::prePersist, method: 'prePersist', entity: Article::class)]
#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: Article::class)]
class ArticleSeoSubscriber
{
    public function __construct(
        private AiSeoService $aiSeoService
    ) {
    }

    public function prePersist(Article $entity): void
    {
        $this->generateSeoIfEmpty($entity);
    }

    public function preUpdate(Article $entity): void
    {
        $this->generateSeoIfEmpty($entity);
    }

    private function generateSeoIfEmpty(Article $entity): void
    {
        // Only generate if SEO elements are empty
        if (empty($entity->getSeoDescription()) || empty($entity->getSeoKeywords())) {
            $seoElements = $this->aiSeoService->generateSeoElements($entity);

            if (!empty($seoElements)) {
                if (empty($entity->getSeoTitle()) && !empty($seoElements['title'])) {
                    $entity->setSeoTitle($seoElements['title']);
                }
                if (empty($entity->getSeoDescription()) && !empty($seoElements['description'])) {
                    $entity->setSeoDescription($seoElements['description']);
                }
                if (empty($entity->getSeoKeywords()) && !empty($seoElements['keywords'])) {
                    $entity->setSeoKeywords($seoElements['keywords']);
                }
            }
        }
    }
}
