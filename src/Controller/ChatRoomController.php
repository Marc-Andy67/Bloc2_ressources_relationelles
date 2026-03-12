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
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $chatRooms = $chatRoomRepository->findUserChatRoomsWithLastMessage($user);

        return $this->render('chat_room/index.html.twig', [
            'chat_rooms' => $chatRooms,
        ]);
    }

    /**
     * Rejoint le salon lié à une ressource ou fait une demande.
     */
    #[Route('/join/{id}', name: 'app_chat_room_join_or_create', methods: ['GET', 'POST'])]
    public function joinOrCreate(Request $request, Ressource $ressource, ChatRoomRepository $chatRoomRepository, EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $chatRoom = $chatRoomRepository->findOneBy(['Ressource' => $ressource]);

        if (!$chatRoom) {
            // Créer le salon s'il n'existe pas
            $chatRoom = new ChatRoom();
            $chatRoom->setName('Discussion : ' . $ressource->getTitle());
            $chatRoom->setRessource($ressource);
            
            // L'auteur de la ressource est l'hôte par défaut
            if ($ressource->getAuthor()) {
                 $chatRoom->addMember($ressource->getAuthor());
            }
            // Si le visiteur courant n'est pas l'auteur, il rejoint aussi en tant que 1er membre s'il crée le salon
            if ($ressource->getAuthor() !== $user) {
                 $chatRoom->addMember($user);
            }
            $em->persist($chatRoom);
            $em->flush();
            return $this->redirectToRoute('app_chat_room_show', ['id' => $chatRoom->getId()]);
        }

        // Si l'utilisateur est déjà membre
        if ($chatRoom->getMembers()->contains($user)) {
            return $this->redirectToRoute('app_chat_room_show', ['id' => $chatRoom->getId()]);
        }
        
        // Si l'utilisateur est l'hôte de la ressource, on l'ajoute direct s'il n'y était pas
        if ($ressource->getAuthor() === $user) {
             $chatRoom->addMember($user);
             $em->flush();
             return $this->redirectToRoute('app_chat_room_show', ['id' => $chatRoom->getId()]);
        }

        // Demande d'accès (Utilisateur non autorisé)
        if ($chatRoom->getPendingMembers()->contains($user)) {
            $this->addFlash('info', 'Votre demande est en cours de traitement par l\'hôte.');
            return $this->redirectToRoute('app_home');
        }

        if ($request->isMethod('POST')) {
            $chatRoom->addPendingMember($user);
            
            // Message système
            $username = explode('@', ltrim((string) $user->getEmail()))[0];
            $systemMessage = new \App\Entity\ChatMessage();
            $systemMessage->setChatRoom($chatRoom);
            $systemMessage->setAuthor($user); // Ou idéalement un compte système
            $systemMessage->setContent("{$username} souhaite rejoindre le salon.");
            $systemMessage->setCreationDate(new \DateTime());
            $em->persist($systemMessage);

            $em->flush();
            // TODO: dispatch Mercure if we want it real time instantly for the host

            $this->addFlash('success', 'Votre demande a été envoyée à l\'hôte du salon.');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('chat_room/request_join.html.twig', [
            'chat_room' => $chatRoom,
        ]);
    }

    #[Route('/{id}/accept/{userId}', name: 'app_chat_room_accept', methods: ['POST'])]
    public function accept(Request $request, ChatRoom $chatRoom, string $userId, \App\Repository\UserRepository $userRepo, EntityManagerInterface $em): Response
    {
        $member = $userRepo->find($userId);
        if (!$member || !$chatRoom->getPendingMembers()->contains($member)) {
            throw $this->createNotFoundException();
        }

        // Seul l'hôte (ou un admin) peut accepter
        $host = $chatRoom->getRessource()->getAuthor();
        if ($this->getUser() !== $host && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('accept' . $userId, $request->getPayload()->getString('_token'))) {
            $chatRoom->removePendingMember($member);
            $chatRoom->addMember($member);
            
            $username = explode('@', ltrim((string) $member->getEmail()))[0];
            $msg = new \App\Entity\ChatMessage();
            $msg->setChatRoom($chatRoom);
            $msg->setAuthor($host);
            $msg->setContent("{$username} a rejoint le salon.");
            $msg->setCreationDate(new \DateTime());
            $em->persist($msg);
            
            $em->flush();
        }

        return $this->redirectToRoute('app_chat_room_show', ['id' => $chatRoom->getId()]);
    }

    #[Route('/{id}/refuse/{userId}', name: 'app_chat_room_refuse', methods: ['POST'])]
    public function refuse(Request $request, ChatRoom $chatRoom, string $userId, \App\Repository\UserRepository $userRepo, EntityManagerInterface $em): Response
    {
        $member = $userRepo->find($userId);
        if (!$member || !$chatRoom->getPendingMembers()->contains($member)) {
            throw $this->createNotFoundException();
        }

        $host = $chatRoom->getRessource()->getAuthor();
        if ($this->getUser() !== $host && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('refuse' . $userId, $request->getPayload()->getString('_token'))) {
            $chatRoom->removePendingMember($member);
            $em->flush();
        }

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
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if (!$chatRoom->getMembers()->contains($user) && !$this->isGranted('ROLE_ADMIN')) {
            if ($chatRoom->getRessource()) {
                // If they are not a member, redirect them to the join logic so they can submit a request 
                // instead of simply being blocked or seeing the chat content.
                return $this->redirectToRoute('app_chat_room_join_or_create', ['id' => $chatRoom->getRessource()->getId()]);
            }
            throw $this->createAccessDeniedException('Vous n\'êtes pas membre de ce salon.');
        }

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
