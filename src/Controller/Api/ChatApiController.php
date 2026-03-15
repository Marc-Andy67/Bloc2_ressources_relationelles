<?php

namespace App\Controller\Api;

use App\Entity\ChatMessage;
use App\Entity\ChatRoom;
use App\Repository\ChatMessageRepository;
use App\Repository\ChatRoomRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/chat', name: 'api_chat_')]
class ChatApiController extends AbstractController
{
    #[Route('/rooms', name: 'rooms_index', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function rooms(ChatRoomRepository $chatRoomRepository): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        // Return all chat rooms accessible by the user (or public ones)
        // Here we just pull all to match the web logic, or customize it if there's user-specific logic
        $rooms = $chatRoomRepository->findAll();
        
        $data = array_map(function ($room) {
            return [
                'id' => (string) $room->getId(),
                'name' => $room->getName(),
                'ressourceId' => $room->getRessource() ? (string) $room->getRessource()->getId() : null,
            ];
        }, $rooms);

        return $this->json($data);
    }

    #[Route('/rooms/{id}/messages', name: 'room_messages', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getMessages(ChatRoom $chatRoom, ChatMessageRepository $chatMessageRepository): JsonResponse
    {
        $messages = $chatMessageRepository->findBy(
            ['chatRoom' => $chatRoom],
            ['creationDate' => 'ASC'] // Chronological order for chat UI
        );

        $data = array_map(function ($msg) {
            return [
                'id' => (string) $msg->getId(),
                'content' => $msg->getContent(),
                'sentAt' => $msg->getCreationDate()?->format(\DateTime::ATOM),
                'author' => [
                    'id' => (string) $msg->getAuthor()->getId(),
                    'name' => $msg->getAuthor()->getName() ?? $msg->getAuthor()->getUserIdentifier(),
                ]
            ];
        }, $messages);

        return $this->json($data);
    }

    #[Route('/rooms/{id}/messages', name: 'post_message', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function postMessage(
        Request $request,
        ChatRoom $chatRoom,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        if (empty($data['content'])) {
            return $this->json(['error' => 'Le message ne peut pas être vide.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $message = new ChatMessage();
        $message->setContent($data['content']);
        $message->setAuthor($user);
        $message->setChatRoom($chatRoom);
        $message->setCreationDate(new \DateTime());

        $entityManager->persist($message);
        $entityManager->flush();

        // Normally here we would broadcast via Mercure. The Web App uses Mercure.
        // For the API, the mobile app could use SSE (Server-Sent Events) or polling.

        return $this->json([
            'id' => (string) $message->getId(),
            'content' => $message->getContent(),
            'sentAt' => $message->getCreationDate()?->format(\DateTime::ATOM),
            'author' => [
                'id' => (string) $user->getId(),
                'name' => $user->getName() ?? $user->getUserIdentifier(),
            ]
        ], JsonResponse::HTTP_CREATED);
    }
}
