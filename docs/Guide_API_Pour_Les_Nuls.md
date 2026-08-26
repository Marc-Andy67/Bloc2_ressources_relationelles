# 📱 Guide de l'API (Pour les Nuls) : Comment ça marche ?

Ce document a été spécialement rédigé pour expliquer avec des mots simples **ce qu’est l'API de notre projet**, comment elle a été construite dans le code, et comment elle permet à l'application mobile de fonctionner.

---

## 1. C'est quoi une API ? (L'analogie du Restaurant)

Imaginez un grand restaurant :
*   **La Cuisine (Le Serveur / Base de données)** : C'est là que sont préparés les plats (les données complexes de Symfony et MySQL). Elle est cachée du public, sécurisée, et a ses propres règles compliquées.
*   **Les Clients à table (L'application Mobile Flutter)** : Le client a le menu, il sait ce qu'il veut manger (afficher des ressources), mais il n'a pas le droit d'entrer dans la cuisine pour se servir lui-même !
*   **Le Serveur en salle (L'API !)** : C'est le messager. Le client donne sa commande au serveur, le serveur va en cuisine, récupère le plat, et le ramène exactement comme le client l'a demandé.

L'**API REST** de notre projet Symfony n'est rien d'autre qu'un serveur en salle. Elle écoute les demandes du téléphone mobile, va chercher les informations dans notre base de données, et les renvoie au téléphone sous forme d'un format de texte très simple et universel : le **JSON**.

---

## 2. Comment c'est implémenté dans notre code ?

Au lieu de renvoyer de belles pages web (`HTML` / `Twig`) avec des couleurs et du design, notre API renvoie uniquement de la donnée brute. 

Dans notre projet Symfony, vous trouverez un dossier dédié : `src/Controller/Api/`.
C'est ici que vivent nos "serveurs en salle". Chaque fichier (ex: `RessourceApiController.php`, `UserApiController.php`) possède des "Routes" :
*   `GET /api/ressources` : L'application demande "Donne-moi la liste des ressources".
*   `POST /api/ressources` : L'application demande "Tiens, crée cette nouvelle ressource".
*   `DELETE /api/ressources/123` : L'application demande "Supprime la ressource numéro 123".

Quand vous regardez ces codes, la particularité est qu'à la fin de la méthode, au lieu de voir `return $this->render(...)`, vous verrez :
```php
return $this->json($ma_donnee);
```
Ceci demande à Symfony de transformer nos objets complexes en dictionnaire classique compréhensible par le smartphone !

---

## 3. La Sécurité : Le Bracelet "VIP" (JWT)

On ne veut pas que n'importe qui puisse supprimer un compte ou publier un message. Il faut donc une sécurité. Sur un site web classique, on se connecte avec une **Session** (un cookie géré par le navigateur). Mais une application mobile ne gère pas bien les sessions web classiques. 

C'est là qu'intervient **LexikJWT** (JSON Web Token).
Imaginez le JWT comme un **Bracelet de boîte de nuit**.

**Comment ça marche étape par étape ?**
1. L'application mobile envoie le pseudo et le mot de passe de l'utilisateur sur la route `/api/login_check`.
2. Symfony vérifie en base de données. Si c'est le bon mot de passe, Symfony **fabrique un long texte crypté** (le token JWT) et l'envoie au téléphone. C’est le fameux "bracelet VIP".
3. Le téléphone stocke ce bracelet dans sa mémoire.
4. Désormais, chaque fois que le téléphone veut faire une action privée (comme "Mes favoris" ou "Publier une ressource"), le téléphone ajoute le Token JWT en en-tête de sa requête (dans le *Header* HTTP : `Authorization: Bearer <token>`).
5. Le pare-feu Symfony (configuré dans `config/packages/security.yaml`) bloque l'entrée à l'API. Il demande : *"Montre-moi ton bracelet"*. S'il est valide et s'il prouve que l'utilisateur est bien l'Auteur, Symfony laisse passer !

---

## 4. Ce qu'on se passe concrètement (Le JSON)

Quand Flutter (le téléphone) et Symfony (notre serveur) discutent, ils ne parlent ni Dart ni PHP. L'API est comme un traducteur qui transforme les objets PHP en **JSON**.

Voici à quoi ressemble la "nourriture" que notre API renvoie au téléphone lorsqu'il demande une ressource :
```json
{
  "id": "e3b0c442-989b-464c-8302",
  "title": "Comment gérer le stress",
  "content": "Voici quelques astuces très utiles...",
  "type": "article",
  "status": "validated",
  "author": {
    "name": "Jean Dupont",
    "email": "jean.dupont@email.com"
  },
  "category": {
    "name": "Santé Mentale"
  },
  "likesCount": 42
}
```
L'application mobile lit ce texte (le reçoit instantanément en millisecondes), et dessine de jolies cartes colorées, des boutons "J'aime" et des textes directement sur l'écran !

---

## 5. Pourquoi on a eu des bugs pendant la création (et comment on les a réglés)

* **Le format des ID (UUID)** : Au début du projet, les identifiants étaient des chiffres (1, 2, 3). Nous sommes passés aux `UUID` (ex: `a54f8b9d-…`). L'API a du être adaptée car on ne pouvait plus envoyer le chiffre `1` pour chercher une catégorie, le téléphone devait envoyer la longue chaîne de caractères au contrôleur pour qu'il la décode (via `new Uuid($id)`).
* **Le problème du Delete (La boucle infinie SQL)** : Quand le téléphone demandait `DELETE /api/ressources/8`, Symfony plantait. Pourquoi ? Parce que l'API essayait de détruire la ressource, mais la Base de Données hurlait : *"Tu ne peux pas ! Cette ressource est liée à des commentaires et à un historique utilisateur !"*. J'ai alors implémenté dans le code de l'API un "effacement manuel" en cascade. Quand l'API reçoit l'ordre de tuer la ressource, elle efface d'abord méticuleusement l'historique et les commentaires, puis elle efface la ressource.
* **Le Firewall (Les murs de notre château)** : Vous vous rappelez du bracelet JWT ? Pour certaines routes (comme simplement *Afficher les ressources publiques*), on ne veut pas forcer les visiteurs à s'inscrire ou avoir un bracelet. Nous avons donc découpé le pare-feu dans `security.yaml` : la route `^/api/ressources$` est déclarée `security: false` avec la méthode `GET`. Les visiteurs sans bracelet passent librement ! Par contre, pour `POST` (créer), le videur interviendra systématiquement. 

---

## Résumé

1. L'application mobile Dart/Flutter n'a **aucune base de données interne**. Elle est vide et amnésique.
2. Tout ce qu'elle sait faire, c'est **crier des requêtes HTTP** vers l'URL de votre ordinateur (`http://127.0.0.1:8000/api/...`).
3. Vos contrôleurs API (`...ApiController.php`) reçoivent ce cri, demandent à `Doctrine` d'interroger `MySQL`, et répondent au téléphone dans la langue universelle : le `JSON`.
4. Le pare-feu `LexikJWT` exige un jeton validé pour laisser passer les cris concernant les actions personnelles privées.
