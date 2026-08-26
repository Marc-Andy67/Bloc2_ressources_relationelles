# 🌗 Implémentation du Mode Jour/Nuit (Dark Mode)

Ce document détaille l'architecture complète du mode "Sombre" implémenté sur l'application Web, depuis le choix de framework jusqu'à l'expérience utilisateur persistante. L'implémentation repose sur le triptyque **Tailwind CSS + JavaScript natif + LocalStorage**.

---

## 1. La fondation : Tailwind CSS en mode `class`

Dans un projet Tailwind classique, le mode sombre peut écouter automatiquement le système d'exploitation de l'utilisateur. Nous avons cependant choisi d'aller plus loin pour donner un **contrôle manuel** à l'utilisateur via un bouton.

Pour ce faire, dans notre fichier `tailwind.config.js`, j'ai activé la stratégie par classe :
```javascript
module.exports = {
    darkMode: 'class', // <--- C'est cette ligne qui fait la magie
    // ...
}
```

**Pourquoi ce choix technique ?**
Cela demande à Tailwind de ne compiler et de n'activer les classes CSS commençant par `dark:` (ex: `dark:bg-slate-900`) que si **et seulement si** la balise parente `<html>` possède la classe `.dark`. Cela nous permet de piloter l'allumage des ombres avec un simple script JavaScript !

---

## 2. L'écriture du CSS : La règle du "Mobile First" et "Light First"

Tout le développement front-end a été pensé de base pour le thème clair. Tailwind utilise les préfixes `dark:` pour appliquer la substitution esthétique.

Exemple direct sur notre fondation HTML :
```html
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white">
```
*   **Par défaut :** le site affiche un fond blanc léger (`bg-slate-50`) et un texte gris très foncé (`text-slate-900`).
*   **Dès que `<html class="dark">` est injecté :** Tailwind bypasses les premières couleurs et force le remplacement par l'apparence sombre (`dark:bg-slate-900` et texte en blanc `dark:text-white`).

Ceci est appliqué partout : des bordures lumineuses (`border-slate-200 dark:border-slate-700`) jusqu'aux palettes de formulaires de notre `app.css`.

---

## 3. La prévention de l'effet "Flash" Blanc (FOUC)

Un bug critique très récurrent avec les modes sombres est le "FOUC" (Flash of Unstyled Content). Si l'utilisateur a enregistré qu'il aime le fond noir, mais que la page charge son CSS en blanc pendant 0.5 seconde avant que le script Javascript n'ait le temps de lire ce paramètre, cela crée un flash très désagréable (effet stroboscopique) qui abîme les yeux.

**La solution appliquée :** J'ai placé un micro-script **tout en haut** de notre fichier `base.html.twig`, directement dans le `<head>` juste après le chargement du cache :

```javascript
/* Script bloquant (synchrone) exécuté avant même l'affichage de la page */
if (localStorage.getItem('color-theme') === 'dark' || 
    (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
    document.documentElement.classList.add('dark');
} else {
    document.documentElement.classList.remove('dark');
}
```
Ce script analyse d'abord la mémoire du navigateur, puis regarde la préférence du système d'exploitation Windows/Mac/Android (`prefers-color-scheme`). Si le mode sombre est détecté, injecte instantanément `.dark` avant qu'un seul pixel ne soit peint à l'écran. C'est robuste et confortable.

---

## 4. Le script de basculement (Le Toggle)

Pour permettre au visiteur de changer dynamiquement ce thème, j'ai implémenté le fameux "bouton lune/soleil" sur notre grand menu de navigation.
Ce script, localisé à la fin de notre `base.html.twig`, a dû être adapté pour fonctionner avec **Symfony Turbo** (qui remplace le classique `document.addEventListener('DOMContentLoaded')`).

**Le workflow lors du clic Utilisateur :**
1. L'utilisateur clique sur l'icône de lune (`#theme-toggle`).
2. Le javascript fait basculer la visibilité de l'icône lune vers l'icône soleil (`classList.toggle('hidden')`).
3. Il inspecte la structure du document. S'il trouve `<html class="dark">`, il le supprime. S'il ne le trouve pas, il le rajoute.
4. **L'étape clé pour la mémoire (Persistance) :** Le script écrit le mot `'dark'` ou `'light'` dans le coffre-fort `localStorage` de l'ordinateur. 

Ainsi, si l'internaute ferme son onglet et revient sur notre site trois jours plus tard, la fonction du `<head>` (voir section 3) relira ce `localStorage` et le connectera sans douleur avec l'Interface exacte qu'il avait personnalisée !

---

## 📈 Résumé de la Soutenance (Si vous êtes interrogé)

- **Approche Utilisée :** Ajout de classe HTML parente (`.dark`) piloté par Javascript.
- **Pourquoi pas le mode `media` Système OS automatique de Tailwind ?** Parce qu'il empêche le visiteur de forcer un thème spécifique s'il veut un mode clair à midi sur un téléphone configuré par défaut en mode nuit. Le choix l'emporte toujours.
- **Stockage Mémoire ?** Enregistrement dans l'Object natif navigateur `localStorage` sous la clé `color-theme`. La persistance ne nécessite dont aucune base de données ni profil utilisateur ! Le mode nuit fonctionne même pour les invités déconnectés.
