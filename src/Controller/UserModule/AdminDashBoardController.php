<?php


namespace App\Controller\UserModule;
use App\Entity\UserModule\Administrator;
use App\Entity\UserModule\Client;
use App\Entity\UserModule\EventPlanner;
use App\Entity\UserModule\Instructor;
use App\Entity\UserModule\UserDTO;
use App\Form\UserModule\adminAddClientFormType;
use App\Form\UserModule\adminAddEventPlannerFormType;
use App\Form\UserModule\adminAddInstructorFormType;
use App\Form\UserModule\ClientRegistrationFormType;
use App\Form\UserModule\ClientUpdateFormType;
use App\Form\UserModule\EventPlannerEditFormType;
use App\Form\UserModule\InstructorEditFormType;
use App\Form\UserModule\InstructorRegistrationFormType;
use App\Service\UserModule\UserService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminDashBoardController extends AbstractController
{

    private UserService $userService;
    private LoggerInterface $logger;
    private EventPlanner $eventPlanner;
    private Instructor $instructor;
    private Client $client;
    public function __construct(UserService $userService,LoggerInterface $logger)
    {
        $this->userService = $userService;
        $this->administrator = new Administrator();
        $this->eventPlanner = new EventPlanner();
        $this->instructor = new Instructor();
        $this->client = new Client();
        $this->logger = $logger;
    }

    #[Route('/admindashboard', name: 'admin_dashboard')]
    public function index(): Response
    {
        $users = $this->userService->getAllUsers();

        // Filter out users with 'ROLE_ADMIN'
        $filteredUsers = array_filter($users, function ($user) {
            // Assuming the user is an array and contains a 'roles' key
            return !in_array('ROLE_ADMIN', $user['role']);
        });

        return $this->render('User/adminDashboard.html.twig', [
            'users' => $filteredUsers,
        ]);
    }

    #[Route('/admin/edit-user/{id}', name: 'admin_edit_user', methods: ['GET', 'POST'])]
    public function editUser(int $id, Request $request): Response
    {
        $user = $this->userService->getUserById($id);

        if (!$user) {
            throw $this->createNotFoundException('User not found.');
        }

        $role = $user->getRoles()[0];
        $this->logger->debug('User roles found', ['user_id' => $id, 'roles' => $role]);
        $userId = $user->getId();
        $userDTO = new UserDTO();
        $form = null;

        switch ($role) {
            case 'ROLE_CLIENT':
                $this->client->setId($userId);
                $userData = $this->userService->getClientById($userId);
                $userDTO->setName($userData->getName());
                $userDTO->setEmail($userData->getEmail());
                $userDTO->setPhoneNumber($userData->getPhoneNumber());
                $form = $this->createForm(ClientUpdateFormType::class, $userDTO);
                break;
            case 'ROLE_EVENTPLANNER':
                $this->eventPlanner->setId($user->getId());
                $userData = $this->userService->getEventPlannerById($userId);
                $userDTO->setName($userData->getName());
                $userDTO->setEmail($userData->getEmail());
                $userDTO->setSpecialization($userData->getSpecialization());
                $userDTO->setAssignedModule($userData->getAssignedModule());
                $form = $this->createForm(EventPlannerEditFormType::class, $userDTO);
                break;
            case 'ROLE_INSTRUCTOR':
                $this->instructor->setId($user->getId());
                $userData = $this->userService->getInstructorById($userId);
                $userDTO->setName($userData->getName());
                $userDTO->setEmail($userData->getEmail());
                $userDTO->setCertificate($userData->getCertification());
                $userDTO->setIsApproved($userData->isApproved());
                $form = $this->createForm(InstructorEditFormType::class, $userDTO);
                break;
            default:
                $this->addFlash('danger', 'Invalid user role.');
                return $this->redirectToRoute('admin_dashboard');
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $updatedUser = $form->getData();
            $changes = false;

            if ($updatedUser->getName() !== $this->userService->getUserById($id)->getName()) {
                $this->client->setName($updatedUser->getName());
                $this->eventPlanner->setName($updatedUser->getName());
                $this->instructor->setName($updatedUser->getName());
                $changes = true;
            }

            if ($updatedUser->getEmail() !== $this->userService->getUserById($id)->getEmail()) {
                $this->client->setEmail($updatedUser->getEmail());
                $this->eventPlanner->setEmail($updatedUser->getEmail());
                $this->instructor->setEmail($updatedUser->getEmail());
                $changes = true;
            }

            if ($role === 'ROLE_CLIENT' && $updatedUser->getPhoneNumber() !== $this->userService->getClientById($id)->getPhoneNumber()) {
                $this->client->setPhoneNumber($updatedUser->getPhoneNumber());
                $changes = true;
            }

            if ($role === 'ROLE_EVENTPLANNER' && $updatedUser->getSpecialization() !== $this->userService->getEventPlannerById($id)->getSpecialization()) {
                $this->eventPlanner->setSpecialization($updatedUser->getSpecialization());
                $changes = true;
            }

            if ($role === 'ROLE_EVENTPLANNER' && $updatedUser->getAssignedModule() !== $this->userService->getEventPlannerById($id)->getAssignedModule()) {
                $this->eventPlanner->setAssignedModule($updatedUser->getAssignedModule());
                $changes = true;
            }

            if ($role === 'ROLE_INSTRUCTOR' && $updatedUser->getCertificate() !== $this->userService->getInstructorById($id)->getCertification()) {
                $this->instructor->setCertification($updatedUser->getCertification());
                $changes = true;
            }


            if ($role === 'ROLE_INSTRUCTOR' && $updatedUser->getIsApproved() !== $this->userService->getInstructorById($id)->isApproved()) {
                $this->instructor->setApproved($updatedUser->getIsApproved());
                $changes = true;
            }

            if ($changes) {
                switch ($role) {
                    case 'ROLE_CLIENT':
                        $this->userService->updateClient($this->client);
                        break;
                    case 'ROLE_EVENTPLANNER':
                        $this->userService->updateEventPlanner($this->eventPlanner);
                        break;
                    case 'ROLE_INSTRUCTOR':
                        $this->userService->updateInstructor($this->instructor);
                        break;
                }
                $this->addFlash('success', 'User updated successfully.');
               // return $this->redirectToRoute('admin_dashboard');
            }else {
                $this->addFlash('info', 'No changes detected.');
            }

            return $this->redirectToRoute('admin_dashboard');
        }

        return $this->render('User/_user_edit_form.html.twig', [
            'form' => $form->createView(),
            'userId' => $user->getId(),
        ]);
    }

    #[Route('/admin/add-user', name: 'admin_add_user')]
    public function addUser(Request $request): Response
    {
        $this->logger->info('Accessed addUser route.');

        $addClientForm = $this->createForm(adminAddClientFormType::class);
        $addInstructorForm = $this->createForm(adminAddInstructorFormType::class);
        $addEventPlannerForm = $this->createForm(adminAddEventPlannerFormType::class);
        $accountType = $request->query->get('type', 'client');

        $this->logger->debug('Forms initialized.', ['accountType' => $accountType]);

        $addClientForm->handleRequest($request);
        $addInstructorForm->handleRequest($request);
        $addEventPlannerForm->handleRequest($request);

        if ($addClientForm->isSubmitted() && $addClientForm->isValid()) {
            $this->logger->info('Client form submitted and is valid.');
            $data = $addClientForm->getData();
            $this->logger->debug('Client form data.', $data);
            $client = new Client();
            $client->setName($data['firstName'] . ' ' . $data['lastName']);
            $client->setEmail($data['email']);
            $client->setPhoneNumber($data['phoneNumber']);
            $client->setPassword($data['password']);
            $this->userService->createClient($client);
            $this->logger->info('Client created successfully.');
             return $this->redirectToRoute('admin_dashboard');
        }

        if ($addInstructorForm->isSubmitted() && $addInstructorForm->isValid()) {
            $this->logger->info('Instructor form submitted and is valid.');
            $data = $addInstructorForm->getData();
            $this->logger->debug('Instructor form data.', $data);
            $instructor = new Instructor();
            $instructor->setName($data['firstName'] . ' ' . $data['lastName']);
            $instructor->setEmail($data['email']);
            $instructor->setCertification($data['certificate']);
            $instructor->setPassword($data['password']);
            $this->userService->createInstructor($instructor);
            $this->logger->info('Instructor created successfully.');
            return $this->redirectToRoute('admin_dashboard');
        }

        if ($addEventPlannerForm->isSubmitted() && $addEventPlannerForm->isValid()) {
            $this->logger->info('Event Planner form submitted and is valid.');
            $data = $addEventPlannerForm->getData();
            $this->logger->debug('Event Planner form data.', $data);
            $eventPlanner = new EventPlanner();
            $eventPlanner->setName($data['firstName'] . ' ' . $data['lastName']);
            $eventPlanner->setEmail($data['email']);
            $eventPlanner->setSpecialization($data['specialization']);
            $eventPlanner->setAssignedModule($data['assignedModule']);
            $eventPlanner->setPassword($data['password']);
            $this->userService->createEventPlanner($eventPlanner);
            $this->logger->info('Event Planner created successfully.');
             return $this->redirectToRoute('admin_dashboard');
        }


        $this->logger->info('Rendering add user form.');
        return $this->render('user/add_user.html.twig', [
            'addClientForm' => $addClientForm->createView(),
            'addInstructorForm' => $addInstructorForm->createView(),
            'addEventPlannerForm' => $addEventPlannerForm->createView(),
            'accountType' => $accountType,
        ]);
    }

    #[Route('/admin/delete-user/{id}', name: 'admin_delete_user', methods: ['POST'])]
    public function deleteUser(int $id): RedirectResponse
    {
        $user = $this->userService->getUserById($id);

        if (!$user) {
            $this->addFlash('danger', 'User not found.');
            return $this->redirectToRoute('admin_dashboard');
        }

        $role = $user->getRoles()[0];
        $this->logger->info('Deleting user', ['user_id' => $id, 'role' => $role]);

        try {
            switch ($role) {
                case 'ROLE_CLIENT':
                    $this->userService->deleteClient($id);
                    break;
                case 'ROLE_EVENTPLANNER':
                    $this->userService->deleteEventPlanner($id);
                    break;
                case 'ROLE_INSTRUCTOR':
                    $this->userService->deleteInstructor($id);
                    break;
                default:
                    $this->addFlash('danger', 'Invalid user role.');
                    return $this->redirectToRoute('admin_dashboard');
            }

            $this->addFlash('success', 'User deleted successfully.');
        } catch (\Exception $e) {
            $this->logger->error('Error deleting user', ['error' => $e->getMessage()]);
            $this->addFlash('danger', 'An error occurred while deleting the user.');
        }

        return $this->redirectToRoute('admin_dashboard');
    }
}