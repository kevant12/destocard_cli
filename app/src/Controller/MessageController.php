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
 * Contrôleur pour la gestion des messages entre utilisateurs.
 * Code simple, structuré, pédagogique.
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
     * Liste des conversations de l'utilisateur connecté
     */
    #[Route('/', name: 'app_message_index')]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        $user = $this->getUser();
        $conversations = $this->messageService->getUserConversations($user);
        return $this->render('message/index.html.twig', [
            'conversations' => $conversations,
        ]);
    }

    /**
     * Affiche une conversation avec un autre utilisateur (optionnellement autour d'un produit)
     */
    #[Route('/conversation/{id}', name: 'app_message_conversation')]
    #[IsGranted('ROLE_USER')]
    public function conversation(Users $otherUser, Request $request): Response
    {
        $user = $this->getUser();
        $messages = $this->messageService->getConversation($user, $otherUser);
        $this->messageService->markMessagesAsRead($user, $otherUser);
        $form = $this->createForm(MessageFormType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->messageService->sendMessage($user, $otherUser, $form->get('content')->getData());
            $this->addFlash('success', 'Message envoyé !');
            return $this->redirectToRoute('app_message_conversation', ['id' => $otherUser->getId()]);
        }
        return $this->render('message/conversation.html.twig', [
            'messages' => $messages,
            'form' => $form,
            'otherUser' => $otherUser,
        ]);
    }
} 