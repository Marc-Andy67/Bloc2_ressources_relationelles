<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Entity\TicketReply;
use App\Entity\User;
use App\Form\TicketReplyType;
use App\Form\TicketType;
use App\Repository\TicketRepository;
use App\Security\Voter\TicketVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/tickets')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class TicketController extends AbstractController
{
    private function getAuthenticatedUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Utilisateur non authentifié.');
        }

        return $user;
    }

    #[Route('', name: 'app_ticket_index', methods: ['GET'])]
    public function index(TicketRepository $ticketRepository): Response
    {
        $tickets = $this->isGranted('ROLE_ADMIN')
            ? $ticketRepository->findAllOrdered()
            : $ticketRepository->findForUser($this->getAuthenticatedUser());

        return $this->render('ticket/index.html.twig', [
            'tickets' => $tickets,
        ]);
    }

    #[Route('/nouveau', name: 'app_ticket_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $ticket = new Ticket();
        $ticket->setUser($this->getAuthenticatedUser());

        $form = $this->createForm(TicketType::class, $ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($ticket);
            $em->flush();

            $this->addFlash('success', 'Votre ticket a bien été envoyé.');

            return $this->redirectToRoute('app_ticket_show', ['id' => $ticket->getId()]);
        }

        return $this->render('ticket/new.html.twig', [
            'form' => $form,
        ], new Response(null, $form->isSubmitted() ? 422 : 200));
    }

    #[Route('/{id}', name: 'app_ticket_show', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function show(Ticket $ticket, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted(TicketVoter::VIEW, $ticket);

        $isAdmin = $this->isGranted('ROLE_ADMIN');

        $reply = new TicketReply();
        $form = $this->createForm(TicketReplyType::class, $reply);
        $form->handleRequest($request);

        if (!$ticket->isEstCloture() && $form->isSubmitted() && $form->isValid()) {
            $reply->setAuthor($this->getAuthenticatedUser());
            $reply->setIsFromAdmin($isAdmin);
            $ticket->addReply($reply);
            $em->flush();

            $this->addFlash('success', 'Votre réponse a été ajoutée.');

            return $this->redirectToRoute('app_ticket_show', ['id' => $ticket->getId()]);
        }

        return $this->render('ticket/show.html.twig', [
            'ticket' => $ticket,
            'form' => $form,
            'is_admin' => $isAdmin,
        ], new Response(null, $form->isSubmitted() ? 422 : 200));
    }

    #[Route('/{id}/cloturer', name: 'app_ticket_close', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function close(Ticket $ticket, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('close_ticket_' . $ticket->getId(), $request->getPayload()->getString('_token'))) {
            $ticket->setEstCloture(true);
            $em->flush();
            $this->addFlash('success', $ticket->getTitle() . ' a été clôturé.');
        }

        return $this->redirectToRoute('app_ticket_show', ['id' => $ticket->getId()]);
    }
}