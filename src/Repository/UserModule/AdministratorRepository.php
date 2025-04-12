<?php
namespace App\Repository\UserModule;

use App\Entity\UserModule\Administrator;
use App\Entity\UserModule\User;
use App\Service\UserModule\PasswordEncryption;
use App\Service\UserModule\ValidationService;


use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\Exception;

class AdministratorRepository extends ServiceEntityRepository
{
    private ValidationService $validationService;
    private PasswordEncryption $passwordEncryption;

    public function __construct(
        ManagerRegistry $registry,
        ValidationService $validationService,
        PasswordEncryption $passwordEncryption
    ) {
        parent::__construct($registry, Administrator::class);
        $this->validationService = $validationService;
        $this->passwordEncryption = $passwordEncryption;
    }

    public function displayUsers(): array
    {
        return $this->createQueryBuilder('a')
            ->innerJoin('a.user', 'u')
            ->select('a.id', 'u.name', 'u.email', 'u.password')
            ->getQuery()
            ->getResult();
    }

    public function addUser(Administrator $administrator): void
    {
        if (!$this->validationService->isValidEmail($administrator->getEmail())) {
            throw new \InvalidArgumentException("Invalid email format.");
        }

        if (!$this->validationService->isValidPassword($administrator->getPassword())) {
            throw new \InvalidArgumentException("Invalid password format.");
        }

        if ($this->findOneBy(['email' => $administrator->getEmail()])) {
            throw new \InvalidArgumentException("Email already exists.");
        }

        $administrator->setPassword(
            $this->passwordEncryption->hashPassword($administrator->getPassword())
        );

        $this->getEntityManager()->persist($administrator);
        $this->getEntityManager()->flush();
    }

    public function updateUser(Administrator $administrator): void
    {
        $existingAdmin = $this->find($administrator->getId());

        if (!$existingAdmin) {
            throw new \InvalidArgumentException("Administrator not found.");
        }

        // Update fields only if they're provided and valid
        if ($administrator->getName()) {
            $existingAdmin->setName($administrator->getName());
        }

        if ($administrator->getEmail() && $this->validationService->isValidEmail($administrator->getEmail())) {
            $existingAdmin->setEmail($administrator->getEmail());
        }

        if ($administrator->getPassword() && $this->validationService->isValidPassword($administrator->getPassword())) {
            $existingAdmin->setPassword(
                $this->passwordEncryption->hashPassword($administrator->getPassword())
            );
        }

        $this->getEntityManager()->flush();
    }

    public function deleteUser(int $id): void
    {
        $administrator = $this->find($id);

        if ($administrator) {
            $this->getEntityManager()->remove($administrator);
            $this->getEntityManager()->flush();
        }
    }

    public function getUser(int $id): ?Administrator
    {
        return $this->find($id);
    }
}