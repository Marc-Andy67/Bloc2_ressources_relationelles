<?php

namespace App\Repository;

use App\Entity\Ressource;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ressource>
 */
class RessourceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ressource::class);
    }

    //    /**
    //     * @return Ressource[] Returns an array of Ressource objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('r.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Ressource
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    /**
     * @return Ressource[] Returns an array of favorited Ressource objects
     */
    public function findFavoritedByUser(\App\Entity\User $user): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.favoritedBy', 'u')
            ->andWhere('u = :user')
            ->setParameter('user', $user)
            ->orderBy('r.creationDate', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return Ressource[] Returns an array of saved Ressource objects
     */
    public function findSetAsideByUser(\App\Entity\User $user): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.setAsideBy', 'u')
            ->andWhere('u = :user')
            ->setParameter('user', $user)
            ->orderBy('r.creationDate', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }
}
