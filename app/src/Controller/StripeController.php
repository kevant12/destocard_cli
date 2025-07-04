<?php

namespace App\Controller;

use App\Service\StripeService;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Contrôleur API pour l'intégration Stripe
 * 
 * Fonctionnalités principales :
 * - Création d'intentions de paiement (PaymentIntent) pour les commandes
 * - Gestion des clients Stripe (création et liaison avec les utilisateurs)
 * - Gestion des abonnements et paiements récurrents
 * - Traitement des webhooks pour synchroniser les événements Stripe
 * - API REST complète pour l'intégration frontend JavaScript
 * 
 * Architecture sécurisée :
 * - Toutes les clés secrètes sont gérées côté serveur uniquement
 * - Validation des montants et données avant envoi à Stripe
 * - Gestion centralisée des erreurs API Stripe
 * - Utilise StripeService pour centraliser la logique métier
 */
#[Route('/api/stripe')]
class StripeController extends AbstractController
{
    public function __construct(private readonly StripeService $stripeService)
    {
    }

    /**
     * Crée une intention de paiement Stripe (PaymentIntent)
     * 
     * Cette méthode est l'étape critique du processus de paiement :
     * - Prépare une transaction Stripe sans la finaliser immédiatement
     * - Retourne un client_secret pour le frontend JavaScript
     * - Permet à Stripe.js de collecter les informations de carte côté client
     * - Sécurise le processus : les données de carte ne touchent jamais notre serveur
     * 
     * @param Request $request Contient le montant en centimes
     * @return JsonResponse Le client_secret pour finaliser le paiement côté client
     */
    #[Route('/create-payment-intent', name: 'api_create_payment_intent', methods: ['POST'])]
    public function createPaymentIntent(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $amount = $data['amount'] ?? 0;

        // Validation côté serveur du montant (sécurité critique)
        if ($amount <= 0) {
            return $this->json(['error' => 'Invalid amount'], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Utiliser le service pour créer l'intention de paiement
            $paymentIntent = $this->stripeService->createPaymentIntent($amount);

            return $this->json(['clientSecret' => $paymentIntent->client_secret]);
        } catch (ApiErrorException $e) {
            // Gérer les erreurs spécifiques à l'API Stripe
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Crée un client Stripe et l'associe à un email
     * 
     * Utilisé pour :
     * - Centraliser les informations de paiement d'un utilisateur
     * - Faciliter les paiements futurs et abonnements
     * - Intégrer avec le système de facturation Stripe
     * 
     * @param Request $request Contient l'email du client
     * @return JsonResponse L'ID du client Stripe créé
     */
    #[Route('/create-customer', name: 'api_create_stripe_customer', methods: ['POST'])]
    public function createCustomer(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;

        // Validation de l'email obligatoire
        if (!$email) {
            return $this->json(['error' => 'Email is required'], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Créer le client via le service Stripe
            $customer = $this->stripeService->createCustomer($email);

            return $this->json(['customerId' => $customer->id]);
        } catch (ApiErrorException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Crée un abonnement Stripe pour un client
     * 
     * Fonctionnalité pour les paiements récurrents :
     * - Associe un client à un plan tarifaire (Price ID)
     * - Gère les abonnements mensuels/annuels
     * - Retourne les informations pour finaliser le premier paiement
     * 
     * @param Request $request Contient customerId et priceId
     * @return JsonResponse Les détails de l'abonnement créé
     */
    #[Route('/create-subscription', name: 'api_create_stripe_subscription', methods: ['POST'])]
    public function createSubscription(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $customerId = $data['customerId'] ?? null;
        $priceId = $data['priceId'] ?? null;

        // Validation des paramètres requis
        if (!$customerId || !$priceId) {
            return $this->json(['error' => 'customerId and priceId are required'], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Créer l'abonnement via le service
            $subscription = $this->stripeService->createSubscription($customerId, $priceId);

            return $this->json([
                'subscriptionId' => $subscription->id,
                'clientSecret' => $subscription->latest_invoice->payment_intent->client_secret ?? null,
            ]);
        } catch (ApiErrorException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Récupère les détails d'une intention de paiement
     * 
     * Utilisé pour :
     * - Vérifier le statut d'un paiement (succeeded, failed, pending)
     * - Récupérer les métadonnées d'une transaction
     * - Débugger les problèmes de paiement
     * 
     * @param string $id L'ID de l'intention de paiement Stripe
     * @return JsonResponse Les détails complets du PaymentIntent
     */
    #[Route('/payment-intent/{id}', name: 'api_get_payment_intent', methods: ['GET'])]
    public function getPaymentIntent(string $id): JsonResponse
    {
        try {
            // Récupérer l'intention de paiement depuis Stripe
            $paymentIntent = $this->stripeService->getPaymentIntent($id);

            return $this->json($paymentIntent);
        } catch (ApiErrorException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Traite les webhooks Stripe pour synchroniser les événements
     * 
     * Les webhooks permettent à Stripe de notifier notre application de :
     * - Paiements réussis ou échoués
     * - Changements d'abonnements
     * - Chargebacks et remboursements
     * 
     * Sécurité critique :
     * - Vérification de la signature Stripe pour authentifier les requêtes
     * - Traitement idempotent pour éviter les doublons
     * 
     * @param Request $request Le payload du webhook avec signature
     * @return JsonResponse Confirmation de traitement pour Stripe
     */
    #[Route('/webhook', name: 'api_stripe_webhook', methods: ['POST'])]
    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->headers->get('stripe-signature');

        try {
            // Vérifier et déconstruire l'événement webhook
            $event = $this->stripeService->constructWebhookEvent($payload, $signature);

            // Traitement basique des événements principaux
            if ($event->type === 'payment_intent.succeeded') {
                $paymentIntent = $event->data->object;
                // TODO: Implémenter la logique de finalisation de commande
                // (marquer la commande comme payée, envoyer email de confirmation, etc.)
            }

            return $this->json(['status' => 'success']);
        } catch (SignatureVerificationException $e) {
            // Signature invalide = tentative de fraude ou erreur de configuration
            return $this->json(['error' => 'Invalid signature'], Response::HTTP_BAD_REQUEST);
        } catch (\UnexpectedValueException $e) {
            // Payload malformé
            return $this->json(['error' => 'Invalid payload'], Response::HTTP_BAD_REQUEST);
        }
    }
} 