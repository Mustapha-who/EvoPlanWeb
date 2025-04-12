<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\UserModule\Client;
use App\Enum\StatutReservation;
use App\Entity\Event;
use App\Form\ReservationType;
use App\Repository\EventRepository;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/reservation')]
final class ReservationController extends AbstractController
{
    #[Route('/event/{id_event}/reservations', name: 'app_reservations_event')]
    public function reservationsByEvent(
        EventRepository $eventRepository,
        ReservationRepository $reservationRepository,
        int $id_event
    ): Response {
        $event = $eventRepository->find($id_event);

        if (!$event) {
            throw $this->createNotFoundException('Event not found.');
        }

        $reservations = $reservationRepository->findBy(['id_event' => $event]);

        return $this->render('reservation/index.html.twig', [
            'event' => $event,
            'reservations' => $reservations,
        ]);
    }

    #[Route('/new/event/{id_event}', name: 'app_reservation_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        int $id_event,
        EntityManagerInterface $entityManager,
        EventRepository $eventRepository
    ): Response {
        $event = $eventRepository->find($id_event);
        if (!$event) {
            throw $this->createNotFoundException('Événement non trouvé');
        }

        // Vérification des places disponibles
        $reservationsCount = $entityManager->getRepository(Reservation::class)
            ->count(['id_event' => $event]);

        if ($event->getCapacite() <= $reservationsCount) {
            $this->addFlash('danger', 'Désolé, plus de places disponibles pour cet événement.');
            return $this->redirectToRoute('app_event_show', ['id_event' => $event->getId_event()]);
        }

        $reservation = new Reservation();
        $reservation->setEvent($event);
        $reservation->setStatut(StatutReservation::CONFIRMEE);

        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($reservation);
            $entityManager->flush();

            // Message de succès
            $this->addFlash('success', 'Votre réservation a été confirmée avec succès!');

            // Redirection vers la page de l'événement
            return $this->redirectToRoute('app_event_show', [
                'id_event' => $event->getId_event()
            ]);
        }

        return $this->render('reservation/new.html.twig', [
            'event' => $event,
            'form' => $form->createView(),
            'remaining_seats' => $event->getCapacite() - $reservationsCount
        ]);
    }
    #[Route('/{id_reservation}/edit', name: 'app_reservation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reservation/edit.html.twig', [
            'reservation' => $reservation,
            'form' => $form,
        ]);
    }

    #[Route('/{id_reservation}', name: 'app_reservation_delete', methods: ['POST'])]
    public function delete(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        $eventId = $reservation->getEvent()->getId_event();

        if ($this->isCsrfTokenValid('delete' . $reservation->getIdReservation(), $request->request->get('_token'))) {
            $entityManager->remove($reservation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_reservations_event', [
            'id_event' => $eventId,
        ]);
    }
}
