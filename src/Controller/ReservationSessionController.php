<?php

namespace App\Controller;

use App\Entity\ReservationSession;
use App\Entity\Session;
use App\Entity\UserModule\Client;
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
        if ($this->isCsrfTokenValid('delete'.$reservationSession->getId(), $request->request->get('_token'))) {
            try {
                // Get the session before removing the reservation
                $session = $reservationSession->getIdSession();
                
                // Decrease participant count
                if ($session) {
                    $currentCount = $session->getParticipantCount();
                    if ($currentCount > 0) {
                        $session->setParticipantCount($currentCount - 1);
                        $entityManager->persist($session);
                    }
                }
                
                $sessionId = $request->request->get('session_id');
                $workshopId = $request->request->get('workshop_id');
                $modalId = $request->request->get('modal');
                
                // Remove the reservation
                $entityManager->remove($reservationSession);
                $entityManager->flush();
                
                $this->addFlash('success', 'Participant removed successfully.');
                
                if ($sessionId && $workshopId) {
                    return $this->redirectToRoute('app_session_index', [
                        'workshop_id' => $workshopId,
                        'openModal' => $modalId
                    ]);
                }
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Error removing participant.');
            }
        }

        return $this->redirectToRoute('app_session_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/enroll/{id_session}', name: 'app_reservation_session_enroll', methods: ['GET'])]
    public function enroll(Session $session, EntityManagerInterface $entityManager): Response
    {
        // Get the current user
        $user = $this->getUser();
        
        // Check if user is logged in and is a client
        if (!$user instanceof Client) {
            $this->addFlash('danger', 'You must be logged in as a client to enroll.');
            return $this->redirectToRoute('app_login');
        }

        // Check if user is not already enrolled
        $existingReservation = $entityManager->getRepository(ReservationSession::class)
            ->findOneBy([
                'id_session' => $session,
                'participant' => $user
            ]);

        if ($existingReservation) {
            $this->addFlash('danger', 'You are already enrolled in this session.');
            return $this->redirectToRoute('app_workshop_front', [
                'id_event' => $session->getIdWorkshop()->getIdEvent()->getId_event()
            ]);
        }

        // Check if session is not full
        if ($session->getParticipantCount() >= $session->getCapacity()) {
            $this->addFlash('danger', 'This session is already full.');
            return $this->redirectToRoute('app_workshop_front', [
                'id_event' => $session->getIdWorkshop()->getIdEvent()->getId_event()
            ]);
        }

        try {
            // Create new reservation
            $reservation = new ReservationSession();
            $reservation->setIdSession($session);
            $reservation->setParticipant($user);
            
            // Increment participant count
            $session->incrementParticipantCount();
            
            $entityManager->persist($reservation);
            $entityManager->flush();
            
            $this->addFlash('success', 'Successfully enrolled in the session!');
        } catch (\Exception $e) {
            $this->addFlash('danger', 'An error occurred during enrollment.');
        }

        return $this->redirectToRoute('app_workshop_front', [
            'id_event' => $session->getIdWorkshop()->getIdEvent()->getId_event()
        ]);
    }
}
