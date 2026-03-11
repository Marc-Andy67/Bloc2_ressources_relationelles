# Conception de la Fonctionnalité ChatRoom (Temps Réel)

**Date :** 11 Mars 2026
**Architecture retenue :** Symfony UX Turbo + Mercure Hub
**Objectif :** Rendre les salons de discussion (app_chat_room) dynamiques et temps réel sans rechargement de page, avec une interface utilisateur moderne et épurée.

## 1. Architecture & Flux de données
- Lorsqu'un utilisateur poste un message via le formulaire, la requête est envoyée au serveur en POST (Turbo Drive).
- Le `ChatMessageController` intercepte la requête, persiste le nouveau message en base de données, et retourne une réponse **Turbo Stream**.
- Cette réponse Turbo Stream ordonne au navigateur de l'expéditeur d'ajouter le message à la fin de la liste des messages.
- Dès que le message est persisté, Doctrine (ou le Contrôleur) déclenche un événement Mercure pour **broadcaster** le message aux autres utilisateurs actuellement connectés au même salon.
- Les autres utilisateurs reçoivent le message silencieusement via leur connexion SSE (Server-Sent Events) au Hub Mercure, et Turbo met à jour leur DOM sans Javascript spécifique.

## 2. Composants techniques à modifier
- **Entités :** Pas de modification du schéma, `ChatRoom` et `ChatMessage` sont déjà prêts.
- **Templates :**
  - `show.html.twig` : Ajout du composant `{{ turbo_stream_listen('chat_room_' ~ chat_room.id) }}`. Création de la zone de scroll et de la structure du chat.
  - `_message.html.twig` : Composant visuel isolé d'un seul message (bulles de chat selon expéditeur).
  - `_message.stream.html.twig` : Template spécifique pour formater la réponse `turbo-stream` (append au container).
- **Contrôleur :**
  - Modification de `ChatRoomController::show` pour s'assurer que le formulaire de message est bien affiché.
  - Modification de `ChatMessageController::new` (ou ajout d'une action dédiée dans ChatRoom) pour traiter l'ajout de message et retourner le flux Turbo Stream (ainsi que la publication sur le Hub Mercure).

## 3. UX & Interface (Skill Design Expert)
- **Liste des salons (`/mine`) :** Design "Glassmorphism", cartes épurées avec indicateurs de nouveaux messages.
- **Vue Salon (`/show`) :** 
  - En-tête : Nom du salon, Ressource liée, Participants.
  - Zone de messages : Bulles distinctes (gauche = autres, droite = utilisateur courant). Les bulles utiliseront des dégradés subtils, des ombres douces et une typographie lisible.
  - Zone de saisie : Input "sticky" en bas, design flottant, bouton d'envoi clair.
  - Auto-scroll vers le bas assuré par un petit contrôleur Stimulus (ex: `chat-scroll_controller.js`).

## 4. Gestion des erreurs et de la sécurité
- Autorisations : Vérifier que l'utilisateur participe bien au salon pour publier (`isGranted` ou vérification `getMembers()`).
- Validation : Formulaire protégé par les contraintes Symfony existantes (CSRF, NotBlank sur le contenu).

## 5. Dépendances requises
Le projet contient déjà `symfony/ux-turbo` et `symfony/mercure-bundle`. Il faudra s'assurer que le processus Mercure tourne en local lors du développement (via `symfony serve` qui l'intègre souvent, ou via un container Docker).
