<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // 1. Création des Catégories
        $categories = ['Communication', 'Écoute active', 'Gestion des conflits', 'Intelligence émotionnelle', 'Développement personnel'];
        foreach ($categories as $catName) {
            $category = new \App\Entity\Category();
            $category->setName($catName);
            $manager->persist($category);
        }

        // 2. Création des Types de Relations Concernées
        $relations = ['Soi', 'Conjoints', 'Famille', 'Amis', 'Collègues', 'Inconnus'];
        foreach ($relations as $relName) {
            $relation = new \App\Entity\RelationType();
            $relation->setName($relName);
            $manager->persist($relation);
        }

        $manager->flush();
    }
}
