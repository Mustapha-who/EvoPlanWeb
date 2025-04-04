<?php

namespace App\Repository\UserModule;

use App\Entity\UserModule\EventPlanner;
use App\Entity\UserModule\User;
use App\Service\UserModule\PasswordEncryption;
use App\Service\UserModule\ValidationService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class EventPlannerRepository
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
        $qb->select('u.id, u.name, u.email, u.password, e.specialization, e.assignedModule')
            ->from('App\Entity\UserModule\User', 'u')
            ->innerJoin('App\Entity\UserModule\EventPlanner', 'e', 'WITH', 'u.id = e.id');

        $query = $qb->getQuery();
        $result = $query->getResult();
        $eventPlanners = [];

        foreach ($result as $row) {
            $eventPlanner = new EventPlanner();
            $eventPlanner->setId($row['id']);
            $eventPlanner->setName($row['name']);
            $eventPlanner->setEmail($row['email']);
            $eventPlanner->setPassword($row['password']);
            $eventPlanner->setSpecialization($row['specialization']);
            $eventPlanner->setAssignedModule($row['assignedModule']);
            $eventPlanners[] = $eventPlanner;
        }

        return $eventPlanners;
    }

    /**
     * @throws Exception
     */
    public function addUser(EventPlanner $eventPlanner): void
    {
        if (!$this->validationService->isValidEmail($eventPlanner->getEmail())) {
            echo "❌ Invalid email format.";
            return;
        }

        if (!$this->validationService->isValidPassword($eventPlanner->getPassword())) {
            echo "❌ Invalid password format.";
            return;
        }

        // Check if the email already exists
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $eventPlanner->getEmail()]);
        if ($existingUser) {
            echo "❌ Error: Email already exists.";
            return;
        }

        $this->entityManager->getConnection()->beginTransaction();
        try {
            $encryptedPassword = $this->passwordEncryption->hashPassword($eventPlanner->getPassword());

            // Create and persist EventPlanner entity
            $eventPlanner->setPassword($encryptedPassword);
            $this->entityManager->persist($eventPlanner);
            $this->entityManager->flush();

            $this->entityManager->getConnection()->commit();
            echo "✅ EventPlanner added successfully.";
        } catch (Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            echo "❌ Error adding EventPlanner: " . $e->getMessage();
        }
    }

    public function updateUser(EventPlanner $eventPlanner): void
    {
        $this->entityManager->getConnection()->beginTransaction();
        try {
            $user = $this->entityManager->getRepository(User::class)->find($eventPlanner->getId());
            if ($user) {
                if ($eventPlanner->getName()) {
                    $user->setName($eventPlanner->getName());
                }
                if ($eventPlanner->getEmail()) {
                    $user->setEmail($eventPlanner->getEmail());
                }
                if ($eventPlanner->getPassword() && $this->validationService->isValidPassword($eventPlanner->getPassword())) {
                    $user->setPassword($this->passwordEncryption->hashPassword($eventPlanner->getPassword()));
                }
                $this->entityManager->flush();
            }

            $this->entityManager->persist($eventPlanner);
            $this->entityManager->flush();

            $this->entityManager->getConnection()->commit();
            echo "✅ EventPlanner updated successfully.";
        } catch (Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            echo "❌ Error updating EventPlanner: " . $e->getMessage();
        }
    }

    /**
     * @throws Exception
     */
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
            echo "✅ EventPlanner deleted successfully.";
        } catch (Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            echo "❌ Error deleting EventPlanner: " . $e->getMessage();
        }
    }

    public function getUser(int $id): ?EventPlanner
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('u.id, u.name, u.email, u.password, e.specialization, e.assignedModule')
            ->from('App\Entity\UserModule\User', 'u')
            ->innerJoin('App\Entity\UserModule\EventPlanner', 'e', 'WITH', 'u.id = e.id')
            ->where('u.id = :id')
            ->setParameter('id', $id);

        $query = $qb->getQuery();
        $result = $query->getOneOrNullResult();

        if ($result) {
            $eventPlanner = new EventPlanner();
            $eventPlanner->setId($result['id']);
            $eventPlanner->setName($result['name']);
            $eventPlanner->setEmail($result['email']);
            $eventPlanner->setPassword($result['password']);
            $eventPlanner->setSpecialization($result['specialization']);
            $eventPlanner->setAssignedModule($result['assignedModule']);
            return $eventPlanner;
        }

        return null;
    }
}