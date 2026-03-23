# 🚀 Guide d'Installation Complet : (RE)Sources Relationnelles

Ce guide est fait pour vous accompagner pas-à-pas dans l'installation du projet sur un ordinateur **complètement vierge** (Windows, Mac ou Linux), n'ayant aucun outil de développement web installé au préalable. 

L'objectif est que vous puissiez lancer le projet et le tester localement (sur votre propre machine) en moins de 15 minutes.

---

## 🛠️ Partie 1 : Préparer votre Ordinateur (Les fondations)
Avant de lancer le site, votre ordinateur a besoin de comprendre trois langages/outils : **PHP**, **MySQL** (pour la base de données), et **Composer** (le gestionnaire de paquets PHP).

### Option Recommandée sous Windows : Installer Laragon ou XAMPP
Le plus simple pour tout avoir d'un coup (PHP + MySQL), c'est d'installer un logiciel "tout-en-un".
1. **Téléchargez Laragon** (Full version) sur [laragon.org/download](https://laragon.org/download/) ou **XAMPP** sur [apachefriends.org](https://www.apachefriends.org/).
2. Lancez l'installation classique (Suivant > Suivant).
3. Ouvrez le logiciel et cliquez sur **"Start All"** (Démarrer Tout). 
> *Félicitations, votre ordinateur possède maintenant une base de données locale (serveur MySQL) et le langage PHP !*

### 1. Installer Composer (Le livreur de paquets)
C'est l'outil qui va télécharger toutes les dépendances de (RE)Sources Relationnelles.
* **Sous Windows** : Téléchargez et installez [Composer-Setup.exe](https://getcomposer.org/Composer-Setup.exe). Lors de l'installation, il vous demandera où est PHP (généralement dans `C:\laragon\bin\php\php-X.X.X\php.exe` ou `C:\xampp\php\php.exe`).
* **Sous Mac/Linux** : Suivez les lignes de commande sur [getcomposer.org](https://getcomposer.org/download/).

### 2. Installer Symfony CLI (Le chef d'orchestre)
C'est un petit exécutable qui permet de lancer un vrai faux-serveur web en 1 seconde.
* **Sous Windows** : Installez-le via ce lien : [Symfony CLI Windows Installer](https://github.com/symfony-cli/symfony-cli/releases/latest/download/symfony-cli-windows-amd64.exe) (ou via la commande Scope / Choco si vous êtes développeur).
* **Sous Mac/Linux** : Tapez dans un terminal : `curl -sS https://get.symfony.com/cli/installer | bash`

---

## 📥 Partie 2 : Récupérer et Installer le Projet

1. **Ouvrir votre Terminal** (Command Prompt / Powershell sur Windows, ou "Terminal" sur Mac/Linux).
2.  Si vous avez le code en dossier ZIP, décompressez-le. Si vous utilisez Git, clonez le projet. Puis naviguez dans le dossier avec votre terminal :
    ```bash
    cd chemin/vers/le/dossier/resources_relationnelles
    ```
3. **Installer les dépendances du projet** :
   Dans le terminal, à la racine du projet, lancez :
   ```bash
   composer install
   ```
   *Ce processus peut prendre une ou deux minutes, il télécharge tous les composants de Symfony.*

---

## 🗄️ Partie 3 : Configurer la Base de Données

Pour que le site puisse sauvegarder les utilisateurs ou les articles, il a besoin d'accéder à la base de données de votre Laragon ou XAMPP.

1. Allez à la racine du projet et **cherchez un fichier nommé `.env`**. (Ouvrez-le avec Notepad ou votre éditeur de code).
2. Cherchez la ligne qui commence par `DATABASE_URL=...`
3. Vérifiez qu'elle correspond à vos identifiants MySQL (par défaut sur XAMPP/Laragon, l'utilisateur est `root` avec aucun mot de passe).
   *Exemple à copier :*
   `DATABASE_URL="mysql://root:@127.0.0.1:3306/resources_relationnelles?serverVersion=8.0.32&charset=utf8mb4"`
4. Toujours dans votre terminal, **créez la base de données** :
   ```bash
   php bin/console doctrine:database:create
   ```
5. **Construisez toutes les tables** (Utilisateurs, Ressources, etc.) :
   ```bash
   php bin/console doctrine:migrations:migrate
   ```
   *Répondez "yes" si un avertissement apparaît.*

---

## 🔐 Partie 4 : Sécuriser l'API (Lexik JWT)
Le backend sert d'API au format JSON (pour l'application Flutter). Pour que la sécurité fonctionne, on doit générer deux clés de cryptage SSL.
Toujours dans le terminal web, tapez :
```bash
php bin/console lexik:jwt:generate-keypair
```
*Le système vient de créer une clé publique et une clé privée dans votre dossier `config/jwt`. Bravo, l'API est blindée !*

---

## 🧪 Partie 5 : Injecter les Données de Test (Fixtures)
Tester un site vide, ce n'est pas très amusant. Le projet inclut un robot qui génère des faux utilisateurs de test (Admin, Modérateur, Utilisateur régulier).
```bash
php bin/console doctrine:fixtures:load
```
Répondez `yes`. Votre base de données contient désormais les 3 comptes vitaux :
* **Administrateur** 👉 `admin@test.com` (Mot de passe : `password`)
* **Modérateur** 👉 `moderator@test.com` (Mot de passe : `password`)
* **Utilisateur Normal** 👉 `user@test.com` (Mot de passe : `password`)

---

## 🚀 Partie 6 : Allumage Moteur !

*Assurez-vous que votre base MySQL (Laragon/XAMPP) tourne toujours en fond.*

1. **Lancez le serveur Web local Symfony** :
   ```bash
   symfony serve -d
   ```
2. **Compilez le design visuel Tailwind CSS** (obligatoire pour avoir un beau site) :
   ```bash
   php bin/console tailwind:build
   ```
3. **Appréciez le résultat** ! 
   Ouvrez votre navigateur web préféré (Chrome, Firefox, Safari) et allez à l'adresse suivante :
   👉 **`http://localhost:8000`** ou **`https://127.0.0.1:8000`**

Vous pouvez dès à présent cliquer sur "Connexion", entrer `admin@test.com` et le mot de passe `password` pour tester l'interface de modération, écrire des commentaires, et naviguer sur l'ensemble de (RE)Sources Relationnelles !

*(Pour stopper le serveur plus tard dans votre terminal, tapez simplement `symfony server:stop`)*.
