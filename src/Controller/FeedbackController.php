<?php

namespace App\Controller;

use App\Entity\Feedback;
use App\Form\FeedbackType;
use App\Form\FeedbackFilterType;
use App\Repository\FeedbackRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use Dompdf\Options;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/feedback')]
final class FeedbackController extends AbstractController
{
    #[Route('/', name: 'app_feedback_index', methods: ['GET'])]
    public function index(Request $request, FeedbackRepository $feedbackRepository, PaginatorInterface $paginator): Response
    {
        $filterForm = $this->createForm(FeedbackFilterType::class);
        $filterForm->handleRequest($request);

        $filters = [];
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            $filters = $filterForm->getData();
        }

        // Create query builder for pagination
        $queryBuilder = $feedbackRepository->createQueryBuilder('f')
            ->leftJoin('f.client', 'c')
            ->orderBy('f.id', 'DESC');

        if (!empty($filters['rating'])) {
            $queryBuilder->andWhere('f.rating = :rating')
                ->setParameter('rating', $filters['rating']);
        }

        if (!empty($filters['keyword'])) {
            $queryBuilder->andWhere('f.comments LIKE :keyword')
                ->setParameter('keyword', '%' . $filters['keyword'] . '%');
        }

        $feedbacks = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            10
        );

        $ratingCounts = $feedbackRepository->getRatingCounts();
        $averageRating = $feedbackRepository->getAverageRating();

        return $this->render('feedback/index.html.twig', [
            'feedbacks' => $feedbacks,
            'rating_counts' => $ratingCounts,
            'average_rating' => $averageRating,
            'filter_form' => $filterForm->createView(),
            'filters' => $filters, // Pass filters for export links
        ]);
    }

    #[Route('/admin/feedback', name: 'app_feedback_admin_index', methods: ['GET'])]
    public function adminIndex(Request $request, FeedbackRepository $feedbackRepository, PaginatorInterface $paginator): Response
    {
        $filterForm = $this->createForm(FeedbackFilterType::class);
        $filterForm->handleRequest($request);

        $filters = [];
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            $filters = $filterForm->getData();
        }

        // Create query builder for pagination
        $queryBuilder = $feedbackRepository->createQueryBuilder('f')
            ->leftJoin('f.client', 'c')
            ->orderBy('f.id', 'DESC');

        if (!empty($filters['rating'])) {
            $queryBuilder->andWhere('f.rating = :rating')
                ->setParameter('rating', $filters['rating']);
        }

        if (!empty($filters['keyword'])) {
            $queryBuilder->andWhere('f.comments LIKE :keyword')
                ->setParameter('keyword', '%' . $filters['keyword'] . '%');
        }

        $feedbacks = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            10
        );

        $ratingCounts = $feedbackRepository->getRatingCounts();
        $averageRating = $feedbackRepository->getAverageRating();

        return $this->render('feedback/indexback.html.twig', [
            'feedbacks' => $feedbacks,
            'rating_counts' => $ratingCounts,
            'average_rating' => $averageRating,
            'filter_form' => $filterForm->createView(),
            'filters' => $filters, // Pass filters for export links
        ]);
    }

    #[Route('/new', name: 'app_feedback_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $feedback = new Feedback();
        $form = $this->createForm(FeedbackType::class, $feedback);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($feedback);
            $entityManager->flush();

            $this->addFlash('success', 'Le feedback a été créé avec succès.');
            return $this->redirectToRoute('app_feedback_index');
        }

        return $this->render('feedback/new.html.twig', [
            'feedback' => $feedback,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_feedback_show', methods: ['GET'])]
    public function show(Feedback $feedback): Response
    {
        return $this->render('feedback/show.html.twig', [
            'feedback' => $feedback,
        ]);
    }

    #[Route('/admin/{id}', name: 'app_feedback_admin_show', methods: ['GET'])]
    public function adminShow(Feedback $feedback): Response
    {
        return $this->render('feedback/show_admin.html.twig', [
            'feedback' => $feedback,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_feedback_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Feedback $feedback, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(FeedbackType::class, $feedback);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Le feedback a été modifié avec succès.');
            return $this->redirectToRoute('app_feedback_index');
        }

        return $this->render('feedback/edit.html.twig', [
            'feedback' => $feedback,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/delete/{id}', name: 'app_feedback_delete', methods: ['POST'])]
    public function delete(Request $request, Feedback $feedback, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$feedback->getId(), $request->request->get('_token'))) {
            $entityManager->remove($feedback);
            $entityManager->flush();
            $this->addFlash('success', 'Le feedback a été supprimé avec succès.');
        } else {
            $this->addFlash('error', 'Erreur lors de la suppression du feedback.');
        }

        return $this->redirectToRoute('app_feedback_admin_index');
    }

    #[Route('/export/excel', name: 'app_feedback_export_excel', methods: ['GET'])]
    public function exportExcel(Request $request, FeedbackRepository $feedbackRepository): Response
    {
        $rating = $request->query->get('rating', null);
        $keyword = $request->query->get('keyword', null);

        $filters = [
            'rating' => $rating !== null && $rating !== '' ? (int) $rating : null,
            'keyword' => $keyword !== null && $keyword !== '' ? $keyword : null,
        ];

        $feedbacks = $feedbackRepository->findByFilters($filters);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'Commentaire');
        $sheet->setCellValue('C1', 'Note');
        $sheet->setCellValue('D1', 'Client (Numéro de téléphone)');

        $row = 2;
        foreach ($feedbacks as $feedback) {
            $sheet->setCellValue('A'.$row, $feedback->getId());
            $sheet->setCellValue('B'.$row, $feedback->getComments());
            $sheet->setCellValue('C'.$row, $feedback->getRating());
            $sheet->setCellValue('D'.$row, $feedback->getClient() ? $feedback->getClient()->getPhoneNumber() : 'N/A');
            $row++;
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'feedbacks_'.date('Y-m-d').'.xlsx';
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);

        $writer->save($temp_file);

        return $this->file($temp_file, $fileName, ResponseHeaderBag::DISPOSITION_ATTACHMENT);
    }

    #[Route('/export/pdf', name: 'app_feedback_export_pdf', methods: ['GET'])]
    public function exportPdf(Request $request, FeedbackRepository $feedbackRepository): Response
    {
        $rating = $request->query->get('rating', null);
        $keyword = $request->query->get('keyword', null);

        $filters = [
            'rating' => $rating !== null && $rating !== '' ? (int) $rating : null,
            'keyword' => $keyword !== null && $keyword !== '' ? $keyword : null,
        ];

        $feedbacks = $feedbackRepository->findByFilters($filters);

        $ratingCounts = $feedbackRepository->getRatingCounts();
        $averageRating = $feedbackRepository->getAverageRating();

        $normalizedFilters = [
            'rating' => $filters['rating'],
            'keyword' => $filters['keyword'],
        ];

        $html = $this->renderView('feedback/export_pdf.html.twig', [
            'feedbacks' => $feedbacks,
            'rating_counts' => $ratingCounts,
            'average_rating' => $averageRating,
            'date' => new \DateTime(),
            'filters' => $normalizedFilters,
        ]);

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Arial');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $fileName = 'feedbacks_'.date('Y-m-d').'.pdf';

        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => ResponseHeaderBag::DISPOSITION_ATTACHMENT.'; filename="'.$fileName.'"',
            ]
        );
    }

    #[Route('/export/csv', name: 'app_feedback_export_csv', methods: ['GET'])]
    public function exportCsv(Request $request, FeedbackRepository $feedbackRepository): Response
    {
        $rating = $request->query->get('rating', null);
        $keyword = $request->query->get('keyword', null);

        $filters = [
            'rating' => $rating !== null && $rating !== '' ? (int) $rating : null,
            'keyword' => $keyword !== null && $keyword !== '' ? $keyword : null,
        ];

        $feedbacks = $feedbackRepository->findByFilters($filters);

        $fileName = 'feedbacks_'.date('Y-m-d').'.csv';

        $response = new StreamedResponse(function() use ($feedbacks) {
            $handle = fopen('php://output', 'w+');

            fputcsv($handle, ['ID', 'Commentaire', 'Note', 'Client (Numéro de téléphone)'], ';');

            foreach ($feedbacks as $feedback) {
                fputcsv($handle, [
                    $feedback->getId(),
                    $feedback->getComments(),
                    $feedback->getRating(),
                    $feedback->getClient() ? $feedback->getClient()->getPhoneNumber() : 'N/A',
                ], ';');
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$fileName.'"');

        return $response;
    }
}