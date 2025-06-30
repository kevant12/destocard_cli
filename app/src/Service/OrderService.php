<?php

namespace App\Service;

use App\Entity\Orders;
use App\Entity\OrdersProducts;
use App\Repository\OrdersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class OrderService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
        private OrdersRepository $ordersRepository
    ) {}

    public function handlePaymentSuccess(string $paymentIntentId): void
    {
        $order = $this->ordersRepository->findOneBy(['stripePaymentIntentId' => $paymentIntentId]);
        
        if (!$order) {
            throw new \Exception('Order not found for payment intent: ' . $paymentIntentId);
        }

        // Mettre à jour le statut de la commande
        $order->setStatus('completed');
        $order->setPaidAt(new \DateTimeImmutable());

        // Mettre à jour les stocks des produits
        foreach ($order->getOrdersProducts() as $orderProduct) {
            $product = $orderProduct->getProducts();
            $newQuantity = $product->getQuantity() - $orderProduct->getQuantity();
            
            if ($newQuantity < 0) {
                throw new \Exception('Not enough stock for product: ' . $product->getTitle());
            }
            
            $product->setQuantity($newQuantity);
        }

        $this->entityManager->flush();
    }

    public function handlePaymentFailure(string $paymentIntentId): void
    {
        $order = $this->ordersRepository->findOneBy(['stripePaymentIntentId' => $paymentIntentId]);
        
        if (!$order) {
            throw new \Exception('Order not found for payment intent: ' . $paymentIntentId);
        }

        // Mettre à jour le statut de la commande
        $order->setStatus('failed');
        
        // Restaurer les produits dans le panier
        $session = $this->requestStack->getSession();
        $cart = $session->get('cart', []);
        
        foreach ($order->getOrdersProducts() as $orderProduct) {
            $productId = $orderProduct->getProducts()->getId();
            $cart[$productId] = ($cart[$productId] ?? 0) + $orderProduct->getQuantity();
        }
        
        $session->set('cart', $cart);
        
        $this->entityManager->flush();
    }
} 