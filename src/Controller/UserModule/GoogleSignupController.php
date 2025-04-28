<?php

namespace App\Controller\UserModule;

use App\Entity\UserModule\Client;
use App\Entity\UserModule\Instructor;
use App\Form\UserModule\ClientRegistrationFormType;
use App\Form\UserModule\GoogleClientRegisterFormType;
use App\Form\UserModule\GoogleInstructorRegisterFormType;
use App\Form\UserModule\InstructorRegistrationFormType;
use App\Service\UserModule\UserEmailService;
use App\Service\UserModule\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GoogleSignupController extends AbstractController
{

    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    #[Route('/google-signup', name: 'google_signup')]
    public function signup(Request $request): Response
    {

        // Retrieve the email from the Google authentication process
        $email = $request->query->get('email');
        if (!$email) {
            return $this->redirectToRoute('login');
        }


        $clientForm = $this->createForm(GoogleclientRegisterFormType::class, null, ['email' => $email]);
        $instructorForm = $this->createForm(GoogleInstructorRegisterFormType::class, null, ['email' => $email]);
        $accountType = $request->query->get('type', 'client');

        $clientForm->handleRequest($request);
        $instructorForm->handleRequest($request);

        if ($clientForm->isSubmitted() && $clientForm->isValid()) {
            $data = $clientForm->getData();
            $client = new Client();
            $client->setName($data['firstName'] . ' ' . $data['lastName']);
            $client->setEmail($email);
            $client->setPhoneNumber($data['phoneNumber']);
            $client->setPassword($data['password']);
            $this->userService->createClient($client);
            return $this->redirectToRoute('login');
        }

        if ($instructorForm->isSubmitted() && $instructorForm->isValid()) {
            $data = $instructorForm->getData();
            $instructor = new Instructor();
            $instructor->setName($data['firstName'] . ' ' . $data['lastName']);
            $instructor->setEmail($email);
            $instructor->setCertification($data['certificate']);
            $instructor->setPassword($data['password']);
            $this->userService->createInstructor($instructor);
            return $this->redirectToRoute('login');
        }

        return $this->render('auth/googleSignup.html.twig', [
            'GoogleclientForm' => $clientForm->createView(),
            'GoogleinstructorForm' => $instructorForm->createView(),
            'accountType' => $accountType,
        ]);
    }

}