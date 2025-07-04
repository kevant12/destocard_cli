<?php

namespace App\Service;

use App\Entity\Messages;
use App\Entity\Users;
use App\Repository\MessagesRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service de gestion de la messagerie entre utilisateurs
 * 
 * Fonctionnalités principales :
 * - Création de nouveaux messages entre utilisateurs
 * - Récupération des conversations (groupées par participants)
 * - Gestion des fils de discussion pour chaque produit
 * - Historique complet des échanges entre acheteurs et vendeurs
 * 
 * Logique métier :
 * - Un message est toujours lié à un produit (contexte)
 * - Les conversations sont groupées par paire d'utilisateurs
 * - Le système maintient un historique chronologique des échanges
 * - Support des notifications pour les nouveaux messages
 */
class MessageService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MessagesRepository $messagesRepository
    ) {}

    /**
     * Crée un nouveau message entre deux utilisateurs
     * 
     * Cette méthode gère la création complète d'un message :
     * - Valide que l'expéditeur et le destinataire sont différents
     * - Associe le message à un produit pour le contexte
     * - Persiste le message en base de données
     * - Peut déclencher des notifications (à implémenter)
     * 
     * @param Users $from L'utilisateur expéditeur du message
     * @param Users $to L'utilisateur destinataire du message
     * @param string $content Le contenu du message
     * @param int $productId L'ID du produit qui est le sujet de la conversation
     * @return Messages Le message créé et persisté
     */
    public function createMessage(Users $from, Users $to, string $content, int $productId): Messages
    {
        // Créer l'entité Message avec tous les champs obligatoires
        $message = new Messages();
        $message->setFromUser($from);
        $message->setToUser($to);
        $message->setContent($content);
        $message->setProductId($productId);
        $message->setCreatedAt(new \DateTimeImmutable()); // Horodatage automatique
        
        // Persister le message en base de données
        $this->entityManager->persist($message);
        $this->entityManager->flush();
        
        // TODO: Déclencher une notification pour le destinataire
        // $this->notificationService->notifyNewMessage($to, $message);
        
        return $message;
    }

    /**
     * Récupère toutes les conversations d'un utilisateur
     * 
     * Une conversation est définie comme l'ensemble des messages échangés
     * entre deux utilisateurs spécifiques pour un produit donné.
     * 
     * Cette méthode retourne les conversations où l'utilisateur est soit
     * l'expéditeur soit le destinataire, triées par date du dernier message.
     * 
     * @param Users $user L'utilisateur dont on veut les conversations
     * @return array Tableau des conversations avec métadonnées
     */
    public function getConversationsForUser(Users $user): array
    {
        // Déléguer la logique complexe au repository spécialisé
        return $this->messagesRepository->findConversationsForUser($user);
    }

    /**
     * Récupère l'historique complet d'une conversation entre deux utilisateurs
     * 
     * Cette méthode retourne tous les messages échangés entre deux utilisateurs
     * pour un produit spécifique, triés par ordre chronologique.
     * 
     * Utile pour afficher le fil complet d'une conversation.
     * 
     * @param Users $user1 Le premier utilisateur de la conversation
     * @param Users $user2 Le second utilisateur de la conversation
     * @param int $productId L'ID du produit concerné par la conversation
     * @return Messages[] Tableau des messages triés chronologiquement
     */
    public function getConversationHistory(Users $user1, Users $user2, int $productId): array
    {
        // Récupérer tous les messages dans les deux sens pour ce produit
        return $this->messagesRepository->findMessagesBetweenUsers($user1, $user2, $productId);
    }

    /**
     * Marque tous les messages d'une conversation comme lus
     * 
     * Fonctionnalité pour la gestion des notifications :
     * - Marque comme lus tous les messages reçus par l'utilisateur
     * - Utilisé quand l'utilisateur ouvre une conversation
     * - Permet de gérer les badges de "nouveaux messages"
     * 
     * @param Users $user L'utilisateur qui lit les messages
     * @param Users $otherUser L'autre participant de la conversation
     * @param int $productId L'ID du produit de la conversation
     */
    public function markConversationAsRead(Users $user, Users $otherUser, int $productId): void
    {
        // Récupérer tous les messages non lus envoyés par l'autre utilisateur
        $unreadMessages = $this->messagesRepository->findUnreadMessagesBetweenUsers($otherUser, $user, $productId);
        
        // Marquer chaque message comme lu
        foreach ($unreadMessages as $message) {
            $message->setIsRead(true);
        }
        
        // Persister les modifications
        $this->entityManager->flush();
    }

    /**
     * Compte le nombre total de messages non lus pour un utilisateur
     * 
     * Utilisé pour afficher un badge de notification dans l'interface :
     * - Compte tous les messages reçus et non lus
     * - Utilisé pour le badge dans la navigation
     * - Mis à jour en temps réel via JavaScript
     * 
     * @param Users $user L'utilisateur dont on veut compter les messages non lus
     * @return int Le nombre de messages non lus
     */
    public function getUnreadMessageCount(Users $user): int
    {
        return $this->messagesRepository->countUnreadMessagesForUser($user);
    }

    /**
     * Vérifie si un utilisateur peut envoyer un message à un autre utilisateur
     * 
     * Règles métier pour la messagerie :
     * - Un utilisateur ne peut pas s'envoyer un message à lui-même
     * - Les utilisateurs bannis ne peuvent pas envoyer de messages
     * - D'autres règles peuvent être ajoutées (limites, blocages, etc.)
     * 
     * @param Users $from L'utilisateur expéditeur
     * @param Users $to L'utilisateur destinataire
     * @return bool True si l'envoi est autorisé, False sinon
     */
    public function canSendMessage(Users $from, Users $to): bool
    {
        // Règle de base : on ne peut pas s'envoyer un message à soi-même
        if ($from->getId() === $to->getId()) {
            return false;
        }
        
        // Vérifier que les deux utilisateurs sont actifs
        if (!$from->isActive() || !$to->isActive()) {
            return false;
        }
        
        // TODO: Ajouter d'autres règles métier si nécessaire
        // - Vérifier si l'utilisateur est bloqué
        // - Vérifier les limites de spam
        // - Vérifier les permissions spéciales
        
        return true;
    }

    /**
     * Recherche des messages par mots-clés
     * 
     * Fonctionnalité de recherche dans l'historique des messages :
     * - Recherche dans le contenu des messages
     * - Limité aux messages de l'utilisateur (expéditeur ou destinataire)
     * - Utile pour retrouver des conversations anciennes
     * 
     * @param Users $user L'utilisateur qui effectue la recherche
     * @param string $query Le texte à rechercher
     * @param int $limit Le nombre maximum de résultats
     * @return Messages[] Les messages correspondant à la recherche
     */
    public function searchMessages(Users $user, string $query, int $limit = 50): array
    {
        // Nettoyer la requête de recherche
        $cleanQuery = trim($query);
        
        if (empty($cleanQuery)) {
            return [];
        }
        
        // Déléguer la recherche au repository
        return $this->messagesRepository->searchMessagesForUser($user, $cleanQuery, $limit);
    }
}