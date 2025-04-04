<?php

namespace App\Controller\UserModule;

use App\Entity\UserModule\Administrator;
use App\Repository\UserModule\AdministratorRepository;
use App\Service\UserModule\ValidationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdministratorController extends AbstractController
{
    private AdministratorRepository $administratorRepository;
    private ValidationService $validationService;

    public function __construct(AdministratorRepository $administratorRepository, ValidationService $validationService)
    {
        $this->administratorRepository = $administratorRepository;
        $this->validationService = $validationService;
    }

    #[Route('/administrators', name: 'list_administrators', methods: ['GET'])]
    public function list(): Response
    {
        $administrators = $this->administratorRepository->displayUsers();
        return $this->json($administrators);
    }

    #[Route('/administrator', name: 'add_administrator', methods: ['POST'])]
    public function add(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!$this->validationService->isValidEmail($data['email'])) {
            return $this->json(['error' => 'Invalid email format.'], Response::HTTP_BAD_REQUEST);
        }

        if (!$this->validationService->isValidPassword($data['password'])) {
            return $this->json(['error' => 'Invalid password format.'], Response::HTTP_BAD_REQUEST);
        }

        $administrator = new Administrator();
        $administrator->setName($data['name']);
        $administrator->setEmail($data['email']);
        $administrator->setPassword($data['password']); // Assume password is already hashed

        $this->administratorRepository->addUser($administrator);

        return $this->json(['message' => 'Administrator added successfully.'], Response::HTTP_CREATED);
    }

    #[Route('/administrator/{id}', name: 'update_administrator', methods: ['PUT'])]
    public function update(int $id, Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        $administrator = $this->administratorRepository->getUser($id);

        if (!$administrator) {
            return $this->json(['error' => 'Administrator not found.'], Response::HTTP_NOT_FOUND);
        }

        if (isset($data['name'])) {
            $administrator->setName($data['name']);
        }
        if (isset($data['email']) && $this->validationService->isValidEmail($data['email'])) {
            $administrator->setEmail($data['email']);
        }
        if (isset($data['password']) && $this->validationService->isValidPassword($data['password'])) {
            $administrator->setPassword($data['password']); // Assume password is already hashed
        }

        $this->administratorRepository->updateUser($administrator);

        return $this->json(['message' => 'Administrator updated successfully.']);
    }

    #[Route('/administrator/{id}', name: 'delete_administrator', methods: ['DELETE'])]
    public function delete(int $id): Response
    {
        $administrator = $this->administratorRepository->getUser($id);

        if (!$administrator) {
            return $this->json(['error' => 'Administrator not found.'], Response::HTTP_NOT_FOUND);
        }

        $this->administratorRepository->deleteUser($id);

        return $this->json(['message' => 'Administrator deleted successfully.']);
    }

    #[Route('/test-crud', name: 'test_crud', methods: ['GET'])]
    public function testCrud(): Response
    {
        // Add Administrator
        $administrator = new Administrator();
        $administrator->setName('Test User');
        $administrator->setEmail('test.us2er@example.com');
        $administrator->setPassword('TestPassword@123'); // Assume password is already hashed
        $this->administratorRepository->addUser($administrator);

        // List Administrators
        $administrators = $this->administratorRepository->displayUsers();
        foreach ($administrators as $admin) {
            echo "ID: " . $admin->getId() . ", Name: " . $admin->getName() . ", Email: " . $admin->getEmail() . "\n";
        }

        // Get Administrator by ID
        $adminId = $administrator->getId();
        $fetchedAdmin = $this->administratorRepository->getUser($adminId);
        if ($fetchedAdmin) {
            echo "Fetched Admin - ID: " . $fetchedAdmin->getId() . ", Name: " . $fetchedAdmin->getName() . ", Email: " . $fetchedAdmin->getEmail() . "\n";
        }

        // Update Administrator
        $administrator->setName('Updated Test User');
        $this->administratorRepository->updateUser($administrator);

        // Delete Administrator
        //$this->administratorRepository->deleteUser($administrator->getId());

        return $this->json(['message' => 'CRUD operations tested successfully.']);
    }
}