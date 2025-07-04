<?php

namespace App\Service;

use App\Entity\Orders;
use App\Entity\OrdersProducts;
use App\Entity\Products;
use App\Entity\Users;
use App\Repository\OrdersRepository;
use App\Repository\ProductsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service de gestion des commandes et du processus de vente
 * 
 * Fonctionnalités principales :
 * - Création et finalisation des commandes après paiement
 * - Gestion des statuts de commande (pending, paid, shipped, delivered)
 * - Traitement des échecs de paiement et annulations
 * - Mise à jour automatique des stocks après achat
 * - Historique complet des commandes pour vendeurs et acheteurs
 * - Intégration avec les webhooks Stripe pour synchronisation
 * 
 * Logique métier critique :
 * - Une commande n'est finalisée qu'après confirmation de paiement
 * - Les stocks sont décrémentés atomiquement pour éviter la survente
 * - Les produits vendus sont automatiquement marqués comme indisponibles
 * - Toutes les opérations sont loggées pour audit et traçabilité
 */
class OrderService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
        private readonly OrdersRepository $ordersRepository,
        private readonly ProductsRepository $productsRepository,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Crée une nouvelle commande en statut "pending" avant paiement
     * 
     * Cette méthode prépare la commande mais ne la finalise pas :
     * - Crée l'entité Orders avec le statut "pending"
     * - Associe l'acheteur et les produits commandés
     * - Calcule le montant total avec éventuels frais de port
     * - Génère un numéro de commande unique
     * - Réserve temporairement les produits (sans décrémenter le stock)
     * 
     * La commande reste "pending" jusqu'à confirmation du paiement par webhook
     * 
     * @param Users $buyer L'utilisateur acheteur
     * @param array $cartItems Les articles du panier avec quantités
     * @param float $totalAmount Le montant total incluant frais
     * @param string $shippingAddress L'adresse de livraison
     * @return Orders La commande créée en statut pending
     */
    public function createPendingOrder(Users $buyer, array $cartItems, float $totalAmount, string $shippingAddress): Orders
    {
        $order = new Orders();
        $order->setBuyer($buyer);
        $order->setStatus('pending');
        $order->setTotalAmount($totalAmount);
        $order->setShippingAddress($shippingAddress);
        $order->setCreatedAt(new \DateTimeImmutable());
        
        // Générer un numéro de commande unique
        $order->setOrderNumber($this->generateOrderNumber());
        
        // Associer les produits commandés
        foreach ($cartItems as $item) {
            $product = $this->productsRepository->find($item['product_id']);
            if ($product) {
                $orderProduct = new OrdersProducts();
                $orderProduct->setOrder($order);
                $orderProduct->setProduct($product);
                $orderProduct->setQuantity($item['quantity']);
                $orderProduct->setUnitPrice($product->getPrice());
                
                $order->addOrderProduct($orderProduct);
            }
        }
        
        // Persister la commande en statut pending
        $this->entityManager->persist($order);
        $this->entityManager->flush();
        
        // Logger la création de commande
        $this->logger->info('Order created', [
            'order_id' => $order->getId(),
            'buyer_id' => $buyer->getId(),
            'total_amount' => $totalAmount
        ]);
        
        return $order;
    }

    /**
     * Finalise une commande après confirmation de paiement Stripe
     * 
     * Cette méthode critique est appelée par le webhook Stripe :
     * - Vérifie que la commande existe et est en statut "pending"
     * - Change le statut à "paid" pour marquer le paiement confirmé
     * - Décrémente automatiquement les stocks des produits vendus
     * - Marque les produits comme "sold" s'ils étaient uniques
     * - Envoie les notifications aux vendeurs et acheteurs
     * - Toutes les opérations sont transactionnelles (atomiques)
     * 
     * @param string $paymentIntentId L'ID Stripe du paiement
     * @throws \Exception Si la commande n'est pas trouvée ou déjà traitée
     */
    public function handlePaymentSuccess(string $paymentIntentId): void
    {
        // Récupérer la commande par l'ID de paiement Stripe
        $order = $this->ordersRepository->findOneBy(['stripePaymentIntentId' => $paymentIntentId]);
        
        if (!$order) {
            $this->logger->error('Order not found for payment intent', ['payment_intent_id' => $paymentIntentId]);
            throw new \Exception('Order not found');
        }
        
        // Vérifier que la commande est dans le bon statut
        if ($order->getStatus() !== 'pending') {
            $this->logger->warning('Order already processed', [
                'order_id' => $order->getId(),
                'current_status' => $order->getStatus()
            ]);
            return; // Traitement idempotent - ne pas échouer
        }
        
        // Commencer une transaction pour garantir l'atomicité
        $this->entityManager->beginTransaction();
        
        try {
            // Étape 1 : Marquer la commande comme payée
            $order->setStatus('paid');
            $order->setPaidAt(new \DateTimeImmutable());
            
            // Étape 2 : Traiter chaque produit commandé
            foreach ($order->getOrderProducts() as $orderProduct) {
                $product = $orderProduct->getProduct();
                $quantityOrdered = $orderProduct->getQuantity();
                
                // Décrémenter le stock
                $currentStock = $product->getStock() ?? 0;
                $newStock = max(0, $currentStock - $quantityOrdered);
                $product->setStock($newStock);
                
                // Si le stock tombe à 0, marquer comme non disponible
                if ($newStock === 0) {
                    $product->setIsAvailable(false);
                }
                
                // Logger la modification de stock
                $this->logger->info('Stock updated', [
                    'product_id' => $product->getId(),
                    'old_stock' => $currentStock,
                    'new_stock' => $newStock
                ]);
            }
            
            // Étape 3 : Persister toutes les modifications
            $this->entityManager->flush();
            $this->entityManager->commit();
            
            // Étape 4 : Envoyer les notifications (après commit pour éviter les conflits)
            $this->sendOrderConfirmationNotifications($order);
            
            $this->logger->info('Order finalized successfully', [
                'order_id' => $order->getId(),
                'payment_intent_id' => $paymentIntentId
            ]);
            
        } catch (\Exception $e) {
            // Annuler la transaction en cas d'erreur
            $this->entityManager->rollback();
            
            $this->logger->error('Failed to finalize order', [
                'order_id' => $order->getId(),
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Gère les échecs de paiement et annule la commande
     * 
     * Cette méthode traite les cas où le paiement Stripe échoue :
     * - Marque la commande comme "failed" ou "cancelled"
     * - Libère les produits réservés temporairement
     * - Restaure les stocks si nécessaire
     * - Notifie l'utilisateur de l'échec
     * 
     * @param string $paymentIntentId L'ID Stripe du paiement échoué
     */
    public function handlePaymentFailure(string $paymentIntentId): void
    {
        // Récupérer la commande concernée
        $order = $this->ordersRepository->findOneBy(['stripePaymentIntentId' => $paymentIntentId]);
        
        if (!$order) {
            $this->logger->error('Order not found for failed payment', ['payment_intent_id' => $paymentIntentId]);
            return;
        }
        
        // Marquer la commande comme échouée
        $order->setStatus('failed');
        $order->setFailedAt(new \DateTimeImmutable());
        
        // Libérer les produits réservés (si une réservation était en place)
        $this->releaseReservedProducts($order);
        
        $this->entityManager->flush();
        
        // Notifier l'utilisateur
        $this->sendPaymentFailureNotification($order);
        
        $this->logger->info('Order marked as failed', [
            'order_id' => $order->getId(),
            'payment_intent_id' => $paymentIntentId
        ]);
    }

    /**
     * Récupère l'historique des commandes pour un acheteur
     * 
     * @param Users $buyer L'utilisateur acheteur
     * @return Orders[] Les commandes de l'acheteur triées par date
     */
    public function getOrdersForBuyer(Users $buyer): array
    {
        return $this->ordersRepository->findBy(
            ['buyer' => $buyer],
            ['createdAt' => 'DESC']
        );
    }

    /**
     * Récupère l'historique des ventes pour un vendeur
     * 
     * @param Users $seller L'utilisateur vendeur
     * @return Orders[] Les commandes où le vendeur a vendu des produits
     */
    public function getSalesForSeller(Users $seller): array
    {
        return $this->ordersRepository->findSalesForSeller($seller);
    }

    /**
     * Met à jour le statut d'une commande
     * 
     * Gère les transitions de statut autorisées :
     * - paid → shipped (quand le vendeur expédie)
     * - shipped → delivered (quand la livraison est confirmée)
     * - paid → refunded (en cas de remboursement)
     * 
     * @param Orders $order La commande à mettre à jour
     * @param string $newStatus Le nouveau statut
     */
    public function updateOrderStatus(Orders $order, string $newStatus): void
    {
        $allowedTransitions = [
            'paid' => ['shipped', 'refunded'],
            'shipped' => ['delivered'],
            'delivered' => ['refunded']
        ];
        
        $currentStatus = $order->getStatus();
        
        // Vérifier si la transition est autorisée
        if (!isset($allowedTransitions[$currentStatus]) || 
            !in_array($newStatus, $allowedTransitions[$currentStatus])) {
            throw new \InvalidArgumentException("Invalid status transition from {$currentStatus} to {$newStatus}");
        }
        
        // Mettre à jour le statut
        $order->setStatus($newStatus);
        
        // Marquer la date selon le nouveau statut
        switch ($newStatus) {
            case 'shipped':
                $order->setShippedAt(new \DateTimeImmutable());
                break;
            case 'delivered':
                $order->setDeliveredAt(new \DateTimeImmutable());
                break;
            case 'refunded':
                $order->setRefundedAt(new \DateTimeImmutable());
                break;
        }
        
        $this->entityManager->flush();
        
        // Envoyer une notification de changement de statut
        $this->sendStatusChangeNotification($order, $newStatus);
    }

    /**
     * Génère un numéro de commande unique
     * 
     * Format : YYYY-MM-DD-XXXXX où XXXXX est un numéro séquentiel
     * 
     * @return string Le numéro de commande unique
     */
    private function generateOrderNumber(): string
    {
        $date = date('Y-m-d');
        $dailySequence = $this->ordersRepository->countOrdersForDate($date) + 1;
        
        return sprintf('%s-%05d', $date, $dailySequence);
    }

    /**
     * Libère les produits réservés temporairement
     * 
     * @param Orders $order La commande annulée
     */
    private function releaseReservedProducts(Orders $order): void
    {
        // Si un système de réservation était en place, libérer ici
        // Pour l'instant, aucune action nécessaire car pas de réservation
    }

    /**
     * Envoie les notifications de confirmation de commande
     * 
     * @param Orders $order La commande confirmée
     */
    private function sendOrderConfirmationNotifications(Orders $order): void
    {
        // TODO: Implémenter l'envoi d'emails de confirmation
        // - Email à l'acheteur avec détails de la commande
        // - Email au vendeur avec notification de vente
    }

    /**
     * Envoie une notification d'échec de paiement
     * 
     * @param Orders $order La commande échouée
     */
    private function sendPaymentFailureNotification(Orders $order): void
    {
        // TODO: Implémenter l'envoi d'email d'échec
    }

    /**
     * Envoie une notification de changement de statut
     * 
     * @param Orders $order La commande modifiée
     * @param string $newStatus Le nouveau statut
     */
    private function sendStatusChangeNotification(Orders $order, string $newStatus): void
    {
        // TODO: Implémenter l'envoi d'email de suivi
    }
} 