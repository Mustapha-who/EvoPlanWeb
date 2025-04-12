<?php
// src/Controller/UserModule/UserAccountController.php

namespace App\Controller\UserModule;

use App\Entity\UserModule\Administrator;
use App\Entity\UserModule\Client;
use App\Entity\UserModule\EventPlanner;
use App\Entity\UserModule\Instructor;
use App\Entity\UserModule\UserDTO;
use App\Form\UserModule\AdministratorUpdateFormType;
use App\Form\UserModule\ClientUpdateFormType;
use App\Form\UserModule\EventPlannerUpdateFormType;
use App\Form\UserModule\InstructorUpdateFormType;
use App\Service\UserModule\UserService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserAccountController extends AbstractController
{
    private UserService $userService;
    private LoggerInterface $logger;
    private Administrator $administrator;
    private EventPlanner $eventPlanner;
    private Instructor $instructor;
    private Client $client;
    public function __construct(UserService $userService, LoggerInterface $logger)
    {
        $this->userService = $userService;
        $this->logger = $logger;
        $this->administrator = new Administrator();
        $this->eventPlanner = new EventPlanner();
        $this->instructor = new Instructor();
        $this->client = new Client();
    }

    #[Route('/user/profile', name: 'user_profile')]
    public function profile(Request $request): Response
    {
        $user = $this->getUser();
        $userId = $user->getId();
        $role = $user->getRoles()[0];
        $userDTO = new UserDTO();
        $form = null;

        switch ($role) {
            case 'ROLE_ADMIN':
                $userData = $this->userService->getAdministratorById($userId);
                $userDTO->setName($userData->getName());
                $userDTO->setEmail($userData->getEmail());
                $form = $this->createForm(AdministratorUpdateFormType::class, $userDTO);
                break;
            case 'ROLE_EVENTPLANNER':
                $userData = $this->userService->getEventPlannerById($userId);
                $userDTO->setName($userData->getName());
                $userDTO->setEmail($userData->getEmail());
                $userDTO->setAssignedModule($userData->getAssignedModule());
                $userDTO->setSpecialization($userData->getSpecialization());
                $form = $this->createForm(EventPlannerUpdateFormType::class, $userDTO);
                break;
            case 'ROLE_CLIENT':
                $userData = $this->userService->getClientById($userId);
                $userDTO->setName($userData->getName());
                $userDTO->setEmail($userData->getEmail());
                $userDTO->setPhoneNumber($userData->getPhoneNumber());
                $form = $this->createForm(ClientUpdateFormType::class, $userDTO);
                break;
            case 'ROLE_INSTRUCTOR':
                $userData = $this->userService->getInstructorById($userId);
                $userDTO->setName($userData->getName());
                $userDTO->setEmail($userData->getEmail());
                $userDTO->setCertificate($userData->getCertification());
                $form = $this->createForm(InstructorUpdateFormType::class, $userDTO);
                break;
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $updatedUser = $form->getData();
            $this->logger->info('form data: ' . json_encode($updatedUser) );
            $this->logger->info('form data: ' . json_encode($userDTO) );
            $changes = false;

            if ($updatedUser->getName() !== $this->userService->getUserById($userId)->getName()) {
                $this->administrator->setName($updatedUser->getName());
                $this->eventPlanner->setName($updatedUser->getName());
                $this->client->setName($updatedUser->getName());
                $this->instructor->setName($updatedUser->getName());
                $changes = true;
            }

            if ($updatedUser->getEmail() !== $this->userService->getUserById($userId)->getEmail()) {
                $this->administrator->setEmail($updatedUser->getEmail());
                $this->eventPlanner->setEmail($updatedUser->getEmail());
                $this->client->setEmail($updatedUser->getEmail());
                $this->instructor->setEmail($updatedUser->getEmail());
                $changes = true;
            }

            if ($updatedUser->getPhoneNumber()!= null && $updatedUser->getPhoneNumber() !== $this->userService->getClientById($userId)->getPhoneNumber()) {
                $this->client->setPhoneNumber($updatedUser->getPhoneNumber());
                $changes = true;
            }

            if ($updatedUser->getSpecialization()!= null && $updatedUser->getSpecialization() !== $this->userService->getEventPlannerById($userId)->getSpecialization()) {
                $this->eventPlanner->setSpecialization($updatedUser->getSpecialization());
                $changes = true;
            }
            if ($updatedUser->getCertificate()!= null && $updatedUser->getCertificate() !== $this->userService->getInstructorById($userId)->getCertification()) {
                $this->instructor->setCertification($updatedUser->getCertificate());
                $changes = true;
            }

            $this->logger->info('Changes detected: ' . ($changes ? 'Yes' : 'No'));



            if ($changes) {
                switch ($role) {
                    case 'ROLE_ADMIN':
                        $this->logger->info('Administrator: ' . json_encode($this->administrator));
                        $this->administrator->setId($userId);
                        $this->userService->updateAdministrator($this->administrator);
                        break;
                    case 'ROLE_EVENTPLANNER':
                        $this->logger->info('EventPlanner: ' . json_encode($this->eventPlanner));
                        $this->eventPlanner->setId($userId);
                        $this->userService->updateEventPlanner($this->eventPlanner);
                        break;
                    case 'ROLE_CLIENT':
                        $this->logger->info('TheClient: ' . json_encode($this->client));
                        $this->client->setId($userId);
                        $this->userService->updateClient($this->client);
                        break;
                    case 'ROLE_INSTRUCTOR':
                        $this->logger->info('Instructor: ' . json_encode($this->instructor));
                        $this->instructor->setId($userId);
                        $this->userService->updateInstructor($this->instructor);
                        break;
                }
                $this->addFlash('success', 'Profile updated successfully.');
            } else {
                $this->addFlash('info', 'No changes detected.');
            }
            return $this->redirectToRoute('user_profile');
        }

        return $this->render('User/user-account.html.twig', [
            'user' => $userDTO,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/user/account_security', name: 'account_security')]
    public function security(): Response
    {
        return $this->render('User/account-security.html.twig');
    }
}