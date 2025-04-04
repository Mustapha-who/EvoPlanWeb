<?php

namespace App\Repository\UserModule;

use App\Entity\UserModule\Client;
use App\Entity\UserModule\User;
use App\Service\UserModule\PasswordEncryption;
use App\Service\UserModule\ValidationService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class ClientRepository
{
    private EntityManagerInterface $entityManager;
    private ValidationService $validationService;
    private PasswordEncryption $passwordEncryption;

    public function __construct(EntityManagerInterface $entityManager, ValidationService $validationService, PasswordEncryption $passwordEncryption)
    {
        $this->entityManager = $entityManager;
        $this->validationService = $validationService;
        $this->passwordEncryption = $passwordEncryption;
    }

    public function displayUsers(): array
    {
        $qb = $this->entityManager->createQueryBuilder();
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

        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $client->getEmail()]);
        if ($existingUser) {
            echo "❌ Error: Email already exists.";
            return;
        }

        $this->entityManager->getConnection()->beginTransaction();
        try {
            $encryptedPassword = $this->passwordEncryption->hashPassword($client->getPassword());

            $client->setPassword($encryptedPassword);
            $this->entityManager->persist($client);
            $this->entityManager->flush();

            $this->entityManager->getConnection()->commit();
            echo "✅ Client added successfully.";
        } catch (Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            echo "❌ Error adding Client: " . $e->getMessage();
        }
    }

    public function updateUser(Client $client): void
    {
        $this->entityManager->getConnection()->beginTransaction();
        try {
            $user = $this->entityManager->getRepository(User::class)->find($client->getId());
            if ($user) {
                if ($client->getName()) {
                    $user->setName($client->getName());
                }
                if ($client->getEmail()) {
                    $user->setEmail($client->getEmail());
                }
                if ($client->getPassword() && $this->validationService->isValidPassword($client->getPassword())) {
                    $user->setPassword($this->passwordEncryption->hashPassword($client->getPassword()));
                }
                $this->entityManager->flush();
            }

            $this->entityManager->persist($client);
            $this->entityManager->flush();

            $this->entityManager->getConnection()->commit();
            echo "✅ Client updated successfully.";
        } catch (Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            echo "❌ Error updating Client: " . $e->getMessage();
        }
    }

    public function deleteUser(int $id): void
    {
        $this->entityManager->getConnection()->beginTransaction();
        try {
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['id' => $id]);
            if ($user) {
                $this->entityManager->remove($user);
                $this->entityManager->flush();
            }

            $this->entityManager->getConnection()->commit();
            echo "✅ Client deleted successfully.";
        } catch (Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            echo "❌ Error deleting Client: " . $e->getMessage();
        }
    }

    public function getUser(int $id): ?Client
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('u.id, u.name, u.email, u.password, c.phoneNumber')
            ->from('App\Entity\UserModule\User', 'u')
            ->innerJoin('App\Entity\UserModule\Client', 'c', 'WITH', 'u.id = c.id')
            ->where('u.id = :id')
            ->setParameter('id', $id);

        $query = $qb->getQuery();
        $result = $query->getOneOrNullResult();

        if ($result) {
            $client = new Client();
            $client->setId($result['id']);
            $client->setName($result['name']);
            $client->setEmail($result['email']);
            $client->setPassword($result['password']);
            $client->setPhoneNumber($result['phoneNumber']);
            return $client;
        }

        return null;
    }
}