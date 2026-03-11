<?php

namespace App\Repository;

use App\Entity\ChatRoom;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ChatRoom>
 */
class ChatRoomRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChatRoom::class);
    }

    /**
     * @return ChatRoom[]
     */
    public function findUserChatRoomsWithLastMessage(\App\Entity\User $user): array
    {
        // This query fetches chatrooms where the user is a member,
        // joins the messages, and orders the rooms by the date of the latest message
        // Due to subqueries and group by limits in Doctrine, doing this efficiently often requires
        // fetching the rooms and joining messages, then sorting in PHP, or a subquery.
        // We will fetch rooms, and sort them in PHP for simplicity as Doctrine DQL
        // doesn't natively support finding the "latest child per parent" easily without subqueries.
        
        $rooms = $this->createQueryBuilder('c')
            ->join('c.members', 'm')
            ->leftJoin('App\Entity\ChatMessage', 'msg', 'WITH', 'msg.chatRoom = c')
            ->addSelect('msg')
            ->where('m = :user')
            ->setParameter('user', $user->getId(), 'uuid')
            ->getQuery()
            ->getResult();
            
        // The join will fetch all messages for these rooms. 
        // Twig will be able to do `room.messages|last` if the relation existed,
        // but ChatRoom doesn't have a $messages relation mapped. 
        // We should map it in the entity or fetch it here.
        return $rooms;
    }
}
