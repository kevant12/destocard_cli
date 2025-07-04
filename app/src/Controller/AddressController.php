<?php

namespace App\Controller;

use App\Entity\Addresses;
use App\Form\AddressFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur de gestion des adresses utilisateur
 * 
 * Fonctionnalités principales :
 * - Gestion CRUD complète des adresses (Create, Read, Update, Delete)
 * - Interface modale AJAX pour une UX fluide
 * - Intégration avec le processus de checkout
 * - Validation et sécurisation des accès utilisateur
 * 
 * Toutes les routes nécessitent une authentification (ROLE_USER)
 */
#[Route('/address')]
#[IsGranted('ROLE_USER')]
class AddressController extends AbstractController
{
    /**
     * Affiche la modal de création d'adresse (AJAX)
     * 
     * Cette méthode prépare le formulaire vide et le renvoie dans une réponse HTML
     * qui sera injectée dans la modale côté frontend.
     */
    #[Route('/new-modal', name: 'app_address_new_modal', methods: ['GET'])]
    public function newModal(Request $request): Response
    {
        $address = new Addresses();
        
        // Si on vient du checkout, on définit le type par défaut
        $defaultType = $request->query->get('type', Addresses::TYPE_SHIPPING);
        $address->setType($defaultType);
        
        $form = $this->createForm(AddressFormType::class, $address);

        return $this->render('address/_modal_form.html.twig', [
            'form' => $form,
            'address' => $address,
            'modal_title' => 'Ajouter une nouvelle adresse'
        ]);
    }

    /**
     * Traite la création d'adresse via AJAX
     * 
     * Reçoit les données du formulaire, les valide et sauvegarde en base.
     * Retourne une réponse JSON pour mise à jour dynamique de l'interface.
     */
    #[Route('/create', name: 'app_address_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        // Debug: Log des données reçues pour diagnostics
        error_log('=== CRÉATION ADRESSE ===');
        error_log('Method: ' . $request->getMethod());
        error_log('Content-Type: ' . $request->headers->get('Content-Type'));
        error_log('Is AJAX: ' . ($request->isXmlHttpRequest() ? 'OUI' : 'NON'));
        error_log('Données POST: ' . print_r($request->request->all(), true));
        error_log('Files: ' . print_r($request->files->all(), true));
        error_log('User connecté: ' . ($this->getUser() ? $this->getUser()->getEmail() : 'NON CONNECTÉ'));
        
        $address = new Addresses();
        $form = $this->createForm(AddressFormType::class, $address);
        $form->handleRequest($request);

        error_log('Form submitted: ' . ($form->isSubmitted() ? 'OUI' : 'NON'));
        error_log('Form valid: ' . ($form->isValid() ? 'OUI' : 'NON'));
        
        if ($form->isSubmitted()) {
            if (!$form->isValid()) {
                error_log('Erreurs de formulaire: ' . (string) $form->getErrors(true, false));
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            // Associer l'adresse à l'utilisateur connecté
            $user = $this->getUser();
            $address->setUsers($user);
            
            error_log('Utilisateur: ' . ($user ? $user->getEmail() : 'NULL'));
            error_log('Adresse avant persist: ' . $address->getStreet() . ', ' . $address->getCity());
            
            $entityManager->persist($address);
            $entityManager->flush();
            
            error_log('Adresse sauvegardée avec ID: ' . $address->getId());

            // Retourner les données de la nouvelle adresse pour mise à jour frontend
            return $this->json([
                'success' => true,
                'message' => 'Adresse ajoutée avec succès !',
                'address' => [
                    'id' => $address->getId(),
                    'label' => sprintf('%s %s, %s %s, %s', 
                        $address->getNumber(), 
                        $address->getStreet(), 
                        $address->getZipCode(), 
                        $address->getCity(), 
                        $address->getCountry()
                    ),
                    'type' => $address->getType()
                ]
            ]);
        }

        // En cas d'erreur de validation, retourner les erreurs avec le formulaire re-rendu
        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }

        return $this->json([
            'success' => false,
            'errors' => $errors,
            'form_html' => $this->renderView('address/_modal_form.html.twig', [
                'form' => $form,
                'address' => $address,
                'modal_title' => 'Ajouter une nouvelle adresse'
            ])
        ], 400);
    }

    /**
     * Liste les adresses de l'utilisateur (pour AJAX)
     * 
     * Utilisé notamment dans le checkout pour peupler les sélecteurs d'adresses.
     * Peut filtrer par type d'adresse (shipping, billing, home).
     */
    #[Route('/list', name: 'app_address_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $type = $request->query->get('type');
        
        $addresses = $user->getAddresses();
        
        // Filtrer par type si spécifié
        if ($type) {
            $addresses = $addresses->filter(function(Addresses $address) use ($type) {
                return $address->getType() === $type;
            });
        }

        $addressData = [];
        foreach ($addresses as $address) {
            $addressData[] = [
                'id' => $address->getId(),
                'label' => sprintf('%s %s, %s %s, %s', 
                    $address->getNumber(), 
                    $address->getStreet(), 
                    $address->getZipCode(), 
                    $address->getCity(), 
                    $address->getCountry()
                ),
                'type' => $address->getType()
            ];
        }

        return $this->json([
            'success' => true,
            'addresses' => $addressData
        ]);
    }

    /**
     * Page de gestion des adresses (optionnelle, pour plus tard)
     * 
     * Interface principale pour visualiser et gérer toutes les adresses.
     * Complément à l'interface modale pour une vue d'ensemble.
     */
    #[Route('/', name: 'app_address_index')]
    public function index(): Response
    {
        $user = $this->getUser();
        $addresses = $user->getAddresses();

        return $this->render('address/index.html.twig', [
            'addresses' => $addresses
        ]);
    }

    /**
     * Affiche la modal d'édition d'adresse (AJAX)
     * 
     * Charge une adresse existante dans le formulaire pour modification.
     * Vérifie que l'adresse appartient bien à l'utilisateur connecté.
     */
    #[Route('/{id}/edit-modal', name: 'app_address_edit_modal', methods: ['GET'])]
    public function editModal(Addresses $address): Response
    {
        // Vérifier que l'adresse appartient à l'utilisateur connecté
        if ($address->getUsers() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Cette adresse ne vous appartient pas.');
        }

        $form = $this->createForm(AddressFormType::class, $address);

        return $this->render('address/_modal_form.html.twig', [
            'form' => $form,
            'address' => $address,
            'modal_title' => 'Modifier l\'adresse'
        ]);
    }

    /**
     * Traite la modification d'adresse via AJAX
     * 
     * Met à jour une adresse existante avec les nouvelles données du formulaire.
     * Vérifie la propriété et valide avant sauvegarde.
     */
    #[Route('/{id}/update', name: 'app_address_update', methods: ['POST'])]
    public function update(Addresses $address, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        // Vérifier que l'adresse appartient à l'utilisateur connecté
        if ($address->getUsers() !== $this->getUser()) {
            return $this->json([
                'success' => false,
                'error' => 'Cette adresse ne vous appartient pas.'
            ], 403);
        }

        $form = $this->createForm(AddressFormType::class, $address);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Adresse modifiée avec succès !',
                'address' => [
                    'id' => $address->getId(),
                    'label' => sprintf('%s %s, %s %s, %s', 
                        $address->getNumber(), 
                        $address->getStreet(), 
                        $address->getZipCode(), 
                        $address->getCity(), 
                        $address->getCountry()
                    ),
                    'type' => $address->getType()
                ]
            ]);
        }

        // En cas d'erreur, retourner les erreurs de validation
        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }

        return $this->json([
            'success' => false,
            'errors' => $errors,
            'form_html' => $this->renderView('address/_modal_form.html.twig', [
                'form' => $form,
                'address' => $address,
                'modal_title' => 'Modifier l\'adresse'
            ])
        ], 400);
    }

    /**
     * Supprime une adresse (AJAX)
     * 
     * Suppression sécurisée avec vérifications :
     * - Propriété de l'adresse
     * - Protection CSRF
     * - Vérification qu'elle n'est pas utilisée dans des commandes
     */
    #[Route('/{id}/delete', name: 'app_address_delete', methods: ['POST'])]
    public function delete(Addresses $address, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        // Vérifier que l'adresse appartient à l'utilisateur connecté
        if ($address->getUsers() !== $this->getUser()) {
            return $this->json([
                'success' => false,
                'error' => 'Cette adresse ne vous appartient pas.'
            ], 403);
        }

        // Vérifier le token CSRF pour éviter les attaques
        if (!$this->isCsrfTokenValid('delete_address' . $address->getId(), $request->request->get('_token'))) {
            return $this->json([
                'success' => false,
                'error' => 'Token CSRF invalide.'
            ], 403);
        }

        // Vérifier que l'adresse n'est pas utilisée dans des commandes
        if (!$address->getOrders()->isEmpty()) {
            return $this->json([
                'success' => false,
                'error' => 'Impossible de supprimer cette adresse car elle est liée à des commandes.'
            ], 400);
        }

        $entityManager->remove($address);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Adresse supprimée avec succès !'
        ]);
    }
} 