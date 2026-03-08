<?php

namespace App\Service;

use App\Entity\Progression;
use App\Entity\Ressource;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class ProgressionService
{
    private EntityManagerInterface $em;

    public const ACTION_VIEW = 'A consulté la ressource';
    public const ACTION_FAVORITE = 'A ajouté aux favoris';
    public const ACTION_UNFAVORITE = 'A retiré des favoris';
    public const ACTION_SAVE = 'A mis de côté';
    public const ACTION_UNSAVE = 'A retiré de ses ressources sauvegardées';
    public const ACTION_LIKE = 'A aimé la ressource';
    public const ACTION_UNLIKE = 'N\'aime plus la ressource';
    public const ACTION_CREATE_RESSOURCE = 'A publié une nouvelle ressource';
    public const ACTION_COMMENT = 'A commenté la ressource';

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Enregistre une nouvelle action dans l'historique de progression de l'utilisateur.
     * Pour la lecture (ACTION_VIEW), évite les doublons rapprochés (limite à 1h).
     */
    public function recordActivity(User $user, Ressource $ressource, string $action): void
    {
        // Anti-spam basique pour la simple "vue" d'une page
        if ($action === self::ACTION_VIEW) {
            $recentView = $this->em->getRepository(Progression::class)->createQueryBuilder('p')
                ->where('p.user = :user')
                ->andWhere('p.ressource = :ressource')
                ->andWhere('p.description = :action')
                ->andWhere('p.date >= :threshold')
                ->setParameter('user', $user)
                ->setParameter('ressource', $ressource)
                ->setParameter('action', $action)
                ->setParameter('threshold', new \DateTime('-1 hour'))
                ->getQuery()
                ->getOneOrNullResult();

            if ($recentView) {
                return; // On a déjà compté cette vue récemment
            }
        }

        $progression = new Progression();
        $progression->setUser($user);
        $progression->setRessource($ressource);
        $progression->setDescription($action);
        $progression->setDate(new \DateTime());

        $this->em->persist($progression);
        $this->em->flush();
    }
}
