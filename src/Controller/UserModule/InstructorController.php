<?php

namespace App\Controller\UserModule;

use App\Entity\UserModule\Instructor;
use App\Repository\UserModule\InstructorRepository;
use App\Service\UserModule\ValidationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class InstructorController extends AbstractController
{
    private InstructorRepository $instructorRepository;
    private ValidationService $validationService;

    public function __construct(InstructorRepository $instructorRepository, ValidationService $validationService)
    {
        $this->instructorRepository = $instructorRepository;
        $this->validationService = $validationService;
    }

    #[Route('/instructors', name: 'list_instructors', methods: ['GET'])]
    public function list(): Response
    {
        $instructors = $this->instructorRepository->displayUsers();
        return $this->json($instructors);
    }

    #[Route('/instructor', name: 'add_instructor', methods: ['POST'])]
    public function add(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!$this->validationService->isValidEmail($data['email'])) {
            return $this->json(['error' => 'Invalid email format.'], Response::HTTP_BAD_REQUEST);
        }

        if (!$this->validationService->isValidPassword($data['password'])) {
            return $this->json(['error' => 'Invalid password format.'], Response::HTTP_BAD_REQUEST);
        }

        $instructor = new Instructor();
        $instructor->setName($data['name']);
        $instructor->setEmail($data['email']);
        $instructor->setPassword($data['password']); // Assume password is already hashed
        $instructor->setCertification($data['certification']);
        $instructor->setApproved($data['isApproved']);

        $this->instructorRepository->addUser($instructor);

        return $this->json(['message' => 'Instructor added successfully.'], Response::HTTP_CREATED);
    }

    #[Route('/instructor/{id}', name: 'update_instructor', methods: ['PUT'])]
    public function update(int $id, Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        $instructor = $this->instructorRepository->getUser($id);

        if (!$instructor) {
            return $this->json(['error' => 'Instructor not found.'], Response::HTTP_NOT_FOUND);
        }

        if (isset($data['name'])) {
            $instructor->setName($data['name']);
        }
        if (isset($data['email']) && $this->validationService->isValidEmail($data['email'])) {
            $instructor->setEmail($data['email']);
        }
        if (isset($data['password']) && $this->validationService->isValidPassword($data['password'])) {
            $instructor->setPassword($data['password']); // Assume password is already hashed
        }
        if (isset($data['certification'])) {
            $instructor->setCertification($data['certification']);
        }
        if (isset($data['isApproved'])) {
            $instructor->setApproved($data['isApproved']);
        }

        $this->instructorRepository->updateUser($instructor);

        return $this->json(['message' => 'Instructor updated successfully.']);
    }

    #[Route('/instructor/{id}', name: 'delete_instructor', methods: ['DELETE'])]
    public function delete(int $id): Response
    {
        $instructor = $this->instructorRepository->getUser($id);

        if (!$instructor) {
            return $this->json(['error' => 'Instructor not found.'], Response::HTTP_NOT_FOUND);
        }

        $this->instructorRepository->deleteUser($id);

        return $this->json(['message' => 'Instructor deleted successfully.']);
    }

    #[Route('/test-crud-instructor', name: 'test_crud_instructor', methods: ['GET'])]
    public function testCrud(): Response
    {
        // Add Instructor
        $instructor = new Instructor();
        $instructor->setName('Test Instructor');
        $instructor->setEmail('test.instructor@example.com');
        $instructor->setPassword('TestPassword@123'); // Assume password is already hashed
        $instructor->setCertification('Test Certification');
        $instructor->setApproved(true);
        $this->instructorRepository->addUser($instructor);

        // List Instructors
        $instructors = $this->instructorRepository->displayUsers();
        foreach ($instructors as $inst) {
            echo "ID: " . $inst->getId() . ", Name: " . $inst->getName() . ", Email: " . $inst->getEmail() . "\n";
        }

        // Get Instructor by ID
        $instructorId = $instructor->getId();
        $fetchedInstructor = $this->instructorRepository->getUser($instructorId);
        if ($fetchedInstructor) {
            echo "Fetched Instructor - ID: " . $fetchedInstructor->getId() . ", Name: " . $fetchedInstructor->getName() . ", Email: " . $fetchedInstructor->getEmail() . "\n";
        }

        // Update Instructor
        $instructor->setName('Updated Test Instructor');
        $this->instructorRepository->updateUser($instructor);

        // Delete Instructor
        //$this->instructorRepository->deleteUser($instructor->getId());

        return $this->json(['message' => 'CRUD operations tested successfully.']);
    }
}