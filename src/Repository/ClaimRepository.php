<?php

namespace App\Repository;

use App\Entity\Claim;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Claim>
 */
class ClaimRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Claim::class);
    }

    /**
     * @param array $filters
     * @return Claim[]
     */
    public function findByFilters(array $filters): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.client', 'cl')
            ->addSelect('cl');

        if (isset($filters['claim_filter']['claimStatus']) && $filters['claim_filter']['claimStatus'] !== null) {
            $qb->andWhere('c.claimStatus = :status')
                ->setParameter('status', $filters['claim_filter']['claimStatus']);
        }

        if (isset($filters['claim_filter']['claimType']) && $filters['claim_filter']['claimType'] !== null) {
            $qb->andWhere('c.claimType = :type')
                ->setParameter('type', $filters['claim_filter']['claimType']);
        }

        if (isset($filters['claim_filter']['keyword']) && !empty($filters['claim_filter']['keyword'])) {
            $qb->andWhere('c.description LIKE :keyword')
                ->setParameter('keyword', '%' . $filters['claim_filter']['keyword'] . '%');
        }

        $qb->orderBy('c.creationDate', 'DESC');

        return $qb->getQuery()->getResult();
    }
}