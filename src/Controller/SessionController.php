<?php

namespace App\Controller;

use App\Entity\Session;
use App\Entity\Workshop;
use App\Form\SessionType;
use App\Repository\SessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/session')]
final class SessionController extends AbstractController
{
    #[Route('/{workshop_id?}', name: 'app_session_index', methods: ['GET'])]
    public function index(SessionRepository $sessionRepository, ?int $workshop_id = null): Response
    {
        $sessions = $workshop_id 
            ? $sessionRepository->findBy(['id_workshop' => $workshop_id])
            : $sessionRepository->findAll();

        return $this->render('session/index.html.twig', [
            'sessions' => $sessions,
            'workshop_id' => $workshop_id
        ]);
    }

    #[Route('/new/{workshop_id?}', name: 'app_session_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ?int $workshop_id = null): Response
    {
        $session = new Session();
        if ($workshop_id) {
            $workshop = $entityManager->getRepository(Workshop::class)->find($workshop_id);
            if ($workshop) {
                $session->setIdWorkshop($workshop);
            }
        }
        
        $form = $this->createForm(SessionType::class, $session);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($session);
            $entityManager->flush();

            return $this->redirectToRoute('app_session_index', [
                'workshop_id' => $session->getIdWorkshop()?->getid_workshop()
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('session/new.html.twig', [
            'session' => $session,
            'form' => $form,
            'workshop_id' => $workshop_id
        ]);
    }

    #[Route('/{id_session}', name: 'app_session_show', methods: ['GET'])]
    public function show(Session $session): Response
    {
        return $this->render('session/show.html.twig', [
            'session' => $session,
        ]);
    }

    #[Route('/{id_session}/edit', name: 'app_session_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Session $session, EntityManagerInterface $entityManager): Response
    {
        $workshop_id = $session->getIdWorkshop()?->getid_workshop();
        $form = $this->createForm(SessionType::class, $session);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_session_index', [
                'workshop_id' => $workshop_id
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('session/edit.html.twig', [
            'session' => $session,
            'form' => $form,
            'workshop_id' => $workshop_id
        ]);
    }

    #[Route('/{id_session}', name: 'app_session_delete', methods: ['POST'])]
    public function delete(Request $request, Session $session, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$session->getId_session(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($session);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_session_index', [], Response::HTTP_SEE_OTHER);
    }
}
