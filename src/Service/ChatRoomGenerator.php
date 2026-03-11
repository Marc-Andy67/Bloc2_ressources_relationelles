<?php

namespace App\Service;

use App\Entity\ChatRoom;
use App\Entity\Ressource;
use App\Repository\ChatRoomRepository;
use Doctrine\ORM\EntityManagerInterface;

class ChatRoomGenerator
{
    private EntityManagerInterface $entityManager;
    private ChatRoomRepository $chatRoomRepository;

    public function __construct(EntityManagerInterface $entityManager, ChatRoomRepository $chatRoomRepository)
    {
        $this->entityManager = $entityManager;
        $this->chatRoomRepository = $chatRoomRepository;
    }

    /**
     * Crée automatiquement un salon de discussion pour une ressource si elle remplit
     * les conditions : catégorie 'jeu', statut 'validated', et pas de salon existant.
     */
    public function generateForJeu(Ressource $ressource): void
    {
        if ($ressource->getStatus() !== 'validated') {
            return;
        }

        $category = $ressource->getCategory();
        if (!$category || strcasecmp($category->getName(), 'jeu') !== 0) {
            return;
        }

        // Eviter les doublons de salon
        $existing = $this->chatRoomRepository->findOneBy(['Ressource' => $ressource]);
        if ($existing) {
            return;
        }

        $chatRoom = new ChatRoom();
        $chatRoom->setName('Discussion : ' . $ressource->getTitle());
        $chatRoom->setRessource($ressource);
        
        $this->entityManager->persist($chatRoom);
        $this->entityManager->flush();
    }
}
