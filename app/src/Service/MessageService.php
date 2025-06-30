<?php

namespace App\Service;

use App\Entity\Messages;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;

class MessageService
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function getUserConversations(Users $user): array
    {
        // Fetch all messages involving the current user, ordered by date descending
        $messages = $this->em->getRepository(Messages::class)->createQueryBuilder('m')
            ->select('m', 's', 'r')
            ->leftJoin('m.sender', 's')
            ->leftJoin('m.receper', 'r')
            ->where('s = :user OR r = :user')
            ->setParameter('user', $user)
            ->orderBy('m.expeditionDate', 'DESC')
            ->getQuery()
            ->getResult();

        $conversations = [];
        foreach ($messages as $message) {
            // Determine the other participant in the conversation
            $otherUser = ($message->getSender()->getId() === $user->getId()) ? $message->getReceper() : $message->getSender();

            // Use a consistent key for each conversation pair (e.g., smaller_id_larger_id)
            $participantIds = [$user->getId(), $otherUser->getId()];
            sort($participantIds); // Ensure consistent order
            $conversationKey = implode('_', $participantIds);

            // If this is the first message encountered for this conversation, initialize it
            if (!isset($conversations[$conversationKey])) {
                $conversations[$conversationKey] = [
                    'otherUser' => $otherUser,
                    'lastMessage' => $message,
                    'unreadCount' => 0,
                ];
            }

            // Increment unread count if the message is for the current user and is 'sent'
            if ($message->getStatus() === 'sent' && $message->getReceper() && $message->getReceper()->getId() === $user->getId()) {
                $conversations[$conversationKey]['unreadCount']++;
            }
        }

        // Convert associative array to indexed array and sort by last message date (most recent first)
        uasort($conversations, function($a, $b) {
            return $b['lastMessage']->getExpeditionDate() <=> $a['lastMessage']->getExpeditionDate();
        });

        return array_values($conversations); // Return as a simple indexed array
    }

    public function getConversation(Users $user1, Users $user2): array
    {
        $query = $this->em->getRepository(Messages::class)->createQueryBuilder('m')
            ->where('(m.sender = :user1 AND m.receper = :user2) OR (m.sender = :user2 AND m.receper = :user1)')
            ->setParameter('user1', $user1)
            ->setParameter('user2', $user2)
            ->orderBy('m.expeditionDate', 'ASC')
            ->getQuery();

        return $query->getResult();
    }

    public function sendMessage(Users $sender, Users $receper, string $content): void
    {
        $message = new Messages();
        $message->setSender($sender);
        $message->setReceper($receper);
        $message->setContent($content);
        $message->setExpeditionDate(new \DateTimeImmutable());
        $message->setStatus('sent');
        $message->setIsRead(false); // Nouveau message est non lu par défaut

        $this->em->persist($message);
        $this->em->flush();
    }

    public function markMessagesAsRead(Users $user1, Users $user2): void
    {
        $this->em->getRepository(Messages::class)->createQueryBuilder('m')
            ->update()
            ->set('m.isRead', 'true')
            ->where('m.receper = :user1 AND m.sender = :user2')
            ->andWhere('m.isRead = false')
            ->setParameter('user1', $user1)
            ->setParameter('user2', $user2)
            ->getQuery()
            ->execute();
    }
}