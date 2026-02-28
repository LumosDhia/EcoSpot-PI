<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Blog\Article\Article;
use App\Service\AiSeoService;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\ObjectManager;

class ArticleSeoSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private AiSeoService $aiSeoService
    ) {
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
            Events::preUpdate,
        ];
    }

    /** @param LifecycleEventArgs<ObjectManager> $args */
    public function prePersist(LifecycleEventArgs $args): void
    {
        $this->generateSeoIfEmpty($args);
    }

    /** @param LifecycleEventArgs<ObjectManager> $args */
    public function preUpdate(LifecycleEventArgs $args): void
    {
        $this->generateSeoIfEmpty($args);
    }

    /** @param LifecycleEventArgs<ObjectManager> $args */
    private function generateSeoIfEmpty(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Article) {
            return;
        }

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
