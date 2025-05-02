<?php

namespace App\Controller;

use App\Entity\Claim;
use App\Form\ClaimType;
use App\Form\ClaimFilterType;
use App\Form\ClaimAdminEditType;
use App\Repository\ClaimRepository;
use App\Service\ProfanityFilter;
use App\Service\TwilioService;
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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/claim')]
final class ClaimController extends AbstractController
{
    #[Route('/', name: 'app_claim_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $entityManager, PaginatorInterface $paginator): Response
    {
        $claims = $entityManager->getRepository(Claim::class)->findBy(['claimStatus' => 'new']);
        $now = new \DateTime();
        foreach ($claims as $claim) {
            $daysSinceCreation = $claim->getCreationDate()->diff($now)->days;
            if ($daysSinceCreation > 7) {
                $claim->setClaimStatus('in_progress');
                $entityManager->persist($claim);
            }
        }
        $entityManager->flush();

        $filterForm = $this->createForm(ClaimFilterType::class);
        $filters = [
            'claimStatus' => $request->query->get('claimStatus'),
            'claimType' => $request->query->get('claimType'),
            'keyword' => $request->query->get('keyword'),
        ];
        $filterForm->submit($filters);

        $queryBuilder = $entityManager->getRepository(Claim::class)->createQueryBuilder('c')
            ->leftJoin('c.client', 'cl')
            ->addSelect('cl');

        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            $data = $filterForm->getData();

            if ($data['claimStatus'] !== null) {
                $queryBuilder->andWhere('c.claimStatus = :status')
                    ->setParameter('status', $data['claimStatus']);
            }

            if ($data['claimType'] !== null) {
                $queryBuilder->andWhere('c.claimType = :type')
                    ->setParameter('type', $data['claimType']);
            }

            if (!empty($data['keyword'])) {
                $queryBuilder->andWhere('c.description LIKE :keyword')
                    ->setParameter('keyword', '%' . $data['keyword'] . '%');
            }
        }

        $pagination = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            10
        );

        $statusCounts = $entityManager->getRepository(Claim::class)->getStatusCounts();

        return $this->render('claim/index.html.twig', [
            'claims' => $pagination,
            'status_counts' => $statusCounts,
            'filter_form' => $filterForm->createView(),
            'filters' => $filters,
        ]);
    }

    #[Route('/admin/claim', name: 'app_claim_admin_index', methods: ['GET'])]
    public function adminIndex(Request $request, EntityManagerInterface $entityManager, PaginatorInterface $paginator): Response
    {
        $claims = $entityManager->getRepository(Claim::class)->findBy(['claimStatus' => 'new']);
        $now = new \DateTime();
        foreach ($claims as $claim) {
            $daysSinceCreation = $claim->getCreationDate()->diff($now)->days;
            if ($daysSinceCreation > 7) {
                $claim->setClaimStatus('in_progress');
                $entityManager->persist($claim);
            }
        }
        $entityManager->flush();

        $filterForm = $this->createForm(ClaimFilterType::class);
        $filters = [
            'claimStatus' => $request->query->get('claimStatus'),
            'claimType' => $request->query->get('claimType'),
            'keyword' => $request->query->get('keyword'),
        ];
        $filterForm->submit($filters);

        $queryBuilder = $entityManager->getRepository(Claim::class)->createQueryBuilder('c')
            ->leftJoin('c.client', 'cl')
            ->addSelect('cl');

        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            $data = $filterForm->getData();

            if ($data['claimStatus'] !== null) {
                $queryBuilder->andWhere('c.claimStatus = :status')
                    ->setParameter('status', $data['claimStatus']);
            }

            if ($data['claimType'] !== null) {
                $queryBuilder->andWhere('c.claimType = :type')
                    ->setParameter('type', $data['claimType']);
            }

            if (!empty($data['keyword'])) {
                $queryBuilder->andWhere('c.description LIKE :keyword')
                    ->setParameter('keyword', '%' . $data['keyword'] . '%');
            }
        }

        $pagination = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            10
        );

        $statusCounts = $entityManager->getRepository(Claim::class)->getStatusCounts();

        return $this->render('claim/indexback.html.twig', [
            'claims' => $pagination,
            'status_counts' => $statusCounts,
            'filter_form' => $filterForm->createView(),
            'filters' => $filters,
        ]);
    }

    #[Route('/admin/claim/calendar', name: 'app_claim_admin_calendar', methods: ['GET'])]
    public function calendar(EntityManagerInterface $entityManager): Response
    {
        $claims = $entityManager->getRepository(Claim::class)->findAll();

        $events = [];
        foreach ($claims as $claim) {
            if ($claim->getCreationDate()) {
                $events[] = [
                    'title' => $claim->getDescription() ? substr($claim->getDescription(), 0, 30) . (strlen($claim->getDescription()) > 30 ? '...' : '') : 'Réclamation',
                    'start' => $claim->getCreationDate()->format('Y-m-d'),
                    'url' => $this->generateUrl('app_claim_admin_show', ['id' => $claim->getId()]),
                    'classNames' => [
                        $claim->getClaimStatus() === 'new' ? 'fc-event-new' :
                            ($claim->getClaimStatus() === 'in_progress' ? 'fc-event-in_progress' : 'fc-event-resolved')
                    ],
                    'extendedProps' => [
                        'type' => $claim->getClaimType(),
                        'status' => $claim->getClaimStatus(),
                    ],
                ];
            }
        }

        return $this->render('claim/calendar.html.twig', [
            'claims' => $claims,
        ]);
    }

    #[Route('/export-excel', name: 'app_claim_export_excel', methods: ['GET'])]
    public function exportExcel(Request $request, ClaimRepository $claimRepository): Response
    {
        $filters = $request->query->all();
        $claims = $claimRepository->findByFilters($filters);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'Description');
        $sheet->setCellValue('C1', 'Type');
        $sheet->setCellValue('D1', 'Date');
        $sheet->setCellValue('E1', 'Statut');
        $sheet->setCellValue('F1', 'Client (Numéro de téléphone)');

        $row = 2;
        foreach ($claims as $claim) {
            $sheet->setCellValue('A'.$row, $claim->getId());
            $sheet->setCellValue('B'.$row, $claim->getDescription());
            $sheet->setCellValue('C'.$row, $claim->getClaimType());
            $sheet->setCellValue('D'.$row, $claim->getCreationDate()->format('d/m/Y'));
            $sheet->setCellValue('E'.$row, $claim->getClaimStatus());
            $sheet->setCellValue('F'.$row, $claim->getClient() ? $claim->getClient()->getPhoneNumber() : 'N/A');
            $row++;
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'reclamations_'.date('Y-m-d').'.xlsx';
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);

        $writer->save($temp_file);

        return $this->file($temp_file, $fileName, ResponseHeaderBag::DISPOSITION_ATTACHMENT);
    }

    #[Route('/export-pdf', name: 'app_claim_export_pdf', methods: ['GET'])]
    public function exportPdf(Request $request, ClaimRepository $claimRepository): Response
    {
        $filters = $request->query->all();
        $claims = $claimRepository->findByFilters($filters);

        $statusCounts = $claimRepository->getStatusCounts();

        $normalizedFilters = [
            'claimStatus' => $filters['claimStatus'] ?? null,
            'claimType' => $filters['claimType'] ?? null,
            'keyword' => $filters['keyword'] ?? null,
        ];

        $html = $this->renderView('claim/export_pdf.html.twig', [
            'claims' => $claims,
            'status_counts' => $statusCounts,
            'date' => new \DateTime(),
            'filters' => $normalizedFilters
        ]);

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Arial');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $fileName = 'reclamations_'.date('Y-m-d').'.pdf';

        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => ResponseHeaderBag::DISPOSITION_ATTACHMENT.'; filename="'.$fileName.'"'
            ]
        );
    }

    #[Route('/export-csv', name: 'app_claim_export_csv', methods: ['GET'])]
    public function exportCsv(Request $request, ClaimRepository $claimRepository): Response
    {
        $filters = $request->query->all();
        $claims = $claimRepository->findByFilters($filters);

        $fileName = 'reclamations_'.date('Y-m-d').'.csv';

        $response = new StreamedResponse(function() use ($claims) {
            $handle = fopen('php://output', 'w+');

            fputcsv($handle, ['ID', 'Description', 'Type', 'Date', 'Statut', 'Client (Numéro de téléphone)'], ';');

            foreach ($claims as $claim) {
                fputcsv($handle, [
                    $claim->getId(),
                    $claim->getDescription(),
                    $claim->getClaimType(),
                    $claim->getCreationDate()->format('d/m/Y'),
                    $claim->getClaimStatus(),
                    $claim->getClient() ? $claim->getClient()->getPhoneNumber() : 'N/A'
                ], ';');
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=utf8');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$fileName.'"');

        return $response;
    }

    #[Route('/new', name: 'app_claim_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ProfanityFilter $profanityFilter, TwilioService $twilioService): Response
    {
        $claim = new Claim();
        $claim->setCreationDate(new \DateTime());
        $claim->setClaimStatus('new');

        $form = $this->createForm(ClaimType::class, $claim);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $censoredDescription = $profanityFilter->censor($claim->getDescription());
            $claim->setDescription($censoredDescription);

            $entityManager->persist($claim);
            $entityManager->flush();

            if ($claim->getClient() && $claim->getClient()->getPhoneNumber()) {
                $message = "Votre réclamation (#{$claim->getId()}) a été enregistrée. Nous la traiterons bientôt.";
                try {
                    $twilioService->sendSms($claim->getClient()->getPhoneNumber(), $message);
                    $this->addFlash('success', 'Réclamation créée et SMS envoyé au client.');
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Réclamation créée, mais échec de l\'envoi du SMS : ' . $e->getMessage());
                }
            } else {
                $this->addFlash('success', 'Réclamation créée, mais aucun numéro de téléphone pour envoyer un SMS.');
            }

            return $this->redirectToRoute('app_claim_index');
        }

        return $this->render('claim/new.html.twig', [
            'claim' => $claim,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_claim_show', methods: ['GET'])]
    public function show(Claim $claim): Response
    {
        return $this->render('claim/show.html.twig', [
            'claim' => $claim,
        ]);
    }

    #[Route('/admin/{id}', name: 'app_claim_admin_show', methods: ['GET'])]
    public function adminShow(Claim $claim): Response
    {
        return $this->render('claim/show_admin.html.twig', [
            'claim' => $claim,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_claim_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Claim $claim, EntityManagerInterface $entityManager, ProfanityFilter $profanityFilter): Response
    {
        $form = $this->createForm(ClaimType::class, $claim);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $censoredDescription = $profanityFilter->censor($claim->getDescription());
            $claim->setDescription($censoredDescription);

            $entityManager->flush();

            $this->addFlash('success', 'La réclamation a été modifiée avec succès.');
            return $this->redirectToRoute('app_claim_index');
        }

        return $this->render('claim/edit.html.twig', [
            'claim' => $claim,
            'form' => $form,
        ]);
    }

    #[Route('/admin/{id}/edit', name: 'app_claim_admin_edit', methods: ['GET', 'POST'])]
    public function adminEdit(Request $request, Claim $claim, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ClaimAdminEditType::class, $claim);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Le statut de la réclamation a été modifié avec succès.');
            return $this->redirectToRoute('app_claim_admin_index');
        }

        return $this->render('claim/edit_admin.html.twig', [
            'claim' => $claim,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_claim_delete', methods: ['POST'])]
    public function delete(Request $request, Claim $claim, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$claim->getId(), $request->request->get('_token'))) {
            $entityManager->remove($claim);
            $entityManager->flush();
            $this->addFlash('success', 'La réclamation a été supprimée avec succès.');
        }

        $referer = $request->headers->get('referer');
        if (str_contains($referer, 'admin/claim')) {
            return $this->redirectToRoute('app_claim_admin_index');
        }
        return $this->redirectToRoute('app_claim_index');
    }
}