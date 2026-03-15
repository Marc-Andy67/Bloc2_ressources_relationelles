<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\RelationType;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // 1. Création des Catégories
        $categories = ['Communication', 'Écoute active', 'Gestion des conflits', 'Intelligence émotionnelle', 'Développement personnel', 'Jeu'];
        foreach ($categories as $catName) {
            $category = new Category();
            $category->setName($catName);
            $manager->persist($category);
        }

        // 2. Création des Types de Relations Concernées
        $relations = ['Soi', 'Conjoints', 'Famille', 'Amis', 'Collègues', 'Inconnus'];
        foreach ($relations as $relName) {
            $relation = new RelationType();
            $relation->setName($relName);
            $manager->persist($relation);
        }

        // 3. Création des Utilisateurs (Citoyens, Admins, Super Admins)
        $usersData = [
    // Citoyens (ROLE_USER uniquement)
            ['email' => 'marie.dupont@gmail.com', 'name' => 'Marie Dupont', 'password' => 'Marie2024@Paris!', 'roles' => ['ROLE_USER']],
            ['email' => 'lucas.martin@gmail.com', 'name' => 'Lucas Martin', 'password' => 'Lucas#Foot2024!', 'roles' => ['ROLE_USER']],
            ['email' => 'sophie.bernard@gmail.com', 'name' => 'Sophie Bernard', 'password' => 'Sophie&Chat2024!', 'roles' => ['ROLE_USER']],
    // Modérateurs (ROLE_MODERATOR uniquement)
            ['email' => 'modo1@reseau.fr', 'name' => 'Modo Un', 'password' => 'Modo1@2024!', 'roles' => ['ROLE_MODERATOR']],
            ['email' => 'modo2@reseau.fr', 'name' => 'Modo Deux', 'password' => 'Modo2@2024!', 'roles' => ['ROLE_MODERATOR']],
            ['email' => 'modo3@reseau.fr', 'name' => 'Modo Trois', 'password' => 'Modo3@2024!', 'roles' => ['ROLE_MODERATOR']],
    // Administrateurs (ROLE_ADMIN uniquement)
            ['email' => 'admin1@reseau.fr', 'name' => 'Admin Un', 'password' => 'Admin1@2024!', 'roles' => ['ROLE_ADMIN']],
            ['email' => 'admin2@reseau.fr', 'name' => 'Admin Deux', 'password' => 'Admin2@2024!', 'roles' => ['ROLE_ADMIN']],
            ['email' => 'admin3@reseau.fr', 'name' => 'Admin Trois', 'password' => 'Admin3@2024!', 'roles' => ['ROLE_ADMIN']],
    // Super Administrateurs (ROLE_SUPER_ADMIN uniquement)
            ['email' => 'superadmin1@reseau.fr', 'name' => 'Super Admin Un', 'password' => 'SuperAdmin1@2024!', 'roles' => ['ROLE_SUPER_ADMIN']],
            ['email' => 'superadmin2@reseau.fr', 'name' => 'Super Admin Deux', 'password' => 'SuperAdmin2@2024!', 'roles' => ['ROLE_SUPER_ADMIN']],
            ['email' => 'superadmin3@reseau.fr', 'name' => 'Super Admin Trois', 'password' => 'SuperAdmin3@2024!', 'roles' => ['ROLE_SUPER_ADMIN']],
    // Comptes pour tests d'intégration Flutter
            ['email' => 'user@test.com', 'name' => 'Test User', 'password' => 'password', 'roles' => ['ROLE_USER']],
            ['email' => 'moderator@test.com', 'name' => 'Test Mod', 'password' => 'password', 'roles' => ['ROLE_MODERATOR']],
            ['email' => 'admin@test.com', 'name' => 'Test Admin', 'password' => 'password', 'roles' => ['ROLE_ADMIN']],
];

        foreach ($usersData as $data) {
            $user = $manager->getRepository(User::class)->findOneBy(['email' => $data['email']]);
            
            if (!$user) {
                $user = new User();
                $user->setEmail($data['email']);
                $manager->persist($user);
            }

            // Toujours appliquer les modifications, même si l'utilisateur existait déjà
            $user->setName($data['name']);
            $user->setRoles($data['roles']);
            
            // Hash password
            $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);
            $user->setIsActive(true);
        }

        $manager->flush();
    }
}
