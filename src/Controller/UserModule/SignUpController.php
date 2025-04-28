<?php

namespace App\Controller\UserModule;

use App\Entity\UserModule\Client;
use App\Entity\UserModule\Instructor;
use App\Form\UserModule\InstructorRegistrationFormType;
use App\Form\UserModule\ClientRegistrationFormType;
use App\Service\UserModule\UserEmailService;
use App\Service\UserModule\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
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
    private UserEmailService $userEmailService;
    public function __construct(UserService $userService,UserEmailService $userEmailService)
    {
        $this->userService = $userService;
        $this->userEmailService = $userEmailService;
    }

    #[Route('/signup', name: 'signup')]
    public function signup(Request $request): Response
    {
        $clientForm = $this->createForm(ClientRegistrationFormType::class);
        $instructorForm = $this->createForm(InstructorRegistrationFormType::class);
        $accountType = $request->query->get('type', 'client');

        $clientForm->handleRequest($request);
        $instructorForm->handleRequest($request);

        $cache = new FilesystemAdapter();

        if ($clientForm->isSubmitted() && $clientForm->isValid()) {
            $data = $clientForm->getData();
            $data['type'] = 'client';
            $token = bin2hex(random_bytes(32));

            // Store user data in cache
            $cache->save($cache->getItem("email_verification_$token")->set($data)->expiresAfter(3600));

            // Generate verification link
            $verificationUrl = $this->generateUrl('verify_email', ['token' => $token], true);

            // Send verification email
            $this->userEmailService->sendEmail(
                $data['email'],
                'Email Verification',
                "Please verify your email by clicking the link: $verificationUrl"
            );

            return $this->json(['message' => 'Verification email sent']);
        }

        if ($instructorForm->isSubmitted() && $instructorForm->isValid()) {
            $data = $instructorForm->getData();
            $data['type'] = 'instructor';
            $token = bin2hex(random_bytes(32));

            // Store user data in cache
            $cache->save($cache->getItem("email_verification_$token")->set($data)->expiresAfter(3600));

            // Generate verification link
            $verificationUrl = $this->generateUrl('verify_email', ['token' => $token], true);

            // Send verification email
            $this->userEmailService->sendEmail(
                $data['email'],
                'Email Verification',
                "Please verify your email by clicking the link: $verificationUrl"
            );

            return $this->json(['message' => 'Verification email sent']);
        }

        return $this->render('auth/signup.html.twig', [
            'clientForm' => $clientForm->createView(),
            'instructorForm' => $instructorForm->createView(),
            'accountType' => $accountType,
        ]);
    }

    #[Route('/verify-email', name: 'verify_email', methods: ['GET'])]
    public function verifyEmail(Request $request): Response
    {
        $token = $request->query->get('token');
        if (!$token) {
            return $this->json(['error' => 'Token is missing'], Response::HTTP_BAD_REQUEST);
        }

        $cache = new FilesystemAdapter();
        $cachedItem = $cache->getItem("email_verification_$token");

        if (!$cachedItem->isHit()) {
            return $this->json(['error' => 'Invalid or expired token'], Response::HTTP_BAD_REQUEST);
        }

        // Retrieve user data from cache
        $userData = $cachedItem->get();

        // Create the user account
        if (isset($userData['type']) && $userData['type'] === 'client') {
            $client = new Client();
            $client->setName($userData['firstName'] . ' ' . $userData['lastName']);
            $client->setEmail($userData['email']);
            $client->setPhoneNumber($userData['phoneNumber']);
            $client->setPassword($userData['password']);
            $this->userService->createClient($client);
        } else {
            $instructor = new Instructor();
            $instructor->setName($userData['firstName'] . ' ' . $userData['lastName']);
            $instructor->setEmail($userData['email']);
            $instructor->setCertification($userData['certificate']);
            $instructor->setPassword($userData['password']);
            $this->userService->createInstructor($instructor);
        }

        // Remove the token from cache
        $cache->deleteItem("email_verification_$token");

        return $this->json(['message' => 'Email verified and account created']);
    }

}