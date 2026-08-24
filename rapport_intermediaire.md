# 📝 Rapport d'Évaluation Intermédiaire — Projet "(RE)Sources Relationnelles"
## Bilan Intermédiaire — BLOC 3 : DevOps, Architecture et Sécurisation Applicative

---

## 1. Architecture et environnement de déploiement

### Contexte général et architecture système
Le projet **(RE)Sources Relationnelles** est une plateforme collaborative de partage de ressources conçue pour renforcer les liens sociaux, la solidarité et l'entraide au sein des communautés d'utilisateurs du CESI. Pour répondre de manière optimale à ces objectifs, l'écosystème logiciel est structuré autour d'une architecture découplée. Le backend est développé avec le framework **Symfony 8.0** s'appuyant sur **PHP 8.4**. Il remplit un double rôle : d'une part, il sert d'interface de gestion et de modération (Back Office) pour les administrateurs et modérateurs via des templates Twig enrichis avec Tailwind CSS et des composants interactifs Symfony UX. D'autre part, il expose une API REST robuste et sécurisée par des jetons JSON Web Token (JWT) à destination d'un client mobile développé sous **Flutter (Dart)** pour les utilisateurs grand public. La persistance des données est déléguée au système de gestion de base de données relationnelle **MySQL 8.0**, dont la communication est gérée de manière transparente par l'ORM **Doctrine 3.6**. Les interactions en temps réel, notamment la création automatique de salons de discussion à la validation d'une ressource, sont assurées par l'intégration du hub de push événementiel **Symfony Mercure**.

### Orchestration de l'environnement et déploiement
Afin de garantir la stricte parité des environnements entre le développement local et la production, l'ensemble de l'infrastructure est conteneurisé et orchestré via **Docker Compose**. Le fichier de configuration multi-conteneurs définit trois services principaux : le conteneur applicatif Symfony (`app`) fonctionnant sous PHP-FPM, le serveur de base de données (`database`) sous MySQL, et le serveur de messagerie instantanée en temps réel (`mercure`). Cette structure permet de déployer l'intégralité du projet en une seule ligne de commande, éliminant les divergences liées au système d'exploitation de l'hôte. L'environnement de test ou de staging est déployé de manière automatisée sur un Serveur Virtuel Privé (VPS) Linux. L'exposition publique de l'application est administrée par un reverse proxy (Caddy ou Nginx) chargé de l'acheminement des requêtes vers le conteneur applicatif et de la gestion automatique des certificats SSL Let's Encrypt. Ce mécanisme permet de sécuriser le trafic HTTP sous le protocole HTTPS et de mettre à disposition l'application à l'adresse de test active suivante : `https://staging.ressources-relationnelles.cesi.fr`. Le dépôt Git privé contenant la totalité du code source et des scripts d'infrastructure est configuré pour être transmis directement aux examinateurs à l'adresse `mmurmann@cesi.fr`.

---

## 2. Automatisation du déploiement

### Pipeline d'Intégration et de Livraison Continues (CI/CD)
Pour éliminer les risques d'erreurs humaines et accélérer le cycle de livraison, l'équipe a mis en œuvre une automatisation complète des processus à l'aide de **GitHub Actions**. Le workflow d'intégration et de livraison continues est structuré en plusieurs phases distinctes, s'exécutant de façon séquentielle à chaque modification de la base de code. Le déclenchement s'opère automatiquement lors de toute soumission de Pull Request ou de Push vers les branches de référence.

```
[ Code Push ] ➔ [ Phase 1 : Build & Lints ] ➔ [ Phase 2 : Tests & Analyse ] ➔ [ Phase 3 : Déploiement SSH ]
```

### Phase 1 : Build (compilation et lints)
Cette première étape prépare l'environnement d'exécution au sein d'un exécuteur Linux éphémère. Elle configure la version adéquate de PHP (8.4), récupère les dépendances via Composer à l'aide d'un système de mise en cache optimisé pour réduire le temps d'exécution, puis procède à des vérifications de syntaxe rigoureuses. Les commandes de validation intégrées au framework Symfony sont invoquées pour analyser la validité des fichiers de configuration YAML (`php bin/console lint:yaml config`), des structures de templates Twig (`php bin/console lint:twig templates`), ainsi que de l'arbre d'injection de dépendances applicatives (`php bin/console lint:container`). Cette étape préventive garantit qu'aucune erreur de configuration grossière ou syntaxique ne perturbe les phases ultérieures.

### Phase 2 : Tests (validation fonctionnelle)
Une fois la phase de compilation validée, le pipeline initie l'exécution de la suite de tests automatisés. Un conteneur de service MySQL 8.0 temporaire est démarré au sein de l'environnement GitHub Actions. La base de données de test est créée dynamiquement, les migrations SQL de Doctrine y sont appliquées pour modéliser le schéma relationnel, et des fixtures de données de test (`doctrine:fixtures:load`) sont injectées afin de reproduire un jeu d'essai réaliste. Les tests unitaires et d'intégration sont lancés à l'aide de **PHPUnit**. En parallèle, l'analyse statique du code est exécutée via **PHPStan** pour s'assurer du strict respect des types et détecter les failles logiques potentielles. Les rapports de couverture de code sont générés et transmis à Codecov, tandis que les résultats des tests sont publiés sous forme de rapports JUnit directement exploitables sur GitHub.

### Phase 3 : Deploy (livraison continue sur le serveur)
Dès que les tests de validation se terminent avec succès sur la branche d'intégration `develop`, la phase de déploiement continu s'active. Le workflow se connecte de manière sécurisée par clé SSH au VPS cible. Le serveur effectue une authentification sur le registre d'images GitHub Container Registry (GHCR) afin de récupérer la version de l'image Docker nouvellement compilée et taguée par le hash du commit Git. Le pipeline met à jour les variables d'environnement locales, recrée les conteneurs Docker de staging à l'aide du fichier de configuration `docker-compose.staging.yml`, applique les dernières migrations de base de données de manière non interactive et redémarre les services. Un test de santé final (Healthcheck HTTP) est exécuté via une requête `curl` pour vérifier que le point d'accès principal de l'application répond positivement avec un code HTTP 200, garantissant ainsi un déploiement sans interruption de service (Zero-Downtime Deployment).

---

## 3. Plan de déploiement et continuité

### Planification logique du déploiement
La planification temporelle et logique du déploiement de la plateforme suit un enchaînement de tâches rigide inspiré d'une planification de type GANTT. Avant toute mise en production, l'environnement serveur fait l'objet d'un provisionnement matériel (dimensionnement CPU/RAM) et logiciel (Docker, certificats SSL, reverse proxy). La seconde étape consiste à sécuriser le stockage des clés de chiffrement de production (clés asymétriques SSL pour Lexik JWT et jetons d'accès aux bases de données) dans le trousseau de secrets GitHub. La troisième phase planifie la compilation, le test et la publication des images conteneurisées. Enfin, la phase de livraison s'articule autour d'une transition rapide où l'ancienne version de l'application est substituée par la nouvelle, suivie par l'exécution ordonnée des scripts de migration de données. Toutes ces tâches sont jalonnées par des tests fonctionnels rigoureux.

### Procédure de rollback et continuité de service
En cas de défaillance majeure lors du déploiement (erreur de conteneur, migration de base de données corrompue, ou échec du healthcheck HTTP), une procédure de secours automatisée et documentée est immédiatement enclenchée pour restaurer l'état stable précédent de l'application. 

1. **Interception de l'erreur** : La CI/CD détecte l'échec du healthcheck ou de la commande d'allumage Docker Compose et stoppe immédiatement le processus de mise en production pour éviter de propager l'incident.
2. **Retour arrière de l'image applicative (Rollback d'image)** : Le script de déploiement invoque la directive de retour en arrière de Docker Compose. Il réaffecte la variable d'image système `APP_IMAGE` à la version précédente stable (identifiée par le hash Git N-1) et relance les conteneurs (`docker compose up -d`).
3. **Restauration de la base de données (si nécessaire)** : Si la défaillance découle d'une migration Doctrine défectueuse ayant altéré l'intégrité ou la structure des données de production, le système applique un correctif descendant (migration down) ou procède à une restauration à partir de la sauvegarde physique automatisée effectuée immédiatement avant le déploiement. Le serveur MySQL est momentanément verrouillé en écriture, le dernier dump SQL stable est réinjecté, puis les connexions applicatives sont rétablies.
4. **Validation et Redémarrage** : Une fois la version précédente réinstallée, le pipeline relance les tests de santé HTTP. Dès confirmation du fonctionnement de la version de repli, une notification d'alerte critique est envoyée à l'équipe technique avec les journaux de plantage afin d'isoler et de corriger l'anomalie en local.

---

## 4. Gestion des versions et stratégie Git

### Stratégie de branchement GitFlow
Le groupe de développement a adopté une stratégie de gestion de versions robuste basée sur le modèle **GitFlow**. Ce flux de travail permet d'isoler rigoureusement les développements en cours du code destiné à la production. L'arbre Git est structuré autour de deux branches principales et permanentes :
- `main` : Cette branche représente l'état stable de l'application en production. Tout code présent sur cette branche est audité, testé et déployé à destination des utilisateurs finaux.
- `develop` : C'est la branche centrale d'intégration. Elle regroupe les fonctionnalités terminées et validées en attente d'être regroupées pour la prochaine version stable. C'est sur cette branche que s'appuie l'environnement de staging.

Pour chaque évolution, les développeurs créent des branches éphémères nommées selon les conventions `feature/nom-fonctionnalite` ou `bugfix/nom-bug` à partir de la branche `develop`. Une fois le développement terminé, une Pull Request (PR) est soumise vers `develop` pour intégration.

```
   (feature/oauth)  ────⚪──────⚪────────┐
                                         ▼
   (develop)        ──⚪─────────────────⚪─── (Staging)
                      │                  ▲
   (main)           ──┴──────────────────┴─── (Production)
```

### Gouvernance du code et charte de nommage des commits
La gouvernance du dépôt Git impose qu'aucune modification ne soit poussée directement sur les branches permanentes. L'intégration de code passe obligatoirement par des Pull Requests nécessitant la validation d'au moins un second développeur de l'équipe (Revue de code par les pairs) et la réussite absolue de la suite de tests et de linter automatisée de la CI. Pour maintenir un historique de projet transparent et faciliter la génération automatique de notes de version, le groupe se conforme à la spécification des **Conventional Commits**. Chaque message de validation doit être préfixé par son type (ex: `feat:` pour une nouveauté, `fix:` pour une correction, `docs:` pour la documentation, `refactor:` pour une réorganisation structurelle de code) et inclure une description concise au présent. Cette rigueur assure une traçabilité totale et simplifie grandement l'identification de l'origine d'une régression lors d'un audit de code.

---

## 5. Gestion des évolutions et méthode agile

### Suivi de projet et méthodologie agile
Le pilotage du projet collaboratif s'appuie sur la méthodologie agile **Scrum** pour sa flexibilité et sa réactivité face aux imprévus, combinée à l'utilisation d'un tableau Kanban visuel fourni par **GitHub Projects**. Le cycle de développement est découpé en itérations de deux semaines (Sprints). Chaque sprint débute par une réunion de planification (Sprint Planning) où l'équipe sélectionne les éléments prioritaires du Product Backlog à intégrer dans le Sprint Backlog. Un point quotidien de dix minutes (Daily Standup) permet de suivre l'avancement des tâches, de lever les points de blocage et d'ajuster l'effort collectif. À la fin de chaque itération, une démonstration des fonctionnalités prêtes à être livrées est réalisée, suivie d'une rétrospective permettant d'améliorer continuellement les processus internes de travail de l'équipe.

### Priorisation MoSCoW et répartition des charges
La gestion du backlog de développement et des tickets d'incidents utilise la matrice de priorisation **MoSCoW** afin de catégoriser scientifiquement les exigences du projet :
- **Must have** : Fonctionnalités indispensables au fonctionnement minimal de la plateforme (authentification JWT, gestion sécurisée des profils, publication et modération des ressources).
- **Should have** : Évolutions importantes apportant une valeur ajoutée forte mais non critiques pour le premier livrable (salons de chat en temps réel via Mercure, journal de progression de l'activité).
- **Could have** : Améliorations de confort ou esthétiques pouvant être reportées en cas de contrainte de temps (animations complexes d'interface au survol, mode sombre automatique selon les préférences système).
- **Won't have** : Éléments exclus du périmètre actuel de ce bilan intermédiaire (recommandation algorithmique des ressources basée sur l'intelligence artificielle).

La charge de travail a été répartie de manière équitable en segmentant les développements par grands blocs de compétences technologiques et en tenant compte des compétences individuelles. Un tableau Kanban attribue explicitement chaque tâche à un responsable unique, évitant ainsi le chevauchement de code et optimisant la responsabilisation de chacun dans l'équipe.

---

## 6. Analyse des risques sécurité

### Tableau synthétique des menaces
L'analyse des risques de sécurité a permis d'identifier les principales menaces pesant sur la plateforme collaborative et de concevoir des contre-mesures techniques adaptées, intégrées au cœur de l'architecture logicielle.

| Menace | Impact | Probabilité | Atténuation |
| :--- | :---: | :---: | :--- |
| **Injections SQL** | Élevé | Faible | Utilisation obligatoire de l'ORM Doctrine et interdiction des requêtes SQL brutes concaténées. Passage par le QueryBuilder et des paramètres typés nommés. |
| **Failles XSS** | Moyen | Faible | Échappement automatique par défaut de toutes les variables interpolées au sein du moteur de rendu Twig. |
| **Fuite de données / Interception** | Élevé | Faible | Chiffrement SSL/TLS (HTTPS) forcé sur l'ensemble des flux réseau. Jetons JWT signés à l'aide d'une paire de clés privées/publiques RSA 4096 bits. |
| **Brute Force (Accès comptes)** | Moyen | Moyen | Mise en œuvre d'un composant de limitation de débit (Rate Limiter) sur le endpoint d'authentification API `/api/login` et authentification web. |
| **Usurpation de requêtes (CSRF)** | Moyen | Faible | Génération et validation automatique de jetons anti-CSRF sur tous les formulaires natifs générés par Symfony. |

### Implémentations techniques de sécurité (Preuves de code)

#### Prévention des Injections SQL via l'ORM Doctrine
Pour écarter définitivement les risques d'injection de code malveillant dans la base de données, les requêtes dynamiques utilisent systématiquement des requêtes préparées via l'API QueryBuilder de Doctrine. Les variables d'entrée ne sont jamais concaténées directement dans la chaîne SQL mais passées comme paramètres sécurisés.

```php
// src/Repository/RessourceRepository.php
namespace App\Repository;

use App\Entity\Ressource;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class RessourceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ressource::class);
    }

    /**
     * Recherche sécurisée des ressources validées par catégorie.
     * Le QueryBuilder utilise des requêtes préparées avec des paramètres nommés pour éviter les injections SQL.
     */
    public function findValidatedByCategory(string $categoryId): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.status = :status')
            ->andWhere('r.category = :categoryId')
            ->setParameter('status', 'validated')
            ->setParameter('categoryId', $categoryId) // Typage et assainissement automatique par Doctrine
            ->orderBy('r.creationDate', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
```

#### Sécurisation du stockage des mots de passe (Hachage)
Les mots de passe des utilisateurs ne sont jamais stockés en clair dans la base de données. L'application s'appuie sur le composant de sécurité natif de Symfony configuré avec des algorithmes modernes de hachage unidirectionnel (Bcrypt / Argon2id par défaut), appliquant un salage automatique géré par le framework.

```php
// src/Controller/Api/SecurityApiController.php
namespace App\Controller\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class SecurityApiController extends AbstractController
{
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request, 
        UserPasswordHasherInterface $passwordHasher, 
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['email']) || !isset($data['password'])) {
            return new JsonResponse(['error' => 'Champs obligatoires manquants.'], 400);
        }

        $user = new User();
        $user->setEmail($data['email']);
        $user->setRoles(['ROLE_USER']);
        
        // Hachage sécurisé du mot de passe en clair à l'aide de l'algorithme configuré dans security.yaml
        $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Utilisateur créé avec succès.'], 201);
    }
}
```

#### Configuration des Firewalls de Sécurité Applicative
Le fichier de configuration de la sécurité définit le comportement des barrières d'accès pour l'API REST et l'interface Web, isolant les points d'entrée et associant la validation des jetons JWT sur l'ensemble de la zone API.

```yaml
# config/packages/security.yaml
security:
    password_hashers:
        Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface: 'auto'

    providers:
        app_user_provider:
            entity:
                class: App\Entity\User
                property: email

    firewalls:
        dev:
            pattern: ^/(_(profiler|wdt)|css|images|js)/
            security: false

        # Configuration spécifique de l'API d'authentification (Public)
        api_login:
            pattern: ^/api/login$
            stateless: true
            json_login:
                check_path: /api/login
                success_handler: lexik_jwt_authentication.handler.authentication_success
                failure_handler: lexik_jwt_authentication.handler.authentication_failure

        # Zone API générique sécurisée par jetons JSON Web Token (JWT)
        api:
            pattern: ^/api
            stateless: true
            jwt: ~

        # Firewall de l'application Web classique (Sessions et formulaires)
        main:
            lazy: true
            provider: app_user_provider
            form_login:
                login_path: app_login
                check_path: app_login
                enable_csrf: true
            logout:
                path: app_logout

    # Contrôle d'accès basé sur les rôles de l'utilisateur
    access_control:
        - { path: ^/api/login, roles: PUBLIC_ACCESS }
        - { path: ^/api/register, roles: PUBLIC_ACCESS }
        - { path: ^/api/comments, methods: [DELETE], roles: ROLE_MODERATOR }
        - { path: ^/api, roles: IS_AUTHENTICATED_FULLY }
        - { path: ^/admin, roles: ROLE_ADMIN }
```

---

## 7. Conformité RGPD

### Catégories de données à caractère personnel collectées
La mise en conformité de la plateforme avec le Règlement Général sur la Protection des Données (RGPD) est au centre de la conception de l'application. Les données collectées se limitent au strict nécessaire pour assurer le bon fonctionnement du service (principe de minimisation des données) :
- **Données d'identification** : Adresse email (utilisée comme identifiant unique de connexion), nom complet ou pseudonyme (pour l'affichage public sur la plateforme).
- **Données de sécurité** : Mot de passe haché de manière unidirectionnelle.
- **Données d'utilisation** : Historique d'activité interne via l'entité `Progression` (historique des ressources créées, aimées ou consultées) et contenu des commentaires publiés.

### Durée de conservation et politique d'anonymisation
Les données à caractère personnel sont conservées uniquement pendant la durée d'activité du compte de l'utilisateur. En cas d'inactivité prolongée (supérieure à 3 ans), une alerte est envoyée à l'utilisateur, et sans réponse de sa part, les données sont définitivement supprimées ou anonymisées. De plus, lorsqu'un utilisateur décide de supprimer son compte, la plateforme applique rigoureusement le "droit à l'oubli". Le compte utilisateur est supprimé physiquement de la base de données. Cependant, afin de préserver la cohérence logique et l'historique des discussions collectives au sein des salons de discussion, les commentaires rédigés par l'utilisateur ne sont pas nécessairement détruits mais subissent une anonymisation irréversible. L'identité de l'auteur est remplacée par la mention générique "Utilisateur supprimé" et les données d'identification personnelles associées sont totalement purgées de la base de données.

### Recueil du consentement et sécurité juridique
Pour assurer la conformité juridique, l'inscription d'un nouvel utilisateur (sur l'application mobile Flutter comme sur le site web) est subordonnée au recueil explicite de son consentement. Une case à cocher non pré-cochée est obligatoire lors du processus de création de compte, renvoyant directement vers la Politique de Confidentialité de la plateforme. Cette dernière détaille en termes simples l'usage qui est fait de ces données, les mesures de sécurité mises en œuvre pour les protéger, ainsi que la procédure à suivre (contact DPO ou interface dédiée dans les paramètres du profil) pour exercer les droits d'accès, de rectification, de portabilité et de suppression des données garantis par la réglementation européenne.

---

## 8. Bonnes pratiques de développement et qualité

### Stratégie de tests automatisés et non-régression
L'assurance qualité de la base de code repose sur une stratégie de tests automatisés rigoureuse intégrée au cycle de développement. L'équipe a écrit des tests unitaires et d'intégration à l'aide du framework **PHPUnit**. Les tests unitaires ciblent les composants de logique métier isolés du framework, tels que le service d'historisation `ProgressionService` ou le gestionnaire d'upload `FileUploader`. Les tests d'intégration, quant à eux, valident l'interaction avec la base de données et le bon fonctionnement des endpoints de l'API en simulant des requêtes HTTP complètes via la classe `WebTestCase` de Symfony. L'utilisation systématique de fixtures de test garantit que chaque exécution s'effectue sur un état de données connu et isolé, évitant les faux positifs. L'exécution de cette suite lors de chaque Pull Request permet de détecter instantanément les régressions et d'assurer que l'ajout d'une fonctionnalité ne dégrade pas les fonctionnalités existantes.

### Analyse statique et documentation technique du projet
En complément des tests dynamiques, la qualité du code est contrôlée de manière statique. Le projet intègre **PHPStan** configuré à un niveau d'exigence élevé pour analyser le code source sans l'exécuter. PHPStan identifie les variables non initialisées, les retours de fonctions non conformes aux déclarations de type et les appels de méthodes obsolètes ou inexistantes. Par ailleurs, une importance capitale est accordée à la documentation technique du dépôt Git. Le répertoire contient un fichier `README.md` exhaustif décrivant le processus d'installation pas-à-pas pour les nouveaux développeurs, ainsi qu'un dossier `docs/` regroupant des guides spécialisés (tels que la spécification complète des routes de l'API REST, les directives d'architecture et le design system). Cette documentation à jour garantit la maintenabilité à long terme de l'application et facilite grandement l'intégration de nouveaux collaborateurs sur le projet.

---

## 9. Plan de sécurisation global

### Authentification, autorisation et gestion des rôles
La sécurité d'accès de la plateforme collaborative repose sur une politique stricte d'authentification et d'autorisation à double niveau. Au niveau de la couche réseau et du protocole d'échange, l'ensemble de l'API est protégé par le protocole JSON Web Token (JWT). Chaque requête émise par l'application mobile Flutter doit intégrer dans son en-tête HTTP un jeton Bearer valide préalablement obtenu lors d'une authentification réussie sur le endpoint `/api/login`. Au niveau applicatif, le contrôle d'accès est géré par la hiérarchie de rôles de Symfony (`ROLE_USER`, `ROLE_MODERATOR`, `ROLE_ADMIN`). Les routes sensibles de l'application sont verrouillées à l'aide de directives de contrôle dans le fichier `security.yaml` et d'annotations de sécurité (`#[IsGranted]`) au sein même des contrôleurs. Par exemple, la modification d'une ressource est restreinte à son auteur d'origine ou à un administrateur, tandis que la censure de commentaires inappropriés est exclusivement réservée aux modérateurs applicatifs.

### Chiffrement des flux réseau et résilience des données
Afin de prévenir toute interception de données sensibles en transit (mots de passe, jetons JWT, données personnelles), la couche de transport réseau impose l'utilisation exclusive du protocole HTTPS sécurisé par TLS 1.3. Les configurations de serveurs Docker interdisent les connexions HTTP en clair en appliquant des redirections automatiques et des en-têtes de sécurité HSTS (HTTP Strict Transport Security). Concernant la résilience et la tolérance aux pannes, la base de données MySQL fait l'objet d'une stratégie de sauvegarde physique automatisée et déportée. Un script planifié de sauvegarde (Cron job) s'exécute quotidiennement à l'intérieur du conteneur de base de données pour réaliser un export complet (`mysqldump`) chiffré des données. Ces fichiers de sauvegarde sont ensuite stockés de manière sécurisée sur un espace de stockage externe (S3 ou serveur de sauvegarde dédié) isolé de l'infrastructure de production principale, garantissant ainsi la capacité de l'équipe à restaurer l'intégralité du service dans un délai minimal en cas de sinistre majeur ou d'altération malveillante des données.
