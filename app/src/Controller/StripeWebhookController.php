<?php

namespace App\Controller;

use App\Service\OrderService;
use App\Service\StripeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Psr\Log\LoggerInterface;

/**
 * Contrôleur spécialisé pour les webhooks Stripe
 * 
 * Fonctionnalités principales :
 * - Réception et traitement sécurisé des événements Stripe
 * - Synchronisation temps réel des changements de statut de paiement
 * - Finalisation automatique des commandes après paiement réussi
 * - Gestion des échecs de paiement et annulations
 * - Logging complet pour audit et débogage
 * 
 * Architecture de sécurité :
 * - Vérification obligatoire de la signature Stripe
 * - Traitement idempotent pour éviter les doublons
 * - Logging détaillé de tous les événements pour audit
 * - Intégration avec OrderService pour la logique métier
 * 
 * Ce contrôleur est critique pour la fiabilité du système de paiement
 */
class StripeWebhookController extends AbstractController
{
    public function __construct(
        private string $webhookSecret,
        private StripeService $stripeService,
        private OrderService $orderService,
        private LoggerInterface $logger
    ) {}

    /**
     * Point d'entrée principal pour tous les webhooks Stripe
     * 
     * Cette méthode est appelée par Stripe à chaque événement important :
     * - Paiement réussi → Finaliser la commande et mettre à jour les stocks
     * - Paiement échoué → Annuler la commande et restaurer le panier
     * - Remboursement → Gérer la logique de retour
     * 
     * Sécurité critique :
     * - Vérification de la signature pour authentifier que la requête vient bien de Stripe
     * - Tous les événements sont loggés pour traçabilité complète
     * 
     * @param Request $request Le payload webhook avec signature Stripe
     * @return Response Confirmation de traitement pour Stripe (200 = OK, 400 = erreur)
     */
    #[Route('/stripe/webhook', name: 'stripe_webhook', methods: ['POST'])]
    public function handleWebhook(Request $request): Response
    {
        $payload = $request->getContent();
        $signature = $request->headers->get('Stripe-Signature');

        try {
            // Étape 1 : Vérifier l'authenticité de la requête Stripe
            // Cette vérification est OBLIGATOIRE pour éviter les attaques
            $event = $this->stripeService->constructWebhookEvent($payload, $signature);

            // Étape 2 : Traiter les différents types d'événements
            switch ($event->type) {
                case 'payment_intent.succeeded':
                    // Paiement réussi : finaliser la commande
                    $paymentIntent = $event->data->object;
                    
                    // Logging pour audit et débogage
                    $this->logger->info('Payment succeeded', [
                        'payment_intent_id' => $paymentIntent->id,
                        'amount' => $paymentIntent->amount,
                        'currency' => $paymentIntent->currency
                    ]);
                    
                    // Déléguer la logique métier au service spécialisé
                    $this->orderService->handlePaymentSuccess($paymentIntent->id);
                    break;

                case 'payment_intent.payment_failed':
                    // Paiement échoué : annuler et restaurer le panier
                    $paymentIntent = $event->data->object;
                    
                    // Logging avec niveau WARNING pour surveillance
                    $this->logger->warning('Payment failed', [
                        'payment_intent_id' => $paymentIntent->id,
                        'error' => $paymentIntent->last_payment_error ?? null
                    ]);
                    
                    // Gérer l'échec via le service
                    $this->orderService->handlePaymentFailure($paymentIntent->id);
                    break;

                default:
                    // Événement non géré : logger pour suivi futur
                    $this->logger->info('Unhandled event type: ' . $event->type);
                    break;
            }

            // Réponse de succès obligatoire pour que Stripe arrête de réessayer
            return new Response('Webhook handled', Response::HTTP_OK);
            
        } catch (SignatureVerificationException $e) {
            // Signature invalide = tentative de fraude ou erreur de configuration
            $this->logger->error('Invalid webhook signature', [
                'error' => $e->getMessage()
            ]);
            return new Response('Invalid signature', Response::HTTP_BAD_REQUEST);
            
        } catch (\Exception $e) {
            // Toute autre erreur : logger et retourner une erreur pour que Stripe réessaie
            $this->logger->error('Webhook error', [
                'error' => $e->getMessage()
            ]);
            return new Response('Webhook error: ' . $e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}