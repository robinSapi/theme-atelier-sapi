# Tasks — Coordination Cowork ↔ Claude Code

## 📋 À faire

## ✅ Terminées

- **Nettoyage fallbacks anciens champs ACF photos** (2026-04-08) — Suppression des références aux champs `bandeau`, `ambiance_1/2/3`, `detail_1/2`, `tailles`. 4 fichiers modifiés :
  - [functions.php](../functions.php) `sapi_get_product_photo_ids()` : suppression du bloc `else { ... old fixed fields fallback ... }` (le repeater `galerie_produit` est désormais la seule source).
  - [front-page.php](../front-page.php) (collections homepage) : suppression du lookup `get_field('bandeau', $pid)`. Image collection = vignette du produit prioritaire (suspension Vincent / accessoire Ampoule).
  - [page-la-star-du-moment.php](../page-la-star-du-moment.php) : suppression de `$bandeau` et de l'entrée `Bandeau` dans `$acf_candidates` du carousel galerie. Reste : ambiance + détail (depuis le repeater).
  - [woocommerce/taxonomy-product_cat.php](../woocommerce/taxonomy-product_cat.php) : suppression du fallback `bandeau` dans le bloc « coup de cœur ». Source unique = `sapi_get_product_photos($pid, 'ambiance', 1)`.
  - **Note :** les occurrences de `'bandeau'` qui restent dans `functions.php` (lignes ~2532, ~4818, ~4880) appartiennent au **nouveau système Robin Conseiller** (contexte d'ouverture du bandeau dual-mode) et sont conservées.
  - **Diff :** +4 / −45 lignes. Branche `test-theme-sapi-maison`.

- **Bandeau dual-mode : réassurance + Robin Conseiller** (2026-04-08) — Bandeau `#robin-bandeau` transformé en deux modes, validé par Robin après 4 itérations. Branche `test-theme-sapi-maison`, commits `45104da` → `c82a3c4`.
  - **Mode repos** (par défaut, aucun label projet en localStorage) : 4 items réassurance statiques (Livraison 48-72h, Fabrication <5j, Retours 30j, Paiement sécurisé) + badge wood **« Démarrer mon projet »** en 5ème position, le tout centré sur la largeur du bandeau. Hover du bandeau → badge passe en orange pour signaler l'interactivité. Scope CSS `var(--color-warm)` + border-bottom wood/0.1, font-size 11px, icônes wood.
  - **Mode projet** (au moins un `state.labels[step]`) : badge `var(--color-wood)` « Mon projet » + chips résumant les réponses + flèche, comme avant.
  - **Toggle des modes** : classes explicites `.robin-bandeau--mode-repos` (par défaut, mise en PHP) et `.robin-bandeau--mode-projet` (basculée par `updateBandeauChips()` dans `assets/robin-conseiller.js`). Plus prévisible que la première version qui utilisait un toggle implicite `has-project` — Robin avait constaté un bug d'affichage lors du premier essai.
  - **Clic** : n'importe où dans le bandeau ouvre la modale Robin Conseiller via le câblage existant `#robin-bandeau` → `openModal('bandeau')`. Pas de JS supplémentaire à câbler pour le badge.
  - **Mobile ≤600px** : `flex-wrap` actif, le badge a `flex-basis: 100%` pour s'isoler sur sa propre ligne (centré, `max-width: max-content`) sous 2 items réassurance tirés au sort. Tirage au sort par `randomizeMobileReassurance()` (Fisher-Yates) qui ajoute la classe `.is-mobile-hidden` au chargement.
  - **Fichiers modifiés** : [inc/template-robin-bandeau-v2.php](../inc/template-robin-bandeau-v2.php) (template restructuré en deux blocs `__repos` / `__projet`), [style.css](../style.css) (bloc `.robin-bandeau` lignes ~1551-1670 + media query mobile), [assets/robin-conseiller.js](../assets/robin-conseiller.js) (`updateBandeauChips`, `randomizeMobileReassurance`).
  - **Itérations notables** : (1) première version avec toggle `has-project` → bug d'affichage chez Robin, (2) refonte avec classes `--mode-repos` / `--mode-projet` explicites → OK, (3) centrage parfait des items via CTA en `position: absolute` desktop, (4) finale : remplacement du CTA texte par un badge wood « Démarrer mon projet » en 5ème position, layout simplifié sans positionnement absolu.

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
