<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\UserModule\Client;
use App\Enum\StatutReservation;
use App\Entity\Event;
use App\Form\ReservationType;
use App\Repository\EventRepository;
use App\Repository\ReservationRepository;
use App\Service\FlouciPayment;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

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
        Security $security,
        FlouciPayment $flouciPayment,
        UrlGeneratorInterface $urlGenerator
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

        $user = $security->getUser();
        if ($user instanceof Client) {
            $reservation->setClient($user);
        }

        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $eventPrice = (float) $event->getPrix();

            if ($eventPrice > 0) {
                // Paiement requis => Stocker infos en session + rediriger vers Flouci
                $successLink = $urlGenerator->generate('app_reservation_success', [], UrlGeneratorInterface::ABSOLUTE_URL);
                $failLink = $urlGenerator->generate('app_reservation_fail', [], UrlGeneratorInterface::ABSOLUTE_URL);
                $trackingId = 'reservation_' . $event->getId_event() . '_' . uniqid();

                try {
                    $payment = $flouciPayment->generatePayment(
                        $eventPrice * 1000, // en millimes
                        $successLink,
                        $failLink,
                        $trackingId
                    );

                    // Stocker les infos de réservation temporairement
                    $request->getSession()->set('reservation_data', [
                        'id_event' => $event->getId_event(),
                        'client_id' => $user?->getId(),
                    ]);

                    return $this->redirect($payment['result']['link']);
                } catch (\Exception $e) {
                    $this->addFlash('danger', 'Erreur lors de la génération du paiement : ' . $e->getMessage());
                    return $this->redirectToRoute('app_event_show', ['id_event' => $event->getId_event()]);
                }
            }

            // Événement gratuit => réservation directe
            $entityManager->persist($reservation);
            $entityManager->flush();

            $this->addFlash('success', 'Votre réservation a été confirmée avec succès !');
            return $this->redirectToRoute('app_event_show', [
                'id_event' => $event->getId_event()
            ]);
        }

        return $this->render('reservation/new.html.twig', [
            'event' => $event,
            'form' => $form->createView(),
            'remaining_seats' => $event->getCapacite() - $reservationsCount,
            'current_user' => $user,
            'is_paid' => $event->getPrix() > 0
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
    #[Route('/reservation/success', name: 'app_reservation_success')]
    public function paymentSuccess(
        Request $request,
        EntityManagerInterface $entityManager,
        EventRepository $eventRepository,
        Security $security
    ): Response {
        $session = $request->getSession();
        $reservationData = $session->get('reservation_data');

        if (!$reservationData) {
            $this->addFlash('danger', 'Aucune donnée de réservation trouvée.');
            return $this->redirectToRoute('app_event_index');
        }

        $event = $eventRepository->find($reservationData['id_event']);
        $client = $security->getUser();

        if (!$event || !$client instanceof Client) {
            $this->addFlash('danger', 'Informations invalides pour finaliser la réservation.');
            return $this->redirectToRoute('app_event_show', ['id_event' => $reservationData['id_event']]);
        }

        $reservation = new Reservation();
        $reservation->setEvent($event);
        $reservation->setClient($client);
        $reservation->setStatut(StatutReservation::CONFIRMEE);

        $entityManager->persist($reservation);
        $entityManager->flush();

        // Nettoyer la session
        $session->remove('reservation_data');

        // ✅ Redirection vers la page de l'événement avec un message de succès
        $this->addFlash('success', 'Votre paiement a été validé et votre réservation est confirmée !');
        return $this->redirectToRoute('app_event_show', [
            'id_event' => $event->getId_event()
        ]);
    }


    #[Route('/reservation/fail', name: 'app_reservation_fail')]
    public function paymentFail(Request $request): Response
    {
        $reservationData = $request->getSession()->get('reservation_data');
        $request->getSession()->remove('reservation_data');

        $eventId = $reservationData['id_event'] ?? null;

        if (!$eventId) {
            $this->addFlash('danger', 'Échec du paiement. Aucun événement trouvé.');
            return $this->redirectToRoute('app_event_index');
        }

        // ❌ Redirection avec message d'échec
        $this->addFlash('danger', 'Le paiement a échoué. Veuillez réessayer.');
        return $this->redirectToRoute('app_event_show', ['id_event' => $eventId]);
    }

}