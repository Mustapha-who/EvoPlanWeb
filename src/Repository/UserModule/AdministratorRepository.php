<?php

namespace App\Repository\UserModule;

use App\Entity\UserModule\Administrator;
use App\Entity\UserModule\User;
use App\Service\UserModule\PasswordEncryption;
use App\Service\UserModule\ValidationService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Exception;

class AdministratorRepository
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
        $qb->select('u.id, u.name, u.email, u.password')
            ->from('App\Entity\UserModule\User', 'u')
            ->innerJoin('App\Entity\UserModule\Administrator', 'a', 'WITH', 'u.id = a.id');

        $query = $qb->getQuery();
        $result = $query->getResult();
        $administrators = [];

        foreach ($result as $row) {
            $admin = new Administrator();
            $admin->setId($row['id']);
            $admin->setName($row['name']);
            $admin->setEmail($row['email']);
            $admin->setPassword($row['password']);
            $administrators[] = $admin;
        }

        return $administrators;
    }

    /**
     * @throws Exception
     */
    public function addUser(Administrator $administrator): void
    {
        if (!$this->validationService->isValidEmail($administrator->getEmail())) {
            echo "❌ Invalid email format.";
            return;
        }

        if (!$this->validationService->isValidPassword($administrator->getPassword())) {
            echo "❌ Invalid password format.";
            return;
        }

        // Check if the email already exists
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $administrator->getEmail()]);
        if ($existingUser) {
            echo "❌ Error: Email already exists.";
            return;
        }

        $this->entityManager->getConnection()->beginTransaction();
        try {
            $encryptedPassword = $this->passwordEncryption->hashPassword($administrator->getPassword());

            // Create and persist Administrator entity
            $administrator->setPassword($encryptedPassword);
            $this->entityManager->persist($administrator);
            $this->entityManager->flush();

            $this->entityManager->getConnection()->commit();
            echo "✅ Administrator added successfully.";
        } catch (Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            echo "❌ Error adding Administrator: " . $e->getMessage();
        }
    }



    public function updateUser(Administrator $administrator): void
    {
        $this->entityManager->getConnection()->beginTransaction();
        try {
            $user = $this->entityManager->getRepository(User::class)->find($administrator->getId());
            if ($user) {
                if ($administrator->getName()) {
                    $user->setName($administrator->getName());
                }
                if ($administrator->getEmail()) {
                    $user->setEmail($administrator->getEmail());
                }
                if ($administrator->getPassword() && $this->validationService->isValidPassword($administrator->getPassword())) {
                    $user->setPassword($this->passwordEncryption->hashPassword($administrator->getPassword()));
                }
                $this->entityManager->flush();
            }

            $this->entityManager->persist($administrator);
            $this->entityManager->flush();

            $this->entityManager->getConnection()->commit();
            echo "✅ Administrator updated successfully.";
        } catch (Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            echo "❌ Error updating Administrator: " . $e->getMessage();
        }
    }

    /**
     * @throws Exception
     */
    public function deleteUser(int $id): void
    {
        $this->entityManager->getConnection()->beginTransaction();
        try {
//            $administrator = $this->entityManager->getRepository(Administrator::class)->find($id);
//            if ($administrator) {
//                $this->entityManager->remove($administrator);
//                $this->entityManager->flush();
//            }

            $user = $this->entityManager->getRepository(User::class)->findOneBy(['id' => $id]);
            if ($user) {

                $this->entityManager->remove($user);
                $this->entityManager->flush();
            }

            $this->entityManager->getConnection()->commit();
            echo "✅ Administrator deleted successfully.";
        } catch (Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            echo "❌ Error deleting Administrator: " . $e->getMessage();
        }
    }

    public function getUser(int $id): ?Administrator
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('u.id, u.name, u.email, u.password')
            ->from('App\Entity\UserModule\User', 'u')
            ->innerJoin('App\Entity\UserModule\Administrator', 'a', 'WITH', 'u.id = a.id')
            ->where('u.id = :id')
            ->setParameter('id', $id);

        $query = $qb->getQuery();
        $result = $query->getOneOrNullResult();

        if ($result) {
            $administrator = new Administrator();
            $administrator->setId($result['id']);
            $administrator->setName($result['name']);
            $administrator->setEmail($result['email']);
            $administrator->setPassword($result['password']);
            return $administrator;
        }

        return null;
    }
}