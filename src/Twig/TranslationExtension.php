<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\TranslationService;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TranslationExtension extends AbstractExtension
{
    private TranslationService $translationService;
    private RequestStack $requestStack;

    public function __construct(TranslationService $translationService, RequestStack $requestStack)
    {
        $this->translationService = $translationService;
        $this->requestStack = $requestStack;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('translate', [$this, 'translateContent']),
        ];
    }

    /**
     * Automatically translates content to the current request locale.
     */
    public function translateContent(?string $content): string
    {
        if (null === $content) {
            return '';
        }

        $request = $this->requestStack->getCurrentRequest();
        $locale = $request ? $request->getLocale() : 'en';

        return $this->translationService->translate($content, $locale);
    }
}
