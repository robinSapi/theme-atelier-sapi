# Tasks — Coordination Cowork ↔ Claude Code

## 📋 À faire

*(rien en attente)*

## ✅ Terminées

- **Bandeau dual-mode : réassurance + Robin Conseiller** (2026-04-08) — Bandeau `#robin-bandeau` transformé en deux modes :
  - **Mode repos** (par défaut, aucun label projet en localStorage) : 4 items réassurance statiques (Livraison 48-72h, Fabrication <5j, Retours 30j, Paiement sécurisé) centrés, scope CSS `var(--color-warm)` + border-bottom wood/0.1, font-size 11px, icônes wood. À droite, CTA discret « Trouver mon luminaire › » séparé par border-left.
  - **Mode projet** (au moins un `state.labels[step]`) : badge `var(--color-wood)` + chips résumant les réponses + flèche, comme avant. Toggle via classe `.has-project` ajoutée par `updateBandeauChips()` dans `assets/robin-conseiller.js`.
  - Le clic sur le bandeau (déjà câblé via `#robin-bandeau` → `openModal('bandeau')`) reste inchangé : ouvre la modale en mode `bandeau` (step 1 si pas de projet, résumé si projet).
  - Mobile ≤600px : nouvelle fonction `randomizeMobileReassurance()` (Fisher-Yates) masque 2 items au hasard via classe `.is-mobile-hidden` au chargement.
  - Fichiers modifiés : [inc/template-robin-bandeau-v2.php](../inc/template-robin-bandeau-v2.php), [style.css](../style.css) (bloc `.robin-bandeau`), [assets/robin-conseiller.js](../assets/robin-conseiller.js) (`updateBandeauChips`, `randomizeMobileReassurance`).

*(purgé le 8 avril 2026)*

- Robin Conseiller — 5 variations aléatoires pour le texte d'accueil de la première fiche. Commit `4495546`.
- Robin Conseiller — Inversion label/dim question Taille : « Petite pièce / Pièce standard / Grande pièce » + sous-titres « intime / confortable / spacieuse ». Commit `4e7b0b9`.
- Robin Conseiller — Labels Taille reformulés + 4e choix « Je ne sais pas » (slug `ne-sais-pas`, no filtre taille, Sortie déclenchée). Commit `43cf475`, branche `test-theme-sapi-maison`.

*(purgé le 3 avril 2026 — tâches du jour validées)*

- Optimisation SEO homepage — titres H1/H2/H3 homepage réécrits + ajustements Robin. 3 commits (`0e19aa7`, `5a3687b`, `b33e0be`), mergé dans master (`78d54ca`). **Déployé en prod** — Robin doit lancer le workflow GitHub Actions.
  - H1 : « Luminaires en bois · Atelier Sâpi »
  - H2 : « Fabriqués à la main, à la commande, dans mon atelier Lyonnais ! »
  - H2 storytelling : « Des créations imaginées et fabriquées avec passion »
  - H3 process : « Mon processus artisanal » (guillemets retirés)

- Réordonnancement page produit + intégration photo client — commit `e04e55d`
- Lightbox galerie produit — redesign complet (overlay flottant, swipe mobile) — commit `3e22522`
- Remplacement ACF "Phrase d'accroche" → description courte WooCommerce — commit `5a05513`, déployé en prod
- Refonte section "L'histoire de" → "Détails" + colonne droite ACF Descriptif — commit `b3924fe`
- Fiche produit accessoire — pill Robin masquée, fiche technique masquée, ratio Détails 2fr/1fr — commit `d2386a1`
- Mise à jour textes pages catégories (intro hero + éditorial) depuis fichier de référence — commit `b6c2581`, déployé en prod
