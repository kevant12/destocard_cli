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

#[Route('/api/stripe')]
class StripeController extends AbstractController
{
    public function __construct(private readonly StripeService $stripeService)
    {
    }

    #[Route('/create-payment-intent', name: 'api_create_payment_intent', methods: ['POST'])]
    public function createPaymentIntent(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $amount = $data['amount'] ?? 0;

        if ($amount <= 0) {
            return $this->json(['error' => 'Invalid amount'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $paymentIntent = $this->stripeService->createPaymentIntent($amount);

            return $this->json(['clientSecret' => $paymentIntent->client_secret]);
        } catch (ApiErrorException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/create-customer', name: 'api_create_stripe_customer', methods: ['POST'])]
    public function createCustomer(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;

        if (!$email) {
            return $this->json(['error' => 'Email is required'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $customer = $this->stripeService->createCustomer($email);

            return $this->json(['customerId' => $customer->id]);
        } catch (ApiErrorException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/create-subscription', name: 'api_create_stripe_subscription', methods: ['POST'])]
    public function createSubscription(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $customerId = $data['customerId'] ?? null;
        $priceId = $data['priceId'] ?? null;

        if (!$customerId || !$priceId) {
            return $this->json(['error' => 'customerId and priceId are required'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $subscription = $this->stripeService->createSubscription($customerId, $priceId);

            return $this->json([
                'subscriptionId' => $subscription->id,
                'clientSecret' => $subscription->latest_invoice->payment_intent->client_secret ?? null,
            ]);
        } catch (ApiErrorException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/payment-intent/{id}', name: 'api_get_payment_intent', methods: ['GET'])]
    public function getPaymentIntent(string $id): JsonResponse
    {
        try {
            $paymentIntent = $this->stripeService->getPaymentIntent($id);

            return $this->json($paymentIntent);
        } catch (ApiErrorException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route('/webhook', name: 'api_stripe_webhook', methods: ['POST'])]
    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->headers->get('stripe-signature');

        try {
            $event = $this->stripeService->constructWebhookEvent($payload, $signature);

            if ($event->type === 'payment_intent.succeeded') {
                $paymentIntent = $event->data->object;
                // Handle successful payment
            }

            return $this->json(['status' => 'success']);
        } catch (SignatureVerificationException $e) {
            return $this->json(['error' => 'Invalid signature'], Response::HTTP_BAD_REQUEST);
        } catch (\UnexpectedValueException $e) {
            return $this->json(['error' => 'Invalid payload'], Response::HTTP_BAD_REQUEST);
        }
    }
} 