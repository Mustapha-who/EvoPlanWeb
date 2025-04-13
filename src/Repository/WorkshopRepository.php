<?php

namespace App\Repository;

use App\Entity\Workshop;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Workshop>
 */
class WorkshopRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Workshop::class);
    }

    public function getSessionsPerWorkshop(): array
    {
        return $this->createQueryBuilder('w')
            ->select('w.title as workshop_title, COUNT(s.id_session) as session_count')
            ->leftJoin('w.sessions', 's')
            ->groupBy('w.id_workshop')
            ->getQuery()
            ->getResult();
    }


    public function getWorkshopsPerEvent(): array
    {
        return $this->createQueryBuilder('w')
            ->select('e.nom as event_name, COUNT(w.id_workshop) as workshop_count')
            ->leftJoin('w.id_event', 'e')
            ->groupBy('e.id_event')
            ->getQuery()
            ->getResult();
    }

    public function getCapacityVsAttendance(): array
    {
        return $this->createQueryBuilder('w')
            ->select('w.title, w.capacity as workshop_capacity, SUM(s.participant_count) as actual_attendance')
            ->leftJoin('w.sessions', 's')
            ->groupBy('w.id_workshop')
            ->orderBy('w.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getAttendanceRates(): array
    {
        return $this->createQueryBuilder('w')
            ->select('w.title, 
                     SUM(s.participant_count) as total_participants,
                     w.capacity as total_capacity,
                     (SUM(s.participant_count) * 100.0 / w.capacity) as attendance_rate')
            ->leftJoin('w.sessions', 's')
            ->groupBy('w.id_workshop')
            ->orderBy('w.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

//    /**
//     * @return Workshop[] Returns an array of Workshop objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('w')
//            ->andWhere('w.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('w.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Workshop
//    {
//        return $this->createQueryBuilder('w')
//            ->andWhere('w.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
