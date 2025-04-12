<?php

namespace App\Controller\UserModule;

use App\Entity\UserModule\Client;
use App\Repository\UserModule\ClientRepository;
use App\Service\UserModule\ValidationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ClientController extends AbstractController
{
    private ClientRepository $clientRepository;
    private ValidationService $validationService;

    public function __construct(ClientRepository $clientRepository, ValidationService $validationService)
    {
        $this->clientRepository = $clientRepository;
        $this->validationService = $validationService;
    }

    #[Route('/clients', name: 'list_clients', methods: ['GET'])]
    public function list(): Response
    {
        $clients = $this->clientRepository->displayUsers();
        return $this->json($clients);
    }

    #[Route('/client', name: 'add_client', methods: ['POST'])]
    public function add(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!$this->validationService->isValidEmail($data['email'])) {
            return $this->json(['error' => 'Invalid email format.'], Response::HTTP_BAD_REQUEST);
        }

        if (!$this->validationService->isValidPassword($data['password'])) {
            return $this->json(['error' => 'Invalid password format.'], Response::HTTP_BAD_REQUEST);
        }

        $client = new Client();
        $client->setName($data['name']);
        $client->setEmail($data['email']);
        $client->setPassword($data['password']); // Assume password is already hashed
        $client->setPhoneNumber($data['phoneNumber']);

        $this->clientRepository->addUser($client);

        return $this->json(['message' => 'Client added successfully.'], Response::HTTP_CREATED);
    }

    #[Route('/client/{id}', name: 'update_client', methods: ['PUT'])]
    public function update(int $id, Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        $client = $this->clientRepository->getUser($id);

        if (!$client) {
            return $this->json(['error' => 'Client not found.'], Response::HTTP_NOT_FOUND);
        }

        if (isset($data['name'])) {
            $client->setName($data['name']);
        }
        if (isset($data['email']) && $this->validationService->isValidEmail($data['email'])) {
            $client->setEmail($data['email']);
        }
        if (isset($data['password']) && $this->validationService->isValidPassword($data['password'])) {
            $client->setPassword($data['password']); // Assume password is already hashed
        }
        if (isset($data['phoneNumber'])) {
            $client->setPhoneNumber($data['phoneNumber']);
        }

        $this->clientRepository->updateUser($client);

        return $this->json(['message' => 'Client updated successfully.']);
    }

    #[Route('/client/{id}', name: 'delete_client', methods: ['DELETE'])]
    public function delete(int $id): Response
    {
        $client = $this->clientRepository->getUser($id);

        if (!$client) {
            return $this->json(['error' => 'Client not found.'], Response::HTTP_NOT_FOUND);
        }

        $this->clientRepository->deleteUser($id);

        return $this->json(['message' => 'Client deleted successfully.']);
    }

    #[Route('/test-crud-client', name: 'test_crud_client', methods: ['GET'])]
    public function testCrud(): Response
    {
        // Add Client
        $client = new Client();
        $client->setName('Test Client');
        $client->setEmail('test.client@example.com');
        $client->setPassword('TestPassword@123'); // Assume password is already hashed
        $client->setPhoneNumber('1234567890');
        $this->clientRepository->addUser($client);

        // List Clients
        $clients = $this->clientRepository->displayUsers();
        foreach ($clients as $cl) {
            echo "ID: " . $cl->getId() . ", Name: " . $cl->getName() . ", Email: " . $cl->getEmail() . "\n";
        }

        // Get Client by ID
        $clientId = $client->getId();
        $fetchedClient = $this->clientRepository->getUser($clientId);
        if ($fetchedClient) {
            echo "Fetched Client - ID: " . $fetchedClient->getId() . ", Name: " . $fetchedClient->getName() . ", Email: " . $fetchedClient->getEmail() . "\n";
        }

        // Update Client
        $client->setName('Updated Test Client');
        $this->clientRepository->updateUser($client);

        // Delete Client
        //$this->clientRepository->deleteUser($client->getId());

        return $this->json(['message' => 'CRUD operations tested successfully.']);
    }
}
