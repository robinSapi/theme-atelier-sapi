---
name: Préférences design heroes et overlays
description: Heroes sombres avec overlay marron bois, overlays opaques préférés sur sections texte Artisan
type: feedback
---

**Heroes du site : overlay marron bois 0.75 + texte blanc**
Les 4 heroes principaux (Nos créations, Artisan, Conseils, Contact) utilisent un overlay `rgba(74, 63, 53, 0.75)` (couleur --color-wood-dark) au lieu de noir. Robin préfère cette teinte chaude cohérente avec la charte.

**Why:** Robin a demandé explicitement un overlay marron bois plutôt que noir ("la couleur de la charte !"), puis a augmenté l'opacité de 0.35 → 0.5 → 0.75 pour un rendu plus sombre.

**How to apply:** Pour tout nouveau hero avec fond photo, utiliser `rgba(74, 63, 53, 0.75)` comme overlay.

---

**Sections texte Artisan (intro + citation) : overlay opaque préféré**
Robin a rejeté le remplacement de l'overlay opaque (0.95/0.88) par un gradient crème semi-transparent sur les sections "Créer c'est tout ce que j'adore" et la citation. Le commit a été reverté.

**Why:** "c'était mieux avant" — l'overlay quasi-opaque rend le texte plus lisible et donne un look plus clean sur ces sections de contenu.

**How to apply:** Ne pas changer les overlays des sections texte de la page Artisan. Le pattern éditorial semi-transparent (comme formulaire Sur-mesure ou catégories) ne convient pas partout.

---

**Contact hero : conteneur large pour H1 sur une ligne**
Plutôt que réduire la taille du H1, Robin préfère élargir le conteneur (max-width 800px → 1200px) + white-space: nowrap.

**Why:** "pourquoi ne pas avoir élargi la zone de texte ?" — Robin veut garder l'impact visuel du grand titre.

**How to apply:** Si un titre hero déborde, d'abord envisager d'élargir le conteneur avant de réduire la taille de police.
