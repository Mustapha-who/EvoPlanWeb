<?php

namespace App\Controller;

use App\Entity\Claim;
use App\Form\ClaimType;
use App\Repository\ClaimRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/claim')]
final class ClaimController extends AbstractController
{
    #[Route('/', name: 'app_claim_index', methods: ['GET'])]
    public function index(ClaimRepository $claimRepository): Response
    {
        return $this->render('claim/index.html.twig', [
            'claims' => $claimRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_claim_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $claim = new Claim();
        $claim->setCreationDate(new \DateTime());
        $claim->setClaimStatus('new'); // Rétabli à setClaimStatus()

        $form = $this->createForm(ClaimType::class, $claim);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($claim);
            $entityManager->flush();

            $this->addFlash('success', 'La réclamation a été créée avec succès.');
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

    #[Route('/{id}/edit', name: 'app_claim_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Claim $claim, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ClaimType::class, $claim);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'La réclamation a été modifiée avec succès.');
            return $this->redirectToRoute('app_claim_show', ['id' => $claim->getId()]);
        }

        return $this->render('claim/edit.html.twig', [
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

        return $this->redirectToRoute('app_claim_index');
    }
}