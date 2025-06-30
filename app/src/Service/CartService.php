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
                    'quantity' => $item['quantity'],
                    'totalItemPrice' => $product->getPrice() * $item['quantity']
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

        if (isset($cart[$productId])) {
            $cart[$productId]['quantity']++;
        } else {
            $cart[$productId] = ['quantity' => 1];
        }

        $this->session->set('cart', $cart);

        return [
            'cart' => $cart,
            'total' => $this->calculateTotal(),
            'cartCount' => $this->getCartCount()
        ];
    }

    public function updateQuantity(int $productId, int $quantity): array
    {
        $cart = $this->getCart();
        $product = $this->productsRepository->find($productId);

        if (!$product) {
            throw new \Exception('Produit non trouvé');
        }

        if ($quantity <= 0) {
            unset($cart[$productId]);
        } else {
            // Vérifier si la quantité demandée est disponible
            $availableQuantity = $product->getQuantity();
            $quantity = min($quantity, $availableQuantity);

            $cart[$productId] = ['quantity' => $quantity];
        }

        $this->session->set('cart', $cart);

        $fullCart = $this->getFullCart();
        $itemTotal = 0;
        foreach ($fullCart as $item) {
            if ($item['product']->getId() === $productId) {
                $itemTotal = $item['totalItemPrice'];
                break;
            }
        }

        return [
            'cart' => $fullCart,
            'total' => $this->calculateTotal(),
            'cartCount' => $this->getCartCount(),
            'itemTotal' => $itemTotal
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
        $count = 0;
        foreach ($this->getFullCart() as $item) {
            $count += $item['quantity'];
        }
        return $count;
    }

    public function calculateTotal(): float
    {
        $total = 0;
        foreach ($this->getFullCart() as $item) {
            $total += $item['product']->getPrice() * $item['quantity'];
        }
        return $total;
    }

    public function validateStock(): array
    {
        $cart = $this->getCart();
        $errors = [];
        $updatedCartSession = []; // Nouveau tableau pour la session
        $fullCart = []; // Nouveau tableau pour le panier complet avec les objets Product

        foreach ($cart as $productId => $item) {
            $product = $this->productsRepository->find($productId);
            if (!$product) {
                $errors[] = "Le produit avec l'ID {$productId} n'est plus disponible.";
                continue;
            }

            $availableQuantity = $product->getQuantity();
            $requestedQuantity = $item['quantity'];

            if ($availableQuantity < $requestedQuantity) {
                if ($availableQuantity > 0) {
                    $updatedCartSession[$productId] = ['quantity' => $availableQuantity];
                    $errors[] = "La quantité du produit {$product->getTitle()} a été ajustée à {$availableQuantity} (stock disponible).";
                } else {
                    $errors[] = "Le produit {$product->getTitle()} n'est plus en stock.";
                    // Ne pas ajouter au updatedCartSession si stock = 0
                    continue; // Passer au produit suivant
                }
            } else {
                $updatedCartSession[$productId] = ['quantity' => $requestedQuantity];
            }

            // Ajouter au fullCart pour le retour
            $fullCart[] = [
                'product' => $product,
                'quantity' => $updatedCartSession[$productId]['quantity'],
                'totalItemPrice' => $product->getPrice() * $updatedCartSession[$productId]['quantity']
            ];
        }

        // Mettre à jour la session avec le panier ajusté
        $this->session->set('cart', $updatedCartSession);

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'cart' => $fullCart // Retourne le panier complet avec les objets Product
        ];
    }
} 