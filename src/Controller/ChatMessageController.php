<?php

namespace App\Controller;

use App\Entity\ChatMessage;
use App\Form\ChatMessageType;
use App\Repository\ChatMessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/chat/message')]
final class ChatMessageController extends AbstractController
{
    #[Route(name: 'app_chat_message_index', methods: ['GET'])]
    public function index(ChatMessageRepository $chatMessageRepository): Response
    {
        return $this->render('chat_message/index.html.twig', [
            'chat_messages' => $chatMessageRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_chat_message_new', methods: ['POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager,
        \App\Repository\ChatRoomRepository $chatRoomRepository,
        \Symfony\Component\Mercure\HubInterface $hub
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthenticated'], 401);
        }

        $roomId = $request->request->get('chatRoom');
        $content = $request->request->get('content');

        if (!$roomId || !$content) {
            return $this->redirectToRoute('app_chat_room_index');
        }

        $chatRoom = $chatRoomRepository->find($roomId);
        if (!$chatRoom) {
            return $this->redirectToRoute('app_chat_room_index');
        }

        $chatMessage = new ChatMessage();
        $chatMessage->setContent($content);
        $chatMessage->setCreationDate(new \DateTime());
        $chatMessage->setAuthor($user);
        $chatRoom->addMessage($chatMessage);

        $entityManager->persist($chatMessage);
        $entityManager->flush();

        // Broadcast the new message via Mercure
        $update = new \Symfony\Component\Mercure\Update(
            'chat_room_' . $chatRoom->getId(),
            $this->renderView('chat_room/_message.stream.html.twig', [
                'message' => $chatMessage
            ])
        );
        $hub->publish($update);

        if (\Symfony\UX\Turbo\TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
            $request->setRequestFormat(\Symfony\UX\Turbo\TurboBundle::STREAM_FORMAT);
            
            // Return an empty form to replace the submitted one
            return $this->render('chat_room/_message_form.html.twig', [
                'chat_room' => $chatRoom
            ]);
        }

        return $this->redirectToRoute('app_chat_room_show', ['id' => $chatRoom->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}', name: 'app_chat_message_show', methods: ['GET'])]
    public function show(ChatMessage $chatMessage): Response
    {
        return $this->render('chat_message/show.html.twig', [
            'chat_message' => $chatMessage,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_chat_message_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ChatMessage $chatMessage, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ChatMessageType::class, $chatMessage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_chat_message_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('chat_message/edit.html.twig', [
            'chat_message' => $chatMessage,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_chat_message_delete', methods: ['POST'])]
    public function delete(Request $request, ChatMessage $chatMessage, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$chatMessage->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($chatMessage);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_chat_message_index', [], Response::HTTP_SEE_OTHER);
    }
}
