<?php

namespace App\Controller\UserModule;

use App\Entity\UserModule\EventPlanner;
use App\Entity\UserModule\EventPlannerModule;
use App\Repository\UserModule\EventPlannerRepository;
use App\Service\UserModule\ValidationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EventPlannerController extends AbstractController
{
    private EventPlannerRepository $eventPlannerRepository;
    private ValidationService $validationService;

    public function __construct(EventPlannerRepository $eventPlannerRepository, ValidationService $validationService)
    {
        $this->eventPlannerRepository = $eventPlannerRepository;
        $this->validationService = $validationService;
    }

    #[Route('/eventplanners', name: 'list_eventplanners', methods: ['GET'])]
    public function list(): Response
    {
        $eventPlanners = $this->eventPlannerRepository->displayUsers();
        return $this->json($eventPlanners);
    }

    #[Route('/eventplanner', name: 'add_eventplanner', methods: ['POST'])]
    public function add(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!$this->validationService->isValidEmail($data['email'])) {
            return $this->json(['error' => 'Invalid email format.'], Response::HTTP_BAD_REQUEST);
        }

        if (!$this->validationService->isValidPassword($data['password'])) {
            return $this->json(['error' => 'Invalid password format.'], Response::HTTP_BAD_REQUEST);
        }

        $eventPlanner = new EventPlanner();
        $eventPlanner->setName($data['name']);
        $eventPlanner->setEmail($data['email']);
        $eventPlanner->setPassword($data['password']); // Assume password is already hashed
        $eventPlanner->setSpecialization($data['specialization']);
        $eventPlanner->setAssignedModule($data['assignedModule']);

        $this->eventPlannerRepository->addUser($eventPlanner);

        return $this->json(['message' => 'EventPlanner added successfully.'], Response::HTTP_CREATED);
    }

    #[Route('/eventplanner/{id}', name: 'update_eventplanner', methods: ['PUT'])]
    public function update(int $id, Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        $eventPlanner = $this->eventPlannerRepository->getUser($id);

        if (!$eventPlanner) {
            return $this->json(['error' => 'EventPlanner not found.'], Response::HTTP_NOT_FOUND);
        }

        if (isset($data['name'])) {
            $eventPlanner->setName($data['name']);
        }
        if (isset($data['email']) && $this->validationService->isValidEmail($data['email'])) {
            $eventPlanner->setEmail($data['email']);
        }
        if (isset($data['password']) && $this->validationService->isValidPassword($data['password'])) {
            $eventPlanner->setPassword($data['password']); // Assume password is already hashed
        }
        if (isset($data['specialization'])) {
            $eventPlanner->setSpecialization($data['specialization']);
        }
        if (isset($data['assignedModule'])) {
            $eventPlanner->setAssignedModule($data['assignedModule']);
        }

        $this->eventPlannerRepository->updateUser($eventPlanner);

        return $this->json(['message' => 'EventPlanner updated successfully.']);
    }

    #[Route('/eventplanner/{id}', name: 'delete_eventplanner', methods: ['DELETE'])]
    public function delete(int $id): Response
    {
        $eventPlanner = $this->eventPlannerRepository->getUser($id);

        if (!$eventPlanner) {
            return $this->json(['error' => 'EventPlanner not found.'], Response::HTTP_NOT_FOUND);
        }

        $this->eventPlannerRepository->deleteUser($id);

        return $this->json(['message' => 'EventPlanner deleted successfully.']);
    }

    #[Route('/test-crud-eventplanner', name: 'test_crud_eventplanner', methods: ['GET'])]
    public function testCrud(): Response
    {
        // Add EventPlanner
        $eventPlanner = new EventPlanner();
        $eventPlanner->setName('Test EventPlanner');
        $eventPlanner->setEmail('test.eventplanner@example.com');
        $eventPlanner->setPassword('TestPassword@123'); // Assume password is already hashed
        $eventPlanner->setSpecialization('Test Specialization');
        $eventPlanner->setAssignedModule(EventPlannerModule::SCHEDULE);
        $this->eventPlannerRepository->addUser($eventPlanner);

        // List EventPlanners
        $eventPlanners = $this->eventPlannerRepository->displayUsers();
        foreach ($eventPlanners as $planner) {
            echo "ID: " . $planner->getId() . ", Name: " . $planner->getName() . ", Email: " . $planner->getEmail() . "\n";
        }

        // Get EventPlanner by ID
        $plannerId = $eventPlanner->getId();
        $fetchedPlanner = $this->eventPlannerRepository->getUser($plannerId);
        if ($fetchedPlanner) {
            echo "Fetched EventPlanner - ID: " . $fetchedPlanner->getId() . ", Name: " . $fetchedPlanner->getName() . ", Email: " . $fetchedPlanner->getEmail() . "\n";
        }

         //Update EventPlanner
        $eventPlanner->setName('Updated Test EventPlanner');
        $this->eventPlannerRepository->updateUser($eventPlanner);

        // Delete EventPlanner
        //$this->eventPlannerRepository->deleteUser(42);

        return $this->json(['message' => 'CRUD operations tested successfully.']);
    }
}
