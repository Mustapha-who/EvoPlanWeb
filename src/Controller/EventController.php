<?php

namespace App\Controller;

use App\Entity\Event;
use App\Form\EventType;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $event = new Event();
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

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
                $this->addFlash('event_success', 'Événement créé avec succès!');
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
    public function show(int $id_event, EventRepository $eventRepository): Response
    {
        $event = $eventRepository->find($id_event);
        if (!$event) {
            throw $this->createNotFoundException('Événement non trouvé');
        }

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


}
