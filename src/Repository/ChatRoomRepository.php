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
        // and carefully joins the mapped messages collection so Doctrine
        // hydrates them properly without returning mixed results.
        
        $rooms = $this->createQueryBuilder('c')
            ->join('c.members', 'm')
            ->leftJoin('c.messages', 'msg')
            ->addSelect('msg')
            ->where('m = :user')
            ->setParameter('user', $user->getId(), 'uuid')
            ->getQuery()
            ->getResult();
            
        return $rooms;
    }
}
