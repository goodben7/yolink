<?php

namespace App\Repository;

use App\Entity\CampaignTrackingInfo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CampaignTrackingInfo>
 *
 * @method CampaignTrackingInfo|null find($id, $lockMode = null, $lockVersion = null)
 * @method CampaignTrackingInfo|null findOneBy(array $criteria, array $orderBy = null)
 * @method CampaignTrackingInfo[]    findAll()
 * @method CampaignTrackingInfo[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CampaignTrackingInfoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CampaignTrackingInfo::class);
    }

//    /**
//     * @return CampaignTrackingInfo[] Returns an array of CampaignTrackingInfo objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?CampaignTrackingInfo
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
