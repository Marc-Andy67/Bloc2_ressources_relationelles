# 📖 Le Guide pour les Nuls : "(RE)Sources Relationnelles"

Bienvenue dans ce guide complet ! Ce document a été spécialement pensé pour quiconque souhaite comprendre de A à Z à quoi sert ce projet, sans aucun jargon compliqué de développeur. Que vous soyez un futur utilisateur, un testeur, ou simplement curieux, vous êtes au bon endroit.

---

## 🎯 1. C'est quoi "(RE)Sources Relationnelles" ?

C'est une plateforme web et mobile (une application sur votre téléphone) conçue comme une **grande bibliothèque d'entraide et d'échange citoyen**. 
Son but principal ? Aider les gens à améliorer leurs relations humaines, que ce soit en famille, en couple, entre amis ou entre collègues.

Au lieu de garder de bons conseils pour soi, chacun peut **partager des "Ressources"**. Une ressource, c'est un contenu qui aide à mieux communiquer. Ça peut prendre plusieurs formes :
* Un article texte (des astuces, une expérience vécue).
* Une vidéo.
* Un document PDF.
* Un mini-jeu de rôle pour s'entraîner.

---

## 👥 2. Qui peut utiliser la plateforme ?

Il y a 3 grands types d'utilisateurs avec des "pouvoirs" différents :

1. **Les Visiteurs (Vous et moi, sans compte)**
   * On peut se balader sur le site.
   * On peut lire les ressources publiques, faire des recherches, consulter les catégories.
   * *Si on veut participer (publier, aimer, commenter), il faut créer un compte gratuit.*

2. **Les Membres Inscrits (Rôle Utilisateur)**
   * On peut publier nos propres ressources pour les partager au monde.
   * On peut commenter les publications des autres.
   * On peut **"Liker"** (Aimer), mettre en **"Favori"** (Coup de cœur) ou **"Sauvegarder pour plus tard"** n'importe quelle ressource.
   * On a accès à un grand **"Tableau de Progression"** : l'application retient notre historique et nous "récompense" chaque fois qu'on participe (ex: +1 point parce qu'on a publié, +1 point car on a aimé un article).
   * On peut discuter en temps réel avec d'autres utilisateurs dans des **Chatrooms** thématiques.

3. **Les Modérateurs & Administrateurs (La Police et les Patrons)**
   * Ils vérifient tout ce qui est publié. Quand un membre poste une nouvelle ressource, elle est d'abord cachée du public et affichée "En attente". C'est l'administrateur qui clique sur "Valider" pour qu'elle devienne visible de tous.
   * Ils peuvent supprimer des commentaires inappropriés ou suspendre des ressources.
   * Ils ont accès à un **"Back-Office"** : une salle de contrôle avec des statistiques complètes sur le site (qui a publié quoi, combien de visites, etc.).

---

## 🗺️ 3. Comment est rangé le contenu ?

Pour éviter que toutes les ressources soient mélangées, elles sont triées de deux grandes manières :

* **Les Catégories** : De quoi ça parle ? (ex: Communication, Gestion de conflit, Confiance en soi...).
* **Les Types de Relations** : Pour qui c'est utile ? (ex: Pour la Famille, Pour le Couple, Pour les Amis...).

Quand vous cherchez de l'aide sur le site, vous pouvez filtrer. Par exemple : *"Je veux lire un article sur la [Gestion de conflit] avec mes [Collègues]"*.

---

## ⚙️ 4. Les fonctionnalités magiques (Ce qu'on peut faire)

### A. La Publication
Il y a un gros bouton pour publier. Vous tapez votre titre, votre texte, vous ajoutez éventuellement une image ou une vidéo, vous choisissez la catégorie et plouf ! C'est envoyé... mais pas directement publié ! Cela part d'abord en vérification chez les modérateurs. Vous pouvez d'ailleurs suivre l'état de vos propres publications (si elles sont validées ou en attente) sur votre profil.

### B. Le Profil et la "Progression"
La plateforme vous motive à être actif. Dans votre menu, vous avez "Ma progression". C'est un résumé de tout ce que vous avez accompli le mois dernier (les articles que vous avez aimés, les commentaires laissés). C'est un peu comme un carnet de bord personnel qui montre votre investissement dans votre bien-être relationnel. En plus, vous y retrouvez vos favoris (pratique pour retrouver cet article génial lu la semaine dernière !).

### C. Les Chats (Conversations en direct)
Sous chaque ressource validée ou chaque jeu proposé, la plateforme peut générer un salon de discussion (une "ChatRoom"). C'est une page type "WhatsApp/Discord" où les membres connectés peuvent taper des messages, poser des questions sur l'article et se répondre instantanément.

---

## 📱 5. Pourquoi y a-t-il aussi une application Mobile (Flutter) ?

Ce projet n'est pas qu'un site internet, c'est aussi une application qui se télécharge sur smartphone. Pour que le téléphone puisse "discuter" avec la base de données du site web (pour afficher vos favoris sur votre téléphone par exemple), la plateforme possède un **"API"**. 

Imaginez l'API comme un serveur dans un restaurant :
1. L'application mobile (le client) dit : *"Hé le serveur (API), donne-moi la liste des catégories !"*
2. Le serveur va en cuisine (la base de données du site web), récupère les catégories, et les ramène à l'application mobile sous un format informatique très propre (qu'on appelle du JSON).

Ainsi, quand vous "*Zappez*" un article, ou cliquez sur le bouton "*Favori*" depuis l'appli sur votre téléphone, le téléphone prévient l'API, qui va instantanément cocher la case dans la base de données. Plus tard, si vous vous connectez sur le site web depuis votre gros ordinateur PC, vous y verrez votre favori synchronisé ! Magique, non ?

---

## 🎨 6. Le Look and Feel (L'ambiance du site)

Le site a été travaillé pour être **splendide, doux, et moderne** (ce qui est important pour parler de relations humaines).
* Utilisable parfaitement sur PC comme sur téléphone portable.
* Navigation ultra-fluide avec des menus tiroirs pour les petits écrans.
* Il possède un bouton "Soleil/Lune" pour basculer de **Mode Clair (Light)** à **Mode Sombre (Dark)**, idéal pour lire sans s'abîmer les yeux le soir.
* De légers effets de transparence (comme des cartes en verre givré) et de douces animations au passage de la souris le rendent vivant.

---

*Fin du guide. Vous avez maintenant toutes les bases pour naviguer sereinement ou tester l'intégralité des possibilités sociales et solidaires de **(RE)Sources Relationnelles** !*
