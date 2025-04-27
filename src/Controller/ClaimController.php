<?php

namespace App\Controller;

use App\Entity\Claim;
use App\Entity\UserModule\Client;
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
use Knp\Component\Pager\PaginatorInterface;
use Psr\Log\LoggerInterface;

#[Route('/claim')]
final class ClaimController extends AbstractController
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[Route('/new', name: 'app_claim_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ProfanityFilter $profanityFilter, TwilioService $twilioService): Response
    {
        $claim = new Claim();
        $form = $this->createForm(ClaimType::class, $claim);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $description = $claim->getDescription();
            $censoredDescription = $profanityFilter->censor($description);
            $this->logger->info('Claim description censored', [
                'original' => $description,
                'censored' => $censoredDescription,
            ]);
            $claim->setDescription($censoredDescription);

            $client = $this->getUser();
            if (!$client instanceof Client) {
                throw $this->createAccessDeniedException('Vous devez être connecté en tant que client.');
            }
            $claim->setClient($client);
            $claim->setCreationDate(new \DateTime());
            $claim->setClaimStatus('new');

            $entityManager->persist($claim);
            $entityManager->flush();

            try {
                $twilioService->sendSms(
                    $client->getPhoneNumber(),
                    'Votre réclamation a été enregistrée avec succès. ID: ' . $claim->getId()
                );
            } catch (\Exception $e) {
                $this->logger->error('Failed to send SMS', ['exception' => $e->getMessage()]);
            }

            $this->addFlash('success', 'Votre réclamation a été enregistrée avec succès.');

            return $this->redirectToRoute('app_claim_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('claim/new.html.twig', [
            'claim' => $claim,
            'form' => $form->createView(),
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
        $filterForm->handleRequest($request);

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

        $statusCounts = [
            'new' => $entityManager->getRepository(Claim::class)
                ->createQueryBuilder('c')
                ->select('COUNT(c.id)')
                ->where('c.claimStatus = :status')
                ->setParameter('status', 'new')
                ->getQuery()
                ->getSingleScalarResult(),
            'in_progress' => $entityManager->getRepository(Claim::class)
                ->createQueryBuilder('c')
                ->select('COUNT(c.id)')
                ->where('c.claimStatus = :status')
                ->setParameter('status', 'in_progress')
                ->getQuery()
                ->getSingleScalarResult(),
            'resolved' => $entityManager->getRepository(Claim::class)
                ->createQueryBuilder('c')
                ->select('COUNT(c.id)')
                ->where('c.claimStatus = :status')
                ->setParameter('status', 'resolved')
                ->getQuery()
                ->getSingleScalarResult(),
        ];

        return $this->render('claim/indexback.html.twig', [
            'claims' => $pagination,
            'status_counts' => $statusCounts,
            'filter_form' => $filterForm->createView(),
        ]);
    }

    #[Route('/admin/claim/calendar', name: 'app_claim_admin_calendar', methods: ['GET'])]
    public function calendar(EntityManagerInterface $entityManager): Response
    {
        $claims = $entityManager->getRepository(Claim::class)
            ->createQueryBuilder('c')
            ->select('c')
            ->getQuery()
            ->getResult();

        $this->logger->info('Claims retrieved for calendar', [
            'claim_count' => count($claims),
        ]);

        $claimsByDate = [];
        foreach ($claims as $claim) {
            $creationDate = $claim->getCreationDate();
            if ($creationDate instanceof \DateTimeInterface) {
                $date = $creationDate->format('Y-m-d');
                if (!isset($claimsByDate[$date])) {
                    $claimsByDate[$date] = 0;
                }
                $claimsByDate[$date]++;
            } else {
                $this->logger->warning('Claim has invalid creation date', [
                    'claim_id' => $claim->getId(),
                    'creation_date' => $creationDate,
                ]);
            }
        }

        $events = [];
        foreach ($claimsByDate as $date => $count) {
            $events[] = [
                'title' => $count . ' réclamation(s)',
                'start' => $date,
                'url' => $this->generateUrl('app_claim_admin_index', ['filter_form' => ['keyword' => $date]]),
            ];
        }

        $this->logger->info('Calendar events generated', [
            'event_count' => count($events),
            'events' => $events,
        ]);

        return $this->render('claim/calendar.html.twig', [
            'events' => json_encode($events, JSON_THROW_ON_ERROR),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_claim_admin_edit', methods: ['GET', 'POST'])]
    public function adminEdit(Request $request, Claim $claim, EntityManagerInterface $entityManager, ProfanityFilter $profanityFilter): Response
    {
        $form = $this->createForm(ClaimAdminEditType::class, $claim);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $description = $claim->getDescription();
            $censoredDescription = $profanityFilter->censor($description);
            $this->logger->info('Claim description censored', [
                'original' => $description,
                'censored' => $censoredDescription,
            ]);
            $claim->setDescription($censoredDescription);

            $entityManager->flush();

            $this->addFlash('success', 'La réclamation a été mise à jour avec succès.');

            return $this->redirectToRoute('app_claim_admin_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('claim/edit.html.twig', [
            'claim' => $claim,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_claim_delete', methods: ['POST'])]
    public function delete(Request $request, Claim $claim, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $claim->getId(), $request->request->get('_token'))) {
            $entityManager->remove($claim);
            $entityManager->flush();
            $this->addFlash('success', 'La réclamation a été supprimée avec succès.');
        } else {
            $this->addFlash('error', 'Erreur lors de la suppression de la réclamation.');
        }

        return $this->redirectToRoute('app_claim_admin_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/export/excel', name: 'app_claim_export_excel', methods: ['GET'])]
    public function exportExcel(Request $request, EntityManagerInterface $entityManager): Response
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Réclamations');

        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'Description');
        $sheet->setCellValue('C1', 'Type');
        $sheet->setCellValue('D1', 'Statut');
        $sheet->setCellValue('E1', 'Date de création');
        $sheet->setCellValue('F1', 'Client');

        $queryBuilder = $entityManager->getRepository(Claim::class)
            ->createQueryBuilder('c')
            ->leftJoin('c.client', 'cl')
            ->addSelect('cl');

        $filters = $request->query->all();
        if (!empty($filters['claimStatus'])) {
            $queryBuilder->andWhere('c.claimStatus = :status')
                ->setParameter('status', $filters['claimStatus']);
        }
        if (!empty($filters['claimType'])) {
            $queryBuilder->andWhere('c.claimType = :type')
                ->setParameter('type', $filters['claimType']);
        }
        if (!empty($filters['keyword'])) {
            $queryBuilder->andWhere('c.description LIKE :keyword')
                ->setParameter('keyword', '%' . $filters['keyword'] . '%');
        }

        $claims = $queryBuilder->getQuery()->getResult();

        $row = 2;
        foreach ($claims as $claim) {
            $sheet->setCellValue('A' . $row, $claim->getId());
            $sheet->setCellValue('B' . $row, $claim->getDescription());
            $sheet->setCellValue('C' . $row, $claim->getClaimType());
            $sheet->setCellValue('D' . $row, $claim->getClaimStatus());
            $sheet->setCellValue('E' . $row, $claim->getCreationDate() ? $claim->getCreationDate()->format('Y-m-d') : '');
            $sheet->setCellValue('F' . $row, $claim->getClient() ? $claim->getClient()->getPhoneNumber() : '');
            $row++;
        }

        $writer = new Xlsx($spreadsheet);
        $response = new StreamedResponse(
            function () use ($writer) {
                $writer->save('php://output');
            }
        );

        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'reclamations.xlsx'
        );
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    #[Route('/export/pdf', name: 'app_claim_export_pdf', methods: ['GET'])]
    public function exportPdf(Request $request, EntityManagerInterface $entityManager): Response
    {
        $queryBuilder = $entityManager->getRepository(Claim::class)
            ->createQueryBuilder('c')
            ->leftJoin('c.client', 'cl')
            ->addSelect('cl');

        $filters = $request->query->all();
        if (!empty($filters['claimStatus'])) {
            $queryBuilder->andWhere('c.claimStatus = :status')
                ->setParameter('status', $filters['claimStatus']);
        }
        if (!empty($filters['claimType'])) {
            $queryBuilder->andWhere('c.claimType = :type')
                ->setParameter('type', $filters['claimType']);
        }
        if (!empty($filters['keyword'])) {
            $queryBuilder->andWhere('c.description LIKE :keyword')
                ->setParameter('keyword', '%' . $filters['keyword'] . '%');
        }

        $claims = $queryBuilder->getQuery()->getResult();

        $html = $this->renderView('claim/export_pdf.html.twig', [
            'claims' => $claims,
        ]);

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $response = new Response($dompdf->output());
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'reclamations.pdf'
        );
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    #[Route('/export/csv', name: 'app_claim_export_csv', methods: ['GET'])]
    public function exportCsv(Request $request, EntityManagerInterface $entityManager): Response
    {
        $queryBuilder = $entityManager->getRepository(Claim::class)
            ->createQueryBuilder('c')
            ->leftJoin('c.client', 'cl')
            ->addSelect('cl');

        $filters = $request->query->all();
        if (!empty($filters['claimStatus'])) {
            $queryBuilder->andWhere('c.claimStatus = :status')
                ->setParameter('status', $filters['claimStatus']);
        }
        if (!empty($filters['claimType'])) {
            $queryBuilder->andWhere('c.claimType = :type')
                ->setParameter('type', $filters['claimType']);
        }
        if (!empty($filters['keyword'])) {
            $queryBuilder->andWhere('c.description LIKE :keyword')
                ->setParameter('keyword', '%' . $filters['keyword'] . '%');
        }

        $claims = $queryBuilder->getQuery()->getResult();

        $response = new StreamedResponse(function () use ($claims) {
            $handle = fopen('php://output', 'w+');
            fputcsv($handle, ['ID', 'Description', 'Type', 'Statut', 'Date de création', 'Client']);

            foreach ($claims as $claim) {
                fputcsv($handle, [
                    $claim->getId(),
                    $claim->getDescription(),
                    $claim->getClaimType(),
                    $claim->getClaimStatus(),
                    $claim->getCreationDate() ? $claim->getCreationDate()->format('Y-m-d') : '',
                    $claim->getClient() ? $claim->getClient()->getPhoneNumber() : '',
                ]);
            }

            fclose($handle);
        });

        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'reclamations.csv'
        );
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    #[Route('/', name: 'app_claim_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $entityManager, PaginatorInterface $paginator): Response
    {
        $client = $this->getUser();
        if (!$client instanceof Client) {
            throw $this->createAccessDeniedException('Vous devez être connecté en tant que client.');
        }

        $queryBuilder = $entityManager->getRepository(Claim::class)
            ->createQueryBuilder('c')
            ->where('c.client = :client')
            ->setParameter('client', $client);

        $pagination = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('claim/index.html.twig', [
            'claims' => $pagination,
        ]);
    }

    #[Route('/{id}', name: 'app_claim_admin_show', methods: ['GET'])]
    public function show(Claim $claim): Response
    {
        return $this->render('claim/show.html.twig', [
            'claim' => $claim,
        ]);
    }
}