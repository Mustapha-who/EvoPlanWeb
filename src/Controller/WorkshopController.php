<?php

namespace App\Controller;

use App\Entity\Workshop;
use App\Form\WorkshopType;
use App\Repository\WorkshopRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/workshop')]
final class WorkshopController extends AbstractController
{
    #[Route(name: 'app_workshop_index', methods: ['GET'])]
    public function index(WorkshopRepository $workshopRepository): Response
    {
        return $this->render('workshop/index.html.twig', [
            'workshops' => $workshopRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_workshop_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $workshop = new Workshop();
        $form = $this->createForm(WorkshopType::class, $workshop);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($workshop);
                $entityManager->flush();
                
                $this->addFlash('success', sprintf(
                    'Workshop "%s" has been created successfully!',
                    $workshop->getTitle()
                ));
            } catch (\Exception $e) {
                $this->addFlash('danger', 'An error occurred while creating the workshop.');
            }

            return $this->redirectToRoute('app_workshop_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('workshop/new.html.twig', [
            'workshop' => $workshop,
            'form' => $form,
        ]);
    }

    #[Route('/{id_workshop}/edit', name: 'app_workshop_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Workshop $workshop, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(WorkshopType::class, $workshop);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->flush();
                $this->addFlash('success', sprintf(
                    'Workshop "%s" has been updated successfully!',
                    $workshop->getTitle()
                ));
            } catch (\Exception $e) {
                $this->addFlash('danger', 'An error occurred while updating the workshop.');
            }

            return $this->redirectToRoute('app_workshop_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('workshop/edit.html.twig', [
            'workshop' => $workshop,
            'form' => $form,
        ]);
    }

    #[Route('/{id_workshop}', name: 'app_workshop_delete', methods: ['POST'])]
    public function delete(Request $request, Workshop $workshop, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$workshop->getid_workshop(), $request->getPayload()->getString('_token'))) {
            try {
                $workshopTitle = $workshop->getTitle(); // Save title before deletion
                $entityManager->remove($workshop);
                $entityManager->flush();
                
                $this->addFlash('success', sprintf(
                    'Workshop "%s" has been deleted successfully!',
                    $workshopTitle
                ));
            } catch (\Exception $e) {
                $this->addFlash('danger', 'An error occurred while deleting the workshop.');
            }
        }

        return $this->redirectToRoute('app_workshop_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/charts', name: 'app_workshop_charts', methods: ['GET'])]
    public function charts(WorkshopRepository $workshopRepository): Response
    {
        return $this->render('workshop/charts.html.twig', [
            'sessionsData' => $workshopRepository->getSessionsPerWorkshop(),
            'workshopsPerEvent' => $workshopRepository->getWorkshopsPerEvent(),
            'capacityData' => $workshopRepository->getCapacityVsAttendance(),
            'attendanceRates' => $workshopRepository->getAttendanceRates()
        ]);
    }

    #[Route('/front/{id_event}', name: 'app_workshop_front', methods: ['GET'])]
    public function front(int $id_event, WorkshopRepository $workshopRepository): Response
    {
        // Get current user
        $user = $this->getUser();
        $userId = $user ? $user->getId() : null;

        return $this->render('workshop/front.html.twig', [
            'workshops' => $workshopRepository->findBy(['id_event' => $id_event]),
            'event_id' => $id_event,
            'user_id' => $userId
        ]);
    }
}
