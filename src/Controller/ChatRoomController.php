<?php

namespace App\Controller;

use App\Entity\ChatRoom;
use App\Entity\Ressource;
use App\Repository\ChatRoomRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/chat/room')]
final class ChatRoomController extends AbstractController
{
    #[Route(name: 'app_chat_room_index', methods: ['GET'])]
    public function index(ChatRoomRepository $chatRoomRepository): Response
    {
        return $this->render('chat_room/index.html.twig', [
            'chat_rooms' => $chatRoomRepository->findAll(),
        ]);
    }

    #[Route('/mine', name: 'app_chat_room_mine', methods: ['GET'])]
    public function myChatRooms(ChatRoomRepository $chatRoomRepository): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $chatRooms = $chatRoomRepository->findUserChatRoomsWithLastMessage($user);

        return $this->render('chat_room/mine.html.twig', [
            'chat_rooms' => $chatRooms,
        ]);
    }

    /**
     * Rejoint le salon lié à une ressource (le crée s'il n'existe pas).
     */
    #[Route('/join/{id}', name: 'app_chat_room_join_or_create', methods: ['GET'])]
    public function joinOrCreate(Ressource $ressource, ChatRoomRepository $chatRoomRepository, EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Chercher un salon existant pour cette ressource
        $chatRoom = $chatRoomRepository->findOneBy(['Ressource' => $ressource]);

        if (!$chatRoom) {
            // Créer le salon s'il n'existe pas
            $chatRoom = new ChatRoom();
            $chatRoom->setName('Discussion : ' . $ressource->getTitle());
            $chatRoom->setRessource($ressource);
            $chatRoom->addMember($user);
            $em->persist($chatRoom);
        } elseif (!$chatRoom->getMembers()->contains($user)) {
            // Ajouter l'utilisateur comme membre s'il ne l'est pas déjà
            $chatRoom->addMember($user);
        }

        $em->flush();

        return $this->redirectToRoute('app_chat_room_show', ['id' => $chatRoom->getId()]);
    }

    #[Route('/new', name: 'app_chat_room_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $chatRoom = new ChatRoom();
        $chatRoom->setName('Nouveau salon');
        $entityManager->persist($chatRoom);
        $entityManager->flush();

        return $this->redirectToRoute('app_chat_room_show', ['id' => $chatRoom->getId()]);
    }

    #[Route('/{id}', name: 'app_chat_room_show', methods: ['GET'])]
    public function show(ChatRoom $chatRoom): Response
    {
        return $this->render('chat_room/show.html.twig', [
            'chat_room' => $chatRoom,
        ]);
    }

    #[Route('/{id}', name: 'app_chat_room_delete', methods: ['POST'])]
    public function delete(Request $request, ChatRoom $chatRoom, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $chatRoom->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($chatRoom);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_chat_room_index', [], Response::HTTP_SEE_OTHER);
    }
}
