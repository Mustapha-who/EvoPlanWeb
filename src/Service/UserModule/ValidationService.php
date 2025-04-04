<?php
namespace App\Service\UserModule;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints\Email;

class ValidationService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function isValidEmail(string $email): bool
    {
        $validator = Validation::createValidator();
        $violations = $validator->validate($email, [new Email()]);

        return count($violations) === 0;
    }

    public function isEmailExists(string $email): bool
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('count(u.id)')
            ->from('App\Entity\UserModule\User', 'u')
            ->where('u.email = :email')
            ->setParameter('email', $email);

        $query = $qb->getQuery();
        $count = $query->getSingleScalarResult();

        return $count > 0;
    }

    public function isValidPassword(string $password): bool
    {
        if (strlen($password) < 8) {
            return false;
        }

        if (!preg_match('/[A-Z]/', $password)) {
            return false;
        }

        if (!preg_match('/[a-z]/', $password)) {
            return false;
        }

        if (!preg_match('/[\W]/', $password)) {
            return false;
        }

        return true;
    }
}