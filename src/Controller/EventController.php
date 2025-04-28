<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Visite;
use App\Form\EventType;
use App\Repository\EventRepository;
use App\Repository\ReservationRepository;
use App\Repository\VisiteRepository;
use App\Repository\WorkshopRepository;
use App\Service\AIContentGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Form\FormError;
use App\Enum\StatutEvent;
use App\Enum\Lieu;


#[Route('/event')]
final class EventController extends AbstractController
{
    #[Route(name: 'app_event_index', methods: ['GET'])]
    public function index(EventRepository $eventRepository): Response
    {
        return $this->render('event/index.html.twig', [
            'events' => $eventRepository->findAll(),
        ]);
    }

    #[Route('/home', name: 'app_event_home', methods: ['GET'])]
    public function home(Request $request, EventRepository $eventRepository): Response
    {
        // Récupérer les paramètres de recherche
        $lieu = $request->query->get('lieu');
        $date = $request->query->get('date');

        // Créer la requête de base
        $query = $eventRepository->createQueryBuilder('e')
            ->where('e.statut = :statut')
            ->setParameter('statut', StatutEvent::DISPONIBLE);

        // Appliquer le filtre par lieu si spécifié
        if ($lieu) {
            $query->andWhere('e.lieu = :lieu')
                ->setParameter('lieu', Lieu::from($lieu));
        }

        // Appliquer le filtre par date si spécifié
        if ($date) {
            $now = new \DateTime();
            switch ($date) {
                case 'today':
                    $query->andWhere('e.dateDebut BETWEEN :start AND :end')
                        ->setParameter('start', $now->format('Y-m-d 00:00:00'))
                        ->setParameter('end', $now->format('Y-m-d 23:59:59'));
                    break;
                case 'tomorrow':
                    $tomorrow = (clone $now)->modify('+1 day');
                    $query->andWhere('e.dateDebut BETWEEN :start AND :end')
                        ->setParameter('start', $tomorrow->format('Y-m-d 00:00:00'))
                        ->setParameter('end', $tomorrow->format('Y-m-d 23:59:59'));
                    break;
                case 'week':
                    $endOfWeek = (clone $now)->modify('next sunday');
                    $query->andWhere('e.dateDebut BETWEEN :start AND :end')
                        ->setParameter('start', $now->format('Y-m-d 00:00:00'))
                        ->setParameter('end', $endOfWeek->format('Y-m-d 23:59:59'));
                    break;
                case 'weekend':
                    $saturday = (clone $now)->modify('next saturday');
                    $sunday = (clone $saturday)->modify('+1 day');
                    $query->andWhere('e.dateDebut BETWEEN :start AND :end')
                        ->setParameter('start', $saturday->format('Y-m-d 00:00:00'))
                        ->setParameter('end', $sunday->format('Y-m-d 23:59:59'));
                    break;
                case 'month':
                    $endOfMonth = (clone $now)->modify('last day of this month');
                    $query->andWhere('e.dateDebut BETWEEN :start AND :end')
                        ->setParameter('start', $now->format('Y-m-d 00:00:00'))
                        ->setParameter('end', $endOfMonth->format('Y-m-d 23:59:59'));
                    break;
            }
        }

        $events = $query->getQuery()->getResult();

        return $this->render('event/acceuil.html.twig', [
            'events' => $events,
            'lieux' => Lieu::cases() // Envoie tous les cas de l'enum au template
        ]);
    }


    #[Route('/new', name: 'app_event_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        AIContentGenerator $aiGenerator
    ): Response {
        $event = new Event();
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        // Gestion de la requête AJAX pour générer la description
        if ($request->isXmlHttpRequest() && $request->isMethod('POST')) {
            $data = json_decode($request->getContent(), true);

            if (isset($data['generateDescription'])) {
                try {
                    // Créer un événement temporaire avec les données reçues
                    $tempEvent = new Event();
                    $tempEvent->setNom($data['nom'] ?? 'Nouvel Événement');

                    if (!empty($data['dateDebut'])) {
                        $tempEvent->setDateDebut(new \DateTime($data['dateDebut']));
                    }

                    if (!empty($data['lieu'])) {
                        $tempEvent->setLieu(Lieu::tryFrom($data['lieu']));
                    }

                    $tempEvent->setPrix($data['prix'] ?? 0);
                    $tempEvent->setCapacite($data['capacite'] ?? 10);

                    if (!empty($data['statut'])) {
                        $tempEvent->setStatut(StatutEvent::tryFrom($data['statut']));
                    }

                    $description = $aiGenerator->generateEventDescription($tempEvent);
                    return new JsonResponse(['description' => $description]);

                } catch (\Exception $e) {
                    return new JsonResponse(
                        ['error' => 'Erreur lors de la génération: ' . $e->getMessage()],
                        500
                    );
                }
            }
        }

        // Traitement du formulaire normal
        if ($form->isSubmitted() && $form->isValid()) {
            // Gérer l'upload de l'image
            $imageFile = $form->get('imageEvent')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('event_images_directory'),
                        $newFilename
                    );
                    $event->setImageEvent($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image');
                    return $this->redirectToRoute('app_event_new');
                }
            }

            try {
                $entityManager->persist($event);
                $entityManager->flush();
                $this->addFlash('success', 'Événement créé avec succès!');
                return $this->redirectToRoute('app_event_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de l\'enregistrement: '.$e->getMessage());
            }
        }

        return $this->render('event/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id_event}', name: 'app_event_show', methods: ['GET'])]
    public function show(
        int $id_event,
        EventRepository $eventRepository,
        EntityManagerInterface $entityManager,
        Security $security
    ): Response
    {
        $event = $eventRepository->find($id_event);
        if (!$event) {
            throw $this->createNotFoundException('Événement non trouvé');
        }

        // Enregistrer une visite
        $visite = new Visite();
        $visite->setEvent($event);

        // Si l'utilisateur est connecté et est un client
        if ($security->getUser() !== null && method_exists($security->getUser(), 'getClient')) {
            $visite->setClient($security->getUser());
        }

        $entityManager->persist($visite);
        $entityManager->flush();

        return $this->render('event/show.html.twig', [
            'event' => $event,
        ]);
    }

    #[Route('/{id_event}/edit', name: 'app_event_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Event $event, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // Validation manuelle des dates
            $now = new \DateTime();
            $startDate = $event->getDateDebut();
            $endDate = $event->getDateFin();

            $isValid = true;

            if (!$startDate || $startDate < $now) {
                $form->get('dateDebut')->addError(new FormError('La date de début doit être supérieure à maintenant'));
                $isValid = false;
            }

            if (!$endDate || ($startDate && $endDate <= $startDate)) {
                $form->get('dateFin')->addError(new FormError('La date de fin doit être supérieure à la date de début'));
                $isValid = false;
            }

            // Vérification supplémentaire que tous les champs requis sont remplis
            if ($isValid) {
                $requiredFields = ['nom', 'description', 'lieu', 'prix', 'capacite', 'statut'];
                foreach ($requiredFields as $field) {
                    if (empty($form->get($field)->getData())) {
                        $form->get($field)->addError(new FormError('Ce champ est obligatoire'));
                        $isValid = false;
                    }
                }
            }

            if ($isValid && $form->isValid()) {
                // Gérer l'upload de l'image seulement si un nouveau fichier est fourni
                $imageFile = $form->get('imageEvent')->getData();
                if ($imageFile) {
                    $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                    try {
                        $imageFile->move(
                            $this->getParameter('event_images_directory'),
                            $newFilename
                        );
                        $event->setImageEvent($newFilename);
                    } catch (FileException $e) {
                        $this->addFlash('error', 'Erreur lors de l\'upload de l\'image');
                    }
                }
                // Si aucun fichier n'est uploadé, on conserve l'ancienne image

                $entityManager->flush();
                $this->addFlash('success', 'Événement mis à jour avec succès!');
                return $this->redirectToRoute('app_event_index');
            }
        }

        return $this->render('event/edit.html.twig', [
            'form' => $form->createView(),
            'event' => $event,
        ]);
    }

    #[Route('/{id_event}', name: 'app_event_delete', methods: ['POST'])]
    public function delete(Request $request, Event $event, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$event->getId_event(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($event);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_event_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/stats', name: 'app_event_stats')]
    public function stats(
        int $id,
        EventRepository $eventRepository,
        ReservationRepository $reservationRepository,
        VisiteRepository $visiteRepository
    ): Response {
        $event = $eventRepository->find($id);

        if (!$event) {
            throw $this->createNotFoundException('Événement non trouvé.');
        }

        $visites = $visiteRepository->findBy(['event' => $event]);
        $totalVisites = count($visites);

        $clientsUniques = [];
        $visitesParJour = [];

        foreach ($visites as $visite) {
            $client = $visite->getClient();
            if ($client) {
                $clientsUniques[$client->getId()] = true;
            }

            // Organiser les visites par jour
            $date = $visite->getDateVisite()->format('Y-m-d');
            if (!isset($visitesParJour[$date])) {
                $visitesParJour[$date] = 0;
            }
            $visitesParJour[$date]++;
        }

        ksort($visitesParJour); // Trier les jours dans l'ordre

        // Formatage des dates pour Twig
        $joursFormates = [];
        foreach (array_keys($visitesParJour) as $date) {
            $joursFormates[] = (new \DateTime($date))->format('d/m/Y');
        }

        $visiteursUniques = count($clientsUniques);

        $reservations = $reservationRepository->findBy(['id_event' => $event]);
        $nombreReservations = count($reservations);

        $tauxConversion = $totalVisites > 0 ? round(($nombreReservations / $totalVisites) * 100, 2) : 0;

        return $this->render('event/stats.html.twig', [
            'event' => $event,
            'totalVisites' => $totalVisites,
            'visiteursUniques' => $visiteursUniques,
            'nombreReservations' => $nombreReservations,
            'tauxConversion' => $tauxConversion,
            'visitesParJour' => $visitesParJour,
            'jours' => $joursFormates, // <-- Ajout des jours formatés
        ]);
    }

}
