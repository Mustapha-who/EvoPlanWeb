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
use App\Form\UserModule\UpdatePasswordFormType;
use App\Service\UserModule\UserEmailService;
use App\Service\UserModule\UserService;
use App\Service\UserModule\ValidationService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\JsonResponse;
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
    private ValidationService $validationService;
    private UserEmailService $userEmailService;
    public function __construct(UserService $userService, LoggerInterface $logger, ValidationService $validationService, UserEmailService $userEmailService)
    {
        $this->userEmailService = $userEmailService;
        $this->validationService = $validationService;
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
    public function security(Request $request): Response
    {
        $form = $this->createForm(UpdatePasswordFormType::class, null, [
            'user_id' => $this->getUser()->getId()
        ]);

        $form->handleRequest($request);

        // Handle regular form submission (validation only)
        if ($form->isSubmitted() && $form->isValid()) {
            // For non-AJAX submissions, redirect back
            return $this->redirectToRoute('account_security');
        }

        return $this->render('user/account-security.html.twig', [
            'form' => $form->createView()
        ]);
    }
    #[Route('/user/validate_password_form', name: 'validate_password_form', methods: ['POST'])]
    public function validatePasswordForm(Request $request): JsonResponse
    {
        $form = $this->createForm(UpdatePasswordFormType::class, null, [
            'user_id' => $this->getUser()->getId()
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return new JsonResponse(['valid' => true]);
        }

        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $errors[$error->getOrigin()->getName()] = $error->getMessage();
        }

        return new JsonResponse([
            'valid' => false,
            'errors' => $errors
        ]);
    }

    #[Route('/user/request_password_update', name: 'request_password_update', methods: ['POST'])]
    public function requestPasswordUpdate(Request $request, LoggerInterface $logger): JsonResponse
    {
        $logger->info('Handling password update request.');

        $user = $this->getUser();
        $form = $this->createForm(UpdatePasswordFormType::class, null, [
            'user_id' => $this->getUser()->getId()
        ]);

        $form->handleRequest($request);

        // This should only be called after client-side validation
        if (!$form->isValid()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Form validation failed unexpectedly'
            ], 400);
        }


        $newPassword = $form->get('new_password')->getData();

        // Generate and store verification code AND password
        $verificationCode = random_int(100000, 999999);
        $cache = new FilesystemAdapter();
        $cacheKey = 'password_reset_' . $user->getId();

        $cacheItem = $cache->getItem($cacheKey);
        $cacheItem->set([
            'code' => $verificationCode,
            'password' => $newPassword // Store the raw password temporarily
        ]);
        $cacheItem->expiresAfter(300); // 5 minutes
        $cache->save($cacheItem);

        // Send the verification code via email
        try {
            $this->userEmailService->sendEmail(
                $user->getEmail(),
                'Password Reset Verification Code',
                "Your verification code is: $verificationCode"
            );
            $logger->info('Verification code sent to email.', ['email' => $user->getEmail()]);
            return new JsonResponse(['success' => true, 'message' => 'Verification code sent.']);
        } catch (\Exception $e) {
            $logger->error('Failed to send verification code.', ['error' => $e->getMessage()]);
            return new JsonResponse(['success' => false, 'message' => 'Failed to send verification code.'], 500);
        }
    }
    #[Route('/user/verify_password_update', name: 'verify_password_update', methods: ['POST'])]
    public function verifyPasswordUpdate(Request $request, LoggerInterface $logger): JsonResponse
    {
        $logger->info('Verifying password update request.');

        $user = $this->getUser();
        if (!$user) {
            $logger->warning('User not authenticated.');
            return new JsonResponse(['success' => false, 'message' => 'User not authenticated.'], 401);
        }

        // Handle verification code from request
        if (str_starts_with($request->headers->get('Content-Type'), 'application/json')) {
            $data = json_decode($request->getContent(), true);
            $verificationCode = $data['verification_code'] ?? null;
        } else {
            $verificationCode = $request->request->get('verification_code');
        }

        $logger->info('User authenticated.', ['user_id' => $user->getId()]);

        if (!$verificationCode) {
            $logger->warning('Verification code missing.');
            return new JsonResponse(['success' => false, 'message' => 'Verification code is required.'], 400);
        }

        // Retrieve both code and password from cache
        $cache = new FilesystemAdapter();
        $cacheKey = 'password_reset_' . $user->getId();
        $storedData = $cache->getItem($cacheKey)->get();

        if (!$storedData || !isset($storedData['code']) || !isset($storedData['password'])) {
            $logger->warning('Verification data has expired or is missing.', ['cache_key' => $cacheKey]);
            return new JsonResponse(['success' => false, 'message' => 'Session has expired. Please start the password reset process again.'], 400);
        }

        if ((string)$storedData['code'] !== (string)$verificationCode) {
            $logger->warning('Invalid verification code.', [
                'submitted_code' => $verificationCode,
                'stored_code' => $storedData['code'],
            ]);
            return new JsonResponse(['success' => false, 'message' => 'Invalid verification code.'], 400);
        }

        $newPassword = $storedData['password'];
        $logger->info('Verification code is valid. Proceeding with password update.', [
            'user_id' => $user->getId(),
            'has_password' => !empty($newPassword)
        ]);

        $logger->info('Verification code is valid. Proceeding with password update.');

        // Determine user role
        $roles = $user->getRoles();
        $logger->info('User roles retrieved.', ['roles' => $roles]);

        $userType = null;
        if (in_array('ROLE_CLIENT', $roles)) {
            $userType = 'client';
        } elseif (in_array('ROLE_INSTRUCTOR', $roles)) {
            $userType = 'instructor';
        } elseif (in_array('ROLE_EVENTPLANNER', $roles)) {
            $userType = 'eventplanner';
        } elseif (in_array('ROLE_ADMIN', $roles)) {
            $userType = 'admin';
        } else {
            $logger->error('Unable to determine user role.');
            return new JsonResponse(['success' => false, 'message' => 'Unable to determine user role.'], 400);
        }

        $logger->info('User role determined.', ['user_type' => $userType]);

        // Create a temporary user object and set the new password
        $tempUser = null;
        switch ($userType) {
            case 'client':
                $tempUser = new Client();
                break;
            case 'instructor':
                $tempUser = new Instructor();
                break;
            case 'eventplanner':
                $tempUser = new EventPlanner();
                break;
            case 'admin':
                $tempUser = new Administrator();
                break;
        }

        $tempUser->setId($user->getId());
        $tempUser->setPassword($newPassword);

        // Call the appropriate update method
        try {
            switch ($userType) {
                case 'client':
                    $this->userService->updateClient($tempUser);
                    break;
                case 'instructor':
                    $this->userService->updateInstructor($tempUser);
                    break;
                case 'eventplanner':
                    $this->userService->updateEventPlanner($tempUser);
                    break;
                case 'admin':
                    $this->userService->updateAdministrator($tempUser);
                    break;
            }

            $logger->info('Password updated successfully.', ['user_id' => $user->getId()]);
            return new JsonResponse(['success' => true, 'message' => 'Password updated successfully.']);
        } catch (\Exception $e) {
            $logger->error('Failed to update password.', ['error' => $e->getMessage()]);
            return new JsonResponse(['success' => false, 'message' => 'Failed to update password.'], 500);
        }
    }
    /**
     * @throws \Exception
     */

//    #[Route('/user/update_password', name: 'update_password', methods: ['POST'])]
//    public function accountSecurity(Request $request, LoggerInterface $logger): JsonResponse
//    {
//        $logger->info('Handling password update request.');
//
//        $user = $this->getUser();
//        if (!$user) {
//            $logger->warning('User not authenticated.');
//            return new JsonResponse(['success' => false, 'message' => 'User not authenticated.'], 401);
//        }
//
//        $logger->info('User authenticated.', ['user_id' => $user->getId()]);
//
//        $form = $this->createForm(UpdatePasswordFormType::class, null, [
//            'user_id' => $user->getId(),
//        ]);
//        $form->handleRequest($request);
//
//        if (!$form->isSubmitted()) {
//            $logger->warning('Form not submitted.');
//            return new JsonResponse(['success' => false, 'message' => 'Form not submitted.'], 400);
//        }
//
//        if (!$form->isValid()) {
//            $logger->warning('Form validation failed.', [
//                'errors' => (string) $form->getErrors(true, false),
//            ]);
//            $errors = [];
//            foreach ($form->getErrors(true) as $error) {
//                $errors[] = $error->getMessage();
//            }
//            return new JsonResponse(['success' => false, 'message' => 'Validation failed.', 'errors' => $errors], 400);
//        }
//
//        $logger->info('Form is valid. Proceeding with password update.');
//
//        $data = $form->getData();
//        $newPassword = $data['new_password'];
//
//        // Determine user role
//        $roles = $user->getRoles();
//        $logger->info('User roles retrieved.', ['roles' => $roles]);
//
//        $userType = null;
//        if (in_array('ROLE_CLIENT', $roles)) {
//            $userType = 'client';
//        } elseif (in_array('ROLE_INSTRUCTOR', $roles)) {
//            $userType = 'instructor';
//        } elseif (in_array('ROLE_EVENTPLANNER', $roles)) {
//            $userType = 'eventplanner';
//        } elseif (in_array('ROLE_ADMIN', $roles)) {
//            $userType = 'admin';
//        } else {
//            $logger->error('Unable to determine user role.');
//            return new JsonResponse(['success' => false, 'message' => 'Unable to determine user role.'], 400);
//        }
//
//        $logger->info('User role determined.', ['user_type' => $userType]);
//
//        // Create a temporary user object and set the new password
//        $tempUser = null;
//        switch ($userType) {
//            case 'client':
//                $tempUser = new Client();
//                break;
//            case 'instructor':
//                $tempUser = new Instructor();
//                break;
//            case 'eventplanner':
//                $tempUser = new EventPlanner();
//                break;
//            case 'admin':
//                $tempUser = new Administrator();
//                break;
//        }
//
//        $tempUser->setId($user->getId());
//        $tempUser->setPassword($newPassword);
//        $email = $user->getEmail();
//        $logger->info('Temporary user object created.', ['user_id' => $tempUser->getId()]);
//
//        // Generate a verification code
//        $verificationCode = random_int(100000, 999999);
//        $logger->info('Verification code generated.', ['verification_code' => $verificationCode]);
//
//        // Send the verification code via email
//        try {
//            $this->userEmailService->sendEmail(
//                $email,
//                'Password Reset Verification Code',
//                "Your verification code is: $verificationCode"
//            );
//            $logger->info('Verification code sent to email.', ['email' => $user->getEmail()]);
//        } catch (\Exception $e) {
//            $logger->error('Failed to send verification code.', ['error' => $e->getMessage()]);
//            return new JsonResponse(['success' => false, 'message' => 'Failed to send verification code.'], 500);
//        }
//
//        // Call the appropriate update method
//        try {
//            switch ($userType) {
//                case 'client':
//                    $this->userService->updateClient($tempUser);
//                    break;
//                case 'instructor':
//                    $this->userService->updateInstructor($tempUser);
//                    break;
//                case 'eventplanner':
//                    $this->userService->updateEventPlanner($tempUser);
//                    break;
//                case 'admin':
//                    $this->userService->updateAdministrator($tempUser);
//                    break;
//            }
//
//            $logger->info('Password updated successfully.', ['user_id' => $user->getId()]);
//
//            return new JsonResponse([
//                'success' => true,
//                'message' => 'Password updated successfully.',
//                'verificationCode' => (string) $verificationCode, // For testing; remove in production
//            ]);
//        } catch (\Exception $e) {
//            $logger->error('Failed to update password.', ['error' => $e->getMessage()]);
//            return new JsonResponse(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()], 500);
//        }
//    }
}