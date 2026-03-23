# 🎓 Tutoriel : Recréer le Projet (RE)Sources Relationnelles de Zéro (Symfony 8.0)

Ce tutoriel rassemble l'historique des commandes en ligne de commande (CLI) utilisées pour générer l'architecture du projet de zéro, étape par étape, en utilisant les standards de **Symfony 8.0**.

---

## Étape 1 : Initialisation du Projet
On crée la structure de base avec tous les outils nécessaires au Web (Twig, Doctrine, Formulaires, etc.).

```bash
# 1. Créer le projet "webapp" (Application Web Complète)
symfony new resources_relationnelles --webapp --version="8.0.*"

# 2. Entrer dans le dossier
cd resources_relationnelles

# 3. Démarrer le serveur local de développement
symfony serve -d
```

---

## Étape 2 : Configuration du Frontend (Tailwind CSS & UX)
Le design s'appuie sur le composant AssetMapper (sans Node.js) et Tailwind CSS.

```bash
# 1. Installer le bundle officiel Tailwind pour Symfony
composer require symfonycasts/tailwind-bundle

# 2. Initialiser Tailwind (crée le fichier tailwind.config.js et app.css)
php bin/console tailwind:init

# 3. Installer Symfony UX Icons pour les icônes (heroicons)
composer require symfony/ux-icons

# 4. Lancer le "watcher" de Tailwind dans un autre terminal
php bin/console tailwind:build --watch
```

---

## Étape 3 : Base de Données et Entité Utilisateur (User)
Mise en place de la sécurité et du modèle de données de base. Pensez d'abord à configurer votre chaîne de connexion MySQL/MariaDB dans le fichier `.env` (`DATABASE_URL`).

```bash
# 1. Créer la base de données
php bin/console doctrine:database:create

# 2. Générer l'entité User avec les outils de sécurité intégrés
php bin/console make:user

# Répondez aux questions :
# - Nom de la classe : User
# - Stocker les infos dans la BDD : oui
# - Propriété d'affichage : email
# - Hasher le mot de passe : oui

# 3. Générer le système d'authentification classique (Form Login pour le Web)
php bin/console make:auth
# Choisir "Login form authenticator" puis nommer le controller "SecurityController"

# 4. Générer le formulaire d'inscription
php bin/console make:registration-form
```

---

## Étape 4 : Création des Entités Métier (MCD)
Création des tables qui constituent la bibliothèque de ressources.

```bash
# Entité Catégorie
php bin/console make:entity Category
# Ajouter un champ 'name' (string, 255)

# Entité Type de Relation
php bin/console make:entity RelationType
# Ajouter un champ 'name' (string, 255)

# L'entité principale : Ressource
php bin/console make:entity Ressource
# Ajouter les champs : title (string), content (text), status (string), type (string, default: 'article')
# Ajouter les relations (OneToMany avec Comment, ManyToMany avec RelationType, ManyToOne avec Category et User)

# Entité Commentaire
php bin/console make:entity Comment
# Ajouter les champs : content (text)
# Ajouter les relations : ManyToOne avec User et Ressource

# Entité Progression (Historique)
php bin/console make:entity Progression
# Ajouter les champs : description (string), createdAt (datetime_immutable)
# Ajouter les relations : ManyToOne avec User

# Entité ChatRoom et ChatMessage
php bin/console make:entity ChatRoom
php bin/console make:entity ChatMessage

# ⚠️ Une fois toutes les entités générées, on pousse les tables dans la base :
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

---

## Étape 5 : Fixtures (Données de test)
Pour injecter des faux profils et des catégories de base pour faciliter les tests de développement.

```bash
# 1. Installer le composant de Fixtures (uniquement pour le développement)
composer require --dev orm-fixtures

# 2. Cela va créer un fichier src/DataFixtures/AppFixtures.php
# (Vous y coderez des utilisateurs "admin@test.com" ou "user@test.com")

# 3. Charger les données dans la base de données
php bin/console doctrine:fixtures:load
```

---

## Étape 6 : Sécurité de l'API REST (Authentification Mobile / Flutter)
Puisque l'application mobile ne peut pas utiliser une "Session" Web classique, on installe un système d'authentification par Token JWT (JSON Web Token).

```bash
# 1. Installer LexikJWTAuthenticationBundle
composer require lexik/jwt-authentication-bundle

# 2. Générer les clés de sécurité SSL pour crypter les tokens (sauvegardées dans config/jwt/)
php bin/console lexik:jwt:generate-keypair

# 3. (Configuration manuelle) : Modifier config/packages/security.yaml pour 
# ajouter le "form_login" JSON (api_login) et le vérificateur JWT.
```

---

## Étape 7 : Génération des Contrôleurs (Web et API)

### Pour le Site Web classique (HTML/Twig)
```bash
# Générer la page d'accueil
php bin/console make:controller HomeController

# Générer un CRUD complet (Create, Read, Update, Delete) avec vues Twig pour les ressources
php bin/console make:crud Ressource
```

### Pour l'API Flutter (Données JSON pures)
```bash
# Créer le contrôleur qui distribuera les ressources en JSON (GET, POST, PATCH)
php bin/console make:controller Api\\RessourceApiController

# Créer les autres terminaux API
php bin/console make:controller Api\\CategoryApiController
php bin/console make:controller Api\\ProgressionApiController
php bin/console make:controller Api\\UserProgressionApiController
```

---

## Étape Finale : Compilation et Déploiement
À chaque fois que vous modifiez le `base.html.twig`, pensez à vider le cache et à relancer Tailwind (si le watcher s'était éteint).

```bash
# Vider le cache Symfony
php bin/console cache:clear

# Mettre à jour la base de données après un changement d'Entity
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

Et voilà ! L'architecture complète est générée et est prête à héberger toute la logique métier que nous avons insérée dans les contrôleurs.
