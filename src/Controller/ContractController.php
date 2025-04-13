<?php

namespace App\Controller;

use App\Entity\Contract;
use App\Form\ContractType;
use App\Repository\ContractRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Form\FormError;

#[Route('/contract')]
final class ContractController extends AbstractController{
    #[Route('/', name: 'app_contract_index', methods: ['GET'])]
    public function index(ContractRepository $contractRepository): Response
    {
        return $this->render('contract/index.html.twig', [
            'contracts' => $contractRepository->findAll(),
        ]);
    }

    #[Route('/{id_contract}', name: 'app_contract_show', methods: ['GET'])]
    public function show(Contract $contract): Response
    {
        return $this->render('contract/show.html.twig', [
            'contract' => $contract,
        ]);
    }

    #[Route('/{id_contract}/edit', name: 'app_contract_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Contract $contract, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ContractType::class, $contract, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Check if status is being set to "expired"
            if ($contract->getStatus() === 'expired') {
                // Get the associated partnership
                $partnership = $contract->getIdPartnership();
                
                if ($partnership) {
                    $today = new \DateTime();
                    $partnershipEndDate = $partnership->getDateFin();
                    
                    // Check if partnership end date is in the future or doesn't exist
                    if ($partnershipEndDate === null || $partnershipEndDate > $today) {
                        $errorMessage = 'Status cannot be set to Expired while the partnership is still active. ';
                        $errorMessage .= $partnershipEndDate ? 'Partnership ends on ' . $partnershipEndDate->format('Y-m-d') : 'Partnership has no end date set.';
                        
                        $form->get('status')->addError(new FormError($errorMessage));
                        return $this->render('contract/edit.html.twig', [
                            'contract' => $contract,
                            'form' => $form,
                        ]);
                    }
                }
            }
            
            // Validate the relationship between dates
            if ($contract->getDateFin() <= $contract->getDateDebut()) {
                $startDate = $contract->getDateDebut()->format('Y-m-d');
                $endDate = $contract->getDateFin()->format('Y-m-d');
                $errorMessage = "End date ($endDate) must be after start date ($startDate).";
                $form->get('date_fin')->addError(new FormError($errorMessage));
                return $this->render('contract/edit.html.twig', [
                    'contract' => $contract,
                    'form' => $form,
                ]);
            }
            
            // Validate start date is today or in the future
            $today = new \DateTime();
            $today->setTime(0, 0, 0);
            if ($contract->getDateDebut() < $today) {
                $startDate = $contract->getDateDebut()->format('Y-m-d');
                $todayStr = $today->format('Y-m-d');
                $errorMessage = "Start date ($startDate) must be today ($todayStr) or in the future.";
                $form->get('date_debut')->addError(new FormError($errorMessage));
                return $this->render('contract/edit.html.twig', [
                    'contract' => $contract,
                    'form' => $form,
                ]);
            }
            
            // Check if terms is empty
            if (empty(trim($contract->getTerms()))) {
                $form->get('terms')->addError(new FormError('Contract terms cannot be empty.'));
                return $this->render('contract/edit.html.twig', [
                    'contract' => $contract,
                    'form' => $form,
                ]);
            }
            
            try {
                // Sync changes with the associated partnership
                $partnership = $contract->getIdPartnership();
                if ($partnership) {
                    // Update the partnership dates and terms to match the contract
                    $partnership->setDateDebut($contract->getDateDebut());
                    $partnership->setDateFin($contract->getDateFin());
                    $partnership->setTerms($contract->getTerms());
                    
                    // If we're updating multiple entities, make sure to persist them
                    $entityManager->persist($partnership);
                }
                
                $entityManager->flush();
                $this->addFlash('success', 'Contract updated successfully! Associated partnership was also updated.');
                return $this->redirectToRoute('app_contract_index', [], Response::HTTP_SEE_OTHER);
            } catch (\Exception $e) {
                $form->addError(new FormError('An error occurred while updating the contract: ' . $e->getMessage()));
            }
        }

        return $this->render('contract/edit.html.twig', [
            'contract' => $contract,
            'form' => $form,
        ]);
    }

    #[Route('/{id_contract}', name: 'app_contract_delete', methods: ['POST'])]
    public function delete(Request $request, Contract $contract, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$contract->getId_contract(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($contract);
            $entityManager->flush();
            
            $this->addFlash('success', 'Contract deleted successfully!');
        }

        return $this->redirectToRoute('app_contract_index', [], Response::HTTP_SEE_OTHER);
    }
}
