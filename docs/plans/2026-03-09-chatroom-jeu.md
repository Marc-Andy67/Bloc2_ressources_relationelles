# Plan d'implémentation de la fonctionnalité Chatroom

**Objectif :** Implémenter des salons de discussion temps réel uniques générés automatiquement lors de la validation des ressources de type "jeu".

**Architecture :** La création du salon (ChatRoom) s'effectue dynamiquement dans `BackOfficeController::approve()`. Une fois instanciée et persistée, l'URL est générée et concaténée au contenu de la ressource. Le mode *temps réel* sera orchestré par Symfony UX Turbo combiné à Symfony Mercure (SSE). Une nouvelle interface "Mes Chatrooms" viendra lister les salons rejoints.

**Stack Technique :** Symfony 8.0, PHP 8.4, Twig, Symfony UX Turbo, Symfony Mercure Bundle, Doctrine ORM.

---

### Tâche 1 : Installation et configuration de Mercure

**Étape 1 : Installer le bundle Mercure pour le temps réel**
- Commande : `composer require mercure`
- Résultat attendu : Configuration de `mercure.yaml` et installation des utilitaires liés pour gérer l'envoi de requêtes SSE en temps réel.

### Tâche 2 : Logique métier de Création Automatique (BackOfficeController)

**Fichiers concernés :**
- Modifier : `src/Controller/BackOfficeController.php`

**Étape 1 : Ajouter la logique de génération dans la méthode `approve`**
- Lors du `$ressource->setStatus('validated');` :
  - Vérifier si `$ressource->getType() === 'jeu'`.
  - Si oui, créer une `ChatRoom` avec `$chatRoom->setRessource($ressource)` et `$chatRoom->setName('Discussion : ' . $ressource->getTitle())`.
  - Effectuer un premier `flush` pour obtenir l'ID de la `ChatRoom`.
  - Générer l'URL via le constructeur de routes (URL absolue vers `/chat/room/join/{id}`) : `$url = $router->generate('app_chat_room_join_or_create', ['id' => $chatRoom->getId()], UrlGeneratorInterface::ABSOLUTE_URL);`
  - Concaténer l'URL au contenu de la ressource : `$ressource->setContent($ressource->getContent() . '<br><br><p><strong>Rejoignez la discussion :</strong> <a href="'.$url.'" class="text-blue-500 underline">'.$url.'</a></p>');`
  - Effectuer un second `flush` pour sauvegarder l'URL dans le texte.

### Tâche 3 : Nouvelle Page "Mes Chatrooms"

**Fichiers concernés :**
- Modifier : `src/Controller/ChatRoomController.php`
- Modifier : `src/Repository/ChatRoomRepository.php`
- Créer : `templates/chat_room/mine.html.twig`

**Étape 1 : Ajouter une méthode personnalisée dans `ChatRoomRepository`**
- Créer une méthode `findUserChatRoomsWithLastMessage(User $user)` avec du DQL pour lister les salons où l'utilisateur est membre et faire une jointure vers le `ChatMessage` le plus récent.

**Étape 2 : Créer la route `myChatRooms`**
Dans `ChatRoomController.php` :
```php
#[Route('/mine', name: 'app_chat_room_mine', methods: ['GET'])]
public function myChatRooms(ChatRoomRepository $chatRoomRepository): Response {
    $user = $this->getUser();
    $chatRooms = $chatRoomRepository->findUserChatRoomsWithLastMessage($user);
    return $this->render('chat_room/mine.html.twig', ['chat_rooms' => $chatRooms]);
}
```

### Tâche 4 : Implémentation UI et Temps Réel du Salon 

**Fichiers concernés :**
- Modifier : `templates/chat_room/show.html.twig`
- Créer/Modifier : `src/Controller/ChatMessageController.php`

**Étape 1 : Interface Twig avec stream**
Dans `show.html.twig`, utiliser l'helper UX Turbo et Mercure :
```twig
<div {{ turbo_stream_listen('chat_room_' ~ chat_room.id) }}></div>
<div id="chat_messages">
    {% for message in messages %}
       {% include 'chat_room/_message.html.twig' %}
    {% endfor %}
</div>
```

**Étape 2 : Traitement de l'envoi de message en temps réel**
Dans la soumission d'un `ChatMessage`, forcer une réponse Turbo Stream :
```php
// Optionnel: utiliser la configuration Broadcaster de symfony ux-turbo 
// ou renvoyer directement Response formattée "application/vnd.turbo-stream.html"
```

---

## Étape Finale : Remise de l'exécution
Le plan a été rédigé avec soins selon les critères d'ingénierie Antigravity et Symfony.
