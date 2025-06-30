<?php

namespace App\Service;

use Stripe\Customer;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\StripeClient;
use Stripe\Subscription;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class StripeService
{
    private StripeClient $client;
    private string $webhookSecret;

    public function __construct(
        #[Autowire('%env(STRIPE_SECRET_KEY)%')] string $stripeSecretKey,
        #[Autowire('%env(STRIPE_WEBHOOK_SECRET)%')] string $webhookSecret
    )
    {
        $this->client = new StripeClient($stripeSecretKey);
        $this->webhookSecret = $webhookSecret;
    }

    /**
     * @throws ApiErrorException
     */
    public function createPaymentIntent(int $amount, string $currency = 'eur'): PaymentIntent
    {
        return $this->client->paymentIntents->create([
            'amount' => $amount,
            'currency' => $currency,
            'automatic_payment_methods' => [
                'enabled' => true,
            ],
        ]);
    }

    /**
     * @throws ApiErrorException
     */
    public function createCustomer(string $email): Customer
    {
        return $this->client->customers->create([
            'email' => $email,
        ]);
    }

    /**
     * @throws ApiErrorException
     */
    public function createSubscription(string $customerId, string $priceId): Subscription
    {
        return $this->client->subscriptions->create([
            'customer' => $customerId,
            'items' => [
                ['price' => $priceId],
            ],
            'payment_behavior' => 'default_incomplete',
            'expand' => ['latest_invoice.payment_intent'],
        ]);
    }

    /**
     * @throws ApiErrorException
     */
    public function getPaymentIntent(string $paymentIntentId): PaymentIntent
    {
        return $this->client->paymentIntents->retrieve($paymentIntentId);
    }

    /**
     * @throws SignatureVerificationException
     */
    public function constructWebhookEvent(string $payload, string $signature): \Stripe\Event
    {
        return Webhook::constructEvent(
            $payload,
            $signature,
            $this->webhookSecret
        );
    }
} 