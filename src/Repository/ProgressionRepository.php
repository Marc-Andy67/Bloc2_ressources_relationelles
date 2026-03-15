<?php

namespace App\Repository;

use App\Entity\Progression;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Progression>
 */
class ProgressionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Progression::class);
    }

    /**
     * Compte le nombre d'entrées de progression selon des filtres
     */
    public function countFilteredProgressions(array $filters): int
    {
        $qb = $this->createQueryBuilder('p')
            ->select('count(p.id)')
            ->leftJoin('p.ressource', 'r')
            ->leftJoin('r.category', 'c')
            ->leftJoin('r.relationTypes', 'rt');

        // Filtre sur le type d'action (description)
        if (!empty($filters['action'])) {
            $qb->andWhere('p.description = :action')
               ->setParameter('action', $filters['action']);
        }

        // Filtre par dates
        if (!empty($filters['date_debut'])) {
            $qb->andWhere('p.date >= :date_debut')
               ->setParameter('date_debut', new \DateTime($filters['date_debut']));
        }
        if (!empty($filters['date_fin'])) {
            $qb->andWhere('p.date <= :date_fin')
               ->setParameter('date_fin', new \DateTime($filters['date_fin'] . ' 23:59:59'));
        }

        // Filtres liés à la ressource
        if (!empty($filters['categorie'])) {
            $qb->andWhere('c.id = :categorie')
               ->setParameter('categorie', $filters['categorie']);
        }
        if (!empty($filters['type_ressource'])) {
            $qb->andWhere('r.type = :type_ressource')
               ->setParameter('type_ressource', $filters['type_ressource']);
        }
        if (!empty($filters['type_relation'])) {
            $qb->andWhere('rt.id = :type_relation')
               ->setParameter('type_relation', $filters['type_relation']);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function findRecentByUser(\App\Entity\User $user, int $days = 30): array
    {
        $since = new \DateTime("-{$days} days");
        return $this->createQueryBuilder('p')
            ->where('p.user = :user')
            ->andWhere('p.date >= :since')
            ->setParameter('user', $user)
            ->setParameter('since', $since)
            ->orderBy('p.date', 'DESC')
            ->setMaxResults(50)
            ->getQuery()
            ->getResult();
    }
}
