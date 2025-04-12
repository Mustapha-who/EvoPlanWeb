<?php
namespace App\Repository\UserModule;

use App\Entity\UserModule\EventPlanner;
use App\Service\UserModule\PasswordEncryption;
use App\Service\UserModule\ValidationService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\Exception;

class EventPlannerRepository extends ServiceEntityRepository
{
    private ValidationService $validationService;
    private PasswordEncryption $passwordEncryption;

    public function __construct(
        ManagerRegistry $registry,
        ValidationService $validationService,
        PasswordEncryption $passwordEncryption
    ) {
        parent::__construct($registry, EventPlanner::class);
        $this->validationService = $validationService;
        $this->passwordEncryption = $passwordEncryption;
    }

    public function displayUsers(): array
    {
        return $this->createQueryBuilder('e')
            ->innerJoin('e.user', 'u')
            ->select(
                'e.id',
                'u.name',
                'u.email',
                'u.password',
                'e.specialization',
                'e.assignedModule'
            )
            ->getQuery()
            ->getResult();
    }

    public function addUser(EventPlanner $eventPlanner): void
    {
        if (!$this->validationService->isValidEmail($eventPlanner->getEmail())) {
            throw new \InvalidArgumentException("Invalid email format.");
        }

        if (!$this->validationService->isValidPassword($eventPlanner->getPassword())) {
            throw new \InvalidArgumentException("Invalid password format.");
        }

        if ($this->findOneBy(['email' => $eventPlanner->getEmail()])) {
            throw new \InvalidArgumentException("Email already exists.");
        }

        $eventPlanner->setPassword(
            $this->passwordEncryption->hashPassword($eventPlanner->getPassword())
        );

        $this->getEntityManager()->persist($eventPlanner);
        $this->getEntityManager()->flush();
    }

    public function updateUser(EventPlanner $eventPlanner): void
    {
        $existingEventPlanner = $this->find($eventPlanner->getId());

        if (!$existingEventPlanner) {
            throw new \InvalidArgumentException("Event planner not found.");
        }

        // Update common fields
        if ($eventPlanner->getName()) {
            $existingEventPlanner->setName($eventPlanner->getName());
        }

        if ($eventPlanner->getEmail() && $this->validationService->isValidEmail($eventPlanner->getEmail())) {
            $existingEventPlanner->setEmail($eventPlanner->getEmail());
        }

        if ($eventPlanner->getPassword() && $this->validationService->isValidPassword($eventPlanner->getPassword())) {
            $existingEventPlanner->setPassword(
                $this->passwordEncryption->hashPassword($eventPlanner->getPassword())
            );
        }

        // Update event planner specific fields
        if ($eventPlanner->getSpecialization() !== null) {
            $existingEventPlanner->setSpecialization($eventPlanner->getSpecialization());
        }

        if ($eventPlanner->getAssignedModule() !== null) {
            $existingEventPlanner->setAssignedModule($eventPlanner->getAssignedModule());
        }

        $this->getEntityManager()->flush();
    }

    public function deleteUser(int $id): void
    {
        $eventPlanner = $this->find($id);

        if ($eventPlanner) {
            $this->getEntityManager()->remove($eventPlanner);
            $this->getEntityManager()->flush();
        }
    }

    public function getUser(int $id): ?EventPlanner
    {
        return $this->find($id);
    }
}