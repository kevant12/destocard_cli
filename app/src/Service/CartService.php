<?php

namespace App\Service;

use App\Entity\Products;
use App\Entity\Orders;
use App\Entity\OrdersProducts;
use App\Entity\Users;
use App\Repository\ProductsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

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

    public function purchaseCart(Users $user, Addresses $address, string $deliveryMethod, float $shippingCost): Orders
    {
        $cart = $this->getCart();
        if (empty($cart)) {
            throw new \Exception('Votre panier est vide.');
        }

        $this->entityManager->beginTransaction();
        try {
            $order = new Orders();
            $order->setUsers($user);
            $order->setAddresses($address); // Associer l'adresse de livraison
            $order->setDeliveryMethod($deliveryMethod); // Définir le mode de livraison
            $order->setShippingCost($shippingCost); // Définir les frais de livraison
            $order->setTotalPrice($this->calculateTotal() + $shippingCost); // Ajouter les frais de livraison au total
            $order->setStatus(Orders::STATUS_PENDING);
            $order->setPaymentProvider('Stripe'); // Ou autre méthode de paiement

            $this->entityManager->persist($order);

            foreach ($cart as $productId => $item) {
                $product = $this->productsRepository->find($productId);

                if (!$product || $product->getQuantity() < $item['quantity']) {
                    throw new \Exception('Stock insuffisant pour le produit : ' . $item['name']);
                }

                $orderProduct = new OrdersProducts();
                $orderProduct->setOrders($order);
                $orderProduct->setProducts($product);
                $orderProduct->setQuantity($item['quantity']);
                $orderProduct->setPrice($item['price']);

                $this->entityManager->persist($orderProduct);

                // Décrémenter le stock du produit
                $product->setQuantity($product->getQuantity() - $item['quantity']);
                $this->entityManager->persist($product);
            }

            $this->entityManager->flush();
            $this->entityManager->commit();

            $this->clearCart();

            return $order;

        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }

    public function getFullCart(): array
    {
        $cart = $this->getCart();
        $fullCart = [];

        foreach ($cart as $productId => $item) {
            $product = $this->productsRepository->find($productId);

            if ($product) {
                $fullCart[] = [
                    'product' => $product,
                    'quantity' => 1,
                    'totalItemPrice' => $product->getPrice()
                ];
            }
        }
        return $fullCart;
    }

    public function getCart(): array
    {
        return $this->session->get('cart', []);
    }

    public function addToCart(int $productId): array
    {
        $cart = $this->getCart();
        $product = $this->productsRepository->find($productId);

        if (!$product) {
            throw new \Exception('Produit non trouvé');
        }

        // Vérifier si le produit est déjà dans le panier
        if (isset($cart[$productId])) {
            throw new \Exception('Ce produit est déjà dans votre panier');
        }

        // Vérifier le stock
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

    public function clearCart(): void
    {
        $this->session->remove('cart');
    }

    public function getCartCount(): int
    {
        return count($this->getCart());
    }

    public function calculateTotal(): float
    {
        $total = 0;
        foreach ($this->getFullCart() as $item) {
            $total += $item['totalItemPrice'];
        }
        return $total;
    }

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