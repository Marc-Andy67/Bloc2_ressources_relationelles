<?php

namespace App\Controller;

use App\Entity\ChatRoom;
use App\Form\ChatRoomType;
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

    #[Route('/new', name: 'app_chat_room_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $chatRoom = new ChatRoom();
        $form = $this->createForm(ChatRoomType::class, $chatRoom);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($chatRoom);
            $entityManager->flush();

            return $this->redirectToRoute('app_chat_room_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('chat_room/new.html.twig', [
            'chat_room' => $chatRoom,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_chat_room_show', methods: ['GET'])]
    public function show(ChatRoom $chatRoom): Response
    {
        return $this->render('chat_room/show.html.twig', [
            'chat_room' => $chatRoom,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_chat_room_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ChatRoom $chatRoom, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ChatRoomType::class, $chatRoom);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_chat_room_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('chat_room/edit.html.twig', [
            'chat_room' => $chatRoom,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_chat_room_delete', methods: ['POST'])]
    public function delete(Request $request, ChatRoom $chatRoom, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$chatRoom->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($chatRoom);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_chat_room_index', [], Response::HTTP_SEE_OTHER);
    }
}
