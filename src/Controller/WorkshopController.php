<?php

namespace App\Controller;

use App\Entity\Workshop;
use App\Entity\Reservation; // Add this use statement at the top
use App\Form\WorkshopType;
use App\Repository\WorkshopRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Service\WorkshopAnalyticsService;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Service\OpenRouterApiService;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/workshop')]
final class WorkshopController extends AbstractController
{
    #[Route('/generate', name: 'app_workshop_generate', methods: ['POST'])]
    public function generateWorkshop(Request $request, OpenRouterApiService $apiService): JsonResponse
    {
        try {
            $content = $request->getContent();
            $data = json_decode($content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \InvalidArgumentException('Invalid JSON payload');
            }

            $prompt = $data['prompt'] ?? '';
            if (empty($prompt)) {
                throw new \InvalidArgumentException('Prompt cannot be empty');
            }

            $workshopData = $apiService->generateWorkshopData($prompt);

            return new JsonResponse([
                'success' => true,
                'title' => $workshopData['title'],
                'description' => $workshopData['description']
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

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

    #[Route('/charts/export', name: 'app_workshop_export', methods: ['GET'])]
    public function exportAnalytics(
        WorkshopRepository $workshopRepository, 
        WorkshopAnalyticsService $analyticsService
    ): StreamedResponse {
        $data = [
            'sessionsData' => $workshopRepository->getSessionsPerWorkshop(),
            'workshopsPerEvent' => $workshopRepository->getWorkshopsPerEvent(),
            'capacityData' => $workshopRepository->getCapacityVsAttendance(),
            'attendanceRates' => $workshopRepository->getAttendanceRates()
        ];

        $analytics = $analyticsService->calculateAnalytics($data);

        $spreadsheet = new Spreadsheet();
        
        // Add data sheets
        $this->addDataSheet($spreadsheet, 'Sessions', $data['sessionsData']);
        $this->addDataSheet($spreadsheet, 'Events', $data['workshopsPerEvent']);
        $this->addDataSheet($spreadsheet, 'Capacity', $data['capacityData']);
        $this->addDataSheet($spreadsheet, 'Attendance', $data['attendanceRates']);
        
        // Add analytics sheet
        $this->addAnalyticsSheet($spreadsheet, $analytics);

        return new StreamedResponse(
            function () use ($spreadsheet) {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
            },
            200,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="workshop-analytics.xlsx"'
            ]
        );
    }

    private function addDataSheet(Spreadsheet $spreadsheet, string $name, array $data): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle($name);
        
        // Add headers
        $headers = array_keys(reset($data));
        $sheet->fromArray([$headers], null, 'A1');
        
        // Add data
        $sheet->fromArray($data, null, 'A2');
    }

    private function addAnalyticsSheet(Spreadsheet $spreadsheet, array $analytics): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Analytics');
        
        $row = 1;
        foreach ($analytics as $category => $data) {
            $sheet->setCellValue('A' . $row, $category);
            $row++;
            
            foreach ($data as $metric => $value) {
                $sheet->setCellValue('B' . $row, $metric);
                $sheet->setCellValue('C' . $row, is_array($value) ? json_encode($value) : $value);
                $row++;
            }
            $row++;
        }
    }

    #[Route('/front/{id_event}', name: 'app_workshop_front', methods: ['GET'])]
    public function front(int $id_event, WorkshopRepository $workshopRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('danger', 'You must be logged in to view workshops.');
            return $this->redirectToRoute('app_event_show', ['id_event' => $id_event]);
        }

        // Fixed: Use full entity class name instead of shorthand notation
        $reservation = $entityManager->getRepository(Reservation::class)
            ->findOneBy([
                'id_event' => $id_event,
                'id_client' => $user->getId()
            ]);

        if (!$reservation) {
            $this->addFlash('danger', 'You must join this event before accessing its workshops. Please register for the event first.');
            return $this->redirectToRoute('app_event_show', ['id_event' => $id_event]);
        }

        return $this->render('workshop/front.html.twig', [
            'workshops' => $workshopRepository->findBy(['id_event' => $id_event]),
            'event_id' => $id_event,
            'user_id' => $user->getId()
        ]);
    }
}
