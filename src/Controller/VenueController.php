<?php

namespace App\Controller;

use App\Entity\Venue;
use App\Form\VenueType;
use App\Repository\VenueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class VenueController extends AbstractController
{
    #[Route('/venue', name: 'app_venue_index', methods: ['GET'])]
    public function index(VenueRepository $venueRepository): Response
    {
        $venues = $venueRepository->findAll();

        return $this->render('venue/index.html.twig', [
            'venues' => $venues,
        ]);
    }

    #[Route('/venue/new', name: 'app_venue_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $venue = new Venue();
        $form = $this->createForm(VenueType::class, $venue);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($venue);
            $entityManager->flush();

            return $this->redirectToRoute('app_venue_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('venue/new.html.twig', [
            'venue' => $venue,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/venue/{id}', name: 'app_venue_show', methods: ['GET'])]
    public function show(Venue $venue): Response
    {
        return $this->render('venue/show.html.twig', [
            'venue' => $venue,
        ]);
    }

    #[Route('/venue/{id}/edit', name: 'app_venue_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Venue $venue, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(VenueType::class, $venue);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Venue modifiée avec succès.');

            return $this->redirectToRoute('app_venue_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('venue/edit.html.twig', [
            'venue' => $venue,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/venue/{id}/delete', name: 'app_venue_delete', methods: ['POST'])]
    public function delete(Request $request, Venue $venue, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$venue->getId(), $request->request->get('_token'))) {
            $entityManager->remove($venue);
            $entityManager->flush();
            $this->addFlash('success', 'Venue supprimée avec succès.');
        } else {
            $this->addFlash('error', 'Erreur lors de la suppression de la venue.');
        }

        return $this->redirectToRoute('app_venue_index', [], Response::HTTP_SEE_OTHER);
    }
}