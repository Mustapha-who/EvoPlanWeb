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
     * Retrieve the count of claims by status.
     *
     * @return array<string, int>
     */
    public function getStatusCounts(): array
    {
        $qb = $this->createQueryBuilder('c')
            ->select('c.claimStatus, COUNT(c.id) as count')
            ->groupBy('c.claimStatus');

        $results = $qb->getQuery()->getResult();

        $counts = [
            'new' => 0,
            'in_progress' => 0,
            'resolved' => 0,
        ];

        foreach ($results as $result) {
            $counts[$result['claimStatus']] = (int) $result['count'];
        }

        return $counts;
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

        if (isset($filters['claimStatus']) && $filters['claimStatus'] !== null) {
            $qb->andWhere('c.claimStatus = :status')
                ->setParameter('status', $filters['claimStatus']);
        }

        if (isset($filters['claimType']) && $filters['claimType'] !== null) {
            $qb->andWhere('c.claimType = :type')
                ->setParameter('type', $filters['claimType']);
        }

        if (isset($filters['keyword']) && !empty($filters['keyword'])) {
            $qb->andWhere('c.description LIKE :keyword')
                ->setParameter('keyword', '%' . $filters['keyword'] . '%');
        }

        $qb->orderBy('c.creationDate', 'DESC');

        return $qb->getQuery()->getResult();
    }
}