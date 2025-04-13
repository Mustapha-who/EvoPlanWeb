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
use Symfony\Bundle\SecurityBundle\Security;

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
        EventRepository $eventRepository,
        Security $security
    ): Response {
        $event = $eventRepository->find($id_event);
        if (!$event) {
            throw $this->createNotFoundException('Événement non trouvé');
        }

        // Vérification des places disponibles (modifié pour utiliser id_event)
        $reservationsCount = $entityManager->getRepository(Reservation::class)
            ->count(['id_event' => $event]); // Changé 'event' en 'id_event'

        if ($event->getCapacite() <= $reservationsCount) {
            $this->addFlash('danger', 'Désolé, plus de places disponibles pour cet événement.');
            return $this->redirectToRoute('app_event_show', ['id_event' => $event->getId_event()]);
        }

        $reservation = new Reservation();
        // Modification pour utiliser setId_event() au lieu de setEvent()
        $reservation->setEvent($event); // Changé setEvent() en setId_event()
        $reservation->setStatut(StatutReservation::CONFIRMEE);

        // Si l'utilisateur est connecté et est un Client
        $user = $security->getUser();
        if ($user instanceof Client) {
            // Modification pour utiliser setId_client() au lieu de setClient()
            $reservation->setClient($user); // Changé setClient() en setId_client()
        }

        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($reservation);
            $entityManager->flush();

            $this->addFlash('reservation_success', 'Votre réservation a été confirmée avec succès!');
            return $this->redirectToRoute('app_event_show', [
                'id_event' => $event->getId_event()
            ]);
        }

        return $this->render('reservation/new.html.twig', [
            'event' => $event,
            'form' => $form->createView(),
            'remaining_seats' => $event->getCapacite() - $reservationsCount,
            'current_user' => $user
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
