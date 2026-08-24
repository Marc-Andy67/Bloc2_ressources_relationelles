# ♿ Diapositive & Récapitulatif : Accessibilité (RGAA) et Front-End

Ce document est une antisèche parfaite pour préparer vos diapositives (PowerPoint / Canva) concernant le front-end et la conformité au Référentiel Général d'Amélioration de l'Accessibilité (RGAA).

---

## ⚠️ Point d'attention : DaisyUI vs Tailwind CSS Custom

**Important pour votre oral :** Le projet n'installe et n'utilise finalement **pas DaisyUI**. 
Les boutons, cartes et formulaires que l'on voit (les classes comme `.btn-primary`, `.glass-card`, `.input-field`) sont des **composants CSS Custom** créés de toutes pièces et compilés directement à l'aide de `@apply` de Tailwind dans notre fichier `assets/styles/app.css`. 

*L'avantage à défendre :* Ne pas utiliser DaisyUI nous a permis de réduire le poids du CSS final, d'avoir un design 100% unique (notamment le système d'ombres *glassmorphism* et les animations de survol) et surtout de garder un **contrôle total sur l'accessibilité** et les contrastes, là où les bibliothèques prêtes à l'emploi ont parfois des défauts natifs.

---

## 🎯 Ce qui a été mis en œuvre pour l'Accessibilité (RGAA)

Si le jury demande : *"Qu'est-ce que vous avez fait concrètement pour les personnes en situation de handicap (malvoyants ou avec des troubles moteurs) ?"*, voici exactement tout ce qui est codé en dur dans le projet :

### 1. ⌨️ Navigation au Clavier (Tabulation experte)

Pour les personnes qui ne peuvent pas utiliser de souris, le site est 100% navigable avec la touche `TAB` et les touches directionnelles.
*   **Focus différencié (Focus-Visible)** : On a utilisé partout sous Tailwind la pseudo-classe `:focus-visible` au lieu de `:focus`.
    *   *L'explication technique :* Cela permet de n'afficher l'anneau de sélection bleu/jaune autour des boutons **que si** l'utilisateur navigue au clavier. S'il clique simplement avec la souris, l'anneau ne vient pas gâcher le design !
    *   *Code Tailwind utilisé :* `focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-cta` (CTA désigne un anneau très contrasté de notre palette).

### 2. 🤖 Soutien aux Lecteurs d'Écran (Screen Readers)

Pour les personnes malvoyantes qui utilisent des synthèses vocales comme *Jaws*, *NVDA*, ou *VoiceOver* :

*   **Le mode "Screen Reader Only" (`.sr-only`)** : Certains éléments très importants pour comprendre le contexte, mais qui alourdiraient le design visuel, sont "cachés" à l'oeil, mais lus par les synthèses.
    *   *Exemple :* Le nombre total de ressources sur la page de recherche est indiqué textuellement `<span class="sr-only">12 ressources trouvées.</span>`.
    *   *Exemple 2 :* Le bouton du chat room affiche juste une icône, mais contient le texte `<span class="sr-only">Envoyer</span>` pour la machine.
*   **Les rôles sémantiques WAI-ARIA** : 
    *   Le menu mobile indique `aria-expanded="false"` puis `true` pour que l'aveugle "entende" si le menu ouvrant est déployé ou non.
    *   Les formulaires de recherche ont tous le rôle explicite `role="search"`.
    *   Le bouton permettant de changer le mode Sombre à un rôle `aria-pressed="false"`.

### 3. 🎨 Gestion des "Bruits Visuels" (Décoration)

*   **`aria-hidden="true"` sur les SVG** : Toutes les icônes visuelles purement décoratives de la bibliothèque Heroicons (l'icône Loupe, l'icône Maison, Soleil...) possèdent ce tag HTML. Ainsi, le lecteur d'écran saute poliment par-dessus sans assommer l'utilisateur en lui épelant "Ligne graphique vecteur 24 par 24 pixels".

### 4. 🔗 Contextualisation des Liens

On ne met jamais simplement un lien sur une div contenant des cartes. Sur la page d'accueil ou de recherche, chaque bouton porte un label aria ultra-contexte.
*   *Mise en pratique :* Au lieu que la machine lise un bête "Lire la suite", notre lien invisible génère au moteur : `aria-label="Voir les détails de la ressource Comment Gérer son Stress"`.

### 5. 🔔 Annonces en Direct (Aria-Live)

Lorsqu'on effectue une recherche qui ne donne aucun résultat, la zone vide du site web contient le tag `<div role="status" aria-live="polite">` (ou aria-atomic). 
*   *Son utilité :* Lorsqu'une information surgit à l'écran sans que la page web ait eu besoin de se recharger (Javascript ou Turbo), le lecteur vocal s'interrompt pour informer vocalement la personne qu'un changement dynamique vient de se produire.

### 6. 🖤 Contrastes et "Dark Mode" Sémantique

*   La palette Tailwind a été minutieusement choisie parmi les classes `slate-900` pour le texte et `white` en fond (sans tomber dans du gris peu contrasté RGAA).
*   L'intégration d'un mode Sombre (`dark:bg-slate-900 dark:text-white`) n'est pas juste "esthétique", c'est une composante phare de l'accessibilité cognitive pour réduire l'éblouissement.

---

## 📝 Résumé Rapide de votre Diapo

**Titre de la Diapositive : Engagement RGAA & UI**
- 🎨 **Stack UI :** Tailwind CSS Custom (Pas de surcouche DaisyUI pour un contrôle fin du code source)
- ⌨️ **Mobilité :** Navigation complète via tabulation avec Focus Ring interactif (`:focus-visible`)
- 🔊 **Non-voyance :** Complétude des fiches ARIA (`role`, `aria-label`, balises sémantiques `nav` / `article`)
- 🤖 **Confort vocal :** Ignorance volontaire des décorations (`aria-hidden`) pour limiter la charge mentale
- 💡 **Confort visuel :** Contraste calibré, Design Glassmorphism épuré et basculement en mode sombre inclus.
