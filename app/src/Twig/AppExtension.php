<?php

namespace App\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;

/**
 * Extension Twig personnalisée pour Destocard
 * 
 * Fonctionnalités principales :
 * - Variables globales disponibles dans tous les templates
 * - Filtres personnalisés pour le formatage et l'affichage
 * - Fonctions utilitaires pour l'interface utilisateur
 */
class AppExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly RequestStack $requestStack
    ) {}

    /**
     * Variables globales disponibles dans tous les templates Twig
     * 
     * Ces variables sont automatiquement injectées dans chaque template,
     * évitant de les passer manuellement depuis chaque contrôleur.
     */
    public function getGlobals(): array
    {
        $session = $this->requestStack->getSession();
        
        // Récupérer le panier depuis la session
        $cart = $session ? $session->get('cart', []) : [];
        
        // Compter le nombre total d'articles dans le panier
        $cartCount = 0;
        foreach ($cart as $item) {
            $cartCount += $item['quantity'] ?? 1;
        }

        return [
            'cartCount' => $cartCount,
        ];
    }

    /**
     * Filtres personnalisés Twig
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('highlight', [$this, 'highlight'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Filtre pour surligner les mots dans les résultats de recherche
     * 
     * @param string $text Le texte à traiter
     * @param string $query Le terme à surligner
     * @return string Le texte avec les termes surlignés
     */
    public function highlight(string $text, string $query): string
    {
        if (empty($query)) {
            return $text;
        }

        // Échapper le terme de recherche pour éviter les problèmes de regex
        $escapedQuery = preg_quote($query, '/');

        // Surligner le terme (insensible à la casse)
        return preg_replace(
            '/(' . $escapedQuery . ')/i',
            '<mark class="search-highlight">$1</mark>',
            $text
        );
    }
}
