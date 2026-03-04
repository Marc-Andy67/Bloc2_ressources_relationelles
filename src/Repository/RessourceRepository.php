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

    /**
     * @param array $filters
     * @return Ressource[]
     */
    public function findByFilters(array $filters): array
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.author', 'u')
            ->leftJoin('r.category', 'c')
            ->leftJoin('r.relationTypes', 'rt');

        if (!empty($filters['author'])) {
            $qb->andWhere('u.email LIKE :author')
                ->setParameter('author', '%' . $filters['author'] . '%');
        }

        if (!empty($filters['type'])) {
            $qb->andWhere('r.type = :type')
                ->setParameter('type', $filters['type']);
        }

        if (!empty($filters['category'])) {
            $qb->andWhere('c.id = :category')
                ->setParameter('category', $filters['category']);
        }

        if (!empty($filters['relation'])) {
            $qb->andWhere('rt.id = :relation')
                ->setParameter('relation', $filters['relation']);
        }

        return $qb->orderBy('r.creationDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

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

    /**
     * @return Ressource[] Returns an array of liked Ressource objects
     */
    public function findLikedByUser(\App\Entity\User $user): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.LikedBy', 'u')
            ->andWhere('u = :user')
            ->setParameter('user', $user)
            ->orderBy('r.creationDate', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }
}
