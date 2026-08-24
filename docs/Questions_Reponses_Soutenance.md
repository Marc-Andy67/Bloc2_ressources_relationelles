# 🎓 Q&A - Préparation et Soutenance de Projet

Ce document rassemble des réponses types techniques et argumentées pour faire face à des questions pointues de jury ou revoir vos choix d'architecture avec votre équipe.

---

## 🔐 Sécurité

**"Pourquoi tu utilises bcrypt et pas Argon2id ? Tu sais que bcrypt a une limite à 72 caractères ?"**
> **Réponse :** Sous Symfony, par défaut l'algorithme est désormais défini sur `auto`, ce qui utilise **Argon2id** en priorité (si l'extension Sodium est disponible sur le serveur) et ne fallback sur `bcrypt` qu'en son absence. Si `bcrypt` est actif, la limite des 72 octets est réelle, mais est contournée indirectement en amont par des contraintes de validation (`Assert\Length`) sur le formulaire d'inscription qui empêchent d'entrer un mot de passe trop long, protégeant de l'attaque. Mais idéalement, l'environnement de production doit supporter Argon2id, qui est le standard actuel.

**"Ton .env est sur le dépôt GitHub public. C'est pas un problème ?"**
> **Réponse :** Si c'est le `.env` de base, non, car il ne doit contenir que des variables de développement factices ou génériques (ex: `root:root` pour un docker local). La vraie sécurité réside dans le fait que les **vrais mots de passe de production** sont placés dans un fichier `.env.local` (qui est lui bien ignoré par Git grâce au `.gitignore`), ou encore mieux, gérés via le gestionnaire de Secrets de Symfony (`php bin/console secrets:set`).

**"Tes JWT, t'as mis quelle durée d'expiration ? Et si le token est volé avant expiration, t'as prévu quoi ?"**
> **Réponse :** Par défaut avec LexikBundle, la durée est de 3600 secondes (1h). Pour mitiger le vol d'un token (étant donné que le JWT est *stateless* et ne peut pas être révoqué facilement sans mettre en place une liste noire en BDD), on opte pour une stratégie combinée : une durée de vie très courte (15 à 30 minutes) pour le JWT, compensée par un **Refresh Token** (sauvegardé en BDD et révocable) géré par un bundle comme `GesdinetRefreshTokenBundle`.

**"La différence entre le JWT de LexikBundle et le JWT de Mercure, c'est quoi exactement ?"**
> **Réponse :** Le JWT de LexikBundle sert à l'**authentification HTTP REST** classique : il prouve l'identité de l'utilisateur qui appelle l'API Symfony.
Le JWT de Mercure sert uniquement à l'**autorisation Temps Réel (Hub SSE)** : il est signé par le backend et envoyé au client pour lui donner spécifiquement le droit de s'abonner (Subscriber) à un ou plusieurs "Topics" (canaux) privés sur le Hub Mercure.

**"T'as des tests sur tes endpoints sécurisés ? Montre-moi."**
> **Réponse :** Oui, la méthode typique avec PHPUnit est d'utiliser un `ApiTestCase` ou un `WebTestCase`. Dans le test, on utilise un client HTTP qui simule une requête `POST /api/login_check` pour récupérer le token, puis on injecte ce token dans le Header (`HTTP_AUTHORIZATION => Bearer...`) des requêtes suivantes pour certifier que les routes de modération (ex: `DELETE /api/comments`) retournent bien un 200 (si Admin) ou un 403 (si Utilisateur standard).

---

## 🏗️ Architecture

**"Pourquoi Symfony 8 alors que c'est encore très récent ? T'as eu des problèmes de compatibilité ?"**
> **Réponse :** Passer sur Symfony 8 garantit la longévité maximale du projet avant d'atteindre la fin de vie du framework, tout en profitant des optimisations natives de PHP 8.3/8.4. Les bundles majeurs (Lexik, Doctrine, Maker) sont déjà mis à niveau. L'innovation implique des risques calculés, mais permet par exemple de profiter du nouvel AssetMapper qui remplace proprement Node/Webpack Encore !

**"T'as Mercure ET du JWT. Dans quel cas tu utilises l'un plutôt que l'autre ?"**
> **Réponse :** Le JWT est utilisé pour le cycle **Requête-Réponse** (Je demande l'affichage d'un profil -> l'API répond immédiatement). Mercure est utilisé pour l'approche **Push (Server-Sent Events)** : le serveur pousse une info au client sans que ce dernier ait rien demandé (ex: une alerte en temps réel, un nouveau message dans une Chatroom de jeu).

**"Ton docker-compose.yml expose quels ports exactement ? C'est sécurisé en prod ?"**
> **Réponse :** En production, seul le port Web externe (80 et 443 pour le reverse proxy Caddy/Nginx/Traefik) et le port public du container Mercure doivent être exposés. Le port de la base de données (ex: 3306) **doit être impérativement enfermé** dans le sous-réseau Docker et surtout pas "bindé" `ports: ["3306:3306"]` sur la machine hôte.

**"La différence entre MERCURE_URL et MERCURE_PUBLIC_URL dans ton .env, pourquoi deux URLs ?"**
> **Réponse :** `MERCURE_URL` est le lien **réseau interne** (ex: `http://mercure/.well-known/mercure`) que le conteneur PHP Symfony va utiliser derrière le pare-feu pour "Pousser" l'évènement. `MERCURE_PUBLIC_URL` est l'adresse URL **publique externe** (ex: `https://monsite.fr/.well-known/mercure`) que le navigateur Javascript du client va contacter pour "Écouter" l'évènement.

---

## 🐳 Docker

**"Si ton container PHP crash, les données sont perdues ? T'as des volumes persistants ?"**
> **Réponse :** Le container PHP est *Stateless* (sans état), donc s'il explose, aucune donnée ne meurt. En revanche, les données sensibles (comme la base de données MySQL ou les uploads d'images PDF) sont mappées sur des **Volumes Docker** externes. Ainsi, si on supprime le container BDD et qu'on le recrée, il se reconnectera au volume persistant sur le disque dur et toutes les données retrouveront immédiatement leur place.

**"Pourquoi t'as un docker-compose.staging.yml séparé ? Qu'est-ce qui change par rapport au dev ?"**
> **Réponse :** L'environnement de *staging* simule strictement la production. Contrairement au fichier de `dev` où le code du développeur est mappé en temps réel dans le container (pour voir les modifs sans recompiler), en `staging` / `prod` on copie "en dur" (via `COPY` dans le Dockerfile) le code de manière immuable, on désactive le profiler de debug, au build on optimise le cache `opcache`, et on charge des variables sensibles.

**"Comment tu gères les variables d'environnement sensibles en prod ? Tu fais pas juste copier le .env j'espère ?"**
> **Réponse :** Non, nous n'utilisons pas le fichier `.env` sur le serveur de production. Les identifiants BDD ou clés API sont injectés directement depuis le panel d'orchestration (les *Secrets* Kubernetes, ou l'interface de variables d'environnement de Docker Swarm / Portainer). Alternativement, on utilise le coffre de secrets géré par Symfony (`secrets:set`).

---

## 🗄️ Base de données

**"T'as des migrations Doctrine. Si une migration plante en prod à mi-chemin, t'as prévu quoi ?"**
> **Réponse :** Sur MySQL, les requêtes de modification de schéma (DDL comme `ALTER TABLE`) ne sont malheureusement pas toujours encapsulées dans des transactions. C'est pourquoi un test de la migration s'effectue systématiquement sur une base de pré-production/staging (copie miroir). Si un désastre absolu frappe, la seule véritable assurance reste le **dump (sauvegarde) de la base de données exécuté automatiquement juste avant** le lancement des migrations en CI/CD.

**"T'as des fixtures de test. Elles partent pas en prod par erreur ?"**
> **Réponse :** Non, car elles sont déclarées dans la section `require-dev` du `composer.json`. En production, le pipeline déploie avec la commande `composer install --no-dev`. Le dossier des fixtures n'arrivera donc jamais sur le serveur !

**"Tes relations Doctrine, t'as pensé aux problèmes de N+1 queries ?"**
> **Réponse :** C'est un grand classique. Si j'affiche une collection de ressources, et que pour chaque ressource je fais appel à `ressource.getAuthor().getName()`, Doctrine (en mode Lazy) va tirer une requête SQL *par auteur*. Pour y pallier, j'ai optimisé mes classes `Repository` avec des jointures explicites via `createQueryBuilder` -> `leftJoin('r.author', 'u') -> addSelect('u')`, ramenant toutes les données relatives en **une seule** requête massive très rapide.

---

## ⚡ Mercure

**"Mercure ça tient combien de connexions simultanées sur ton infra actuelle ?"**
> **Réponse :** Mercure est écrit en Go (un langage d'une vélocité incroyable pour le réseau). Sur un serveur standard (2 VCpu / 4Go RAM), il est capable de maintenir **plusieurs dizaines de milliers** de connexions (Server-Sent Events) simultanées consommant très peu de bande passante, souvent limité uniquement par le quota linux des fichiers ouverts (`ulimit`) que l'on configure lors du déploiement.

**"Si le Hub Mercure est down, ton app continue de fonctionner ou tout plante ?"**
> **Réponse :** Le reste de la plateforme fonctionne (grâce à l'encapsulation d'exceptions) ! Si Symfony n'arrive pas à joindre le Hub pour annoncer "Nouveau message !", une exception PHP HTTP est générée mais elle est gérée (Try/Catch ou asynchronisme via Messenger), ce qui permet à l'application web de s'en sortir avec un *fallback* (Le client n'aura pas sa notification en direct, mais en rafraîchissant sa page, il verra quand même l'action via sa BDD).

**"Pourquoi t'as choisi Mercure plutôt que des WebSockets ou Pusher ?"**
> **Réponse :** Les WebSockets sont complexes, nécessitent un tunnel bi-directionnel persistant plus lourd à gérer côté infra/navigateur. Mercure, lui, repose du SSE (Server-Sent Event), du trafic unilatéral (serveur -> client) très léger supporté de base sous HTTP/2 et HTTPS. Contrairement à Pusher, Mercure est **open-source**, déployable gratuitement (pas d'abonnement tiers dépendant du volume de messages) et est organiquement designé par Fabien Potencier (lui-même patron de Symfony).

**"Tes updates Mercure sont publiques ou privées ? Tu peux pas lire les notifs d'un autre utilisateur ?"**
> **Réponse :** Mes canaux (Topics) sensibles sont **privés**. Quand l'utilisateur navigue sur l'URL de sa page, mon contrôleur génére un Token JWT avec l'URL du Topic autorisé (ex: `/chat/24`). Le client Javascript transmet ce token à Mercure. Si un pirate écoute le Hub avec le Topic de quelqu'un d'autre mais n'a pas la signature JWT prouvant qu'il en est autorisé, la connexion sera refusée avec un statut `401 Unauthorized`.

---

## 🧪 Tests

**"T'as PHPUnit et Panther. C'est quoi la différence et pourquoi les deux ?"**
> **Réponse :** `PHPUnit` via un composant `WebTestCase` analyse le code serveur de l'API sans lancer de navigateur. C'est instantané pour tester la base de données.
`Panther`, quant à lui, est du End-To-End (E2E). Il ouvre un **vrai navigateur Chrome** en arrière-plan (Headless) ! Il permet de vérifier si le Menu Hamburger en Javascript s'ouvre, ou si lors d'un clic visuel le JWT est bien stocké dans le navigateur. L'un teste la structure, l'autre teste l'Expérience Utilisateur.

**"Ton taux de couverture de tests il est à combien ?"**
> **Réponse :** Sur ce framework, l'objectif réaliste se situe autours des **70%**. Je couvre impérativement chaque Service (`ProgressionService`, `EntityManagers`), tous les points de contrôle API de sécurité (les fameuses autorisations de suppression ou d'auteurs). Pousser aveuglément à 100% fait souvent perdre du temps en obligeant à tester des setters et getters inutiles ou natifs à Symfony.

**"T'as des tests fonctionnels sur le workflow de modération ?"**
> **Réponse :** Évidemment. Le scénario est le suivant : Je connecte automatiquement le client WebTestCase avec le `ROLE_USER` = Je crée une ressource = J'affirme avec un assert qu'elle est en état "pending".
Puis, je purge le client, je le reconnecte avec `ROLE_MODERATOR` = Je fais un appel PATCH à mon endpoint d'évaluation = J'affirme avec l'assert que l'élément revient bien en état "validated" dans le schéma SQLite en RAM.

---

## 🌐 HTTPS / Certbot

**"Certbot renouvelle automatiquement, mais si le renouvellement échoue un dimanche à 3h du matin, t'as une alerte ?"**
> **Réponse :** Déjà, *Let's Encrypt* envoie un mail sur les adresses administratives configurées lors de la génération si l'échéance des -20 jours est atteinte. En complément, j'utilise un système de ping externe gratuit (comme UptimeRobot ou un Prometheus) qui déclenche une sonde sur la validité du certificat et prévient l'équipe sur Slack s'il expire dans moins de 7 jours.

**"Le certificat Let's Encrypt est valable 90 jours. Pourquoi 90 jours et pas plus ?"**
> **Réponse :** C'est une doctrine de Let's Encrypt pour décourager les installations manuelles et forcer l'automatisation intégrale (`cron`). De plus, une durée de vie très courte limite drastiquement le délai d'impact de la fraude si la clé privée du serveur vient accidentellement à être compromise.

**"HTTPS chiffre le transport. Mais les données en base, elles sont chiffrées aussi ?"**
> **Réponse :** HTTPS chiffre le flux entre le client et le serveur. Une fois stockées sur mon serveur, les données sont "au repos". Certains de mes objets extrêmement sensibles, comme les mots de passe, sont irréversiblement Hashés (`bcrypt/argon2`). Si je devais un jour stocker des données PII graves (Dossiers médicaux), j'implémenterais une librairie de chiffrement Doctrine (`AES-256`) qui chiffre la donnée *avant* de l'écrire dans la BDD.

---

## 🤝 Collaboration / Git

**"Vous êtes plusieurs sur le projet. Comment vous avez géré les conflits de migrations Doctrine ?"**
> **Réponse :** Si deux développeurs créent des migrations concurrentes dans la semaine avec le même *VersionNumber*, Git sera incapable de choisir la bonne. Le développeur "en retard" (qui pull d'un collègue) a alors la responsabilité de supprimer de son ordinateur son ancien fichier de migration local, d'incorporer celle de son partenaire, puis de relancer `php bin/console doctrine:migrations:diff` pour recréer une entité toute fraîche sans conflit temporel !

**"163 commits sur main directement ? Vous avez pas utilisé de branches par feature ?"**
> **Réponse :** Le pattern idéal est la méthode *GitFlow* de séparation stricte `feature/nom_de_la_fonctionnalité`, suivie d'une **Pull Request**, revue par le Pair et fusionnée sur `main`. Dans un contexte initial de prototypage rush (ou POC), commit en masse sur `main` s'est malheureusement imposé. Cela étant, dès l'entrée en pré-production, la restriction de push direct sur Github est la première sécurité à activer dans les paramètres de dépôt.

**"Qui a fait quoi concrètement ? T'as travaillé sur quelle partie ?"**
> **Réponse :** En tant que contributeur principal, j'ai notamment eu la charge de désamorcer les complexités de dépendances (comme le déboggage de la fausse suppression Doctrine impliquant un cascade manuel des composants `Progression`, `ChatRooms` et `Commentaires`), le routing complet des API Endpoints pour permettre à Flutter de communiquer sainement en JSON avec notre module LexikJWT, et d'intégrer des assets graphiques dynamiques dans un front end Tailwind complété par AssetMapper.
