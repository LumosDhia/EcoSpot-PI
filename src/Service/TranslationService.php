<?php

declare(strict_types=1);

namespace App\Service;

use Stichoza\GoogleTranslate\GoogleTranslate;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class TranslationService
{
    private CacheInterface $cache;
    private GoogleTranslate $tr;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
        $this->tr = new GoogleTranslate();
    }

    /**
     * Translates text to the target language.
     * Uses cache to minimize external requests.
     */
    public function translate(string $text, string $targetLocale): string
    {
        if (empty($text)) {
            return $text;
        }

        // If no target locale is provided, default to 'en'
        $targetLocale = $targetLocale ?: 'en';

        // Create a unique cache key based on text and target language
        $cacheKey = 'tr_' . md5($text . '_' . $targetLocale);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($text, $targetLocale) {
            // Cache translations for 1 week
            $item->expiresAfter(604800);

            try {
                $this->tr->setTarget($targetLocale);
                return $this->tr->translate($text);
            } catch (\Exception $e) {
                // Fallback to original text if translation fails
                return $text;
            }
        });
    }
}
