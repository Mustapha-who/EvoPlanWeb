<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\UserModule\Client;
use App\Enum\StatutReservation;
use App\Entity\Event;
use App\Form\ReservationType;
use App\Repository\EventRepository;
use App\Repository\ReservationRepository;
use App\Service\EventEmailService;
use App\Service\FlouciPayment;
use App\Service\TicketGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
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
        UrlGeneratorInterface $urlGenerator,
        EventEmailService $emailService, // ✅ Service d’email
        TicketGeneratorService $ticketGenerator, // ✅ Service de génération de ticket
        MailerInterface $mailer // ✅ Mailer Symfony
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
                // Événement payant => rediriger vers Flouci
                $successLink = $urlGenerator->generate('app_reservation_success', [], UrlGeneratorInterface::ABSOLUTE_URL);
                $failLink = $urlGenerator->generate('app_reservation_fail', [], UrlGeneratorInterface::ABSOLUTE_URL);
                $trackingId = 'reservation_' . $event->getId_event() . '_' . uniqid();

                try {
                    $payment = $flouciPayment->generatePayment(
                        $eventPrice * 1000,
                        $successLink,
                        $failLink,
                        $trackingId
                    );

                    // Stocker les infos pour traitement post-paiement
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

            // Événement gratuit => Réservation directe
            $entityManager->persist($reservation);
            $entityManager->flush();

            // ✅ Génération du ticket
            if ($user instanceof Client && $user->getEmail()) {
                $clientInfo = sprintf("Nom: %s\nEmail: %s", $user->getName(), $user->getEmail());

                $ticketPath = $ticketGenerator->generateTicket(
                    $event->getNom(),
                    $event->getDateDebut()->format('Y-m-d'),
                    $event->getPrix() . ' TND',
                    $event->getLieu()->value,
                    $event->getImageEvent(), // ❗Assurez-vous que cette méthode existe dans Event
                    $clientInfo
                );

                // ✅ Envoi de l'e-mail avec le ticket en pièce jointe
                $email = (new Email())
                    ->from('yacineamrouche2512@gmail.com')
                    ->to($user->getEmail())
                    ->subject('Confirmation de votre réservation')
                    ->text('Merci pour votre réservation. Veuillez trouver votre ticket en pièce jointe.')
                    ->attachFromPath($ticketPath, 'ticket.png', 'image/png');

                $mailer->send($email);
            }

            // ✅ Message de succès
            $this->addFlash('success', 'Votre réservation a été confirmée avec succès ! Un ticket vous a été envoyé par email.');

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
    #[Route('/reservation/success', name: 'app_reservation_success')]
    public function success(Request $request, EntityManagerInterface $em, EventRepository $eventRepo, Security $security, EventEmailService $emailService,MailerInterface $mailer,TicketGeneratorService $ticketGenerator): Response
    {
        $session = $request->getSession();
        $data = $session->get('reservation_data');

        if (!$data) {
            $this->addFlash('danger', 'Aucune réservation en cours.');
            return $this->redirectToRoute('app_event_list');
        }

        $event = $eventRepo->find($data['id_event']);
        $client = $security->getUser();

        if (!$event || !$client) {
            $this->addFlash('danger', 'Erreur lors de la récupération de la réservation.');
            return $this->redirectToRoute('app_event_list');
        }

        $reservation = new Reservation();
        $reservation->setEvent($event);
        $reservation->setClient($client);
        $reservation->setStatut(StatutReservation::CONFIRMEE);
        $user = $security->getUser();

        $em->persist($reservation);
        $em->flush();

        // ✅ Envoi de l'email
        if ($user instanceof Client && $user->getEmail()) {
            $clientInfo = sprintf("Nom: %s\nEmail: %s", $user->getName(), $user->getEmail());

            $ticketPath = $ticketGenerator->generateTicket(
                $event->getNom(),
                $event->getDateDebut()->format('Y-m-d'),
                $event->getPrix() . ' TND',
                $event->getLieu()->value,
                $event->getImageEvent(), // ❗Assurez-vous que cette méthode existe dans Event
                $clientInfo
            );

            // ✅ Envoi de l'e-mail avec le ticket en pièce jointe
            $email = (new Email())
                ->from('yacineamrouche2512@gmail.com')
                ->to($user->getEmail())
                ->subject('Confirmation de votre réservation')
                ->text('Merci pour votre réservation. Veuillez trouver votre ticket en pièce jointe.')
                ->attachFromPath($ticketPath, 'ticket.png', 'image/png');

            $mailer->send($email);
        }

        $this->addFlash('success', 'Réservation confirmée après paiement. Un email vous a été envoyé.');
        $session->remove('reservation_data');

        return $this->redirectToRoute('app_event_show', [
            'id_event' => $event->getId_event(),
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
    #[Route('/{id_reservation}/delete', name: 'app_reservation_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Reservation $reservation,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer
    ): Response {
        // Vérification du token CSRF pour la sécurité
        if ($this->isCsrfTokenValid('delete'.$reservation->getIdReservation(), $request->request->get('_token'))) {
            // Récupération de l'événement avant suppression pour la redirection
            $event = $reservation->getEvent();

            // Envoi d'un email de confirmation d'annulation si nécessaire
            $client = $reservation->getClient();
            if ($client && $client->getEmail()) {
                $email = (new Email())
                    ->from('noreply@evoplan.com')
                    ->to($client->getEmail())
                    ->subject('Confirmation d\'annulation de réservation')
                    ->text(sprintf(
                        "Votre réservation pour l'événement %s a été annulée.\n\n" .
                        "Détails de l'événement :\n" .
                        "Date : %s\n" .
                        "Lieu : %s\n\n" .
                        "Si vous n'êtes pas à l'origine de cette annulation, veuillez nous contacter.",
                        $event->getNom(),
                        $event->getDateDebut()->format('d/m/Y H:i'),
                        $event->getLieu()->value
                    ));

                $mailer->send($email);
            }

            // Suppression effective de la réservation
            $entityManager->remove($reservation);
            $entityManager->flush();

            $this->addFlash('success', 'La réservation a été supprimée avec succès.');
        } else {
            $this->addFlash('danger', 'Jeton CSRF invalide, impossible de supprimer la réservation.');
        }

        // Redirection vers la liste des réservations de l'événement
        return $this->redirectToRoute('app_reservations_event', [
            'id_event' => $event->getId_event()
        ], Response::HTTP_SEE_OTHER);
    }
}