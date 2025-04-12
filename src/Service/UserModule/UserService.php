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

class UserService
{
    private AdministratorRepository $administratorRepository;
    private EventPlannerRepository $eventPlannerRepository;
    private ClientRepository $clientRepository;
    private InstructorRepository $instructorRepository;
    private UserRepository $userRepository;

    public function __construct(
        AdministratorRepository $administratorRepository,
        EventPlannerRepository  $eventPlannerRepository,
        ClientRepository        $clientRepository,
        InstructorRepository    $instructorRepository, UserRepository $userRepository
    ) {
        $this->administratorRepository = $administratorRepository;
        $this->eventPlannerRepository = $eventPlannerRepository;
        $this->clientRepository = $clientRepository;
        $this->instructorRepository = $instructorRepository;
        $this->userRepository = $userRepository;
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
}