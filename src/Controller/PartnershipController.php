<?php

namespace App\Controller;

use App\Entity\Partnership;
use App\Entity\Contract;
use App\Form\PartnershipType;
use App\Repository\PartnershipRepository;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Form\FormError;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Service\PartnershipReminderService;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/partnership')]
class PartnershipController extends AbstractController
{
    private EmailService $emailService;
    
    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }
    
    #[Route('/', name: 'app_partnership_index', methods: ['GET'])]
    public function index(PartnershipRepository $partnershipRepository, PartnershipReminderService $reminderService, Request $request, PaginatorInterface $paginator): Response
    {
        $query = $partnershipRepository->createQueryBuilder('p')
            ->orderBy('p.id_partnership', 'DESC')
            ->getQuery();

        $partnerships = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            5// Items per page
        );

        $remindersCreated = 0;
        
        // Log start of partnership check process
        error_log("[DEBUG] Starting partnership reminder check in index page");
        
        // AUTOMATIC CHECK: Check each partnership for pending reminders
        foreach ($partnerships as $partnership) {
            $endDate = $partnership->getDateFin();
            $today = new \DateTime();
            
            if ($endDate && $today < $endDate) {
                $daysDiff = $endDate->diff($today)->days;
                
                // If partnership ends within the next 7 days, check for reminders
                if ($daysDiff <= 7) {
                    error_log("[DEBUG] Found partnership #" . $partnership->getIdPartnership() . " ending in $daysDiff days");
                    
                    // This will automatically create reminders if needed
                    $created = $reminderService->checkSinglePartnership($partnership);
                    error_log("[DEBUG] Reminder created: " . ($created ? "YES" : "NO"));
                    
                    if ($created) {
                        $remindersCreated++;
                    }
                }
            }
        }
        
        error_log("[DEBUG] Reminders created: $remindersCreated");
        
        // Show message if reminders were created
        if ($remindersCreated > 0) {
            $this->addFlash('info', $remindersCreated . ' Google Calendar reminder(s) were automatically created for partnerships ending soon.');
        }
        
        return $this->render('partnership/index.html.twig', [
            'partnerships' => $partnerships,
        ]);
    }

    #[Route('/new', name: 'app_partnership_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        $partnership = new Partnership();
        $form = $this->createForm(PartnershipType::class, $partnership);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Check for existing partnership with same partner and event
            $existingPartnership = $entityManager->getRepository(Partnership::class)->findOneBy([
                'id_partner' => $partnership->getIdPartner(),
                'id_event' => $partnership->getIdEvent()
            ]);

            if ($existingPartnership) {
                // Add error to the form
                $form->get('id_partner')->addError(new FormError('This partner is already associated with this event.'));
                return $this->render('partnership/new.html.twig', [
                    'partnership' => $partnership,
                    'form' => $form,
                ]);
            }

            // Custom date validation
            if ($partnership->getDateFin() !== null && $partnership->getDateFin() <= $partnership->getDateDebut()) {
                $form->get('date_fin')->addError(new FormError('End date must be after start date.'));
                return $this->render('partnership/new.html.twig', [
                    'partnership' => $partnership,
                    'form' => $form,
                ]);
            }

            try {
                $entityManager->persist($partnership);

                // Create a contract automatically
                $contract = new Contract();
                $contract->setIdPartnership($partnership);
                $contract->setIdPartner($partnership->getIdPartner());
                $contract->setDateDebut($partnership->getDateDebut());
                
                // Check if date_fin is null (shouldn't happen with form validation, but as a safeguard)
                if ($partnership->getDateFin() === null) {
                    $form->get('date_fin')->addError(new FormError('End date is required.'));
                    return $this->render('partnership/new.html.twig', [
                        'partnership' => $partnership,
                        'form' => $form,
                    ]);
                }
                
                $contract->setDateFin($partnership->getDateFin());
                $contract->setTerms($partnership->getTerms());
                $contract->setStatus('active');

                $entityManager->persist($contract);
                $entityManager->flush();
                
                // Send contract email to partner
                $partner = $partnership->getIdPartner();
                $event = $partnership->getIdEvent();
                
                if ($partner && $event) {
                    try {
                        $emailSent = $this->emailService->sendContractEmail(
                            $partner->getEmail(),
                            $partner->getTypePartner(),
                            $event->getNom(),
                            $partnership->getDateDebut(),
                            $partnership->getDateFin(),
                            $partnership->getTerms()
                        );
                        
                        if ($emailSent) {
                            $this->addFlash('success', 'Partnership created and contract PDF emailed to the partner.');
                        } else {
                            $this->addFlash('warning', 'Partnership created, but there was an issue sending the contract PDF email.');
                        }
                    } catch (\Exception $e) {
                        $this->addFlash('warning', 'Partnership created, but could not generate or send contract PDF: ' . $e->getMessage());
                    }
                } else {
                    $this->addFlash('success', 'Partnership created successfully.');
                }

                return $this->redirectToRoute('app_partnership_index', [], Response::HTTP_SEE_OTHER);
            } catch (\Exception $e) {
                // Add a generic error to the form
                $form->addError(new FormError('An error occurred while creating the partnership: ' . $e->getMessage()));
            }
        }

        return $this->render('partnership/new.html.twig', [
            'partnership' => $partnership,
            'form' => $form,
        ]);
    }

    #[Route('/{id_partnership}', name: 'app_partnership_show', methods: ['GET'])]
    public function show(
        int $id_partnership, 
        PartnershipRepository $partnershipRepository,
        PartnershipReminderService $reminderService
    ): Response
    {
        $partnership = $partnershipRepository->find($id_partnership);

        if (!$partnership) {
            throw $this->createNotFoundException('Partnership not found');
        }

        // Check if partnership is ending soon and create a calendar reminder if needed
        $endDate = $partnership->getDateFin();
        $today = new \DateTime();
        
        if ($endDate && $today < $endDate) {
            $daysDiff = $endDate->diff($today)->days;
            
            // If partnership ends within the next 7 days, try to create a reminder
            if ($daysDiff <= 7) {
                $reminderResult = $reminderService->checkAndCreateReminderForPartnership($partnership);
                
                // Only show message if a new reminder was created (don't show message if one already exists)
                if ($reminderResult['success'] && $reminderResult['event_id']) {
                    $this->addFlash('info', 'A Google Calendar reminder has been created for this partnership ending soon.');
                }
            }
        }

        return $this->render('partnership/show.html.twig', [
            'partnership' => $partnership,
        ]);
    }

    #[Route('/{id_partnership}/edit', name: 'app_partnership_edit', methods: ['GET', 'POST'])]
    public function edit(int $id_partnership, Request $request, PartnershipRepository $partnershipRepository, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        $partnership = $partnershipRepository->find($id_partnership);

        if (!$partnership) {
            throw $this->createNotFoundException('Partnership not found');
        }

        $originalPartner = $partnership->getIdPartner();
        $originalEvent = $partnership->getIdEvent();

        $form = $this->createForm(PartnershipType::class, $partnership);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Check for partner/event change and uniqueness
            if (($partnership->getIdPartner() !== $originalPartner || $partnership->getIdEvent() !== $originalEvent)) {
                $existingPartnership = $entityManager->getRepository(Partnership::class)->findOneBy([
                    'id_partner' => $partnership->getIdPartner(),
                    'id_event' => $partnership->getIdEvent()
                ]);

                if ($existingPartnership && $existingPartnership->getIdPartnership() !== $partnership->getIdPartnership()) {
                    $form->get('id_partner')->addError(new FormError('This partner is already associated with this event.'));
                    return $this->render('partnership/edit.html.twig', [
                        'partnership' => $partnership,
                        'form' => $form,
                    ]);
                }
            }

            // Custom date validation
            if ($partnership->getDateFin() !== null && $partnership->getDateFin() <= $partnership->getDateDebut()) {
                $form->get('date_fin')->addError(new FormError('End date must be after start date.'));
                return $this->render('partnership/edit.html.twig', [
                    'partnership' => $partnership,
                    'form' => $form,
                ]);
            }

            try {
                // Update the associated contract(s)
                foreach ($partnership->getContracts() as $contract) {
                    $contract->setIdPartner($partnership->getIdPartner());
                    $contract->setDateDebut($partnership->getDateDebut());
                    
                    // Check if date_fin is null (shouldn't happen with form validation, but as a safeguard)
                    if ($partnership->getDateFin() === null) {
                        $form->get('date_fin')->addError(new FormError('End date is required.'));
                        return $this->render('partnership/edit.html.twig', [
                            'partnership' => $partnership,
                            'form' => $form,
                        ]);
                    }
                    
                    $contract->setDateFin($partnership->getDateFin());
                    $contract->setTerms($partnership->getTerms());

                    // Explicitly persist each contract
                    $entityManager->persist($contract);
                }

                $entityManager->flush();
                $this->addFlash('success', 'Partnership and associated contract(s) updated.');

                return $this->redirectToRoute('app_partnership_index', [], Response::HTTP_SEE_OTHER);
            } catch (\Exception $e) {
                $form->addError(new FormError('An error occurred while updating the partnership: ' . $e->getMessage()));
            }
        }

        return $this->render('partnership/edit.html.twig', [
            'partnership' => $partnership,
            'form' => $form,
        ]);
    }

    #[Route('/{id_partnership}/resend-contract', name: 'app_partnership_resend_contract', methods: ['GET'])]
    public function resendContract(int $id_partnership, PartnershipRepository $partnershipRepository): Response
    {
        $partnership = $partnershipRepository->find($id_partnership);

        if (!$partnership) {
            throw $this->createNotFoundException('Partnership not found');
        }
        
        $partner = $partnership->getIdPartner();
        $event = $partnership->getIdEvent();
        
        if ($partner && $event) {
            try {
                $emailSent = $this->emailService->sendContractEmail(
                    $partner->getEmail(),
                    $partner->getTypePartner(),
                    $event->getNom(),
                    $partnership->getDateDebut(),
                    $partnership->getDateFin(),
                    $partnership->getTerms()
                );
                
                if ($emailSent) {
                    $this->addFlash('success', 'Contract PDF generated and emailed to the partner.');
                } else {
                    $this->addFlash('warning', 'There was an issue generating or sending the contract PDF email.');
                }
            } catch (\Exception $e) {
                $this->addFlash('error', 'Failed to generate or send contract PDF: ' . $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Unable to send contract email - missing partner or event information.');
        }
        
        return $this->redirectToRoute('app_partnership_show', ['id_partnership' => $id_partnership], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id_partnership}', name: 'app_partnership_delete', methods: ['POST'])]
    public function delete(int $id_partnership, Request $request, PartnershipRepository $partnershipRepository, EntityManagerInterface $entityManager): Response
    {
        $partnership = $partnershipRepository->find($id_partnership);

        if (!$partnership) {
            throw $this->createNotFoundException('Partnership not found');
        }

        if ($this->isCsrfTokenValid('delete'.$partnership->getIdPartnership(), $request->getPayload()->getString('_token'))) {
            // Contracts associated with partnership will be removed automatically
            // because of cascade = {"remove"} in OneToMany relationship
            $entityManager->remove($partnership);
            $entityManager->flush();

            $this->addFlash('success', 'Partnership and associated contract(s) deleted.');
        }

        return $this->redirectToRoute('app_partnership_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/debug-reminders', name: 'app_partnership_debug_reminders')]
    public function debugReminders(
        PartnershipRepository $partnershipRepository,
        PartnershipReminderService $reminderService
    ): Response
    {
        // Only allow in dev environment
        if ($this->getParameter('kernel.environment') !== 'dev') {
            throw $this->createAccessDeniedException('Debug endpoint only available in dev environment');
        }
        
        $output = [];
        $partnerships = $partnershipRepository->findAll();
        $count = 0;
        $created = 0;
        $errors = 0;
        
        // Get token path
        $tokenPath = $this->getParameter('kernel.project_dir') . '/var/google_token.json';
        $output[] = "Token path: $tokenPath";
        $output[] = "Token exists: " . (file_exists($tokenPath) ? 'Yes' : 'No');
        
        if (file_exists($tokenPath)) {
            $tokenContent = file_get_contents($tokenPath);
            $output[] = "Token content length: " . strlen($tokenContent) . " bytes";
            $tokenData = json_decode($tokenContent, true);
            $output[] = "Token valid JSON: " . (json_last_error() === JSON_ERROR_NONE ? 'Yes' : 'No - ' . json_last_error_msg());
            
            if (isset($tokenData['access_token'])) {
                $output[] = "Access token present: Yes";
            } else {
                $output[] = "Access token present: No";
            }
            
            if (isset($tokenData['refresh_token'])) {
                $output[] = "Refresh token present: Yes";
            } else {
                $output[] = "Refresh token present: No";
            }
        }
        
        $output[] = "------- Checking Partnerships -------";
        
        foreach ($partnerships as $partnership) {
            $count++;
            $endDate = $partnership->getDateFin();
            
            if (!$endDate) {
                $output[] = "Partnership #{$partnership->getIdPartnership()}: No end date";
                continue;
            }
            
            $today = new \DateTime();
            $diff = $today->diff($endDate);
            $diffDays = $diff->days;
            $inFuture = $today < $endDate;
            
            $output[] = "Partnership #{$partnership->getIdPartnership()}: " . 
                "Ends {$endDate->format('Y-m-d')}, " . 
                "Today: {$today->format('Y-m-d')}, " . 
                "Days until end: $diffDays, " . 
                "End in future: " . ($inFuture ? 'Yes' : 'No');
            
            if ($inFuture && $diffDays <= 7) {
                $output[] = "  - Eligible for reminder (ending within 7 days)";
                
                try {
                    $created = $reminderService->checkSinglePartnership($partnership);
                    if ($created) {
                        $output[] = "  - CREATED NEW REMINDER";
                        $created++;
                    } else {
                        $output[] = "  - Not created (already exists or error occurred)";
                    }
                } catch (\Exception $e) {
                    $output[] = "  - ERROR: " . $e->getMessage();
                    $errors++;
                }
            } else {
                $output[] = "  - Not eligible for reminder";
            }
        }
        
        $output[] = "------- Summary -------";
        $output[] = "Total partnerships: $count";
        $output[] = "Reminders created: $created";
        $output[] = "Errors: $errors";
        
        return new Response("<html><body><pre>" . implode("\n", $output) . "</pre></body></html>");
    }
}