<?php

namespace App\Controller;

use App\Repository\PartnerRepository;
use App\Entity\Event;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class baseController extends AbstractController
{
    #[Route('/base', name: 'app_base')]
    public function index(): Response
    {
        // You might want to pass some variables to your template
        return $this->render('base.html.twig', [
            'controller_name' => 'baseController',
        ]);
    }

    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        return $this->render('base.html.twig', [
            'controller_name' => 'baseController',
        ]);
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('dashboard.html.twig', [
            'controller_name' => 'baseController',
        ]);
    }

    #[Route('/Partners', name: 'app_projects')]
    public function projects(PartnerRepository $partnerRepository): Response
    {
        $partners = $partnerRepository->findAll();

        return $this->render('tables.html.twig', [
            'controller_name' => 'baseController',
            'partners' => $partners,
        ]);
    }

    #[Route('/tasks', name: 'app_tasks')]
    public function tasks(): Response
    {
        return $this->render('tasks.html.twig', [
            'controller_name' => 'baseController',
        ]);
    }

    #[Route('/team', name: 'app_team')]
    public function team(): Response
    {
        return $this->render('team.html.twig', [
            'controller_name' => 'baseController',
        ]);
    }

    #[Route('/profile', name: 'app_profile')]
    public function profile(): Response
    {
        return $this->render('profile.html.twig', [
            'controller_name' => 'baseController',
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): Response
    {
        // This method will not be executed.
        // The logout functionality is handled by Symfony's security system.
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}