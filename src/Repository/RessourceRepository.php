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
        $status = $filters['status'] ?? 'validated';
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.author', 'u')
            ->leftJoin('r.category', 'c')
            ->leftJoin('r.relationTypes', 'rt')
            ->andWhere('r.status = :status')
            ->setParameter('status', $status);

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
     * @return Ressource[] Returns an array of Ressource objects created by the user
     */
    public function findAuthoredByUser(\App\Entity\User $user): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.author = :user')
            ->setParameter('user', $user->getId(), 'uuid')
            ->orderBy('r.creationDate', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return Ressource[] Returns an array of favorited Ressource objects
     */
    public function findFavoritedByUser(\App\Entity\User $user): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.favoritedBy', 'u')
            ->andWhere('u = :user')
            ->setParameter('user', $user->getId(), 'uuid')
            ->andWhere('r.status = :status')
            ->setParameter('status', 'validated')
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
            ->setParameter('user', $user->getId(), 'uuid')
            ->andWhere('r.status = :status')
            ->setParameter('status', 'validated')
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
            ->setParameter('user', $user->getId(), 'uuid')
            ->andWhere('r.status = :status')
            ->setParameter('status', 'validated')
            ->orderBy('r.creationDate', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Compte le nombre de ressources selon des filtres pour les statistiques
     */
    public function countFilteredResources(array $filters, string $status = 'validated'): int
    {
        $qb = $this->createQueryBuilder('r')
            ->select('count(r.id)')
            ->leftJoin('r.category', 'c')
            ->leftJoin('r.relationTypes', 'rt')
            ->andWhere('r.status = :status')
            ->setParameter('status', $status);

        // Filtre par dates
        if (!empty($filters['date_debut'])) {
            $qb->andWhere('r.creationDate >= :date_debut')
               ->setParameter('date_debut', new \DateTime($filters['date_debut']));
        }
        if (!empty($filters['date_fin'])) {
            $qb->andWhere('r.creationDate <= :date_fin')
               ->setParameter('date_fin', new \DateTime($filters['date_fin'] . ' 23:59:59'));
        }

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
}
