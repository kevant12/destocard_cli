<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('highlight', [$this, 'highlight'], ['is_safe' => ['html']]),
        ];
    }

    public function highlight(string $text, string $query): string
    {
        if (empty($query)) {
            return $text;
        }

        // Échapper les caractères spéciaux du terme de recherche pour éviter les problèmes avec preg_replace
        $escapedQuery = preg_quote($query, '/');

        // Utiliser une expression régulière insensible à la casse pour trouver et remplacer
        // On enveloppe le terme trouvé dans un span avec la classe 'highlight'
        return preg_replace('/' . $escapedQuery . '/iu', '<span class="highlight">$0</span>', $text);
    }
}
