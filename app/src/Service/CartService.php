<?php

namespace App\Service;

use App\Entity\Products;
use App\Entity\Orders;
use App\Entity\OrdersProducts;
use App\Entity\Users;
use App\Repository\ProductsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service de gestion du panier d'achat
 * 
 * Centralise toute la logique métier liée au panier :
 * - Gestion des articles (ajout, suppression, modification)
 * - Validation des stocks et contraintes
 * - Finalisation des commandes avec transaction sécurisée
 * - Calculs de prix et totaux
 * - Persistance en session pour les utilisateurs non connectés
 * 
 * Utilise la session pour stocker le panier temporairement
 */
class CartService
{
    private $session;
    private $productsRepository;
    private $entityManager;

    public function __construct(
        RequestStack $requestStack,
        ProductsRepository $productsRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->session = $requestStack->getSession();
        $this->productsRepository = $productsRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * Finalise l'achat du panier (commande complète)
     * 
     * Transforme le contenu du panier en commande persistante avec :
     * - Création de l'entité Order avec toutes les informations
     * - Création des relations OrdersProducts pour chaque article
     * - Décrément automatique des stocks
     * - Gestion transactionnelle pour éviter les incohérences
     * - Nettoyage du panier après succès
     * 
     * @param Users $user L'utilisateur qui passe commande
     * @param Addresses $address L'adresse de livraison choisie
     * @param string $deliveryMethod Le mode de livraison (standard/express)
     * @param float $shippingCost Les frais de livraison calculés
     * @return Orders La commande créée
     * @throws \Exception En cas d'erreur (stock insuffisant, panier vide, etc.)
     */
    public function purchaseCart(Users $user, Addresses $address, string $deliveryMethod, float $shippingCost): Orders
    {
        $cart = $this->getCart();
        if (empty($cart)) {
            throw new \Exception('Votre panier est vide.');
        }

        // Utiliser une transaction pour garantir la cohérence
        $this->entityManager->beginTransaction();
        try {
            // Créer la commande principale avec toutes les informations
            $order = new Orders();
            $order->setUsers($user);
            $order->setAddresses($address); // Associer l'adresse de livraison
            $order->setDeliveryMethod($deliveryMethod); // Définir le mode de livraison
            $order->setShippingCost($shippingCost); // Définir les frais de livraison
            $order->setTotalPrice($this->calculateTotal() + $shippingCost); // Ajouter les frais de livraison au total
            $order->setStatus(Orders::STATUS_PENDING);
            $order->setPaymentProvider('Stripe'); // Ou autre méthode de paiement

            $this->entityManager->persist($order);

            // Traiter chaque article du panier
            foreach ($cart as $productId => $item) {
                $product = $this->productsRepository->find($productId);

                // Vérification critique du stock avant finalisation
                if (!$product || $product->getQuantity() < $item['quantity']) {
                    throw new \Exception('Stock insuffisant pour le produit : ' . $item['name']);
                }

                // Créer la relation Order <-> Product avec les détails
                $orderProduct = new OrdersProducts();
                $orderProduct->setOrders($order);
                $orderProduct->setProducts($product);
                $orderProduct->setQuantity($item['quantity']);
                $orderProduct->setPrice($item['price']);

                $this->entityManager->persist($orderProduct);

                // Décrémenter le stock du produit immédiatement
                $product->setQuantity($product->getQuantity() - $item['quantity']);
                $this->entityManager->persist($product);
            }

            // Sauvegarder toutes les modifications en une fois
            $this->entityManager->flush();
            $this->entityManager->commit();

            // Vider le panier après succès
            $this->clearCart();

            return $order;

        } catch (\Exception $e) {
            // En cas d'erreur, annuler toutes les modifications
            $this->entityManager->rollback();
            throw $e;
        }
    }

    /**
     * Récupère le panier complet avec les détails des produits
     * 
     * Reconstitue les informations complètes depuis les IDs stockés en session.
     * Vérifie l'existence des produits et calcule les prix totaux.
     * 
     * @return array Tableau des articles avec objets Product et calculs
     */
    public function getFullCart(): array
    {
        $cart = $this->getCart();
        $fullCart = [];

        foreach ($cart as $productId => $item) {
            $product = $this->productsRepository->find($productId);

            if ($product) {
                $fullCart[] = [
                    'product' => $product,
                    'quantity' => 1, // Quantité fixe pour ce système
                    'totalItemPrice' => $product->getPrice()
                ];
            }
        }
        return $fullCart;
    }

    /**
     * Récupère le panier brut depuis la session
     * 
     * @return array Le contenu du panier (IDs et quantités uniquement)
     */
    public function getCart(): array
    {
        return $this->session->get('cart', []);
    }

    /**
     * Ajoute un produit au panier
     * 
     * Logique spécifique : un seul exemplaire par produit autorisé.
     * Vérifie la disponibilité en stock avant ajout.
     * 
     * @param int $productId L'ID du produit à ajouter
     * @return array Informations mises à jour du panier
     * @throws \Exception Si produit inexistant, déjà présent ou en rupture
     */
    public function addToCart(int $productId): array
    {
        $cart = $this->getCart();
        $product = $this->productsRepository->find($productId);

        if (!$product) {
            throw new \Exception('Produit non trouvé');
        }

        // Vérifier si le produit est déjà dans le panier (limitation : 1 seul)
        if (isset($cart[$productId])) {
            throw new \Exception('Ce produit est déjà dans votre panier');
        }

        // Vérifier le stock disponible
        if ($product->getQuantity() <= 0) {
            throw new \Exception('Ce produit n\'est plus en stock');
        }

        // Ajouter le produit au panier (1 seul exemplaire)
        $cart[$productId] = ['quantity' => 1];

        $this->session->set('cart', $cart);

        return [
            'cart' => $cart,
            'total' => $this->calculateTotal(),
            'cartCount' => $this->getCartCount()
        ];
    }

    /**
     * Met à jour la quantité d'un produit (fonctionnalité désactivée)
     * 
     * Cette méthode est conservée pour compatibilité mais ne fait rien
     * car le système ne permet qu'un exemplaire par produit.
     * 
     * @param int $productId L'ID du produit
     * @param int $quantity La nouvelle quantité (ignorée)
     * @return array Informations du panier inchangées
     */
    public function updateQuantity(int $productId, int $quantity): array
    {
        // Cette méthode n'est plus nécessaire car on ne peut avoir qu'1 exemplaire
        // On peut la garder pour la compatibilité mais elle ne fait rien
        return [
            'cart' => $this->getFullCart(),
            'total' => $this->calculateTotal(),
            'cartCount' => $this->getCartCount(),
            'itemTotal' => 0
        ];
    }

    /**
     * Retire un produit du panier
     * 
     * @param int $productId L'ID du produit à retirer
     * @return array Informations mises à jour du panier
     */
    public function removeFromCart(int $productId): array
    {
        $cart = $this->getCart();

        if (isset($cart[$productId])) {
            unset($cart[$productId]);
            $this->session->set('cart', $cart);
        }

        return [
            'cart' => $cart,
            'total' => $this->calculateTotal(),
            'cartCount' => $this->getCartCount()
        ];
    }

    /**
     * Vide complètement le panier
     * 
     * Supprime toutes les données du panier de la session.
     * Utilisé après une commande réussie.
     */
    public function clearCart(): void
    {
        $this->session->remove('cart');
    }

    /**
     * Compte le nombre d'articles dans le panier
     * 
     * @return int Le nombre total d'articles différents
     */
    public function getCartCount(): int
    {
        return count($this->getCart());
    }

    /**
     * Calcule le prix total du panier
     * 
     * @return float Le montant total en euros
     */
    public function calculateTotal(): float
    {
        $total = 0;
        foreach ($this->getFullCart() as $item) {
            $total += $item['totalItemPrice'];
        }
        return $total;
    }

    /**
     * Valide la disponibilité de tous les produits du panier
     * 
     * Vérifie que tous les produits existent encore et sont en stock.
     * Utilisé avant affichage du panier et avant finalisation de commande.
     * 
     * @return array Résultat de validation avec erreurs éventuelles
     */
    public function validateStock(): array
    {
        $cart = $this->getCart();
        $errors = [];
        $fullCart = [];

        foreach ($cart as $productId => $item) {
            $product = $this->productsRepository->find($productId);
            if (!$product) {
                $errors[] = "Le produit avec l'ID {$productId} n'est plus disponible.";
                continue;
            }

            // Vérifier si le produit est en stock
            if ($product->getQuantity() <= 0) {
                $errors[] = "Le produit {$product->getTitle()} n'est plus en stock.";
                continue;
            }

            // Ajouter au panier complet (toujours 1 exemplaire)
            $fullCart[] = [
                'product' => $product,
                'quantity' => 1,
                'totalItemPrice' => $product->getPrice()
            ];
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'cart' => $fullCart
        ];
    }
} 