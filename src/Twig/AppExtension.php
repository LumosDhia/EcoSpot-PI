<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('decode_html_entities', [$this, 'decodeHtmlEntities']),
        ];
    }

    public function decodeHtmlEntities(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        return html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
