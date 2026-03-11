# Conception de la Fonctionnalité ChatRoom (Temps Réel & Privée)

**Date :** 11 Mars 2026
**Architecture retenue :** Symfony UX Turbo + Mercure Hub
**Objectif :** Rendre les salons dynamiques et temps réel, renommer les menus, et implémenter un système de salons privés sur invitation/validation.

## 1. Nouveaux Besoins Métier
1. **Génération automatique pour jeux :** La logique existe via `ChatRoomGenerator`. Nous allons l'optimiser pour que l'auteur de la ressource "Jeu" soit automatiquement ajouté comme premier membre du salon (l'hôte).
2. **Accès privé et Validation :** Les autres utilisateurs qui tentent de rejoindre verront un écran pour "Demander à rejoindre". Cette action ajoutera l'utilisateur dans une liste d'attente (`pendingMembers`) et enverra un message système automatique dans le chat.
3. **Renommage :** Tous les termes "Conversations" dans les menus doivent devenir "Chatrooms".

## 2. Architecture & Flux de données
- Le chat reste Propulsé par Turbo Stream + Mercure.
- Lorsqu'une personne non-membre accède à `/chat/room/join/{id}`, elle ne peut pas voir les messages. Un bouton "Demander l'accès" envoie une requête POST qui la met dans les `pendingMembers` du `ChatRoom`.
- Le serveur émet alors un `ChatMessage` automatique dans le salon, prévenant les membres : "Jean souhaite rejoindre le salon."  
- Dans le chat, les membres existants pourront voir une bannière ou un bouton sur ces messages spécifiques pour appeler une route `accept_member` ou `refuse_member`.

## 3. Composants techniques à modifier
- **Entité ChatRoom :** Ajouter la propriété `pendingMembers` (ManyToMany avec User) pour lister les demandes.
- **Service ChatRoomGenerator :** Modifier la logique pour ajouter `addMember($ressource->getAuthor())` dès la création du chat pour la ressource jeu.
- **Templates :**
  - Renommage de `base.html.twig`, `home/index.html.twig`, et `chat_room/index.html.twig` ("Conversations" -> "Chatrooms").
  - `show.html.twig` : Ajout des écoutes Turbo Stream. S'assurer que le bouton Accepter/Refuser s'affiche correctement pour les `pendingMembers`.
  - Nouvelle vue "Accès restreint" pour la route `join` ou condition IF dans le `show` si non-membre.
- **Contrôleur ChatRoomController :**
  - Modifier le `/join` pour gérer l'ajout aux `pendingMembers` et envoyer le `ChatMessage`.
  - Ajouter `/accept/{userId}` et `/refuse/{userId}` pour basculer de `pendingMembers` à `members`.

## 4. Expérience Temps Réel
(Conservé de la V1)
Turbo Drive s'occupe de l'envoi asynchrone, Mercure diffuse en local. Le contrôleur Stimulus `chat-scroll_controller.js` garantira le confort de lecture.
