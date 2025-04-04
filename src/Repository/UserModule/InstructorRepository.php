<?php

namespace App\Repository\UserModule;

use App\Entity\UserModule\Instructor;
use App\Entity\UserModule\User;
use App\Service\UserModule\PasswordEncryption;
use App\Service\UserModule\ValidationService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class InstructorRepository
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
        $qb->select('u.id, u.name, u.email, u.password, i.certification, i.isApproved')
            ->from('App\Entity\UserModule\User', 'u')
            ->innerJoin('App\Entity\UserModule\Instructor', 'i', 'WITH', 'u.id = i.id');

        $query = $qb->getQuery();
        $result = $query->getResult();
        $instructors = [];

        foreach ($result as $row) {
            $instructor = new Instructor();
            $instructor->setId($row['id']);
            $instructor->setName($row['name']);
            $instructor->setEmail($row['email']);
            $instructor->setPassword($row['password']);
            $instructor->setCertification($row['certification']);
            $instructor->setApproved($row['isApproved']);
            $instructors[] = $instructor;
        }

        return $instructors;
    }

    public function addUser(Instructor $instructor): void
    {
        if (!$this->validationService->isValidEmail($instructor->getEmail())) {
            echo "❌ Invalid email format.";
            return;
        }

        if (!$this->validationService->isValidPassword($instructor->getPassword())) {
            echo "❌ Invalid password format.";
            return;
        }

        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $instructor->getEmail()]);
        if ($existingUser) {
            echo "❌ Error: Email already exists.";
            return;
        }

        $this->entityManager->getConnection()->beginTransaction();
        try {
            $encryptedPassword = $this->passwordEncryption->hashPassword($instructor->getPassword());

            $instructor->setPassword($encryptedPassword);
            $this->entityManager->persist($instructor);
            $this->entityManager->flush();

            $this->entityManager->getConnection()->commit();
            echo "✅ Instructor added successfully.";
        } catch (Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            echo "❌ Error adding Instructor: " . $e->getMessage();
        }
    }

    public function updateUser(Instructor $instructor): void
    {
        $this->entityManager->getConnection()->beginTransaction();
        try {
            $user = $this->entityManager->getRepository(User::class)->find($instructor->getId());
            if ($user) {
                if ($instructor->getName()) {
                    $user->setName($instructor->getName());
                }
                if ($instructor->getEmail()) {
                    $user->setEmail($instructor->getEmail());
                }
                if ($instructor->getPassword() && $this->validationService->isValidPassword($instructor->getPassword())) {
                    $user->setPassword($this->passwordEncryption->hashPassword($instructor->getPassword()));
                }
                $this->entityManager->flush();
            }

            $this->entityManager->persist($instructor);
            $this->entityManager->flush();

            $this->entityManager->getConnection()->commit();
            echo "✅ Instructor updated successfully.";
        } catch (Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            echo "❌ Error updating Instructor: " . $e->getMessage();
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
            echo "✅ Instructor deleted successfully.";
        } catch (Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            echo "❌ Error deleting Instructor: " . $e->getMessage();
        }
    }

    public function getUser(int $id): ?Instructor
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('u.id, u.name, u.email, u.password, i.certification, i.isApproved')
            ->from('App\Entity\UserModule\User', 'u')
            ->innerJoin('App\Entity\UserModule\Instructor', 'i', 'WITH', 'u.id = i.id')
            ->where('u.id = :id')
            ->setParameter('id', $id);

        $query = $qb->getQuery();
        $result = $query->getOneOrNullResult();

        if ($result) {
            $instructor = new Instructor();
            $instructor->setId($result['id']);
            $instructor->setName($result['name']);
            $instructor->setEmail($result['email']);
            $instructor->setPassword($result['password']);
            $instructor->setCertification($result['certification']);
            $instructor->isApproved($result['isApproved']);
            return $instructor;
        }

        return null;
    }
}