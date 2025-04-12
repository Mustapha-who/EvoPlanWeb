<?php


namespace App\Controller\UserModule;
use App\Entity\UserModule\Administrator;
use App\Entity\UserModule\Client;
use App\Entity\UserModule\EventPlanner;
use App\Entity\UserModule\Instructor;
use App\Entity\UserModule\UserDTO;
use App\Form\UserModule\ClientUpdateFormType;
use App\Form\UserModule\EventPlannerEditFormType;
use App\Form\UserModule\InstructorEditFormType;
use App\Service\UserModule\UserService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
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
        return $this->render('User/adminDashboard.html.twig', [
            'users' => $users,
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
                throw $this->createNotFoundException('Invalid user role.');
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
                return $this->json(['success' => true, 'message' => 'User updated successfully.']);
               // return $this->redirectToRoute('admin_dashboard');
            }

            return $this->json(['success' => false, 'message' => 'No changes detected.']);
           // return $this->redirectToRoute('admin_dashboard');
        }

        return $this->render('User/_user_edit_form.html.twig', [
            'form' => $form->createView(),
            'userId' => $user->getId(),
        ]);
    }
}