# 🛠️ Documentation Technique Complète : Backend & API "(RE)Sources Relationnelles"

Ce document répertorie l'architecture technique, les design patterns, les composants de sécurité et les implémentations clés utilisés pour la conception du backend Symfony et de l'API REST destinée à l'application mobile Flutter.

## 1. Stack Technique Globale
- **Framework** : Symfony (PHP 8+)
- **ORM** : Doctrine (MySQL / MariaDB)
- **Authentification Front-end** : Sessions Symfony (`security.yaml` - firewall main, Form Login)
- **Authentification Back-end (API)** : JSON Web Token (JWT) via `lexik/jwt-authentication-bundle`
- **Frontend Web** : Twig, Tailwind CSS (AssetMapper), Symfony UX (Icônes, Turbo), Vanilla JS.

---

## 2. Architecture des Entités (MCD - Modèle Conceptuel de Données)

Le modèle de données repose sur plusieurs grilles de `Repository` et `Entity` fortement liées :

### 👤 `User`
*Identifié par `Uuid`.*
- **Champs** : `email` (identifiant), `password` (hashé), `name`, `roles` (`ROLE_USER`, `ROLE_MODERATOR`, `ROLE_ADMIN`, `ROLE_SUPER_ADMIN`).
- **Relations** :
    - `OneToMany` vers `Ressource` (Author).
    - `ManyToMany` vers `Ressource` (likedRessources, favoritedRessources, savedRessources).
    - `OneToMany` vers `Comment`.
    - `OneToMany` vers `Progression`.

### 📚 `Ressource`
*Identifié par `Uuid`.* Le cœur du métier.
- **Champs** : `title`, `content`, `type` (ex: 'video', 'article', 'game'), `status` ('pending', 'validated', 'rejected', 'suspended'), `creationDate`, `size` (int).
- **Relations** :
    - `ManyToOne` vers `Category`.
    - `ManyToMany` vers `RelationType`.
    - `ManyToOne` vers `User` (Author).
    - `OneToMany` vers `Comment`.
    - `OneToOne` vers `ChatRoom` (optionnel).
    - Appréciations relationnelles : `likedBy`, `favoritedBy`, `setAsideBy` (ManyToMany User).

### 🏷️ `Category` & 🤝 `RelationType`
- Tables de nomenclature (Méta-données) pour classifier les ressources. Identifiés par `Uuid`. Gérées depuis le BackOffice ou l'API par un Administrateur.

### 💬 `ChatRoom` & 🗨️ `ChatMessage`
- Temps Réel. Une `ChatRoom` est généralement générée automatiquement (via `ChatRoomGenerator` service) lorsqu'une Ressource passe au statut `validated` ou bien pour jouer avec des jeux. Ses `ChatMessage` encadrent le nom de l'auteur et le timestamp `createdAt`.

### 📊 `Progression`
- Log historique (`OneToMany` par User).
- **Champs** : `user`, `description` (ex: "Auteur XYZ a publié la ressource X"), `createdAt`.

---

## 3. Architecture Logique (Services & Managers)

Toute logique métier complexe ou redondante a été abstraite hors des Contrôleurs vers le dossier `src/Service/`.

- **`ProgressionService`** : Contient un dictionnaire de constantes (ex: `ACTION_LIKE`, `ACTION_CREATE_RESSOURCE`) et injecte un historique (`recordActivity($user, $ressource, $action)`) à chaque fois qu'un utilisateur interagit sur la plateforme.
- **`ChatRoomGenerator`** : Service appelé lors du déclenchement d'un statut `validated` sur une ressource pour ouvrir automatiquement un salon de discussion `ChatRoom` autour du sujet.
- **`FileUploader`** : Implémente `FileUploaderInterface`. Gère de façon sécurisée (nommage unique UUID, guess extension) l'upload de fichiers joints (images, pdfs) sur les formulaires Symfony classiques.
- **`CommentManager` / `RessourceManager`** : Wrappers métiers manipulant le cycle de vie via l'`EntityManagerInterface`.

---

## 4. Sécurité & Contrôle d'Accès (`security.yaml`)

La configuration de la sécurité est scindée de façon pointue pour satisfaire à la fois l'application Web et l'application mobile (API).

1. **Firewalls API Publics (Priorité Haute)**
    - `api_register` (`^/api/register$`) : Inscription (POST). Public.
    - `api_login` (`^/api/login$`) : Vérification des credentials par `json_login` issu de LexikJWT, retourne le token. Public.
    - `api_relation_types` (`^/api/relation-types$`) : Nomenclature de sélection de type. Public.
    - `api_ressources_public` (`^/api/ressources$`) ET `api_ressource_detail_public` (`^/api/ressources/[0-9a-f-]{36}$`) : Exposition de la collection de base et du GET par Id, **obligatoirement stricts** pour ne pas écraser l'authentification des routes `/user/*`.

2. **Firewalls API Protégés**
    - `api` (`^/api`) : Firewall "Catch-all" sur les endpoints restants. Utilise `jwt`. L'utilisateur est identifié.

3. **Firewall Web Client**
    - `main` : Utilisation de sessions utilisateurs classiques, `form_login`, `logout`.

**Subtilité JWT (`JWTCreatedListener`)** : 
Le listener Lexik intercepte la formation du token JWT à l'`event` de création pour y injecter explicitement les rôles (`['ROLE_USER', ...]`) et le nom de l'utilisateur, facilitant l'intégration côté Flutter (pour réagir à des privilèges `ROLE_ADMIN`).

---

## 5. Endpoints API REST (Intégration Flutter)

Les API sont basées dans `src/Controller/Api`. Les requêtes attendent et renvoient le format `application/json`.
Toutes les dates sont formatées en `DateTime::ATOM` et toutes les clés primaires sont exposées en string (`Uuid`).

### A. Flux d'Authentification (`/api`)
- `POST /api/register` : Créer un User.
- `POST /api/login` : Recevoir en Body `{ "email": "...", "password": "..." }`, récupérer le Bearer Token.

### B. Ressources (`/api/ressources`)
- `GET /` : (Public) Retourne toutes les ressources `validated`. (Paramètres de string query possibles : `category`, `relation`, `author`). Si encodé avec un Bearer Token, les ressources `pending` propres à cet auteur sont également glissées dans la liste. 
- `GET /{id}` : (Public) Détail de la ressource.
- `POST /` : (Auth Requis) Crée une ressource (`status=pending` sauf modérateurs).
- `PATCH / PUT /{id}` : (Auteur Requis) Modifie une ressource existante.
- `PATCH /{id}/status` : (`ROLE_MODERATOR` Requis) Change le statut `pending` vers `validated`/`rejected` (générant par-la suite une chatroom).

### C. Référentiels
- `GET | POST | PUT | DELETE /api/categories` : (`ROLE_ADMIN` pour mutation).
- `GET /api/relation-types`.

### D. Actions de Progression et Tri (`/api/ressources/user/`)
- `GET /authored` | `/favorites` | `/saved` | `/liked` : Filtre automatique des ressources personnelles via le User injecté par le JWT token.
- `GET /api/user/progression` (`UserProgressionApiController`) : Renvoie les 30 derniers jours de `Progression`.
- `POST /api/ressources/{id}/action` : Reçoit le JSON `{ "action": "like" | "favorite" | "save" | "view" }` pour actionner les relations `ManyToMany` correspondantes en base.

### E. Modération (`/api/comments`)
- `DELETE /api/comments/{id}` : (`ROLE_MODERATOR` requis) Remplace un contenu de commentaire par une mention de censure pour protéger la structure de discussion. Fallback sur `403` pour l'utilisateur lambda.

---

## 6. Frontend Web (Twigs & Tailwind)

- Implémenté pour assurer un rôle vital d'interface pour le **Back Office** (Superviseur) et d'une alternative Desktop UI pour consulter la bibliothèque.
- **`app.css`** : S'exécute avec Tailwind CSS compilation. Comprend des classes customisées (`.glass-card`) pour un design transparent Depth-of-Field (Givré/Ombres douces).
- **Navigation adaptative** : Le `base.html.twig` implémente un `<nav>` qui intercepte les requêtes média `< md` pour basculer en un menu Hamburger (Dropdown Vanilla JS).
- **Dark Mode** : Utilise l'attribut `class="dark"` persistant sur le Local Storage pour alterner la palette de couleur sur l'ensemble du DOM.

---

## 7. Directives pour Tests Locaux
1. Démarrer le serveur web Symfony localement : `symfony serve`.
2. Démarrer potentiellement **Mercure** si besoin de pub/sub sur le module Realtime Chat (dépendant du setup).
3. Exécuter `php bin/console doctrine:fixtures:load` : Génèrera un set fiable de 3 utilisateurs (Admin, Modérateur, Utilisateur régulier `user@test.com`, mdp:`password`) et des Catégories de base.
4. Surveiller l'évolution des interfaces frontend : `php bin/console tailwind:build --watch` en fond.
5. S'assurer d'insérer le header `Authorization: Bearer <votre_token_JWT_issu_du_login>` pour 90% des échanges applicatifs du framework côté Flutter Dart.
