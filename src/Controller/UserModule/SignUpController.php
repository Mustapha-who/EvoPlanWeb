<?php

namespace App\Controller\UserModule;

use App\Entity\UserModule\Client;
use App\Entity\UserModule\Instructor;
use App\Form\UserModule\InstructorRegistrationFormType;
use App\Form\UserModule\ClientRegistrationFormType;
use App\Service\UserModule\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SignUpController extends AbstractController
{
//    #[Route('/signup', name: 'signup')]
//    public function signup(): Response
//    {
//        return $this->render('auth/signup.html.twig');
//    }
//
//    #[Route('/tologin', name: 'tologin')]
//    public function tologin(): Response
//    {
//        return $this->render('auth/login.html.twig');
//    }

    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    #[Route('/signup', name: 'signup')]
    public function signup(Request $request): Response
    {
        $clientForm = $this->createForm(ClientRegistrationFormType::class);
        $instructorForm = $this->createForm(InstructorRegistrationFormType::class);
        $accountType = $request->query->get('type', 'client');

        $clientForm->handleRequest($request);
        $instructorForm->handleRequest($request);

        if ($clientForm->isSubmitted() && $clientForm->isValid()) {
            $data = $clientForm->getData();
            $client = new Client();
            $client->setName($data['firstName'] . ' ' . $data['lastName']);
            $client->setEmail($data['email']);
            $client->setPhoneNumber($data['phoneNumber']);
            $client->setPassword($data['password']);
            $this->userService->createClient($client);
            return $this->redirectToRoute('login');
        }

        if ($instructorForm->isSubmitted() && $instructorForm->isValid()) {
            $data = $instructorForm->getData();
            $instructor = new Instructor();
            $instructor->setName($data['firstName'] . ' ' . $data['lastName']);
            $instructor->setEmail($data['email']);
            $instructor->setCertification($data['certificate']);
            $instructor->setPassword($data['password']);
            $this->userService->createInstructor($instructor);
            return $this->redirectToRoute('login');
        }

        return $this->render('auth/signup.html.twig', [
            'clientForm' => $clientForm->createView(),
            'instructorForm' => $instructorForm->createView(),
            'accountType' => $accountType,
        ]);
    }

}