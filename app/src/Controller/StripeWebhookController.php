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

class StripeWebhookController extends AbstractController
{
    public function __construct(
        private string $webhookSecret,
        private StripeService $stripeService,
        private OrderService $orderService,
        private LoggerInterface $logger
    ) {}

    #[Route('/stripe/webhook', name: 'stripe_webhook', methods: ['POST'])]
    public function handleWebhook(Request $request): Response
    {
        $payload = $request->getContent();
        $signature = $request->headers->get('Stripe-Signature');

        try {
            $event = $this->stripeService->constructWebhookEvent($payload, $signature);

            // Gérer différents types d'événements
            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $paymentIntent = $event->data->object;
                    $this->logger->info('Payment succeeded', [
                        'payment_intent_id' => $paymentIntent->id,
                        'amount' => $paymentIntent->amount,
                        'currency' => $paymentIntent->currency
                    ]);
                    $this->orderService->handlePaymentSuccess($paymentIntent->id);
                    break;

                case 'payment_intent.payment_failed':
                    $paymentIntent = $event->data->object;
                    $this->logger->warning('Payment failed', [
                        'payment_intent_id' => $paymentIntent->id,
                        'error' => $paymentIntent->last_payment_error ?? null
                    ]);
                    $this->orderService->handlePaymentFailure($paymentIntent->id);
                    break;

                default:
                    $this->logger->info('Unhandled event type: ' . $event->type);
                    break;
            }

            return new Response('Webhook handled', Response::HTTP_OK);
        } catch (SignatureVerificationException $e) {
            $this->logger->error('Invalid webhook signature', [
                'error' => $e->getMessage()
            ]);
            return new Response('Invalid signature', Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            $this->logger->error('Webhook error', [
                'error' => $e->getMessage()
            ]);
            return new Response('Webhook error: ' . $e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}