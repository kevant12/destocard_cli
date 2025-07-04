<?php

namespace App\Controller;

use App\Entity\Messages;
use App\Entity\Users;
use App\Entity\Products;
use App\Form\MessageFormType;
use App\Service\MessageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * Contrôleur de gestion des messages entre utilisateurs
 * 
 * Fonctionnalités principales :
 * - Affichage de la liste des conversations de l'utilisateur
 * - Interface de messagerie privée entre acheteurs et vendeurs
 * - Gestion des conversations avec historique et statut de lecture
 * - Envoi de nouveaux messages avec validation et protection CSRF
 * 
 * Utilise MessageService pour centraliser la logique métier des messages
 * Code simple et structuré, adapté pour l'apprentissage
 */
#[Route('/messages')]
class MessageController extends AbstractController
{
    private MessageService $messageService;
    private EntityManagerInterface $em;

    public function __construct(MessageService $messageService, EntityManagerInterface $em)
    {
        $this->messageService = $messageService;
        $this->em = $em;
    }

    /**
     * Affiche la liste des conversations de l'utilisateur connecté
     * 
     * Cette méthode présente un tableau de bord des conversations avec :
     * - Liste de tous les interlocuteurs avec qui l'utilisateur a échangé
     * - Aperçu du dernier message de chaque conversation
     * - Compteur de messages non lus pour chaque conversation
     * - Tri par date du dernier message (plus récent en premier)
     * 
     * @return Response La page de liste des conversations
     */
    #[Route('/', name: 'app_message_index')]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        $user = $this->getUser();
        
        // Récupérer toutes les conversations groupées par interlocuteur
        // MessageService gère la logique complexe de regroupement
        $conversations = $this->messageService->getUserConversations($user);
        
        return $this->render('message/index.html.twig', [
            'conversations' => $conversations,
        ]);
    }

    /**
     * Affiche une conversation complète avec un autre utilisateur
     * 
     * Interface de messagerie complète permettant de :
     * - Voir l'historique complet des messages échangés
     * - Envoyer un nouveau message via le formulaire intégré
     * - Marquer automatiquement les messages comme lus lors de la consultation
     * - Scroll automatique vers le dernier message (géré en JavaScript)
     * 
     * @param Users $otherUser L'utilisateur avec qui on souhaite converser
     * @param Request $request Pour traiter le formulaire d'envoi de message
     * @return Response La page de conversation avec formulaire d'envoi
     */
    #[Route('/conversation/{id}', name: 'app_message_conversation')]
    #[IsGranted('ROLE_USER')]
    public function conversation(Users $otherUser, Request $request): Response
    {
        $user = $this->getUser();
        
        // Récupérer tous les messages entre les deux utilisateurs, triés chronologiquement
        $messages = $this->messageService->getConversation($user, $otherUser);
        
        // Marquer automatiquement les messages reçus comme lus
        // Améliore l'expérience utilisateur et met à jour les compteurs
        $this->messageService->markMessagesAsRead($user, $otherUser);
        
        // Créer le formulaire d'envoi de nouveau message
        $form = $this->createForm(MessageFormType::class);
        $form->handleRequest($request);
        
        // Traiter l'envoi de nouveau message
        if ($form->isSubmitted() && $form->isValid()) {
            // Utiliser le service pour centraliser la logique d'envoi
            $this->messageService->sendMessage($user, $otherUser, $form->get('content')->getData());
            
            $this->addFlash('success', 'Message envoyé !');
            
            // Redirection Pattern Post-Redirect-Get pour éviter le double envoi
            return $this->redirectToRoute('app_message_conversation', ['id' => $otherUser->getId()]);
        }
        
        return $this->render('message/conversation.html.twig', [
            'messages' => $messages,
            'form' => $form,
            'otherUser' => $otherUser,
        ]);
    }
} 