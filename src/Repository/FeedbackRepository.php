<?php

namespace App\Repository;

use App\Entity\Feedback;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Feedback>
 */
class FeedbackRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Feedback::class);
    }

    /**
     * Find feedbacks by filters (rating and/or keyword).
     *
     * @param array $filters
     * @return Feedback[]
     */
    public function findByFilters(array $filters = []): array
    {
        $qb = $this->createQueryBuilder('f')
            ->leftJoin('f.client', 'c')
            ->orderBy('f.id', 'DESC');

        if (!empty($filters['rating'])) {
            $qb->andWhere('f.rating = :rating')
                ->setParameter('rating', $filters['rating']);
        }

        if (!empty($filters['keyword'])) {
            $qb->andWhere('f.comments LIKE :keyword')
                ->setParameter('keyword', '%' . $filters['keyword'] . '%');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Get the count of feedbacks for each rating (1 to 5).
     *
     * @return array<int, int>
     */
    public function getRatingCounts(): array
    {
        $results = $this->createQueryBuilder('f')
            ->select('f.rating, COUNT(f.id) as count')
            ->groupBy('f.rating')
            ->getQuery()
            ->getArrayResult();

        // Initialize counts for ratings 1 to 5
        $counts = [
            1 => 0,
            2 => 0,
            3 => 0,
            4 => 0,
            5 => 0,
        ];

        // Fill counts with actual data
        foreach ($results as $result) {
            $rating = (int) $result['rating'];
            if (isset($counts[$rating])) {
                $counts[$rating] = (int) $result['count'];
            }
        }

        return $counts;
    }

    /**
     * Get the average rating of all feedbacks.
     *
     * @return float
     */
    public function getAverageRating(): float
    {
        $result = $this->createQueryBuilder('f')
            ->select('AVG(f.rating) as average')
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $result ?: 0.0;
    }
}