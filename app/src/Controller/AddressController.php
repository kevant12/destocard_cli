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

#[Route('/address')]
#[IsGranted('ROLE_USER')]
class AddressController extends AbstractController
{
    /**
     * Affiche la modal de création d'adresse (AJAX)
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
     */
    #[Route('/create', name: 'app_address_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        // Debug: Log des données reçues
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

            // Retourner les données de la nouvelle adresse
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
                'modal_title' => 'Ajouter une nouvelle adresse'
            ])
        ], 400);
    }

    /**
     * Liste les adresses de l'utilisateur (pour AJAX)
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
} 