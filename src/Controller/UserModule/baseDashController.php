<?php
namespace App\Controller\UserModule;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class baseDashController extends AbstractController
{
    #[Route('/sidenav/profile', name: 'side_user_profile')]
    public function profile(): Response
    {
        return $this->redirectToRoute('user_profile');
    }

    #[Route('/sidenav/userdash', name: 'side_user_dashboard')]
    public function index(): Response
    {
        return $this->redirectToRoute('user_dashboard');
    }
    #[Route('/sidenav/account_security', name: 'side_account_security')]
    public function security(): Response
    {
        return $this->redirectToRoute('account_security');
    }


}