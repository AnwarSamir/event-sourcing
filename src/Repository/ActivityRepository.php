<?php

namespace App\Repository;

use App\Entity\Activity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Activity>
 */
class ActivityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Activity::class);
    }

    /**
     * Find activities that are received but not yet applied
     */
    public function findReceivedActivities(string $activityId): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.activityId = :activityId')
            ->andWhere('a.applied = :applied')
            ->andWhere('a.invalidated = :invalidated')
            ->setParameter('activityId', $activityId)
            ->setParameter('applied', false)
            ->setParameter('invalidated', false)
            ->orderBy('a.date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find activities that are applied
     */
    public function findAppliedActivities(string $activityId): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.activityId = :activityId')
            ->andWhere('a.applied = :applied')
            ->andWhere('a.invalidated = :invalidated')
            ->setParameter('activityId', $activityId)
            ->setParameter('applied', true)
            ->setParameter('invalidated', false)
            ->orderBy('a.date', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
