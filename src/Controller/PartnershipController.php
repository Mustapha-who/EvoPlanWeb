<?php

namespace App\Controller;

use App\Entity\Partnership;
use App\Entity\Contract;
use App\Form\PartnershipType;
use App\Repository\PartnershipRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Form\FormError;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/partnership')]
class PartnershipController extends AbstractController
{
    #[Route('/', name: 'app_partnership_index', methods: ['GET'])]
    public function index(PartnershipRepository $partnershipRepository): Response
    {
        return $this->render('partnership/index.html.twig', [
            'partnerships' => $partnershipRepository->findAll(),
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
                $contract->setDateFin($partnership->getDateFin());
                $contract->setTerms($partnership->getTerms());
                $contract->setStatus('active');
                
                $entityManager->persist($contract);
                $entityManager->flush();

                return $this->redirectToRoute('app_partnership_index', [], Response::HTTP_SEE_OTHER);
            } catch (\Exception $e) {
                // Add a generic error to the form
                $form->addError(new FormError('An error occurred while creating the partnership.'));
            }
        }

        return $this->render('partnership/new.html.twig', [
            'partnership' => $partnership,
            'form' => $form,
        ]);
    }

    #[Route('/{id_partnership}', name: 'app_partnership_show', methods: ['GET'])]
    public function show(int $id_partnership, PartnershipRepository $partnershipRepository): Response
    {
        $partnership = $partnershipRepository->find($id_partnership);
        
        if (!$partnership) {
            throw $this->createNotFoundException('Partnership not found');
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
                    $contract->setDateFin($partnership->getDateFin());
                    $contract->setTerms($partnership->getTerms());
                    
                    // Explicitly persist each contract
                    $entityManager->persist($contract);
                }
                
                $entityManager->flush();
                $this->addFlash('success', 'Partnership and associated contract(s) updated.');
                
                return $this->redirectToRoute('app_partnership_index', [], Response::HTTP_SEE_OTHER);
            } catch (\Exception $e) {
                $form->addError(new FormError('An error occurred while updating the partnership.'));
            }
        }

        return $this->render('partnership/edit.html.twig', [
            'partnership' => $partnership,
            'form' => $form,
        ]);
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
}
