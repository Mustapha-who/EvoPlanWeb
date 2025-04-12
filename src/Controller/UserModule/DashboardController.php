<?php

namespace App\Controller\UserModule;

use App\Service\UserModule\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{


    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    #[Route('/userdash', name: 'user_dashboard')]
    public function index(): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('admin_dashboard');
        } elseif ($this->isGranted('ROLE_EVENTPLANNER')) {
            return $this->render('User/eventplannerDashboard.html.twig');
        }

        throw $this->createAccessDeniedException('Access Denied.');
    }

}