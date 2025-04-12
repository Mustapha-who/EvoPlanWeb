<?php
namespace App\Repository\UserModule;

use App\Entity\UserModule\Instructor;
use App\Entity\UserModule\User;
use App\Service\UserModule\PasswordEncryption;
use App\Service\UserModule\ValidationService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\Exception;

class InstructorRepository extends ServiceEntityRepository
{
    private ValidationService $validationService;
    private PasswordEncryption $passwordEncryption;

    public function __construct(
        ManagerRegistry $registry,
        ValidationService $validationService,
        PasswordEncryption $passwordEncryption
    ) {
        parent::__construct($registry, Instructor::class);
        $this->validationService = $validationService;
        $this->passwordEncryption = $passwordEncryption;
    }

    public function displayUsers(): array
    {
        return $this->createQueryBuilder('i')
            ->innerJoin('i.user', 'u')
            ->select(
                'i.id',
                'u.name',
                'u.email',
                'u.password',
                'i.certification',
                'i.isApproved'
            )
            ->getQuery()
            ->getResult();
    }

    public function addUser(Instructor $instructor): void
    {
        if (!$this->validationService->isValidEmail($instructor->getEmail())) {
            throw new \InvalidArgumentException("Invalid email format.");
        }

        if (!$this->validationService->isValidPassword($instructor->getPassword())) {
            throw new \InvalidArgumentException("Invalid password format.");
        }

        if ($this->findOneBy(['email' => $instructor->getEmail()])) {
            throw new \InvalidArgumentException("Email already exists.");
        }


        $instructor->setPassword(
            $this->passwordEncryption->hashPassword($instructor->getPassword())
        );

        $this->getEntityManager()->persist($instructor);
        $this->getEntityManager()->flush();
    }

    public function updateUser(Instructor $instructor): void
    {
        $existingInstructor = $this->find($instructor->getId());

        if (!$existingInstructor) {
            throw new \InvalidArgumentException("Instructor not found.");
        }

        // Update common fields
        if ($instructor->getName()) {
            $existingInstructor->setName($instructor->getName());
        }

        if ($instructor->getEmail() && $this->validationService->isValidEmail($instructor->getEmail())) {
            $existingInstructor->setEmail($instructor->getEmail());
        }

        if ($instructor->getPassword() && $this->validationService->isValidPassword($instructor->getPassword())) {
            $existingInstructor->setPassword(
                $this->passwordEncryption->hashPassword($instructor->getPassword())
            );
        }

        // Update instructor-specific fields
        if ($instructor->getCertification() !== null) {
            $existingInstructor->setCertification($instructor->getCertification());
        }

        if ($instructor->isApproved() !== null) {
            $existingInstructor->setApproved($instructor->isApproved());
        }

        $this->getEntityManager()->flush();
    }

    public function deleteUser(int $id): void
    {
        $instructor = $this->find($id);

        if ($instructor) {
            $this->getEntityManager()->remove($instructor);
            $this->getEntityManager()->flush();
        }
    }

    public function getUser(int $id): ?Instructor
    {
        return $this->find($id);
    }
}