<?php

namespace App\Repository;

use App\Entity\Visite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Visite>
 *
 * @method Visite|null find($id, $lockMode = null, $lockVersion = null)
 * @method Visite|null findOneBy(array $criteria, array $orderBy = null)
 * @method Visite[]    findAll()
 * @method Visite[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VisiteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Visite::class);
    }

    public function save(Visite $visite, bool $flush = false): void
    {
        $this->getEntityManager()->persist($visite);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Visite $visite, bool $flush = false): void
    {
        $this->getEntityManager()->remove($visite);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    // Exemple : trouver toutes les visites d'un événement
    public function findByEvent(int $eventId): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.event = :eventId')
            ->setParameter('eventId', $eventId)
            ->orderBy('v.dateVisite', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
