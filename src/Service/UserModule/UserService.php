<?php

namespace App\Service\UserModule;

use App\Entity\UserModule\Administrator;
use App\Entity\UserModule\Client;
use App\Entity\UserModule\EventPlanner;
use App\Entity\UserModule\Instructor;
use App\Entity\UserModule\User;
use App\Repository\UserModule\AdministratorRepository;
use App\Repository\UserModule\ClientRepository;
use App\Repository\UserModule\EventPlannerRepository;
use App\Repository\UserModule\InstructorRepository;
use App\Repository\UserModule\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class UserService
{
    private AdministratorRepository $administratorRepository;
    private EventPlannerRepository $eventPlannerRepository;
    private ClientRepository $clientRepository;
    private InstructorRepository $instructorRepository;
    private UserRepository $userRepository;
    private PasswordEncryption $passwordEncryption;
    private EntityManagerInterface $entityManager;

    public function __construct(
        AdministratorRepository $administratorRepository,
        EventPlannerRepository  $eventPlannerRepository,
        ClientRepository        $clientRepository,
        InstructorRepository    $instructorRepository,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        PasswordEncryption $passwordEncryption

    ) {
        $this->administratorRepository = $administratorRepository;
        $this->eventPlannerRepository = $eventPlannerRepository;
        $this->clientRepository = $clientRepository;
        $this->instructorRepository = $instructorRepository;
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->passwordEncryption= $passwordEncryption;
    }

    public function getUserById(int $id): ?User
    {
        return $this->userRepository->getUser($id);
    }

    public function getAllUsers(): array
    {
        // Fetch all users from the repository
        $users = $this->userRepository->findAll();

        // Format the data to include only id, email, name, and role
        $userData = [];
        foreach ($users as $user) {
            $userData[] = [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'role' => $user->getRoles(),
            ];
        }

        return $userData;
    }

    // Administrator methods
    public function createAdministrator(Administrator $administrator): void
    {
        $this->administratorRepository->addUser($administrator);
    }

    public function updateAdministrator(Administrator $administrator): void
    {
        $this->administratorRepository->updateUser($administrator);
    }

    public function deleteAdministrator(int $id): void
    {
        $this->administratorRepository->deleteUser($id);
    }

    public function getAdministratorById(int $id): ?Administrator
    {
        return $this->administratorRepository->getUser($id);
    }

    public function displayAdministrators(): array
    {
        return $this->administratorRepository->displayUsers();
    }

    // Event Planner methods
    public function createEventPlanner(EventPlanner $eventPlanner): void
    {
        $this->eventPlannerRepository->addUser($eventPlanner);
    }

    public function updateEventPlanner(EventPlanner $eventPlanner): void
    {
        $this->eventPlannerRepository->updateUser($eventPlanner);
    }

    public function deleteEventPlanner(int $id): void
    {
        $this->eventPlannerRepository->deleteUser($id);
    }

    public function getEventPlannerById(int $id): ?EventPlanner
    {
        return $this->eventPlannerRepository->getUser($id);
    }

    public function displayEventPlanners(): array
    {
        return $this->eventPlannerRepository->displayUsers();
    }

    // Client methods
    public function createClient(Client $client): void
    {
        $this->clientRepository->addUser($client);
    }

    public function updateClient(Client $client): void
    {
        $this->clientRepository->updateUser($client);
    }

    public function deleteClient(int $id): void
    {
        $this->clientRepository->deleteUser($id);
    }

    public function getClientById(int $id): ?Client
    {
        return $this->clientRepository->getUser($id);
    }

    public function displayClients(): array
    {
        return $this->clientRepository->displayUsers();
    }

    // Instructor methods
    public function createInstructor(Instructor $instructor): void
    {
        $this->instructorRepository->addUser($instructor);
    }

    public function updateInstructor(Instructor $instructor): void
    {
        $this->instructorRepository->updateUser($instructor);
    }

    public function deleteInstructor(int $id): void
    {
        $this->instructorRepository->deleteUser($id);
    }

    public function getInstructorById(int $id): ?Instructor
    {
        return $this->instructorRepository->getUser($id);
    }

    public function displayInstructors(): array
    {
        return $this->instructorRepository->displayUsers();
    }


    public function updatePasswordByEmail(string $email, string $newPassword): void
    {
        // Step 1: Find the user by email
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) {
            throw new \Exception('User not found');
        }

        // Step 2: Determine the user type
        $roles = $user->getRoles();
        $userType = null;

        if (in_array('ROLE_CLIENT', $roles)) {
            $userType = 'client';
        } elseif (in_array('ROLE_INSTRUCTOR', $roles)) {
            $userType = 'instructor';
        } elseif (in_array('ROLE_EVENTPLANNER', $roles)) {
            $userType = 'eventplanner';
        } elseif (in_array('ROLE_ADMIN', $roles)) {
            $userType = 'admin';
        }

        // Fallback: Use dtype column if roles are insufficient
        if (!$userType) {
            $userType = $this->entityManager->getConnection()->fetchOne(
                'SELECT dtype FROM user WHERE email = :email',
                ['email' => $email]
            );
        }

        if (!$userType) {
            throw new \Exception('Unable to determine user type');
        }

        // Step 3: Create a temporary object and set the new password
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
            default:
                throw new \Exception('Unknown user type');
        }
        

        $tempUser->setId($user->getId());
        $tempUser->setPassword($newPassword);

        // Step 4: Call the appropriate update service
        switch ($userType) {
            case 'client':
                $this->updateClient($tempUser);
                break;
            case 'instructor':
                $this->updateInstructor($tempUser);
                break;
            case 'eventplanner':
                $this->updateEventPlanner($tempUser);
                break;
            case 'admin':
                $this->updateAdministrator($tempUser);
                break;
        }
    }

    public function isCurrentPassword(int $id, string $password): bool
    {

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['id' => $id]);

        if (!$user) {
            throw new \Exception('User not found');
        }


        return $this->passwordEncryption->verifyPassword($password,$user->getPassword());
    }
}