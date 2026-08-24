# 🎨 Palette de Couleurs (UI & RGAA)

Voici les codes couleurs hexadécimaux exacts utilisés sur le projet. Ces couleurs ont été spécialement choisies (et ajustées par rapport à la palette Tailwind par défaut) pour respecter un taux de contraste suffisant (Niveau AA / AAA selon les normes RGAA) entre le fond et le texte.

## 🔵 Couleurs Principales (Marque & Interactions)

Ces couleurs dirigent l'attention de l'utilisateur sur les actions primaires (liens, boutons centraux).

| Nom de la variable | Code Hexadécimal | Visuel / Tailwind proche | Utilisation principale |
| :--- | :--- | :--- | :--- |
| **Primary** | `#1D4ED8` | Bleu Foncé (`blue-700`) | Boutons principaux, liens actifs, header. Grosse garantie de contraste sur fond clair. |
| **Secondary** | `#0369A1` | Bleu Ciel Assombri (`sky-700`) | Effets de survol (hover) sur les boutons primaires. |
| **Call To Action (CTA)** | `#C2410C` | Orange Foncé (`orange-700`) | Utilisé spécifiquement pour l'anneau de tabulation clavier (le fameux `focus-visible`). L'orange permet de trancher radicalement avec l'esthétique globalement bleue du site pour que la sélection au clavier soit immanquable. |

## 📐 Fonds et Textes (Structure)

On évite le Noir pur (`#000000`) et le Blanc pur (`#FFFFFF`) car ils fatiguent les yeux (éblouissement). On utilise à la place les teintes "Ardoise" (Slate) de Tailwind.

### Mode Clair (Light Mode)
*   **Fond principal (Background Light) :** `#F8FAFC` (`slate-50`)
    *   *Un gris/bleu extrêmement clair, doux pour la lecture prolongée.*
*   **Texte principal (Text Light) :** `#0F172A` (`slate-900`)
    *   *Un gris anthracite très profond qui offre un contraste maximal (AAA).*

### Mode Sombre (Dark Mode)
*   **Fond principal (Background Dark) :** `#0F172A` (`slate-900`)
    *   *Le fond absorbe la lumière tout en restant ancré dans des tons bleu/gris naturels.*
*   **Texte principal (Text Dark) :** `#F8FAFC` (`slate-50`)

## 🚦 Couleurs Sémantiques (Statuts et Tags)

Pour les badges de catégorie, les statuts de ressources et les actions secondaires, la palette standard de Tailwind est invoquée dynamiquement :

*   ✅ **Validée / Succès :** Vert Émeraude (`emerald-700` et `emerald-50`)
*   ⏳ **En attente / Brouillon :** Ambre (`amber-700` et `amber-50`)
*   ❌ **Refusée / Erreur :** Rose Rouge (`rose-700` et `rose-50`)
*   🏷️ **Catégories / Tags :** Indigo (`indigo-700`) ou Violet (`violet-700`)

*Note pour l'oral:* Précisez bien que l'utilisation de ces couleurs de "statuts" n'est jamais la seule information véhiculée (Règle RGAA). Un badge valide est vert, certes, mais il y a toujours un texte explicite "Publiée" ou une icône à l'intérieur du badge, pour que les daltoniens ne se fient pas uniquement à la perception de la teinte.
