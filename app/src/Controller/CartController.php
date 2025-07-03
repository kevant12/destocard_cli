<?php

namespace App\Controller;

use App\Service\CartService;
use App\Service\StripeService;
use App\Form\CheckoutFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CartController extends AbstractController
{
    private $cartService;
    private $stripeService;

    public function __construct(CartService $cartService, StripeService $stripeService)
    {
        $this->cartService = $cartService;
        $this->stripeService = $stripeService;
    }

    #[Route('/checkout', name: 'app_checkout')]
    #[IsGranted('ROLE_USER')]
    public function checkout(Request $request): Response
    {
        $cart = $this->cartService->getFullCart();
        if (empty($cart)) {
            $this->addFlash('warning', 'Votre panier est vide. Impossible de passer commande.');
            return $this->redirectToRoute('cart');
        }

        $user = $this->getUser();
        
        // Debug temporaire
        error_log('=== CHECKOUT DEBUG ===');
        error_log('User connecté: ' . ($user ? $user->getEmail() . ' (ID: ' . $user->getId() . ')' : 'NON CONNECTÉ'));
        error_log('Nombre d\'adresses shipping: ' . $user->getAddresses()->filter(function($addr) { return $addr->getType() === 'shipping'; })->count());
        
        $form = $this->createForm(CheckoutFormType::class, null, [
            'user' => $user
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $deliveryAddress = $form->get('deliveryAddress')->getData();
            $deliveryMethod = $form->get('deliveryMethod')->getData();
            $shippingCost = (float) $form->get('shippingCost')->getData();

            $totalAmount = ($this->cartService->calculateTotal() + $shippingCost) * 100; // Montant en centimes

            try {
                $paymentIntent = $this->stripeService->createPaymentIntent($totalAmount);

                // Stocker les informations de livraison en session pour la finalisation après paiement
                $session = $request->getSession();
                $session->set('checkout_delivery_address_id', $deliveryAddress->getId());
                $session->set('checkout_delivery_method', $deliveryMethod);
                $session->set('checkout_shipping_cost', $shippingCost);

                return $this->redirectToRoute('app_payment', [
                    'clientSecret' => $paymentIntent->client_secret
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la création de l\'intention de paiement : ' . $e->getMessage());
                return $this->redirectToRoute('app_checkout');
            }
        }

        return $this->render('cart/checkout.html.twig', [
            'form' => $form,
            'cart' => $cart,
            'total' => $this->cartService->calculateTotal(),
            'cartCount' => $this->cartService->getCartCount()
        ]);
    }

    #[Route('/add-to-cart/{id}', name: 'add_to_cart', methods: ['POST'])]
    public function addToCart(int $id, Request $request): Response
    {
        try {
            $result = $this->cartService->addToCart($id);

            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => true,
                    'cartCount' => $result['cartCount'],
                    'total' => $result['total']
                ]);
            }

            return $this->redirectToRoute('cart');
        } catch (\Exception $e) {
            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], 400);
            }

            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('cart');
        }
    }

    #[Route('/cart', name: 'cart', methods: ['GET'])]
    public function cart(): Response
    {
        // Valider le stock avant d'afficher le panier
        $validation = $this->cartService->validateStock();
        
        if (!empty($validation['errors'])) {
            foreach ($validation['errors'] as $error) {
                $this->addFlash('warning', $error);
            }
        }

        return $this->render('cart/index.html.twig', [
            'cart' => $validation['cart'],
            'cartCount' => $this->cartService->getCartCount(),
            'total' => $this->cartService->calculateTotal()
        ]);
    }

    #[Route('/cart/remove/{id}', name: 'cart_remove', methods: ['POST'])]
    public function removeItem(int $id, Request $request): Response
    {
        // Valider le jeton CSRF
        if (!$this->isCsrfTokenValid('cart_remove' . $id, $request->request->get('_token'))) {
            return $this->json(['success' => false, 'error' => 'Jeton CSRF invalide.'], 403);
        }

        $result = $this->cartService->removeFromCart($id);

        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'success' => true,
                'cartCount' => $result['cartCount'],
                'total' => $result['total']
            ]);
        }

        return $this->redirectToRoute('cart');
    }

    #[Route('/cart/buy', name: 'cart_buy', methods: ['POST'])]
    public function buy(Request $request): Response
    {
        // Valider le stock avant de finaliser l'achat
        $validation = $this->cartService->validateStock();
        
        if (!$validation['valid']) {
            $errors = $validation['errors'];
            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => false,
                    'message' => implode('\n', $errors), // Concaténer les erreurs pour l'affichage
                    'redirect' => $this->generateUrl('cart') // Rediriger vers le panier pour voir les ajustements
                ], 400);
            } else {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->redirectToRoute('cart');
            }
        }

        // Rediriger vers la page de checkout pour la sélection de l'adresse et du mode de livraison
        return $this->redirectToRoute('app_checkout');
    }

    #[Route('/payment', name: 'app_payment')]
    #[IsGranted('ROLE_USER')]
    public function payment(Request $request): Response
    {
        $clientSecret = $request->query->get('clientSecret');

        if (!$clientSecret) {
            $this->addFlash('error', 'Client Secret manquant pour le paiement.');
            return $this->redirectToRoute('app_checkout');
        }

        return $this->render('cart/payment.html.twig', [
            'clientSecret' => $clientSecret,
            'total' => $this->cartService->calculateTotal() + $request->getSession()->get('checkout_shipping_cost', 0.0),
            'stripe_public_key' => $_ENV['STRIPE_PUBLIC_KEY']
        ]);
    }

    #[Route('/confirm-order', name: 'app_confirm_order', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function confirmOrder(Request $request, 
        \App\Repository\AddressesRepository $addressesRepository
    ): JsonResponse
    {
        $session = $request->getSession();
        $deliveryAddressId = $session->get('checkout_delivery_address_id');
        $deliveryMethod = $session->get('checkout_delivery_method');
        $shippingCost = $session->get('checkout_shipping_cost');

        if (!$deliveryAddressId || !$deliveryMethod || $shippingCost === null) {
            return $this->json([
                'success' => false,
                'message' => 'Informations de livraison manquantes. Veuillez recommencer le processus de commande.',
                'redirect' => $this->generateUrl('app_checkout')
            ], 400);
        }

        $deliveryAddress = $addressesRepository->find($deliveryAddressId);

        if (!$deliveryAddress || $deliveryAddress->getUsers() !== $this->getUser()) {
            return $this->json([
                'success' => false,
                'message' => 'Adresse de livraison invalide.',
                'redirect' => $this->generateUrl('app_checkout')
            ], 400);
        }

        try {
            $order = $this->cartService->purchaseCart(
                $this->getUser(), 
                $deliveryAddress, 
                $deliveryMethod, 
                $shippingCost
            );

            // Nettoyer les informations de session après la commande
            $session->remove('checkout_delivery_address_id');
            $session->remove('checkout_delivery_method');
            $session->remove('checkout_shipping_cost');

            return $this->json([
                'success' => true,
                'message' => 'Achat effectué avec succès ! Votre commande n°' . $order->getId() . ' a été enregistrée.',
                'redirect' => $this->generateUrl('app_home') // Rediriger vers une page de confirmation ou d'accueil
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de l\'achat : ' . $e->getMessage()
            ], 400);
        }
    }
}