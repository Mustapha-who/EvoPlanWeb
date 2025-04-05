<?php

namespace App\Controller;

use App\Entity\ReservationSession;
use App\Form\ReservationSessionType;
use App\Repository\ReservationSessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/reservation/session')]
final class ReservationSessionController extends AbstractController
{
    #[Route(name: 'app_reservation_session_index', methods: ['GET'])]
    public function index(ReservationSessionRepository $reservationSessionRepository): Response
    {
        return $this->render('reservation_session/index.html.twig', [
            'reservation_sessions' => $reservationSessionRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_reservation_session_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $reservationSession = new ReservationSession();
        $form = $this->createForm(ReservationSessionType::class, $reservationSession);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($reservationSession);
            $entityManager->flush();

            return $this->redirectToRoute('app_reservation_session_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reservation_session/new.html.twig', [
            'reservation_session' => $reservationSession,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_reservation_session_show', methods: ['GET'])]
    public function show(ReservationSession $reservationSession): Response
    {
        return $this->render('reservation_session/show.html.twig', [
            'reservation_session' => $reservationSession,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_reservation_session_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ReservationSession $reservationSession, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ReservationSessionType::class, $reservationSession);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_reservation_session_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reservation_session/edit.html.twig', [
            'reservation_session' => $reservationSession,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_reservation_session_delete', methods: ['POST'])]
    public function delete(Request $request, ReservationSession $reservationSession, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$reservationSession->getId(), $request->getPayload()->getString('_token'))) {
            $sessionId = $request->request->get('session_id');
            $workshopId = $request->request->get('workshop_id');
            
            $entityManager->remove($reservationSession);
            $entityManager->flush();

            if ($sessionId && $workshopId) {
                return $this->redirectToRoute('app_session_index', [
                    'workshop_id' => $workshopId,
                    'openModal' => $sessionId
                ]);
            }
        }

        return $this->redirectToRoute('app_reservation_session_index', [], Response::HTTP_SEE_OTHER);
    }
}
