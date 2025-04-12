<?php

namespace App\Repository\UserModule;

use App\Entity\UserModule\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager , ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
        $this->entityManager = $entityManager;
    }

    public function getUserNameById(int $userId): string
    {
        try {
            $user = $this->entityManager->getRepository(User::class)->find($userId);
            return $user ? $user->getName() : 'Unknown User';
        } catch (Exception $e) {

            return 'Unknown User';
        }
    }

    public function getAllClientNames(): array
    {
        $clientNames = [];
        try {
            $qb = $this->entityManager->createQueryBuilder();
            $qb->select('u.name')
                ->from('App\Entity\UserModule\User', 'u')
                ->innerJoin('u', 'App\Entity\UserModule\Client', 'c', 'u.id = c.id');

            $query = $qb->getQuery();
            $result = $query->getResult();

            foreach ($result as $row) {
                $clientNames[] = $row['name'];
            }
        } catch (Exception $e) {
            // Log the exception message
        }
        return $clientNames;
    }

    public function getUser(int $id): ?User
    {
        return $this->find($id);
    }



}