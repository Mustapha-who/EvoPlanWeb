<?php

namespace App\Repository\UserModule;

use App\Entity\UserModule\Client;
use App\Entity\UserModule\User;
use App\Service\UserModule\PasswordEncryption;
use App\Service\UserModule\ValidationService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Exception;

class ClientRepository extends ServiceEntityRepository
{
    private EntityManagerInterface $entityManager;
    private ValidationService $validationService;
    private PasswordEncryption $passwordEncryption;

    public function __construct(
        ManagerRegistry $registry,
        ValidationService $validationService,
        PasswordEncryption $passwordEncryption
    ) {
        parent::__construct($registry, Client::class);
        $this->validationService = $validationService;
        $this->passwordEncryption = $passwordEncryption;
    }

    public function displayUsers(): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('u.id, u.name, u.email, u.password, c.phoneNumber')
            ->from('App\Entity\UserModule\User', 'u')
            ->innerJoin('App\Entity\UserModule\Client', 'c', 'WITH', 'u.id = c.id');

        $query = $qb->getQuery();
        $result = $query->getResult();
        $clients = [];

        foreach ($result as $row) {
            $client = new Client();
            $client->setId($row['id']);
            $client->setName($row['name']);
            $client->setEmail($row['email']);
            $client->setPassword($row['password']);
            $client->setPhoneNumber($row['phoneNumber']);
            $clients[] = $client;
        }

        return $clients;
    }

    public function addUser(Client $client): void
    {
        if (!$this->validationService->isValidEmail($client->getEmail())) {
            echo "❌ Invalid email format.";
            return;
        }

        if (!$this->validationService->isValidPassword($client->getPassword())) {
            echo "❌ Invalid password format.";
            return;
        }

        $existingUser = $this->getEntityManager()->getRepository(User::class)->findOneBy(['email' => $client->getEmail()]);
        if ($existingUser) {
            echo "❌ Error: Email already exists.";
            return;
        }

        $this->getEntityManager()->getConnection()->beginTransaction();
        try {
            $encryptedPassword = $this->passwordEncryption->hashPassword($client->getPassword());

            $client->setPassword($encryptedPassword);
            $this->getEntityManager()->persist($client);
            $this->getEntityManager()->flush();

            $this->getEntityManager()->getConnection()->commit();
            echo "✅ Client added successfully.";
        } catch (Exception $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            echo "❌ Error adding Client: " . $e->getMessage();
        }
    }

    public function updateUser(Client $client): void
    {
        // Get the managed User entity
        $user = $this->getEntityManager()->getRepository(User::class)->find($client->getId());

        // Get the managed Client entity (important!)
        $managedClient = $this->getEntityManager()->getRepository(Client::class)->find($client->getId());

        if (!$user || !$managedClient) {
            echo "❌ Error: User not found";
            return;
        }

        try {
            // Update common User fields
            if ($client->getName()) {
                $user->setName($client->getName());
                $managedClient->setName($client->getName());
            }

            if ($client->getEmail()) {
                $user->setEmail($client->getEmail());
                $managedClient->setEmail($client->getEmail());
            }

            if ($client->getPassword() && $this->validationService->isValidPassword($client->getPassword())) {
                $hashedPassword = $this->passwordEncryption->hashPassword($client->getPassword());
                $user->setPassword($hashedPassword);
                $managedClient->setPassword($hashedPassword);
            }

            // Update Client-specific fields
            if ($client->getPhoneNumber() !== null) {
                $managedClient->setPhoneNumber($client->getPhoneNumber());
            }

            // Single flush operation
            $this->getEntityManager()->flush();
            echo "✅ Client updated successfully.";
        } catch (Exception $e) {
            echo "❌ Error updating Client: " . $e->getMessage();
        }
    }

    public function deleteUser(int $id): void
    {
        $this->getEntityManager()->getConnection()->beginTransaction();
        try {
            $user = $this->getEntityManager()->getRepository(User::class)->findOneBy(['id' => $id]);
            if ($user) {
                $this->getEntityManager()->remove($user);
                $this->getEntityManager()->flush();
            }

            $this->getEntityManager()->getConnection()->commit();
            echo "✅ Client deleted successfully.";
        } catch (Exception $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            echo "❌ Error deleting Client: " . $e->getMessage();
        }
    }

    public function getUser(int $id): ?Client
    {
        // Return the actual managed entity, don't create new one
        return $this->getEntityManager()->getRepository(Client::class)->find($id);
    }
}