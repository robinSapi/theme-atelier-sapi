# Tasks — Coordination Cowork ↔ Claude Code

## 🔧 À faire

## ✅ Livré

## [RETOUR] Conseiller V3 — Round 2 : 17 bugs livrés sur 6 chantiers
**Date livrée :** 2026-05-22
**Branche :** `test-theme-sapi-maison`
**Commits :** `a29ae5a` (5) → `9f6d8ec` (1) → `0fb0722` (4) → `49cdbda` (2) → `bcbc37d` (3)
**Statut :** 16 fixes appliqués + 1 rapport (Chantier 6). Pas d'aller-retour 4.1 (diagnostic code-only).

### Récap par chantier

| Chantier | Bugs | Commit | Statut |
|---|---|---|---|
| 5 — UX wording | 5.1 + 5.2 | `a29ae5a` | ✅ |
| 1 — Prompts IA | 1.1 + 1.2 + 1.3 + 1.4 | `9f6d8ec` | ✅ |
| 4 — Freetext + catalogue | 4.1 + 4.2 + 4.3 (check) | `0fb0722` | ✅ |
| 2 — Filtre JS | 2.1 + 2.2 + 2.3 | `49cdbda` | ✅ |
| 3 — Modale JS | 3.1 + 3.2 + 3.3 + 3.4 | `bcbc37d` | ✅ |
| 6 — Investigation N8 | 6.1 | — | 📝 rapport ci-dessous |

### Chantier 4.1 — Diagnostic freetext (sans error_log)

**Décision Robin** dans la spec : *"investigue + fixe d'un coup (pas d'aller-retour)"*. J'ai donc fait le diagnostic à l'aveugle code-only, qui s'est avéré sans ambiguïté possible — pas besoin du log d'extraction Haiku pour identifier la cause.

**Cause root identifiée :**

Test C1 ("Je cherche une applique pour ma chambre") → Haiku extrait `{piece:'chambre', sortie:'mur'}`. Côté JS, `submitFreetext` appelle `applyFiltersBatch(filters)` qui termine par `cleanInvisibleAnswers()`. Cette fonction calcule les steps visibles selon `state.answers` :

- Visibilité de `sortie` dans `inc/guide-data.php` (avant fix) :
  ```php
  'visibility' => ['_or' => [
    ['taille' => ['petite', 'moyenne', 'ne-sais-pas']],
    ['eclairage' => ['principal', 'secondaire']],
    ['piece' => ['escalier']],
  ]],
  ```
- Avec `{piece:'chambre', sortie:'mur'}` : aucune des 3 clauses n'est satisfaite (taille absent, eclairage absent, piece='chambre' ≠ 'escalier') → `sortie` n'est PAS visible → **`cleanInvisibleAnswers` supprime `sortie=mur` en silence**.

Résultat : `state.answers = {piece:'chambre'}`, `sapiProject.update` reçoit juste la pièce, la grille filtre uniquement sur `piece` → tous types de luminaires affichés au lieu des appliques seules.

**Fix appliqué en 2 axes :**

1. **Visibility relaxée** (`inc/guide-data.php`) : 4e clause OR sur `piece ∈ [cuisine, bureau, salon, chambre, entree, escalier]`. `sortie` reste visible dès qu'une pièce est connue. Le parcours linéaire (piece → taille → sortie) n'est pas affecté car STEPS reste traversé dans l'ordre du tableau. Vérifié mentalement : après piece=chambre, `getVisibleStepIds` retourne `[piece, taille, sortie, style]` → la prochaine après piece est bien `taille`, pas `sortie`.

2. **Prompt freetext renforcé** (`sapi_megafilter_build_freetext_prompt`) :
   - 3 voies explicites (standard / incomplet / hors-norme)
   - Exemples de déductions métier intégrés au prompt :
     - "applique" → `sortie=mur`
     - "suspension" → `sortie=plafond`
     - "lampadaire" / "lampe à poser" → `sortie=pas-de-sortie`
   - Support `action: contact` pour projets pro / sur-mesure / multi-luminaires
   - Backend propage `action` au front, JS lock le chat si `action=contact`

**Échantillon de prompt freetext :** non capturé (pas de PHP CLI dispo). Si tu veux valider la sortie Haiku après le fix, ajoute temporairement un `error_log($parsed)` à la ligne 2876 de functions.php, fais un test C1, et envoie-moi le log cPanel — je vérifie.

### Chantier 6 — Mini-rapport N8 (bureau + sortie=ne-sais-pas)

**Hypothèse retenue (cause #2 dans la spec) :** asymétrie entre `cats_by_sortie['ne-sais-pas']` et `cats_secondaire_by_sortie['ne-sais-pas']` dans `functions.php` L325-338 :

```php
'cats_by_sortie' => [
  ...
  'ne-sais-pas'   => ['suspensions', 'lampadaires', 'lampesaposer'],  // ❌ pas d'appliques
],
'cats_secondaire_by_sortie' => [
  ...
  'ne-sais-pas'   => ['lampadaires', 'lampesaposer', 'appliques'],     // ✅ inclut appliques
],
```

**Constat :** en éclairage **principal** + sortie=ne-sais-pas, les appliques sont exclues. En éclairage **secondaire** + sortie=ne-sais-pas, elles sont incluses. Sémantiquement, si le visiteur "ne sait pas où installer", l'applique reste possible dans les 2 cas (via le kit prise électrique mentionné dans `regles.txt:37` et `savoir.txt:48`).

**Reproduction mentale du scénario bureau** : `{piece:'bureau', taille:'moyenne', sortie:'ne-sais-pas'}` → `getAcceptedCategories` retourne `['suspensions', 'lampadaires', 'lampesaposer']` (sortie=ne-sais-pas, eclairage absent, pas en escalier) → filtre ampoule bureau = `['ampoule_degagee', 'semi_degagee']`. Le pool est étroit : suspensions ou lampadaires ou lampes à poser, ET ampoule_degagee ou semi_degagee. Selon le catalogue, peut donner 0-3 produits.

L'élargissement progressif (ordre `style, table, hauteur, eclairage, taille, piece`) **n'élargit jamais `sortie`** (intentionnel d'après le commentaire L209-211 de `sapi-cards-conseiller.js`). Donc même à l'étape la plus large (sans piece), on reste sur les 3 catégories ne-sais-pas. Si le catalogue manque de produits dans ces 3 catégories avec ampoule dégagée/semi-dégagée, on tombe à 0.

**Hypothèse moins probable mais à noter :** un bug dans l'enchaînement filtre principal + filtre secondaire pour `eclairage=secondaire` — mais ce n'est pas le cas N8 (bureau+taille=moyenne implique `eclairage` non visible, donc absent, donc branche principal).

**Décision proposée à Robin :**

Option A — ajouter `'appliques'` à `cats_by_sortie['ne-sais-pas']` (symétrie avec le secondaire) :
```php
'ne-sais-pas' => ['suspensions', 'lampadaires', 'lampesaposer', 'appliques'],
```
Effet : bureau+sortie=ne-sais-pas montrera aussi les appliques ampoule_degagee/semi_degagee. Cohérent avec le secondaire et avec le kit prise électrique disponible. Safe (n'exclut rien).

Option B — ne rien changer si tu considères que "ne sais pas où installer" doit pousser vers suspensions/lampadaires (objets faciles à placer) plutôt qu'appliques.

Je n'ai pas appliqué de fix automatique — la spec dit "décision Robin après lecture". Si tu veux Option A, dis-moi et je commit.

### Toute déviation par rapport à la spec (justifications)

- **3.2 commentaire mirror PHP** : la spec demande d'ajouter un commentaire *"MIRROR de sapiProject.cleanInvisibleAnswers"* dans `inc/guide-data.php`. Or il n'existe **aucune fonction PHP équivalente** à `cleanInvisibleAnswers` — le PHP fait juste de la validation contre la whitelist via `sapi_megafilter_sanitize_project` (functions.php:3124), qui ne calcule pas la visibility tree. Pas de duplication réelle → pas de commentaire à mettre. La centralisation JS dans `sapi-project.js` reste appliquée comme demandé.

- **3.4 close button** : ajouté à la card modale directement (pas au .conseiller-modal__inner inexistant — c'est `.conseiller-card__inner` qui existe, à l'intérieur de chaque écran). Position absolute top-right de `.conseiller-card--modal` (passée en `position: relative`).

- **5.2 conseil taille markup** : ajouté juste après le conseil de style avec une classe modifier `--taille` pour le margin-top 10px. Tu pourras voir les 2 paragraphes empilés sur la fiche produit.

### Critères de succès — checklist

À tester sur `test.atelier-sapi.fr` :

1. **Chantier 1** (C1-C5) : tutoiement OK (déjà acquis Round 1), plus de "tu vois les modèles à côté" (1.3), phrase d'élargissement nomme précisément la contrainte (1.4) ex. *"j'ai relâché ta préférence de style pour pouvoir te montrer mes appliques"*
2. **Chantier 2** (B3 escalier ouvert) : grille montre uniquement suspensions, pas de lampadaires ni lampes à poser
3. **Chantier 3** (G6 mobile) : croix top-right visible et fonctionnelle, modale fermable sur mobile
4. **Chantier 4.1** (C1 "applique pour ma chambre") : `sapiProject.answers` contient bien `{piece:'chambre', sortie:'mur'}` après "Voir la sélection", grille filtre sur appliques seules
5. **Chantier 5.1** (A3 salon) : choix taille = Petit / Standard / Grand
6. **Chantier 5.2** (F3 fiche produit) : 2 paragraphes italiques en récap — conseil de style + conseil de taille
7. **Chantier 6** : à toi de trancher Option A/B (cf. rapport ci-dessus)

### Notes finales

- Aucune régression attendue sur les fixes Round 1 (`e41f735`).
- 16 bugs traités sur 17. Le 17e (6.1) est livré en rapport — ton appel.
- Push effectué sur `test-theme-sapi-maison` (workflow auto-deploy O2switch déclenché).

---

## [TÂCHE] Conseiller V3 — Round 2 : 17 bugs (audit code restant + tests UX Robin)
**Date :** 2026-05-22
**Branche :** `test-theme-sapi-maison`
**Priorité :** haute — finalisation V3 avant merge master

### Contexte

Round 1 (`e41f735`) a livré 7 fixes. Restent **17 sujets** identifiés par :
- L'audit code initial du 22/05 (Groupes A et B non encore traités)
- Les tests UX de Robin sur `test.atelier-sapi.fr` (nouveaux bugs N1-N8)

Référence tests Robin (annotations dans la colonne "Observé") : `business/docs/scenarios-test-conseiller-v3-2026-05-22.md` côté Cowork.

Structuré en **6 chantiers thématiques** indépendants — Claude Code peut les attaquer dans l'ordre ou en parallèle, à sa discrétion. Tous les fichiers du périmètre sont sur `test-theme-sapi-maison`.

### Décisions Robin actées avant rédaction
- **N4** (accord grammatical labels taille) : Option C → **Petit / Standard / Grand** (masculin court)
- **N1** (freetext extraction filtres) : Claude Code investigue + fixe d'un coup (pas d'aller-retour)
- **Toutes les autres décisions** par défaut acceptées (cf. présentation Cowork du 22/05)

---

### CHANTIER 1 — Prompts IA (cohérence + conscience contextuelle)

**1.1 — Conflit ampoule cuisine vs filtre élargi.** Quand `ignored_answers` contient `piece`, l'IA peut citer la règle "pas de lampe à poser en cuisine" de `regles.txt` alors que la grille élargie en propose. Solution : dans `adaptive_consigne_block` (functions.php), ajouter une consigne conditionnelle :

> *"Si la clé `piece` figure parmi les RÉPONSES ÉLARGIES, les règles métier par pièce ont été assouplies volontairement pour pouvoir te montrer une sélection. N'oppose donc PAS au visiteur les règles 'pas de lampe à poser en cuisine' ou autres règles ampoule par pièce. Présente la sélection telle qu'elle, sans contredire la grille."*

**1.2 — `savoir.txt` parle d'un bouton "Contacter Robin" inexistant.** Modifier `assets/guide-prompt-savoir.txt` ligne 4 : remplacer *"propose un bouton 'Contacter Robin'"* par *"propose au visiteur de me contacter via le formulaire (utilise `action: contact` dans ton JSON de sortie, pas un bouton littéral)."*

**1.3 — Chat IA ment sur le contexte ("tu vois les modèles à côté").** Dans `sapi_megafilter_build_chat_prompt`, ajouter en TÊTE du system prompt (avant tout autre contenu) un bloc :

```
CONTEXTE D'INTERACTION :
Tu es Robin dans une modale flottante ouverte par-dessus la grille des modèles.
TANT QUE le visiteur n'a pas cliqué sur "Voir la sélection" pour fermer la modale,
IL NE VOIT PAS la grille en dessous (elle est masquée par la modale).
Ne dis donc JAMAIS "tu vois les modèles à côté", "regarde la sélection", ou équivalent.
Présente-lui la sélection en mots, comme si vous étiez au téléphone ensemble.
```

**1.4 — Phrase IA d'élargissement trop vague.** Aujourd'hui l'IA dit *"j'ai un peu élargi"* sans préciser quoi. Modifier `adaptive_consigne_block` (le cas "réponses élargies") pour exiger une explication précise :

> *"Si des réponses ont été élargies, NOMME précisément lesquelles avec leur libellé humain (ex: 'j'ai relâché ta préférence de style', 'j'ai mis de côté la taille de pièce') et explique brièvement pourquoi ('pour pouvoir te montrer mes appliques', 'pour t'ouvrir plus de modèles'). Pas de formule générique vague."*

Vérifier qu'on passe bien les **labels humains** des contraintes élargies (pas juste les slugs) au prompt — utiliser `sapi_megafilter_format_ignored_answers` ou helper équivalent.

---

### CHANTIER 2 — Logique filtre JS (règles métier + edge cases)

**2.1 — Event `sapi:open-modal` sans garde-fou PRODUCT_CTX.** Dans `sapi-modal-conseiller.js`, listener `sapi:open-modal` (~L1178-1182) : avant de traiter `e.detail.state === 'product'`, vérifier que `config.product` (= `SAPI_MODAL_CONSEILLER.product`) est non-null. Sinon :
```js
console.warn('[sapi-modal] open-modal state=product reçu sans config.product, abort.');
return;
```
Évite le `applyProductSelection` avec `productId:0` silencieux.

**2.2 — `taille_escalier` pas normalisé côté JS.** Dans `sapi-cards-conseiller.js`, en tête de `computeEffectiveAnswers(rawAnswers)`, normaliser avant tout traitement :
```js
const answers = { ...rawAnswers };
if (answers.taille_escalier === 'ouvert')    answers.taille = 'grande';
else if (answers.taille_escalier === 'standard') answers.taille = 'petite';
// taille_escalier reste en place (pas supprimé) — sert juste à dériver taille
```
Mirror exact de `sapi_robin_handle_recommendation` côté PHP.

**2.3 — Escalier + lampadaires (règle métier manquante).** Un lampadaire dans un escalier n'a aucun sens. Dans `sapi-cards-conseiller.js`, après `getAcceptedCategories(answers)`, ajouter :
```js
if (answers.piece === 'escalier') {
  // Règle métier : pas de lampadaires ni lampes à poser dans un escalier
  acceptedCats = acceptedCats.filter(c => c !== 'lampadaires' && c !== 'lampesaposer');
}
```
Quel que soit `taille_escalier`. Si après ce filtre il ne reste plus rien, l'élargissement progressif prend le relais normalement.

---

### CHANTIER 3 — Modale JS (qualité + accessibilité)

**3.1 — `state.shortMode` jamais reset à `closeModal`.** Dans `sapi-modal-conseiller.js:closeModal()`, ajouter avant le reset visuel :
```js
state.shortMode = false;
```

**3.2 — Centraliser `cleanInvisibleAnswers`.** Aujourd'hui 3 implémentations (`sapi-modal-conseiller.js`, `sapi-cards-conseiller.js`, et côté PHP `inc/guide-data.php`). Solution :
- Exposer dans `assets/sapi-project.js` une fonction `sapiProject.cleanInvisibleAnswers(answers, steps)` qui implémente la logique unique.
- Les 2 consommateurs JS l'appellent (`sapi-modal-conseiller.js` et `sapi-cards-conseiller.js`).
- Côté PHP : garder la fonction native (partage JS-PHP impossible sans transpiler), mais ajouter un commentaire en tête : *"MIRROR de sapiProject.cleanInvisibleAnswers (assets/sapi-project.js) — keep in sync."*

**3.3 — `hasAnyAnswer` lit answers brutes.** Dans le ou les consommateurs JS de `hasAnyAnswer()`, faire passer les answers par `cleanInvisibleAnswers` (du chantier 3.2) **avant** le compte. Évite les chips fantômes.

**3.4 — Pas de bouton close sur mobile.** Critique accessibilité — la modale plein écran sans croix sur mobile bloque le visiteur.

Dans `sapi_render_conseiller_modal` (functions.php), ajouter en haut de la card modale (avant le contenu des écrans), un bouton close visible toutes tailles d'écran :
```php
<button class="conseiller-modal__close" data-action="close" aria-label="<?php esc_attr_e('Fermer', 'theme-sapi-maison'); ?>">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <line x1="18" y1="6" x2="6" y2="18"/>
    <line x1="6" y1="6" x2="18" y2="18"/>
  </svg>
</button>
```

Style.css : positionner en absolute top-right de `.conseiller-modal__inner` ou `.conseiller-card--modal`, taille de touch target mobile (~44×44px), z-index au-dessus des écrans, couleur discrète mais cliquable.

Câbler côté JS : dans `sapi-modal-conseiller.js`, le handler `data-action="close"` doit appeler `closeModal()` (probablement déjà câblé pour d'autres triggers, vérifier).

---

### CHANTIER 4 — Catalogue & freetext (CRITIQUE — investigation + fix)

**4.1 — Extraction freetext qui n'applique pas les filtres (CRITIQUE).** Test C1 : visiteur tape *"Je cherche une applique pour ma chambre"*, l'IA répond qu'elle a compris `piece=chambre, sortie=mur` mais les filtres ne sont PAS appliqués à `sapiProject` quand on clique "Voir la sélection".

Approche en 3 sous-étapes (à enchaîner dans la même session) :

**4.1.a — Investigation.** Ajouter temporairement `error_log()` dans `sapi_ajax_megafilter_freetext` juste après réception de la réponse Haiku, qui dump :
- Le raw text retourné par Haiku
- Le `$parsed` après `sapi_megafilter_parse_json()`
- Le `$clean_filters` après whitelist
- Ce qui est retourné côté `wp_send_json_success`

Lancer un test ("Je cherche une applique pour ma chambre"), récupérer le log (cPanel → Logs d'erreurs du domaine), inclure le contenu dans le retour de la tâche.

**4.1.b — Diagnostic.** Sur la base du dump, identifier la cause :
- L'IA ne retourne pas `filters` ? → renforcer prompt
- L'IA retourne `filters` mais le JS ne les applique pas ? → fixer `submitFreetext` côté `sapi-modal-conseiller.js`
- Le `sapiProject.update({answers: ...})` n'est pas appelé ? → câblage manquant

**4.1.c — Fix.** Selon le diagnostic. Le prompt freetext doit aussi être renforcé pour gérer **3 cas de sortie explicites** (à demander à l'IA) :
1. **Projet standard** → extraire `filters` aussi complet que possible
2. **Projet incomplet** (manque info) → poser une question de précision dans `message`
3. **Projet hors-norme** (pro, pièce inconnue, demande spéciale, sur-mesure) → retourner `action: contact` + `message` chaleureux qui explique la démarche

Ajouter à la consigne du system prompt freetext une note explicite sur ces 3 voies. Aujourd'hui l'IA peut juste discuter sans appliquer ni router — c'est ce qui crée le bug.

**4.2 — `format_catalog_split` sans essence ni prix.** Aujourd'hui le catalogue split passé à l'IA contient `title | Catégorie | Format | Ampoule`. `ton.txt` demande de parler de matière, `regles.txt` demande de mentionner l'essence — l'IA n'a pas la donnée → risque d'inventer.

Solution : enrichir `sapi_megafilter_format_catalog_split` pour inclure :
- **Essences disponibles** : récupérer via les variations WooCommerce du produit (typiquement `peuplier`, `okoume`)
- **Prix dès** : récupérer le prix minimum (`get_variation_prices('min')` ou équivalent)

Nouveau format ligne : `- <title> | Catégorie : <cats> | Format : <format> | Ampoule : <type> | Essences : <peuplier, okoume> | Prix dès : <85>€`

Réutiliser autant que possible la fonction existante `sapi_guide_query_all_products` qui charge déjà ces métadonnées (voir si `variation_label` ou autre champ porte déjà les essences).

**4.3 — Slug `eclairage` à vérifier vs whitelist.** Grep dans `regles.txt` et `savoir.txt` les références aux slugs `eclairage`, comparer avec la sortie de `sapi_megafilter_filters_whitelist()`. Si discordance, harmoniser le `.txt`. Pas de fix automatique nécessaire si tout est cohérent.

---

### CHANTIER 5 — UX wording

**5.1 — Accord grammatical labels taille (N4).** Décision Robin : **Option C — Petit / Standard / Grand** (masculin court).

Modifier `inc/guide-data.php`, question `taille`, choices :
```php
'choices' => [
  ['label' => 'Petit',         'dim' => 'intime',      'slug' => 'petite',     'icon' => 'square-sm'],
  ['label' => 'Standard',      'dim' => 'confortable', 'slug' => 'moyenne',    'icon' => 'square-md'],
  ['label' => 'Grand',         'dim' => 'spacieuse',   'slug' => 'grande',     'icon' => 'square-lg'],
  ['label' => 'Je ne sais pas','dim' => '',            'slug' => 'ne-sais-pas','icon' => 'question'],
],
```

Les `slug` restent inchangés (`petite`, `moyenne`, `grande`) pour ne pas casser la logique de filtrage.

**5.2 — Récap fiche produit manque phrase taille.** Aujourd'hui seul `sapi_megafilter_get_style_conseils()` existe. Créer un mirror `sapi_megafilter_get_size_conseils()` :

```php
function sapi_megafilter_get_size_conseils() {
  return [
    'petite'  => __("Cette taille s'adapte bien à un petit espace sans être trop imposante.", 'theme-sapi-maison'),
    'moyenne' => __("Cette taille standard convient à la plupart des pièces.", 'theme-sapi-maison'),
    'grande'  => __("Cette grande taille créera un point focal fort dans ton espace.", 'theme-sapi-maison'),
  ];
}
```

Localizer côté JS (`SAPI_MODAL_CONSEILLER.sizeConseils = sapi_megafilter_get_size_conseils()`), et dans `sapi-modal-conseiller.js` écran `s-product-recap`, ajouter un deuxième paragraphe `conseil de taille` sous le conseil de style, en utilisant le slug de taille dérivé de `sapiProject.answers.taille` (ou `taille_escalier` mappé).

Markup à ajouter dans `sapi_render_conseiller_modal` :
```html
<p class="conseiller-product-recap__conseil-taille" data-product-recap-conseil-taille></p>
```

---

### CHANTIER 6 — Investigation (rapport uniquement)

**6.1 — `sortie=ne-sais-pas` dans bureau ne fonctionne pas (N8).** Robin a constaté que le scénario `bureau + sortie=ne-sais-pas` ne renvoie pas de produits (alors qu'on s'y attendrait). Normal en cuisine (règles ampoule strictes), bizarre en bureau.

Tester manuellement le cas, identifier ce qui se passe côté JS (`computeEffectiveAnswers` + `cardMatchesAnswers`). Trois hypothèses :
1. Manque catalogue (très peu de produits matchent la combinaison) → l'élargissement devrait prendre le relais, vérifier qu'il le fait
2. Bug dans `cats_by_sortie['ne-sais-pas']` côté JS (peut-être pas câblé pareil que côté PHP)
3. Bug dans l'enchaînement filtre principal + filtre secondaire pour `eclairage=secondaire`

Livrer un mini-rapport (pas de fix automatique — décision Robin après lecture). Si bug évident, fixer.

---

### Critères de succès globaux

À tester sur `test.atelier-sapi.fr` après livraison :

1. **Chantier 1** : retester C1-C5 → tutoiement OK, plus de "tu vois les modèles à côté" (1.3), phrase d'élargissement précise qui nomme la contrainte (1.4)
2. **Chantier 2** : test B3 (escalier ouvert) → grille montre uniquement suspensions (pas de lampadaires) (2.3)
3. **Chantier 3** : test G6 mobile → croix visible et fonctionnelle, modale fermable (3.4)
4. **Chantier 4.1** : retester C1 → filtres `piece=chambre, sortie=mur` réellement appliqués à `sapiProject` quand on clique "Voir la sélection". Inclure dans le retour le résultat de l'investigation 4.1.a + le diagnostic + le fix appliqué.
5. **Chantier 5.1** : test A3 (salon) → labels Petit/Standard/Grand cohérents quel que soit le genre du sujet
6. **Chantier 5.2** : test F3 (fiche produit récap) → conseil taille présent en plus du conseil style
7. **Chantier 6** : rapport sur G3 bureau livré

### Notes pour le retour

Au retour, indiquer pour chaque chantier :
- Hash des commits livrés
- Liste des bugs traités vs reportés (si quelque chose n'a pas pu être fait)
- Échantillon de prompt freetext capturé via `error_log` (chantier 4.1.a) — utile pour valider le diagnostic
- Mini-rapport investigation 6.1
- Toute déviation par rapport à la spec (avec justification)

---

## ✅ Livré

## [RETOUR] Audit Conseiller V3 — 7 fixes livrés (3 critiques + 4 réels)
**Date livrée :** 2026-05-22
**Branche :** `test-theme-sapi-maison`
**Commit :** `e41f735`
**Statut :** Livré. 7 bugs sur 7 du périmètre actés.

### Récap des fixes

| # | Type | Sujet | Statut |
|---|---|---|---|
| 1 | 🔴 Critique | Chat S2 format brut → utilise `format_project_text` | ✅ |
| 2 | 🔴 Critique | `advice_text` figé → reset auto dans `update()` | ✅ |
| 3 | 🔴 Critique | Contradiction tu/vous → ton.txt + exemples.txt réécrits | ✅ |
| 4 | 🟡 Réel | `cats_secondaire_by_sortie['ne-sais-pas']` manquant | ✅ |
| 5 | 🟡 Réel | Fetches IA sans timeout/r.ok → `sapiSafeFetch` | ✅ |
| 6 | 🟡 Réel | "Nommer / ne pas nommer" → interdit partout | ✅ |
| 7 | 🟡 Réel | Race + callbacks orphelins → `AbortController` + garde-fou DOM | ✅ |

### Implémentation par fix

**#1 Chat S2 format**
- Nouveau helper PHP `sapi_megafilter_labels_from_slugs($filters)` : lookup slug→label via `sapi_guide_get_steps()`
- Section "FILTRES APPLIQUÉS" du chat builder remplacée par `PROJET DU VISITEUR :` + `format_project_text` (clés explicites multi-ligne — héritage du fix `f221ba0`)

**#2 advice_text invalidation**
- `sapi-project.js:update()` snapshote `JSON.stringify(p.answers)` avant/après patch, invalide `advice_text = null` si différent
- `setAdviceText()` écrit en direct via `writeRaw` (ne passe pas par `update`) → pas de boucle d'auto-invalidation

**#3 Tutoiement**
- `guide-prompt-ton.txt` : 2 modifications principales (vouvoiement → tutoiement, "tu ne prétends pas être Robin" → "tu es Robin, tu parles à la première personne")
- `guide-prompt-exemples.txt` : RÉÉCRITURE COMPLÈTE (256 lignes) au tutoiement systématique
- Gardé inchangé : structure fiches, libellés boutons UI, contenu sémantique
- **Vérification grep** : 0 occurrence résiduelle de `vous/votre/vos/vôtre` sur les 2 fichiers

**#4 ne-sais-pas key**
```php
'cats_secondaire_by_sortie' => [
  ...
  'ne-sais-pas' => ['lampadaires', 'lampesaposer', 'appliques'], // ← AJOUTÉ
],
```

**#5 sapiSafeFetch**
- Helper en haut de `sapi-modal-conseiller.js` : timeout (15s Haiku / 25s Sonnet) + check `r.ok` + support `AbortSignal` externe
- Throw `'timeout'` / `'aborted'` / `'HTTP <status>'` au caller
- Les 3 fetches IA utilisent ce helper
- En cas d'erreur : reset `state.transition = false`, message explicite côté UI (chat/freetext) ou fallback générique silencieux côté card "Mon projet" pour advice (pour ne pas perturber la rendering)

**#6 Interdire nommer**
- `regles.txt` L2 : "Ne nomme JAMAIS de modèle précis..."
- `exemples.txt` FICHE 8 : "Évoque l'ambiance, les essences et formats sans nommer..."
- `build_chat_prompt` : "Ne nomme JAMAIS de modèle précis dans `message`..."

**#7 AbortController + garde-fou**
- `state.aiController` (AbortController de la requête IA en cours)
- `startAiRequest()` / `clearAiRequest()` : abort la précédente + crée un nouveau controller
- Garde-fou `if (!state.open) return;` au début des `.then` et `.catch` (évite écriture DOM démonté)
- `closeModal()` abort le controller pendant la fermeture

### Notes pour le retour

- **Échantillon de prompt chat** non capturé localement (pas de PHP CLI dispo). Si Robin veut vérifier la présence des 3 sections (PROJET multi-ligne / catalogue split / ignored_answers) ET la directive "Ne nomme JAMAIS de modèle précis", je peux ajouter un `error_log()` temporaire dans `sapi_ajax_megafilter_chat` ou `sapi_ajax_megafilter_advice` juste avant le `sapi_megafilter_call_claude` — déclencher un test puis récupérer dans le log d'erreurs cPanel.
- **Régressions** : aucune attendue sur les fixes précédents (`318b112`, `c0e1f02`, `f221ba0`).

### Question pour Robin

8 cas à tester côté `scenarios-test-conseiller-v3-2026-05-22.md` (référence Cowork). En particulier :
- **C1-C4 + B2** : tutoiement systématique + plus de "ta cuisine est au mur"
- **G2** : parcours Salon → Recommencer → parcours Bureau → `advice_text` reflète bien Bureau (pas reste de Salon)
- **G3** : `salon+grande+secondaire+sortie="Je ne sais pas"` affiche bien des appliques
- **H1** : bloquer admin-ajax → message d'erreur clair après timeout, modale déblocable

---

## [TÂCHE — LIVRÉ] Fixes audit Conseiller V3 — 3 critiques + 4 réels (7 bugs)
**Date :** 2026-05-22
**Branche :** `test-theme-sapi-maison`
**Priorité :** haute — bloque la validation V3 avant merge master

### Contexte

L'audit côté Cowork du 22/05 (3 sous-agents en parallèle sur les angles filtre JS, prompts IA, intégration JS↔PHP) a identifié 12 trouvailles. Robin a tranché pour fixer **les 3 critiques + 4 réels** dans une seule passe. Les 5 mineurs (8-12) restent en backlog.

Rapport d'audit complet côté Cowork : `business/docs/scenarios-test-conseiller-v3-2026-05-22.md` (et la synthèse des 3 sous-agents dans le transcript Cowork).

### Les 7 bugs à fixer

---

#### 🔴 CRITIQUE #1 — Chat S2 utilise le format brut au lieu de `format_project_text`

**Symptôme :** dans `sapi_megafilter_build_chat_prompt` (~L2783-2790 de `functions.php`), la section "FILTRES ACTUELLEMENT APPLIQUÉS DANS LE MÉGA-FILTRE" dump `- piece = cuisine\n- sortie = mur`. **C'est exactement le format qui a déclenché le bug "ta cuisine est au mur"**, déjà corrigé pour `advice` au commit `f221ba0` mais pas pour le chat. Reproductible en testant un parcours puis en passant en chat S2.

**Fix :**
1. Créer un helper `sapi_megafilter_labels_from_slugs($filters)` qui mappe `slug → label affichable` en parcourant `sapi_guide_get_steps()[].choices[]`. Ex : `['piece' => 'cuisine']` → `['piece' => 'Cuisine']`.
2. Dans `sapi_megafilter_build_chat_prompt`, remplacer la boucle existante par :
   ```php
   $labels = sapi_megafilter_labels_from_slugs($current_filters);
   $prompt .= "PROJET DU VISITEUR :\n" . sapi_megafilter_format_project_text($current_filters, $labels) . "\n";
   ```

---

#### 🔴 CRITIQUE #2 — `advice_text` figé après changement d'answers (bug 19/05 toujours actif)

**Symptôme :** dans `sapi-project.js:update()` (~L139-173), la fonction patche les `answers` mais ne touche jamais `advice_text`. Quand le visiteur change une réponse via chip dans la modale (`handleChipAnswer → update`), l'ancien `advice_text` est conservé. Pattern observé le 19/05 (Hero "Pour un salon" + advice mentionnant "cuisine"). Confirmé toujours actif par l'audit.

**Fix dans `sapi-project.js:update()`** :
```js
function update(patch) {
  const data = load();
  const oldAnswersHash = JSON.stringify(data.answers || {});
  // ... apply patch ...
  const newAnswersHash = JSON.stringify(data.answers || {});
  if (oldAnswersHash !== newAnswersHash && !patch._keepAdvice) {
    data.advice_text = null;
  }
  save(data);
  notify();
}
```
Le `setAdviceText` (fin de parcours modale) doit alors être appelé avec un flag interne pour éviter l'auto-invalidation pendant qu'on le re-renseigne. Solution simple : `setAdviceText` n'utilise pas `update()` mais une voie directe `setAdviceTextRaw` qui écrit `data.advice_text` sans toucher aux answers.

---

#### 🔴 CRITIQUE #3 — Contradiction tutoiement/vouvoiement dans les prompts

**Symptôme :** `assets/guide-prompt-ton.txt` dit *"Vouvoie toujours le client"* et *"Tu ne prétends pas être Robin"*. Les 3 builders inline (`functions.php` L2734, L2773, L3513) injectent juste après *"Tu es Robin… tutoiement systématique"*. Tous les exemples de `exemples.txt` (12 KB) sont au vouvoiement. → L'IA reçoit deux directives opposées, résultat instable selon le dernier token gagnant.

**Fix :**
1. Modifier `assets/guide-prompt-ton.txt` :
   - Remplacer *"Vouvoie toujours le client"* par *"Tutoie toujours le visiteur (jamais le vouvoiement)"*
   - Remplacer *"Tu ne prétends pas être Robin"* par *"Tu es Robin, artisan menuisier lyonnais. Tu parles à la première personne."*
   - Vérifier le reste du fichier : tout "vous/votre/vos" résiduel doit passer au tutoiement.
2. **Réécrire entièrement `assets/guide-prompt-exemples.txt` au tutoiement** (décision Robin actée — 12 KB à passer du vouvoiement au tutoiement singulier) :
   - Tous les "vous" (sujet) → "tu"
   - Tous les "votre/vos/vôtre" → "ton/ta/tes/tien" selon le genre
   - Adapter les terminaisons verbales : "vous cherchez" → "tu cherches", "vous avez" → "tu as", "vous voulez" → "tu veux", "vous m'avez indiqué" → "tu m'as indiqué", etc.
   - Adapter les pronoms compléments : "vous orienter" → "t'orienter", "vous aider" → "t'aider", "vous propose" → "te propose"
   - Adapter les formules de politesse : "Si vous voulez" → "Si tu veux", "Vous pouvez" → "Tu peux"
   - Garder STRICTEMENT inchangés :
     - La structure du fichier (fiches numérotées, sections, contextes)
     - Le contenu sémantique (règles, exemples métier, descriptions de pièces, descriptions de produits)
     - Les libellés de boutons (ex: "Boutons : Cuisine / Bureau …") — ce sont des choix UI, pas du langage humain
     - Les références techniques (slugs, IDs, conditionnels "si taille = grande")
3. Vérification finale obligatoire : `grep -iE "\bvou(s|tre|s)\b|\bvôtre\b|\bvos\b" assets/guide-prompt-exemples.txt` doit retourner **0 ligne**. Si des occurrences subsistent (cas litigieux ex. "vous (collectif)" dans une règle), les annoter en commentaire et demander confirmation Robin.
4. Pas besoin de note en tête du fichier après réécriture — la cohérence interne suffit.

---

#### 🟡 RÉEL #4 — `cats_secondaire_by_sortie` manque la clé `'ne-sais-pas'`

**Symptôme :** `$sapi_filter_rules['cats_secondaire_by_sortie']` (`functions.php` ~L332-337) ne couvre pas `'ne-sais-pas'` alors que `cats_by_sortie` le fait. Scénario reproductible : `piece=salon, taille=grande, eclairage=secondaire, sortie=ne-sais-pas` → on perd les appliques alors qu'on les aurait en `sortie=pas-de-sortie`.

**Fix :** ajouter la clé manquante, par symétrie avec `cats_by_sortie` :
```php
'cats_secondaire_by_sortie' => [
  'plafond'       => ['suspensions'],
  'mur'           => ['appliques'],
  'pas-de-sortie' => ['lampadaires', 'lampesaposer', 'appliques'],
  'ne-sais-pas'   => ['lampadaires', 'lampesaposer', 'appliques'],  // ← AJOUTER
  ''              => ['lampadaires', 'lampesaposer'],
],
```

---

#### 🟡 RÉEL #5 — Aucune gestion d'erreur HTTP sur les fetches IA

**Symptôme :** `fetchAdviceFromIA` (`sapi-modal-conseiller.js` ~L353-362), `submitChat` (~L683), `submitFreetext` (~L608) appellent `.then(r => r.json())` sans check de `r.ok` ni timeout. Un 500 PHP renvoyant du HTML crash dans `.json()` → catch silencieux → fallback générique sans message. Pire : pas de timeout, donc un endpoint qui hang laisse `state.transition = true` indéfiniment et la modale est bloquée (ESC désactivé pendant transition).

**Fix :** créer un helper partagé `sapiSafeFetch(url, options, opts)` (inline en haut de `sapi-modal-conseiller.js` ou nouveau fichier `assets/sapi-safe-fetch.js`) :

```js
async function sapiSafeFetch(url, options = {}, { timeout = 15000, signal: externalSignal } = {}) {
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort('timeout'), timeout);
  if (externalSignal) {
    externalSignal.addEventListener('abort', () => controller.abort('external'), { once: true });
  }
  try {
    const r = await fetch(url, { ...options, signal: controller.signal });
    clearTimeout(timer);
    if (!r.ok) throw new Error(`HTTP ${r.status}`);
    return await r.json();
  } catch (e) {
    clearTimeout(timer);
    if (e.name === 'AbortError') {
      throw new Error(controller.signal.reason === 'timeout' ? 'timeout' : 'aborted');
    }
    throw e;
  }
}
```

Utiliser dans les 3 endpoints IA. Dans le catch :
- Reset `state.transition = false` pour débloquer la modale
- Afficher un message d'erreur clair côté UI : *"Je n'arrive pas à te répondre pour l'instant, tu peux réessayer ou me contacter directement via le formulaire."* (ou message rate-limit existant si erreur 429)
- Pas de fallback silencieux

Timeout par défaut : 15s pour Haiku (freetext), 25s pour Sonnet (chat/advice, plus lent).

---

#### 🟡 RÉEL #6 — Contradiction "nommer / ne pas nommer un produit"

**Symptôme :** Plusieurs directives qui se contredisent dans le prompt du chat :
- `adaptive_consigne_block` (L3432) : *"Ne nomme pas de modèle précis"*
- `exemples.txt` FICHE 8 (L215) : *"Nomme 2-3 produits précis avec explications personnalisées"*
- `regles.txt` (L5) : *"Tu peux mentionner des noms de produits du catalogue pour illustrer"*
- `sapi_megafilter_build_chat_prompt` (L2816) : *"Tu peux référencer un modèle précis du catalogue par son nom"*

Le chat va probablement nommer un produit (3 directives le poussent à le faire), tandis que l'`advice` final ne nommera rien → incohérence vécue par le visiteur.

**Fix : interdire de nommer partout** (décision Robin actée, plus cohérent avec l'esprit "le visiteur voit les produits dans la grille à côté") :
1. `assets/guide-prompt-regles.txt` L5 : remplacer *"Tu peux mentionner des noms de produits du catalogue pour illustrer, mais ne désigne pas UN produit comme 'le bon choix'"* par *"Ne nomme JAMAIS de modèle précis du catalogue dans ta réponse — le visiteur les voit dans la grille à côté. Présente plutôt l'ambiance, la matière, le format."*
2. `assets/guide-prompt-exemples.txt` FICHE 8 L215 : remplacer *"Nomme 2-3 produits précis avec explications personnalisées"* par *"Évoque l'ambiance, les essences et formats de la sélection sans nommer de modèle précis (le visiteur les voit dans la grille à côté)."*
3. `sapi_megafilter_build_chat_prompt` (~L2816) : retirer la ligne *"Tu peux référencer un modèle précis du catalogue par son nom dans `message` si pertinent."* — remplacer par *"Ne nomme JAMAIS de modèle précis dans `message` (le visiteur les voit dans la grille)."*

---

#### 🟡 RÉEL #7 — Race condition + callbacks orphelins

**Symptôme :** si l'utilisateur clique "Voir la sélection" pendant qu'un fetch chat IA est en cours, la réponse arrive après la fermeture de la modale et écrit dans le DOM démonté (`addRobinBubble` ligne 686 référence `els.chatMessages`). Pas un crash mais une fuite de callback orphelin + consommation Sonnet pour rien.

**Fix combiné avec #5 :**
1. Garder un `AbortController` au niveau du state de la modale : `state.aiController`
2. Quand un nouveau fetch démarre : `state.aiController?.abort('replaced'); state.aiController = new AbortController();` et passer son signal à `sapiSafeFetch` en `externalSignal`
3. Dans `showTransitionAndExit` et `closeModal` : `state.aiController?.abort('closed');`
4. Au début de chaque `.then` de fetch IA : `if (!state.open) return;` (garde-fou écriture DOM démonté)

---

### Décisions actées par Robin (22/05/2026, Cowork)

- **#3** : **réécriture complète de `exemples.txt` au tutoiement** (décision Robin contre ma reco initiale). Modifier aussi `ton.txt` minimalement. Vérification grep obligatoire pour zéro résiduel.
- **#6** : interdire de nommer partout (option B). Plus cohérent que de différencier chat vs advice.
- **#5/#7** : helper `sapiSafeFetch` partagé. Pas de fallback silencieux : on affiche un message d'erreur explicite.

### Critères de succès

Référence : `business/docs/scenarios-test-conseiller-v3-2026-05-22.md` côté Cowork (8 blocs de test).

1. **Test C1-C4 + B2** (chat S2) : l'IA ne dit plus jamais "ta cuisine est au mur" ni variantes (#1)
2. **Test G2** : parcours Salon → Recommencer → parcours Bureau → l'`advice_text` reflète Bureau (pas reste de Salon) (#2)
3. **Tests C1-C4 + B2** : tutoiement systématique partout ("tu", "ton", jamais "vous") (#3)
4. **Test G3** : salon+grande+secondaire+sortie="Je ne sais pas" affiche bien des appliques (#4)
5. **Test H1** : bloquer admin-ajax → message d'erreur clair après timeout, modale déblocable (Escape, click hors zone) (#5)
6. **Tests C1-C5 + B2** : l'IA ne nomme JAMAIS un modèle précis dans `message` (#6)
7. **Test manuel** : ouvrir modale, taper un message en chat S2, cliquer "Voir la sélection" avant que la réponse arrive → pas d'erreur console, pas d'écriture DOM démonté, modale ferme proprement (#7)
8. **Pas de régression** sur les fixes précédents (`318b112`, `c0e1f02`, `f221ba0`)

### Notes pour le retour

Au retour, indiquer :
- Hash des commits livrés
- Confirmation que `sapiSafeFetch` est bien partagé entre les 3 endpoints
- Échantillon de prompt `chat` capturé via `error_log` montrant le `PROJET DU VISITEUR :` avec clés explicites (#1)
- Échantillon de prompt `chat` montrant la directive "Ne nomme JAMAIS de modèle précis" (#6)

---

## ✅ Livré

## [RETOUR] Fix IA "Ta cuisine est au mur" — clés explicites + consigne contenu
**Date livrée :** 2026-05-22
**Branche :** `test-theme-sapi-maison`
**Commit :** `f221ba0`
**Statut :** Livré, à tester sur le même cas que celui qui a produit le bug.

### Symptôme

Sur le cas "Cuisine · Petite · Au mur · Pas de préf" testé après la refonte (`c0e1f02`), la phrase IA disait :

> *"Ta cuisine est au mur, donc j'ai sélectionné des appliques — j'ai un peu élargi la sélection pour pouvoir te montrer des modèles. Garde en tête qu'avec une ampoule entourée, ces pièces habillent bien l'espace mais demandent une autre source lumineuse principale pour bien éclairer un plan de travail."*

L'IA a interprété la réponse `Sortie : Au mur` comme si la cuisine elle-même était au mur. Cause : le `project_text` envoyé était trop télégraphique (`Sortie : Au mur`), l'IA ne savait pas que "Sortie" désignait l'emplacement de l'arrivée électrique.

### Décisions actées

- **Axe 1** retenu : enrichir les **clés** du project_text (pas les valeurs)
- **Axe 2** retenu : consigne explicite dans le prompt sur le contenu de la phrase
- **Pas d'indice de type de produit** dans le mapping de `sortie` : le filtre s'en occupe déjà, l'IA déduit du catalogue split déjà injecté

### Implémentation

**Axe 1 — `sapi_megafilter_format_project_text`** refondu en multi-ligne avec clés explicites :

| Slug | Avant | Après |
|---|---|---|
| piece | Pièce | **Pièce où installer le luminaire** |
| sortie | Sortie | **Emplacement de la sortie électrique** |
| taille | Taille | **Taille de la pièce** |
| eclairage | Éclairage | **Rôle d'éclairage attendu** |
| hauteur | Hauteur | **Hauteur sous plafond** |
| table | Au-dessus | **Sera-t-il au-dessus d'un meuble (table/lit/bureau)** |
| style | Style | **Style décoratif souhaité** |
| taille_escalier | Escalier | **Type d'escalier** |

Format : `- <clé> : <valeur>` une ligne par champ (au lieu du `·` compact). Cohabite avec l'email sur-mesure à Robin (lisibilité préservée).

**Axe 2 — `sapi_megafilter_adaptive_consigne_block`** étendu d'une section `CONTENU DE LA PHRASE` :
- N'énumère PAS chaque réponse, va à l'essentiel
- Si style = "Pas de préférence" / "neutre" → NE LE MENTIONNE PAS
- Évite les tournures qui confondent caractéristique de la PIÈCE avec RÉPONSE du visiteur, **avec anti-exemple littéral** du bug ("ta cuisine est au mur") pour conditionner négativement l'IA

Bloc commun → s'applique automatiquement à `advice` ET `chat`.

### Question pour Robin

- Retester le même cas "Cuisine · Petite · Au mur · Pas de préf" : la phrase IA ne devrait plus mal interpréter le `sortie=mur` ni mentionner le style "Pas de préférence".

---

## [RETOUR] Fallback filtre global + contrat IA enrichi (refonte + bug latéral C)
**Date livrée :** 2026-05-22
**Branche :** `test-theme-sapi-maison`
**Commit :** `c0e1f02`
**Statut :** Livré, à tester.

### Décisions techniques actées en cours

- **Option α** : le JS calcule le filtre + l'élargissement + les matching_ids et les passe au PHP. Pas de duplication PHP de la logique de match.
- **Catalogue split** : sections "PRÉSENTÉS AU VISITEUR" + "ÉCARTÉS PAR LE FILTRE" dans les prompts (plus lisible que juste des IDs).
- **ignored_answers filtré** aux clés vraiment répondues (skip silencieux pour ce que le visiteur n'avait pas indiqué).
- **Chat builder enrichi aussi** (le visiteur peut atterrir en chat via "Préciser avec Robin" depuis S3).
- **Cumul rigide** confirmé pour l'élargissement (style → table → hauteur → eclairage → taille → piece, sortie intouchable).

### Implémentation

**Architecture en 3 couches :**

1. **JS = source de vérité du filtre** (`assets/sapi-cards-conseiller.js`) :
   - Refactor `cardMatches` → `cardMatchesAnswers(card, answers)` (signature explicite)
   - Nouveau `computeMatchingIds(answers)` : compte les cards matchant
   - Nouveau `computeEffectiveAnswers(rawAnswers)` : élargissement progressif cumulatif selon `WIDENING_ORDER = ['style','table','hauteur','eclairage','taille','piece']`. Retourne `{effectiveAnswers, ignoredAnswers, matchingIds}`. `taille_escalier` géré comme avatar de `taille`. `sortie` JAMAIS retirée.
   - `refilterGrid` calcule la meta AVANT que shop.js itère
   - `window.sapiMegaFilter.computeFilterMeta` exposé pour la modale

2. **Modale enrichit ses POSTs** (`assets/sapi-modal-conseiller.js`) :
   - Helper `buildFilterMeta(answers)` lit l'API exposée
   - `fetchAdviceFromIA` + `submitChat` appendent `matching_product_ids` + `ignored_answers` (calculés à la volée depuis `state.answers` — peut différer de `sapiProject` quand la modale n'a pas encore persisté)

3. **PHP enrichit les system prompts** (`functions.php`) :
   - 4 nouveaux helpers communs : `parse_matching_ids`, `parse_ignored_answers`, `format_catalog_split`, `format_ignored_answers` (avec labels humains FR : "la pièce", "la taille de pièce", etc.), `adaptive_consigne_block`
   - `sapi_ajax_megafilter_advice` : parse + 3 sections dans user_msg + consigne adaptative dans system prompt
   - `sapi_megafilter_build_chat_prompt` : signature étendue, remplace l'ancienne section "CATALOGUE COMPLET" par split présentés/écartés + ignored_answers + consigne adaptative
   - `sapi_ajax_megafilter_chat` : parse + passe au builder
   - `freetext` (Haiku) inchangé (décision Robin)

**Consigne adaptative** ajoutée au prompt (commune advice + chat) :
- 0 produit présenté → propose sur-mesure sans baratin
- Réponses élargies → mentionne subtilement et sincèrement (demi-phrase)
- OK → présente naturellement
- Dans TOUS les cas → ne nomme PAS de modèle précis

**Bug latéral C — Empty state** :
- `archive-product.php` : refonte du markup `.woocommerce-no-products-found` → `.shop-empty-state` avec texte principal + sous-texte italique + CTA orange vers `/sur-mesure/`
- `style.css` : panneau crème dashed wood (langage Conseiller) + bouton orange
- Le message ne devrait s'afficher que dans le cas extrême "0 même après élargissement max" (très rare avec le fallback global)

### Cas testables sur `test.atelier-sapi.fr`

| Cas | Comportement attendu |
|---|---|
| **Cuisine · Petite · Au mur · Pas de préf** | Grille montre les 4 appliques (élargissement retire style + taille + piece), phrase IA mentionne l'élargissement ("j'ai un peu élargi ta sélection...") |
| **Cuisine · Grande · Au plafond · Moderne** | Grille montre les suspensions ampoule dégagée/semi (filtre direct OK), phrase IA présente normalement |
| **Escalier · Ouvert** | Grille montre suspensions verticales (filtre direct OK selon visibility), phrase IA présente normalement |
| **Cas extrême : combinaison impossible** | Empty state `.shop-empty-state` visible avec CTA sur-mesure, phrase IA propose le sur-mesure |

### Pour vérifier les prompts envoyés (échantillon)

Phase 5 (capture du prompt via `error_log`) n'est pas faite — j'ai pas accès aux logs O2switch en local. Si Robin veut vérifier que les 3 sections sont bien injectées, je peux ajouter temporairement `error_log("=== ADVICE PROMPT ===\n" . $system_prompt . "\n=== USER MSG ===\n" . $user_msg);` dans `sapi_ajax_megafilter_advice` juste avant l'appel `sapi_megafilter_call_claude`. Robin déclenche un parcours, récupère le log dans cPanel (`error_log` du domaine), me le passe, je retire.

### Notes opérationnelles

- **Taille prompts** : advice passe de ~22 KB (V2 prompts) à ~22 + ~5 KB (catalogue split + ignored = ~27 KB total). Largement dans les marges Sonnet (200K tokens contexte).
- **Aucune régression** sur `freetext` (Haiku, inchangé).
- **Effet de bord à observer** : longueur des phrases IA peut varier selon la consigne adaptative — à monitorer côté UX si certaines phrases deviennent trop longues.

### Question pour Robin

- Tests sur les 4 cas du tableau ci-dessus.
- Si tu veux le sample de prompt, dis-le, je rajoute le `error_log` temporaire.
- Le message "Aucun produit" devrait être beaucoup plus visible désormais (encart crème dashed avec CTA orange). Si l'élargissement fonctionne, il ne devrait s'afficher qu'en cas extrême.

---

## [TÂCHE — LIVRÉ] Fallback filtre global + contrat IA `advice` enrichi (refonte)
**Date :** 2026-05-22
**Branche :** `test-theme-sapi-maison`
**Priorité :** haute (bug fonctionnel actif sur test)

### Contexte & arbitrage Robin

L'enquête du 22/05 (voir historique de la queue) a identifié 2 problèmes sur le cas *Cuisine · Petite · Au mur · Pas de préférence* : grille vide ET phrase IA qui promet des modèles inexistants. Plutôt que de patcher au cas par cas, Robin a tranché pour une refonte unifiée du contrat filtre + IA :

1. **Si une réponse mène à 0 produit, on la retire et on relance** — règle générale, pas juste pour cuisine+mur
2. **L'IA doit avoir le catalogue + le résultat du filtre dans tous les cas** pour que ses phrases reflètent la réalité
3. **L'IA doit signaler subtilement et sincèrement** quand des contraintes ont été élargies

Cohérent avec la Phase 1 livrée (`318b112`) qui a déjà rapatrié la voix Robin V2 dans les prompts.

### À faire — 3 chantiers couplés

#### A. Côté JS — Fallback global du filtre

Dans `sapi-cards-conseiller.js`, refonte de la logique d'élargissement progressif :

1. Tenter le filtre avec **toutes** les réponses du projet
2. Si 0 produit, retirer les réponses dans cet ordre fixe (du moins critique au plus) :
   - `style` (préférence esthétique)
   - `table` (Au-dessus de…)
   - `hauteur` (sous-plafond)
   - `eclairage` (principal / secondaire)
   - `taille` (de la pièce)
   - `piece` (cuisine / salon / chambre / bureau / entrée / escalier — retire le filtre ampoule par pièce et toute logique d'ambiance ; classé en dernier car moins structurant que `sortie`)
3. Stratégie : retirer **une** réponse à la fois (dans l'ordre), si toujours 0, retirer **2** (cumul à partir du début), puis 3, etc., jusqu'à toutes (cumul des 6).
4. **Ne JAMAIS retirer `sortie`** — c'est le client qui a indiqué où installer son luminaire, ça détermine le type de produit (applique / suspension / lampadaire / lampe à poser). Intouchable.
5. Si toujours 0 même au maximum d'élargissement (les 6 retirées, seule `sortie` reste) : tomber sur état "0 produit catalogue → CTA sur-mesure proéminent"

À chaque relance, mémoriser la **liste des slugs retirés** dans une variable accessible (ex. `projectMeta.ignoredAnswers`) pour la transmettre ensuite à l'endpoint `advice`.

#### B. Côté PHP — Contrat enrichi de `sapi_ajax_megafilter_advice`

Le POST reçoit maintenant en plus 2 nouveaux champs :
- `matching_product_ids` (JSON array) : IDs des produits qui matchent après filtrage final côté JS
- `ignored_answers` (JSON array de slugs) : ex `["style"]`, `["style","table"]`, ou `[]` si filtre direct OK

Le system prompt est enrichi de **3 nouvelles sections** (en plus du rapatriement V2 déjà fait) :

1. **Catalogue complet** — repris du builder `sapi_megafilter_build_chat_prompt` (qui le passe déjà via `$all_products = sapi_guide_query_all_products([])`) :
   ```
   CATALOGUE COMPLET (tous les luminaires disponibles) :
   - <title> | Catégorie : <cats> | Format : <format> | Ampoule : <type>
   ...
   ```

2. **Résultat filtre** — extrait depuis `matching_product_ids` :
   ```
   PRODUITS RETENUS APRÈS FILTRAGE (N modèles) :
   - <title> (ID <id>)
   ...
   ```
   ou `(aucun)` si liste vide.

3. **Contraintes ignorées** — depuis `ignored_answers`, avec labels humains :
   ```
   RÉPONSES ÉLARGIES POUR TROUVER DES MODÈLES : style, taille
   ```
   ou ligne absente si `ignored_answers = []`.

**Consigne ajoutée au prompt** :
> Si la liste des produits retenus est vide après élargissement maximum, propose chaleureusement le sur-mesure (sans baratin). Si certaines réponses ont été élargies, mentionne-le subtilement et sincèrement (une demi-phrase suffit, naturel). Sinon, présente la sélection comme d'habitude. **Ne nomme pas de modèle précis** — le visiteur les voit dans la grille.

#### C. Côté JS — Bug latéral du message "Aucun produit"

Investiguer pourquoi le fallback de `shop.js:80` (message "Aucun produit ne correspond") ne s'affiche pas visiblement quand le filtre retourne 0. Hypothèses listées dans l'enquête : carousel qui masque, `visibleCount` qui n'arrive pas à 0, condition d'affichage cassée. **À corriger dans la même livraison.**

Dans le nouveau monde (avec fallback global du chantier A), ce message ne devrait s'afficher que dans le cas extrême "0 produit même au max d'élargissement". Mais il faut qu'il s'affiche bien quand ce cas se produit.

### Critères de succès

1. **Cas "Cuisine · Petite · Au mur · Pas de préf"** : la grille affiche ≥1 produit (élargissement automatique a retiré `style` puis éventuellement `taille`), et la phrase IA mentionne l'élargissement de manière naturelle (*"j'ai un peu élargi ta sélection pour pouvoir te montrer des modèles…"* par exemple).
2. **Cas extrême "0 même après élargissement max"** : grille montre un message + CTA sur-mesure proéminent, et l'IA propose le sur-mesure sans détour ni promesse de modèle catalogue.
3. **Cas standard (filtre direct OK)** : aucun changement visible, l'IA répond avec sa voix V2 + ses règles métier.
4. Le system prompt de `advice` contient bien les 3 nouvelles sections (catalogue + résultat filtre + contraintes ignorées) — vérifiable par `error_log($system_prompt)` temporaire.
5. Pas de régression sur `chat` ni `freetext` (inchangés).
6. Test du cycle complet sur `test.atelier-sapi.fr` : parcours en modale → grille → phrase IA cohérente.

### Décisions Robin actées (Cowork, 22/05/2026)

- **Ordre de retrait** : style → table → hauteur → eclairage → taille → piece. **Seule `sortie` est intouchable** (le client a indiqué où installer, ça détermine le type de produit).
- **Visibilité UI** des réponses élargies : aucune chip atténuée / barrée / hint pour le MVP. Le texte IA suffit. Si pas clair en test, on ajoutera.
- **Catalogue dans freetext** (Haiku) : NON. Haiku reste sans catalogue. Son rôle est l'extraction JSON, pas la formulation de phrases sur des produits.
- **Catalogue dans chat** (Sonnet) : déjà présent, inchangé.
- **Catalogue dans advice** (Sonnet) : oui, à ajouter (objet du fix).

### Notes pour le retour

Au retour, indiquer :
- Hash des commits livrés
- Un échantillon de prompt complet `advice` (capturé via `error_log`) avec les 3 nouvelles sections visibles
- Les cas testés (au moins : cuisine+petite+mur+neutre, cuisine+grande+plafond+moderne, escalier+ouvert)
- Tout effet de bord (longueur de réponse IA, temps de réponse, rate-limit)

---

## ✅ Livré

## [RETOUR] Rapatriement voix Robin V2 + règles métier dans les prompts IA V3
**Date livrée :** 2026-05-21
**Branche :** `test-theme-sapi-maison`
**Commit :** `318b112`
**Statut :** Livré.

### Implémentation

**Helper créé :** `sapi_megafilter_load_v2_prompts($with_exemples = false)` dans `functions.php`. Lit les 4 fichiers `assets/guide-prompt-*.txt` (ton + savoir + regles obligatoires, exemples optionnel) et retourne la concaténation prête à coller en tête d'un system prompt.

**Injection dans les 3 builders :**

| Builder | Modèle | V2 prompts injectés | Position |
|---|---|---|---|
| `sapi_megafilter_build_freetext_prompt` | Haiku (extraction JSON) | ton + savoir + regles | En tête, avant `"Tu es Robin..."` |
| `sapi_megafilter_build_chat_prompt` | Sonnet (chat libre) | ton + savoir + regles + **exemples** | En tête, avant `"Tu es Robin..."` |
| Prompt inline de `sapi_ajax_megafilter_advice` | Sonnet (phrase finale) | ton + savoir + regles | En tête, avant `"Tu es Robin..."` |

**Pattern V2 respecté :** `exemples.txt` UNIQUEMENT dans le mode conversationnel (chat S2 — équivalent V2 `build_step_prompt`). Pas d'exemples dans les prompts à sortie JSON structurée (équivalent V2 `call_recommendation`) — risque de pollution sinon.

**Aucune modification :** format de sortie JSON, whitelist anti-hallucination, hooks AJAX, appels JS.

### Notes opérationnelles

- **Taille** : +22 KB max ajoutés en tête (les 4 .txt totalisent ~22 KB). Largement dans les marges Haiku/Sonnet (200K tokens de contexte).
- **Échantillon de prompt** : pas capturé localement (pas de PHP CLI dispo). À vérifier sur le live via `error_log($system_prompt)` temporaire si besoin.
- **Effets de bord à observer en test** :
  - Mode chat S2 : Robin devrait tutoyer systématiquement + refuser activement les lampes à poser pour une cuisine (règle de `regles.txt`)
  - Card "Mon projet" : `advice_text` devrait refléter le ton artisan
  - Mode freetext : extraction JSON toujours fiable, message court chaleureux

### Question pour Robin

- Test sur `test.atelier-sapi.fr` : parcours complet en modale (S0 → questions → S1 → IA) sur une cuisine pour observer si Robin refuse bien les lampes à poser
- Test chat S2 : soumettre un freetext → vérifier ton chaleureux des exemples V2

---

## [TÂCHE — LIVRÉ] Rapatrier la voix Robin + règles métier V2 dans les prompts IA V3
**Date :** 2026-05-21
**Branche :** `test-theme-sapi-maison`
**Priorité :** normale

### Contexte

L'audit IA du 21/05 a confirmé que les 3 system prompts V3 actuels (`sapi_megafilter_build_freetext_prompt`, `sapi_megafilter_build_chat_prompt`, et le prompt inline de `sapi_ajax_megafilter_advice`) **ne réutilisent pas** les 4 fichiers `assets/guide-prompt-*.txt` construits pour V2. Conséquence : la voix de Robin (tutoiement chaleureux, artisan passionné) et les règles métier dures (cuisine sans lampe à poser, multi-ampoules, escalier, applique kit prise…) n'agissent plus sur les sorties IA quand un visiteur passe par la modale V3.

Cette tâche rapatrie la logique V2 dans V3 **sans changer l'UX ni le format de sortie**. On respecte le même découpage que V2 :
- `exemples.txt` injecté **seulement** dans le mode conversationnel (chat S2) — comme V2 l'injecte dans `sapi_robin_build_step_prompt`
- `exemples.txt` **pas injecté** dans les prompts à sortie JSON structurée — comme V2 ne l'injecte pas dans `sapi_robin_call_recommendation`

Référence : rapport d'audit complet dans `business/docs/audit-appels-ia-master-vs-test-2026-05-21.md` (côté Cowork).

### À faire

Dans `functions.php` (branche `test-theme-sapi-maison`), modifier les 3 builders de system prompt V3 pour injecter le contenu des fichiers `.txt` en tête du prompt actuel.

**Pattern de chargement à reprendre tel quel** depuis `sapi_robin_build_step_prompt` (l. ~2960 actuel test) :

```php
$theme_dir = get_template_directory();
$ton      = @file_get_contents($theme_dir . '/assets/guide-prompt-ton.txt') ?: '';
$savoir   = @file_get_contents($theme_dir . '/assets/guide-prompt-savoir.txt') ?: '';
$regles   = @file_get_contents($theme_dir . '/assets/guide-prompt-regles.txt') ?: '';
$exemples = @file_get_contents($theme_dir . '/assets/guide-prompt-exemples.txt') ?: '';
```

**Recommandation perso : créer un helper unique** pour éviter de dupliquer 4 fois ce bloc :

```php
/**
 * Charge le contenu des 4 fichiers prompt V2 et retourne la concaténation
 * prête à coller en tête d'un system prompt méga-filtre.
 *
 * @param bool $with_exemples Inclure guide-prompt-exemples.txt (verbeux, à
 *                            réserver aux prompts conversationnels).
 * @return string
 */
function sapi_megafilter_load_v2_prompts($with_exemples = false) {
  $theme_dir = get_template_directory();
  $ton      = @file_get_contents($theme_dir . '/assets/guide-prompt-ton.txt') ?: '';
  $savoir   = @file_get_contents($theme_dir . '/assets/guide-prompt-savoir.txt') ?: '';
  $regles   = @file_get_contents($theme_dir . '/assets/guide-prompt-regles.txt') ?: '';
  $out  = $ton . "\n\n" . $savoir . "\n\n" . $regles . "\n\n";
  if ($with_exemples) {
    $exemples = @file_get_contents($theme_dir . '/assets/guide-prompt-exemples.txt') ?: '';
    if ($exemples) {
      $out .= "EXEMPLES DE CONSEILS PAR ÉTAPE (pour le ton et la direction) :\n" . $exemples . "\n\n";
    }
  }
  return $out;
}
```

**Modifications par endpoint :**

| Builder | Fichiers à injecter | Position |
|---|---|---|
| `sapi_megafilter_build_freetext_prompt` (Haiku, extraction filtres) | ton + savoir + regles (**pas** exemples — risque de pollution sortie JSON) | En tête du prompt actuel, avant `"Tu es Robin, artisan menuisier lyonnais…"` |
| `sapi_megafilter_build_chat_prompt` (Sonnet, conversation libre) | ton + savoir + regles + **exemples** (équivalent V2 `build_step_prompt`) | En tête du prompt actuel |
| Prompt inline de `sapi_ajax_megafilter_advice` (Sonnet, phrase finale) | ton + savoir + regles (**pas** exemples — équivalent V2 `call_recommendation`) | En tête du prompt actuel |

Le **reste de chaque prompt** (sections "FILTRES DISPONIBLES", "CATALOGUE", "FORMAT DE RÉPONSE", "RÈGLES" inline) reste **strictement inchangé**. On ne fait qu'ajouter du contexte en amont.

**Aucune modification :**
- du format de sortie JSON de chaque endpoint
- de la whitelist anti-hallucination dans freetext et chat
- des hooks AJAX
- des appels côté JS (`sapi-modal-conseiller.js`, `sapi-cards-conseiller.js`)

### Critères de succès

1. Les 3 system prompts V3 contiennent le contenu des fichiers `.txt` en tête (vérifiable en faisant un `error_log()` temporaire du prompt complet sur un appel test).
2. La structure de sortie JSON de chaque endpoint reste **identique** (pas de régression front).
3. La whitelist anti-hallucination de freetext continue de filtrer correctement.
4. Test manuel sur test.atelier-sapi.fr — observer une amélioration sur :
   - **Mode chat S2** : Robin tutoie systématiquement, ton chaleureux d'artisan, mentionne les essences/règles si pertinent. Refuse activement les lampes à poser pour une cuisine (règle de `regles.txt`).
   - **Card "Mon projet" sur /mes-creations/** : l'`advice_text` reflète la voix Robin (Square Peg signature côté front, mais le texte lui-même doit avoir le ton artisan).
   - **Mode freetext** : extraction de filtres toujours fiable (JSON correct), message court (1-2 phrases) et chaleureux.
5. Pas de timeout — les 4 fichiers font au total ~22 KB, largement dans les marges de Haiku/Sonnet.

### Notes pour le retour

Au retour, indiquer :
- Le hash des commits livrés
- Si un helper a été créé (et son nom)
- Un échantillon de prompt complet (capturé via `error_log()` puis retiré) pour vérifier que les 4 fichiers sont bien injectés
- Tout effet de bord observé en test (rate limit, longueur réponse, ton)

---

## [ANNULÉE] Mass-update GPC Pinterest + Brand Google for WC + Condition variations (script one-shot)
**Date :** 2026-05-21
**Statut :** Robin gère l'exécution du snippet lui-même via Code Snippets. Pas besoin de Claude Code. Voir le guide dans `business/docs/snippet-pinterest-mass-update-mai-2026.md`.

### Contexte (pour archive)

Diagnostic Pinterest mai 2026 : sur 167 produits ingérés dans Pinterest, **141 sont en warning** parce que `google_product_category` est soit manquante (105), soit incomplète (34 avec seulement 2 niveaux `Home & Garden > Lighting`). Conséquence : -90% de visibilité organique sur Pinterest (1 960 impressions/mois sur 167 produits = 11,7 par produit, vs norme 200-1000).

Le plugin **Pinterest for WooCommerce** expose un champ "Catégorie Google" dans l'onglet Pinterest de chaque fiche produit parent (UI native), avec propagation auto vers les variations. Pas d'UI de mass-update — d'où ce script one-shot.

On en profite pour aligner :
- **Brand = "Atelier Sâpi"** dans Google for WC sur chaque parent (déjà géré globalement via Attribute Mapping mais Robin veut la persistance au niveau produit)
- **Pinterest Condition = "new"** sur toutes les variations (actuellement "Default" partout, pas de signal envoyé)

### Mapping retenu (catégorie WC → Google Product Category)

| Slug catégorie WC | Google Product Category (chaîne complète) |
|---|---|
| `suspensions` | `Home & Garden > Lighting > Light Fixtures > Ceiling Light Fixtures` |
| `lampadaires` | `Home & Garden > Lighting > Lamps > Floor Lamps` |
| `lampesaposer` | `Home & Garden > Lighting > Lamps > Table Lamps` |
| `appliques` | `Home & Garden > Lighting > Light Fixtures > Wall Light Fixtures` |
| `accessoires` | **EXCLU** — ne rien modifier sur ces produits |

### À faire

**Étape 1 — Découverte des meta keys exactes** (préalable obligatoire)

**Méthode sécurisée — Rosetta stone via produits pilotes :**

Robin va configurer **manuellement avant cette tâche** un produit pilote + une variation pilote dans WP admin (Pinterest > Catégorie Google + Brand Google for WC sur le parent, Pinterest > Condition sur 1 variation). Les IDs des produits pilotes seront communiqués dans la tâche queue.

Pour identifier les meta keys exactes :

1. Récupérer les IDs produits pilotes communiqués par Robin (parent + variation).
2. Requête DB directe :
   ```sql
   SELECT meta_key, meta_value
   FROM wp_postmeta
   WHERE post_id IN (<ID_parent>, <ID_variation>)
     AND (meta_key LIKE '%pinterest%' OR meta_key LIKE '%gla%' OR meta_key LIKE '%google_product_category%' OR meta_key LIKE '%brand%')
   ORDER BY post_id, meta_key;
   ```
3. Identifier les 4 meta keys cibles (Pinterest GPC parent, Pinterest Condition variation, GLA GPC parent, GLA Brand parent).
4. **Validation croisée** : grep ces meta keys dans `wp-content/plugins/pinterest-for-woocommerce/src/` et `wp-content/plugins/google-listings-and-ads/src/` pour vérifier que ce sont bien les clés primaires utilisées par les plugins (pas des sous-clés ou des artefacts).

**Méthode fallback (si Robin n'a pas eu le temps de faire le pilote) :**

Grep direct dans `wp-content/plugins/pinterest-for-woocommerce/` et `wp-content/plugins/google-listings-and-ads/` pour identifier les meta keys utilisées :
- Pinterest "Catégorie Google" sur le produit parent → probablement `_pinterest_for_woocommerce_google_product_category` mais à confirmer
- Pinterest "Condition" sur la variation → probablement `_pinterest_for_woocommerce_condition`
- Google for WC "Google Product Category" → probablement `_wc_gla_google_product_category`
- Google for WC "Brand" → probablement `_wc_gla_brand`

Dans tous les cas, confirmer le **slug exact** de la catégorie WC "accessoires" via `wp term list product_cat` (peut être `accessoires`, `accessoire`, `accessories`...).

**Étape 2 — Écrire le snippet PHP "Run once"**

Snippet à créer dans Code Snippets WP, type "Run once", avec ce squelette :

```php
<?php
/**
 * One-shot mass-update : GPC Pinterest + Google for WC + Brand + Condition variations.
 * Date : 2026-05-21
 * À exécuter une seule fois puis supprimer.
 */

// =========================================================================
// CONFIG — à ajuster après découverte des meta keys exactes (étape 1)
// =========================================================================
$dry_run = true; // <<<<< METTRE À false POUR EXÉCUTION RÉELLE

$gpc_mapping = array(
    'suspensions'   => 'Home & Garden > Lighting > Light Fixtures > Ceiling Light Fixtures',
    'lampadaires'   => 'Home & Garden > Lighting > Lamps > Floor Lamps',
    'lampesaposer'  => 'Home & Garden > Lighting > Lamps > Table Lamps',
    'appliques'     => 'Home & Garden > Lighting > Light Fixtures > Wall Light Fixtures',
);

$excluded_categories = array( 'accessoires' ); // à confirmer slug exact

// Meta keys (à confirmer par grep dans les plugins)
$meta_pinterest_gpc       = '_pinterest_for_woocommerce_google_product_category';
$meta_pinterest_condition = '_pinterest_for_woocommerce_condition';
$meta_gla_gpc             = '_wc_gla_google_product_category';
$meta_gla_brand           = '_wc_gla_brand';

$brand_value = 'Atelier Sâpi';

// =========================================================================
// LOGIQUE
// =========================================================================
$log = array();
$updated_parents = 0;
$updated_variations = 0;
$skipped_accessoires = 0;
$skipped_no_category = 0;

$products = wc_get_products( array(
    'limit'  => -1,
    'status' => 'publish',
    'type'   => array( 'simple', 'variable' ),
) );

foreach ( $products as $product ) {
    $product_id = $product->get_id();
    $slugs = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'slugs' ) );

    if ( is_wp_error( $slugs ) || empty( $slugs ) ) {
        $skipped_no_category++;
        $log[] = "SKIP #$product_id (" . $product->get_name() . ") : aucune catégorie";
        continue;
    }

    if ( array_intersect( $excluded_categories, $slugs ) ) {
        $skipped_accessoires++;
        $log[] = "SKIP #$product_id (" . $product->get_name() . ") : accessoire";
        continue;
    }

    // Identifier la catégorie cible
    $matched_category = null;
    foreach ( $slugs as $slug ) {
        if ( isset( $gpc_mapping[ $slug ] ) ) {
            $matched_category = $slug;
            break;
        }
    }

    if ( ! $matched_category ) {
        $skipped_no_category++;
        $log[] = "SKIP #$product_id (" . $product->get_name() . ") : aucune catégorie mappée (slugs : " . implode( ',', $slugs ) . ")";
        continue;
    }

    $gpc = $gpc_mapping[ $matched_category ];

    if ( ! $dry_run ) {
        update_post_meta( $product_id, $meta_pinterest_gpc, $gpc );
        update_post_meta( $product_id, $meta_gla_gpc, $gpc );
        update_post_meta( $product_id, $meta_gla_brand, $brand_value );
    }
    $updated_parents++;
    $log[] = "OK parent #$product_id (" . $product->get_name() . ") → GPC = $gpc, brand = $brand_value";

    // Mettre à jour les variations
    if ( $product->is_type( 'variable' ) ) {
        $variation_ids = $product->get_children();
        foreach ( $variation_ids as $variation_id ) {
            if ( ! $dry_run ) {
                update_post_meta( $variation_id, $meta_pinterest_condition, 'new' );
            }
            $updated_variations++;
            $log[] = "  └─ OK variation #$variation_id → Pinterest condition = new";
        }
    }
}

// =========================================================================
// REPORT
// =========================================================================
$report = sprintf(
    "[Mass-update Pinterest/GMC %s]\nParents updated : %d\nVariations updated : %d\nSkipped accessoires : %d\nSkipped no-category : %d\n\nDétail :\n%s",
    $dry_run ? 'DRY-RUN' : 'LIVE',
    $updated_parents,
    $updated_variations,
    $skipped_accessoires,
    $skipped_no_category,
    implode( "\n", $log )
);

// Écrire le rapport dans un fichier pour audit
$log_file = WP_CONTENT_DIR . '/uploads/mass-update-' . date('Y-m-d-His') . '.log';
file_put_contents( $log_file, $report );

error_log( "[Mass-update] Rapport écrit dans : $log_file" );
error_log( $report );
```

**Étape 3 — Exécuter en dry-run d'abord**

1. Coller le snippet dans Code Snippets, type "Run once", `$dry_run = true`
2. Sauvegarder + activer
3. Lire le fichier log généré dans `wp-content/uploads/`
4. **Renvoyer le log à Robin via le claude_code_queue pour validation** avant exécution réelle

**Étape 4 — Exécution réelle après validation Robin**

1. Passer `$dry_run = false`
2. Ré-activer le snippet (Run once)
3. Vérifier le nouveau log
4. **Désactiver et supprimer le snippet** une fois exécuté
5. Confirmer à Robin via le claude_code_queue

### Critères de succès

1. **Dry-run** : log montre ~24 parents updated + ~140 variations updated + N accessoires skipped + 0 erreurs
2. **Exécution réelle** : log identique au dry-run, tous les metas posés en DB
3. **Vérification manuelle** : Robin ouvre 2-3 fiches au hasard dans WP admin → la "Catégorie Google" Pinterest est bien remplie + la Condition variation est bien "new" + le Brand Google for WC est bien "Atelier Sâpi"
4. **Pas de side-effect** sur les accessoires (à vérifier en ouvrant un accessoire après exécution)

### Notes pour le retour

Au retour, indiquer :
- Les meta keys exactes trouvées dans les deux plugins
- Le slug exact de la catégorie accessoires (et tout slug "produit non-luminaire" qu'il aurait fallu exclure)
- Le log complet du dry-run et de l'exécution réelle
- Toute anomalie (produit sans catégorie, catégorie inattendue, échec d'écriture meta…)
- L'emplacement du fichier log d'audit pour archivage

### Étapes post-exécution (Robin, côté Cowork)

Une fois Claude Code a confirmé l'exécution :
1. Sync Pinterest forcée (Pinterest > Settings > Sync)
2. Attendre 24h pour le re-ingest complet
3. Vérifier dans Pinterest > Catalog > Diagnostics : warnings passent de 141 à <5
4. Vérifier dans Google for WC > Product Feed : statut des produits, warnings GMC

---

## [RETOUR] Polish modale — Titres avec contexte (dynamiques par pièce) + choix taille raccourcis
**Date livrée :** 2026-05-21
**Branche :** `test-theme-sapi-maison`
**Statut :** Livré, prêt à tester.

### Contexte

Suite à F2a-sexies, l'utilisateur peut atterrir au milieu du parcours modale via la chip-card "Mon projet" — sans contexte amont sur la pièce qu'il est en train de configurer. Les titres "Style de l'intérieur ?" ou "Hauteur sous plafond ?" perdent leur ancrage. On rallonge légèrement les titres et on dynamise certains par pièce.

### Commits

1. **`50174be`** Titres modale avec plus de contexte
2. **`c9c563a`** Choix taille raccourcis "Petite/Standard/Grande"

### Titres modale (`inc/guide-data.php`)

**Statiques :**
| Step | Avant | Après |
|---|---|---|
| piece | Pour quelle pièce ? | _inchangé_ |
| taille_escalier | Quel type d'escalier ? | _inchangé_ |
| eclairage | Éclairage principal ? | **Ce sera l'éclairage principal ?** |
| sortie | Où l'installer ? | **Où installerez-vous votre luminaire ?** |
| hauteur | Hauteur sous plafond ? | **Quelle hauteur sous plafond ?** |

**Dynamiques par pièce (via `dynamic_question.piece`)**
| Step | Fallback | Variantes |
|---|---|---|
| **taille** | Quelle taille fait votre pièce ? | cuisine / bureau / salon / chambre / entrée |
| **style** | Quel style pour votre intérieur ? | cuisine / bureau / salon / chambre / entrée / escalier |
| **table** | Au-dessus d'une table ou d'un îlot ? | cuisine ("votre table ou d'un îlot") / bureau / salon / chambre — ajout de "votre" |

→ Ex. avec piece=cuisine et taille pas répondu : la modale affiche *"Quelle taille fait votre cuisine ?"*

### Côté JS — sapi-cards-conseiller.js

- Ajout du helper `getDynamicQuestion(step, answers)` (mirror exact de celui de sapi-modal-conseiller.js)
- `renderInlineQuestion(step, answers)` honore désormais `dynamic_question` → la chip-question sur la card "Mon projet" affiche aussi le titre dynamique par pièce

### Choix taille — labels raccourcis

| Avant | Après |
|---|---|
| Petite pièce | **Petite** |
| Pièce standard | **Standard** |
| Grande pièce | **Grande** |
| Je ne sais pas | _inchangé_ |

Le mot "pièce" est désormais redondant puisque la question le mentionne déjà ("Quelle taille fait votre cuisine ?"). Le `dim` subtitle (intime / confortable / spacieuse) reste inchangé.

**Slugs inchangés** (`petite/moyenne/grande/ne-sais-pas`) → aucun impact sur :
- filtrage produits
- mapping S/M/L des variations
- projets déjà persistés en localStorage

### Effet de bord positif

Les chips affichées sur la card "Mon projet" et dans les récaps (S3, pill produit) deviennent plus concises. Ex. pill produit : **Cuisine · Petite · Peuplier ✓**

### Question pour Robin

- **Test** : ouvrir la modale via la chip-card de `/mes-creations/?piece=cuisine` → vérifier que les titres se cuisinisent ("Quelle taille fait votre cuisine ?", "Quel style pour votre cuisine ?", "Au-dessus de votre table ou d'un îlot ?")
- Idem pour `?piece=chambre` (devrait afficher "votre chambre", "votre lit" pour table)
- Récap S3 / pill produit : vérifier que les labels raccourcis "Petite/Standard/Grande" rendent bien

---

## [RETOUR] F2a-sexies — Card "Mon projet" : chip-question d'accroche + lien Modifier + card cliquable + 2 fixes
**Date livrée :** 2026-05-21
**Branche :** `test-theme-sapi-maison`
**URL test :** `test.atelier-sapi.fr/mes-creations/?piece=chambre` (ou `?piece=cuisine`)
**Statut :** Livré. La card invite désormais explicitement le visiteur à continuer son parcours.

### Commits de la séquence

1. **`3b77461`** F2a-sexies — Implémentation initiale (chip-question + lien Modifier + 3 états)
2. **`3319dbe`** F2a-sexies-bis — Card cliquable comme un tout (sur retour utilisateur)
3. **`7192bc6`** Fix — Ordre update/dispatch inversé (modale s'ouvrait sur la question déjà répondue)
4. **`d1d598e`** Fix — Dispatch sur document (chip détaché par le re-render bloquait le bubbling)

### Bugs corrigés en cours de route

**Bug #1 : modale s'ouvrait sur la question qu'on venait de répondre**
- Cause : le spec disait "dispatch AVANT update pour éviter le flash", mais `openModal` appelle `hydrateFromProject()` immédiatement → lit le projet AVANT que l'update soit appliqué → `determineInitialState` retourne la même question comme "non répondue"
- Fix : inverser l'ordre — update en PREMIER, dispatch ensuite. La modale lit l'état frais.
- Aucun flash perceptible : la modale s'ouvre dans le même tick que le re-render de la card.

**Bug #2 : modale ne s'ouvrait plus du tout après le fix #1**
- Cause : `sapiProject.update` notifie le subscribe → render → `renderInlineQuestion` réécrit `els.inlineQuestion.innerHTML` → le chip cliqué est DÉTACHÉ du DOM. Dispatcher l'event DEPUIS un chip détaché ne bubble pas jusqu'à `document` où le listener écoute.
- Fix : `document.dispatchEvent(...)` directement (pas besoin de bubbling, le listener est sur document).

### Implémentation

**1. Markup `woocommerce/archive-product.php`**
- Conversion `<button>` Mon projet → `<section>` (la card n'est plus cliquable comme un tout)
- Ajout du lien Modifier `<a class="conseiller-mon-projet__edit" data-action="open-modal" data-modal-state="s3" data-mon-projet-edit hidden>` positionné absolute coin haut-droite
- Ajout de la zone `<div class="conseiller-mon-projet__inline-question" data-inline-question hidden></div>` sous la phrase IA

**2. JS `assets/sapi-cards-conseiller.js`**
- Nouveau helper `getNextUnansweredStep(answers)` : retourne le step COMPLET (question + choices) de la prochaine question visible non répondue, ou null si parcours complet
- Helper `escHtml()` pour injection sécurisée des labels de choix
- Helper `renderInlineQuestion(step)` : injecte le markup `<span class="inline-question__label">…</span><div class="inline-question__answers"><button class="answer-chip" data-step-id="…" data-slug="…" data-label="…">…</button>…</div>`
- Refactor `renderMonProjet()` : 3 états gérés
  - **awaiting** (inchangé) : dots pulsants, chip et edit masqués
  - **parcours incomplet** : phrase IA générique + chip-question affiché + edit caché
  - **parcours complet** : phrase IA enrichie + chip caché + edit affiché
- Nouveau handler `handleChipAnswer(chip)` : dispatch `sapi:open-modal { state: 's0' }` AVANT `sapiProject.update()` (évite le flash visuel — la modale est en cours d'ouverture quand le subscribe re-render)
- Délégation click étendue : `.answer-chip[data-step-id]` capturé en priorité, sinon fallback sur `[data-action="open-modal"]`

**3. CSS `style.css`**
- Suppression hover global `.conseiller-card--mon-projet:hover` (translate + shadow + badge orange) — la card n'est plus un bouton
- Suppression `cursor: pointer` + `border: none` (reset button devenu inutile)
- Ajout `position: relative` sur la card pour ancrer le lien Modifier
- `.conseiller-mon-projet__edit` : pill coin haut-droit, 12px wood, border-bottom dashed, hover orange
- `.conseiller-mon-projet__inline-question` : flex wrap centré, gap 10/14px, margin-top 18
- `.inline-question__label` : 13px italic wood, font-weight 600
- `.answer-chip` : 8px 16px white bg + 1.5px dashed wood-35% + radius 50, hover warm bg + border orange + translateY -1

### Comportement bout-en-bout
1. Visiteur arrive sur `/mes-creations/?piece=chambre` → card "Mon projet" affiche phrase générique chambre + chip "Taille de la pièce ?" avec 4 chips
2. Clic sur "Pièce standard" → modale s'ouvre sur S0 hybride → `determineInitialState` détecte projet partiel → renderS0Hybrid avec next step (Style de l'intérieur ? si chambre, car eclairage/sortie/hauteur/table tous masqués pour ne-pas-grande)
3. Visiteur finit le parcours → animation sortie → IA → advice_text → retour grille
4. Card "Mon projet" : phrase enrichie + lien Modifier en haut-droite + plus de chip-question
5. Clic Modifier → modale S3 carrefour (3 actions : Recommencer / Préciser / Voir la sélection)

### Question pour Robin
- **Tests** :
  - `/mes-creations/?piece=chambre` → vérifier chip "Taille de la pièce ?" en bas de la card
  - `/mes-creations/?piece=cuisine` → vérifier chip "Taille de la pièce ?" puis enchaînement automatique vers les questions cuisine (éclairage si grande, sortie, hauteur si plafond, table)
  - Compléter un parcours → vérifier que le chip disparaît et que le lien Modifier apparaît
  - Mobile 375px : chip wrap proprement

---

## [RETOUR] Finitions production — Cleanup + harmonisation design + raccourcis titres + loading dots
**Date livrée :** 2026-05-20
**Branche :** `test-theme-sapi-maison`
**Statut :** Tout sur test, prêt à merger master après validation finale Robin.

### Commits livrés depuis F2b Phase 4

1. **`4e76747` Cleanup mega-filtre.js orphelin**
   - Suppression de `assets/mega-filtre.js` (894 lignes mortes neutralisées en F1a)
   - Suppression de l'enqueue + localize SAPI_MEGAFILTER dans `functions.php`
   - Remplacement de la dep `'sapi-mega-filtre'` par `'sapi-maison-shop'` sur sapi-cards-conseiller
   - **Bilan : -913 lignes nettes**

2. **`4ce79e3` Harmonisation roompicker → design system Conseiller (référence : modale)**
   - `.room-picker-title` : uppercase wood-dark letter-spacing 0.02em (= conseiller-h2)
   - `.room-picker-sub` : 14.5px wood-mid line-height 1.55 max-width 580px (= conseiller-subtitle)
   - `.room-card` : fond blanc + bordure 1px var(--color-line) + radius 12 + shadow-card (au lieu de cream + bordure orange + radius 16). Hover : transform Y-2 + shadow-card-hover + bordure wood
   - `.room-card-icon` : 48x48 + SVG 24x24 (au lieu de 56/30)
   - `.room-card-label` : 12px UPPERCASE letter-spacing 0.08em wood-dark
   - Conteneurs outer `.advice-room-picker` + `.bento-room-picker` : radius 16, plus de hover sur le panneau

3. **`f37977d` Roompicker : bordure dashed INSET (=modale)**
   - Pseudo `::before` `inset: 12px` + 1.5px dashed wood-35% + radius 12 (= pattern .conseiller-card)
   - Suppression de la bordure directe `2px dashed wood-30%` qui était plaquée au bord

4. **`1b86eed` Raccourcissement titres modale (7 questions + intro récap)**
   - `inc/guide-data.php` : toutes les questions raccourcies (cf. tableau ci-dessous)
   - `sapi-modal-conseiller.js` : intro récap fiche produit → "Pour votre cuisine, Robin recommande :" (suppression de la dimension)

5. **`977dea3` Dots loading IA "Mon projet" : plus gros + bien centrés**
   - Bump font-size 28 → clamp(44, 5.5vw, 60)
   - `display: block` sur le pseudo ::after → centré par text-align parent
   - `.conseiller-signature` passe de opacity:0 à display:none en awaiting → libère l'espace pour le centrage

6. **`7077612` Dots loading : 3 vrais ronds décalés en cascade**
   - Remplacement du glyph "·" par 3 spans `<span class="conseiller-awaiting-dot">` injectés en JS
   - Chaque rond 18x18 wood radius 50% → vrai disc visible
   - Espacement inchangé (margin 0 4px)
   - Animation décalée : dot 2 à +0.18s, dot 3 à +0.36s, scale 0.85↔1 + opacity 0.2↔0.85 sur 1.2s

### Tableau des titres modale raccourcis

| Question | Avant | Après |
|---|---|---|
| piece | Pour quelle pièce cherchez-vous un luminaire ? | Pour quelle pièce ? |
| taille | Quelle est la taille de votre pièce ? | Taille de la pièce ? |
| eclairage | Ce luminaire sera-t-il votre principale source de lumière ? | Éclairage principal ? |
| sortie | Où installerez-vous votre luminaire ? | Où l'installer ? |
| hauteur | Quelle est votre hauteur sous-plafond ? | Hauteur sous plafond ? |
| style | Quel est le style de votre intérieur ? | Style de l'intérieur ? |
| table cuisine | Sera-t-il au-dessus d'une table ou d'un îlot ? | Au-dessus d'une table ou d'un îlot ? |
| table bureau | Sera-t-il au-dessus du bureau ? | Au-dessus du bureau ? |
| table salon | Sera-t-il au-dessus d'une table ? | Au-dessus d'une table ? |
| table chambre | Sera-t-il au-dessus du lit ? | Au-dessus du lit ? |
| taille_escalier | Quel type d'escalier ? | _déjà court — gardé_ |
| Récap fiche produit | Pour votre cuisine de taille petite pièce, Robin recommande : | Pour votre cuisine, Robin recommande : |

### Décisions encore en attente (héritées F2a)
1. **Card sur-mesure** sur `/mes-creations/` : toujours masquée (enqueue commenté dans `functions.php`). Réactiver / supprimer / revoir le wording ?
2. **Brevo opt-in** sur le formulaire sur-mesure : toujours bloqué
3. **Merge master** : tout le bloc F2a + F2b + cleanup + finitions est sur `test-theme-sapi-maison`. Plus rien de cassé ni en attente sur le code, donc dès que Robin valide le test → merge.

---

## [RETOUR] F2b Phase 3 — Pré-sélection variation au load + hint + listener apply
**Date livrée :** 2026-05-20
**Branche :** `test-theme-sapi-maison`
**URL test :** `test.atelier-sapi.fr/produit/<un-luminaire-variable>/`
**Statut :** Phase 3/4 livrée. La logique éprouvée pré-F1c (pattern jQuery `wc_variation_form`) est reprise, adaptée à `sapiProject` au lieu de `sapiGuidePrefs`.

### Livraison Phase 3
- `assets/sapi-product-preselect.js` (nouveau, ~190 lignes) :
  - **AU LOAD** : si `sapiProject` contient `taille` (ou `taille_escalier`), map vers code S/M/L (`petite→S`, `moyenne→M`, `grande→L`, `escalier ouvert→M`), trouve l'option dans `select[name="attribute_pa_taille"]` (ou variantes `pa_format`, `pa_taille*`), applique + trigger `change` jQuery (pour que WC réagisse)
  - **ÉVÉNEMENT** `sapi:apply-product-selection` (dispatché par la modale au CTA "Appliquer cette sélection") : utilise en priorité l'ID variation retourné par l'IA serveur (lit `data-product_variations` pour mapper variation_id → attributs), fallback sur mapping S/M/L côté client
  - Matching cascade : value exacte → préfixe (s-petit, l-grande) → label textuel → index (S=0, M=1, L=2) en dernier recours
  - Échec silencieux si rien ne matche (décision Robin C)
  - Hint discret "✓ Pré-sélectionné pour votre projet" injecté dans le `<th class="label">` de l'attribut taille (apparait avec un fade léger)

- `functions.php` : enqueue `sapi-product-preselect` sur `is_product()` avec deps `['sapi-project', 'jquery']`

- `style.css` : style `.conseiller-preselect-hint` (italic 11px, color wood, opacity 0.85 avec fade-in)

### Ce qui marche désormais bout-en-bout
1. Visiteur arrive sur une fiche produit avec un projet existant → variation taille pré-sélectionnée automatiquement + hint visible
2. Visiteur clique la pill → modale → 3 questions ou récap → "Appliquer cette sélection" → modale ferme + variation pré-sélectionnée + scroll vers le selector + hint apparaît
3. Visiteur sans projet → aucune pré-sélection (état WC standard)
4. Si attribut taille n'a pas de valeur S/M/L matchable → fallback index ; si rien ne matche → silence total (pas d'erreur)

### Question pour Robin
1. **Test #1 — Arrivée directe avec projet** : ouvrir `/mes-creations/`, créer un projet (au minimum piece + taille), puis cliquer sur une carte produit → la fiche doit s'ouvrir avec la taille déjà cochée et un hint "✓ Pré-sélectionné pour votre projet" sous le label Taille
2. **Test #2 — Via pill modale** : ouvrir une fiche produit en navigation directe, cliquer pill, répondre aux 3 questions (ou si projet déjà → récap direct) → "Appliquer cette sélection" doit fermer la modale ET pré-sélectionner la taille
3. **À VÉRIFIER (important)** : sur ton catalogue, l'attribut taille est bien sluggé `pa_taille` (donc `attribute_pa_taille` dans le select WC) ? Et les valeurs sont quoi : `s/m/l`, `petite/moyenne/grande`, ou autre ? Je suppose `s/m/l` mais le matcher couvre plusieurs cas en cascade — dis-moi si rien ne se pré-sélectionne, j'aurai besoin du nom exact de l'attribut + des slugs de variation
4. **Card sur-mesure** (héritage F2a), **Brevo opt-in**, **merge master** : toujours les 3 mêmes décisions en attente

### Ce qui reste optionnel (Phase 4 — finitions)
- Polish UX : meilleure animation de focus sur la variation pré-sélectionnée
- Sauvegarde projet partiel si modale fermée avant la fin (`update` incrémental déjà en place — à vérifier)
- Suppression `assets/guide-personalize.js` si pas déjà fait (legacy F1c, ne sert plus à rien)

---

## [RETOUR] F2b Phase 2 — Mode court + écran s-product-recap + endpoint IA produit
**Date livrée :** 2026-05-20
**Branche :** `test-theme-sapi-maison`
**URL test :** `test.atelier-sapi.fr/produit/<un-luminaire-variable>/`
**Statut :** Phase 2/4 livrée. Le parcours modale sur fiche produit est désormais court (3 questions) et débouche sur une phrase IA dédiée qui recommande une taille explicite.

### Livraison Phase 2
- `functions.php` :
  - Helper `sapi_megafilter_project_to_size_code()` : mapping projet → S/M/L (`petite→S`, `moyenne→M`, `grande→L`, `escalier ouvert→M`, escalier standard → pas de reco)
  - Helper `sapi_megafilter_find_variation_for_size()` : cherche dans les variations WC du produit une variation dont la valeur d'attribut matche le code taille
  - Endpoint AJAX `sapi_megafilter_product_advice` (Sonnet) : prompt qui DOIT citer la taille recommandée explicitement ("Taille L", etc.). Output : `{ advice_text, recommended_variation_id?, recommended_size? }`
  - Localize : ajout de `shortSteps` (whitelist `['piece','taille','taille_escalier','style']`) + `product: {id, name}` (null hors fiche produit)
  - Markup `s-product-recap` ajouté à `sapi_render_conseiller_modal()` : badge + h2 + chips + quote IA (dots loading) + CTA "Appliquer cette sélection" (svg check) + back link "Modifier mes réponses"

- `assets/sapi-modal-conseiller.js` :
  - Nouvel état `state.shortMode` (set par `openModal('product')` uniquement)
  - Refactor visibilité : `computeRawVisibleSteps()` pour `cleanInvisibleAnswers` (ne supprime pas les réponses long-mode quand on est en court), `getVisibleStepIds()` applique le filtre court par dessus
  - `openModal('product')` :
    - Projet complet (piece + taille|taille_escalier + style) → directement `showProductRecap()` (fetch IA immédiat)
    - Projet partiel → `renderS0Hybrid('s0-partiel')`, parcourt SHORT_STEPS uniquement
    - Pas de projet → `renderS0Hybrid('s0-initial')`
  - À la fin du parcours court → `sapiProject.set()` + `showProductRecap()` (au lieu du morphing modale→card du long mode)
  - `showProductRecap()` : populate chips + dots loading + fetch IA produit (mémoïsé via `state.productAdviceFetch`)
  - `applyProductSelection()` : dispatch `sapi:apply-product-selection { productId, variationId, answers, labels }` + close + scroll smooth vers `form.variations_form` (Phase 3 ajoutera le listener qui pré-sélectionne réellement)
  - `modifyProductAnswers()` : reset history + revient sur S0 hybride pour recommencer le parcours court

- `style.css` : styles dédiés `[data-screen="s-product-recap"]` (chips identiques au S3 + nouvelle zone `.conseiller-product-quote` avec dots pulsants + signature en Square Peg)

### Ce qui marche déjà
- Pill sur fiche produit (Phase 1) → clic ouvre la modale en mode court
- **Sans projet** : 3 questions seulement (piece, taille|escalier, style) puis récap avec phrase IA spécifique au produit
- **Avec projet partiel** : reprend là où c'est manquant parmi les 3 questions courtes
- **Avec projet complet** : récap direct + phrase IA dédiée au produit (mention de taille explicite)
- "Appliquer cette sélection" → ferme modale + scroll smooth vers `form.variations_form` (la pré-sélection elle-même est Phase 3)
- "Modifier mes réponses" → revient à S0 pour reprendre le parcours court

### Ce qui reste à faire (Phases 3-4)
- **Phase 3** : listener `sapi:apply-product-selection` sur fiche produit → applique réellement la pré-sélection variation (radio WC) + scroll. Pré-sélection automatique aussi au LOAD de la page si projet existant. Hint discret "✓ Pré-sélectionné pour votre projet" sous le label de l'attribut taille
- **Phase 4** : sauvegarde projet partiel si modale fermée avant la fin (déjà gérée par `update` incrémental) + petites finitions UX

### Question pour Robin
1. **Test #1 — Sans projet** : sur une fiche produit variable, clic pill → modale s'ouvre → réponds aux 3 questions → vérifie que la phrase IA mentionne bien une taille (S/M/L) cohérente
2. **Test #2 — Avec projet** : passe d'abord sur `/mes-creations/` pour créer un projet (au moins piece + taille), reviens sur la fiche → clic pill → modale doit s'ouvrir directement sur le récap avec la phrase IA
3. **Test #3 — Mapping attribut** : sur ton catalogue, l'attribut taille de variation s'appelle bien quelque chose comme "Taille" avec valeurs S/M/L ? Si c'est un autre nom (ex. "Format" → "Petit/Moyen/Grand"), il faudra adapter le matcher dans `sapi_megafilter_find_variation_for_size()`. Pour Phase 3 je récupèrerai les noms d'attribut depuis le catalogue réel.
4. **Card sur-mesure** (héritage F2a) : toujours désactivée — même décision à prendre
5. **Brevo opt-in** : toujours bloqué
6. **Merge master** : on continue F2b jusqu'au bout ou on merge le bloc F2a avant ?

---

## [RETOUR] F2b Phase 1 — Modale partagée + Pill "Comment choisir ?" sur fiche produit
**Date livrée :** 2026-05-20
**Branche :** `test-theme-sapi-maison`
**URL test :** `test.atelier-sapi.fr/produit/<un-luminaire-variable>/`
**Statut :** Phase 1/4 livrée. Câblage uniquement — la modale s'ouvre depuis la fiche produit avec le même contenu que sur /mes-creations/.

### Décisions cadre validées par Robin
- **A — Mapping variation** : petite/intime→S, moyenne/confortable→M, grande/spacieuse→L, escalier ouvert→M
- **B — Recommandation IA** : phrase IA doit explicitement nommer une taille (ex. "Taille L")
- **C — Préselection silencieuse** : si fail, ni erreur ni hint affiché
- **D — Pill dynamique** : "Comment choisir ?" (sans projet) → "Adapter à mon projet" (avec projet)

### Livraison Phase 1
- `functions.php` : nouveau helper `sapi_render_conseiller_modal()` hooké sur `wp_footer` (condition `is_shop() || is_product()`) → la modale est rendue une seule fois, mutualisée entre les deux templates
- `woocommerce/archive-product.php` : markup modale supprimé (était inline en lignes 478-618), placeholder commentaire qui pointe vers le helper
- Bloc enqueues méga-filtre + cards + modale étendu de `is_shop()` à `is_shop() || is_product()`. Les scripts no-op silencieusement si leurs sélecteurs ne sont pas présents
- `woocommerce/single-product.php` : pill `.conseiller-pill-secondary` ajoutée juste au-dessus de `<p class="variations-intro">` (variables uniquement), `data-action="open-modal" data-modal-state="product"` + `data-help-pill-text` pour update live
- `assets/sapi-help-pill.js` (nouveau, ~50 lignes) : subscribe à `sapiProject`, met à jour le texte ("Comment choisir ?" ↔ "Adapter à mon projet"), dispatch `sapi:open-modal { state: 'product' }` au click
- `assets/sapi-modal-conseiller.js` : `openModal('product')` route vers S0 hybride normal — câblage validé sans logique métier (Phase 2 ajoutera le mode court + écran s-product-recap)
- `style.css` : `margin-bottom: 1.25rem` sur la pill dans `.product-form-v2` (+ `margin-top: 0.5rem` desktop)

### Ce qui marche déjà
- Pill visible sur toute fiche produit variable (donc tous les luminaires)
- Click pill → modale s'ouvre par-dessus la fiche produit avec overlay et S0 hybride
- Si projet déjà en localStorage : pill affiche "Adapter à mon projet" et la modale s'ouvre directement sur S3 carrefour
- Modale fonctionne identiquement à la version /mes-creations/ (puisque c'est le même DOM)

### Ce qui reste à faire (Phases 2-4)
- **Phase 2** : mode court (3 questions sur fiche produit pour préselectionner une variation) + écran `s-product-recap` + endpoint `sapi_megafilter_product_advice` (Sonnet, doit citer une taille explicite)
- **Phase 3** : pré-sélection variation au load de fiche produit selon `sapiProject.answers.taille` ou `taille_escalier` (mapping S/M/L) + hint discret "✓ Pré-sélectionné pour votre projet"
- **Phase 4** : CTA "Appliquer cette sélection" → save projet partiel + close modal + preselect + scroll vers variations

### Question pour Robin
1. **Test rapide** : ouvre une fiche produit variable sur `test.atelier-sapi.fr/produit/...`, vérifie que la pill apparaît au bon endroit et que la modale s'ouvre. Si OK, on enchaîne Phase 2.
2. **Card sur-mesure (héritage F2a)** : toujours désactivée. Décision à prendre : réactiver / supprimer / revoir le wording ?
3. **Brevo opt-in sur formulaire sur-mesure** : toujours bloqué. Tu veux qu'on creuse ?
4. **Merge master** : pas encore fait pour F2a + tous les sous-points. Toujours en attente — on continue F2b ou on merge le bloc F2a d'abord ?

---

## [RETOUR] F2a-quater — Modale hybride (suppression des portes) + animation sortie + audit refactor
**Date livrée :** 2026-05-20
**Branche :** `test-theme-sapi-maison`
**URL test :** `test.atelier-sapi.fr/mes-creations/`
**Statut :** Livré et déployé. Périmètre majeur (refonte modale + animation sortie + audit) — recommandation de tests intensifs avant merge master.

### Périmètre livré

**Refonte structurelle S0 hybride (commit `f389a88`)**
- Suppression de l'écran S0 "Que préfères-tu ?" avec ses 2 portes (Questions — Réponses / Décrire ton projet)
- Remplacement par un **écran hybride unique** qui affiche simultanément la question courante + champ texte libre
- 3 sous-états déterminés par `determineInitialState()` selon `sapiProject` :
  - **s0-initial** (projet vide) : badge "Conseil de Robin", question Pièce, placeholder "Décris ton projet en quelques mots…", lien Effacer caché
  - **s0-partiel** (au moins 1 réponse, reste des questions) : badge "Mon projet", question = prochaine non répondue, placeholder "Précise ton projet en quelques mots…", lien "Effacer et recommencer" visible
  - **s3-carrefour** (toutes les visibles répondues) : route vers le S3 inchangé depuis F2a-ter (chips + 3 actions)
- Suppression du markup S2.start (input + suggestions), JS associé (`startFreetextFlow`, `chooseDoor`, handlers `[data-freetext-form]` / `[data-suggestion]`)
- Nouveau `submitFromS0Text` qui bascule de S0 vers S2.chat avec bulle initiale construite côté client (advice_text + "Qu'est-ce que tu veux affiner ?") puis appel Haiku via `submitFreetext` réutilisé
- Délégation `.conseiller-choice` au niveau modale (couvre S0 hybride ET S1 d'un coup)
- Card "Mon projet" : `data-modal-state` passe de `s3` à `s0` (uniformisation — la modale décide)
- Le **point ouvert "Compléter projet partiel"** évoqué dans le retour F2a-ter devient obsolète — l'état partiel affiche directement la prochaine question

**Hauteur modale ajustée (commit `d4eabcb`)**
- `height: 600px → 680px` desktop pour accueillir le S0 hybride (badge + H2 + 6 choices + separator + input + reset) sans scroll interne. Mobile inchangé `calc(100dvh - 32px)`.

**Animation de sortie modale (commits `34e40d5` → `01cfe78`)**
- Suppression de l'écran s-transition "Robin réfléchit" qui forçait une attente passive
- 1re tentative : morphing FLIP modale → position card "Mon projet" en 1s (Robin a trouvé trop rapide)
- Solution retenue : **séquence en 3 phases (~1,9 s)** :
  - Phase 1 (0–600 ms) : fade-out du contenu interne (screens) → opacity 0
  - Phase 2 (500–1100 ms) : fade-out de la modale entière (overlay fade transparent + card-dialog opacity 0 + scale 0.96)
  - Phase 3 (1100–1900 ms) : scroll smooth de la page pour centrer la card "Mon projet" dans la viewport
- Texte (advice IA) apparaît après la séquence via typewriter, signature fade-in
- Pendant l'attente : class `.is-awaiting-advice` sur la card "Mon projet" affiche 3 dots pulsants à la place de la phrase, signature cachée
- ESC + click-outside désactivés pendant `state.transition` (protection animation)

**Réorganisation boutons S3 carrefour (commit `475d5c5`)**
- Avant : `[Voir la sélection (orange)] [Préciser avec Robin (wood-dark)]` côte à côte + lien souligné "Effacer et recommencer" en bas
- Après :
  - `[↻ Recommencer (wood-dark)] [💬 Préciser avec Robin (wood-dark)]` côte à côte (2 secondaires, icônes refresh + bulle de chat)
  - `[VOIR LA SÉLECTION →]` (primaire orange) seul en bas
- "Effacer et recommencer" → "Recommencer" (raccourci) avec promotion en bouton wood-dark
- Nouvelle classe CSS `.conseiller-s3-secondary-actions`

**Centrage et stabilité S0 hybride (commit `98cf84d`)**
- Refonte du centrage : `justify-content: center` sur l'inner du S0 → tout le bloc (badge + h2 + choices + separator + input + reset) est un groupe unique centré verticalement dans la card
- Cohérent état initial et partiel
- Plus de découpe en 2 zones via auto-margins (qui sectionnait la lecture)

**Hotfixes successifs** (résolus en cours de travail)
- Card "Mon projet" : 2 cards visibles simultanément → fix `.conseiller-card--mon-projet[hidden] { display: none }` (commit `17c4a72`)
- Chips récap S3 trop petits → padding+font augmentés (commit `5da8c8a`)
- "Effacer et recommencer" depuis S3 cassait l'écran (S0 vide, reset link visible) → fix `resetFromS3` appelle `renderS0Hybrid('s0-initial')` + nouvelle règle `[hidden]` sur `.conseiller-modal__nav` (commit `bc91aeb`)
- Projet effacé revenait au refresh à cause de `?piece=` dans l'URL → `clear()` nettoie aussi `?piece` via `history.replaceState` (commit `f4328d7`)
- Reset complet du projet quand `?piece=X` arrive avec une pièce différente (depuis home / roompicker) → `clearRaw()` silencieux + update piece, un seul notify (commit `a54a232`)
- Focus ring visible autour du dialog → `outline: none` (commit `c6ab47e`)
- Cards isolées sur la 2e ligne du grid quand 2 ou 4 choices → `.conseiller-choices--2col` appliqué dynamiquement par JS (commit `c6ab47e`)
- Bouton submit du champ texte écrasé (padding du selector global `button {}`) → `padding: 0` explicite (commit `c6ab47e`)
- Plus d'air entre la pill badge et le H2 dans la modale (28px au lieu de 18px) (commit `ea53be6`)

**Audit + refactor (commit `56aa3c8`)**
Suite à la complexité accumulée, lancement d'un agent d'exploration pour identifier le code mort et les conflits CSS. Trouvailles + actions :
- Markup PHP : suppression du screen `s-transition` (orphelin depuis le morphing)
- CSS : suppression `.conseiller-transition-dots` + `.conseiller-suggestions` (anciennes UI) + règles mobile associées
- JS : suppression `startFreetextFlow_DEPRECATED()` + `case 'close'` du switch
- **Nouvelle règle défensive** : `.conseiller-modal [hidden], .conseiller-cards-zone [hidden] { display: none !important }` au top du bloc CSS → prévient les régressions futures (déjà piégé 2× sur mon-projet et modal__nav)
- Commentaire structurant au-dessus des règles margin-auto qui documente l'intention par écran (S0/S1/S3)
- Commentaire `state.screen` mis à jour : `'s0' | 's1' | 's2-chat' | 's3'`
- Bilan : –55 LOC nettes, code mort balayé, garde-fou anti-régression en place

### Critères de succès — état

✅ S0 hybride affiche question + texte libre simultanément, sans écran de portes intermédiaire
✅ Détection automatique état initial / partiel / complet selon `sapiProject`
✅ Clic réponse → bascule S1 sur question suivante avec parcours classique
✅ Submit texte → S2.chat avec bulle initiale (zéro nouvel appel IA pour la bulle)
✅ "Effacer et recommencer" S0 partiel : `sapiProject.clear()` + bascule fluide vers initial dans même modale
✅ S3 carrefour reste fonctionnel (déclenché si projet complet)
✅ Animation sortie en 3 phases ~1,9 s (fade contenu → fade modale → scroll page → texte)
✅ Class `.is-awaiting-advice` affiche dots pendant l'attente IA, typewriter prend le relais à l'arrivée
✅ Modale hauteur 680px desktop / calc(100dvh - 32px) mobile
✅ Card "Mon projet" entièrement cliquable (passe par `data-modal-state="s0"`, modale route automatiquement)
✅ Card sur-mesure toujours masquée
✅ Reset complet du projet quand `?piece=X` arrive avec une autre pièce (depuis home, roompicker, lien externe)
✅ ESC + click-outside protégés pendant l'animation morph
✅ Bouton submit S0 rond avec flèche visible (padding override du `button {}` global)
✅ Grid 2 cols quand 2 ou 4 choices (taille, escalier, etc.)
✅ Focus ring de la card-dialog masqué

### Audit code identifié + résolu

- **s-transition** orphelin (markup PHP + CSS + référence JS) → supprimé
- `.conseiller-suggestions` + `.conseiller-suggestion` CSS orphelin (S2.start retiré) → supprimé
- `startFreetextFlow_DEPRECATED` fonction JS jamais appelée → supprimée
- `case 'close'` du switch jamais déclenché (croix supprimée depuis F2a-quater Phase 3) → supprimé
- Commentaires obsolètes mis à jour
- Règle `[hidden]` défensive globale ajoutée pour la modale + cards-zone

### Écarts vs spec initiale F2a-quater

1. **Animation morph FLIP → séquence 3 phases** : la spec n'imposait pas de méthode précise. 1re tentative en morphing FLIP rejetée par Robin (1s trop rapide), final en séquence 3 phases (1,9 s) avec scroll smooth.
2. **Réorganisation boutons S3** : non prévu dans F2a-quater initial. Robin a demandé en cours de travail (recommencer + préciser en haut, Voir la sélection en bas).
3. **Audit + cleanup** : non prévu, déclenché par Robin face à l'instabilité visuelle après accumulation de hotfixes.

### Tests qui restent à valider par Robin

- [ ] Mobile 375px : S0 hybride avec 6 choices en 2 cols, séparateur + input visibles sans scroll
- [ ] Animation sortie sur connexion réelle : fluidité, timing, scroll smooth fonctionne bien
- [ ] Cycle complet d'un parcours S1 : entrée → 7 questions → sortie animation → card "Mon projet" avec advice IA
- [ ] S2.chat depuis S0 hybride (submit texte) : bulle initiale + fetch Haiku + bulles correctes
- [ ] Effacer depuis S0 partiel ET depuis S3 carrefour : bascule sans bug visuel
- [ ] Roompicker → `/mes-creations/?piece=X` (différent pièce existante) : reset complet du projet précédent
- [ ] Centrage S0 sur tous les écrans (questions à 2, 3, 4, 6 choices)
- [ ] Cas où l'IA est lente (>3s) : dots pulsants restent affichés jusqu'à arrivée

### Questions ouvertes pour Cowork → Robin

1. **Merge master ?** F2a + F2a-bis + F2a-ter + F2a-quater livrés sur `test-theme-sapi-maison`. Le module est en bon état désormais (après audit + refactor), recommandation : tester intensivement en conditions réelles puis merger.
2. **Card sur-mesure** : toujours masquée. Décision pending.
3. **Brevo opt-in** sur form sur-mesure : bloqué tant que card sur-mesure masquée.
4. **F2b** : prochaine tâche dans la queue (fiche produit). Démarrer avant merge master ou après ?

### Prochaine tâche

**F2b** — Logique projet sur fiche produit (pill "Comment choisir ?" + mode court 3 questions + pré-sélection variation). Plan inchangé depuis le retour F2a-ter.

⚠️ F2b devra intégrer le modèle IA F2a-bis (1 appel à la sortie). L'endpoint `sapi_megafilter_product_advice` à créer suivra le même pattern.

---

## [RETOUR] F2a-ter — Carrefour S3 "Modifier mon projet" + raffinements card "Mon projet"
**Date livrée :** 2026-05-19
**Branche :** `test-theme-sapi-maison`
**URL test :** `test.atelier-sapi.fr/mes-creations/`
**Statut :** Livré et déployé. Un point UX ouvert en attente de décision Robin (cf. § Point ouvert plus bas).

### Périmètre livré

**S3 ressuscité comme carrefour 3 actions (commit `2ffb4dd` + ajustements `589686d`, `92a2d0c`)**
- Spec respectée : écran S3 réactivé UNIQUEMENT quand le visiteur ouvre la modale depuis la card "Mon projet" (le flow S1 normal continue de fermer direct via showTransitionAndExit)
- Card "Mon projet" → bouton "Modifier mon projet ✎" pointe vers `data-modal-state="s3"` (au lieu de `s0` avant)
- Markup S3 : badge wood-dark "Mon projet" + H2 "Voici ton projet" + chips récap (lecture seule) + 3 actions
- 3 actions hiérarchisées :
  - **Voir la sélection** (pill orange primaire) → close modale + scroll vers grille
  - **Préciser avec Robin** (pill wood-dark secondaire) → bascule S2.chat avec **bulle initiale construite côté client** à partir de `sapiProject.advice_text` (ou texte générique de la pièce via `SAPI_CARDS_CONSEILLER.genericAdvice`) suivi de "Qu'est-ce que tu veux affiner ?". La bulle est pushée dans `state.chat.conversation` pour que les messages suivants l'incluent dans l'historique envoyé à `sapi_megafilter_chat`. **ZÉRO nouvel appel IA pour la bulle initiale.**
  - **Effacer et recommencer** (lien tertiaire souligné) → `sapiProject.clear()` + state local vidé + bascule S0 (2 portes vides). Pas de confirmation modale.
- Layout final S3 : header + chips collés en haut, actions+reset collés en bas, gap au milieu (chips poussées par `margin: auto`)
- KEY_LABELS hardcoded restaurées dans le JS pour les chips ("Pièce", "Taille", "Escalier", etc.)
- `populateRecapChips()` restaurée
- CSS `.conseiller-cta--secondary` (variante wood-dark du CTA primaire orange)
- Layout 3 actions : 2 boutons côte à côte desktop / empilés mobile, lien reset dessous centré

**Refonte de la card "Mon projet" (commits `20347ce` → `90f71b8` → `e90222e`)**
- **Largeur alignée sur la grille produit** : `.conseiller-cards-zone` max-width 1400px + padding `0 3rem` (cohérent avec `.shop-products .product-grid`), mobile `0 1rem`
- **Card entière cliquable** : `.conseiller-card--mon-projet` transformée en `<button data-action="open-modal" data-modal-state="s3" aria-label="Modifier mon projet">`. Bouton interne "Modifier mon projet ✎" supprimé. Enfants `<div>`/`<p>` changés en `<span>` pour respecter le HTML valide (button ne peut contenir d'éléments block-level).
- **Reset des styles button HTML** : le selector global `button {}` du thème imposait `text-transform: uppercase` + `background: orange` + `font-size: 14px` + autres. Override explicite sur `.conseiller-card--mon-projet` (border none, text-transform none, font-size inherit, bg warm).
- **Hover** : pill "Mon projet" devient orange + translateY(-2px) + box-shadow → signal visuel d'interactivité
- **Hauteur verrouillée** : `min-height: 280px` + flex column + `align-items: center` sur l'inner → la card garde la même hauteur quel que soit le texte. La pill reste en haut, la citation centrée verticalement.
- **Citation agrandie** : `clamp(22px, 2.8vw, 30px)` (était 17px fixed à l'origine) + max-width 880px
- **Effet typewriter avec fade-in cascadé** : refonte complète. Chaque caractère wrappé dans un `<span class="conseiller-typewriter__char">` avec `transition-delay` individuel (initialDelay + index × 32ms). Tous les spans démarrent `opacity: 0` (CSS) puis passent à `opacity: 1` quand la classe `.is-revealing` est ajoutée → chaque lettre fade-in en cascade fluide (`transition: opacity 0.4s ease`). Pour ~100 caractères : ~3.8s total. Beaucoup plus doux que l'apparition brutale char-by-char d'avant.
- **Signature "— Robin" en fondu après la frappe** : `opacity: 0` par défaut, transition 0.6s. Le JS ajoute `.is-typing-done` à la fin du dernier fade-in caractère → opacity 1, fade-in doux.
- Triggers : animation déclenchée uniquement quand le texte change (`dataset.lastText` guard) — pas à chaque `sapiProject.subscribe` notification.

### Critères de succès — état

✅ Card "Mon projet" : lien "Modifier mon projet ✎" remplacé par cliquabilité entière de la card
✅ Click sur card → modale s'ouvre directement à S3 (carrefour)
✅ Chips récap reflètent les réponses du sapiProject
✅ "Voir la sélection" → ferme, statu quo
✅ "Préciser avec Robin" → S2.chat avec bulle initiale (zéro appel IA pour la bulle)
✅ "Effacer et recommencer" → `clear()` + S0 sans pré-remplissage
✅ Largeur card alignée sur la grille (1400px max + padding 3rem)
✅ Hover : pill orange + élévation
✅ Hauteur card stable indépendamment du texte (min-height 280px)
✅ Typewriter avec fade-in fluide par lettre + signature en fondu après
✅ Pas d'uppercase sur la citation (reset button text-transform)
✅ Esthétique cohérente avec le pattern Conseiller universel

### Point UX ouvert — en attente de décision Robin

Sur un **projet partiel** (ex. `?piece=entrée` sans avoir répondu à taille/sortie/etc.), Robin a remarqué qu'**il manque une option pour continuer le parcours questions là où il s'est arrêté**. Actuellement S3 ne propose que "Préciser avec Robin" (qui bascule en mode chat libre) — pas d'option pour répondre aux questions restantes en mode guidé.

**Solution proposée par Claude Code** : ajouter un 2ème bouton secondaire wood-dark, visible UNIQUEMENT si le projet est partiel (il reste des questions visibles sans réponse) :

Layout S3 projet partiel = **4 actions** :
- Voir la sélection (orange primaire)
- **[Nouveau bouton]** (wood-dark secondaire) → bascule en S1 sur la prochaine question non répondue, avec questionHistory pré-remplie
- Préciser avec Robin (wood-dark secondaire)
- Effacer et recommencer (lien tertiaire)

Layout S3 projet complet = statu quo 3 actions (le nouveau bouton est caché).

Détection projet partiel : `getNextUnansweredVisibleStep()` qui itère `getVisibleStepIds(answers)` et retourne le premier step sans answer.

**3 wordings au choix** :
- A — "Continuer mes réponses" (court, suppose un parcours entamé)
- B — "Compléter mon projet" ⭐ recommandé (universel, marche pour ?piece= sans clic préalable)
- C — "Répondre aux questions" (factuel, technique)

**Robin doit choisir** A/B/C avant que Claude Code code. Ou modifier wording. Estimation impl : ~30 lignes (1 bouton dans markup S3 + détection partiel dans showS3Recap + handler dans switch + fonction continueQuestionsFromS3).

### Écarts vs spec initiale F2a-ter

1. **Card "Mon projet" refondue en bouton cliquable** : pas dans la spec F2a-ter originale (le lien "Modifier mon projet" était toujours un sub-button). Robin a demandé ce changement après livraison initiale du carrefour S3. Implémenté commit `20347ce`.
2. **Hauteur verrouillée + typewriter fade** : raffinements UX successifs demandés par Robin (commits `90f71b8`, `e90222e`). Pas dans la spec F2a-ter.
3. **Largeur alignée sur la grille** : pas dans la spec, demande implicite "card alignée visuellement". Réutilise les valeurs CSS de la grille pour rester cohérent.

### Tests qui restent à valider par Robin

- [ ] Mobile 375px : layout 3 actions en colonne, hauteur card mobile OK, typewriter fluide
- [ ] Effet typewriter : observer la cascade fade-in sur un texte long (100+ caractères) — fluidité OK ?
- [ ] Signature "— Robin" : fade-in visible après la dernière lettre ?
- [ ] Card cliquable : tab clavier focus visible (focus-visible), Enter/Space ouvre la modale
- [ ] Hover : pill orange + élévation perceptible
- [ ] Click sur card → modale S3, 3 actions fonctionnelles
- [ ] "Préciser avec Robin" depuis S3 → S2.chat avec bulle initiale OK (advice_text injecté + "Qu'est-ce que tu veux affiner ?")
- [ ] "Effacer et recommencer" → projet vidé, retour à card "Conseil de Robin" si on ferme modale

### Questions ouvertes pour Cowork → Robin

1. **Wording A/B/C** pour le nouveau bouton "Compléter projet partiel" (point UX ci-dessus). Recommandation Claude Code : B.
2. **Merge master ?** F2a + F2a-bis + F2a-ter livrés sur `test-theme-sapi-maison`. Toujours en attente du feu vert pour merger ou enchaîner F2b.
3. **Card sur-mesure** : toujours masquée temporairement. Décision pending.
4. **Brevo opt-in** sur form sur-mesure : bloqué.

### Prochaine tâche

Soit :
- **Action immédiate** : décision Robin sur wording → coder le bouton "Compléter projet partiel"
- **Plus tard** : F2b (fiche produit) si décision Robin de continuer sur la branche test ou merger d'abord

---

## [RETOUR] F2a-bis — Correctif wording hero + simplification IA + ajustements modale
**Date livrée :** 2026-05-19
**Branche :** `test-theme-sapi-maison`
**URL test :** `test.atelier-sapi.fr/mes-creations/`
**Statut :** Livré et déployé. En attente de validation Robin.

### Périmètre livré

**Phase A — Hero wording (commit `916fe07`)**
- Mapping `$piece_hero_map` passé de `det` (ton/ta) à `article` (un/une)
- H1 : "Pour un salon" / "Pour une chambre" / "Pour une cuisine" / "Pour un bureau" / "Pour une entrée" / "Pour un escalier"
- Sous-titre raccourci : "Découvre la sélection de l'atelier pour ton projet"
- Hero standard (sans `?piece=`) inchangé

**Phases B+C+D+E — 1 seul appel IA par parcours (commit `d46edd6`)**
- `sapi_megafilter_advice` refactoré : modèle Sonnet (était Haiku), accepte `answers + labels + conversation?`, output `{advice_text}`, **pas de cache** transient, fallback gracieux sur texte générique par pièce
- `sapi_megafilter_recap` **supprimé** proprement (fonction + add_action)
- Nouvelle source de vérité PHP : `sapi_megafilter_get_generic_advices()` retourne 6 phrases pré-rédigées par pièce, partagées PHP/JS via `wp_localize_script`
- `sapi-project.js` étendu :
  - Schema avec `advice_text` + setter `setAdviceText`
  - `set()` accepte `extra.advice_text` (1 seul write au lieu de 2)
  - `ingestQueryParams` règle URL autorité : si `?piece=X` ≠ piece localStorage, on réécrit + on efface l'`advice_text` (correspondait à l'ancienne pièce)
- `sapi-cards-conseiller.js` allégé :
  - Suppression complète de `fetchAdvice` + pulse loading + setTimeout 600ms
  - `getAdviceText(project)` synchronous : advice_text → générique de la pièce → fallback
  - Card "Mon projet" s'affiche immédiatement, **ZÉRO AJAX au load**
- `sapi-modal-conseiller.js` refondu :
  - Suppression écran **S3 récap** (chips + IA quote + CTA "Voir la sélection")
  - Suppression `showRecap`, `populateRecapChips`, `fetchRecapPhrase`, `backToQuestions`
  - Suppression handlers `'back-to-questions'` et `'apply'` (ancienne version)
  - Nouveau `showTransitionAndExit(opts)` : affiche écran s-transition, fetch advice (avec conversation optionnelle), attend min 700ms (lisibilité), `sapiProject.set + advice_text`, close + refilter + scroll
  - S1 dernière question répondue → `showTransitionAndExit({source: 's1'})`
  - S2.chat CTA "Voir la sélection" → `showTransitionAndExit({source: 's2', conversation: ...})`
  - Lien "Préciser ou modifier mon projet" pointe maintenant vers `s0` (au lieu de `s3` supprimé). **Point 3 (UX modification fine du projet) reste à reprendre plus tard avec Robin.**
- Markup + CSS :
  - Écran `s-transition` ajouté (badge + H2 "Robin réfléchit à ton projet" + 3 dots pulsants `.conseiller-transition-dots`)
  - CSS S3 supprimé (`.conseiller-ia-quote`, `.conseiller-card--modal .conseiller-chips`)
  - CSS pulse loading sur card "Mon projet" supprimé
  - `data-state="loading"` + placeholder text retirés du markup card "Mon projet"

**Ajustements UX modale (séries d'allers-retours avec Robin)**
- Simplification écran S0 (commit `9b56b5e`) :
  - H2 raccourci : "Que préfères-tu ?"
  - Sous-titre supprimé
  - Porte 1 titre : "Questions — Réponses" (au lieu de "Je choisis")
  - Porte 2 titre : "Décrire ton projet" (au lieu de "Je décris")
  - Descriptions sous les portes supprimées
- Modale à taille fixe (commit `f5607c1`) :
  - Card 600px desktop / `calc(100dvh - 32px)` mobile
  - Plus de "respirer" entre écrans S0/S1/S2.start/s-transition
- Centrage du contenu (commits `f4e3944` → `17e0a44`) :
  - 1re tentative `space-between` rejetée par Robin (pill "Conseil de Robin" étirée plein-largeur)
  - Solution finale : `align-items: center` sur inner flex column + `margin: auto` ciblé sur le contenu principal de chaque écran
  - Résultat : badge "Conseil de Robin" garde sa taille naturelle, H2 stable juste sous la pill, contenu "réponses" centré verticalement dans l'espace restant
- Mode chat respecte la taille fixe (commit `da8adca`) :
  - Retrait de l'override `height: calc(100dvh - 64px)` sur `.is-chat-mode`
  - La card reste à 600px en mode chat, seule la zone chat scrolle en interne

**Card Sur-mesure masquée temporairement (commit `8bad8cf`)**
- Robin a demandé de désactiver l'affichage pour l'instant
- Enqueue `sapi-surmesure-card.js` commenté dans `functions.php`
- Markup + JS + CSS + endpoint restent intacts pour réactivation triviale
- Pour réactiver : décommenter le bloc enqueue (1 minute)

### Critères de succès — état

✅ `?piece=salon` → H1 "Pour un salon" + sous-titre court
✅ `?piece=chambre` → H1 "Pour une chambre"
✅ `/mes-creations/` nu → "Mes Créations" (inchangé)
✅ `?piece=salon` sans parcours modale : card "Mon projet" affiche texte générique salon + **ZÉRO AJAX au load**
✅ Parcours S1 complet : écran transition "Robin réfléchit" (700ms min) → fermeture → card affiche `advice_text` IA
✅ Parcours S2 chat + "Voir la sélection" : même transition + appel IA + stockage + fermeture
✅ Refresh `/mes-creations/` après parcours : `advice_text` toujours là, **zéro appel IA réseau**
✅ Navigation `?piece=salon` → `?piece=chambre` : sapiProject mis à jour + advice_text effacé → texte générique chambre affiché → **cohérence hero/card garantie**
✅ Endpoint `sapi_megafilter_recap` supprimé (`grep` retourne 0)
✅ Écran S3 (chips + récap) n'apparaît plus jamais
✅ Fallback gracieux si IA plante → texte générique de la pièce
✅ Modale taille fixe stable entre tous les écrans (y compris mode chat)
✅ Pill "Conseil de Robin" petite et centrée, H2 stable, réponses centrées verticalement

### Écarts vs spec initiale F2a-bis

1. **Card sur-mesure masquée** : non prévu par la spec F2a-bis mais Robin a demandé après livraison initiale. Réactivation triviale (décommenter 11 lignes dans functions.php).
2. **Layout modale** : la spec disait "taille fixe + contenu adapté" sans préciser comment. Plusieurs itérations ont été nécessaires (space-between rejeté, centrage final via margin auto). Solution finale validée visuellement par Robin.
3. **Lien "Modifier mon projet"** : la spec laissait Point 3 (UX modification fine du projet) hors scope. Solution intermédiaire : le lien ouvre la modale à S0 au lieu de s3 (qui n'existe plus). À reprendre dans une tâche dédiée si Robin veut une UX plus fine.

### Tests qui restent à valider par Robin

- [ ] Mobile 375px : modale `calc(100dvh - 32px)`, centrage des éléments, mode chat avec scroll interne
- [ ] Parcours S1 complet desktop + mobile : 7 questions conditionnelles, écran transition, sauvegarde projet
- [ ] Parcours S2 chat sur cas réels : qualité IA Sonnet avec conversation, sortie via "Voir la sélection"
- [ ] Cohérence URL > localStorage : `?piece=X` différent → effacement advice_text + texte générique nouvelle pièce
- [ ] Lien "Préciser ou modifier mon projet" : ouvre S0 avec réponses pré-remplies, nouveau parcours génère nouveau advice_text

### Questions ouvertes pour Cowork → Robin

1. **Merge master ?** F2a + F2a-bis livrés sur `test-theme-sapi-maison`. Soit on merge maintenant, soit on attend F2b (fiche produit) pour merger l'ensemble.
2. **Card sur-mesure** : masquée temporairement. Voulez-vous :
   - La réactiver telle quelle (seuil ≤ 6 produits visibles)
   - L'enlever définitivement (suppression du markup PHP + JS + CSS + endpoint surmesure)
   - La revoir (autre seuil, autre UX, autre wording)
3. **Point 3 (réinitialiser/modifier le projet)** : actuellement le lien "Préciser ou modifier mon projet" ouvre S0 (2 portes) avec réponses pré-remplies. Robin trouve-t-il ça suffisant ou veut-il une UX plus fine (bouton "Effacer mon projet" séparé, modale dédiée…) ?
4. **Brevo opt-in** sur form sur-mesure (question pendante depuis F2a) : statut bloqué tant que la card est masquée.

### Prochaine tâche dans la queue

**F2b** — Logique projet sur fiche produit (pill "Comment choisir ?" + mode court 3 questions + pré-sélection variation). Plan de découpe Claude Code proposé :
- Phase 1 : modal partagée (wp_footer hook) + pill sur single-product.php
- Phase 2 : mode court S1-short (3 questions piece/taille/style) + endpoint `sapi_megafilter_product_advice` (Sonnet, recommande UNE variation)
- Phase 3 : pré-sélection variation au load (récupérer code git pré-F1c commit `3be8ba7`) + hint "✓ Pré-sélectionné pour votre projet"
- Phase 4 : CTA "Appliquer cette sélection" → ferme modale + pré-sélectionne variation + scroll vers sélecteurs

⚠️ F2b devra intégrer le nouveau modèle IA F2a-bis (1 appel à la sortie, pas au load) — l'endpoint `sapi_megafilter_product_advice` suivra le même pattern.

En attente du feu vert Robin pour démarrer F2b ou merger F2a+F2a-bis d'abord.

---

## [RETOUR] F2a — Refonte UX /mes-creations/ livrée sur test
**Date livrée :** 2026-05-19
**Branche :** `test-theme-sapi-maison`
**URL test :** `test.atelier-sapi.fr/mes-creations/`
**Statut :** Livré et déployé. En attente de validation Robin avant merge master.

### Périmètre livré (4 phases + hotfixes)

**Fondations (commit `8af6f2d`)**
- Suppression complète de la card méga-filtre inline + chips dropdowns + modale shell F1b (~700 lignes supprimées)
- Module `assets/sapi-project.js` : source unique `localStorage.sapiProject` (get/set/update/clear/subscribe, sync inter-onglets, ingestion `?piece=X`)
- Pattern visuel universel Conseiller V3 en CSS : `.conseiller-card`, badge wood-dark/orange, h2 uppercase, CTA orange, pill secondaire dashed, signature Square Peg, chips récap, back link
- 3 endpoints AJAX dans `functions.php` :
  - `sapi_megafilter_advice` (Haiku, cache 1h serveur) — phrase courte card "Mon projet"
  - `sapi_megafilter_recap` (Sonnet) — phrase conseillère écran récap S3
  - `sapi_megafilter_surmesure` — soumission form sur-mesure (email Robin + honeypot anti-bot)
- Helpers `sapi_megafilter_sanitize_project` + `format_project_text`
- Tous endpoints : nonce + rate-limit + fallback générique si IA down

**Cards d'invitation entre hero et grille (commit `7e668cb` + hotfix `c9c98b4`)**
- Card "Conseil de Robin" (sans projet) : badge + H2 "Un coup de main pour choisir ?" + sous-titre + CTA orange "Décrire mon projet"
- Card "Mon projet" (avec projet) : phrase IA italique entre « » + signature Square Peg "— Robin" inline + lien souligné "Préciser ou modifier mon projet ✎"
- Filtrage grille au load via `window.sapiMegaFilter.cardMatches()` (override des no-ops de mega-filtre.js Phase 1) qui lit `sapiProject.answers` et mirror la logique de `inc/guide-data.php`
- Rules factorisées en `$sapi_filter_rules` (cats_by_sortie, ampoule_by_piece, etc.)
- Hotfix : SVG du CTA fixé à 14×14px (Chrome rendait à 100%), phrase pulse loading visible 600ms minimum même si réponse IA cachée serveur

**Modale tunnel S0/S1/S3 (commits `1f7a37f` → `8def608`)**
- Overlay sombre `rgba(50,40,30,0.55)` couvre la page, card Conseiller crème flottante centrée (max 880px, calc(100dvh−64px), radius 16px, ombre prononcée, animation pop scale 0.96→1)
- Click sur l'overlay OU touche ESC pour fermer (pas de croix, pas de bandeau blanc — Robin a explicitement demandé de retirer le chrome dialog)
- **S0** : 2 portes "Je choisis" + "Je décris" avec séparateur "ou" circulaire entre
- **S1** : barre progression fine, H2 question dynamique (table-bureau/lit selon piece), grille 3 cols desktop / 2 mobile de boutons-cards (icônes `sapi_guide_get_icons()`), avance auto à la question suivante, Retour vers question précédente ou S0
- **S3** : chips récap (Pièce : Salon · Taille : Spacieuse · …), card blanche IA quote italique + signature Square Peg à droite, CTA orange "Voir la sélection →", lien "Modifier mes réponses" → revient à S1 sur la dernière question répondue
- State machine + questionHistory pour Retour, sauvegarde incrémentale dans sapiProject à chaque réponse (S1), fetch IA Sonnet via `sapi_megafilter_recap` (minimum 700ms avant swap), focus management, scroll lock body

**Porte "Je décris" S2 + card Sur-mesure (commit `eb86567` + hotfixes `cab9a22`, `fa212a3`)**
- Porte "Je décris" activée → **S2.start** : input pill central + bouton submit orange (flèche) + 3 suggestions cliquables ("Une suspension moderne pour mon salon", "Une lampe d'appoint pour ma chambre", "Quelque chose pour éclairer mon escalier")
- Submit → fetch `sapi_megafilter_freetext` (Haiku, endpoint F1b existant réutilisé) → transition vers **S2.chat**
- **S2.chat** : card devient flex column 100dvh (classe `.is-chat-mode`, padding 0), badge fixe en haut, zone chat scrollable, CTA "Voir la sélection" et footer fixes en bas
- Bulles user à droite (wood-dark blanc) + bulles Robin à gauche (blanc + bordure), encarts "Filtres appliqués" sous bulles Robin qui touchent aux chips
- Bulle "Robin réfléchit" 3 dots pulsants pendant fetch
- Footer fixe : input "Continuer à discuter avec Robin…" + bouton Envoyer wood-dark
- Garde-fou 15 messages user max → CTA forcée + input locked
- Endpoints F1b réutilisés tels quels (`sapi_megafilter_freetext` Haiku + `sapi_megafilter_chat` Sonnet)
- **Card Sur-mesure** intercalée dans la grille après le 7e produit, `grid-column: span 2` (2 colonnes au lieu de toute la largeur — Robin a précisé "2x1")
- 3 états : `empty` (form complet email + textarea), `project` (compact + chips dashed + précisions optionnelles), `success` (confirmation après envoi)
- Bascule selon `sapiProject.hasProject()` + state local `submitted`
- Honeypot anti-bot (champ `website` caché en `position: absolute left -10000px`)
- **Condition d'affichage** : visible UNIQUEMENT si grille filtrée ≤ 6 produits (seuil `VISIBLE_THRESHOLD` ajustable dans sapi-surmesure-card.js) — Robin veut suggérer le sur-mesure quand la sélection est maigre, pas spammer

### Écarts vs spec initiale (décidés en cours de travail avec Robin)

1. **Modale plein écran → overlay centré** : la spec disait "plein écran 100dvh", Robin a demandé un overlay sombre + dialog flottant centré pendant la livraison Phase 3
2. **Suppression du chrome dialog** : la spec prévoyait un header blanc avec titre "Décrire mon projet" et croix de fermeture. Robin a demandé de retirer ce chrome — la card Conseiller crème devient elle-même le dialog, fermeture par click-outside ou ESC uniquement
3. **Porte "Je décris" disabled en Phase 3** puis activée Phase 4 (jamais montré "Bientôt" en prod après Phase 3, le badge a été retiré au commit eb86567)
4. **Card Sur-mesure conditionnelle** : la spec disait "toujours intercalée après les 6-8 premiers produits". Robin a précisé : afficher seulement si grille filtrée ≤ 6 produits (suggérer le sur-mesure quand la sélection est maigre). Robin a précisé pour plus tard : "le filtre IA dira lui-même quand c'est du sur-mesure qu'il faut" — pour l'instant seuil fixe.
5. **Brevo** : la spec mentionnait "Optionnel : ajoute l'email à une liste Brevo dédiée (à confirmer avec Robin avant)". Non implémenté Phase 4. À confirmer si on veut le rajouter.

### Fichiers nouveaux

- `assets/sapi-project.js` (256 lignes)
- `assets/sapi-cards-conseiller.js` (322 lignes)
- `assets/sapi-modal-conseiller.js` (865 lignes)
- `assets/sapi-surmesure-card.js` (~180 lignes)

### Tests qui restent à valider par Robin

- [ ] Mobile 375px : portes en colonne, choix 2 cols, modal padding réduit, sur-mesure prend toute la largeur (1 col sur mobile)
- [ ] Soumission réelle du form sur-mesure : email arrive-t-il bien à `robin@atelier-sapi.fr` ?
- [ ] Mode chat S2 sur cas réels : qualité des réponses IA, pertinence des filtres extraits, robustesse sur questions hors-scope
- [ ] Limit 15 messages user en chat : message d'arrêt OK, locked input/CTA forcée
- [ ] Retour navigation S1 : sur la 1re question → ramène à S0, sur S2-start → ramène à S0
- [ ] Flow `?piece=salon` direct → card "Mon projet" + phrase IA + grille filtrée
- [ ] Card sur-mesure : tester avec une combinaison qui restreint à ≤6 produits (ex. `?piece=escalier`)

### Questions ouvertes pour Cowork → Robin

1. **Merge master ou pas ?** F2a est livré sur `test-theme-sapi-maison`. Soit on merge maintenant sur master pour engranger, soit on enchaîne F2b sur la même branche test puis on merge les deux ensemble.
2. **Brevo opt-in côté form sur-mesure** : on l'ajoute (newsletter ou liste dédiée) ou on laisse seulement l'email à Robin pour l'instant ?
3. **Seuil card sur-mesure à 6** : à ajuster ? Plus haut (8) pour exposer plus, ou plus bas (4) pour être plus contextuel ?
4. **Décision long terme** sur la logique "IA flagge le sur-mesure" : c'est un projet séparé (extension du prompt Sonnet pour inclure un flag dans la réponse JSON ?), à backlogger.
5. **Mega-filtre.js dead code** : Phase 1 a neutralisé `assets/mega-filtre.js` (init early-return). Il reste enqueué mais ne fait rien. À supprimer proprement dans un commit dédié (Phase 5 cleanup) ou pendant F2b ?

### Prochaine tâche dans la queue

**F2b** — Logique projet sur fiche produit (pill "Comment choisir ?" + mode court 3 questions + pré-sélection variation). Spec complète juste en dessous dans cette queue. Plan de découpe Claude Code proposé :
- Phase 1 : modal partagée (wp_footer hook) + pill sur single-product.php
- Phase 2 : mode court S1-short (3 questions piece/taille/style) + endpoint `sapi_megafilter_product_advice` (Sonnet, recommande UNE variation)
- Phase 3 : pré-sélection variation au load (récupérer code git pré-F1c commit `3be8ba7`) + hint "✓ Pré-sélectionné pour votre projet"
- Phase 4 : CTA "Appliquer cette sélection" → ferme modale + pré-sélectionne variation + scroll vers sélecteurs

En attente du feu vert Robin pour démarrer F2b ou merger F2a d'abord.

---

## 📋 À faire

## [TÂCHE] F2a-sexies — Card "Mon projet" : chip-question d'accroche qui embarque dans la modale

**Date :** 2026-05-21
**Priorité :** haute (dernière modif UX avant prod)
**Branche :** `test-theme-sapi-maison`
**Prérequis :** F2a-quater livrée et validée (F2a-quinquies indépendant — ordre libre).

---

### Contexte

Aujourd'hui, sur `/mes-creations/?piece=chambre` (visiteur arrivant via le roompicker de la home), la card "Mon projet" affiche le badge + la phrase IA générique + la signature Robin… et **rien n'invite à aller plus loin**. Le visiteur n'a pas de signal qu'il peut préciser son projet. La card "ferme la porte" au lieu de l'ouvrir.

Robin a validé sur le mockup #10 (variante F + F bis) le principe suivant : **afficher sous la phrase IA un chip-question d'accroche** = la prochaine question non répondue du parcours. **Au clic sur une réponse, on enregistre la réponse en localStorage puis on ouvre la modale directement sur la question d'après.**

Le visiteur est embarqué dans le parcours guidé dès son 1er clic. À la sortie de la modale (parcours complet), l'appel IA génère l'advice_text personnalisé qui remplace la phrase générique. La règle "1 seul appel IA par parcours" est respectée.

**Mockup de référence :** `site-web/mockups/mockup-10-card-mon-projet-variantes.html` — variantes **F** (état entrée) et **F bis** (état sortie).

---

### À LIRE AVANT TOUTE MODIFICATION

1. `assets/sapi-cards-conseiller.js` — rendu actuel de la card "Mon projet" (`renderMonProjet`), helpers `getVisibleStepIds()`, `cleanInvisibleAnswers()`, dispatch `sapi:open-modal`
2. `assets/sapi-project.js` — API `update(patchAnswers, patchLabels)`, `subscribe()`, `hasProject()`
3. `assets/sapi-modal-conseiller.js` — listener `sapi:open-modal` + `openModal('s0')` qui appelle `determineInitialState()` (s0-partiel démarre automatiquement sur la 1re question non répondue)
4. `inc/guide-data.php` — `sapi_guide_get_steps()` (id, question, choices[], visibility)
5. Mockup `site-web/mockups/mockup-10-card-mon-projet-variantes.html` — variantes F + F bis pour le visuel cible
6. Mémoire Cowork `project_conseiller_v3_pivot.md` — pattern visuel universel Conseiller (à respecter)

---

### Périmètre F2a-sexies

#### A. Nouveau helper JS — `getNextUnansweredStep(answers)`

Dans `sapi-cards-conseiller.js`, ajouter à côté de `getVisibleStepIds()` :

```js
function getNextUnansweredStep(answers) {
  var visibleIds = getVisibleStepIds(answers);
  for (var i = 0; i < visibleIds.length; i++) {
    var id = visibleIds[i];
    if (!answers[id]) {
      // Retourne le step complet pour avoir question + choices
      for (var j = 0; j < STEPS.length; j++) {
        if (STEPS[j].id === id) return STEPS[j];
      }
    }
  }
  return null; // parcours complet
}
```

#### B. Rendu de la card "Mon projet" — 3 états

Refonte de `renderMonProjet()` pour gérer trois états selon le projet :

1. **État "awaiting"** (transition modale → IA, déjà existant)
   - 3 dots qui pulsent → inchangé.

2. **État "parcours incomplet"** (au moins 1 answer, au moins 1 step visible non répondu)
   - Badge "Mon projet" + phrase IA générique de la pièce + signature Robin
   - **Sous la phrase** : nouvelle zone `.conseiller-bento__inline-question` contenant :
     - Un label italique : la question du step (ex: *"Taille de la pièce ?"*) — provient de `step.question` raccourci (déjà fait dans le commit `1b86eed`)
     - Une liste de chips boutons = `step.choices[]` (label, slug, optionnellement dim)
   - **Pas** de séparateur dashed entre la phrase et la question. **Pas** de texte explicatif sous les chips.

3. **État "parcours complet"** (tous les steps visibles répondus → `getNextUnansweredStep` retourne null)
   - Badge "Mon projet" + phrase IA enrichie (advice_text personnalisé) + signature Robin
   - **Lien "Modifier" en haut-droite** de la card (déjà câblé en F2a-ter pour ouvrir S3 carrefour — réutiliser tel quel)
   - Pas de chip-question (puisque tout est répondu)

L'état actuel "phrase + signature" sans rien sous devient un **cas qui ne doit plus exister** : soit on a un chip-question (parcours incomplet), soit on a le lien Modifier (parcours complet). Le seul cas particulier qui resterait sans chip ni lien : projet vide → la card "Mon projet" n'est pas affichée du tout (c'est la card "Conseil de Robin" qui prend le relais via `renderConseil()`).

#### C. Comportement au clic sur une chip-réponse

Au clic sur `.answer-chip[data-step-id="taille"][data-slug="moyenne"]` :

1. Récupérer `stepId` et `slug` depuis les data-attributes du bouton
2. Récupérer le `label` du choice depuis STEPS (pour `sapiProject.labels`)
3. `sapiProject.update({ [stepId]: slug }, { [stepId]: label })` → enregistre la réponse silencieusement
4. **Dispatch `sapi:open-modal`** avec `detail: { state: 's0' }`
   - La modale `openModal('s0')` appelle `determineInitialState()` qui retourne automatiquement `'s0-partiel'` (puisqu'il y a maintenant ≥1 answer) → démarre sur la 1re question non répondue, soit la **question d'après** celle qu'on vient de répondre
   - Pas besoin d'ajouter un mécanisme `startAt` : la logique existante fait déjà ça
5. **Important** : `sapiProject.update()` notifie les subscribers → ça re-déclenche `render()` côté card. Pour éviter un flash visuel "chip change pendant que la modale s'ouvre", **dispatcher l'événement modal AVANT** d'écrire en localStorage (ou avec un petit setTimeout). Au final, quand la modale se ferme et que le visiteur revient sur la page, la card aura le bon état (parcours complet ou question encore en cours).

#### D. Markup HTML cible (à injecter dans la card)

Dans le template PHP de la card "Mon projet" (probablement dans `woocommerce/archive-product.php` ou un partial dédié — vérifier où vit le markup actuel), ajouter un placeholder pour la zone chip :

```html
<div class="conseiller-bento__card" data-conseiller-card="mon-projet" hidden>
  <a class="conseiller-bento__edit" href="#" data-action="open-modal" data-modal-state="s3" hidden>
    <svg>...crayon...</svg> Modifier
  </a>

  <span class="conseiller-bento__badge">
    <svg>...crayon...</svg> Mon projet
  </span>

  <p class="conseiller-bento__text" data-mon-projet-phrase>
    <span data-mon-projet-phrase-content></span>
    <span class="conseiller-bento__sig">— Robin</span>
  </p>

  <div class="conseiller-bento__inline-question" data-inline-question hidden>
    <!-- injecté par JS selon getNextUnansweredStep() -->
  </div>
</div>
```

Le `data-inline-question` est injecté par JS au render :

```html
<span class="inline-question__label">Taille de la pièce ?</span>
<div class="inline-question__answers">
  <button class="answer-chip" type="button" data-step-id="taille" data-slug="petite">Petite</button>
  <button class="answer-chip" type="button" data-step-id="taille" data-slug="moyenne">Standard</button>
  <button class="answer-chip" type="button" data-step-id="taille" data-slug="grande">Grande</button>
  <button class="answer-chip" type="button" data-step-id="taille" data-slug="ne-sais-pas">Je ne sais pas</button>
</div>
```

Le lien `.conseiller-bento__edit` est masqué par défaut, révélé uniquement en état "parcours complet" (toggled par `renderMonProjet()` selon le retour de `getNextUnansweredStep()`).

#### E. CSS à ajouter

Référence visuelle = mockup #10 variante F (version épurée finale). Styles à ajouter dans le CSS de la card conseiller (probablement `style.css` ou un partial) :

```css
.conseiller-bento__inline-question {
  margin-top: 16px;
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  align-items: center;
  gap: 10px 12px;
}
.inline-question__label {
  font-size: 13px;
  font-weight: 600;
  color: var(--color-wood);
  font-style: italic;
}
.inline-question__answers {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 8px;
}
.answer-chip {
  display: inline-flex;
  align-items: center;
  padding: 8px 16px;
  background: #fff;
  border: 1.5px solid rgba(139, 115, 85, 0.35);
  border-radius: 50px;
  font-family: inherit;
  font-size: 12.5px;
  font-weight: 600;
  color: var(--color-wood-dark);
  cursor: pointer;
  transition: border-color 0.2s, background 0.2s, transform 0.2s;
}
.answer-chip:hover {
  background: var(--color-warm);
  border-color: var(--color-orange);
  transform: translateY(-1px);
}
```

Mobile (déjà appliqué via les query existantes du fichier) : si la ligne ne tient pas, ça wrap naturellement grâce au `flex-wrap`.

#### F. Délégation événements

Étendre le `bindCTAs()` existant pour intercepter aussi les `.answer-chip` :

```js
els.zone.addEventListener('click', function (e) {
  var chip = e.target.closest('.answer-chip[data-step-id]');
  if (chip) {
    e.preventDefault();
    handleChipAnswer(chip);
    return;
  }
  var btn = e.target.closest('[data-action="open-modal"]');
  if (btn) {
    // ...code existant
  }
});

function handleChipAnswer(chip) {
  var stepId = chip.getAttribute('data-step-id');
  var slug = chip.getAttribute('data-slug');
  if (!stepId || !slug) return;

  // Récupère le label depuis STEPS
  var label = '';
  for (var i = 0; i < STEPS.length; i++) {
    if (STEPS[i].id !== stepId) continue;
    var choices = STEPS[i].choices || [];
    for (var j = 0; j < choices.length; j++) {
      if (choices[j].slug === slug) { label = choices[j].label; break; }
    }
    break;
  }

  // Dispatch modal AVANT update pour ouvrir immédiatement
  var event = new CustomEvent('sapi:open-modal', {
    bubbles: true, detail: { state: 's0' }
  });
  chip.dispatchEvent(event);

  // Puis enregistre la réponse (notifiera les subscribers, mais la modale est déjà ouverte)
  if (window.sapiProject && typeof window.sapiProject.update === 'function') {
    var patch = {}; patch[stepId] = slug;
    var lpatch = {}; lpatch[stepId] = label;
    window.sapiProject.update(patch, lpatch);
  }
}
```

---

### Ce qui n'est PAS dans F2a-sexies

- ❌ Modification de la modale interne (pas de nouveau `startAt`, on réutilise `s0-partiel` qui démarre déjà sur la 1re non répondue)
- ❌ Modification du fonctionnement de la phrase IA (typewriter, advice_text, génération) — strict statu quo
- ❌ Modification du filtrage produit (la grille se filtre normalement après modale, pas au clic chip)
- ❌ Modification de la card "Conseil de Robin" (état projet vide) — inchangée
- ❌ Logique fiche produit (F2b déjà livrée)
- ❌ Bug `advice_text` qui ne s'invalide pas — sera traité dans le gros chantier IA + filtrage PHP qui suit

---

### Critères de succès

1. Sur `/mes-creations/?piece=chambre` (visiteur depuis la home, projet contenant uniquement `piece`) : la card "Mon projet" affiche la phrase générique chambre + la signature + **un chip-question "Taille de la pièce ?" avec 4 chips de réponse**. Pas de lien "Modifier".
2. Clic sur "Standard" → la modale s'ouvre directement sur **Éclairage principal ?** (la question d'après dans `sapi_guide_get_steps()` selon visibility chambre). La réponse "Standard" est bien enregistrée dans `sapiProject.answers.taille`.
3. Le visiteur termine le parcours dans la modale → animation de sortie → advice_text généré → retour sur `/mes-creations/`. La card "Mon projet" affiche maintenant la phrase enrichie + le lien "Modifier" en haut-droite + **plus de chip-question**.
4. Clic sur "Modifier" → ouvre S3 carrefour (comportement F2a-ter inchangé).
5. Recommencer depuis S3 → `sapiProject.clear()` → la card "Mon projet" disparaît, remplacée par la card "Conseil de Robin" (état projet vide). Pas de chip orphelin.
6. Si le visiteur arrive avec une URL `?piece=cuisine` + n'a jamais terminé de parcours : le chip-question affiché est bien la 1re question visible non répondue selon `visibility` (différent pour cuisine vs chambre — ex: chambre n'a pas de `table`, cuisine oui).
7. Mobile 375px : la zone `.conseiller-bento__inline-question` wrap proprement (label au-dessus, chips dessous) sans déborder.
8. Aucun appel réseau au clic chip — l'ouverture de modale est purement front. L'AJAX (advice_text) ne se déclenche qu'en sortie de modale comme aujourd'hui.
9. Pas de flash visuel "chip qui change pendant que la modale s'ouvre" — l'ordre d'opérations (dispatch event puis update) garantit que la modale est déjà ouverte quand la card re-render.
10. Le rendu reste cohérent quel que soit le nombre de réponses déjà cochées (1, 2, 3… jusqu'à N-1).

---

### Précautions

- **Strict statu quo sur le typewriter et la génération d'advice_text** — F2a-sexies ne touche pas à la phrase IA, juste ce qui est dessous.
- **Lien "Modifier" en haut-droite** = celui déjà existant (F2a-ter). Juste son hidden state qui devient piloté par `getNextUnansweredStep()`.
- **Ne pas re-déclencher l'IA** sur le clic chip — la phrase reste générique tant que la modale n'a pas été terminée.
- **L'ordre dispatch → update** est important pour éviter le flash visuel. Sinon `subscribe()` re-rend la card avant que la modale s'ouvre.
- **Pas de spinner** sur le clic chip — c'est instantané pour l'utilisateur, l'enregistrement est synchrone localStorage.
- Branche `test-theme-sapi-maison`, push test uniquement.

---


## [TÂCHE] F2a-quinquies — Hero /mes-creations/ qui s'adapte en live + suppression du sous-titre

**Date :** 2026-05-20
**Priorité :** moyenne (raffinement UX après F2a-quater)
**Branche :** `test-theme-sapi-maison`
**Prérequis :** F2a-quater livrée et validée.

---

### Contexte

Aujourd'hui, le H1 du hero ("Pour un salon" / "Pour une chambre" / etc.) est rendu côté serveur PHP à partir de `?piece=X` dans l'URL. Mais si le visiteur **modifie son projet sans recharger la page** — typiquement en sortant de la modale après un parcours, en cliquant "Recommencer" depuis S3, ou en changeant la pièce via la modale — le hero **reste figé** sur l'ancienne valeur. Désynchronisation visuelle.

Robin veut que le hero **s'adapte en live** au changement de `sapiProject.answers.piece`, avec une transition fluide. Et il veut **supprimer le sous-titre H2** ("Découvre la sélection de l'atelier pour ton projet") — l'air gagné fait respirer le H1.

**Périmètre étroit** : on touche au H1 et au sous-titre. **La photo de fond du hero N'EST PAS modifiée** par cette tâche — le swap de photo selon la pièce relève du chantier **S28 (photos par pièce)** déjà planifié et en pause (cf. mémoire Cowork `project_photos_par_piece.md`).

---

### À LIRE AVANT TOUTE MODIFICATION

1. `woocommerce/archive-product.php` — où vit le hero (`.shop-hero-artisan`) avec son mapping `$piece_hero_map`
2. `assets/sapi-project.js` — `sapiProject.subscribe()` qui notifie au changement
3. Mémoire Cowork `project_photos_par_piece.md` (S28) — pour comprendre pourquoi la photo n'est pas touchée ici

---

### Périmètre F2a-quinquies

#### A. Suppression du sous-titre H2

Dans `archive-product.php`, retirer le `<p>` ou `<h2>` qui affiche *"Découvre la sélection de l'atelier pour ton projet"* (ou son équivalent quand pas de pièce). Suppression **du DOM**, pas juste `display: none`.

L'ancien sous-titre marketing (cas hero sans `?piece=`) est **également supprimé** pour cohérence — désormais le hero ne contient que H1 + photo de fond. Le hero "standard" et le hero "réactif" affichent la même structure minimale : juste le titre.

#### B. H1 mis à jour en live au changement de `sapiProject.answers.piece`

Créer un nouveau module léger `assets/sapi-hero-live.js` :
- S'abonne à `sapiProject.subscribe()` au load
- Reçoit la config du mapping piece → titre via `wp_localize_script` (variable `SAPI_HERO_TITLES`) — source PHP = même `$piece_hero_map` que le hero (cohérence stricte)
- Au notify, lit la nouvelle valeur `answers.piece` :
  - Si pièce reconnue dans le mapping → met à jour le `textContent` du H1 avec *"Pour un/une X"*
  - Si pas de pièce (projet vide ou pièce inconnue) → met à jour avec *"Mes Créations"* (le titre par défaut)
- Crossfade subtil (~250ms) sur le H1 : opacity 1 → 0 → swap textContent → opacity 0 → 1

Le module est enqueue sur `is_shop()` uniquement (page /mes-creations/).

#### C. wp_localize_script du mapping

Dans `functions.php` (ou le fichier où sont déjà localizés les variables de la page), ajouter :

```php
wp_localize_script('sapi-hero-live', 'SAPI_HERO_TITLES', [
  'default' => 'Mes Créations',
  'pieces'  => [
    'salon'    => 'Pour un salon',
    'chambre'  => 'Pour une chambre',
    'cuisine'  => 'Pour une cuisine',
    'bureau'   => 'Pour un bureau',
    'entree'   => 'Pour une entrée',
    'escalier' => 'Pour un escalier',
  ],
]);
```

Idéalement, factoriser le mapping `$piece_hero_map` dans une fonction helper `sapi_get_hero_piece_titles()` qui sert à la fois au rendu PHP initial du hero ET au localize JS. Évite la duplication des labels.

#### D. Comportement à la première charge (PHP)

Au load initial de la page :
- Si `?piece=X` est présent et reconnu → H1 = *"Pour un/une X"* (comportement actuel F2a-bis, inchangé)
- Si pas de `?piece=` MAIS `sapiProject` en localStorage contient une pièce → on garde la logique PHP (qui ne sait pas lire localStorage). Le H1 s'affiche en *"Mes Créations"* au premier paint, puis le JS prend le relais via `sapiProject.subscribe` au load et met à jour le H1 vers la bonne valeur. **Cela peut créer un flash visuel**. Acceptable car rare en pratique (les visiteurs récurrents avec projet stocké arrivent généralement avec `?piece=` redirigés par la home, sauf cas direct mes-creations/ sans param)
- Si pas de `?piece=` ET sapiProject vide → H1 = *"Mes Créations"* (comportement actuel inchangé)

Le flash mentionné ci-dessus pourrait être atténué par un `<script inline>` ultra-early dans `<head>` qui lit localStorage et override le textContent du H1 avant le premier paint — **mais pas dans le périmètre de cette tâche**, à voir plus tard si Robin le demande.

#### E. Hauteur du hero conservée

Pas de changement de hauteur. L'espace vertical qui était occupé par le sous-titre devient de l'air autour du H1.

---

### Ce qui n'est PAS dans F2a-quinquies

- ❌ Changement de la photo de fond du hero — réservé à S28 (cf. mémoire `project_photos_par_piece.md`)
- ❌ Refonte du hero standard `/mes-creations/` sans `?piece=` — juste suppression du sous-titre
- ❌ Modification du hero sur d'autres pages (catégorie, conseils-eclaires, home) — non concernées
- ❌ Script `<head>` inline pour éviter le flash localStorage — à voir plus tard si gênant

---

### Critères de succès

1. Sur `/mes-creations/?piece=salon` : H1 = *"Pour un salon"*, pas de sous-titre, photo de fond inchangée
2. Sur `/mes-creations/` sans param : H1 = *"Mes Créations"*, pas de sous-titre, photo inchangée
3. Ouvrir la modale depuis `/mes-creations/` (projet vide), faire un parcours S1 complet jusqu'à la sortie → pendant l'animation de sortie (1,9s), le H1 du hero se met à jour en crossfade vers *"Pour un X"* selon la pièce répondue
4. Depuis S3 carrefour, clic "Recommencer" → `sapiProject.clear()` → le H1 revient à *"Mes Créations"* en crossfade
5. Naviguer de `/mes-creations/?piece=salon` à `/mes-creations/?piece=chambre` (URL externe) → au load PHP le H1 est directement *"Pour une chambre"*, pas de flash
6. Aucun appel réseau supplémentaire — l'update est local, basé sur le mapping localisé
7. Pas de flash visuel sur les cas standards (avec `?piece=` ou sans projet)
8. Mobile 375px : H1 reste lisible, plus aéré sans le sous-titre
9. Le module `sapi-hero-live.js` n'est enqueue que sur `is_shop()` (pas chargé sur les autres pages)

---

### Précautions

- **Ne PAS toucher** à la photo de fond du hero — S28 s'en occupera (lecture seule de sapiProject pour cette tâche, juste pour le titre)
- **Ne PAS supprimer** `$piece_hero_map` côté PHP — il sert toujours au rendu initial server-side
- **Factoriser** le mapping en helper PHP réutilisable par les 2 contextes (rendu PHP + localize JS) pour éviter la duplication
- **Crossfade subtil** : 250ms maxi, pas d'effet wow (sobre, lisible)
- Branche `test-theme-sapi-maison`, push test uniquement

---


## [TÂCHE] F2a-quater — Modale hybride : suppression du choix de portes, écran unique question + texte

**Date :** 2026-05-19
**Priorité :** moyenne (raffinement UX modale après F2a-ter)
**Branche :** `test-theme-sapi-maison`
**Prérequis :** F2a + F2a-bis + F2a-ter livrées et validées.

---

### Contexte

L'écran S0 actuel ("Que préfères-tu ?" avec 2 grosses portes "Questions — Réponses" / "Décrire ton projet") est une étape intermédiaire qui force un choix avant de commencer. Robin veut la supprimer.

**Nouveau S0** = un écran unique qui affiche **simultanément les deux modes** :
- En haut : la première question disponible avec ses boutons-cards de réponse
- Au milieu : séparateur "ou"
- En bas : un champ texte pour décrire son projet librement

Cet écran s'adapte automatiquement à 3 contextes selon l'état du `sapiProject` :

| État | Quand | Badge | Question affichée | Placeholder texte | Lien Effacer |
|---|---|---|---|---|---|
| **Initial** | sapiProject vide | "Conseil de Robin" | Pièce (1re question) | *"Décris ton projet en quelques mots…"* | Caché |
| **Partiel** | sapiProject contient au moins 1 réponse mais il reste des questions visibles non répondues | "Mon projet" | Prochaine question non répondue | *"Précise ton projet en quelques mots…"* | Visible (lien souligné en bas) |
| **Complet** | Toutes les questions visibles répondues | (S3 carrefour 3 actions — pas concerné par cette tâche, inchangé depuis F2a-ter) | — | — | — |

---

### À LIRE AVANT TOUTE MODIFICATION

1. **Mockup de référence** : `site-web/mockups/mockup-09-modale-hybride.html` — montre les 2 états (initial + partiel) sur la même page
2. `assets/sapi-modal-conseiller.js` — la modale actuelle avec ses états s0/s1/s2/s3
3. `assets/sapi-project.js` — `sapiProject.answers` et helpers
4. `inc/guide-data.php` — `sapi_guide_get_steps()` (visibility logic des questions)
5. Spec F2a-ter au-dessus dans cette queue — le carrefour S3 reste comme spec'é, pas touché

---

### Périmètre F2a-quater

#### A. Suppression de l'ancien écran S0 (2 portes "Que préfères-tu ?")

Dans `assets/sapi-modal-conseiller.js` :
- Supprimer le markup des 2 portes (`.conseiller-portes` ou similaire)
- Supprimer le H2 *"Que préfères-tu ?"*
- Supprimer le séparateur "ou" circulaire entre les 2 portes
- Supprimer les handlers `'choose-questions'` et `'choose-text'` (les boutons des portes)
- Supprimer le CSS associé (`.conseiller-porte`, `.conseiller-portes__or`, etc.)

#### B. Nouveau S0 hybride

L'état `s0` est refondu. Structure (suivre le mockup #9) :

```
.conseiller-card
  .conseiller-card__badge        ← dynamique : "Conseil de Robin" ou "Mon projet"
  .conseiller-card__title        ← dynamique : question courante (H2 uppercase)
  .conseiller-choices            ← grille de boutons-cards (3 cols desktop / 2 mobile)
  .conseiller-or                 ← séparateur "ou" horizontal avec pastille centrée
  .conseiller-text-input         ← input pill + bouton submit orange
  .conseiller-reset-link         ← lien souligné "Effacer et recommencer" (uniquement état partiel)
```

**Le séparateur "ou"** : pastille crème centrée avec lignes horizontales de chaque côté (cf. mockup). Plus le séparateur circulaire entre 2 portes qui existait.

**Le champ texte** : input pill 50px + bouton flèche orange dans un wrapper relative. Identique à S2.start actuel mais sans les 3 suggestions cliquables (supprimées — épuration validée).

#### C. Logique de détection automatique de l'état

À l'ouverture de la modale via le sélecteur `data-modal-state="s0"` (ou similaire) :

```js
function determineInitialState() {
  const project = sapiProject.get();
  const visibleSteps = getVisibleStepIds(project.answers);
  const allAnswered = visibleSteps.every(id => project.answers[id]);
  const noneAnswered = visibleSteps.every(id => !project.answers[id]);

  if (allAnswered && visibleSteps.length > 0) return 's3-carrefour';   // toutes répondues → S3 3 actions
  if (noneAnswered) return 's0-initial';                                // aucune → état initial
  return 's0-partiel';                                                  // entre les deux → partiel
}
```

Selon le résultat :
- **s3-carrefour** : afficher S3 (inchangé depuis F2a-ter)
- **s0-initial** : afficher le nouveau hybride avec badge "Conseil de Robin", question Pièce (la 1re du parcours), placeholder *"Décris ton projet en quelques mots…"*, lien Effacer caché
- **s0-partiel** : afficher le nouveau hybride avec badge "Mon projet", question = `getNextUnansweredVisibleStep(answers)`, placeholder *"Précise ton projet en quelques mots…"*, lien Effacer visible

#### D. Comportements interactifs

**Clic sur une réponse de la grille** (Pièce, Taille, etc.) :
- Enregistre la réponse dans `sapiProject.answers`
- Bascule vers **S1 normal** sur la question suivante (le parcours guidé continue comme avant)
- Le texte tapé dans le champ texte (s'il y en avait) est **ignoré sans avertissement** — le clic bouton gagne (décision Robin)

**Submit du champ texte** (Entrée ou clic bouton orange) :
- Bascule vers **S2.chat** avec une **bulle initiale Robin** construite côté client : `advice_text + "Qu'est-ce que tu veux affiner ?"` (advice_text = stocké dans sapiProject, ou texte générique de la pièce si projet partiel sans advice, ou rien si projet vide → dans ce cas la bulle initiale = *"Décris-moi ton projet, je t'aide à trouver une sélection."* ou similaire)
- La bulle initiale est pushée dans `state.chat.conversation` (comme F2a-ter pour "Préciser avec Robin")
- Le texte tapé par le visiteur devient la 1re bulle user du chat
- Le chat continue normalement (footer input, endpoints `sapi_megafilter_chat`)
- Aucun nouvel appel IA pour la bulle initiale

**Clic sur "Effacer et recommencer"** (état partiel) :
- Appelle `sapiProject.clear()`
- **Bascule fluide vers l'état initial dans la même modale** (badge "Conseil de Robin", question Pièce, placeholder "Décris…", lien Effacer disparaît)
- Pas de fermeture de la modale, pas de redirect

#### E. Conséquences sur la card "Mon projet"

La card "Mon projet" (sur /mes-creations/) reste cliquable comme aujourd'hui (refondue en bouton entier en F2a-ter). Son action :
- Ouvre la modale en mode `s0` (le nouvel hybride)
- C'est `determineInitialState()` qui décide ensuite si on affiche s0-initial, s0-partiel ou s3-carrefour selon l'état du projet

**Aucun changement** sur la card "Conseil de Robin" (sans projet) — son CTA "Décrire mon projet" ouvre la modale en mode `s0`, et la modale tombe automatiquement sur s0-initial parce que sapiProject est vide.

---

### Ce qui n'est PAS dans F2a-quater

- ❌ S1 (parcours questions guidées) — inchangé après le 1er clic depuis l'hybride
- ❌ S2.chat — inchangé (mais la bulle initiale construite par F2a-ter est réutilisée par l'hybride)
- ❌ S3 carrefour 3 actions pour projet complet — inchangé
- ❌ Tout autre composant (cards /mes-creations/, hero, grille, fiche produit) — inchangés
- ❌ Suggestions cliquables sous le champ texte — **supprimées** (épuration)
- ❌ Chips récap dans l'état partiel — **non affichés** (visibles sur la card "Mon projet" avant ouverture, pas besoin de les répéter)

---

### Critères de succès

1. **L'ancien écran "Que préfères-tu ?" avec les 2 portes n'existe plus dans le code** (grep retourne 0)
2. **Ouverture de la modale via card "Conseil de Robin"** (sapiProject vide) → écran hybride état initial : badge "Conseil de Robin", question Pièce, 6 boutons-cards, séparateur "ou", champ texte placeholder "Décris ton projet…", pas de lien Effacer
3. **Ouverture de la modale via card "Mon projet" avec projet partiel** (`?piece=salon` direct depuis home, par exemple) → écran hybride état partiel : badge "Mon projet", question Taille (la prochaine non répondue), 4 boutons, champ texte placeholder "Précise ton projet…", lien "Effacer et recommencer" en bas
4. **Ouverture de la modale via card "Mon projet" avec projet complet** → S3 carrefour 3 actions (inchangé depuis F2a-ter)
5. Clic sur un bouton-card de réponse → bascule en S1 sur la question suivante, le parcours continue normalement
6. Si du texte avait été tapé dans le champ et le visiteur clique sur une réponse bouton → le texte est ignoré, parcours S1 démarre (comportement implicite, pas de message d'avertissement)
7. Submit du champ texte (état initial OU partiel) → bascule en S2.chat avec bulle initiale Robin construite côté client à partir d'`advice_text` + *"Qu'est-ce que tu veux affiner ?"*, suivie de la 1re bulle user
8. Clic sur "Effacer et recommencer" en état partiel → `sapiProject.clear()` + bascule fluide vers état initial dans la même modale (badge change, question Pièce s'affiche, lien Effacer disparaît). **Pas de fermeture de la modale.**
9. ESC ou click-outside → ferme la modale, sapiProject conservé tel quel
10. Mobile 375px : grille passe à 2 colonnes, séparateur "ou" et champ texte responsive, lien Effacer reste centré

---

### Précautions

- **Ne PAS toucher** au S1 (les questions une à une après le 1er clic), ni au S2.chat (la conversation libre), ni au S3 carrefour (projet complet)
- **Ne PAS rajouter** les 3 suggestions cliquables sous le champ texte (épuration validée)
- **Ne PAS afficher** de chips récap des réponses précédentes dans l'état partiel (le visiteur les voit déjà sur la card "Mon projet" avant d'ouvrir)
- **Référence visuelle obligatoire** : `mockups/mockup-09-modale-hybride.html` — Claude Code doit l'ouvrir avant de coder pour voir la structure
- Le séparateur "ou" est **horizontal avec pastille centrée** (cf. mockup), pas circulaire entre 2 portes
- Branche `test-theme-sapi-maison`, push test uniquement

---


## [TÂCHE] F2a-ter — Carrefour S3 "Modifier mon projet" (récap + 3 actions, zéro appel IA)

**Date :** 2026-05-19
**Priorité :** moyenne (UX du retour visiteur sur son projet)
**Branche :** `test-theme-sapi-maison`
**Prérequis :** F2a + F2a-bis livrées et validées.

---

### Contexte

Dans F2a-bis, l'écran S3 de la modale (récap + phrase IA + CTA) a été supprimé : la fin du parcours S1 ferme directement la modale, et le lien *"Préciser ou modifier mon projet"* sur la card "Mon projet" ouvre la modale à S0 (2 portes) avec les réponses pré-remplies.

Solution intermédiaire fonctionnelle mais pas optimale : le visiteur tombe sur l'écran de démarrage 2 portes alors qu'il a déjà un projet en cours. Pas de récap, pas de choix d'action clair.

**Nouvelle idée Robin :** ressusciter S3, mais avec un contenu radicalement différent — un **carrefour d'options** quand le visiteur revient sur son projet :
- Voir le récap de son projet (chips lecture seule)
- Choisir entre 3 actions claires : consulter, affiner par chat, ou recommencer

---

### À LIRE AVANT TOUTE MODIFICATION

1. `assets/sapi-modal-conseiller.js` — modale S0/S1/S2 (S3 supprimé en F2a-bis, à restaurer avec nouveau contenu)
2. `assets/sapi-cards-conseiller.js` — la card "Mon projet" et son lien *"Préciser ou modifier mon projet"*
3. `assets/sapi-project.js` — sapiProject.advice_text + clear()
4. `style.css` — chercher `.conseiller-card--modal .conseiller-chips` (supprimé en F2a-bis, à réintégrer)
5. Mémoire Cowork `project_conseiller_v3_pivot.md` — pattern visuel universel Conseiller

---

### Périmètre F2a-ter

#### A. Restaurer un écran S3 dans la modale (contenu nouveau)

L'écran S3 réapparaît, mais **uniquement déclenché depuis la card "Mon projet"** (pas à la fin de S1 qui continue de fermer directement la modale comme dans F2a-bis).

**Structure visuelle :**
- Card Conseiller universelle (fond crème, dashed, badge wood-dark "Mon projet")
- H2 *"Voici votre projet"*
- Chips récap des réponses, lecture seule, format `<key> : <valeur>` (Pièce : Salon · Taille : Spacieuse · etc.) — réutiliser/recréer le composant `.conseiller-chips` supprimé en F2a-bis
- **3 actions hiérarchisées visuellement** :

| Niveau | Style | Wording | Effet |
|---|---|---|---|
| 1 (primaire) | Pill orange (`var(--color-orange)`) | **Voir la sélection** | Ferme la modale, retour à la grille filtrée. Statu quo. |
| 2 (secondaire) | Pill wood-dark (cohérent badge) | **Préciser avec Robin** | Bascule vers S2.chat avec bulle initiale (cf. C) |
| 3 (tertiaire) | Lien souligné `var(--color-wood)` discret | **Effacer et recommencer** | `sapiProject.clear()` + bascule vers S0 |

Layout : les 2 boutons (orange + wood-dark) côte à côte (ou empilés sur mobile), le lien souligné en dessous, centré.

#### B. Adapter le lien sur la card "Mon projet"

Dans `assets/sapi-cards-conseiller.js` :
- Renommer le lien *"Préciser ou modifier mon projet ✎"* → **"Modifier mon projet ✎"** (plus court, plus direct)
- Le handler ouvre maintenant la modale **à S3** (au lieu de S0 actuellement)

#### C. Action "Préciser avec Robin" → bascule vers S2.chat avec bulle initiale

Quand le visiteur clique sur "Préciser avec Robin" depuis S3 :
- La modale bascule vers **S2.chat** (pas S2.start)
- Une **bulle initiale Robin** est injectée automatiquement, qui réutilise `sapiProject.advice_text` déjà stocké, suivi d'une invite à préciser

Construction de la bulle initiale :
```
<advice_text>

Qu'est-ce que tu veux affiner ?
```

Exemple si advice_text = *"Pour un salon spacieux et moderne, j'ai sélectionné 17 luminaires à ampoule entourée."* :
> *"Pour un salon spacieux et moderne, j'ai sélectionné 17 luminaires à ampoule entourée. Qu'est-ce que tu veux affiner ?"*

Si advice_text est un texte générique (projet partiel via `?piece=X`), même principe : *"Pour un salon, j'ai sélectionné une variété de luminaires qui créent une atmosphère chaleureuse. Qu'est-ce que tu veux affiner ?"*

**Aucun nouvel appel IA** — la bulle initiale est construite côté client à partir d'`advice_text` + l'invite. C'est cohérent avec la règle F2a-bis "1 seul appel IA par parcours".

Le chat continue ensuite normalement (footer input + bouton Envoyer, endpoints `sapi_megafilter_chat` Sonnet déjà câblés). À la sortie ("Voir la sélection"), le projet est mis à jour, advice_text est regénéré via `sapi_megafilter_advice` comme dans F2a-bis.

#### D. Action "Effacer et recommencer"

- Appel `sapiProject.clear()` (méthode existante de `sapi-project.js`)
- Bascule de la modale vers **S0** (écran 2 portes) sans réponses pré-remplies
- **Pas de confirmation modale ni popup** — le libellé du lien est explicite et le visiteur sait ce qu'il fait
- Si le visiteur veut annuler le reset, il peut fermer la modale (ESC ou click-outside) — mais le projet est déjà effacé. C'est le compromis assumé.

Note : si tu veux ajouter une mini-confirmation inline (genre "Confirmer l'effacement" qui apparaît au clic), à voir avec Robin. Pour la première version, pas de confirmation.

#### E. Action "Voir la sélection"

- Ferme la modale
- Pas de modification du projet
- Pas d'appel IA
- Retour à la grille filtrée (statu quo)

---

### Ce qui n'est PAS dans F2a-ter

- ❌ Confirmation modale pour "Effacer et recommencer" — à voir plus tard si besoin
- ❌ Modification fine d'une réponse individuelle (genre cliquer sur une chip pour la changer) — option C de la discussion non retenue. Si visiteur veut modifier finement, il utilise "Préciser avec Robin" (chat) ou "Effacer et recommencer".
- ❌ Tout autre changement sur S0/S1/S2 — inchangés
- ❌ F2b (fiche produit) — séparé

---

### Critères de succès

1. Sur `/mes-creations/` avec projet : card "Mon projet" affiche un lien **"Modifier mon projet ✎"** (raccourci)
2. Clic sur ce lien → modale s'ouvre **directement à S3** (carrefour) avec chips récap + 3 actions
3. Les chips récap reflètent les réponses du `sapiProject` (Pièce : Salon · Taille : …)
4. Bouton "Voir la sélection" (orange) → ferme la modale, projet inchangé, grille toujours filtrée
5. Bouton "Préciser avec Robin" (wood-dark) → modale bascule vers S2.chat. **Une bulle initiale Robin** apparaît, contenant `advice_text` + *"Qu'est-ce que tu veux affiner ?"*. Aucun nouvel appel IA pour générer cette bulle.
6. Footer chat S2 fonctionne normalement (input, bouton Envoyer)
7. À la sortie du chat ("Voir la sélection" dans S2.chat) → fermeture + advice_text regénéré (comportement F2a-bis inchangé)
8. Lien "Effacer et recommencer" (souligné, en dessous des 2 boutons) → `sapiProject.clear()` + modale bascule vers S0 (2 portes, vide)
9. Aucune confirmation modale pour le reset
10. Esthétique : card Conseiller crème + dashed + badge "Mon projet" (wood-dark), chips standard, hiérarchie visuelle claire des 3 actions
11. Mobile (375px) : 2 boutons s'empilent verticalement + lien souligné en dessous, lisible

---

### Précautions

- **Ne PAS toucher** au flow de fin de S1 (qui continue de fermer direct la modale après écran transition "Robin réfléchit")
- **Ne PAS rajouter** d'appel IA — la bulle initiale Robin est construite côté client à partir d'`advice_text` déjà stocké
- **Mockup #6 reste OBSOLÈTE pour le contenu** — on s'en inspire uniquement pour le composant chips récap (lecture seule). Le reste du mockup (card IA blanche + CTA seul) ne s'applique pas.
- Branche `test-theme-sapi-maison`, push test uniquement
- Si impasse layout 3 actions (mobile notamment), remonter à Robin avec capture

---


## [TÂCHE] F2a-bis — Correctif wording hero + simplification du modèle IA (1 seul appel à la sortie de la modale)

**Date :** 2026-05-19
**Priorité :** haute (correctif F2a livré sur test — Robin a identifié un bug d'incohérence + revoit le rôle de l'IA)
**Branche :** `test-theme-sapi-maison`
**Prérequis :** F2a livrée sur test (cf. retour plus haut).

---

### Contexte

Après test de F2a, Robin a fait 3 retours :
1. **Wording hero** : "Pour ta chambre" présume une appartenance qu'on ne peut pas affirmer. Le sous-titre est trop long.
2. **Incohérence textes IA** : hero "chambre" + phrase IA "cuisine" sur le même écran — symptôme d'un modèle où l'IA est appelée trop souvent et désynchronisée avec le hero PHP.
3. **Réinitialiser/modifier le projet** : la méthode actuelle ne lui plaît pas (sujet traité dans une tâche séparée).

F2a-bis couvre **les Points 1 + 2**. Le Point 3 viendra dans une autre tâche après échange avec Robin.

**Décision Robin sur l'IA :** moins d'IA, et pas n'importe quand. **Un seul appel IA par parcours, à la sortie de la modale.** Le résultat est stocké dans `sapiProject.advice_text` et réutilisé partout ensuite sans nouvel appel. Si pas de parcours fait, on affiche un texte générique pré-rédigé selon la pièce.

---

### À LIRE AVANT TOUTE MODIFICATION

1. `woocommerce/archive-product.php` — où vit le hero (statique PHP avec mapping piece)
2. `assets/sapi-cards-conseiller.js` — la card "Mon projet" qui appelle aujourd'hui `sapi_megafilter_advice` à chaque load
3. `assets/sapi-modal-conseiller.js` — modale S0/S1/S2/S3
4. `functions.php` — endpoints `sapi_megafilter_advice`, `sapi_megafilter_recap`, `sapi_megafilter_freetext`, `sapi_megafilter_chat`
5. `assets/sapi-project.js` — gestion `localStorage.sapiProject`
6. Mémoire Cowork `feedback_wording_selection_luminaires.md` — règle "sélection de luminaires"

---

### Périmètre F2a-bis

#### A. Correctif wording hero (Point 1)

Dans `archive-product.php`, mapping `$piece_hero_map` à corriger :

```php
$piece_hero_map = [
  'salon'    => ['article' => 'un',  'nom' => 'salon'],
  'chambre'  => ['article' => 'une', 'nom' => 'chambre'],
  'cuisine'  => ['article' => 'une', 'nom' => 'cuisine'],
  'bureau'   => ['article' => 'un',  'nom' => 'bureau'],
  'entree'   => ['article' => 'une', 'nom' => 'entrée'],
  'escalier' => ['article' => 'un',  'nom' => 'escalier'],
];
```

H1 devient : `"Pour {article} {nom}"` → *"Pour un salon"*, *"Pour une chambre"*, etc.

**Sous-titre** remplacé par : *"Découvre la sélection de l'atelier pour ton projet"*

Le hero standard (pas de `?piece=`) reste inchangé : H1 *"Mes Créations"* + sous-titre marketing actuel + lien "Conseils de Robin →" visible.

#### B. Nouveau modèle IA — 1 seul appel, à la sortie de la modale

**Règle d'or** : l'IA est appelée **une et une seule fois** par parcours abouti dans la modale. Le résultat est persistant dans `sapiProject.advice_text`. Plus aucun appel IA au load de /mes-creations/, ni au refresh, ni à la navigation.

##### B1. Suppression de l'écran S3 récap (modale mode S1 questions)

L'écran S3 (chips récap + phrase IA + CTA "Voir la sélection") **disparaît** du flow S1.

Nouveau flow S1 :
1. Visiteur répond à la dernière question visible (selon `sapi_guide_get_steps()` visibility logic)
2. **Écran de transition très court** : la modale affiche *"Robin réfléchit à votre projet"* + animation 3 dots pulsants (minimum 700ms même si la réponse IA arrive plus vite — pour la lisibilité)
3. En parallèle, appel `sapi_megafilter_advice` avec le projet final
4. Au retour : `sapiProject.advice_text` est stocké côté client
5. Fermeture de la modale + soft refresh ou re-render de la card "Mon projet" + filtrage grille via `window.sapiShopRefilter()`

**Plus de bouton "Voir la sélection" dans S1.** L'avancée est automatique.

##### B2. Sortie de S2 (mode texte libre) — Option B retenue

Au clic sur "Voir la sélection" depuis le chat S2 :
1. Même écran de transition *"Robin réfléchit à votre projet"* + 3 dots (~700ms minimum)
2. Appel `sapi_megafilter_advice` avec `{answers, conversation: state.modal.conversation, nonce}`
3. L'endpoint reçoit l'historique de chat + les chips activés et génère une phrase de synthèse
4. Stockage `sapiProject.advice_text` + fermeture modale + refresh card "Mon projet"

**Pas de raccourci** sur la dernière bulle Robin — on régénère une phrase de synthèse propre.

##### B3. Endpoint `sapi_megafilter_advice` refactoré

Avant (F2a) : appelé à chaque load de /mes-creations/ avec un projet, retourne une phrase rapide (Haiku, cache 1h serveur).

Après (F2a-bis) :
- Appelé **uniquement** à la sortie de la modale (S1 ou S2)
- **Modèle Sonnet** (qualité du ton, sortie unique, on peut se permettre le coût)
- Input : `{answers, conversation?, nonce}` — `conversation` optionnel, présent uniquement en sortie de S2
- System prompt : génère 1-2 phrases qui *résument le conseil et la sélection* selon le projet (et le chat si présent). Ton chaleureux, signature implicite "— Robin" ajoutée côté front
- Output : `{advice_text: "..."}`
- **Pas de cache serveur** — chaque parcours est unique
- Rate-limited via `sapi_guide_check_rate_limit()` existant
- Fallback en cas d'échec API : retourner le texte générique correspondant à la pièce (cf. B6)

##### B4. Endpoint `sapi_megafilter_recap` supprimé

Cet endpoint pilotait l'écran S3 supprimé. À retirer proprement de `functions.php` (la fonction + le `add_action`).

##### B5. Suppression de l'appel IA au load de /mes-creations/

Dans `assets/sapi-cards-conseiller.js`, retirer tout appel AJAX qui charge la phrase IA au load. Plus de loading state, plus de pulse 600ms — la card "Mon projet" lit sa valeur synchroniquement depuis `sapiProject.advice_text` ou les textes génériques.

##### B6. Textes génériques par pièce — 6 phrases pré-rédigées

Stocker en PHP via `wp_localize_script` (ou directement en JS) :

```php
$piece_generic_advice = [
  'salon'    => "Pour un salon, j'ai sélectionné une variété de luminaires qui créent une atmosphère chaleureuse.",
  'chambre'  => "Pour une chambre, ma sélection privilégie les lumières douces et apaisantes.",
  'cuisine'  => "Pour une cuisine, je propose des éclairages à la fois fonctionnels et conviviaux.",
  'bureau'   => "Pour un bureau, j'ai retenu des luminaires qui aident à la concentration tout en restant beaux.",
  'entree'   => "Pour une entrée, voici des modèles qui marquent l'arrivée sans encombrer.",
  'escalier' => "Pour un escalier, des luminaires conçus pour éclairer et habiller la cage.",
];
```

Suivis tous de la signature *"— Robin"* en Square Peg côté front (le PHP renvoie juste le texte sans signature).

##### B7. Card "Mon projet" — nouvelle logique d'affichage

Côté `assets/sapi-cards-conseiller.js`, l'affichage de la card "Mon projet" suit cette logique synchronisée (zéro AJAX au load) :

```js
function getAdviceText(project) {
  // 1. Si advice_text existe (parcours modale fait) → l'utiliser
  if (project.advice_text) return project.advice_text;
  // 2. Sinon si une pièce est définie → texte générique
  if (project.answers && project.answers.piece) {
    return SAPI_GENERIC_ADVICE[project.answers.piece] || FALLBACK;
  }
  // 3. Fallback ultime (ne devrait jamais arriver)
  return "Voici ma sélection pour votre projet.";
}
```

Plus de loading state visible — la card s'affiche tout de suite avec le texte stocké ou générique.

##### B8. Cohérence hero ↔ card ↔ sapiProject

Pour éviter le bug "hero chambre + texte IA cuisine" observé :
- Au load de `/mes-creations/`, **si `?piece=X` est présent dans l'URL et différent de `sapiProject.answers.piece`**, le `?piece=X` **écrase** la valeur du localStorage immédiatement (avant le render des cards). Le URL fait autorité.
- Le sapiProject est mis à jour, advice_text est effacé (parce qu'il correspondait à l'ancienne pièce), et la card "Mon projet" utilise le texte générique de la nouvelle pièce.
- Sans `?piece=`, le sapiProject existant est utilisé tel quel.

Cette règle vit côté `assets/sapi-project.js` au moment de l'init.

#### C. Mockup #6 (récap S3) — OBSOLÈTE

Le mockup `mockups/mockup-06-modale-recap.html` ne correspond plus au flow. **Ne pas l'implémenter.** Il peut être laissé dans `/mockups/` comme trace historique, ou supprimé si on veut nettoyer.

---

### Ce qui n'est PAS dans F2a-bis

- ❌ Point 3 (réinitialiser/modifier le projet) — discussion à reprendre avec Robin
- ❌ Toucher à F2b (logique projet sur fiche produit) — la même règle "IA minimale" s'appliquera mais on cadrera en F2b
- ❌ Modification de la card "Conseil de Robin" (sans projet) — inchangée
- ❌ Modification de la card sur-mesure — inchangée
- ❌ Modification du flow S0 (2 portes) — inchangé
- ❌ Modification de la conversation S2 elle-même — inchangée (les bulles, les filtres appliqués, etc.)

---

### Critères de succès

1. Sur `/mes-creations/?piece=salon` : H1 = *"Pour un salon"*, sous-titre = *"Découvre la sélection de l'atelier pour ton projet"*
2. Sur `/mes-creations/?piece=chambre` : H1 = *"Pour une chambre"*
3. Sur `/mes-creations/` nu : H1 = *"Mes Créations"* (standard inchangé)
4. Sur `/mes-creations/?piece=salon` sans parcours modale fait : card "Mon projet" affiche **"Pour un salon, j'ai sélectionné une variété de luminaires qui créent une atmosphère chaleureuse. — Robin"** (texte générique, ZÉRO appel AJAX au load — vérifier dans l'onglet Network du navigateur)
5. Parcours S1 complet (réponses jusqu'au bout) : écran transition *"Robin réfléchit"* (~700ms) → fermeture modale → card "Mon projet" affiche le `advice_text` IA fraîchement généré
6. Parcours S2 chat + clic "Voir la sélection" : même écran transition + appel IA + stockage + fermeture
7. Refresh `/mes-creations/` après parcours : `advice_text` toujours là, **zéro appel IA réseau** (vérifier Network)
8. Visiteur navigue de `/mes-creations/?piece=salon` à `/mes-creations/?piece=chambre` (sans vider localStorage) : sapiProject est mis à jour avec chambre, advice_text est effacé, le texte générique chambre s'affiche → **cohérence hero/card garantie**
9. Endpoint `sapi_megafilter_recap` n'existe plus dans `functions.php` (grep retourne 0)
10. L'écran S3 (chips récap + phrase IA + CTA "Voir la sélection") n'apparaît plus jamais dans la modale
11. Si l'appel IA échoue à la sortie : on stocke le texte générique de la pièce comme advice_text et on ferme la modale (pas de blocage)

---

### Précautions

- **Ne PAS appeler l'IA au load** — uniquement à la sortie de la modale. Si Claude Code est tenté de remettre un appel "rapide" sur la card "Mon projet", c'est NON.
- **Sauvegarder `advice_text` AVANT de fermer la modale** pour que la card "Mon projet" puisse l'afficher immédiatement
- **Fallback gracieux si l'IA plante** : utiliser le texte générique de la pièce. Ne pas afficher d'erreur au visiteur.
- **Mockup #6 (récap S3) est OBSOLÈTE** — ne pas s'en inspirer pour le flow.
- Branche `test-theme-sapi-maison`, push test uniquement
- Si impasse, remonter à Robin avec captures plutôt que s'acharner

---


## [TÂCHE] F2a — Refonte UX /mes-creations/ + modale-tunnel 2 portes + card sur-mesure + projet persistant

**Date :** 2026-05-19
**Priorité :** haute (pivot après livraison F1 — Robin a jugé la card méga-filtre inline trop froide)
**Branche :** `test-theme-sapi-maison`
**Prérequis :** F1a/F1a-bis/F1a-ter/F1c/F1b livrées et validées (déjà fait).

---

### Contexte

Suite à la livraison complète du méga-filtre F1, Robin a constaté que la **card "Affiner avec Robin" inline** avec ses 7 chips dropdowns sur /mes-creations/ ne donnait pas le ressenti voulu — trop "filtre catalogue", pas assez "conseiller chaleureux". **Pivot acté** vers une modale-tunnel à 2 portes (questions guidées / texte libre), avec un projet persistant qui suit le visiteur sur tout le site.

**Ce qu'on garde de F1 :**
- Câblage home → `/mes-creations/?piece=X` (intact)
- Hero réactif au `?piece=` (intact)
- Cleanup ancien Conseiller V2 effectué (intact)
- Backend IA F1b : endpoints `sapi_megafilter_freetext` (Haiku) + `sapi_megafilter_chat` (Sonnet) — réutilisés tels quels, juste appelés depuis la nouvelle modale
- Roompicker sur `/conseils-eclaires/` (intact)
- CTA "Affiner ma sélection" sur pages catégorie (intact)
- Plus de pills catégorie ni search bar sur /mes-creations/ (intact)
- Filtrage client-side `mega-filtre.js` `cardMatches()` (réutilisé)

**Ce qui change avec F2a :**
- Suppression de la card `.megafilter-bar` inline + ses chips dropdowns
- Sur /mes-creations/ sans projet → **card "Conseil de Robin"** invite à ouvrir la modale
- Sur /mes-creations/ avec projet → **card "Mon projet"** avec phrase IA + lien Modifier
- La modale devient **un tunnel à 2 portes** (questions guidées / texte libre)
- Mode questions = parcours linéaire conditionnel avec gros boutons (1 question / écran)
- Mode texte = chat IA (la version actuelle F1b reste, juste avec le nouvel écran de démarrage en amont)
- Écran récap final commun avec phrase IA personnalisée
- Nouvelle card **"Sur-mesure"** dans la grille (2 états : sans projet / avec projet)
- **Persistance projet** dans `localStorage.sapiProject` — silencieux (pas d'indicateur visuel global)

---

### ⚠️ À LIRE AVANT TOUTE MODIFICATION

**Mockups de référence visuelle** (DOIVENT être ouverts dans un navigateur pour comprendre les interactions — leurs classes CSS sont des noms de travail, pas la convention finale) :

1. `site-web/mockups/mockup-01-message-invitation-v2.html` — Card "Conseil de Robin" sans projet
2. `site-web/mockups/mockup-02-phrase-ia-projet-v2.html` — Card "Mon projet" avec projet
3. `site-web/mockups/mockup-03-modale-demarrage.html` — Écran 2 portes
4. `site-web/mockups/mockup-04-modale-questions.html` — Mode questions guidées
5. `site-web/mockups/mockup-05-modale-texte-libre.html` — Mode texte (état initial + chat)
6. `site-web/mockups/mockup-06-modale-recap.html` — Écran récap (variante A : fin mode complet)
7. `site-web/mockups/mockup-07-card-sur-mesure.html` — Card sur-mesure (2 états)

**Code existant à lire** :

1. `woocommerce/archive-product.php` — actuellement contient `.megafilter-bar` et tout le shell modale F1b. À refondre.
2. `assets/mega-filtre.js` — état + logique filtrage + appels endpoints IA. Beaucoup à réutiliser/refactorer.
3. `style.css` — chercher `.megafilter-*` (à dégager ou re-styler), `.robin-bandeau*` (à conserver), `.reassurance-*` (à conserver), `.product-card-cinetique` (à conserver — c'est la grille).
4. `inc/guide-data.php` — `sapi_guide_get_steps()` toujours utilisé pour la config des 7 questions et leur logique de visibilité conditionnelle.
5. `functions.php` — endpoints `sapi_megafilter_freetext` / `sapi_megafilter_chat` à réutiliser tels quels.
6. `memory/design_system.md` (côté Cowork) — typo, couleurs, ombres, border-radius officiels.
7. `memory/project_conseiller_v3_pivot.md` (côté Cowork) — pattern visuel universel Conseiller acté.

---

### Pattern visuel universel Conseiller (à respecter partout)

Acté pendant les 8 mockups, applicable à TOUS les composants Conseiller sur le site :

- **Fond** : `var(--color-warm)` (crème)
- **Bordure dashed décorative interne** : `1.5px dashed rgba(139, 115, 85, 0.35)`, `border-radius: 12px`, `position: absolute; inset: 12px`
- **Border-radius container** : `16px`
- **Badge en haut** :
  - Variante par défaut (Conseil/Mon projet) : fond `var(--color-wood-dark)` + texte blanc uppercase
  - Variante Sur-mesure : fond `var(--color-orange)` + texte blanc uppercase
  - Padding `7px 16px`, `border-radius: 50px`, font-size 10.5px, letter-spacing 0.14em, icône SVG crayon
- **H2** : Montserrat 700 uppercase `var(--color-wood-dark)`, `clamp(20px, 3vw, 32px)`
- **CTA principal** : pill orange (`var(--color-orange)`, hover `--color-orange-dark`) avec icône SVG, padding `14px 30px`, border-radius 50px, font-size 13px uppercase 700
- **Pill secondaire** ("Comment choisir ?", style F2b) : fond crème + bordure dashed wood, hover fond blanc + bordure pleine
- **Signature** : "— Robin" en `'Square Peg'` cursive `var(--color-wood)` 22-24px
- **Chips récap** (non interactifs) : fond blanc + bordure `var(--color-line)`, padding `7px 14px`, font-size 12.5px, format `<key> : <valeur>`
- **Pas de sous-titres superflus** — le H2 + les chips parlent d'eux-mêmes
- **Bouton Retour** centré dans la modale, style lien souligné `var(--color-wood)` 13px

---

### Périmètre F2a

#### A. Suppression de la card méga-filtre inline (chips)

Dans `archive-product.php`, supprimer entièrement la section `.megafilter-bar` (les 7 chips dropdowns + bouton "Décrire précisément" + Tout effacer). Et tout le CSS `.megafilter-bar*`, `.megafilter-chip*`, `.megafilter-header*`, `.megafilter-actions*`, etc.

**À conserver** : la modale plein écran (`.megafilter-modal*`) — on la refond mais on garde le shell HTML pour ne pas tout réécrire. Ses sous-éléments seront repensés selon mockups #3 à #6.

#### B. Sur /mes-creations/ — Card "Conseil de Robin" (sans projet) vs "Mon projet" (avec projet)

État du projet déterminé par `localStorage.sapiProject` (cf. section G) **ou** par `?piece=X` dans l'URL (qui crée un projet partiel immédiat).

**Si pas de projet** (`localStorage.sapiProject` vide ET pas de `?piece=`) :
- Insérer **la card "Conseil de Robin"** selon mockup #1 v2, juste entre le hero et la grille
- Container `<section>` max-width 1200px
- Card crème + dashed (pattern universel)
- Badge "Conseil de Robin"
- H2 "Un coup de main pour choisir ?"
- Sous-titre "Décrivez votre projet — pièce, taille, style — et Robin vous propose une sélection de luminaires adaptés."
- CTA orange "Décrire mon projet" — ouvre la modale (cf. section D)
- Pas de chips inline, pas de search bar — c'est tout

**Si projet existant** :
- Insérer **la card "Mon projet"** selon mockup #2 v2, même position
- Badge "Mon projet"
- Phrase IA italique (générée côté serveur via `sapi_megafilter_chat` ou similaire) + signature "— Robin" Square Peg
- Lien discret "Préciser ou modifier mon projet ✎" → ouvre la modale au récap (mockup #6) avec le projet pré-chargé
- Le filtrage de la grille produit utilise les réponses du projet (logique `cardMatches()` réutilisée de F1b)

Pour la phrase IA :
- Côté serveur, créer un nouvel endpoint léger `sapi_ajax_megafilter_advice` qui prend l'état du projet (chips répondus) en input et retourne une phrase courte de 2-3 phrases
- Modèle Haiku (rapide, peu cher)
- Cache 1h par combinaison (piece+taille+style) — pas besoin de regénérer à chaque visite
- Si l'endpoint plante, fallback à une phrase générique : "Voici ma sélection pour votre projet. — Robin"

#### C. La modale — refonte complète à partir des mockups #3 à #6

La modale est plein écran (`100dvh` mobile, `100vh` desktop). Layout flexbox :
```
.megafilter-modal (flex column, 100dvh)
  .megafilter-modal__header (flex-shrink: 0)
  .megafilter-modal__body (flex: 1, overflow: hidden)
    .conseiller-card (display: flex column, max-height: 100%)
      [contenu selon état]
  .chat-footer (flex-shrink: 0) — uniquement en mode chat
```

**Plusieurs états gérés par JS :**

**État S0 — Écran de démarrage 2 portes** (mockup #3)
- Card Conseiller avec badge, H2 "Comment voulez-vous me parler de votre projet ?", sous-titre "Deux manières d'arriver à une sélection de luminaires adaptés à votre projet."
- 2 grosses cards-portes côte à côte (grille 1fr 1fr, empilées mobile) avec séparateur "ou" entre
- Porte A "Je choisis" + icône checklist → bascule vers S1
- Porte B "Je décris" + icône plume → bascule vers S2

**État S1 — Mode questions guidées** (mockup #4)
- Card Conseiller
- Barre de progression fine en haut (sans chiffre)
- Badge "Conseil de Robin"
- H2 = question courante (depuis `sapi_guide_get_steps()`)
- Grille de choix : gros boutons-cards (3 colonnes desktop / 2 mobile), même style que les room-cards du roompicker existant (`.room-card`) — icône dans un fond crème circulaire + label uppercase
- Clic sur un choix → enregistre la réponse + avance auto à la question suivante (la prochaine `visible` selon `sapi_guide_get_steps()` — mirror de la logique conditionnelle existante)
- À la dernière question répondue → bascule vers S3 (récap)
- Bouton "← Retour" centré en bas → revient à la question précédente, ou à S0 si on est sur la 1re question
- **PAS de sous-titre superflu** sous le H2, **PAS de hint** "Cliquez sur une réponse"

**État S2 — Mode texte libre** (mockup #5, 2 sous-états)
- Réutilise la logique F1b actuelle (`submitFreetext` + `submitChat`) avec adaptations UI
- **Sous-état S2.start** : input central pill + bouton submit orange (flèche) + 3 suggestions cliquables en pills crème + bouton "← Retour" centré
- **Sous-état S2.chat** : 
  - Layout flex column (badge en haut fixe, zone chat scrollable au milieu, CTA + footer en bas fixes)
  - Bulles user à droite (fond `wood-dark`, blanc, border-radius 18px sauf bottom-right 4px)
  - Bulles Robin à gauche (fond blanc, bordure `--color-line`, border-radius 18px sauf bottom-left 4px)
  - Sous chaque bulle Robin qui applique des filtres : encart "Filtres appliqués" (fond `rgba(139,115,85,0.08)`, border-left 3px wood)
  - Zone chat = `overflow-y: auto` avec scrollbar fine wood
  - Autoscroll vers la nouvelle bulle après chaque envoi
  - Le CTA "Voir la sélection" reste en bas de card, jamais dans le scroll (`flex-shrink: 0`)
  - Footer fixe avec input "Continuer à discuter avec Robin…" + bouton Envoyer wood-dark

**État S3 — Récap final** (mockup #6 variante A)
- Card Conseiller
- Badge "Conseil de Robin"
- H2 "Voici votre projet"
- Chips de récap : tous les `<key> : <valeur>` des réponses (Pièce : Salon · Taille : Spacieuse · etc.)
- **Phrase IA personnalisée** dans une card blanche ombrée à part (italique + signature "— Robin" Square Peg à droite). Endpoint `sapi_megafilter_chat` ou un nouveau plus léger (Sonnet pour la qualité).
- CTA orange "Voir la sélection →" → ferme la modale, applique les filtres à la grille via `window.sapiShopRefilter()`, sauvegarde le projet en localStorage
- Bouton "← Modifier mes réponses" centré → revient à S1 sur la dernière question

**Transitions S0 → S1, S0 → S2, S1 → S3, S2 → S1 (si user clique Retour vers questions), etc.**
- Animation fade-in/out 200ms
- L'état de la modale est tracké dans `state.modal.screen = 's0' | 's1' | 's2start' | 's2chat' | 's3'`

#### D. Câblage du CTA "Décrire mon projet" de la card "Conseil de Robin"

Sur /mes-creations/ sans projet, le CTA de la card "Conseil de Robin" ouvre la modale **à l'état S0** (écran 2 portes).

Sur /mes-creations/ avec projet, le lien "Préciser ou modifier mon projet" de la card "Mon projet" ouvre la modale **à l'état S3** (récap) avec le projet pré-chargé. L'utilisateur peut alors cliquer "Modifier mes réponses" pour repasser en S1.

#### E. Card "Sur-mesure" dans la grille produit (mockup #7)

**Emplacement** : insérer la card **dans la grille produit**, intercalée. Position à arbitrer côté Claude Code (après les 6-8 premiers produits ? après les best-sellers ? toujours à la fin ?). Robin a précisé : "pour le moment seulement dans la grille de la page mes-creations" (pas sur les pages catégorie pour l'instant).

**Deux états selon `localStorage.sapiProject`** :

**État A — Sans projet** (mockup #7 v2)
- Card crème + dashed + badge **orange** "Sur-mesure"
- H2 "Et si on créait votre luminaire sur-mesure ?"
- Intro "Laissez votre email et décrivez votre projet en quelques mots."
- Form : input email (obligatoire) + textarea description du projet (obligatoire)
- CTA orange "Recevoir une proposition →"
- Réassurance "Réponse de Robin sous 48h · Aucun engagement"

**État B — Avec projet** (mockup #7 v2 état condensé)
- Card crème + dashed + badge orange "Sur-mesure" — **version compacte** (padding réduit)
- H2 court "Un sur-mesure pour ce projet ?"
- Pas d'intro (le H2 + chips parlent d'eux-mêmes)
- Chips récap discrets (fond transparent, bordure dashed wood-mid, juste les valeurs sans labels)
- Form : input email (obligatoire) + input texte single-line "Précisions ou inspirations (optionnel)" plus discret
- CTA orange "Recevoir une proposition →"
- Réassurance idem

**Backend** : nouvel endpoint AJAX `sapi_ajax_megafilter_surmesure` qui :
- Reçoit `{email, description, project_snapshot, nonce}`
- Envoie un email à `robin@atelier-sapi.fr` avec le contenu (description + snapshot projet si présent)
- Optionnel : ajoute l'email à une liste Brevo dédiée (à confirmer avec Robin avant)
- Retourne un succès qui affiche un message de confirmation dans la card ("✓ Reçu, Robin vous écrit sous 48h.")

#### F. Persistance projet — `localStorage.sapiProject`

Format JSON :
```json
{
  "answers": { "piece": "salon", "taille": "spacieuse", "sortie": "plafond", ... },
  "labels":  { "piece": "Salon / Salle à manger", ... },
  "created_at": 1716000000,
  "updated_at": 1716000123,
  "session_id": "optional-uuid-from-modale-session"
}
```

**Sauvegarde** :
- À chaque réponse dans la modale (état S1 questions OU état S2 chat avec filters_update)
- À l'arrivée sur /mes-creations/?piece=X (crée un projet partiel avec juste la pièce)
- À la fermeture de la modale (sauvegarde finale)

**Lecture** :
- Au load de /mes-creations/ → détermine si on affiche la card "Conseil de Robin" ou "Mon projet"
- Au load de fiche produit (F2b) → détermine la pré-sélection variation
- Au load de toute page → SILENCIEUX pour l'instant (pas d'indicateur global)

**Reset** :
- Pas de bouton "Vider mon projet" explicite pour l'instant (à voir selon usage)
- Le projet vit aussi longtemps que le localStorage. À voir si on ajoute une expiration (genre 30 jours).

#### G. Filtrage de la grille produit selon le projet

La logique `cardMatches()` de `mega-filtre.js` est conservée. Elle prend en input les `answers` du projet et filtre les cards `.product-card-cinetique` via `display: none` / `display: block`. Le compteur est mis à jour aussi.

Quand un projet est actif : la grille est filtrée à l'arrivée. Sinon la grille montre tous les modèles (sauf Accessoires + Carte cadeau, exclusion par défaut).

---

### Endpoints IA à réutiliser ou créer

**Réutilisés tels quels (F1b)** :
- `sapi_ajax_megafilter_freetext` — pour les inputs S2.start (1er message + 3 suggestions)
- `sapi_ajax_megafilter_chat` — pour les messages suivants en S2.chat

**Nouveaux à créer en F2a** :
- `sapi_ajax_megafilter_advice` — phrase IA courte pour la card "Mon projet" sur /mes-creations/. Input : projet. Output : 1-2 phrases. Modèle Haiku. Cache 1h.
- `sapi_ajax_megafilter_recap` — phrase IA pour l'écran récap S3. Input : projet complet. Output : phrase plus longue/conseillère. Modèle Sonnet (qualité du ton). Pas de cache (chaque récap est unique).
- `sapi_ajax_megafilter_surmesure` — soumission de la card sur-mesure. Input : email + description + snapshot projet. Output : succès/erreur + envoi email Robin.

---

### Ce qui n'est PAS dans F2a

- ❌ La logique de pré-sélection variation sur fiche produit — c'est F2b
- ❌ La pill "Comment choisir ?" sur fiche produit — c'est F2b
- ❌ Le mode court de la modale (3 questions) — c'est F2b (appelé depuis la pill)
- ❌ L'indicateur projet global visible (bandeau, badge) — pas pour l'instant
- ❌ La logique d'expiration du projet localStorage — pas pour l'instant
- ❌ Photos produit adaptées à la pièce — projet futur séparé (cf. mémoire `project_photos_par_piece.md`)

---

### Critères de succès

1. Sur `/mes-creations/` sans projet : la card "Conseil de Robin" s'affiche entre le hero et la grille, avec le CTA "Décrire mon projet"
2. Clic sur CTA → modale s'ouvre à l'état S0 (écran 2 portes)
3. Clic sur "Je choisis" → S1 (1re question : Pièce avec 6 choix gros boutons-cards)
4. Clic sur "Salon / Salle à manger" → avance auto à la question suivante (Taille)
5. Quand toutes les questions visibles sont répondues → S3 (récap avec chips + phrase IA + CTA "Voir la sélection")
6. Clic sur "Voir la sélection" → modale se ferme, projet sauvegardé en localStorage, grille filtrée, page rechargée propre avec card "Mon projet" affichée
7. Sur retour /mes-creations/ avec projet → card "Mon projet" affichée (pas card "Conseil de Robin"), grille déjà filtrée, phrase IA visible
8. Clic sur "Préciser ou modifier mon projet" depuis card "Mon projet" → modale s'ouvre à S3, on peut cliquer "Modifier mes réponses" pour repasser en S1
9. Sur S0, clic "Je décris" → S2.start (input texte + 3 suggestions). Idem au comportement F1b actuel mais avec le pré-écran S0 en amont
10. Le câblage `?piece=salon` continue de fonctionner — il crée un projet partiel en localStorage et la card "Mon projet" s'affiche (avec juste la pièce + phrase IA légère)
11. Card "Sur-mesure" s'affiche dans la grille (position à arbitrer) :
    - Sans projet → état A (form complet)
    - Avec projet → état B (compact, chips récap, input optionnel)
12. Soumission du form sur-mesure → email envoyé à Robin + message de confirmation dans la card
13. Le bandeau réassurance reste affiché en haut de toutes les pages, fonction `randomizeMobileReassurance()` toujours active
14. Mobile (375px) : tout fonctionne, modale en `100dvh`, zone chat scrollable, gros boutons-cards en 2 colonnes, card sur-mesure prend toute la largeur

---

### Précautions

- **Pattern visuel universel** (cf. section dédiée) à respecter STRICTEMENT sur toutes les cards Conseiller. Pas d'écart.
- **Ne PAS toucher** au bandeau réassurance (`.robin-bandeau`, `.reassurance-*`), aux cards produit (`.product-card-cinetique`), au hero, au footer, à la page conseils, à la home
- **Ne PAS supprimer** les helpers `sapi_guide_*` ni `inc/guide-data.php` — la logique conditionnelle des questions vit toujours là
- **Ne PAS supprimer** les endpoints F1b — réutilisés directement
- **Ne PAS introduire** de hex en dur — toutes les couleurs via variables CSS
- **Mockups = STRUCTURE et WORDING, pas pixel-perfect.** Les classes CSS des mockups (`.conseiller-card`, `.megafilter-*`, etc.) sont des noms de travail. Adapter aux conventions existantes du thème quand pertinent (réutiliser `.product-card-cinetique`, classes Sapi existantes…).
- Branche `test-theme-sapi-maison`, push test uniquement
- **Volumétrie attendue** : ~800-1200 lignes ajoutées (HTML + JS + CSS), suppressions du méga-filtre inline (~300 lignes). Bilan net positif modéré.
- Si impasse CSS/layout, ne pas s'acharner — remonter à Robin avec captures

---


## [TÂCHE] F2b — Logique projet sur fiche produit + pill "Comment choisir ?"

**Date :** 2026-05-19
**Priorité :** moyenne (à enchaîner après validation F2a sur test)
**Branche :** `test-theme-sapi-maison`
**Prérequis :** F2a mergée sur test, modale fonctionnelle avec modes S0/S1/S3.

---

### Contexte

F2a livre la modale Conseiller V3 sur /mes-creations/ avec persistance du projet en `localStorage.sapiProject`. F2b porte cette logique projet sur les **fiches produit** :

- **Pré-sélection automatique de la variation** selon le projet (logique qui existait avant F1c — supprimée en cleanup, à récupérer du git history)
- **Pill "Comment choisir ?"** au-dessus des sélecteurs de variation
- **Mode court de la modale** (3 questions : pièce, taille, style) appelé depuis la pill quand le visiteur n'a pas encore de projet
- **Mode récap direct** (saute les questions) quand le visiteur a déjà un projet

---

### À LIRE AVANT TOUTE MODIFICATION

**Mockups de référence** :
- `site-web/mockups/mockup-06-modale-recap.html` — variante B (mode court fiche produit, CTA "Appliquer cette sélection")
- `site-web/mockups/mockup-08-pill-fiche-produit.html` — pill "Comment choisir ?" sur fiche produit + cas A/B

**Code existant à lire** :
- `woocommerce/single-product.php` — la fiche produit. Repérer où s'insère la pill (juste au-dessus des sélecteurs de variation)
- `assets/cinetique.js` (et autres modules WC) — pour la logique de présélection variation
- **Git history** : avant le commit F1c (`3be8ba7`), les fichiers `assets/guide-personalize.js` (80 lignes) et le bloc lignes 561-615 de `assets/cinetique.js` (55 lignes) contenaient la logique de pré-sélection. Récupérer ces deux blocs via `git show <commit-avant-F1c>:path/to/file` et les adapter au nouveau localStorage `sapiProject`.
- `assets/mega-filtre.js` (livré en F2a) — la modale et ses états S0/S1/S2/S3. Pour F2b on ajoute un état S1-court (3 questions seulement)
- `memory/project_conseiller_v3_pivot.md` — la vision globale

---

### Périmètre F2b

#### A. Pill "Comment choisir ?" sur fiche produit

Dans `woocommerce/single-product.php`, insérer une pill **juste au-dessus** des sélecteurs de variation WooCommerce (taille, essence). Pattern visuel exact selon mockup #8 :

```css
.help-pill {
  display: inline-flex; gap: 7px; padding: 8px 18px;
  background: var(--color-warm);
  color: var(--color-wood-dark);
  border: 1.5px dashed rgba(139, 115, 85, 0.5);
  border-radius: 50px;
  font-size: 12px; font-weight: 600; letter-spacing: 0.06em;
  cursor: pointer;
}
.help-pill:hover {
  background: #fff;
  border-color: var(--color-wood-dark);
}
```

Icône SVG = point d'interrogation rond. Libellé = "Comment choisir ?".

Position : juste avant le 1er sélecteur de variation, dans une `<div class="help-pill-row">` (cf. mockup).

#### B. Au clic sur la pill — comportement conditionnel

**Si pas de projet** (`localStorage.sapiProject` absent ou vide) :
- Ouvrir la modale Conseiller en **mode court S1-court**
- Mode court = **3 questions seulement** : `piece`, `taille`, `style` (les questions "always" + Taille qui se débloque dès qu'une pièce est répondue)
- Skip toutes les autres questions conditionnelles (Éclairage, Sortie, Hauteur, Table)
- À la fin des 3 questions → écran S3-recap variante B (mockup #6 variante B) avec CTA "Appliquer cette sélection"

**Si projet existant** (`localStorage.sapiProject` présent) :
- Ouvrir la modale directement à l'état **S3-recap variante B** (saute les questions)
- Le récap affiche les chips du projet + une phrase IA spécifique au produit en cours :
  - Endpoint dédié `sapi_ajax_megafilter_product_advice` qui prend `{project, product_id}` en input
  - Modèle Sonnet
  - Output : phrase qui recommande explicitement UNE variation du produit en cours
  - Exemple : *"Pour un salon spacieux et moderne comme le vôtre, je vous recommande la version **Taille L** de Gaston, en peuplier."*

#### C. Pré-sélection de la variation à l'arrivée sur la fiche produit

**Au load de single-product.php**, si `localStorage.sapiProject` existe :
- Lire les `answers` du projet (notamment `taille`)
- Appliquer le mapping pour pré-cocher la bonne variation côté JS WooCommerce
- Ajouter une classe `.is-selected` sur le swatch ciblé
- Afficher un petit **hint discret** à côté du label de l'attribut : *"✓ Pré-sélectionné pour votre projet"* en italique wood (cf. mockup #8 cas B)

**Mapping de pré-sélection** (à reprendre du git history pré-F1c) :
- Taille du projet → variation de taille du produit
- Logique : matcher le slug `taille` du projet (`petite`, `moyenne`, `grande`, `ouvert`) au format/dimension de la variation WC
- Le code historique faisait `intime → S`, `confortable → M`, `spacieuse → L` (à confirmer en lisant `assets/guide-personalize.js` avant F1c)
- Note : tous les produits n'ont pas le même set de variations — gérer le cas où aucune variation ne matche (rien faire, pas d'erreur)

#### D. CTA "Appliquer cette sélection" du récap mode court

Au clic sur le CTA orange "Appliquer cette sélection" du récap S3-court :
- Fermer la modale
- Appliquer la pré-sélection variation comme décrit en C
- Animer / scroller vers les sélecteurs de variation pour montrer le résultat
- Sauvegarder le projet en localStorage (s'il vient d'être créé dans la modale court)

#### E. État du mode court de la modale (nouveau)

Côté `assets/mega-filtre.js`, ajouter un mode `state.modal.short = true` qui :
- Filtre les questions à `piece`, `taille`, `style` uniquement
- Saute les autres questions même si elles seraient visibles selon `sapi_guide_get_steps()` visibility logic
- Au récap (S3) : affiche le CTA "Appliquer cette sélection" au lieu de "Voir la sélection"
- À la fermeture/clic CTA : applique la logique de pré-sélection produit (au lieu de filtrer la grille /mes-creations/)

#### F. Endpoint backend `sapi_ajax_megafilter_product_advice`

Nouveau dans `functions.php` :
- Reçoit `{project_answers, product_id, nonce}`
- Récupère les variations WC du produit
- Détermine la variation recommandée selon les règles (taille du projet → taille du produit, + éventuellement style si on a une vision plus fine)
- Construit un system prompt qui inclut : nom du produit, variations disponibles, projet du visiteur
- Appelle Claude Sonnet
- Output : phrase IA qui recommande explicitement UNE variation avec un mot sur le pourquoi
- Cache 1h par combinaison (project_hash + product_id)

---

### Ce qui n'est PAS dans F2b

- ❌ Le changement de variation au-delà de la pré-sélection initiale (l'utilisateur garde la main complète sur les sélecteurs WC)
- ❌ Photos produit adaptées à la pièce (projet futur séparé)
- ❌ Pill sur les pages catégorie (Robin a dit "plus tard pour les pages catégorie")

---

### Critères de succès

1. Sur n'importe quelle fiche produit `/produit/<slug>/` : la pill "Comment choisir ?" s'affiche juste au-dessus des sélecteurs de variation
2. Sans projet : clic pill → modale S0 → utilisateur clique "Je choisis" → modale **S1-court** (3 questions max) → S3-recap variante B
3. Avec projet : clic pill → modale directement à **S3-recap variante B** (saute les questions) avec phrase IA spécifique au produit
4. CTA "Appliquer cette sélection" → ferme la modale + pré-sélectionne la bonne variation côté WC
5. À l'arrivée sur fiche produit avec projet existant : variation pré-sélectionnée automatiquement (sans clic) + hint discret "Pré-sélectionné pour votre projet"
6. Sans projet : aucune variation pré-sélectionnée par défaut (état standard WC)
7. La logique de pré-sélection ne casse PAS le comportement WC normal (changement manuel de variation, ajout au panier, etc.)
8. Aucune régression sur les autres fonctionnalités fiche produit (galerie, prix, description, etc.)
9. Mobile (375px) : pill bien visible, modale 100dvh, tout fluide

---

### Précautions

- **Récupérer le code de pré-sélection variation du git history** (commit avant `3be8ba7` F1c) — ne pas le réécrire from scratch. Adapter aux noms localStorage actuels.
- **Pattern visuel Conseiller** à respecter (pill dashed wood crème, modale identique à F2a)
- **Ne PAS toucher** au bandeau réassurance, à la grille /mes-creations/ (F2a), à la home
- **Ne PAS modifier** la logique métier WooCommerce native (variations, panier, etc.) — juste pré-cocher
- Branche `test-theme-sapi-maison`, push test uniquement
- Si impasse, remonter à Robin avant d'écrire 200 lignes de hack

---


## [TÂCHE] F1b — Intégration IA dans la modale "Décrire précisément mon projet"

**Date :** 2026-05-18
**Priorité :** haute (dernière brique avant merge master du méga-filtre)
**Branche :** `test-theme-sapi-maison`
**Prérequis :** F1a-ter et F1c mergées sur test (déjà fait).

---

### Contexte

Le shell de la modale plein écran "Décrire précisément mon projet" est livré depuis F1a, mais c'est aujourd'hui une démo :
- L'input central "Décris ton projet" ne fait rien (pas de submit câblé)
- Les 3 suggestions sont des simulations en dur qui cochent des filtres pré-déterminés
- Le footer input "Continuer à discuter avec Robin" + bouton Envoyer sont disabled
- Aucune intégration Claude API

F1b transforme tout ça en vraie modale IA, en **réutilisant le backend déjà existant** (endpoints AJAX qui parlent à Claude API, helpers PHP, rate limit, logging). On adapte le format des payloads pour le contexte méga-filtre — on ne réécrit pas la plomberie Claude.

**Décision Robin (option A) :** pas de commentaire de Robin sur la grille pour le moment. L'IA s'exprime UNIQUEMENT dans la modale, quand le visiteur la sollicite explicitement.

---

### À LIRE AVANT TOUTE MODIFICATION

1. `assets/mega-filtre.js` — le shell de la modale (états start/chat, les 3 simulations câblées à virer)
2. `functions.php` lignes 1949 — `sapi_ajax_guide_results()` : endpoint de reco initiale (à adapter ou réutiliser)
3. `functions.php` lignes 2279 — `sapi_ajax_guide_refine()` : endpoint conversationnel chat, déjà rate-limited et câblé sur Claude API
4. `functions.php` lignes 4775 — `sapi_ajax_robin_log_session()` : endpoint de logging des sessions (le CSV qu'on utilise pour analytics)
5. `functions.php` — chercher `sapi_guide_build_*_prompt()` (builders de system prompts) et `sapi_guide_check_rate_limit()`
6. Les system prompts existants côté `sapi_guide_build_refine_prompt()` etc. — on en adapte la structure pour le méga-filtre

---

### Périmètre F1b

#### A. Deux nouveaux endpoints AJAX (côté backend)

Créer dans `functions.php` deux nouveaux endpoints **distincts** de ceux de l'ancien Conseiller pour éviter de mélanger les contextes (l'ancien Conseiller étant mort, ses endpoints peuvent à terme être supprimés sans risque) :

**A1. `sapi_ajax_megafilter_freetext`** — extraction filtres depuis texte libre
- Reçoit : `{message: string, nonce: string}`
- Calls Claude **Haiku** (rapide, déterministe pour extraction)
- System prompt : "Tu es Robin. Extrait les filtres structurés du texte libre. Réponds en JSON pur, sans prose. Format : `{filters: {piece?, taille?, eclairage?, sortie?, hauteur?, table?, style?}, message: \"<1-2 phrases de Robin\">}`. Les slugs autorisés pour chaque clé sont (cf. `sapi_guide_get_steps()`). Si tu ne peux pas extraire un filtre avec confiance, laisse-le absent."
- Retourne : `{success: true, filters: {...}, message: "...", session_id?: string}`
- Rate-limited via `sapi_guide_check_rate_limit()`
- Log la session via le même mécanisme que l'ancien Conseiller (contexte = `megafilter_freetext`)

**A2. `sapi_ajax_megafilter_chat`** — conversation libre dans la modale
- Reçoit : `{user_message: string, current_filters: object, conversation: array, session_id?: string, nonce: string}`
- Calls Claude **Sonnet** (qualité du ton de Robin)
- System prompt :
  - Persona Robin (ton chaleureux, tutoiement, artisan lyonnais)
  - État actuel des chips comme contexte
  - Catalogue produit dynamique (réutiliser `sapi_guide_query_all_products()` ou similaire pour passer la liste filtrée actuelle)
  - Instruction : "Tu peux répondre en prose conversationnelle. Si tu veux ajuster les filtres (parce que l'utilisateur a clarifié ou changé d'avis), inclus un objet `filters_update` optionnel dans ta réponse JSON. Sinon, juste `{message: \"...\"}`. Tu peux aussi suggérer un contact via `{action: \"contact\"}` si l'utilisateur est bloqué."
- Retourne : `{success: true, message: "...", filters_update?: {...}, action?: "contact"|null, conversation: array}` (renvoyer la conversation mise à jour pour le state frontend)
- Rate-limited, logged (contexte = `megafilter_chat`), max 15 messages par session comme l'ancien Conseiller

Le code des deux endpoints **réutilise** :
- `sapi_guide_check_rate_limit()` (rate limit)
- Le wrapper d'appel Claude API existant (extraire en helper `sapi_claude_call($model, $system, $messages)` si pas déjà fait)
- `sapi_guide_query_all_products()` pour le catalogue dynamique
- Le mécanisme de log session du CSV admin
- Le nonce `sapi-guide-results` (ou un nouveau `sapi-megafilter` à créer)

**Anciens endpoints à NE PAS toucher pour l'instant** : `sapi_ajax_guide_refine`, `sapi_ajax_guide_results`, `sapi_ajax_robin_conseil_step`, `sapi_ajax_robin_filter_products`. Ils sont orphelins (plus de frontend), on les supprimera dans une F1d cleanup plus tard une fois qu'on a confirmé que les nouveaux endpoints couvrent tous les cas d'usage.

#### B. Câblage frontend dans `assets/mega-filtre.js`

**B1. Input central "Décris ton projet" (état start de la modale)**
- Au submit (Entrée ou clic sur un bouton) : appel POST `sapi_ajax_megafilter_freetext` avec `{message: input.value, nonce}`
- Pendant l'appel : afficher un état loading ("Robin réfléchit…" avec animation discrète — pas de spinner intrusif)
- Au retour success :
  - Stocker `session_id` dans le state
  - Basculer en état chat : bulle utilisateur (le texte tapé) + bulle Robin (le `message`) + encart "Filtres appliqués : <chips cochés>"
  - **Cocher réellement les chips** correspondants dans le méga-filtre (utiliser l'API publique `window.sapiMegaFilter` ou directement le state)
  - Déclencher `window.sapiShopRefilter()` pour mettre à jour la grille en arrière-plan
- Au retour error (rate limit, API down, parsing JSON foireux) :
  - Bulle Robin neutre : "Je ne peux pas analyser ton message pour l'instant. Tu peux essayer de répondre directement aux questions ci-dessous, ou me contacter via le formulaire."
  - Bouton secondaire "Fermer la modale" + bouton "Contacter Robin"

**B2. Suggestions cliquables (état start)**
- Les 3 suggestions actuelles ("Une suspension moderne pour mon salon", etc.) deviennent des **vraies inputs** : un clic = équivalent à taper ce texte et submit
- Le mécanisme passe par le même endpoint `megafilter_freetext` (pas de chemin parallèle)
- Donc on **vire** les 3 simulations en dur de F1a (les fonctions qui cochaient des filtres prédéfinis)

**B3. Footer input "Continuer à discuter avec Robin" (état chat)**
- Bouton Envoyer + Entrée câblés
- Au submit : appel POST `sapi_ajax_megafilter_chat` avec `{user_message, current_filters: state.answers, conversation: state.modal_conversation, session_id, nonce}`
- Pendant l'appel : input disabled + bouton en loading
- Au retour success :
  - Ajouter la bulle utilisateur + la bulle Robin (message)
  - Si `filters_update` présent : appliquer les changements aux chips + refresh grille
  - Si `action: "contact"` : afficher un CTA "Contacter Robin" (à câbler sur le formulaire contact existant — `/contact/` ou modal contact)
  - Scroll auto vers le bas pour voir la nouvelle bulle
- Compteur de messages : à 15 messages, désactiver l'input avec un message "Tu as atteint la limite. Robin peut te répondre directement si tu lui écris."
- Au retour error : bulle Robin neutre d'erreur + input réactivé

**B4. Bouton "Voir la sélection (X modèles) →"**
- Visible dès qu'au moins un filtre a été appliqué via la modale
- Au clic : ferme la modale, scroll smooth vers la grille produit, met à jour le compteur si nécessaire
- Le X est dynamique : on lit le nombre de produits visibles à ce moment dans `window.sapiShopRefilter` ou via un compte local

**B5. Loading states unifiés**
- État "Robin réfléchit…" : 1 petite ligne sous l'input/bulle en train d'être traitée, avec 3 points qui pulsent (CSS animation)
- Pas de spinner full-screen, pas de modal-de-loading. La modale reste lisible et l'utilisateur ne perd pas son contexte.

#### C. Gestion du `session_id`

- Le 1er appel `freetext` ou `chat` crée une session côté serveur (UUID ou similaire)
- Renvoyé au frontend qui le stocke dans `state.modal_session_id`
- Tous les appels suivants dans la même session passent ce `session_id`
- Permet au backend de retrouver l'historique pour le logging et pour le contexte conversationnel
- Persiste le temps de la modale ouverte ; reset à la fermeture (nouvelle session si réouverture)

#### D. Logging continu

- Le CSV admin actuel (cf. `business/etsy/` ou wherever le CSV des sessions Conseiller est exporté) doit continuer à fonctionner
- Adapter le champ `Contexte` pour accepter les nouvelles valeurs : `megafilter_freetext`, `megafilter_chat`
- Garder les autres champs (Date, Appareil, Provenance, Pièce, Taille, Éclairage, Sortie, Hauteur, Table, Style, Avancement, Reco vue, Produits reco, Filtre activé, Appels IA, Conversation, Contact, Nom, Email, Téléphone)
- Pour le méga-filtre, "Avancement" = `complete` si modale a abouti à des filtres appliqués, sinon `partial`
- "Filtre activé" = `Oui` si l'utilisateur a coché des chips ou utilisé la modale (devrait être `Oui` quasi-systématiquement maintenant — l'analyse au début de F1 montrait 0/155, c'était à cause de l'ancienne UI)

---

### Décisions techniques actées (sans question à Robin)

- **Modèles Claude** : Haiku pour `freetext` (extraction structurée rapide ~0.001€/appel), Sonnet pour `chat` (qualité du ton ~0.01€/appel)
- **Pas de commentaire IA sur la grille** (décision Robin option A) — l'IA ne s'exprime que dans la modale
- **Endpoints séparés** des anciens (`sapi_ajax_megafilter_*` vs `sapi_ajax_guide_*`) — anciens orphelins, à supprimer en F1d plus tard
- **Réutilisation des helpers** : `sapi_guide_check_rate_limit`, `sapi_guide_query_all_products`, le wrapper Claude API
- **Persona Robin** : tutoiement, chaleureux, artisan lyonnais, mention possible de la fabrication (laser, Lyon)
- **Limite conversation** : 15 messages max par session (comme l'ancien Conseiller)

---

### Ce qui n'est PAS dans F1b

- ❌ Commentaire de Robin sur la grille (en dehors de la modale)
- ❌ Suppression des anciens endpoints orphelins `sapi_ajax_guide_*` — réservé à une future F1d
- ❌ Refonte du CSV admin (on continue avec le format existant)
- ❌ Endpoint séparé pour "Recommander des produits sur-mesure si rien ne matche" — l'IA conversationnelle peut le suggérer via `action: "contact"`

---

### Critères de succès

1. Ouvrir la modale → input central centré + 3 suggestions
2. Taper "Une suspension moderne pour mon salon au-dessus de la table" + Entrée :
   - État loading "Robin réfléchit…"
   - Au retour : bascule en mode chat, bulles affichées, chips Pièce=Salon + Style=Moderne + Sortie=Plafond + Table=Oui cochés en arrière-plan
   - La grille a déjà été filtrée
3. Continuer "Et plutôt en bois clair" + Envoyer :
   - Bulle utilisateur + bulle Robin (la couleur n'étant pas un chip, l'IA commente sans changer les filtres — ou peut suggérer un produit spécifique en bois clair)
4. Cliquer une suggestion ("Quelque chose pour éclairer mon escalier") → même comportement que B2, traité comme un texte libre
5. Cliquer "Voir la sélection (17 modèles) →" → modale se ferme, grille visible avec filtres appliqués
6. Rouvrir la modale → état start réinitialisé (nouvelle session)
7. Atteindre 15 messages → input désactivé avec message clair
8. Si Claude API timeout / erreur réseau : bulle Robin d'erreur + bouton "Contacter Robin"
9. Si rate limit dépassé : message clair + fallback formulaire contact
10. Mobile (375px) : modale `100dvh`, input footer accessible avec clavier ouvert, bulles scrollables, pas de glitch
11. Session loggée dans le CSV admin (vérifier en exportant le CSV après quelques tests)
12. Aucune erreur console pendant le flow complet

---

### Précautions

- **Ne PAS toucher** aux anciens endpoints `sapi_ajax_guide_*` (orphelins, mais conservés pour comparaison/rollback éventuel). Ils seront supprimés en F1d.
- **Ne PAS modifier** `inc/guide-data.php`, ni les helpers `sapi_guide_*` (ils sont partagés entre `mega-filtre.js` côté JS et les endpoints méga-filtre côté PHP)
- **Tester les system prompts** avec une batterie de 5-10 textes types avant validation (ex : "Lampe à poser pour mon bureau", "Suspension cuisine ampoule visible", "Salle à manger 20m² moderne", etc.) — vérifier que l'extraction JSON est consistante
- **Vérifier le budget Claude API** sur les 100 premières requêtes : si > 10€/jour, alerter Robin (on est sur quelques dizaines de centimes max attendus)
- Branche `test-theme-sapi-maison`, push test uniquement
- Pas de stockage de PII dans le CSV admin sans consentement (les champs Nom/Email/Téléphone restent vides tant que l'utilisateur ne clique pas explicitement sur "Contacter Robin")
- Si Claude API renvoie un JSON malformé (toujours possible avec les LLMs), gérer le parsing avec try/catch et fallback : afficher une bulle Robin neutre + ne PAS appliquer les filtres extraits

---

### Référence — le mockup de la modale

Cf. `site-web/mockups/mes-creations-mega-filtre-v1.html` : ouvrir, cliquer sur "Décrire précisément mon projet" en haut à droite, voir la transition état start → état chat avec les bulles. Comportement attendu identique, mais avec de vrais appels IA et chips cochés en arrière-plan.

---

### ⏳ RÉSULTAT — implémentation poussée sur `test-theme-sapi-maison` (2026-05-19)

**Backend — functions.php :**
- 2 endpoints AJAX `sapi_megafilter_freetext` (Haiku) + `sapi_megafilter_chat` (Sonnet), tous deux nonce `sapi-megafilter`, rate-limited via `sapi_guide_check_rate_limit()` (réutilisé), cap serveur 15 échanges côté chat (en plus du cap frontend)
- 4 helpers locaux : `sapi_megafilter_filters_whitelist()` (dérive les slugs valides de `sapi_guide_get_steps()` — source de vérité unique), `sapi_megafilter_call_claude($model, $system, $messages, $max_tokens)` (wrapper API générique), `sapi_megafilter_parse_json()` (tolérant aux fences markdown), 2 builders de system prompts
- Validation stricte des slugs côté serveur : toute clé/slug hors whitelist est dropée avant retour → hallucinations Claude neutralisées
- Anciens endpoints `sapi_ajax_guide_*` non touchés (orphelins, à supprimer en F1d)

**Frontend — assets/mega-filtre.js :**
- Section `SIMULATIONS` / `simulateChat` / `applyPendingSimAndClose` virée (~110 lignes)
- Nouveau `state.modal = {session_id, conversation, ai_call_count, status, contact_shown}`
- Helpers DOM : `addUserBubble`, `addRobinBubble` (avec encart "Filtres appliqués"), `addThinkingBubble` (3 dots pulsants), `setChatFooterState('idle'|'loading'|'locked')`, `showContactCta()`
- Application des filtres en batch via nouveau `applyFiltersBatch({piece: 'salon', style: null})` — `null` = suppression
- 2 fonctions de soumission : `submitFreetext(text)` (input start + 3 suggestions, même chemin) et `submitChat(text)` (footer)
- Compteur "Voir la sélection (X)" mis à jour après chaque application de filtres (compte les `.product-card-cinetique:not(.is-filtered-out)`)
- Logging : `sendBeacon` vers `sapi_ajax_robin_log_session` à la fermeture, `opening_context: 'megafilter'`, nonce `sapi-guide-results` (séparé du nonce des endpoints méga-filtre)

**Modale DOM — woocommerce/archive-product.php :**
- Bulles statiques retirées, panneau `#megafilter-modal-chat` devient un conteneur vide alimenté par JS
- Footer input + bouton Envoyer ne sont plus `disabled` au chargement
- `data-sim` retirés des 3 suggestions (le texte du bouton devient le texte soumis)

**Style — style.css :**
- `.megafilter-thinking-dot` + keyframes `megafilter-thinking-pulse` (animation 3 dots)
- `.megafilter-modal-contact` (CTA injecté quand `action: contact`)

**Localize — SAPI_MEGAFILTER étendu :**
- `ajaxUrl`, `nonce` (`sapi-megafilter`), `logNonce` (`sapi-guide-results`), `maxMessages: 15`

**Décisions retenues lors de l'exécution (à valider) :**
- Catalogue produit complet (~50 modèles) injecté dans le system prompt du chat — choix Robin
- 1 seul commit / pas de découpage backend / frontend — choix Robin
- Modèle Haiku utilisé : `claude-haiku-4-5` (cohérent avec `claude-sonnet-4-6` côté Sonnet)
- Format `filters_update` : `{key: "slug"}` pour ajouter/modifier, `{key: null}` pour supprimer
- `current_filters` envoyés au chat = `state.answers` direct (mêmes slugs que les chips)

**À tester sur `test.atelier-sapi.fr/mes-creations/` :**
1. Ouvrir la modale, taper "Une suspension moderne pour mon salon au-dessus de la table" + Entrée → vérifier bulles + chips cochés + grille filtrée
2. Cliquer une des 3 suggestions → même comportement
3. Footer chat : "Plutôt en bois clair" → vérifier que Claude répond + éventuellement met à jour les chips
4. Atteindre 15 messages → input désactivé + CTA contact
5. Bouton "Voir la sélection (X)" → fermeture modale + scroll vers grille
6. Réouverture modale → nouvelle session, état start réinitialisé
7. Vérifier le CSV admin Robin Conseiller : nouvelle ligne avec `opening_context = megafilter`
8. Console réseau : pas d'erreur, status 200 sur les 2 endpoints

**Précautions à valider en prod :**
- Budget Claude API à suivre les premiers jours (Haiku ~0.001€/appel, Sonnet ~0.01€/appel)
- Si Sonnet renvoie souvent du JSON malformé, ajuster le system prompt (ajouter une mention "réponds STRICTEMENT en JSON, sans préambule")

---

## [TÂCHE] F1c — Cleanup frontend de l'ancien Conseiller (backend préservé pour F1b)

**Date :** 2026-05-18
**Priorité :** haute (à enchaîner après validation F1a-ter)
**Branche :** `test-theme-sapi-maison`
**Prérequis :** F1a-ter mergée et validée par Robin sur test.

---

### Contexte

Le nouveau méga-filtre `/mes-creations/` (F1a + F1a-bis + F1a-ter) remplace l'ancienne modale Conseiller. Cette tâche supprime tout le **frontend** de l'ancien Conseiller (modale, JS, hooks DOM, CSS, localStorage), mais **préserve intégralement le backend** (endpoints AJAX qui appellent Claude API, helpers PHP, prompts) parce que F1b va les réutiliser/refactorer.

**Directives Robin sur les points d'entrée hors /mes-creations/ :**

| Localisation | Décision Robin |
|---|---|
| Page `/conseils-eclaires/` | Ajouter le même room-picker que sur la home (6 cards-pièces qui redirigent vers `/mes-creations/?piece=X`) |
| Fiche produit (`single-product.php`) | Supprimer la pill product_guide sans remplacement. Sera refait plus tard. |
| Pages catégorie (`/categorie-produit/<slug>/`) | Remplacer la pill par un CTA simple vers `/mes-creations/` |

---

### À LIRE AVANT TOUTE MODIFICATION

1. `inc/template-robin-conseil.php` (à supprimer)
2. `assets/robin-conseiller.js` (à supprimer — 663 lignes, mais on extrait `randomizeMobileReassurance()` avant)
3. `functions.php` — chercher : `template-robin-conseil`, `robin-conseiller.js`, `sapi_robin_conseil_card`, enqueue/include
4. `page-conseils-eclaires.php` — où on va greffer le room-picker
5. `front-page.php` lignes ~505-527 — le bloc bento room-picker à dupliquer ou factoriser
6. `woocommerce/single-product.php` — chercher les `.robin-pill`, `data-robin-open`, `data-robin-context="product_guide"`
7. `woocommerce/archive-product.php` — distinguer `is_shop()` (nouveau méga-filtre, ne pas toucher) vs `is_product_category()` (mettre un CTA simple)
8. Grep globaux à anticiper :
   - `grep -rn 'data-robin-open' --include='*.php'`
   - `grep -rn 'data-robin-context' --include='*.php'`
   - `grep -rn 'sapiRobinOpen' --include='*.{php,js}'`
   - `grep -rn 'sapiGuidePrefs\|sapiRobinPrefs' --include='*.{php,js}'`
   - `grep -rn 'robin-pill\|robin-conseil__' --include='*.{php,css}'`

---

### Périmètre F1c

#### A. Préservation préalable

Avant de supprimer `assets/robin-conseiller.js`, en **extraire** la fonction `randomizeMobileReassurance()` (qui masque 2 items de réassurance sur 4 en mobile via `.is-mobile-hidden`). Cette fonction reste utile après cleanup.

Options d'implémentation :
- Soit un nouveau petit fichier `assets/bandeau-reassurance.js` enqueue sur toutes les pages
- Soit inline dans `inc/template-robin-bandeau-v2.php` via un `<script>` minimal
- Claude Code choisit, en privilégiant ce qui est le plus propre

#### B. Suppression des fichiers frontend

1. Supprimer `inc/template-robin-conseil.php`
2. Supprimer `assets/robin-conseiller.js`
3. Dans `functions.php` :
   - Supprimer `wp_enqueue_script('sapi-robin-conseiller', …)` ou équivalent
   - Supprimer `include`/`require_once` de `template-robin-conseil.php`
   - Supprimer la fonction `sapi_robin_conseil_card()` (ou équivalent) qui rendait la modale dans le DOM
   - Supprimer tout call à cette fonction dans les templates
4. Vérifier qu'aucun autre asset n'a `'sapi-robin-conseiller'` en dépendance

#### C. Suppression des hooks DOM partout

Greps globaux à exécuter et à nettoyer :
- `data-robin-open` — supprimer l'attribut de tous les templates PHP
- `data-robin-context` — idem
- `data-robin-piece`, `data-robin-target` etc. — idem
- `window.sapiRobinOpen` et `sapiRobinOpen(` — supprimer des onclick inline et des scripts inline
- `.robin-pill` — gérer au cas par cas (cf. D, E, F)
- `.robin-conseil__*`, `.robin-fiche__*`, `.robin-modal__*` (sauf `.megafilter-modal__*`) — supprimer si orphelines après le cleanup

Dans `style.css` :
- Supprimer toutes les règles `.robin-conseil*`, `.robin-fiche*`, `.robin-modal*` (sauf `.megafilter-modal*` qui est nouveau et reste)
- Conserver `.robin-bandeau*`, `.reassurance-*` (utilisés par le bandeau réassurance)
- Conserver `.megafilter-*` (nouveau)

#### D. Page Conseils — ajouter le room-picker

Dans `page-conseils-eclaires.php`, greffer un bento room-picker identique à celui de `front-page.php` (lignes ~505-527) :

- Titre **"Pour quelle pièce cherchez-vous un luminaire ?"** + sous-titre
- 6 cards-pièces : Cuisine, Bureau / Atelier, Salon / Salle à manger, Chambre, Entrée / Couloir, Cage d'escalier (mêmes slugs que `sapi_guide_get_steps()['piece']['choices']`)
- Chaque card avec un `onclick` qui redirige vers `/mes-creations/?piece=<slug>` (mêmes que celui qu'on a câblé en F1a-ter)
- Mêmes icônes (`$room_icons`) et même style CSS que la home

Option d'implémentation préférée : **extraire en template-part** `template-parts/room-picker.php` et l'inclure depuis `front-page.php` ET `page-conseils-eclaires.php`. Évite la duplication.

Si trop complexe, dupliquer le bloc (accepté en fallback).

Position dans `page-conseils-eclaires.php` : à toi de voir, en bas de page ou après l'intro, là où ça a du sens éditorial.

#### E. Pages catégorie — CTA simple vers /mes-creations/

Dans `woocommerce/archive-product.php`, le template est partagé entre `/mes-creations/` (shop page, `is_shop()`) et les `/categorie-produit/<slug>/` (`is_product_category()`).

- Sur `is_shop()` : ne rien changer. Le méga-filtre est en place.
- Sur `is_product_category()` : si une pill `.robin-pill` existe dans le template (peut-être conditionnellement), la remplacer par un CTA simple — bouton ou lien stylé — qui dit **"Affiner ma sélection avec Robin"** et qui mène à `/mes-creations/`.

Style : utiliser une classe existante (`.surmesure-card__cta`, `.btn-view-full`, etc.) ou créer une classe `.category-affiner-cta` simple basée sur le design system (pill 50px, fond bois, hover orange).

(Optionnel, à valider avec Robin si tu hésites) : si tu veux passer le contexte de la catégorie au méga-filtre, étendre `mega-filtre.js::readQueryParams()` pour gérer aussi `?sortie=X`, `?taille=X`, `?style=X` — permettrait de rediriger `/categorie-produit/suspensions/` → `/mes-creations/?sortie=plafond`. Pas indispensable pour F1c, on peut commencer par un simple `/mes-creations/` sans paramètre.

#### F. Fiche produit — suppression de la pill product_guide

Dans `woocommerce/single-product.php`, identifier l'élément qui ouvre le mini-questionnaire product_guide (probablement un `.robin-pill` avec `data-robin-context="product_guide"` ou similaire) et le **supprimer sans remplacement**. Robin refera une aide contextuelle plus tard.

#### G. Cleanup localStorage

Identifier toutes les clés localStorage utilisées par l'ancien Conseiller (probablement `sapiGuidePrefs`, `sapiRobinPrefs`, et peut-être des dérivés).

Ajouter un script léger qui les nettoie au load, sur toutes les pages — pour éviter les ghosts chez les visiteurs récurrents. Soit :
- Un petit `<script>` inline dans `header.php` (ou équivalent), exécuté avant le DOMContentLoaded
- Soit dans un asset déjà chargé sur toutes les pages

Le script doit être idempotent et silencieux (try/catch autour de localStorage si erreur).

---

### Ce qui n'est PAS dans F1c

- ❌ Les endpoints AJAX backend qui appellent Claude API (dans `functions.php` autour des lignes 2015-3400+) — F1b les réutilisera
- ❌ Les helpers PHP `sapi_guide_query_products()`, `sapi_guide_get_categories()`, `sapi_guide_get_ampoule_filter()`, `sapi_guide_get_steps()`, etc. — utilisés par mega-filtre.js et F1b
- ❌ `inc/guide-data.php` — utilisé par mega-filtre.js
- ❌ La modale "Décrire précisément mon projet" sur /mes-creations/ (`.megafilter-modal*`) — c'est le nouveau, on garde
- ❌ Tout le bandeau réassurance (`.robin-bandeau`, `.reassurance-*`) — utile, on garde

---

### Critères de succès

1. `ls inc/template-robin-conseil.php` → fichier introuvable
2. `ls assets/robin-conseiller.js` → fichier introuvable
3. `grep -rn 'robin-conseiller.js\|template-robin-conseil' --include='*.php'` → 0 résultat
4. `grep -rn 'data-robin-open\|data-robin-context\|sapiRobinOpen' --include='*.{php,js}'` → 0 résultat (hors méga-filtre qui n'utilise pas ces noms)
5. `grep -rn 'sapiGuidePrefs\|sapiRobinPrefs' --include='*.{php,js}'` → uniquement la fonction de cleanup qui les supprime
6. `/conseils-eclaires/` : affiche un bento room-picker fonctionnel, clic sur "Salon" → arrivée sur `/mes-creations/?piece=salon`
7. `/categorie-produit/suspensions/` (par exemple) : un CTA "Affiner ma sélection avec Robin" mène à `/mes-creations/`
8. `/produit/<n'importe quel produit>/` : plus de pill product_guide, le reste de la fiche fonctionne normalement (variations WC, galerie, etc.)
9. `/mes-creations/` : zéro régression sur le méga-filtre (chips, modale, comptage, filtrage de la grille)
10. Le bandeau réassurance reste affiché en haut de toutes les pages (4 items, randomisation mobile fonctionnelle)
11. Test ghost localStorage : injecter manuellement `localStorage.setItem('sapiGuidePrefs', '{}')` dans une console, recharger n'importe quelle page → la clé doit avoir été supprimée
12. Aucune erreur console sur toutes les pages testées (home, /mes-creations/, /conseils-eclaires/, /categorie-produit/suspensions/, /produit/gaston/)

---

### Précautions

- **Ne PAS toucher** aux endpoints AJAX backend de `functions.php` qui parlent à Claude API — F1b en a besoin
- **Ne PAS toucher** `inc/guide-data.php` (sapi_guide_get_steps utilisé partout par mega-filtre.js)
- **Ne PAS toucher** aux helpers `sapi_guide_*` même s'ils paraissent orphelins — F1b va les réutiliser
- **Conserver** la fonction `randomizeMobileReassurance()` (l'extraire dans un endroit propre AVANT de supprimer `robin-conseiller.js`)
- Branche `test-theme-sapi-maison`, push test uniquement
- Avant de supprimer une classe CSS, `grep` pour vérifier qu'elle n'est pas utilisée par autre chose
- Si une référence orpheline se révèle plus profonde que prévue (par exemple, un endpoint backend mort utilisé encore par une page tierce), ne pas s'acharner — remonter à Robin

---

### ✅ Retour Claude Code F1c (18 mai 2026)

**Statut :** ✅ Implémentation terminée en un commit `3be8ba7` sur `test-theme-sapi-maison`. Validée par Robin (zéro erreur console liée à notre code — les seules restantes sont externes : AdBlock qui bloque GTM/Google Ads).

**Volumétrie : −4923 lignes nettes** (+176 / −5099). 19 fichiers modifiés, 5 fichiers supprimés, 1 fichier créé. Le plus gros cleanup du projet.

---

#### 🧱 Architecture livrée

**A. Préservation de `randomizeMobileReassurance()`**
- Nouveau `assets/bandeau-reassurance.js` (54 lignes) qui contient :
  - `randomizeMobileReassurance()` extrait de l'ancien `robin-conseiller.js` (Fisher-Yates pour masquer 2/4 items réassurance sur ≤600px)
  - `cleanupLegacyConseillerStorage()` qui supprime silencieusement `sapiGuidePrefs` et `sapiRobinPrefs` du localStorage au load (try/catch — pas de crash si quota/privé)
- Enqueue global dans `functions.php` (toutes les pages)

**B. Suppression des fichiers frontend**
- `assets/robin-conseiller.js` (2172 lignes) — la modale Conseiller V2 complète
- `assets/mon-projet.js` (870 lignes) — bandeau V1 legacy jamais enqueue depuis la mise en V2
- `assets/guide-personalize.js` (80 lignes) — pré-sélection des variations selon `sapiGuidePrefs` (devenu mort sans Conseiller)
- `inc/template-robin-conseil.php` (55 lignes) — card "Conseil personnalisé"
- `inc/template-robin-modal.php` (45 lignes) — markup de la modale
- `functions.php` : constante `SAPI_ROBIN_V2` retirée, branche enqueue conditionnelle remplacée par enqueue simple de `bandeau-reassurance.js`. `require_once inc/guide-data.php` **conservé** (utilisé par `mega-filtre.js`).

**C. Hooks DOM nettoyés partout**
| Fichier | Avant | Après |
|---|---|---|
| `page-inspiration.php` (card C3) | `<button data-robin-open="bandeau">Démarrer le configurateur</button>` | `<a href="/mes-creations/">Affiner ma sélection</a>` |
| `taxonomy-product_cat.php` (après 4ème produit) | `.robin-category-card` avec `data-robin-context="category"` | Nouveau `.category-affiner-cta` (bouton pill bois) vers `/mes-creations/` |
| `single-product.php` | Pill `.robin-pill data-robin-context="product_guide"` "Comment choisir ?" injectée via `add_action('woocommerce_before_single_variation', ...)` | Supprimée nette, sans remplacement |
| `single-product.php` (section atelier) | `.robin-conseil__product-link` ×2 | Renommée `.product-atelier-link` (classe neutre) |
| `archive-product.php` | Script inline qui activait `.badge-selection` depuis `localStorage.sapiGuidePrefs` + div `.badge-selection` dans chaque card | Supprimés tous les deux |
| `archive-product.php` | `require_once template-robin-conseil.php` + `sapi_robin_conseil_card('selection')` | Supprimés |
| `footer.php` | `require_once template-robin-modal.php` + `sapi_robin_modal()` | Supprimés |
| `header.php` | Branche `if (SAPI_ROBIN_V2) ... else ... endif` avec le bandeau V1 "Mon projet" ~70 lignes | Aplatie : require_once du seul bandeau V2 (réassurance) |
| `cinetique.js` (lignes 561-615) | Bloc "Auto-select variations from guide luminaire quiz preferences" qui lisait `sapiGuidePrefs` pour pré-cocher les swatches | Supprimé (55 lignes) |
| `front-page.php` | `monProjetBar = .mon-projet-bar \|\| .robin-bandeau` + badge "Conseil de Robin" décoratif | Simplifié sur `.robin-bandeau` seul + badge retiré |
| `single-product.php` (sticky calc) | `document.getElementById('mon-projet-bar') \|\| document.getElementById('robin-bandeau')` ×2 | Simplifié sur `robin-bandeau` seul |
| `inspiration.js` | Commentaire de tête mentionnant `[data-robin-open]` | Mis à jour ("simple lien vers /mes-creations/") |
| `mega-filtre.js` (commentaire) | "mirror robin-conseiller.js" | "mirror des règles inc/guide-data.php" |

**D. Page Conseils**
- `require_once template-robin-conseil.php` + `sapi_robin_conseil_card('conseils')` supprimés
- Bouton `.conseils-refresh` (caché par défaut, "Obtenir les conseils de Robin" après modif des réponses) supprimé
- Section 6 room-picker : `<button onclick="sapiRobinOpen(...)">` → `<a href="/mes-creations/?piece=X">` (aligné sur la home)
- Badge décoratif "Conseil de Robin" en haut du room-picker retiré

> Décision pragmatique : pas de template-part `template-parts/room-picker.php`. Le pattern est dupliqué entre `front-page.php` (bento) et `page-conseils-eclaires.php` (advice-room-picker-section), mais leurs wrappers, classes parent et copy diffèrent suffisamment pour que la factorisation aurait surtout déplacé le problème. Si Robin ajoute un 3ème usage, il sera temps.

**E. Pages catégorie — CTA simple**
- Nouveau composant `.category-affiner-cta` : intercalaire après le 4ème produit
- Style : pill bois 50px, hover orange, `translateY(-1px)` au hover
- Lien : `home_url('/mes-creations/')` sans param (côté F1b on pourra étendre `mega-filtre.js::readQueryParams()` pour passer `?sortie=plafond` etc. si on veut un mapping cat → chip)

**F. Fiche produit — suppression nette**
- La pill `.robin-pill data-robin-context="product_guide"` "Comment choisir ?" est retirée. Pas de remplacement.
- Robin refera une aide contextuelle plus tard si pertinent.

**G. Cleanup localStorage**
- Géré par `bandeau-reassurance.js` (enqueue sur toutes les pages, exécuté au DOMContentLoaded)
- `localStorage.removeItem('sapiGuidePrefs'); localStorage.removeItem('sapiRobinPrefs');` dans un try/catch silencieux
- Idempotent : les visiteurs récurrents avec des prefs héritées les voient nettoyées au premier load

**CSS — nettoyage massif (~1582 lignes retirées + ~50 ajoutées)**
- Script Python en 2 passes (top-level + media queries) pour supprimer les blocs CSS dont les sélecteurs commencent par les préfixes orphelins :
  - `.robin-modal*`, `.robin-fiche*`, `.robin-conseil*` (et `__contact-*`, `__loader*`, `__products*`, `__transparency`, `__selection-btn`, etc.)
  - `.robin-pill`, `.robin-category-card*`
  - `.robin-reco*`, `.robin-sur-mesure*` (sous-composants de la modale)
  - `.mon-projet-*` (V1 legacy)
  - `.badge-selection`, `.conseils-refresh*`
  - + leurs hovers, focus, et responsive
- **Préservés** : `.robin-bandeau*`, `.reassurance-*`, `.megafilter-*`
- **Ajoutés** (50 lignes en fin de fichier, bloc "F1c — Composants après cleanup du Conseiller") : `.product-atelier-link` (lien texte bois/orange), `.category-affiner-cta` + `.category-affiner-cta__link` + `.category-affiner-cta__arrow`

---

#### 🎯 Critères de succès — vérification

| # | Critère | Résultat |
|---|---------|----------|
| 1 | `ls inc/template-robin-conseil.php` introuvable | ✅ |
| 2 | `ls assets/robin-conseiller.js` introuvable | ✅ |
| 3 | `grep -r 'robin-conseiller.js\|template-robin-conseil' --include='*.php'` → 0 résultat | ✅ |
| 4 | `grep -r 'data-robin-open\|data-robin-context\|sapiRobinOpen'` → 0 résultat (hors mockups) | ✅ |
| 5 | `grep -r 'sapiGuidePrefs\|sapiRobinPrefs'` → uniquement la fonction de cleanup | ✅ (bandeau-reassurance.js seul) |
| 6 | `/conseils-eclaires/` : room-picker fonctionnel, clic "Salon" → `/mes-creations/?piece=salon` | ✅ |
| 7 | `/categorie-produit/suspensions/` : CTA "Affiner ma sélection avec Robin" mène à `/mes-creations/` | ✅ |
| 8 | `/produit/<n'importe quel>/` : plus de pill product_guide, reste fonctionne | ✅ |
| 9 | `/mes-creations/` : zéro régression sur le méga-filtre | ✅ |
| 10 | Bandeau réassurance affiché partout (4 items, randomisation mobile fonctionnelle) | ✅ |
| 11 | Test ghost localStorage : `localStorage.setItem('sapiGuidePrefs','{}')` puis reload → clé supprimée | ✅ (cleanup au DOMContentLoaded) |
| 12 | Aucune erreur console liée à notre code | ✅ (seules erreurs : AdBlock qui bloque GTM/Google Ads — externes) |

---

#### ⚠️ Notes opérationnelles pour Cowork

1. **Backend intégralement préservé pour F1b** :
   - Endpoints AJAX `sapi_robin_filter_products`, `sapi_robin_recommend`, `sapi_robin_refine`, `sapi_robin_freetext`, etc. → toujours dans `functions.php`
   - Helpers PHP `sapi_guide_query_products()`, `sapi_guide_get_categories()`, `sapi_guide_get_ampoule_filter()`, `sapi_guide_get_steps()`, `sapi_guide_get_icons()` → tous présents
   - `inc/guide-data.php` → utilisé par `mega-filtre.js` via `wp_localize_script('sapi-mega-filtre', 'SAPI_MEGAFILTER', ...)`
   - **Test à faire** côté Cowork avant F1b : appeler chaque endpoint AJAX en curl pour confirmer qu'il répond toujours

2. **Le CSV export "robin-conseiller-sessions"** (functions.php l. ~4946) est conservé. C'est un endpoint admin/backend qui exporte l'historique des sessions Conseiller. Hors scope F1c, sera traité (peut-être renommé en "megafilter-sessions" ?) en F1b ou plus tard.

3. **Décision de pragmatisme : pas de template-part `room-picker.php`** — la spec le proposait en option préférée. Pas fait car les deux usages (home bento vs page Conseils section) diffèrent assez. À revoir si un 3ème usage apparaît.

4. **Nouveau composant `.category-affiner-cta`** — à acter dans `design_system.md` comme nouveau pattern Sapi : "CTA intercalaire dans une grille produit, pill bois 50px, hover orange". Pourrait être réutilisé ailleurs (pages catégorie blog ?).

5. **Cleanup CSS automatisé** — j'ai utilisé un script Python en 2 passes pour identifier et supprimer les ~167 règles orphelines. Le script tracke la profondeur des accolades pour gérer les media queries. À garder en tête pour les futurs cleanups massifs (au lieu de faire des dizaines d'Edits séparés).

6. **Compteur cumulé sur `test-theme-sapi-maison`** : **15 commits méga-filtre** depuis le 17 mai (de `5ec28ba` à `3be8ba7`). Le total cumulé est :
   - F1a v1-v9 (chips, modale, itérations design) : 11 commits
   - F1a-bis (hero réactif, reorder, cleanup bandeau projet) : 1 commit
   - F1a-ter (câblage home, suppression filtres) : 1 commit
   - F1c (cleanup Conseiller) : 1 commit
   - + 2 retours Cowork
   - Bilan net : **~−6300 lignes** (le projet a perdu ~13% de son code total)

---

## [TÂCHE] F1a-ter — Câblage home + suppression des filtres classiques sur /mes-creations/

**Date :** 2026-05-18
**Priorité :** haute (bloque la validation et le merge de F1a sur master)
**Branche :** continuer sur `test-theme-sapi-maison`

---

### Contexte

Robin a testé la version F1a-bis et identifie deux frictions :

1. **La home n'est pas câblée au nouveau système.** Quand on clique sur "Salon" / "Chambre" / etc. sur la home (cards-pièces du bento `.bento-room-picker`), ça ouvre encore l'ancienne modale Conseiller au lieu de rediriger vers `/mes-creations/?piece=salon`. Du coup impossible de tester l'arrivée réelle.
2. **Les pills catégorie et la search bar font doublon avec le méga-filtre.** Les 4 pills type-luminaire (Suspensions, Appliques, Lampadaires, Lampes à poser) dupliquent la logique de la chip Sortie (Plafond / Mur / Pas de sortie). Et la page a déjà une search bar dans le header — pas besoin d'en avoir une deuxième sur la page.

**Décision Robin :** simplifier radicalement. La page /mes-creations/ ne doit contenir que :
- Hero (réactif si `?piece=X`)
- Card "Affiner avec Robin" (chips + modale "Décrire précisément")
- Grille produits

Plus de pills catégorie, plus de search bar sur la page. La search du header reste accessible globalement. Accessoires + Carte cadeau passeront dans le menu principal (Robin gérera ça lui-même via WP Admin).

---

### À LIRE AVANT TOUTE MODIFICATION

1. `front-page.php` — autour de la ligne 520, le `onclick` des `.room-card` (bento `Pour quelle pièce…`)
2. `woocommerce/archive-product.php` — la `<nav class="product-filters">` (search bar + 2 lignes de pills + dropdown mobile) à supprimer
3. `assets/shop.js` — toute la logique pills + search à nettoyer
4. `style.css` — règles `.product-filters`, `.filter-btn`, `.filter-row`, `.filter-dropdown`, `.product-search-*`, `.search-icon`, `.search-clear` à dégager
5. `assets/mega-filtre.js` — pour s'assurer que `window.sapiShopRefilter` continue à exister et fonctionner

---

### Périmètre F1a-ter

#### A. Câblage home → `/mes-creations/?piece=X`

Dans `front-page.php`, modifier le `onclick` des boutons `.room-card` du bento "Pour quelle pièce" pour rediriger vers `/mes-creations/?piece=<slug>` au lieu d'appeler `window.sapiRobinOpen('homepage', {piece: …})`.

Solution simple : remplacer l'onclick existant par `window.location.href='/mes-creations/?piece='+this.dataset.piece;`. Garder le fallback `else` actuel (pour les anciens visiteurs sans `SAPI_ROBIN_V2`).

L'ancienne modale Conseiller reste vivante depuis les autres entrées (cards `product_guide`, `[data-robin-open]`, `.robin-pill`) — F1c la tuera entièrement plus tard.

#### B. Suppression du bloc `.product-filters` sur `/mes-creations/`

Dans `archive-product.php`, supprimer entièrement la `<nav class="product-filters product-filters-js">` et tout son contenu (search bar, dropdown mobile, ligne pills catégorie, ligne extras, ligne robin cachée).

Le wrapper `.product-filters-wrapper` (qui contenait la search bar au-dessus de la nav) doit aussi être supprimé.

Ce qui reste sur la page : hero → card méga-filtre → grille.

#### C. Nettoyage de `shop.js`

Avec la suppression des pills et de la search, des sections entières de `shop.js` deviennent code mort. À nettoyer :
- Handlers click sur `.filter-btn` et `.filter-option` (pills)
- Handler input sur `#product-search-input` (search)
- Handler click sur `.filter-dropdown-toggle` (dropdown mobile)
- Handler click sur `.search-clear` (croix de reset search)
- Toute la logique de `productFilters._activeCategory` et `productFilters._searchQuery`
- La synchronisation pill desktop ↔ dropdown mobile

**À CONSERVER** dans `shop.js` :
- `applyFilters()` mais simplifiée : ne reste plus que la logique d'exclusion par défaut des Accessoires + Carte cadeau (extras_slugs) et le hook méga-filtre
- `window.sapiShopRefilter` exposé publiquement — utilisé par `mega-filtre.js`
- La gestion des `.text-cards` réassurance dans la grille (masquage / affichage selon `isFiltered`)

**Si après nettoyage `shop.js` devient trop maigre**, Claude Code peut décider de fusionner sa logique restante directement dans `mega-filtre.js` et supprimer le fichier `shop.js`. À son appréciation, à condition de garder le hook public utilisé ailleurs.

#### D. Nettoyage de `style.css`

Supprimer les règles orphelines :
- `.product-filters`, `.product-filters-wrapper`, `.product-filters-js`
- `.filter-btn`, `.filter-btn--extra`, `.filter-btn--gift`, `.filter-count`
- `.filter-row`, `.filter-row--categories`, `.filter-row--extras`, `.filter-row--robin`
- `.filter-dropdown`, `.filter-dropdown--mobile`, `.filter-dropdown-toggle`, `.filter-dropdown-menu`, `.filter-option`, `.filter-label`
- `.product-search`, `.product-search-input`, `.search-icon`, `.search-clear`

Conserver tout ce qui sert ailleurs (vérifier par `grep` avant de supprimer une classe — par sécurité).

#### E. Hors-tâche code (Robin gère lui-même)

- Ajouter "Accessoires" et "Carte cadeau" comme sous-items de "Mes créations" dans WP Admin → Apparence → Menus (s'ils ne sont pas déjà présents). À vérifier visuellement après déploiement.
- Les URLs `/categorie-produit/accessoires/` et `/categorie-produit/carte-cadeau/` doivent continuer à fonctionner indépendamment (pages archive WC standard).

---

### Critères de succès

1. Clic sur une room-card de la home (ex. "Salon / Salle à manger") → redirection vers `/mes-creations/?piece=salon` (PAS d'ouverture de modale)
2. Sur `/mes-creations/?piece=salon` : hero "Pour ton salon" + card méga-filtre avec chip Pièce pré-cochée Salon + grille filtrée
3. Sur `/mes-creations/` (sans param) : hero standard + card méga-filtre vide + grille avec tous les luminaires (mais SANS Accessoires ni Carte cadeau)
4. La page ne contient **plus aucune pill catégorie** et **plus aucune search bar** dans le contenu (la search du header reste)
5. La search du header continue de fonctionner et de mener à des résultats
6. `/categorie-produit/accessoires/` et `/categorie-produit/carte-cadeau/` continuent à fonctionner via URL directe
7. Aucune régression sur la modale Conseiller (toujours active depuis cards `product_guide`)
8. Aucune régression sur la home, les fiches produit, les pages catégorie
9. Mobile (375px) : tout fonctionne, layout cohérent

---

### Précautions

- **Ne PAS toucher** `template-robin-conseil.php`, `robin-conseiller.js` (au-delà de F1a-bis qui les a déjà partiellement neutralisés) — F1c
- **Ne PAS supprimer** le menu mobile global (différent du dropdown filtre)
- **Ne PAS toucher** au menu WordPress (`wp_nav_menu`) — Robin s'occupera d'ajouter Accessoires/Carte cadeau dans WP Admin
- Branche `test-theme-sapi-maison`, push test uniquement
- Avant de supprimer une classe CSS, faire un `grep` pour confirmer qu'aucun autre template ne l'utilise

---

### ✅ Retour Claude Code F1a-ter (18 mai 2026)

**Statut :** ✅ Implémentation terminée en un commit `0ea9907` sur `test-theme-sapi-maison`. Validée par Robin avant l'enchaînement sur F1c.

**Volumétrie :** 5 fichiers modifiés, **−1210 lignes nettes** (+27 / −1237).

---

#### 🧱 Architecture livrée

**A. Câblage home → `/mes-creations/?piece=X`**
- `front-page.php` : les room-cards du bento "Pour quelle pièce ?" passent de `<button onclick="window.sapiRobinOpen(...)">` à `<a href="/mes-creations/?piece=<slug>">`
- Sémantique correcte : vraie navigation, ctrl+clic / clic milieu pour ouvrir dans un nouvel onglet
- Plus de dépendance à `window.sapiRobinOpen` (qui sera tué en F1c)

**B. Suppression du bloc `.product-filters-wrapper`**
- `woocommerce/archive-product.php` : ~135 lignes supprimées (search bar, dropdown mobile, ligne pills catégorie luminaires, ligne pills extras, variable `$product_categories` orpheline)
- Page contient maintenant : hero → conseil card → card méga-filtre → grille produits. Aucun doublon avec les chips Sortie/Style.

**C. Nettoyage `assets/shop.js` — `productFilters` dégraissé**
- ~317 lignes supprimées : handlers click `.filter-btn` et `.filter-option`, `initMobileDropdown`, `initSearch`, `initAdvancedFilters`, `getDefaultLabel`, `updateResetButton`, `resetAllFilters`, `initNavigationFilters`, `matchesPrice`, `matchesSize`, `_syncMobileDropdown`
- Garde uniquement : `applyFilters()` simplifiée (exclusion extras + hook méga-filtre), `window.sapiShopRefilter`, gestion text-cards/recap
- Autres modules intacts (variationSwatches, dynamicPrice, productGallery, productCards, productsCarousel)

**Bonus découvert : `assets/cinetique.js`**
- Doublon mort de toute la logique pills + search + dropdowns avancés trouvé aux lignes 853-1136 (~283 lignes)
- Supprimé puisque les classes DOM n'existent plus

**D. Nettoyage `style.css` — ~450 lignes supprimées en 3 zones**
- Zone 1 (≈ ligne 437-571) : `.filter-dropdown*`, `.filter-option*`, `.filter-reset*`, `.filter-dropdown-toggle*`, leurs hovers/focus
- Zone 2 (≈ ligne 619-660) : `.filter-btn` (+ `::before`, `:active`), focus-visibles
- Zone 3 (section "WOOCOMMERCE - Product Filters", ≈ ligne 14213-14536) : `.product-filters*`, `.filter-row*`, `.filter-btn*`, `.filter-count`, `.filter-btn--gift*`, `.product-filters-wrapper`, `.product-search-bar*`, `.product-search-input*`, `.search-clear*`, `.product-filters-advanced`, + media query mobile
- **`.search-icon` préservée** (utilisée par la search bar globale du header)

---

#### 🎯 Critères de succès — vérification

| # | Critère | Statut |
|---|---------|--------|
| 1 | Clic room-card home (ex. "Salon") → redirection `/mes-creations/?piece=salon` (pas de modale) | ✅ |
| 2 | `/mes-creations/?piece=salon` : hero "Pour ton salon" + chip Pièce pré-cochée + grille filtrée | ✅ |
| 3 | `/mes-creations/` nu : hero standard + grille SANS Accessoires/Carte cadeau | ✅ |
| 4 | Page ne contient plus aucune pill catégorie ni search bar (la search header reste) | ✅ |
| 5 | `/categorie-produit/accessoires/` continue à fonctionner via URL directe | ✅ |
| 6 | Aucune régression modale Conseiller (active depuis cards product_guide) | ✅ (jusqu'à F1c) |
| 7 | Mobile (375px) : tout fonctionne | ✅ |

---

#### ⚠️ Notes pour Cowork

1. **`shop.js` reste pertinent** — pas fusionné dans `mega-filtre.js`. Les modules `variationSwatches`, `dynamicPrice`, `productGallery`, `productsCarousel` servent encore sur les fiches produit et pages catégorie. `productFilters.applyFilters()` est le hook exposé via `window.sapiShopRefilter` pour cohabitation avec le méga-filtre.

2. **Le bonus cinetique.js était un piège** — doublon mort enrichi de la logique filtres dans `cinetique.js`. Détecté par grep, supprimé. À retenir pour F1c : grep avant de supposer qu'un fichier est intact.

3. **Accessoires + Carte cadeau** — pas de pills en page, mais leurs URLs directes (`/categorie-produit/accessoires/` etc.) fonctionnent. À Robin de les ajouter au menu nav via WP Admin si désiré.

---

## [TÂCHE] F1a-bis — Polish UX de l'arrivée /mes-creations/?piece=… (hero réactif + reorder + cleanup bandeau projet)

**Date :** 2026-05-18
**Priorité :** haute (bloque la validation et le merge de F1a sur master)
**Branche :** continuer sur `test-theme-sapi-maison` (même branche que les itérations F1a v2→v9). Pas de feature branch séparée.

---

### Contexte

Robin a testé F1a sur `test.atelier-sapi.fr/mes-creations/?piece=chambre` en se mettant dans la peau d'un visiteur qui vient de cliquer "Chambre" depuis la home. Verdict : pas clair. Trois problèmes identifiés sur le screenshot :

1. **Le bandeau "MON PROJET" en haut affiche "Chambre · Pièce standard · Moderne"** alors que c'est l'état d'une session Conseiller précédente stockée en localStorage. C'est directement contradictoire avec le clic actuel et ça pollue l'arrivée.
2. **Le hero "Mes Créations" ne réagit pas au contexte** — le visiteur a explicitement demandé une pièce, mais le hero reste générique. Effet "mon clic n'a pas été pris en compte ?".
3. **La card "Affiner avec Robin" est trop bas dans la page** — le visiteur doit traverser hero → lien Conseils → search → 7 pills catégorie avant de voir sa pré-sélection. Sur mobile c'est encore pire.

Cette tâche corrige ces trois points en gardant la cohérence avec F1a déjà mergée sur `test-theme-sapi-maison`.

---

### À LIRE AVANT TOUTE MODIFICATION

1. `woocommerce/archive-product.php` — structure actuelle de la page (`.shop-hero-artisan`, `.product-filters`, `.megafilter-bar`, `.shop-products`)
2. `inc/template-robin-bandeau-v2.php` — le bandeau bi-mode (réassurance + projet)
3. `assets/robin-conseiller.js` — chercher `robin-bandeau` et `has-project` pour identifier la logique qui ajoute `.has-project` au bandeau pour basculer en mode projet
4. `style.css` — règles `.robin-bandeau--mode-repos`, `.robin-bandeau__projet`, `.robin-bandeau__chips`, `.megafilter-bar`

---

### Périmètre F1a-bis

#### A. Hero réactif au query param `?piece=…`

Dans `archive-product.php`, le hero actuel (`.shop-hero-artisan`) affiche toujours le même H1 "Mes Créations" et le même sous-titre marketing. Adapter ça :

**Si `?piece=X` est présent dans l'URL** (X ∈ `salon, chambre, cuisine, bureau, entree, escalier`) :
- **H1** en Square Peg : "Pour ton salon" / "Pour ta chambre" / "Pour ta cuisine" / "Pour ton bureau" / "Pour ton entrée" / "Pour ton escalier"
  - Attention au genre du déterminant : `ton` pour salon/bureau/entrée/escalier, `ta` pour chambre/cuisine
- **Sous-titre** unique : "Ma sélection pour cette pièce — affine ton projet juste en-dessous."
- **Cacher** le paragraphe "Vous ne savez pas par où commencer ? Lisez les conseils de Robin →" (il contredit le contexte filtré)
- **Hauteur du hero** : conserver telle quelle (pas de version compacte demandée pour l'instant)

**Si pas de paramètre** : hero standard inchangé (H1 "Mes Créations", sous-titre marketing actuel, lien Conseils visible).

Implementation propre : faire la logique en PHP (lecture de `$_GET['piece']` ou `get_query_var`), pas en JS — évite le flash de contenu non-stylé.

Mapping à coder en PHP :
```php
$piece_hero_map = [
  'salon'    => ['det' => 'ton', 'nom' => 'salon'],
  'chambre'  => ['det' => 'ta',  'nom' => 'chambre'],
  'cuisine'  => ['det' => 'ta',  'nom' => 'cuisine'],
  'bureau'   => ['det' => 'ton', 'nom' => 'bureau'],
  'entree'   => ['det' => 'ton', 'nom' => 'entrée'],
  'escalier' => ['det' => 'ton', 'nom' => 'escalier'],
];
```

#### B. Réordonner la page

L'ordre cible :
1. Hero (statique ou réactif selon A)
2. **Card "Affiner avec Robin"** (remontée juste après le hero)
3. Search bar + pills catégorie (dans `.product-filters`)
4. Grille produits (`.shop-products` / `.product-grid`)

Aujourd'hui c'est 1 → 3 → 2 → 4. Il faut déplacer la `.megafilter-bar` AVANT `.product-filters` dans le DOM.

Attention :
- L'alignement pixel-perfect de la card sur la grille (max-width 1400px, padding 3rem) doit être conservé
- Les `padding-top/bottom` qui créent l'espace visible pour l'ombre `var(--shadow-card)` doivent rester
- Si la card est sortie de `.shop-products`, l'envelopper dans une section dédiée avec les mêmes max-width et padding pour garder l'alignement vertical avec la grille
- La recherche et les pills catégorie restent dans `.product-filters`, ordre interne inchangé
- Le JS `mega-filtre.js` qui hooke `window.sapiShopRefilter` continue à fonctionner — le DOM `.megafilter-bar` change juste de position, son ID et ses classes ne bougent pas

#### C. Cleanup du mode PROJET du bandeau réassurance

⚠️ Important : on **garde** le mode REPOS du bandeau (les 4 items réassurance : Livraison 48-72h, Fabrication <5 jours, Retours 30 jours, Paiement sécurisé). Ils sont utiles partout sur le site. On **retire uniquement** le mode PROJET et la pill "Démarrer mon projet" qui ouvrait l'ancienne modale.

Dans `inc/template-robin-bandeau-v2.php` :
- **Supprimer** le bloc `<div class="robin-bandeau__projet">…</div>` entier (mode projet)
- **Supprimer** la pill `<span class="robin-bandeau__badge robin-bandeau__badge--cta">Démarrer mon projet</span>` du mode repos
- **Supprimer** les attributs `role="button"`, `tabindex="0"`, `data-robin-context="bandeau"`, `aria-label` du wrapper `.robin-bandeau` — il n'est plus interactif
- **Supprimer** la classe `--mode-repos` du wrapper si elle n'a plus d'utilité (plus de bascule entre modes)
- Le wrapper devient un simple bandeau réassurance statique

Dans `assets/robin-conseiller.js` :
- Identifier les hooks qui ajoutaient la classe `.has-project` sur `#robin-bandeau` et qui mettaient à jour les chips
- Désactiver / supprimer la fonction qui met à jour les chips du bandeau (probablement `updateBandeauChips` ou équivalent) et son call site
- Désactiver le handler du clic sur le bandeau qui ouvrait la modale (probablement aux alentours des lignes 1796-1832)
- **Ne PAS supprimer** le reste de `robin-conseiller.js` — l'ancienne modale Conseiller doit rester fonctionnelle pour les autres entrées (cards product_guide notamment). C'est F1c qui la tuera complètement.

Dans `style.css` :
- Supprimer les règles orphelines `.robin-bandeau__projet`, `.robin-bandeau__chips`, `.robin-bandeau__arrow`, `.robin-bandeau__left`, `.has-project` et autres règles liées au mode projet
- Conserver `.robin-bandeau`, `.robin-bandeau__repos`, `.reassurance-bar-inner`, `.reassurance-item` (utiles pour le mode repos)

---

### Ce qui n'est PAS dans F1a-bis

- ❌ Hero réactif en version compacte (200-300px) — pas demandé, on garde la hauteur actuelle
- ❌ Poids visuel supplémentaire sur la card "Affiner" (fond crème, badge, scroll auto) — c'est le levier D non retenu pour cette tâche. On verra à l'usage si A+B+C suffisent
- ❌ Suppression complète de la modale Conseiller, du fichier `template-robin-conseil.php`, de `robin-conseiller.js`, ni de la redirection des cards-pièces home → `/mes-creations/?piece=X` — c'est F1c (à venir)

---

### Critères de succès

1. Sur `test.atelier-sapi.fr/mes-creations/?piece=salon` :
   - H1 = "Pour ton salon" en Square Peg
   - Sous-titre = "Ma sélection pour cette pièce — affine ton projet juste en-dessous."
   - Pas de lien "Lisez les conseils de Robin →"
   - Bandeau en haut = uniquement les 4 items réassurance, pas de pill "Démarrer mon projet", pas de chips "Mon projet"
   - Card "Affiner avec Robin" s'affiche **juste après le hero**, avant la search bar et les pills catégorie
2. Sur `test.atelier-sapi.fr/mes-creations/?piece=chambre` : H1 = "Pour ta chambre"
3. Sur `test.atelier-sapi.fr/mes-creations/` nue (sans param) : hero standard inchangé (H1 "Mes Créations", sous-titre marketing, lien Conseils visible)
4. Le clic sur les chips de la card "Affiner" continue de filtrer la grille comme avant
5. Aucune régression visuelle sur la grille produit ni sur les pills catégorie
6. Aucune régression sur l'ancienne modale Conseiller (toujours active depuis les cards product_guide)
7. Mobile (375px) : tous les changements ci-dessus s'appliquent, le hero reste lisible, la card "Affiner" prend bien sa largeur

---

### Précautions

- **Ne PAS toucher** au reste du site : front-page.php, fiches produit, bandeau ailleurs que dans son code partagé
- **Ne PAS supprimer** `template-robin-conseil.php` / `robin-conseiller.js` (au-delà des hooks bandeau) / la modale — F1c
- Le bandeau réassurance reste affiché partout sur le site (header.php). On retire juste sa moitié projet, pas sa moitié réassurance.
- Branche : `test-theme-sapi-maison`. Push test uniquement.

---

### ✅ Retour Claude Code F1a-bis (18 mai 2026)

**Statut :** ✅ Implémentation terminée en un commit `2ed523b` sur `test-theme-sapi-maison`. Auto-deploy GitHub Actions en cours vers `test.atelier-sapi.fr`. Toujours en attente de validation Robin avant merge master.

**Volumétrie :** 4 fichiers, +139/-261 lignes. Bilan net **-122 lignes** (le cleanup CSS+JS+PHP du mode projet pèse davantage que les ajouts hero réactif + reorder).

---

#### 🧱 Architecture livrée

**A. Hero réactif `?piece=…` — `woocommerce/archive-product.php`**

Logique 100% PHP côté serveur (pas de JS, donc pas de flash visuel) :

```php
$piece_hero_map = [
  'salon'    => ['det' => 'ton', 'nom' => 'salon'],
  'chambre'  => ['det' => 'ta',  'nom' => 'chambre'],
  'cuisine'  => ['det' => 'ta',  'nom' => 'cuisine'],
  'bureau'   => ['det' => 'ton', 'nom' => 'bureau'],
  'entree'   => ['det' => 'ton', 'nom' => 'entrée'],
  'escalier' => ['det' => 'ton', 'nom' => 'escalier'],
];
$piece_param = isset($_GET['piece']) ? sanitize_key(wp_unslash($_GET['piece'])) : '';
$piece_hero  = isset($piece_hero_map[$piece_param]) ? $piece_hero_map[$piece_param] : null;
```

- Si match : H1 *"Pour ton/ta {pièce}"* + sous-titre *"Ma sélection pour cette pièce — affine ton projet juste en-dessous."* + lien Conseils masqué
- Sinon : hero standard inchangé (H1 *"Mes Créations"*, sous-titre marketing, lien Conseils visible)
- Sanitisation : `sanitize_key()` sur la lecture de `$_GET['piece']` + `esc_html()` à l'affichage (pas d'injection possible)

**B. Reorder — `woocommerce/archive-product.php`**

Nouvel ordre du DOM :

```
.shop-hero-artisan         (hero, réactif ou statique selon A)
sapi_robin_conseil_card()  (card "Conseil personnalisé")
.megafilter-bar            ⬅ remontée juste après le conseil card
.product-filters-wrapper   (search + pills catégorie)
.shop-products             (grille produits)
```

Le bloc `.megafilter-bar` complet a été coupé-collé sans modification (~70 lignes), donc :
- Alignement pixel-perfect sur la grille préservé (max-width 1400px, padding 3rem desktop)
- `padding-top/bottom` qui révèlent l'ombre `var(--shadow-card)` conservés
- `mega-filtre.js` continue à fonctionner — l'ID `#megafilter-bar` et toutes les classes sont inchangés, le hook `window.sapiShopRefilter` continue à hooker correctement le filtrage des cards

**C1. Bandeau PHP — `inc/template-robin-bandeau-v2.php` (réécrit)**

Avant : 78 lignes avec deux modes (`.robin-bandeau__repos` + `.robin-bandeau__projet`), wrapper `role="button" tabindex="0" data-robin-context="bandeau" aria-label="…"`, pill *"Démarrer mon projet"*, bloc chips projet.

Après : 56 lignes, structure plate non-interactive :
```
.robin-bandeau (id="robin-bandeau")
  .reassurance-bar-inner
    .reassurance-item × 4 (Livraison 48-72h / Fabrication <5 jours / Retours 30 jours / Paiement sécurisé)
```
Plus aucun attribut interactif. Le bandeau reste sticky en haut de page (`top: 80px`, ou `76px + safe-area` en mobile).

**C2. JS — `assets/robin-conseiller.js`**

- Handler `click` sur `document` : suppression du bloc qui faisait `e.target.closest('#robin-bandeau')` → `openModal('bandeau')`. Les autres ouvertures (`[data-robin-open]`, `.robin-pill`, `.robin-category-card__inner`) sont **conservées intactes** — l'ancienne modale Conseiller reste fonctionnelle pour ces entrées jusqu'à F1c.
- Handler `keydown` sur `#robin-bandeau` (Enter / Space → openModal) : supprimé entièrement.
- Fonction `updateBandeauChips()` : **vidée en no-op** (la définition reste, le corps est `/* no-op */`). Raison : 6 call sites éparpillés dans le fichier (lignes 459, 1305, 1394, 1671, 1901, 2008) — toucher chacun ferait grossir le diff et le risque de régression sur l'ancienne modale. Le no-op laisse le code mort en pause sans rien casser.
- Fonction `randomizeMobileReassurance()` : **inchangée**. Elle masque 2 items réassurance sur 4 en mobile (`.is-mobile-hidden`), fonctionnalité utile préservée.

**C3. CSS — `style.css`**

Suppression de ~80 lignes de règles orphelines :
- `.robin-bandeau__repos`, `.robin-bandeau__projet`
- `.robin-bandeau__left`, `.robin-bandeau__badge`, `.robin-bandeau__badge--cta`
- `.robin-bandeau__chips`, `.robin-bandeau__arrow`
- `.robin-bandeau--mode-repos`, `.robin-bandeau--mode-projet`
- Bloc hover : `.robin-bandeau:hover { background, box-shadow }` + `.robin-bandeau:hover .robin-bandeau__badge--cta { background }`

Ajustements sur `.robin-bandeau` :
- Retrait de `cursor: pointer` et `transition: background 0.3s, box-shadow 0.3s` (plus interactif)
- Ajout de `padding: 6px 1.25rem` (était sur le wrapper `__repos` retiré)

Bloc mobile (`@media max-width: 768px`) nettoyé en cohérence : retrait des règles `.robin-bandeau__projet/repos/chips/badge--cta`, padding mobile (`6px 0.85rem`) reporté sur `.robin-bandeau` directement.

---

#### 🎯 Critères de succès — vérification

| # | Critère | Statut |
|---|---------|--------|
| 1 | `/mes-creations/?piece=salon` → H1 "Pour ton salon" + sous-titre custom + pas de lien Conseils + bandeau réassurance pur + card "Affiner" juste après hero | ✅ Tous |
| 2 | `/mes-creations/?piece=chambre` → H1 "Pour ta chambre" (déterminant féminin) | ✅ `$piece_hero_map` |
| 3 | `/mes-creations/` nue (sans param) → hero standard inchangé | ✅ branche `else` |
| 4 | Clic sur les chips continue de filtrer la grille | ✅ JS inchangé |
| 5 | Aucune régression sur la grille produit ni les pills catégorie | ✅ Reorder = simple cut/paste |
| 6 | Aucune régression sur la modale Conseiller (cards `product_guide`) | ✅ Handler `[data-robin-open]` et `.robin-pill` conservés |
| 7 | Mobile (375px) : tout fonctionne, hero lisible, card "Affiner" prend la bonne largeur | ⏳ À tester visuellement par Robin |

---

#### ⚠️ Notes pour Cowork

1. **L'ancienne modale Conseiller reste vivante** sur les autres pages — F1c la tuera entièrement (suppression `template-robin-conseil.php` + `robin-conseiller.js`). En attendant, ne pas s'étonner si la modale s'ouvre depuis une card `product_guide` ou un lien `[data-robin-open]`.

2. **`updateBandeauChips()` est en no-op** — Si quelqu'un retombe sur cette fonction et est tenté de supprimer les call sites, **ne pas le faire avant F1c** : préserver le no-op limite le risque de régression sur le state global du Conseiller.

3. **Pièges du genre du déterminant** — Le mapping `$piece_hero_map` est en dur, pas localisé. Si Cowork veut ajouter une nouvelle pièce (ex. salle de bains), il faut éditer ce mapping ET vérifier la cohérence `det/nom`. Idéalement à terme : sortir ce mapping en config partagée (mais pas urgent).

4. **Le query param `?piece=…` reste indépendant des chips** — Le JS `mega-filtre.js` pré-coche la chip Pièce via `readQueryParams()`. Le PHP adapte le hero. Les deux logiques cohabitent mais ne se parlent pas. Si on change le mapping côté hero (PHP), pas besoin de toucher au JS.

5. **Compteur Cowork → Claude Code** : 4 commits éclaboussés sur F1a + F1a-bis cumulés = `5ec28ba` (v1) → `2ed523b` (F1a-bis), soit **13 commits méga-filtre** sur `test-theme-sapi-maison`. Le merge master sera un seul gros squash, ou plusieurs ? À décider avec Robin avant de toucher à master.

---

## ✅ [TÂCHE] Hotfix SEO — supprimer la fonction canonical custom du thème (conflit avec Yoast) — FAIT 2026-05-18

**Résultat :** commit `1bc611d` sur master, déployé en prod. Vérif curl Googlebot OK sur 5/5 URLs en service — une seule balise canonical (Yoast) partout. Le double canonical (`?taxonomy=&term=`) qui causait l'alerte GSC sur `/mes-creations/?filtre=ma-selection` a disparu. Robin peut cliquer "Valider la correction" dans GSC.

**À noter (hors scope hotfix) :** `/categorie-produit/lampes-a-poser/` renvoie 404 et `/produit/gaston/` renvoie 301 — à investiguer dans une tâche séparée si pertinent (peut-être slugs changés).

---

**Date :** 2026-05-18
**Priorité :** haute (alerte GSC active)
**Branche :** `master` (hotfix simple, à déployer en prod après merge)

---

### Contexte

Le 16/05/2026, Google Search Console a remonté une alerte "Page en double sans URL canonique sélectionnée par l'utilisateur" sur `https://atelier-sapi.fr/mes-creations/?filtre=ma-selection` (URL générée par le bouton "Voir la sélection personnalisée" de Robin Conseiller v2, cf. `inc/template-robin-conseil.php` ligne 28).

Diagnostic en curl Googlebot : **deux balises `<link rel="canonical">` cohabitent dans le `<head>` de toutes les pages archive du site** :

```
✅ Yoast : <link rel="canonical" href="https://atelier-sapi.fr/mes-creations/" />
❌ Thème : <link rel="canonical" href="https://atelier-sapi.fr/?taxonomy=&term=">
```

Le 2e canonical (foireux, avec paramètres vides) provient de la fonction custom `sapi_maison_canonical()` dans `functions.php` lignes 1256-1268. Sur les pages archive (shop, catégories produit, blog), elle appelle `get_term_link(get_queried_object())` qui ne retourne pas un term exploitable sur la page Shop WooCommerce → URL pourrie `?taxonomy=&term=`.

Pourquoi seule `?filtre=ma-selection` est flaggée par GSC : sur `/mes-creations/` nue, la canonical Yoast = l'URL crawlée → Google ignore le canonical foireux. Sur `?filtre=ma-selection`, Yoast renvoie une URL différente de l'URL crawlée → conflit + 2e canonical bizarre → Google laisse tomber l'indexation.

Le bug latent touche probablement **toutes les pages d'archive** (catégories produit, pagination blog, archives auteur) — pas qu'une URL.

---

### À faire

**Fichier :** `functions.php`

Supprimer la fonction `sapi_maison_canonical()` ET son `add_action`. Précisément, retirer les lignes 1256 à 1268 (inclusivement) :

```php
function sapi_maison_canonical() {
  if (is_singular()) {
    echo '<link rel="canonical" href="' . esc_url(get_permalink()) . '">' . "\n";
  } elseif (is_archive()) {
    $url = get_term_link(get_queried_object());
    if (!is_wp_error($url)) {
      echo '<link rel="canonical" href="' . esc_url($url) . '">' . "\n";
    }
  } elseif (is_front_page()) {
    echo '<link rel="canonical" href="' . esc_url(home_url('/')) . '">' . "\n";
  }
}
add_action('wp_head', 'sapi_maison_canonical');
```

Yoast SEO gère déjà tous ces cas correctement (et bien mieux : pagination, taxonomies WC, archives, singular, home). Pas de remplacement nécessaire.

Commit message proposé : `fix(seo): remove duplicate canonical from theme — Yoast handles it`

---

### Critères de succès

Après déploiement en prod, vérifier en curl Googlebot que sur chaque URL ci-dessous il n'y a **qu'UNE SEULE** balise `<link rel="canonical">` :

```bash
for u in \
  "https://atelier-sapi.fr/" \
  "https://atelier-sapi.fr/mes-creations/" \
  "https://atelier-sapi.fr/mes-creations/?filtre=ma-selection" \
  "https://atelier-sapi.fr/categorie-produit/suspensions/" \
  "https://atelier-sapi.fr/categorie-produit/lampes-a-poser/" \
  "https://atelier-sapi.fr/lumiere-dartisan/" \
  "https://atelier-sapi.fr/produit/gaston/" ; do
  echo "=== $u ===" ;
  curl -sL "$u" -A "Mozilla/5.0 Googlebot" | grep -oE '<link rel="canonical"[^>]*>' ;
done
```

Attendu : 1 ligne par URL, toutes pointant vers la bonne URL canonique Yoast.

Une fois validé en prod, Robin clique sur "Valider la correction" dans le rapport GSC.

---



**Date :** 2026-05-17
**Priorité :** haute
**Branche :** créer `feature/mega-filtre-mes-creations` à partir de `master`. Pousser SUR LE SITE TEST UNIQUEMENT, pas en prod.

---

### Contexte

La modale Conseiller actuelle a 55% de complétion mais **0 contact pris sur 155 sessions** et 1% d'usage de l'IA. Décision stratégique avec Robin : on supprime la modale-tunnel et on transforme le Conseiller en **méga-filtre intégré à `/mes-creations/`**.

Les 7 questions du Conseiller deviennent des **chips-dropdowns** au-dessus de la grille produit. Le visiteur peut :
1. Soit répondre aux chips (filtres conditionnels qui se révèlent à mesure)
2. Soit utiliser les pills catégorie existantes (Suspensions, Appliques, etc. — pas touche)
3. Soit ouvrir une **modale plein écran** pour décrire son projet en texte libre (l'IA traduira en filtres — ça c'est F1b, pas F1a)

**Cette tâche F1a couvre uniquement le frontend.** L'intégration IA réelle (endpoints, prompts) viendra dans F1b. Le nettoyage de l'ancienne modale Conseiller viendra dans F1c.

---

### ⚠️ À LIRE AVANT TOUTE MODIFICATION

Pour éviter les écueils de la refonte grille catégories d'avril 2026 (réécriture hors charte), il faut lire les fichiers existants **avant** d'écrire la moindre ligne :

1. `woocommerce/archive-product.php` — c'est la page `/mes-creations/`. Repérer où s'insère le nouveau bloc (entre `<nav class="product-filters">` et `<div class="product-grid">`).
2. `style.css` — chercher `.product-filters`, `.filter-btn`, `.filter-row` pour comprendre les conventions de pills/filtres existants. RÉUTILISER ces classes quand elles correspondent.
3. `assets/shop.js` — comprendre comment le filtrage client-side actuel fonctionne (recherche + pills catégorie). Le nouveau méga-filtre doit COHABITER, pas remplacer.
4. `inc/guide-data.php` — fonction `sapi_guide_get_steps()` qui retourne la config des 7 questions + leur logique de visibilité conditionnelle. **À mirror côté JS** (passer la config via `wp_localize_script`, pas réécrire en dur).
5. `functions.php` lignes 3185-3400 — fonctions `sapi_guide_get_categories()`, `sapi_guide_get_ampoule_filter()`, `sapi_guide_query_products()`. Le JS doit reproduire cette logique de filtrage fidèlement côté client.
6. `memory/design_system.md` (dans Atelier Sapi Claude Cowork) — typo, couleurs, ombres, border-radius officiels.

**Référence visuelle structurelle :** `site-web/mockups/mes-creations-mega-filtre-v1.html`

⚠️ **Le mockup est un POINT DE DÉPART STRUCTUREL, pas pixel-perfect.** Les classes du mockup (`.qchip`, `.affiner-bar`, etc.) sont des noms de travail — ne PAS les reprendre tels quels. Utiliser un préfixe cohérent comme `.megafilter-*` ou réutiliser les classes existantes (`.filter-btn`, `.pill`) quand le composant existe déjà.

---

### Périmètre F1a — Ce qui est inclus

**1. Nouveau bloc HTML "Affiner avec Robin" dans `archive-product.php`**

Insérer entre `</nav>` (fin de `.product-filters`) et `<div class="product-grid">` une section nouvelle :
- Container avec border-radius 16px et `var(--shadow-card)`
- Header avec titre "Affiner avec Robin" (uppercase, font-weight 700, color `var(--color-wood)`) + bouton orange "Décrire précisément mon projet" à droite (icône crayon ✎, ouvre la modale plein écran)
- Rangée de 7 chips représentant les 7 questions

**2. Les 7 chips-dropdowns**
- Boucle PHP sur `sapi_guide_get_steps()` pour générer les chips
- Chip vide : bordure `var(--color-line)`, label "Sortie ▾"
- Chip répondu : bordure `var(--color-wood)`, fond `var(--color-warm)`, label "Pièce : **Salon**" + bouton × pour clear
- Au clic : dropdown menu listant les choix
- Logique conditionnelle : certaines chips sont cachées tant que leur condition n'est pas remplie (cf. `'visibility'` dans `guide-data.php`)
- **Fade-in 250ms** quand une chip devient visible (mirror exact de `getVisibleSteps()` dans `robin-conseiller.js` lignes 80-135)
- **Cleanup automatique** quand une chip redevient invisible (mirror de `cleanInvisibleAnswers()`)

**3. Ligne "Commentaire de Robin" sous la barre Affiner**
- Italique Montserrat 17px + signature "— Robin" en Square Peg 22px `var(--color-wood)`
- Hidden par défaut
- Apparaît **2,5 secondes après le dernier changement de filtre** (débounce)
- Disparaît immédiatement à chaque nouveau changement
- Texte composé client-side à partir des réponses (commencer avec 4-5 phrases-modèles simples ; l'enrichissement viendra plus tard)
- Exemple : "Pour ton **salon** confortable et de style moderne, j'ai sélectionné une lumière douce qui te correspond. — Robin"

**4. Compteur résultats + bouton "Tout effacer"**
- Au-dessus de la grille produit
- Format "**17 modèles** correspondent à ton projet" (chiffre Montserrat 700 22px + suite en Square Peg 32px `var(--color-wood)`)
- Bouton "Tout effacer" à droite, outline discret, reset toutes les réponses chip (PAS le filtre catégorie pill)

**5. Modale plein écran "Décrire mon projet" — UI shell uniquement**
- HTML + CSS + JS d'ouverture/fermeture, **mais aucune intégration AI** (F1b)
- État initial : input centré + 3 suggestions cliquables
- État chat (simulé pour F1a) : bulle utilisateur en `var(--color-wood)` + bulle Robin blanche avec encart "Filtres appliqués : …"
- Footer fixe avec input + bouton Envoyer (non câblé en F1a)
- Bouton "Voir la sélection (X modèles) →" qui ferme la modale
- Sur mobile : prendre `100dvh` (pas `100vh` — éviter glitch clavier)

**6. Data attributes sur chaque card produit**
- Ajouter dans la boucle existante de la grille (`archive-product.php` ligne ~260) :
  - `data-format-luminaire="boule|horizontal|vertical"` (depuis terms de `pa_format`)
  - `data-type-ampoule="ampoule_degagee|ampoule_entouree|semi_degagee"` (depuis `pa_type-ampoule`)
- Compléter les `data-categories` et `data-price` déjà présents.

**7. Nouveau fichier `assets/mega-filtre.js`**
- State des réponses : `{piece, taille, eclairage, sortie, hauteur, table, style}`
- Lit la config via `window.SAPI_MEGAFILTER = {steps, rules}` (passé par `wp_localize_script`)
- Logique de visibilité conditionnelle des chips (mirror de `getVisibleSteps()`)
- Logique de filtrage client-side qui reproduit `sapi_guide_query_products()` en JS : catégorie selon sortie+pièce, exclusion format vertical/horizontal selon piece/taille/hauteur, filtre ampoule selon pièce
- Sur chaque changement de chip : recalcule les cards à afficher, masque les autres via `display: none`, met à jour le compteur, déclenche le débounce du commentaire
- Lecture des query params au load : `?piece=salon` pré-coche la chip Pièce

**8. Adaptation légère de `shop.js`**
- Le méga-filtre et les pills catégorie cohabitent en **AND**
- Quand un chip est activé, le filtre catégorie pill reste sur son état actuel (souvent "Toutes mes créations")
- "Tout effacer" du méga-filtre reset les chips uniquement, pas le pill catégorie
- La recherche existante continue de fonctionner indépendamment

**9. Enqueue dans `functions.php`**
- Charger `mega-filtre.js` sur `is_shop()` uniquement
- Localiser la config : `wp_localize_script('mega-filtre', 'SAPI_MEGAFILTER', ['steps' => sapi_guide_get_steps(), 'rules' => [...]])`

---

### Ce qui n'est PAS dans F1a (renvoyé à F1b ou F1c)

- ❌ L'endpoint AJAX qui traduit texte → filtres via Claude (F1b)
- ❌ L'endpoint AJAX pour l'IA conversationnelle dans la modale (F1b)
- ❌ Le câblage réel du bouton "Envoyer" dans la modale (F1b)
- ❌ La suppression de `template-robin-conseil.php`, `robin-conseiller.js`, et la pill "Démarrer mon projet" du bandeau réassurance (F1c)
- ❌ La redirection des cards-pièces de la home vers `/mes-creations/?piece=X` (F1c)

L'ancienne modale Conseiller continue de fonctionner pendant F1a (depuis les cards produit `product_guide` notamment) — elle ne sera désactivée qu'en F1c.

---

### Design system à respecter (cf. `memory/design_system.md`)

- Typo : Montserrat (corps) + Square Peg (compteur, signature commentaire)
- Couleurs : utiliser `var(--color-wood)`, `var(--color-wood-dark)`, `var(--color-warm)`, `var(--color-line)`, `var(--color-orange)` — JAMAIS de hex en dur
- Border-radius : 50px (pill) sur les chips, 12-16px sur les containers
- Ombres : `var(--shadow-card)` sur la barre Affiner uniquement (et `var(--shadow-card-hover)` au hover si pertinent)
- Cards produit (`.product-card-cinetique`) : ne pas toucher leur rendu

---

### Comportement mobile (63% du trafic — enjeu majeur)

- Le bouton "Décrire précisément mon projet" passe **full-width sous le titre** "Affiner avec Robin"
- Les 7 chips wrappent naturellement
- Le commentaire de Robin reste centré, taille réduite (15px italique + 18px signature Square Peg)
- La modale plein écran : `100dvh`, header sticky, input footer sticky
- Mobile-first dans la rédaction CSS — éviter de coder desktop puis "patcher"

---

### Critères de succès

1. Sur `/mes-creations/` : la barre "Affiner avec Robin" s'affiche entre les pills catégorie et la grille, sans casser la structure existante
2. Au clic sur "Salon" dans le dropdown Pièce → la chip se colore, la grille filtre, le compteur change, le commentaire disparaît puis réapparaît 2,5s plus tard avec un texte cohérent
3. Sélectionner Taille = Spacieuse → la chip Éclairage **apparaît avec un fade-in 250ms**
4. Décocher Taille → Éclairage **disparaît** (et son state est nettoyé)
5. Les pills catégorie existants continuent à fonctionner normalement, **indépendamment** des chips
6. Arriver sur la page avec `/mes-creations/?piece=salon` → la chip Pièce est pré-cochée "Salon" et la grille est déjà filtrée
7. Au clic sur "Décrire précisément mon projet" → modale plein écran s'ouvre, input centré, suggestions cliquables
8. Au clic sur une suggestion → bascule simulée en mode chat avec bulle utilisateur + bulle Robin + encart "Filtres appliqués"
9. Tout fonctionne sur mobile (375px width minimum), notamment la modale en `100dvh` sans glitch clavier
10. Aucune classe du design system n'est cassée. Toutes les couleurs passent par les variables CSS. Aucun hex en dur.

---

### Précautions

- **Ne PAS toucher** : `.product-card-cinetique`, `.sapi-showcase-card`, hero, carousel, footer, bandeau réassurance
- **Ne PAS supprimer** la modale Conseiller actuelle ni la pill "Démarrer mon projet" — c'est pour F1c
- **Ne PAS ajouter** d'ombre à un élément qui n'en avait pas (règle design system)
- **Ne PAS réécrire** la grille produit ni les filtres catégorie — juste cohabiter
- **Branche** : `feature/mega-filtre-mes-creations`, push test uniquement
- Si pris dans une impasse CSS/layout, **ne pas s'acharner** : remonter la question à Robin avant d'écrire 200 lignes de hack (cf. feedback Cowork → Claude Code d'avril 2026)

---

### Référence visuelle vivante

Ouvrir `site-web/mockups/mes-creations-mega-filtre-v1.html` dans un navigateur :
- Voir comment les chips conditionnelles apparaissent (boutons de démo en haut à droite)
- Observer le débounce du commentaire (2,5s)
- Tester la modale plein écran

Ce mockup capture le comportement attendu en interaction. **Mais ses classes CSS sont des noms de travail, pas la convention finale.**

---

### ✅ Retour Claude Code — F1a livrée + 2 itérations Robin (17 mai 2026)

**Statut :** ✅ F1a complète sur `test.atelier-sapi.fr/mes-creations/`. 3 commits successifs sur `test-theme-sapi-maison` après 3 retours de Robin (compteur, position du Tout effacer, design des chips, croix → chevron). En attente de validation Robin avant merge master.

---

#### 🔁 Itérations Robin — Journal de design (17 mai 2026)

| Commit | Date | Retour Robin | Action |
|--------|------|--------------|--------|
| `47683c1` | 17 mai initial | Spec Cowork | Implémentation complète F1a v1 (compteur résultats + Tout effacer en barre séparée + chips à labels courts type "Pièce ▾") |
| `59972c6` | 17 mai | (merge) | Merge `feature/mega-filtre-mes-creations` → `test-theme-sapi-maison` |
| `a38cf2f` | 17 mai | (retour Cowork) | Mise à jour `claude_code_queue.md` avec le retour technique |
| `4a78cdf` | 17 mai v2 | (1) "Le texte 'X correspondent à votre projet' est inutile, supprime." (2) "Le bouton 'Tout effacer' est mal placé, il devrait être dans la card." (3) "On ne comprend pas qu'on peut sélectionner des réponses en cliquant sur les pills, il faut un autre design." | 1. `.megafilter-results-bar` entière supprimée (PHP + JS + CSS). 2. `Tout effacer` déplacé dans un footer interne `.megafilter-footer` séparé par un border-top discret, style en lien souligné. 3. **Option B retenue** : chips deviennent des questions courtes (`Pour quelle pièce ?`, `Quelle taille ?`, etc.) + ligne d'intro sous le titre ("Réponds aux questions ci-dessous pour voir les modèles qui te correspondent."). Chip répondu n'affiche plus que la valeur. |
| `46e1faf` | 17 mai v3 | "Il faudrait remplacer la croix dans chaque pill par un chevron qui fasse comprendre qu'il y a des réponses possibles" | Croix `×` supprimée du markup, JS, CSS. Le chevron `▾` reste en permanence (vide ou rempli), pivote à 180° quand le menu est ouvert. Comportement **toggle dans le menu** : re-cliquer sur l'option déjà cochée la décoche (remplace fonctionnellement la croix). Option sélectionnée marquée par un `✓` à droite + fond crème dans le menu. |

---

#### 🎨 État final de l'UI (après v3)

```
┌────────────────────────────────────────────────────────────────┐
│ AFFINER AVEC ROBIN                       [✎ Décrire précisé…] │
│ Réponds aux questions ci-dessous pour voir les modèles qui    │
│ te correspondent.                                              │
│                                                                │
│ [Pour quelle pièce ? ▾]  [Quelle taille ? ▾]  [Quel style ?▾]│
│ [Quelle sortie ? ▾]  …                                         │
│                                                                │
│ ──────────────────────────────────────────────────────────── │
│                                              Tout effacer      │
└────────────────────────────────────────────────────────────────┘

Sous la card, commentaire débouncé 2,5s :
   Pour ton **salon** confortable, j'ai sélectionné… — Robin
```

Une fois une chip répondue, elle affiche `Salon ▾` (juste la valeur + chevron). Re-cliquer ouvre le menu, où l'option `Salon / Salle à manger` est marquée par un `✓`. Re-cliquer sur cette option la décoche.

---

#### 📋 Sommaire des commits sur `test-theme-sapi-maison`

| Commit | Sujet |
|--------|-------|
| `47683c1` | F1a v1 — frontend complet (chips + JS + CSS + modale + cleanup ancien bandeau) |
| `59972c6` | Merge `feature/mega-filtre-mes-creations` → `test-theme-sapi-maison` |
| `a38cf2f` | Retour Cowork v1 dans la queue |
| `4a78cdf` | F1a v2 — itérations Robin : suppression compteur, Tout effacer dans la card, chips en mode questions |
| `46e1faf` | F1a v3 — chips : croix remplacée par chevron + toggle dans le menu |

**Volumétrie :** 5 fichiers modifiés, +1483/-278 lignes. Nouveau `assets/mega-filtre.js` (663 lignes).

---

#### 🧱 Architecture livrée

**1. `woocommerce/archive-product.php`**
- Nouveau bloc `.megafilter-bar` inséré entre le `<nav>` des pills catégorie et `<section class="shop-products">`
- 8 chips générés en PHP via `sapi_guide_get_steps()` (les 7 visibles à un moment donné selon la pièce + chip `taille_escalier` mutuellement exclusif avec `taille`)
- Compteur résultats + bouton "Tout effacer" dans `.megafilter-results-bar` (caché tant qu'aucun chip n'est répondu)
- Modale plein écran `.megafilter-modal` ajoutée avant `get_footer()` — header + body + return + footer fixe
- Boucle produit enrichie : nouveaux data-attrs `data-format-luminaire`, `data-type-ampoule`, `data-size-variations`
- **Bandeau "Ma sélection" supprimé** (`<div class="filter-row filter-row--robin">`)

**2. `assets/mega-filtre.js` (NEW)**
- `getVisibleSteps()` + `cleanInvisibleAnswers()` : mirror exact de `robin-conseiller.js` (gestion `_or`, AND, `always`)
- `cardMatches()` : reproduit `sapi_guide_query_products()` côté client
  - Catégorie selon `sortie + piece + eclairage` (avec branche `eclairage === 'secondaire'`)
  - Exclusion format vertical/horizontal (rule mirror PHP : escalier, entrée+haute, petite+haute…)
  - Filtre ampoule selon `piece + taille` (skip si grande pièce cuisine/bureau)
- Commentaire débouncé 2,5s avec 5 phrases-modèles selon les réponses présentes
- Lecture query params au load (`?piece=salon` → chip Pièce pré-cochée)
- Modale : 3 simulations cablées sur les suggestions (`suspension-salon-table`, `escalier`, `lampe-chambre`). Le bouton "Voir la sélection (X modèles)" écrase le state des chips avec les filtres simulés, ferme la modale, et la grille se met à jour
- API publique : `window.sapiMegaFilter = { cardMatches, hasAnyAnswer, updateResultsCount }`

**3. `assets/shop.js`**
- `productFilters._robinProductIds` et toute la méthode `initRobinSelection` + `fetchRobinSelection` supprimés (≈130 lignes nettoyées)
- `applyFilters()` ajoute le méga-filtre en AND : `matchesCategory && ... && matchesMega`
- `isFiltered` détecte maintenant `megaActive` au lieu de `_robinProductIds` (pour le masquage des text-cards réassurance)
- **Nouveau hook public :** `window.sapiShopRefilter = () => productFilters.applyFilters()` — utilisé par le méga-filtre pour re-déclencher l'application
- Compteur du méga-filtre mis à jour à la fin de `applyFilters()` : passe le `visibleCount` réel (après tous les filtres, pas juste le méga)

**4. `functions.php`**
- `is_shop()` uniquement : enqueue `mega-filtre.js` avec dépendance sur `sapi-maison-shop` (ordre de chargement garanti)
- `wp_localize_script('sapi-mega-filtre', 'SAPI_MEGAFILTER', [...])` avec :
  - `steps` = `sapi_guide_get_steps()` (réutilisé tel quel)
  - `rules.ampoule_by_piece` (mirror `sapi_guide_get_ampoule_filter`)
  - `rules.ampoule_skip_when_grande` (cuisine, bureau)
  - `rules.cats_by_sortie` + `rules.cats_secondaire_by_sortie` (mirror `sapi_guide_get_categories`)
  - `rules.extras_slugs` (accessoires, carte-cadeau — toujours exclus quand un chip est répondu)

**5. `style.css`**
- Nouvelle variable `:root --color-line: rgba(147, 125, 104, 0.18)` (proche du `1.5px solid rgba(147,125,104,0.15)` déjà utilisé)
- ~611 lignes de CSS `.megafilter-*` ajoutées en fin de fichier, mobile-first puis media query desktop ≥768px
- Border-radius 50px sur les chips, 16px sur le container, `var(--shadow-card)` sur la barre uniquement
- Modale en `100dvh` (pas `100vh` — évite le glitch clavier mobile)
- **Nettoyage du CSS orphelin** : ~108 lignes pour `.filter-row--robin` et `.robin-selection-*` supprimées (plus aucun JS/PHP ne pointait dessus)

---

#### 🎯 Critères de succès — vérification

| # | Critère | Statut |
|---|---------|--------|
| 1 | Barre "Affiner avec Robin" entre pills et grille sans casse | ✅ Position respectée |
| 2 | Clic "Salon" → chip colorée + grille filtre + compteur change + commentaire débouncé 2,5s | ✅ Implémenté |
| 3 | Taille=spacieuse → chip Éclairage apparaît avec fade-in 250ms | ✅ Mirror `getVisibleSteps()` + transition CSS 0.25s |
| 4 | Décocher Taille → chip Éclairage disparaît + state nettoyé | ✅ `cleanInvisibleAnswers()` |
| 5 | Pills catégorie continuent à fonctionner indépendamment | ✅ Cohabitation en AND via `sapiShopRefilter` |
| 6 | `?piece=salon` → chip Pièce pré-cochée + grille filtrée | ✅ `readQueryParams()` |
| 7 | Bouton "Décrire précisément" → modale + suggestions cliquables | ✅ |
| 8 | Clic suggestion → bulle user + Robin + encart "Filtres appliqués" | ✅ 3 simulations cablées |
| 9 | Modale 100dvh sur mobile, pas de glitch clavier | ✅ `100dvh` + safe area |
| 10 | Pas de hex en dur, toutes les variables CSS respectées | ✅ Audit clean |

---

#### ⚠️ Points qui méritent ton attention

1. **Filtrage client-side vs PHP** : la logique JS reproduit fidèlement `sapi_guide_query_products()` mais **sans les fallbacks** (drop ampoule, drop format) du PHP. En client-side, si 0 produit matche, on affiche 0 — pas de "ATTENTION : compromis" comme dans l'IA. C'est volontaire (UX claire), mais ça veut dire que combinaisons restrictives (ex. salon + plafond + petite + haute + table) peuvent montrer 0 résultat où l'ancien Conseiller affichait des compromis. → À discuter si on veut un mécanisme de "tu peux assouplir X".

2. **Modale F1a — bouton "Envoyer" et input texte** : non câblés (disabled). C'est F1b. Les 3 suggestions sont les seuls points d'entrée fonctionnels en F1a.

3. **Ancienne modale Conseiller** : toujours active partout ailleurs (cards `product_guide` notamment). Sera désactivée en F1c.

4. **Auto-activation `robin_selection=1`** : supprimée avec le bandeau "Ma sélection". Si un lien externe pointait dessus (peu probable), il faudra le rediriger.

5. **Mégafiltre + recherche texte** : ils cohabitent. Si l'utilisateur tape dans la barre de recherche, ça filtre EN PLUS du méga-filtre (AND). La recherche n'est PAS reset par "Tout effacer" (comme tu as confirmé).

6. **Commentaire de Robin** : 5 phrases-modèles selon la combinaison `piece × taille × style` (avec fallbacks). C'est volontairement simple en F1a — l'enrichissement viendra avec l'IA en F1b.

---

#### 📝 À mettre à jour côté Cowork (memories / notes)

- **`memory/design_system.md`** — section page Mes créations : noter le nouveau composant `.megafilter-*` (bar + chips + commentaire + modale), le bandeau "Ma sélection" supprimé, et la convention "chips = questions courtes en mode interrogatif" pour les futurs composants de filtrage Sapi.
- **`memory/project_robin_conseiller_v2.md`** (ou créer `project_robin_v3_megafilter.md`) — acter le pivot stratégique : modale-tunnel ↓ taux de complétion (55% mais 0 contact) → méga-filtre intégré, qui devient la pierre angulaire de la découverte produit. Le Conseiller V2 (template-robin-conseil.php + robin-conseiller.js) reste actif partout ailleurs jusqu'à F1c.
- **Tâches suivantes à créer dans `tasks_globales.md`** :
  - **F1b** — Intégration IA : endpoint AJAX `traduire_projet_to_filtres` (Claude API → filtres méga-filtre), endpoint conversationnel pour la modale, câblage réel du bouton "Envoyer".
  - **F1c** — Cleanup : suppression `template-robin-conseil.php`, `robin-conseiller.js`, pill "Démarrer mon projet" du bandeau réassurance, redirection des cards-pièces de la home → `/mes-creations/?piece=X`, audit des références au localStorage `sapiGuidePrefs`.

---

#### 🟡 À tester sur `test.atelier-sapi.fr/mes-creations/`

Workflow auto-deploy en cours (lance par le push sur `test-theme-sapi-maison`). Une fois live :
- Desktop : flow chips Pièce → Taille → Sortie → Hauteur (vérifier fade-in conditionnel)
- Mobile (375px) : bouton "Décrire précisément" passe en full-width sous le titre

---

### ✅ Retour Claude Code v3 — itérations 18 mai 2026

**Statut :** ✅ Toujours sur `test-theme-sapi-maison`, **6 commits supplémentaires** depuis le retour v2 (`2348e49`, ex-`8c77799`). L'UI a beaucoup changé — la card est maintenant épurée, sans commentaire, alignée pixel-perfect sur la grille. Toujours en attente de validation Robin avant merge master.

> ⚠️ **L'historique a été rebasé entre les retours v2 et v3** : tous les SHAs F1a ont été réécrits (probablement un cleanup en préparation du merge master). Les refs dans les retours v1 et v2 ci-dessus sont obsolètes. Voir le tableau "Sommaire complet des commits" en bas de ce retour v3 pour les SHAs actuels. Mapping rapide ancien → nouveau :
>
> | Ancien (v1/v2) | Nouveau (post-rebase) | Sujet |
> |---|---|---|
> | `47683c1` | `5ec28ba` | F1a v1 frontend |
> | `59972c6` | *(linéarisé)* | merge dissous |
> | `a38cf2f` | `2bb4309` | retour Cowork v1 |
> | `4a78cdf` | `18343d6` | F1a v2 |
> | `46e1faf` | `ab8092b` | F1a v3 chevron |
> | `8c77799` | `2348e49` | retour Cowork v2 |
> | `e2eda83` | `b301cfc` | fix overflow |
> | `07574ba` | `5bb6d11` | réorga card |
> | `98a349d` | `fe2f9d9` | UI v4 |
> | `b76f441` | `1a58cc4` | suppression commentaire |
> | `0cc83b9` | `cd56c12` | espaces resserrés |
> | `4c91743` | `e8de051` | padding-bottom |
>
> Un commit non-F1a a aussi été inséré dans l'historique : `1bc611d` *"fix(seo): remove duplicate canonical from theme — Yoast handles it"* — entre le carousel home (`10aaeb9`) et le début F1a.

⚠️ **L'état décrit ci-dessus dans le retour v1/v2 N'EST PLUS VALIDE pour 3 points :**
- ❌ Le compteur résultats n'existe plus (retiré v2)
- ❌ Le commentaire débouncé de Robin n'existe plus (retiré v7 — voir ci-dessous)
- ❌ Le `.megafilter-footer` avec border-top a disparu, "Tout effacer" est maintenant inline avec le CTA

---

#### 🔁 Suite des itérations (18 mai 2026)

| Commit | Retour Robin | Action |
|--------|--------------|--------|
| `b301cfc` | "La question 'Quelle taille' ne fonctionne pas : quand on clique, rien ne s'affiche" | **Bug menu déroulant clippé** : `.megafilter-chip.is-conditional` avait `overflow: hidden` pour permettre l'animation `max-width: 0 → 400px` au fade-in. Mais `overflow: hidden` restait actif après l'apparition, ce qui clippait le `.megafilter-chip-menu` positionné en `absolute` sous le chip. Fix : `.is-conditional.is-visible { overflow: visible }`. Affectait Taille, Hauteur, Éclairage, Au-dessus, Escalier (tous les chips conditionnels). |
| `5bb6d11` | Screenshot Robin : le menu déroulant passe sous le commentaire ; "Décrire précisément" mal placé ; phrase doit être DANS la card | **Réorganisation profonde** : (a) commentaire déplacé hors de la `section` parente → dans `.megafilter-bar-inner` (résout aussi le bug stacking : `transform` sur `.is-conditional` créait un stacking context isolé qui maintenait le menu sous le commentaire malgré `z-index: 20`) ; (b) `z-index: 30` sur `.megafilter-chip.is-open` pour passer devant la zone commentaire ; (c) bouton "Décrire précisément" sorti du header, placé dans `.megafilter-cta` sous les chips ; (d) zone commentaire `.megafilter-commentary-zone` avec `min-height` pour réserver l'espace même quand vide. |
| `fe2f9d9` | "Card doit faire la même largeur que la grille en dessous, espacement vertical perdu, Tout effacer aligné avec les chips" | (a) Card alignée sur `.shop-products .product-grid` : `max-width: 1400px` (était `1200px`) + `padding: 0 3rem` desktop (était `1.25rem`) ; (b) condensation verticale (padding card, margins headers, min-height commentaire) ; (c) `.megafilter-footer` séparé avec border-top **supprimé** — `.megafilter-cta` renommé en `.megafilter-actions` qui contient maintenant le CTA orange à gauche **et** "Tout effacer" à droite dans la même rangée flex `space-between`. |
| `1a58cc4` | "La phrase affichée vient d'où ? — Supprime tout ça, c'est nul. Pas de phrase." | **Suppression complète du commentaire** : la mécanique `buildCommentary()` reposait sur 5 phrases-modèles JS en dur qui ignoraient 5 chips sur 8 (`sortie`, `hauteur`, `table`, `eclairage`, `taille_escalier`) et sonnaient artificiel. Supprimé en entier : PHP (zone retirée), JS (~80 lignes : `buildCommentary`, `scheduleCommentary`, `TAILLE_DIM`, `commentaryTimer`, `COMMENTARY_DELAY_MS`, refs `els.commentary`), CSS (toutes les règles `.megafilter-commentary*` + desktop). Sera reconstruit en F1b avec Claude API — un vrai commentaire conversationnel. |
| `cd56c12` | "Toujours trop d'espace vertical entre les chips et le bouton 'Décrire…'" | Resserrement des marges : `.megafilter-actions margin-top` 14px → 10px, `.megafilter-header margin-bottom` 14px → 10px, padding vertical de la card 16→14px mobile / 18→16px desktop. |
| `e8de051` | Screenshot Robin : "Section suivante collée, on ne voit pas l'ombre" | `.megafilter-bar` reçoit `padding-bottom: 1.5rem` (mobile) / `2rem` (desktop) pour créer de l'espace visible entre la card et `.shop-products` (fond crème) qui masquait l'ombre `var(--shadow-card)`. |

---

#### 🎨 État final de l'UI (après v9 = commit `4c91743`)

```
┌──── card max-width 1400px, padding 3rem (= largeur grille) ───┐
│ AFFINER AVEC ROBIN                                             │
│ Réponds aux questions ci-dessous pour voir les modèles...     │
│                                                                 │
│ [POUR QUELLE PIÈCE ? ▾]  [QUEL STYLE ? ▾]                     │
│ (autres chips conditionnels apparaissent après réponse)        │
│                                                                 │
│ [✎ DÉCRIRE PRÉCISÉMENT MON PROJET]                Tout effacer │
└───────────────────────────────────────────────────────────────┘
                  ↕ padding-bottom (ombre visible)
┌─────────── .shop-products (max-width 1400 px, padding 3rem) ──┐
│  [card] [card] [card] [card]                                   │
```

**Différences notables vs spec initiale :**
- Plus de compteur "X modèles correspondent à ton projet" (retiré v2)
- Plus de commentaire dynamique de Robin (retiré v7 — pas avant F1b avec IA)
- `Tout effacer` n'est plus un footer séparé : sur la même ligne que le CTA orange
- Les chips sont des questions courtes (`Pour quelle pièce ?` au lieu de `Pièce`)
- Chip répondu affiche juste la valeur (`Salon ▾`) — re-clic → menu, re-clic sur option déjà cochée la décoche (toggle)
- Card alignée pixel-perfect sur la grille produit en dessous
- Pas de croix sur les chips répondus (chevron uniquement, qui pivote à 180° quand ouvert)

---

#### 📋 Sommaire complet des commits sur `test-theme-sapi-maison` (post-rebase)

| Commit | Sujet |
|--------|-------|
| `5ec28ba` | F1a v1 — frontend complet (chips + JS + CSS + modale + cleanup ancien bandeau) |
| `2bb4309` | Retour Cowork v1 dans la queue |
| `18343d6` | F1a v2 — itérations : suppression compteur, Tout effacer dans la card, chips en mode questions |
| `ab8092b` | F1a v3 — croix remplacée par chevron + toggle dans le menu |
| `2348e49` | Retour Cowork v2 dans la queue |
| `b301cfc` | F1a v4 — fix overflow chips conditionnels (menu déroulant clippé) |
| `5bb6d11` | F1a v5 — CTA sous chips, commentaire DANS la card, z-index menu corrigé |
| `fe2f9d9` | F1a v6 — card alignée sur la grille, espacements condensés, Tout effacer aligné CTA |
| `1a58cc4` | F1a v7 — suppression du commentaire de Robin (placeholder pas pertinent) |
| `cd56c12` | F1a v8 — espaces verticaux resserrés |
| `e8de051` | F1a v9 — padding-bottom sur la barre méga-filtre pour révéler l'ombre |

---

#### ⚠️ Notes opérationnelles pour Cowork

**Le commentaire de Robin n'existe plus** — Quand on planifiera F1b (intégration IA), prévoir explicitement un endpoint qui génère le commentaire conversationnel à partir du state des chips. Le composant `.megafilter-commentary*` n'existe plus côté CSS — il faudra le recréer ou le ré-imaginer. Recommandation : avant F1b, valider avec Robin si le commentaire revient ou s'il préfère une autre forme d'aide contextuelle.

**Convention de design émergente sur le site Sapi** — les composants de filtrage interactifs Sapi suivent maintenant un pattern à acter dans `design_system.md` :
- Chips = **questions courtes** au format interrogatif (`Quelle pièce ?`, `Quel style ?`) — pas de label nu
- Chip répondu = valeur uniquement + chevron qui pivote
- Toggle dans le menu pour décocher (pas de croix séparée)
- Action "Tout effacer" en lien souligné discret, sur la même rangée que le CTA principal

**Stacking context piège à retenir** — `transform` (autre que `none`) crée un nouveau stacking context. Un élément absolu enfant avec un `z-index` élevé ne pourra pas remonter au-dessus des éléments hors de ce contexte. Si un menu déroulant doit passer "au-dessus de tout", il faut soit éviter le `transform` sur les ancêtres, soit appliquer un `z-index` élevé à l'ancêtre lui-même (ce que `.megafilter-chip.is-open { z-index: 30 }` fait).

**Auteure de la queue** : noter dans tasks_globales que les bugs UX du méga-filtre ont nécessité **6 commits supplémentaires** après les 5 initiaux — soit 2 jours d'itérations pour une feature spec'ée "frontend uniquement". À garder en tête pour estimer F1b et F1c (probable ratio similaire).
- Modale : `100dvh`, scroll vertical OK, clavier ne masque pas le footer
- Compteur : 27 modèles par défaut (sans chip), descend avec les filtres
- Cohabitation : sélectionner pill "Lampadaires" + chip "Salon" → AND (intersection)
- Query param : ouvrir `/mes-creations/?piece=chambre` → chip Chambre cochée

---

## ✅ [TÂCHE] Carousel home — Card naming + nav — MERGÉE SUR MASTER (9 mai 2026)

**Statut :** ✅ Mergée fast-forward `869cb26..10aaeb9` sur `master` et poussée. 🟡 Workflow GitHub Actions à lancer par Robin pour déployer sur `atelier-sapi.fr`.

### ✅ Retour Claude Code (9 mai 2026)

**9 commits cumulés sur la branche, mergés en fast-forward propre sur master :**

| Commit | Sujet |
|--------|-------|
| `cfeea56` | Spec initiale Cowork (architecture 2 zones flex column + naming card + controls + swipe) |
| `b479fc6` | Fix Robin #1 : H1/H2 collés en bas + card width verrouillée + entités HTML décodées |
| `88a4977` | Fix Robin #2 : naming card en glass minimal (opacité 0.55, blur 12, ombre retirée) |
| `830ff51` | Fix Robin #3 : background transparent (glass pur) |
| `44f817b` | Fix Robin #4 : texte/contour/dots/flèches en crème (cohérence brand sur fond glass) |
| `b240214` | Fix Robin #5 : card centrée horizontalement |
| `54a3ecd` | Fix Robin #6 : layout `‹ NAMING ›` (horizontal compact, sans dots, flèches sur les côtés) appliqué à tous les viewports |
| `0c0a48e` | Fix Robin #7 : `flex: 1` + `text-align: center` sur naming → flèches ancrées aux bords, naming centré peu importe sa longueur |
| `10aaeb9` | Fix Robin #8 : élargissement `min-width: 360 → 440px` |

---

### 📦 Architecture finale livrée

**Markup (`front-page.php`)**
- `<section class="homepage-carousel-fullscreen">` → `<div class="carousel-container">` 
- 2 couches superposées :
  - `.carousel-slides` (z:0) : slides image intactes (M22 préservé, slides promo `<a>` ou `<div>`)
  - `.carousel-foreground` (z:2, flex column, pointer-events: none) :
    - `.hero-text-area` (flex: 1, justify-content: flex-end, padding-bottom 24px) → H1 + H2-en-`<p>`
    - `.card-area` (min-height 130 desktop / 90 mobile, justify-content: center) → `.naming-card`
- `.naming-card` contient `<a class="naming-link" id="carousel-naming-link">` (vide, peuplé par JS) + `.card-controls` (flèche prev + dots cachés + flèche next)
- `<script>window.SAPI_CAROUSEL_DATA = …</script>` injecté avec décodage HTML entities

**CSS (`style.css`)**
- Layout 2 zones : `.carousel-foreground` + `.hero-text-area` + `.card-area`
- `.naming-card` : layout horizontal `‹ NAMING ›` partout, glass pur (`background: transparent`, `backdrop-filter: blur(12px)`), bordure crème 55%, `min-width: 440px`
- `.naming-link` : crème, text-shadow doux, `flex: 1`, `text-align: center`, hover orange (override global `a:hover` via spec 0,0,2,0)
- `.naming-link.is-promo` : Square Peg pleine pour les slides promo
- `.product-firstname` / `.product-restname` (sous `.naming-link`) : Montserrat upper / Square Peg
- `.card-controls` en `display: contents` → flèches deviennent siblings du naming, réordonnées via `order`
- `.carousel-arrow` : 32×32 cercle crème, `flex-shrink: 0`
- `.carousel-dots` / `.carousel-dot` : présents dans le DOM pour cohérence mais `display: none` sur la card
- Mobile (≤ 768px) : `min-width: 0`, padding/gap/typo plus petits, arrow 30×30

**JS (`assets/homepage-carousel.js`)**
- Lecture de `window.SAPI_CAROUSEL_DATA` au démarrage
- Helpers `escapeHtml(s)` + `formatProductName(name)` (split firstname/restname inline, reproduit la logique du formatter global sans le polluer)
- `updateNamingCard(index)` : pose innerHTML, classe `is-promo`, href, aria-label, gère URL vide (pointer-events: none)
- `showSlide(index)` appelle `updateNamingCard(index)` à chaque transition
- Init naming card avec slide 0
- Handlers prev/next sur `.carousel-arrow-prev/next` (wrap-around modulo + restart autoplay)
- Swipe tactile sur `.carousel-slides` : touchstart/touchend, seuil 50px, swipe gauche → next, droite → prev
- Pause autoplay au touch (M22) préservée

**PHP**
- `$promo_slides` : ajout du champ ACF `titre` lu depuis le repeater `slides_en_avant`
- `$carousel_slides_data` : tableau name/url/isPromo, exposé au JS via `<script>window.SAPI_CAROUSEL_DATA = …</script>`
- `html_entity_decode()` appliqué sur chaque `name` avant sérialisation (fix `L&Rsquo;Incandescent`)

---

### 🧠 Apprentissages techniques notables

1. **Spec mockup ≠ vrai goût Robin** — Le mockup proposait `align/justify center` sur la zone H1/H2 (centrage vertical). Robin voulait en fait les H1/H2 collés en bas, comme dans l'historique. Le mockup est un point de départ, pas une vérité gravée.

2. **Card stable = `min-width`, pas `width`** — Avec `width` fixe + `white-space: nowrap`, les longs naming débordent. Avec `min-width`, la card grandit naturellement si besoin tout en gardant une taille minimale prévisible.

3. **Centrage avec flèches aux bords = `flex: 1` sur l'item du milieu** — Plutôt que `justify-content: space-between` (qui ne centre pas vraiment), `flex: 1` + `text-align: center` sur le naming ancre les flèches aux bords et garantit un centrage parfait peu importe la longueur du texte.

4. **`display: contents` pour aplatir un wrapper** — Pour réordonner des éléments via `order` sans changer le HTML, mettre le wrapper en `display: contents` flatten ses enfants comme siblings du parent. Pattern propre pour transformer un layout sans modifier le markup.

5. **Entités HTML littérales dans les titres WP** — Certains titres contiennent `&rsquo;` au lieu du caractère apostrophe. Sans `html_entity_decode()` côté serveur, ça remonte tel quel dans le DOM, et la règle globale `text-transform: capitalize` capitalise les lettres après les `&` (word-boundary CSS) → rendu cassé `L&Rsquo;`.

6. **Glass pur (`background: transparent`)** — Effet verre dépoli minimaliste : pas de teinte, juste `backdrop-filter: blur()` + bordure fine. Très discret mais lisibilité dépend du fond. Pour ce projet : combiné à du texte crème + text-shadow pour lisibilité sur photos variées.

7. **Override `:hover` global sur `<a>`** — Le projet a `a:hover { color: var(--color-link-hover) }` (spec 0,0,1,1). Tout `<a>` doit avoir un override explicite `.classe:hover { color: ... }` (spec 0,0,2,0) pour éviter que le bleu link-hover s'affiche au survol.

---

### 📋 État final

- ✅ Architecture 2 zones flex column garantissant zéro chevauchement entre H1/H2 et card
- ✅ H1/H2 collés en bas du `.hero-text-area` (juste au-dessus de la card)
- ✅ Naming card centrée horizontalement, glass pur (transparent + blur 12), texte/contour/dots/flèches en crème
- ✅ Layout `‹ NAMING ›` partout (desktop + mobile), `min-width: 440px` desktop, layout fluide mobile
- ✅ Naming centré peu importe sa longueur (`flex: 1` + `text-align: center`)
- ✅ Slides produits cliquables (M22), aria-label, pointer-events guard
- ✅ Slides promo : titre ACF lu, affiché dans la card en variante `is-promo` (Square Peg pleine)
- ✅ Auto-rotation 5s + handlers manuels (flèches) + swipe tactile mobile (seuil 50px)
- ✅ Pause autoplay au touch (M22) préservée
- ✅ Header transparent + bascule scroll inchangé
- ✅ Carousel 90vh inchangé
- ✅ Bandeau Mon Projet inchangé
- ✅ H1 unique (le `<h1 class="carousel-hero-title">` historique)
- ✅ HTML entities décodées (fix `L&Rsquo;Incandescent`)

**Côté Robin (post-deploy) :** rendre le champ `titre` du repeater ACF `slides_en_avant` **obligatoire** côté admin. Une slide promo sans titre afficherait une card vide.

---

### 📋 Brief original (archivé)

**Date :** 2026-05-09
**Priorité :** haute
**Branche :** `test-theme-sapi-maison`. Workflow standard : commits sur test → Robin valide sur `test.atelier-sapi.fr` → fast-forward merge vers `master` → Robin déclenche le workflow GHA pour la prod.
**Mockup de référence (à recharger) :** `mockups/home-carousel-mini-ajouts/index.html` — version finale validée par Robin. C'est la cible visuelle.

### Contexte

Suite au revert de la refonte hero ambitieuse (8 mai), Robin redemande deux ajouts mais **dans une approche très différente** : pas de bandeau orientation, pas de modification du H1/H2, pas de naming centré. À la place, une **card crème en bas-droite** (centrée bas sur mobile) qui regroupe le naming + la navigation manuelle.

**Validations Robin :**
- Architecture en 2 zones flex column pour garantir zéro chevauchement entre H1+H2 et card.
- Naming dans la card cliquable vers la fiche produit (en plus de la slide entière qui reste cliquable via M22).
- Slides promo : le `titre` ACF (déjà existant) remplace le naming. Robin va rendre le champ `titre` obligatoire côté admin.
- Mobile : card en layout horizontal compact `‹ NAMING ›` (flèches autour du naming, pas de dots), centrée bas.
- Swipe tactile mobile : à implémenter dans la même tâche.

### Ce qu'on NE TOUCHE PAS (verrouillé)

- ❌ Header : reste transparent sur la home + bascule opaque au scroll. **Pas de header opaque permanent.**
- ❌ Carousel : reste à `90vh`. **Pas de `100vh`, pas de `--header-height`.**
- ❌ H1 + H2 contenu et taille : strictement inchangés. Juste leur container CSS change (passe dans `.hero-text-area` au lieu de `.carousel-hero-text` overlay).
- ❌ Slides cliquables M22 : préservées. La slide reste un `<a>` qui pointe vers la fiche.
- ❌ Système slides promo : structure ACF + filtrage + rendu inchangés (sauf qu'on lit aussi `titre` désormais).
- ❌ Bandeau Mon Projet : laissé à sa position actuelle (repositionné par script sous le carousel).

### Lecture préalable obligatoire

1. **Mockup cible** : `mockups/home-carousel-mini-ajouts/index.html` — pour calibrer visuellement (couleurs card, ombres, spacing, mobile compact).
2. `front-page.php` lignes ~313–402 : carousel actuel.
3. `style.css` section carousel (`HOMEPAGE FULLSCREEN CAROUSEL`, ~ligne 11456+) — toutes les règles `.carousel-*`.
4. `assets/homepage-carousel.js` : auto-rotation 5s, handlers dots existants, pause autoplay touch (M22).
5. `assets/product-name-formatter.js` : voir comment le sélecteur formate `.carousel-product-name`. **Important** : on va réutiliser le même mécanisme pour la nouvelle classe `.naming-link` côté card. Ajouter `.naming-link` aux sélecteurs si pas déjà fait, OU faire le split firstname/restname côté JS dans `homepage-carousel.js` au moment de l'update de la card (au choix selon la simplicité).
6. **Mémoire `feedback_overrides_globaux_links.md`** : le projet a deux règles globales sur `<a>` qui peuvent saboter les liens (color hover bleue, overflow caché). La card a un fond CLAIR donc le `<a>` interne (.naming-link) n'a pas le piège du blanc-sur-image, mais bien override `:hover` explicitement quand même.

### Architecture cible (exacte du mockup)

```
.hero-carousel (90vh) [position: relative]
├── .carousel-slides (z-index 0) [position: absolute, inset 0]
│   └── boucle slides — chaque slide est un <a class="carousel-slide">
│       (les slides existantes M22 + les slides promo, structure inchangée)
│
└── .carousel-foreground (z-index 1) [position: absolute, inset 0, flex column, pointer-events: none]
    ├── .hero-text-area [flex: 1, align/justify center]
    │   └── H1 + H2 (contenu inchangé)
    │
    └── .card-area [min-height 130px desktop, 90px mobile, justify-content: flex-end (desktop) / center (mobile)]
        └── .naming-card [pointer-events: auto]
            ├── <a class="naming-link" href="..."> (firstname + restname OU titre promo)
            └── .card-controls [flex row]
                ├── <button class="carousel-arrow carousel-arrow-prev">
                ├── .carousel-dots (un .carousel-dot par slide totale, masqué en mobile)
                └── <button class="carousel-arrow carousel-arrow-next">
```

### À faire — modifications précises

#### 1. `front-page.php` — Refondre la structure du carousel

**1.1 — Construire un tableau de data slides à passer en JS**

Tout en haut du fichier (juste avant `?>`), construire un tableau qui contient les données utilisables côté JS :

```php
$carousel_slides_data = [];

// Slides promo en premier
foreach ($promo_slides as $promo) {
  $carousel_slides_data[] = [
    'name'    => $promo['titre'] ?? '',          // ACF titre (Robin va rendre obligatoire)
    'url'     => $promo['url'] ?? '',
    'isPromo' => true,
  ];
}

// Slides produits ensuite
foreach ($carousel_products as $product) {
  $carousel_slides_data[] = [
    'name'    => $product['name'],
    'url'     => $product['url'],
    'isPromo' => false,
  ];
}
```

**1.2 — Enrichir `$promo_slides` pour relire le `titre`**

Dans la boucle qui construit `$promo_slides` (vers la ligne 90), réajouter `titre` (le champ ACF est encore présent) :

```php
$promo_slides[] = [
  'image_id' => (int) $slide['image'],
  'url'      => trim((string) ($slide['url'] ?? '')),
  'titre'    => trim((string) ($slide['titre'] ?? '')),  // ← AJOUTER
];
```

**1.3 — Refondre le markup `<section class="hero-carousel">`**

L'ancien markup ressemble à :

```html
<section class="homepage-carousel-fullscreen">
  <div class="carousel-container">
    <div class="carousel-slides">
      [boucle slides promo + boucle slides produits avec leur naming bas-droite via .carousel-content]
    </div>
    <div class="carousel-hero-text">  <!-- H1 + H2 overlay -->
      <h1 ...>Luminaires en bois · Atelier Sâpi</h1>
      <h2 ...>Fabriqués à la main, à la commande, dans mon atelier à Lyon</h2>
    </div>
  </div>
</section>
```

Le **nouveau** markup :

```html
<section class="homepage-carousel-fullscreen">
  <div class="carousel-container">

    <!-- Couche 1 : slides (image + cliquabilité M22 inchangée) -->
    <div class="carousel-slides">
      <?php foreach ($promo_slides as $promo) : ?>
        <!-- Slide promo : <a> si url, <div> sinon. NE CONTIENT QUE l'image + overlay,
             plus de bloc texte (le titre passe dans la card globale). -->
        <?php /* … structure existante slide promo SANS le bloc carousel-content … */ ?>
      <?php endforeach; ?>

      <?php foreach ($carousel_products as $product) : ?>
        <!-- Slide produit : <a class="carousel-slide carousel-slide-product" href="..." aria-label="..."> M22 inchangé.
             NE CONTIENT QUE l'image + overlay, plus de .carousel-content (le naming passe dans la card globale). -->
        <?php /* … structure existante slide produit SANS le bloc carousel-content … */ ?>
      <?php endforeach; ?>
    </div>

    <!-- Couche 2 : foreground en flex column -->
    <div class="carousel-foreground">

      <!-- Zone 1 : H1 + H2 -->
      <div class="hero-text-area">
        <h1 class="carousel-hero-title">Luminaires en bois · Atelier Sâpi</h1>
        <p class="carousel-hero-subtitle">Fabriqués à la main, à la commande, dans mon atelier à Lyon</p>
      </div>

      <!-- Zone 2 : card area réservée -->
      <div class="card-area">
        <div class="naming-card">
          <a class="naming-link" href="#" id="carousel-naming-link" aria-label="Découvrir le modèle affiché">
            <!-- Contenu mis à jour par JS au changement de slide -->
          </a>
          <div class="card-controls">
            <button type="button" class="carousel-arrow carousel-arrow-prev" aria-label="Slide précédente">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polyline points="15 18 9 12 15 6"/>
              </svg>
            </button>
            <div class="carousel-dots">
              <?php $total_slides = count($promo_slides) + count($carousel_products); ?>
              <?php for ($i = 0; $i < $total_slides; $i++) : ?>
                <button type="button"
                        class="carousel-dot<?php echo $i === 0 ? ' active' : ''; ?>"
                        aria-label="Aller à la slide <?php echo ($i + 1); ?>"></button>
              <?php endfor; ?>
            </div>
            <button type="button" class="carousel-arrow carousel-arrow-next" aria-label="Slide suivante">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polyline points="9 18 15 12 9 6"/>
              </svg>
            </button>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>
```

**Suppressions importantes** :
- L'ancien `<div class="carousel-content"><p class="carousel-product-name">...</p></div>` dans chaque slide produit : à **supprimer** (le naming passe dans la card globale unique).
- L'ancien `<div class="carousel-hero-text">` overlay autonome : son contenu déménage dans `.hero-text-area`. Garder les classes `.carousel-hero-title` et `.carousel-hero-subtitle` (réutilisées dans la nouvelle structure).

**1.4 — Localiser les data pour le JS**

Juste avant la fermeture du fichier (ou avant le `<script>` qui repositionne le bandeau Mon Projet), injecter les data :

```php
<script>
window.SAPI_CAROUSEL_DATA = <?php echo wp_json_encode($carousel_slides_data); ?>;
</script>
```

Ou idéalement, via `wp_localize_script` dans `functions.php` si c'est plus propre côté theme. Au choix selon ce qui est cohérent avec le reste du projet.

#### 2. `style.css` — Refondre la zone hero du carousel

**2.1 — Ajouter `.carousel-foreground`, `.hero-text-area`, `.card-area`**

À ajouter dans la section carousel (après la règle `.carousel-slides` ~ligne 11475) :

```css
/* Couche overlay en flex column : zone texte + zone card réservées */
.carousel-foreground {
  position: absolute;
  inset: 0;
  z-index: 1;
  display: flex;
  flex-direction: column;
  pointer-events: none; /* laisse passer les clics sur l'image dessous */
}

.hero-text-area {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 0 3rem;
}

.card-area {
  min-height: 130px;
  display: flex;
  align-items: flex-end;
  justify-content: flex-end;
  padding: 0 36px 28px;
}

@media (max-width: 768px) {
  .hero-text-area { padding: 0 1.5rem; }
  .card-area {
    min-height: 90px;
    justify-content: center;
    padding: 0 16px 24px;
  }
}
```

**2.2 — Adapter `.carousel-hero-title` et `.carousel-hero-subtitle`**

Les règles existantes (font-size, color, text-shadow) sont à **conserver**, juste retirer le `position: absolute` et les coordonnées `bottom`/`left` qui ne sont plus pertinents (le positionnement vient maintenant du flex parent `.hero-text-area`). Ces classes deviennent juste des styles de typographie.

**2.3 — Supprimer les règles obsolètes**

À retirer (orphelines après la refonte) :
- `.carousel-hero-text` (le wrapper position: absolute) — plus utilisé
- `.carousel-content` (le wrapper bas-droite) — plus utilisé
- `.carousel-product-name` (le naming bas-droite) — remplacé par `.naming-link`
- `.carousel-product-name .product-firstname` / `.product-restname` peuvent rester (utilisées dans la nouvelle `.naming-link` si on garde le format), ou être renommées sous `.naming-link .product-firstname` / `.naming-link .product-restname`. **Choisir l'option la plus simple** (garder le sélecteur `.product-firstname` / `.product-restname` indépendamment du parent, c'est plus DRY).

**2.4 — Ajouter `.naming-card` + `.naming-link` + `.card-controls`**

```css
/* La card en bas-droite (desktop) / bas-centré (mobile) */
.naming-card {
  pointer-events: auto;
  background: rgba(251, 246, 234, 0.96);
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
  border-radius: 16px;
  padding: 18px 24px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 14px;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.25);
}

/* Le naming dans la card — cliquable */
.naming-link {
  font-size: 1.5rem;
  line-height: 1;
  color: var(--color-wood-dark);
  white-space: nowrap;
  text-decoration: none;
  cursor: pointer;
  transition: color 0.2s var(--ease-smooth);
}

@media (hover: hover) {
  .naming-link:hover {
    color: var(--color-orange);
  }
}

/* Format firstname / restname (slide produit) — réutilise le pattern existant */
.naming-link .product-firstname {
  font-family: var(--font-body, 'Montserrat', sans-serif);
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.18em;
  font-size: 0.55em;
  vertical-align: baseline;
  margin-right: 0.4em;
  opacity: 0.95;
}
.naming-link .product-restname {
  font-family: var(--font-display, 'Square Peg', cursive);
  font-size: 1.6em;
  line-height: 1;
  vertical-align: baseline;
}

/* Slide promo — affichage du titre brut, sans split */
.naming-link.is-promo {
  font-family: var(--font-display, 'Square Peg', cursive);
  font-size: 2rem; /* plus gros que le naming produit pour bien marquer la promo */
}

/* Controls dans la card */
.card-controls {
  display: flex;
  align-items: center;
  gap: 14px;
}

.carousel-arrow {
  width: 32px;
  height: 32px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: rgba(74, 63, 53, 0.06);
  border: 1px solid rgba(74, 63, 53, 0.22);
  border-radius: 50%;
  color: var(--color-wood-dark);
  cursor: pointer;
  padding: 0;
  transition: all 0.3s var(--ease-smooth);
}

@media (hover: hover) {
  .carousel-arrow:hover {
    background: rgba(74, 63, 53, 0.12);
    border-color: rgba(74, 63, 53, 0.4);
  }
}

.carousel-arrow svg {
  width: 16px;
  height: 16px;
  stroke: currentColor;
}

.carousel-dots {
  display: flex;
  gap: 10px;
}

.carousel-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  border: 1.5px solid rgba(139, 115, 85, 0.5);
  background: transparent;
  cursor: pointer;
  padding: 0;
  transition: all 0.3s var(--ease-smooth);
}

.carousel-dot.active {
  background: var(--color-wood);
  border-color: var(--color-wood);
}

@media (hover: hover) {
  .carousel-dot:hover:not(.active) {
    background: rgba(139, 115, 85, 0.3);
  }
}

/* MOBILE — Card horizontale compacte, sans dots */
@media (max-width: 768px) {
  .naming-card {
    flex-direction: row;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    max-width: calc(100% - 8px);
  }

  .naming-card .carousel-dots {
    display: none; /* pas de dots en mobile */
  }

  /* Le card-controls en `display: contents` pour que les flèches deviennent siblings du naming */
  .naming-card .card-controls {
    display: contents;
  }

  /* Réordonner : flèche prev / naming / flèche next */
  .card-controls .carousel-arrow:first-child { order: -1; }
  .card-controls .carousel-arrow:last-child { order: 1; }
  .naming-link {
    order: 0;
    font-size: 1.2rem;
  }
  .naming-link.is-promo {
    font-size: 1.6rem;
  }

  .carousel-arrow {
    width: 30px;
    height: 30px;
    flex-shrink: 0;
  }
  .carousel-arrow svg {
    width: 14px;
    height: 14px;
  }
}
```

#### 3. `assets/homepage-carousel.js` — Logique de mise à jour de la card + swipe

**3.1 — Lire `window.SAPI_CAROUSEL_DATA`** au démarrage de l'init :

```js
const slidesData = window.SAPI_CAROUSEL_DATA || [];
const namingLink = carousel.querySelector('#carousel-naming-link');
```

**3.2 — Fonction `updateNamingCard(index)`** à ajouter :

```js
function formatProductName(name) {
  // Reproduit la logique de product-name-formatter.js : split au premier espace
  // OU délègue : si le formatter gère .naming-link, utiliser sa fonction publique.
  const trimmed = (name || '').trim();
  if (!trimmed) return '';
  const firstSpace = trimmed.indexOf(' ');
  if (firstSpace === -1) {
    return '<span class="product-firstname">' + escapeHtml(trimmed) + '</span>';
  }
  const firstname = trimmed.substring(0, firstSpace);
  const restname  = trimmed.substring(firstSpace + 1);
  return '<span class="product-firstname">' + escapeHtml(firstname) + '</span>'
       + '<span class="product-restname">' + escapeHtml(restname) + '</span>';
}

function escapeHtml(s) {
  return String(s).replace(/[&<>"']/g, function(c) {
    return { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[c];
  });
}

function updateNamingCard(index) {
  if (!namingLink || !slidesData[index]) return;
  const data = slidesData[index];
  if (data.isPromo) {
    namingLink.innerHTML = escapeHtml(data.name);
    namingLink.classList.add('is-promo');
  } else {
    namingLink.innerHTML = formatProductName(data.name);
    namingLink.classList.remove('is-promo');
  }
  // href : si url vide (slide promo sans URL), désactiver le lien
  if (data.url) {
    namingLink.setAttribute('href', data.url);
    namingLink.style.pointerEvents = '';
  } else {
    namingLink.setAttribute('href', '#');
    namingLink.style.pointerEvents = 'none';
  }
  namingLink.setAttribute('aria-label', 'Découvrir ' + data.name);
}
```

**3.3 — Modifier `showSlide(index)`** pour appeler `updateNamingCard` :

```js
function showSlide(index) {
  // ... logique existante (active class, etc.) ...
  updateNamingCard(index);
}
```

**Important** : appeler aussi `updateNamingCard(0)` au démarrage de l'init pour initialiser la card avec la première slide.

**3.4 — Handlers prev/next** : déjà bien définis dans la dernière refonte abandonnée, à reprendre :

```js
const prevBtn = carousel.querySelector('.carousel-arrow-prev');
const nextBtn = carousel.querySelector('.carousel-arrow-next');

if (prevBtn) {
  prevBtn.addEventListener('click', function() {
    const prevIndex = (currentIndex - 1 + slides.length) % slides.length;
    showSlide(prevIndex);
    startAutoRotate();
  });
}

if (nextBtn) {
  nextBtn.addEventListener('click', function() {
    nextSlide();
    startAutoRotate();
  });
}
```

**3.5 — Swipe tactile** sur `.carousel-slides` (ou `.hero-carousel`, à toi de juger) :

```js
let touchStartX = null;
let touchEndX = null;
const SWIPE_THRESHOLD = 50; // px

const swipeTarget = carousel.querySelector('.carousel-slides') || carousel;

swipeTarget.addEventListener('touchstart', function(e) {
  touchStartX = e.changedTouches[0].screenX;
}, { passive: true });

swipeTarget.addEventListener('touchend', function(e) {
  if (touchStartX === null) return;
  touchEndX = e.changedTouches[0].screenX;
  const diff = touchEndX - touchStartX;
  if (Math.abs(diff) > SWIPE_THRESHOLD) {
    if (diff < 0) {
      // swipe gauche → next
      const nextIndex = (currentIndex + 1) % slides.length;
      showSlide(nextIndex);
    } else {
      // swipe droite → prev
      const prevIndex = (currentIndex - 1 + slides.length) % slides.length;
      showSlide(prevIndex);
    }
    startAutoRotate();
  }
  touchStartX = null;
}, { passive: true });
```

**Important** : conserver la pause autoplay existante au `touchstart` (acquis M22) — c'est compatible avec le swipe ci-dessus.

#### 4. `assets/product-name-formatter.js` — vérifier `.naming-link`

Si la stratégie côté JS est de splitter le nom dans `homepage-carousel.js` (option ci-dessus), pas besoin de toucher au formatter. Sinon, ajouter `.naming-link` au tableau des sélecteurs et adapter le formatter pour qu'il appelle au bon moment.

**Recommandation** : faire le split inline dans `homepage-carousel.js` (plus simple, le formatter reste centré sur les sélecteurs statiques).

### Critères de succès

1. **Architecture deux zones** : ouvrir DevTools → vérifier que `.carousel-foreground` est en flex column, que `.hero-text-area` a `flex: 1` et que `.card-area` a `min-height: 130px`. Aucun chevauchement possible entre H1+H2 et card, peu importe la hauteur du H2.
2. **Card en bas-droite (desktop)** : positionnée à droite, fond crème opaque + blur léger, ombre douce. Naming + dots/flèches dedans.
3. **Card en bas-centré (mobile, ≤ 768 px)** : layout horizontal `‹ NAMING ›`, sans dots. Centrée bas. Visible sans chevaucher le H2.
4. **Naming cliquable** : click sur le naming → fiche produit (ou URL promo). Hover desktop → couleur orange.
5. **Image cliquable (M22 préservé)** : click sur l'image (zone hors card) → fiche produit. Acquis M22.
6. **Card mise à jour dynamiquement** : à chaque changement de slide active (auto-rotation, dot, flèche, swipe), le naming et son href sont mis à jour avec les data de la nouvelle slide.
7. **Slide promo** : la card affiche le `titre` ACF (sans split firstname/restname, avec la classe `.is-promo` qui active la typo Square Peg pleine).
8. **Slide promo sans URL** : naming non cliquable (`href="#"` + `pointer-events: none` côté JS).
9. **Flèches et dots** : cliquables, navigation correcte, auto-rotation reprend après interaction.
10. **Swipe tactile mobile** : swipe gauche → slide suivante, swipe droite → précédente. Seuil 50px. Auto-rotation reprend.
11. **H1 + H2 inchangés** : contenu strictement identique, position centrée verticalement dans `.hero-text-area`.
12. **Header inchangé** : transparent par défaut sur la home + bascule opaque au scroll.
13. **Pas de régression** : carousel 90vh, fade entre slides, slides promo opérationnelles, bandeau Mon Projet repositionné, autres pages non touchées.

### Pièges à éviter

- ❌ **Ne pas refaire un bandeau orientation, header opaque permanent, naming centré, pill, ou catégorie cliquable** — Robin a explicitement refusé tout ça lors du revert.
- ❌ **Ne pas modifier les classes des slides** (`carousel-slide`, `carousel-slide-product`, `carousel-slide-promo`) — la cliquabilité M22 doit être préservée à 100%.
- ❌ **Ne pas oublier** d'appeler `updateNamingCard(0)` au démarrage pour initialiser la card avec la 1ère slide.
- ❌ **Override explicite** du `:hover` sur `.naming-link` (mémoire `feedback_overrides_globaux_links.md`) : le projet a une règle globale `a:hover { color: var(--color-link-hover) }`. La règle `.naming-link:hover { color: var(--color-orange) }` doit avoir la spécificité 0,0,2,0 pour vaincre — elle l'a (classe + pseudo-class), donc OK, mais bien la garder.
- ❌ **Ne pas ajouter de `<a>` overlay** par-dessus la slide pour la cliquabilité globale — la slide est déjà un `<a>` (M22), aucun stretched-link à inventer. La card est sibling de `.carousel-slides` (dans `.carousel-foreground`), pas enfant — donc pas de `<a>` imbriqué.

### Côté Robin (post-merge prod)

- Rendre **obligatoire** le champ `titre` du repeater ACF `slides_en_avant` (Robin l'a indiqué — un slide promo ne devrait jamais être créée sans titre).

### Tests fonctionnels après commit

1. Hard refresh sur `test.atelier-sapi.fr` → carousel 90vh, H1+H2 centré (inchangé), card en bas-droite avec Vincent + dots/flèches.
2. Cliquer la flèche `›` → slide suivante, naming dans la card change pour la nouvelle slide.
3. Cliquer un dot → atterrit sur la slide correspondante.
4. Auto-rotation 5s reprend après interaction. Le naming change tout seul.
5. Cliquer le naming dans la card → fiche produit.
6. Cliquer l'image (hors card) → fiche produit (M22).
7. Mobile DevTools (≤ 768 px) : card centrée bas, layout horizontal `‹ NAMING ›`, dots invisibles.
8. Sur mobile : swiper l'écran vers la gauche → slide suivante. Vers la droite → précédente.
9. Activer une slide promo dans l'admin (avec `titre` rempli) → 1ʳᵉ position, card affiche le titre promo en grande typo Square Peg, clic mène à l'URL promo.
10. Slide promo sans URL → naming visible mais non cliquable (cursor default).
11. Vérifier qu'aucune autre page du site n'est affectée (Mes créations, fiches produit, etc.).

Si tout OK : Robin te dit « go prod » → fast-forward merge `test-theme-sapi-maison` → `master` → workflow GHA.

---

## ✅ [TÂCHE] REVERT carousel home — retour à l'état AVANT refonte hero — EXÉCUTÉ (8 mai 2026)

**Statut :** ✅ `test-theme-sapi-maison` reset hard sur `master` (= commit `869cb26`) + force push effectué. Les 11 commits de refonte (`106a31b` → `cbccd15`) sont retirés du remote. 🟡 En attente test Robin sur `test.atelier-sapi.fr`.

### ✅ Retour Claude Code (8 mai 2026)

**Stratégie retenue : reset hard, pas édition chirurgicale**

J'avais initialement proposé une approche en édition manuelle (3 fichiers, des dizaines d'edits, restauration depuis git history). Robin a fait remarquer qu'**un commit existait déjà à l'état souhaité** : `869cb26`, qui était l'HEAD de `master`. À cet état, le code contenait déjà :
- ✅ Bug fade fix (46e972d)
- ✅ Système slides en avant (e61dea2) — mais **avant** que Robin ajoute les sous-champs ACF `titre`/`sous_titre`. La version `869cb26` ne lit que `image_id` + `url`.
- ✅ Cliquabilité M22 complète (7360f4d + 63233fb + 869cb26) — slide en `<a>`, aria-label, pointer-events guard, pause autoplay au touch, pill « Découvrir » retirée
- ✅ Naming bas-droite simple (avec spans firstname/restname rendus inline par le JS formatter)
- ✅ Header transparent + bascule opaque au scroll
- ✅ Carousel 90vh + H1/H2 dans le carousel
- ❌ Pas de bandeau orientation, pas d'image pure, pas de dots/flèches, pas de pill ni lignes hover, pas de catégorie cliquable

Cible parfaite. Reset hard incomparablement plus propre que l'édition chirurgicale.

**Commandes exécutées :**
```bash
# Backup des fichiers locaux non-commitables (queue + snippet Robin)
cp memory/claude_code_queue.md /tmp/queue-backup-XXXX.md
cp snippet-sapi-cookie-popup.php /tmp/snippet-backup-XXXX.php

git checkout test-theme-sapi-maison
git reset --hard master
git push --force-with-lease origin test-theme-sapi-maison

# Restore des fichiers locaux
cp /tmp/queue-backup-XXXX.md memory/claude_code_queue.md
cp /tmp/snippet-backup-XXXX.php snippet-sapi-cookie-popup.php
```

**Vérifications post-reset :**
- ✅ `git rev-parse HEAD` (test) = `git rev-parse master` = `869cb26fa47e1608693d6731cd71595c5441050f`
- ✅ M22 acquis présent : `front-page.php:373` (`$classes = 'carousel-slide carousel-slide-product'`), `front-page.php:376` (`<a aria-label="Découvrir …">`), `style.css:11513` (`.carousel-slide:not(.active) { pointer-events: none }`), `style.css:11706` (`a.carousel-slide-product`)
- ✅ Slides promo system intact : `front-page.php:83` (`$promo_slides = []`), `front-page.php:86` (lecture ACF `slides_en_avant`), `front-page.php:93` (boucle filtrage), `front-page.php:342` (boucle de rendu)
- ✅ H1 unique : `front-page.php:399` (`<h1 class="carousel-hero-title">Luminaires en bois · Atelier Sâpi</h1>`) — le H1 historique dans le carousel, pas celui du bandeau abandonné
- ✅ Aucune classe orpheline de la refonte abandonnée : grep `bandeau-orientation`, `carousel-slide-link`, `carousel-slide-info`, `carousel-pill`, `carousel-product-category`, `carousel-promo-title`, `carousel-arrow-prev/next` → AUCUNE référence
- ✅ Bug fade fix toujours en place (la règle `:first-child` supprimée le 5 mai n'a pas été réintroduite)

**Notes de vérif** : 2 matches secondaires ignorés sur `carousel-controls` :
- `style.css:6447 .products-carousel-controls` — autre feature (grille produits)
- `style.css:7551 .carousel-controls` — `display: flex` générique utilisé ailleurs sur le site (pré-existant)

---

### 🧠 Apprentissage technique notable

**Identifier un commit cible avant de reverter à la main** — Quand une longue série de commits doit être annulée pour revenir à un état antérieur, le réflexe doit être :
1. Inspecter `git log` pour repérer un commit qui correspond exactement à la cible
2. Si trouvé : `git reset --hard <commit>` + `git push --force-with-lease`
3. Sinon seulement, édition chirurgicale

J'ai initialement choisi l'édition chirurgicale (option C de mon plan) parce que je pensais que les acquis M22 + slides promo étaient « mélangés » dans `e61dea2` avec la refonte hero. C'était faux : `e61dea2` ne contenait QUE le système slides promo, et la refonte hero a commencé au commit `106a31b`. L'état `869cb26` (master HEAD) avait déjà tout ce qu'on voulait conserver.

Reflex à intégrer : **toujours commencer par chercher si le passé contient déjà la cible** avant de reconstruire.

---

### 📋 État final sur `test-theme-sapi-maison`

- HEAD = `869cb26` (= master)
- Visuel hero : carousel 90vh, H1+H2 centré-bas par-dessus toutes les slides, header transparent par défaut sur la home, bascule opaque au scroll
- Slides produits : cliquables vers fiche (M22), aria-label, navigation clavier OK
- Slides promo : système opérationnel (image + URL uniquement, sans `titre`/`sous_titre`)
- Naming produit : bas-droite, petit, sans interaction
- Pas de bandeau orientation, pas de dots interactifs, pas de flèches, pas de pill, pas de lignes hover, pas de catégorie cliquable
- Variable CSS `--header-height` : non présente sur master (je ne l'ai pas réintroduite, comme autorisé par la spec)

**À tester par Robin sur `test.atelier-sapi.fr` :**
1. Hard refresh → carousel à 90vh, H1+H2 visibles au centre, header transparent qui devient opaque au scroll
2. Naming produit en bas-droite, petit, sans hover effect
3. Click sur slide produit → fiche produit (M22 OK)
4. Activer une slide promo dans l'admin → 1ʳᵉ position, image pure (sans titre/sous-titre)
5. Bandeau Mon Projet repositionné sous le carousel
6. Naviguer vers `/mes-creations/`, fiche produit, `/sur-mesure/` → header inchangé
7. Plus aucune trace : pas de bandeau orientation, pas de dots, pas de flèches, pas de catégorie cliquable, pas de pill, pas de lignes hover

**Si validation OK → workflow particulier : test == master, donc PAS de merge à faire.** Robin déclenche directement le workflow GHA qui déploie master (`869cb26`) sur `atelier-sapi.fr`.

**Côté Robin (post-deploy) :** retirer dans ACF admin les sous-champs `titre` + `sous_titre` du repeater `slides_en_avant` (devenus inutiles côté code, lecture supprimée).

---

### 📋 Brief original (archivé)

**Date :** 2026-05-07
**Priorité :** haute
**Branche :** `test-theme-sapi-maison`. Workflow : commits sur `test-theme-sapi-maison` → Robin valide sur `test.atelier-sapi.fr` → fast-forward merge vers `master` → Robin déclenche le workflow GHA pour la prod.

### Contexte

Robin a vu le rendu final du carousel après la série de refontes (refonte hero + header opaque + naming/catégorie/pill/lignes/dots/flèches) et a tranché : **il préfère le rendu d'avant**. Trop de friction visuelle, trop éloigné de ce qu'il aimait. Il demande un retour en arrière ciblé.

**À conserver impérativement** (deux chantiers indépendants, business-critical) :

1. **Système slides en avant (`slides_en_avant`)** — repeater ACF + bloc PHP `$promo_slides` + boucle de rendu des slides promo. Permet à Robin d'activer une slide saisonnière (Fête des Mères, Noël, soldes…) en première position avec dates + URL + image. Mergé sur master via commit `e61dea2`.
2. **Cliquabilité des slides produits (M22)** — la slide produit est un `<a>` qui mène à la fiche, avec `aria-label`, `pointer-events: none` sur slides non-actives, pause autoplay au touch mobile. Acquis intégré dans la refonte hero, à préserver.

**À reverter** (tout le visuel hero qui a été ajouté/modifié depuis le 5 mai) :

- Bandeau orientation `<section class="bandeau-orientation">` (H1 + H2 + 2 portes) — à supprimer du PHP et du CSS.
- Header opaque permanent partout — restaurer le comportement transparent au-dessus du carousel + opaque au scroll (`.home .site-header { transparent }` + `.is-scrolled { opaque }` + script JS toggle).
- Carousel `100vh` — restaurer `height: 90vh`.
- H1/H2 retirés du carousel — restaurer la `<div class="carousel-hero-text">` globale au centre-bas du carousel avec H1 « Luminaires en bois · Atelier Sâpi » + H2 « Fabriqués à la main, à la commande, dans mon atelier à Lyon » (texte exact de la prod historique).
- Naming produit centré bas, agrandi, avec catégorie cliquable, pill puis lignes hover — restaurer naming en **bas-droite** simple (`<p class="carousel-product-name">` dans `.carousel-content`), petit, sans catégorie, sans hover effect, sans pill.
- Dots interactifs + flèches prev/next — supprimer le markup (`.carousel-controls`, `.carousel-dot` boutons, `.carousel-arrow-prev/next`). Le JS `homepage-carousel.js` continue de tourner (auto-rotation 5s) ; il cherche des dots et n'en trouvant pas, skip simplement les handlers.
- Variable CSS `--header-height` introduite pour le calc carousel — peut rester dans `:root` (pas gênant) ou être retirée selon la préférence Claude Code.

### Stratégie git

Claude Code décide de la meilleure approche : soit revert chirurgical des fichiers à la main (`front-page.php` + `style.css` + `assets/homepage-carousel.js` éventuellement), soit `git revert` ciblé des commits de refonte avec re-application manuelle des changements à conserver, soit reset hard sur un commit de référence + cherry-pick des deux chantiers à garder. **Le résultat compte plus que la méthode.**

Repères de commits utiles :
- `46e972d` (avant tout) — bug fade fix sur `master`. C'est le dernier état "propre" avant les refontes hero. À garder absolument.
- `e61dea2` — refonte hero (mergé master) qui a mélangé : système slides promo + bandeau orientation + carousel image pure + cliquabilité M22 intégrée. **À splitter** : garder slides promo + M22, défaire le reste.
- Commits sur `test-theme-sapi-maison` post-refonte : header opaque, naming agrandi, hover pill puis ligne, etc. **Tous à défaire.**

### État cible attendu (pour vérification)

**`front-page.php`**
- Bloc PHP `$carousel_products` enrichi avec les 8 produits — **conservé**, mais sans `category_name` ni `category_url` (à retirer si présents — non utilisés dans le rendu cible).
- Bloc PHP `$promo_slides` — **conservé** (filtrage actives + dates), avec `image_id` + `url` uniquement. Les champs `titre` et `sous_titre` ne sont plus lus (Robin supprimera les sous-champs ACF côté admin).
- Markup carousel :
  - `<section class="homepage-carousel-fullscreen">` → `<div class="carousel-container">` → `<div class="carousel-slides">`.
  - Boucle slides promo en `<a>` (si url) ou `<div>` (sinon), juste image + overlay (pas de naming, pas de pill, pas de titre).
  - Boucle slides produits en **`<a class="carousel-slide carousel-slide-product" href="..." aria-label="...">`** (M22 acquis) → image + overlay + `<div class="carousel-content"><p class="carousel-product-name"><?php echo esc_html($product['name']); ?></p></div>` (naming en bas-droite).
  - **Suppression** de `.carousel-slide-link` (overlay stretched), `.carousel-slide-info`, `.carousel-pill`, `.carousel-product-category`, `.carousel-promo-title`, `.carousel-promo-subtitle`.
  - **Suppression** de `<div class="carousel-controls">` (dots + flèches).
- **Suppression** de `<section class="bandeau-orientation">` et tout son contenu (H1, H2, deux portes).
- Hero text global restauré : `<div class="carousel-hero-text">` avec `<h1 class="carousel-hero-title">Luminaires en bois · Atelier Sâpi</h1>` + `<h2 class="carousel-hero-subtitle">Fabriqués à la main, à la commande, dans mon atelier à Lyon</h2>`, placé en bas du `.carousel-container` ou en sibling de `.carousel-slides`, comme dans la version avant la refonte hero.
- Script JS en bas du fichier : **conserver** le repositionnement du bandeau Mon Projet (bloc 1) ET **réintroduire** le toggle `is-scrolled` du header (bloc 2 supprimé lors de "Header opaque permanent").

**`style.css`**
- `.site-header` : restaurer fond `rgba(255, 255, 255, 0.97)` (au lieu de `var(--color-white)`).
- `.home .site-header { position: fixed; background: rgba(255,255,255,0.35) !important; box-shadow: none !important; transition: ... }` — **restaurer**.
- `.home .site-header.is-scrolled { background: rgba(255,255,255,1) !important; box-shadow: 0 2px 8px rgba(0,0,0,0.12) !important }` — **restaurer**.
- `.carousel-container { height: 90vh }` (au lieu de `calc(100vh - var(--header-height))`).
- Restaurer `.carousel-hero-text`, `.carousel-hero-title`, `.carousel-hero-subtitle` (les règles avaient été supprimées lors de la refonte hero — à récupérer depuis git history).
- Restaurer `.carousel-content` (naming bas-droite) avec ses styles d'origine (positionné `bottom`, `right`, font-size petit), et `.carousel-product-name` à sa version d'avant (sans `position: absolute` centré).
- **Supprimer** `.bandeau-orientation`, `.bandeau-h1`, `.bandeau-h2`, `.bandeau-portes`, `.porte`, `.porte--catalogue`, `.porte--conseil`, `.porte-label`, `.porte-title`, `.porte-sub`, `.porte-cta`, `.porte-cta--orange`, `.porte-cta--wood`.
- **Supprimer** `.carousel-slide-link`, `.carousel-slide-info`, `.carousel-pill`, `.carousel-product-category`, `.carousel-promo-title`, `.carousel-promo-subtitle`, `.carousel-controls`, `.carousel-arrow`, `.carousel-arrow-prev`, `.carousel-arrow-next`.
- Variable `--header-height` peut rester (inoffensive) ou être retirée.

**`assets/homepage-carousel.js`**
- Conserver l'auto-rotation 5s, la pause autoplay au touch (acquis M22).
- Retirer les handlers `prevBtn` / `nextBtn` ajoutés à la dernière refonte (les éléments n'existeront plus dans le DOM, mais autant nettoyer).
- Les handlers de dots peuvent rester (ils ne s'attacheront à rien si pas de markup) ou être nettoyés — au choix.

### Ce que Robin fera de son côté (post-merge prod)

- **Supprimer** dans ACF les sous-champs `titre` et `sous_titre` du repeater `slides_en_avant` (devenus inutiles côté code). Les 5 sous-champs restant : `active`, `image`, `url`, `date_debut`, `date_fin`.

### Critères de succès

1. **Visuel hero** : carousel à 90vh, H1+H2 visibles au centre-bas par-dessus toutes les slides, header transparent par défaut sur la home et bascule opaque au scroll. **Strictement comme avant la refonte hero.**
2. **Slides produits cliquables** : un clic sur la slide → fiche produit (acquis M22 préservé), `aria-label` correct, navigation clavier OK.
3. **Slides promo** : système toujours fonctionnel — si Robin active une slide en avant via l'admin, elle apparaît en première position du carousel comme avant. Sans `titre`/`sous_titre` lus, juste image + URL.
4. **Hero text global** : H1 « Luminaires en bois · Atelier Sâpi » + H2 « Fabriqués à la main, à la commande, dans mon atelier à Lyon » au centre-bas du carousel.
5. **Naming produit** : en bas-droite, petit, simple, sans catégorie, sans pill, sans hover effect.
6. **Pas de bandeau orientation, pas de dots interactifs, pas de flèches, pas de `--header-height` calcul carousel** (sauf variable CSS dans `:root` qui peut rester inoffensive).
7. **Pas de régression** sur les autres pages du site (header inchangé partout ailleurs, fiches produit inchangées, etc.).
8. **Bandeau Mon Projet** : continue de se repositionner sous le carousel via le bloc JS 1 (préservé).
9. **Le bug du fade carousel reste corrigé** (la règle `:first-child` supprimée le 5 mai ne doit pas être réintroduite par accident).

### Pièges à éviter

- ❌ **Ne pas reverter** le bug fade fix (`46e972d`) — c'est antérieur et indépendant, à conserver absolument.
- ❌ **Ne pas reverter** le système slides promo (repeater ACF + filtrage PHP `$promo_slides` + boucle promo dans le rendu).
- ❌ **Ne pas reverter** la cliquabilité des slides produits (M22 — `<a>` overlay sur la slide, `aria-label`, `pointer-events`).
- ❌ **Ne pas toucher** au comportement du header sur les autres pages (Mes créations, fiches produit, Sur mesure, etc.) — il était déjà opaque ailleurs et doit le rester.
- ❌ **Ne pas oublier** de réintroduire le script JS `is-scrolled` toggle dans `front-page.php`.

### Tests fonctionnels après commit

1. Hard refresh sur `test.atelier-sapi.fr` → carousel à 90vh, H1+H2 visibles au centre, header transparent qui devient opaque dès qu'on scrolle un peu.
2. Naming produit en bas-droite, petit, sans interaction au hover.
3. Click sur une slide produit → fiche produit (M22 OK).
4. Activer une slide promo dans l'admin → elle apparaît en 1ʳᵉ position avec image (sans titre/sous-titre, image pure).
5. Bandeau Mon Projet toujours repositionné sous le carousel.
6. Naviguer vers `/mes-creations/`, fiche produit, `/sur-mesure/` → header inchangé visuellement.
7. Plus aucune trace des éléments du chantier annulé : pas de bandeau orientation, pas de dots, pas de flèches, pas de catégorie cliquable, pas de pill, pas de lignes hover.

Si tout OK : Robin te dit « go prod » → fast-forward merge `test-theme-sapi-maison` → `master` → workflow GHA. Robin retire ensuite les champs ACF `titre` + `sous_titre` côté admin.

---

## ✅ [TÂCHE] Carousel home — ajustements après test : resserrer le bloc bas, hover ligne au lieu de pill — EXÉCUTÉ + 4 itérations Robin (7 mai 2026)

**Statut :** ✅ Code poussé sur `test-theme-sapi-maison`. Spec initiale livrée commit `2663643`, puis 4 itérations Robin pour finaliser le comportement et le rendu. 🟡 En attente validation Robin sur `test.atelier-sapi.fr`.

**Commits cumulés sur la branche :**
- `2663643` — Spec initiale Cowork (retrait pill, gap réduit, controls remontés, lignes hover)
- `e0aad41` — Fix Robin #1 : trigger hover par texte individuel (pas par la slide entière)
- `5ba86a5` — Fix Robin #2 : couleur bleue au hover du naming (override `a:hover` global)
- `a166a3d` — Fix Robin #3 : ligne invisible sous le naming (override `overflow: hidden` global sur `<a>`)
- `b932820` — Fix Robin #4a : remonter la ligne (`bottom: -6 → -2px`)
- `cbccd15` — Fix Robin #4b : remonter encore (`bottom: -2 → 2px`, juste sous la baseline)

### ✅ Retour Claude Code (7 mai 2026)

**Spec initiale appliquée (commit `2663643`) :**

`front-page.php`
- Suppression du wrapper `<div class="carousel-pill">` autour du naming et du titre promo. Ils deviennent enfants directs de `.carousel-slide-info`.

`style.css`
- Suppression complète du bloc `.carousel-pill` et de son hover.
- `.carousel-slide-info` gap : `12 → 4` desktop, `10 → 4` mobile.
- `.carousel-controls` bottom : `24 → 40` desktop, `18 → 28` mobile.
- `.carousel-product-name` + `.carousel-product-category` + `.carousel-promo-title` reçoivent un `::after` (ligne 1px blanche, width 0 au repos, transition 0.4s) qui pousse au hover.
- `.carousel-product-category` : suppression du `border-bottom` permanent (option B Robin : rien en repos).

---

### 🔁 Itérations Robin post-livraison

**Itération #1 — Trigger hover individuel (commit `e0aad41`)**

Robin a constaté que les deux lignes (naming + catégorie) s'activaient ensemble dès qu'on survolait l'image. Cause : sélecteur `.carousel-slide.active:hover .X::after`. Fix :
- `<p class="carousel-product-name">` → `<a class="carousel-product-name" href="<product-url>">` (le naming devient un lien direct).
- `<p class="carousel-promo-title">` → `<a>` si URL renseignée, `<p>` sinon.
- Sélecteurs hover changés : `.carousel-product-name:hover::after`, `.carousel-product-category:hover::after`, `a.carousel-promo-title:hover::after` (chaque texte déclenche sa propre ligne).
- Ajout `pointer-events: auto`, `text-decoration: none`, `cursor: pointer` sur le naming et `a.carousel-promo-title`.

**Itération #2 — Naming bleu au hover (commit `5ba86a5`)**

Le naming devenait bleu au survol. Cause : règle globale `a:hover { color: var(--color-link-hover) }` (spécificité `0,0,1,1`) battait `.carousel-product-name { color: white }` (spécificité `0,0,1,0`). Fix : ajout de `.carousel-product-name:hover { color: white }` (spécificité `0,0,2,0`) et `a.carousel-promo-title:hover { color: white }`.

La catégorie n'avait pas le bug parce qu'elle a déjà un `:hover` explicite (qui change la couleur en blanc plein).

**Itération #3 — Ligne sous le naming invisible (commit `a166a3d`)**

La ligne sous le naming/titre promo n'apparaissait pas malgré le hover. Cause : règle globale `a, button, ... { overflow: hidden }` (~ligne 237 du style.css, prévue pour l'effet ripple) qui clippait le `::after` positionné à `bottom: -6px` (en dehors du box). Fix : `overflow: visible` sur `.carousel-product-name` et `.carousel-promo-title`.

La catégorie n'avait pas le bug parce que sa ligne est positionnée à `bottom: 0` (dans le padding interne), pas en dehors.

**Itération #4 — Ligne trop basse (commits `b932820` + `cbccd15`)**

Robin trouvait la ligne trop proche de la catégorie en-dessous. Deux passes :
- `b932820` : `bottom: -6px → -2px`
- `cbccd15` : `bottom: -2px → 2px` (la ligne rentre dans le box du texte, juste sous la baseline).

---

### 🧠 Apprentissages techniques notables

1. **Spécificité CSS et overrides globaux** — Le projet a deux règles globales qui interagissent mal avec n'importe quel `<a>` stylé en blanc/coloré sur fond image :
   - `a { color: var(--color-link); text-decoration: none }` (~ligne 686)
   - `a:hover { color: var(--color-link-hover) }` (~ligne 692)
   - `a, button, ... { position: relative; overflow: hidden }` (~ligne 237, pour ripple)

   **À retenir** : pour tout futur `<a>` qui doit rester blanc + afficher un `::after` débordant, prévoir explicitement `:hover { color: ... }` ET `overflow: visible` dès la première implémentation.

2. **Stretched-link + textes cliquables locaux** — Le pattern de stretched-link (lien overlay couvrant la slide) cohabite parfaitement avec des liens locaux (catégorie, naming, titre promo) car ils sont siblings dans le DOM, pas nested. Pas de souci HTML invalide.

3. **`product-name-formatter.js` continue de fonctionner sur `<a>`** — Le formatter détecte les éléments via le sélecteur `.carousel-product-name`, peu importe si c'est `<p>` ou `<a>`. Il fait `target.innerHTML = ...` pour insérer les spans firstname/restname. L'`href` du `<a>` est préservé.

4. **Catégorie sans `border-bottom` permanent (option B)** — Sur mobile (sans hover), l'utilisateur déduit la cliquabilité du contexte (texte secondaire centré sous un naming, conventions de carousel). Validé par Robin.

---

### 📋 État final

- ✅ Pill retirée du markup et du CSS.
- ✅ Naming et catégorie resserrés (gap 4px) et naming agrandi.
- ✅ Contrôles remontés (bottom 40px desktop / 28px mobile).
- ✅ Hover individuel : souris sur naming → ligne sous naming uniquement, souris sur catégorie → ligne sous catégorie uniquement, souris sur image → aucune ligne.
- ✅ Click texte → navigation directe ; click image → navigation via stretched-link overlay.
- ✅ Couleurs blanches verrouillées au hover (override de la règle globale `a:hover`).
- ✅ Lignes visibles (override `overflow: hidden` global).
- ✅ Position ligne fine-tunée (bottom 2px sous la baseline, juste accolée au texte).
- ✅ Slide promo : titre `<a>` cliquable + ligne au hover ; titre `<p>` inerte si pas d'URL ; sous-titre toujours non cliquable.
- ✅ Mobile sans hover : aucun soulignement visible, comportement clean.

**À tester par Robin sur `test.atelier-sapi.fr` :**
1. Hard refresh → naming et catégorie resserrés, contrôles remontés.
2. Souris sur image (zone hors texte) → aucune ligne.
3. Souris sur le naming → ligne fine sous le naming uniquement, texte reste blanc.
4. Souris sur la catégorie → ligne fine sous la catégorie uniquement.
5. Click image → fiche produit ; click naming → fiche produit ; click catégorie → page catégorie.
6. Slide promo avec `titre` + URL en admin → ligne au hover sous le titre.
7. Slide promo avec `titre` mais sans URL → titre inerte, pas de ligne, pas de cursor pointer.
8. Mobile (DevTools responsive) → aucun soulignement, naming + catégorie resserrés.

Si validation OK → "go prod" → fast-forward merge `test-theme-sapi-maison` → `master` → workflow GHA.

---

### 📋 Brief original (archivé)

**Date :** 2026-05-07
**Priorité :** haute
**Branche :** `test-theme-sapi-maison` (déploiement auto sur `test.atelier-sapi.fr`). **Pas de merge direct sur master.** Workflow : commits sur `test-theme-sapi-maison` → Robin valide sur `test.atelier-sapi.fr` → fast-forward merge vers `master` → Robin déclenche le workflow GHA pour la prod.

### Contexte

Robin a vu le rendu sur test après les commits `50b2187` + `4120596`. Il valide la structure (naming agrandi + catégorie cliquable + dots/flèches en pied), mais demande **3 ajustements** :

1. **Resserrer** le gap entre le naming et la catégorie (aujourd'hui trop d'espace, on dirait deux blocs distincts).
2. **Remonter** les contrôles (dots + flèches) dans le carousel — aujourd'hui à `bottom: 24px`, à remonter pour qu'ils respirent davantage.
3. **Remplacer la pill outline** au hover par **deux lignes blanches fines** qui apparaissent : une sous le naming, une sous la catégorie. Plus de pill, plus de border-radius autour du naming.

**Décision Robin (option B confirmée en chat)** : pas de soulignement permanent sur la catégorie en mobile non plus. La ligne n'apparaît qu'au hover desktop, et c'est tout. Sur mobile, le visiteur déduit la cliquabilité du contexte (texte secondaire centré sous un naming).

### À faire — modifications précises

#### 1. `front-page.php` — Retirer le wrapper `.carousel-pill`

Aujourd'hui le markup ressemble à (pour les slides produit) :

```html
<div class="carousel-slide-info">
  <div class="carousel-pill">
    <p class="carousel-product-name">Vincent l'incandescent</p>
  </div>
  <a class="carousel-product-category" href="...">Suspension</a>
</div>
```

Nouveau markup (slide produit) :

```html
<div class="carousel-slide-info">
  <p class="carousel-product-name">Vincent l'incandescent</p>
  <a class="carousel-product-category" href="...">Suspension</a>
</div>
```

Idem pour les slides promo : retirer le wrapper `<div class="carousel-pill">` autour de `<p class="carousel-promo-title">`. Le titre devient enfant direct de `.carousel-slide-info`.

#### 2. `style.css` — Suppression du bloc `.carousel-pill`

Supprimer **entièrement** la règle `.carousel-pill` (et le sélecteur `.carousel-slide.active:hover .carousel-pill` qui révélait la border-color au hover). Plus de pill, plus de border-radius, plus de padding.

#### 3. `style.css` — Resserrer `.carousel-slide-info`

Modifier la règle existante :

```css
/* AVANT (extrait) */
.carousel-slide-info {
  bottom: 100px;
  /* ... */
  gap: 12px;
}

/* APRÈS */
.carousel-slide-info {
  bottom: 100px; /* inchangé */
  /* ... */
  gap: 4px;
}
```

Mobile (`@media (max-width: 768px)`) : passer le gap de 10px à 4px aussi.

#### 4. `style.css` — Remonter les contrôles

```css
/* AVANT */
.carousel-controls {
  bottom: 24px;
  /* ... */
}

/* APRÈS */
.carousel-controls {
  bottom: 40px;
  /* ... */
}
```

Mobile : passer le bottom de 18px à 28px.

#### 5. `style.css` — Lignes au hover sous le naming et la catégorie

**`.carousel-product-name`** (modifier la règle existante) — ajouter `position: relative`, `display: inline-block` (pour que le pseudo-element ne s'étire pas sur toute la largeur du parent flex), et un `::after` qui contient la ligne :

```css
.carousel-product-name {
  /* ...règles existantes (font-size, line-height, color, text-shadow, white-space, etc.)... */
  position: relative;
  display: inline-block;
  margin: 0;
}

.carousel-product-name::after {
  content: '';
  position: absolute;
  left: 50%;
  bottom: -6px;
  width: 0;
  height: 1px;
  background: rgba(255, 255, 255, 0.85);
  transform: translateX(-50%);
  transition: width 0.4s var(--ease-smooth);
}

@media (hover: hover) {
  .carousel-slide.active:hover .carousel-product-name::after {
    width: 70%;
  }
}
```

**`.carousel-product-category`** (modifier la règle existante) — retirer le `border-bottom` permanent, ajouter un `::after` :

```css
.carousel-product-category {
  /* ...règles existantes (font-family, font-size, letter-spacing, color, etc.)... */
  position: relative;
  display: inline-block;
  pointer-events: auto;
  text-decoration: none;
  border-bottom: none; /* SUPPRIMER l'ancien border-bottom permanent */
  padding: 4px 0;
}

.carousel-product-category::after {
  content: '';
  position: absolute;
  left: 50%;
  bottom: 0;
  width: 0;
  height: 1px;
  background: rgba(255, 255, 255, 0.85);
  transform: translateX(-50%);
  transition: width 0.4s var(--ease-smooth);
}

@media (hover: hover) {
  .carousel-slide.active:hover .carousel-product-category::after {
    width: 100%;
  }

  /* Hover spécifique sur la catégorie elle-même : couleur plus marquée + ligne déjà visible */
  .carousel-product-category:hover {
    color: var(--color-white);
  }
}
```

**Note** : le `:hover` direct sur `.carousel-product-category` reste utile (renforce la couleur quand le pointeur est précisément dessus, signal de fin de course).

**`.carousel-promo-title`** — même traitement que le naming produit (ligne au hover de la slide active) :

```css
.carousel-promo-title {
  /* ...règles existantes... */
  position: relative;
  display: inline-block;
  margin: 0;
}

.carousel-promo-title::after {
  content: '';
  position: absolute;
  left: 50%;
  bottom: -6px;
  width: 0;
  height: 1px;
  background: rgba(255, 255, 255, 0.85);
  transform: translateX(-50%);
  transition: width 0.4s var(--ease-smooth);
}

@media (hover: hover) {
  .carousel-slide.active:hover .carousel-promo-title::after {
    width: 70%;
  }
}
```

**`.carousel-promo-subtitle`** — **aucun changement**, pas de ligne au hover (sous-titre non cliquable).

#### 6. JS — aucun changement

`assets/homepage-carousel.js` reste tel quel.

### Critères de succès

1. **Naming et catégorie resserrés** : visuellement les deux lignes paraissent appartenir au même bloc (gap réduit à 4px).
2. **Contrôles remontés** : dots + flèches plus haut dans le carousel, plus d'air en pied.
3. **Hover slide produit active** : deux lignes blanches fines apparaissent — une sous le naming (~70% de sa largeur, centrée), une sous la catégorie (100% de sa largeur). Apparition fluide sur 0.4s. Pas de pill outline visible.
4. **Repos / hors hover** : aucun soulignement sur la catégorie ni sous le naming. Texte « nu » sur l'image.
5. **Mobile (≤ 768 px)** : pas de hover, donc aucun soulignement nulle part. Le visiteur clique/tape directement.
6. **Slide promo avec `titre` rempli** : ligne sous le titre au hover desktop. Le sous-titre n'a jamais de ligne (pas cliquable).
7. **Slide promo sans `titre` ni `sous_titre`** : image pure, comportement inchangé.
8. **Pas de jump de layout** au passage hover/non-hover (la ligne grandit en largeur, pas en hauteur du conteneur).
9. **Pas de régression** sur le stretched link : clic sur la slide → fiche produit, clic sur la catégorie → page catégorie.

### Pièges à éviter

- ❌ **Ne pas inventer** de nouvelles classes — toutes les règles modifient des sélecteurs existants ou ajoutent des `::after` à des classes existantes.
- ❌ **Ne pas oublier** de supprimer les règles `.carousel-pill` du CSS ET le wrapper `<div class="carousel-pill">` du HTML (front-page.php).
- ❌ **Ne pas garder** le `border-bottom` permanent sur la catégorie — Robin a tranché option B (rien en repos).
- ❌ **Ne pas modifier** le sous-titre des slides promo (`.carousel-promo-subtitle`) — pas de hover effect dessus.

### Tests fonctionnels après commit

1. Hard refresh sur `test.atelier-sapi.fr` → naming et catégorie collés (gap visible mais minimal). Pas de pill autour du naming. Catégorie sans soulignement permanent.
2. Hover desktop sur slide active → deux lignes apparaissent, une sous chaque texte, en transition fluide.
3. Mouse out → les lignes se rétractent vers leur centre.
4. Clic sur l'image → fiche produit.
5. Clic sur la catégorie → page catégorie.
6. Mobile (DevTools responsive ≤ 768 px) → aucun soulignement, naming + catégorie resserrés, contrôles plus haut.
7. Slide promo avec titre + sous-titre dans l'admin → ligne au hover sous le titre uniquement, pas sous le sous-titre.

Si tout OK → "go prod" → fast-forward merge `test-theme-sapi-maison` → `master` → workflow GHA.

---

## ✅ [TÂCHE] Carousel home — naming agrandi + catégorie cliquable + hover pill + dots/flèches manuels — EXÉCUTÉ (7 mai 2026)

**Statut :** ✅ Code poussé sur `test-theme-sapi-maison` (commits `50b2187` + `4120596`). 🟡 En attente test Robin sur `test.atelier-sapi.fr`.

### ✅ Retour Claude Code (7 mai 2026, commits `50b2187` + `4120596`)

**Fichiers modifiés (3) :**

**`front-page.php`**
- `$carousel_products` enrichi : `category_name` (mappé via tableau `$category_singular` hardcodé `suspensions → Suspension`, etc.) + `category_url` (via `get_term_link()`).
- `$promo_slides` enrichi : `titre` + `sous_titre` (les 2 nouveaux sous-champs ACF du repeater `slides_en_avant`).
- Markup refondu en **stretched-link pattern** : la slide redevient un `<div>`, un `<a class="carousel-slide-link">` en `position: absolute; inset: 0; z-index: 1` couvre toute la zone pour la cliquabilité globale. La catégorie a son propre `<a>` avec `pointer-events: auto` qui surclasse l'overlay.
  - Slide produit : `<div.carousel-slide-info>` ⊃ `<div.carousel-pill>` ⊃ `<p.carousel-product-name>` + `<a.carousel-product-category>`.
  - Slide promo : `<div.carousel-slide-info>` ⊃ `<div.carousel-pill>` ⊃ `<p.carousel-promo-title>` + `<p.carousel-promo-subtitle>` (les deux conditionnels). Si `titre` ET `sous_titre` vides → image pure (pas de `.carousel-slide-info` rendu).
- Nouveau `<div class="carousel-controls">` au pied du carousel : flèche prev (button) + `<div.carousel-dots>` (boutons générés par `for ($i = 0; $i < $total_slides; $i++)`) + flèche next. Pattern `‹ ● ● ● ● ● ›` centré.

**`style.css`**
- Suppression des règles **orphelines** `a.carousel-slide-promo` et `a.carousel-slide-product` (la slide n'est plus un `<a>`) — les link styles sont sur `.carousel-slide-link`.
- Nouveau bloc `.carousel-slide-info` wrapper centré bas (`bottom: 100px`, flex column, gap 12px). `pointer-events: none` par défaut, les enfants interactifs (catégorie) remettent `auto`.
- `.carousel-pill` : padding 14×32, border 1.5px transparent, transition border-color. **Hover de la slide active** révèle `border-color: rgba(255,255,255,0.85)` (sélecteur `.carousel-slide.active:hover .carousel-pill`).
- `.carousel-product-name` : `font-size: clamp(28px, 3.2vw, 48px)` — agrandi vs précédent `clamp(26px, 2.8vw, 42px)`. Le `pointer-events: none` est désormais hérité du wrapper info.
- `.carousel-product-category` : pill underline blanche, `pointer-events: auto`, hover qui renforce le contraste.
- `.carousel-promo-title` : Square Peg, `clamp(36px, 4.5vw, 64px)`. `.carousel-promo-subtitle` : Montserrat uppercase letter-spacing 0.15em.
- `.carousel-slide-link` : stretched link (`inset: 0`, `z-index: 1`).
- Suppression de l'ancien `.carousel-dots` `position: absolute` autonome.
- Nouveau `.carousel-controls` (`bottom: 24px`, flex gap 24, `pointer-events: none` sur wrapper, `auto` sur enfants).
- Flèches : 36×36 cercle, fond `rgba(255,255,255,0.12)`, bord `rgba(255,255,255,0.35)`, hover bg 0.22 / border 0.7. SVG 18×18 stroke `currentColor`.
- Dots refondus : 10×10 (pas 12×12), bord 1.5px (pas 2px), gap 12.
- Mobile (≤ 768 px) : `.carousel-slide-info` bottom 80, pill 10×22, naming `clamp(22, 5.5vw, 32)`, promo-title `clamp(28, 7vw, 44)`, category 10/0.15em ; controls bottom 18 gap 16, arrow 32×32, svg 14×14, dots gap 10.

**`assets/homepage-carousel.js`**
- `prevBtn` / `nextBtn` captés via `.carousel-arrow-prev` / `.carousel-arrow-next`.
- Handler prev : `(currentIndex - 1 + slides.length) % slides.length` → wrap-around propre.
- Handler next : réutilise `nextSlide()` existant.
- Auto-rotation redémarre après chaque clic, comme pour les dots.

**Conformité brief :**
- ✅ Naming agrandi (clamp 28-48 vs 26-42 avant) et monté (bottom 100 vs 60 avant).
- ✅ Catégorie cliquable sous le naming, libellé singulier (`Suspension` etc.), pointe vers `/categorie-produit/{slug}/`.
- ✅ Hover pill desktop sur slide active (border-color transition fluide, padding interne déjà réservé donc pas de jump de layout).
- ✅ Stretched link : un seul `<a>` overlay capte les clics génériques, la catégorie a son propre `<a>` avec `pointer-events: auto`. Pas de `<a>` imbriqué.
- ✅ Dots boutons (a11y clavier), un par slide globale.
- ✅ Flèches prev/next groupées avec dots au pied, pas sur les côtés.
- ✅ Slides promo : `titre` + `sous_titre` affichés conditionnellement, image pure si les deux vides.
- ✅ `.carousel-overlay`, `.carousel-slide`, `.carousel-slide-img`, `.carousel-slide-product`, `.carousel-slide-promo` non touchées.
- ✅ Acquis M22 préservé : `pointer-events: none` sur slides non-actives, pause autoplay au touch mobile.
- ✅ H1 unique (dans `.bandeau-h1`), pas de duplication.
- ✅ `product-name-formatter.js` non touché — `.carousel-product-name` est dans ses sélecteurs depuis la phase A.

**🔧 Ajustement post-implémentation Robin (commit `4120596`)**

Robin a demandé de retirer le `text-transform: lowercase` sur `.carousel-product-name .product-restname`. Le restname affiche maintenant la capitalisation naturelle du titre WP (donc `L'incandescent`, `Le Dahlia`, pas `l'incandescent`). Le mockup avait du lowercase parce que le HTML était écrit en minuscule en dur ; sur les vrais titres WP, Robin préfère respecter la casse d'origine.

**À tester par Robin sur `test.atelier-sapi.fr` :**
1. Hard refresh → naming gros + centré + monté ; catégorie sous ; dots + flèches en bas centré.
2. Hover sur le naming d'une slide produit → pill outline blanche apparaît avec transition.
3. Clic sur l'image (zone hors catégorie) → fiche produit.
4. Clic sur la catégorie → page catégorie correspondante.
5. Clic flèche gauche / droite → slide précédente / suivante avec wrap-around et fade existant.
6. Clic sur un dot → atterrit sur la slide correspondante.
7. Auto-rotation 5s reprend après interaction manuelle.
8. Mobile (≤ 768 px) : naming lisible, contrôles compacts mais cliquables.
9. Slide promo avec `titre` + `sous_titre` configurés en admin → s'affichent à la place du naming/catégorie.
10. Slide promo sans `titre` ni `sous_titre` → image pure, comme avant.

Si validation OK → "go prod" → fast-forward merge `test-theme-sapi-maison` → `master` → workflow GHA.

---

### 📋 Brief original (archivé)

**Date :** 2026-05-07
**Priorité :** haute
**Branche :** `test-theme-sapi-maison` (déploiement auto sur `test.atelier-sapi.fr`). **Pas de merge direct sur master.** Workflow : commits sur `test-theme-sapi-maison` → Robin valide sur `test.atelier-sapi.fr` → fast-forward merge vers `master` → Robin déclenche le workflow GHA pour la prod.

### Contexte

Évolution du carousel hero (suite à la tâche header opaque déjà mergée). Robin veut :
1. Naming produit **plus grand** et **un peu plus haut**, avec la **catégorie cliquable** juste en dessous.
2. Au **hover desktop** sur le naming : un **pill outline** apparaît autour du naming (border fine blanche, border-radius 50px). La slide entière reste cliquable comme aujourd'hui — la pill n'est qu'un signal visuel.
3. Des **dots** (déjà gérés en JS, juste markup à ajouter) **et des flèches** gauche/droite pour navigation manuelle, **placées côte à côte au pied du carousel** (pattern `‹ ● ● ● ● ● ›`), pas sur les côtés du carousel.
4. Côté **slides promo** (le repeater `slides_en_avant`) : Robin a ajouté **2 nouveaux sous-champs ACF** `titre` et `sous_titre`. Quand ils sont remplis, ils s'affichent à la place du naming/catégorie sur la slide promo.

### ACF — déjà en place côté admin (par Robin)

Le repeater `slides_en_avant` (page Accueil) a maintenant **7 sous-champs**, dans cet ordre :

| # | Nom | Type | Notes |
|---|---|---|---|
| 1 | `active` | True/False | Default coché |
| 2 | `image` | Image | Obligatoire. Return Format = ID |
| 3 | `titre` | Text | **NOUVEAU**. Optionnel. Texte d'en-tête de la slide promo. |
| 4 | `sous_titre` | Text | **NOUVEAU**. Optionnel. Texte secondaire sous le titre. Non cliquable individuellement. |
| 5 | `url` | URL | Optionnel. Vide = slide non cliquable. |
| 6 | `date_debut` | Date Picker | Return format `Y-m-d`. Optionnel. |
| 7 | `date_fin` | Date Picker | Return format `Y-m-d`. Optionnel. |

### Lecture préalable obligatoire

1. **Mockup de référence** : `mockups/home-deux-portes/index.html` — donne l'esprit visuel (naming centré bas, pill outline, dots discrets).
2. **`front-page.php`** lignes ~313–402 : carousel actuel (slides promo, slides produit, naming).
3. **`style.css`** :
   - Section carousel (~11456+) — toutes les règles `.carousel-*`.
   - **Référence pour les flèches** : `.gallery-nav` / `.gallery-nav-prev` / `.gallery-nav-next` (~ligne 19002+) — style minimal SVG flèche couleur wood. À adapter en **blanc translucide** pour fond image du carousel home.
4. **`assets/homepage-carousel.js`** : déjà des handlers pour les dots (`dots.forEach((dot, index) => dot.addEventListener('click', ...))`). Le markup des dots n'existe pas encore dans le DOM, donc le JS ne fait rien actuellement. À l'ajouter côté PHP.
5. **`assets/product-name-formatter.js`** : confirmer que `.carousel-product-name` est bien dans les sélecteurs (devrait l'être suite à la refonte hero).

### À faire — modifications précises

#### 1. `front-page.php` — Enrichir `$carousel_products` avec catégorie

Trouver la boucle qui construit `$carousel_products` (~lignes 12–80). Ajouter les champs **`category_name`** (singulier) et **`category_url`** dans chaque entrée.

Mapping slug → nom singulier (à hardcoder) :

```php
$category_singular = [
  'suspensions'   => 'Suspension',
  'appliques'     => 'Applique',
  'lampesaposer'  => 'Lampe à poser',
  'lampadaires'   => 'Lampadaire',
];

// Dans la boucle où chaque produit est ajouté :
$cat_term = get_term_by('slug', $cat_slug, 'product_cat');
$products_by_category[$cat_slug][] = [
  // ...champs existants...
  'category_name' => $category_singular[$cat_slug] ?? '',
  'category_url'  => $cat_term ? get_term_link($cat_term) : '',
];
```

#### 2. `front-page.php` — Enrichir `$promo_slides` avec `titre` et `sous_titre`

Dans la boucle de filtrage des slides promo (~lignes 110+), ajouter `titre` et `sous_titre` dans le tableau filtré :

```php
$promo_slides[] = [
  'image_id'   => (int) $slide['image'],
  'url'        => trim((string) ($slide['url'] ?? '')),
  'titre'      => trim((string) ($slide['titre'] ?? '')),
  'sous_titre' => trim((string) ($slide['sous_titre'] ?? '')),
];
```

#### 3. `front-page.php` — Refondre le markup des slides

**Pattern : stretched link** (au lieu de la slide-en-`<a>` actuelle). La slide redevient un `<div>`, et un `<a>` overlay en `position:absolute; inset:0; z-index:1` couvre toute la zone pour la cliquabilité globale. La catégorie a son propre `<a>` avec `z-index:2` qui intercepte le clic local.

**Slide produit (avant) :**

```html
<a class="carousel-slide carousel-slide-product..." href="..." aria-label="...">
  <img>
  <div class="carousel-overlay"></div>
  <p class="carousel-product-name">...</p>
</a>
```

**Slide produit (après) :**

```html
<div class="carousel-slide carousel-slide-product<?php echo $is_first ? ' active' : ''; ?>">
  <a class="carousel-slide-link" href="<?php echo esc_url($product['url']); ?>"
     aria-label="Découvrir <?php echo esc_attr($product['name']); ?>"></a>
  <?php echo wp_get_attachment_image($product['image_id'], 'full', false, $img_attr); ?>
  <div class="carousel-overlay"></div>
  <div class="carousel-slide-info">
    <div class="carousel-pill">
      <p class="carousel-product-name"><?php echo esc_html($product['name']); ?></p>
    </div>
    <?php if (!empty($product['category_name']) && !empty($product['category_url'])) : ?>
      <a class="carousel-product-category" href="<?php echo esc_url($product['category_url']); ?>">
        <?php echo esc_html($product['category_name']); ?>
      </a>
    <?php endif; ?>
  </div>
</div>
```

Note : le splitting firstname/restname continue d'être fait côté JS par `product-name-formatter.js` qui détecte `.carousel-product-name`.

**Slide promo (avant) :**

```html
<?php if ($has_url) : ?>
  <a class="carousel-slide carousel-slide-promo..." href="...">
<?php else : ?>
  <div class="carousel-slide carousel-slide-promo...">
<?php endif; ?>
  <img>
  <div class="carousel-overlay"></div>
<?php /* fermeture conditionnelle */ ?>
```

**Slide promo (après) :**

```html
<div class="carousel-slide carousel-slide-promo<?php echo $is_first ? ' active' : ''; ?>">
  <?php if ($promo['url'] !== '') : ?>
    <a class="carousel-slide-link" href="<?php echo esc_url($promo['url']); ?>"
       aria-label="<?php echo esc_attr($promo['titre'] ?: 'Voir l\'offre'); ?>"></a>
  <?php endif; ?>
  <?php echo wp_get_attachment_image($promo['image_id'], 'full', false, $img_attr); ?>
  <div class="carousel-overlay"></div>
  <?php if ($promo['titre'] !== '' || $promo['sous_titre'] !== '') : ?>
    <div class="carousel-slide-info">
      <?php if ($promo['titre'] !== '') : ?>
        <div class="carousel-pill">
          <p class="carousel-promo-title"><?php echo esc_html($promo['titre']); ?></p>
        </div>
      <?php endif; ?>
      <?php if ($promo['sous_titre'] !== '') : ?>
        <p class="carousel-promo-subtitle"><?php echo esc_html($promo['sous_titre']); ?></p>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>
```

#### 4. `front-page.php` — Ajouter dots + flèches au pied du carousel

Juste après la fermeture de `</div>` du `.carousel-slides` (avant la fermeture de `.carousel-container`), insérer :

```html
<div class="carousel-controls">
  <button type="button" class="carousel-arrow carousel-arrow-prev" aria-label="Slide précédente">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
      <polyline points="15 18 9 12 15 6"/>
    </svg>
  </button>
  <div class="carousel-dots">
    <?php for ($i = 0; $i < $total_slides; $i++) : ?>
      <button type="button"
              class="carousel-dot<?php echo $i === 0 ? ' active' : ''; ?>"
              aria-label="Aller à la slide <?php echo ($i + 1); ?>"></button>
    <?php endfor; ?>
  </div>
  <button type="button" class="carousel-arrow carousel-arrow-next" aria-label="Slide suivante">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
      <polyline points="9 18 15 12 9 6"/>
    </svg>
  </button>
</div>
```

`$total_slides` est déjà calculé en haut du carousel (refonte hero précédente). Les dots sont des `<button>` (pas des `<span>`) pour l'accessibilité clavier.

#### 5. `style.css` — Naming agrandi, plus haut, et `.carousel-slide-info` wrapper

Remplacer la règle existante `.carousel-product-name` (~ligne 11535) par :

```css
/* Bloc info en bas de slide — wrapper centré qui contient pill + catégorie */
.carousel-slide-info {
  position: absolute;
  bottom: 100px; /* monté par rapport à l'ancien 60px */
  left: 50%;
  transform: translateX(-50%);
  z-index: 2;
  text-align: center;
  pointer-events: none; /* le link overlay capte les clics — on les remet sur les éléments interactifs */
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}

/* Pill — wrapper du naming, bordure révélée au hover */
.carousel-pill {
  display: inline-flex;
  align-items: baseline;
  padding: 14px 32px;
  border: 1.5px solid transparent;
  border-radius: 50px;
  transition: border-color 0.3s var(--ease-smooth), background 0.3s var(--ease-smooth);
  white-space: nowrap;
}

@media (hover: hover) {
  .carousel-slide.active:hover .carousel-pill {
    border-color: rgba(255, 255, 255, 0.85);
  }
}

/* Naming produit — agrandi par rapport à la version précédente */
.carousel-product-name {
  margin: 0;
  color: var(--color-white);
  font-size: clamp(28px, 3.2vw, 48px); /* avant : clamp(20px, 2.2vw, 32px) */
  line-height: 1;
  white-space: nowrap;
  text-shadow: 0 2px 18px rgba(0, 0, 0, 0.55);
}

.carousel-product-name .product-firstname {
  font-family: var(--font-body, 'Montserrat', sans-serif);
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.18em;
  font-size: 0.55em;
  vertical-align: baseline;
  margin-right: 0.4em;
  opacity: 0.95;
}

.carousel-product-name .product-restname {
  font-family: var(--font-display, 'Square Peg', cursive);
  font-size: 1.5em;
  line-height: 1;
  vertical-align: baseline;
  text-transform: lowercase;
}

/* Catégorie cliquable — sous la pill */
.carousel-product-category {
  pointer-events: auto; /* intercepte le clic local, surclasse le link overlay */
  display: inline-block;
  font-family: var(--font-body, 'Montserrat', sans-serif);
  font-size: 11px;
  font-weight: 500;
  letter-spacing: 0.2em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.85);
  text-decoration: none;
  padding: 4px 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.35);
  transition: color 0.3s var(--ease-smooth), border-color 0.3s var(--ease-smooth);
}

@media (hover: hover) {
  .carousel-product-category:hover {
    color: var(--color-white);
    border-color: rgba(255, 255, 255, 0.9);
  }
}

/* Slides promo — titre + sous-titre */
.carousel-promo-title {
  margin: 0;
  font-family: var(--font-display, 'Square Peg', cursive);
  font-size: clamp(36px, 4.5vw, 64px);
  line-height: 1;
  color: var(--color-white);
  white-space: nowrap;
  text-shadow: 0 2px 18px rgba(0, 0, 0, 0.55);
}

.carousel-promo-subtitle {
  margin: 0;
  font-family: var(--font-body, 'Montserrat', sans-serif);
  font-size: clamp(13px, 1.2vw, 16px);
  font-weight: 500;
  letter-spacing: 0.15em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.85);
  text-shadow: 0 2px 12px rgba(0, 0, 0, 0.5);
  pointer-events: none; /* explicit, pas cliquable */
}

/* Stretched link overlay — couvre toute la slide pour cliquabilité globale */
.carousel-slide-link {
  position: absolute;
  inset: 0;
  z-index: 1;
  display: block;
  text-decoration: none;
  color: inherit;
  cursor: pointer;
}

@media (max-width: 768px) {
  .carousel-slide-info { bottom: 80px; gap: 10px; }
  .carousel-pill { padding: 10px 22px; }
  .carousel-product-name { font-size: clamp(22px, 5.5vw, 32px); }
  .carousel-promo-title { font-size: clamp(28px, 7vw, 44px); }
  .carousel-product-category { font-size: 10px; letter-spacing: 0.15em; }
}
```

#### 6. `style.css` — Dots + flèches groupés en pied de carousel

Remplacer la règle `.carousel-dots` existante (~ligne 11651) par un bloc unifié `.carousel-controls`. **Conserver** les classes `.carousel-dot` et `.carousel-dot.active` mais les adapter (boutons et non spans).

```css
/* Contrôles unifiés au pied du carousel : flèche prev — dots — flèche next */
.carousel-controls {
  position: absolute;
  bottom: 24px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 4;
  display: flex;
  align-items: center;
  gap: 24px;
  pointer-events: none; /* le wrapper laisse passer, les enfants captent */
}

.carousel-arrow,
.carousel-dot {
  pointer-events: auto;
}

/* Flèches — style minimal inspiré .gallery-nav, blanc translucide pour image */
.carousel-arrow {
  width: 36px;
  height: 36px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.12);
  border: 1px solid rgba(255, 255, 255, 0.35);
  border-radius: 50%;
  color: rgba(255, 255, 255, 0.95);
  cursor: pointer;
  padding: 0;
  transition: background 0.3s var(--ease-smooth), border-color 0.3s var(--ease-smooth);
  -webkit-tap-highlight-color: transparent;
}

@media (hover: hover) {
  .carousel-arrow:hover {
    background: rgba(255, 255, 255, 0.22);
    border-color: rgba(255, 255, 255, 0.7);
  }
}

.carousel-arrow svg {
  width: 18px;
  height: 18px;
  stroke: currentColor;
}

/* Dots — déjà existants, on bascule de span à button */
.carousel-dots {
  display: flex;
  gap: 12px;
}

.carousel-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  border: 1.5px solid rgba(255, 255, 255, 0.7);
  background: transparent;
  cursor: pointer;
  padding: 0;
  transition: background 0.3s var(--ease-smooth);
}

.carousel-dot.active {
  background: var(--color-white);
}

@media (hover: hover) {
  .carousel-dot:hover:not(.active) {
    background: rgba(255, 255, 255, 0.4);
  }
}

@media (max-width: 768px) {
  .carousel-controls { bottom: 18px; gap: 16px; }
  .carousel-arrow { width: 32px; height: 32px; }
  .carousel-arrow svg { width: 14px; height: 14px; }
  .carousel-dots { gap: 10px; }
}
```

**Supprimer l'ancien bloc `.carousel-dots` autonome** (positionné `position: absolute; bottom: 22px;`) — il est remplacé par `.carousel-controls`.

#### 7. `assets/homepage-carousel.js` — Handler prev/next

Dans la fonction `init()`, après la déclaration `dots`, ajouter :

```js
const prevBtn = carousel.querySelector('.carousel-arrow-prev');
const nextBtn = carousel.querySelector('.carousel-arrow-next');
```

Et après la boucle des handlers de dots, ajouter les handlers de flèches :

```js
if (prevBtn) {
  prevBtn.addEventListener('click', function() {
    const prevIndex = (currentIndex - 1 + slides.length) % slides.length;
    showSlide(prevIndex);
    startAutoRotate();
  });
}

if (nextBtn) {
  nextBtn.addEventListener('click', function() {
    nextSlide();
    startAutoRotate();
  });
}
```

`nextSlide()` existe déjà dans le fichier. La logique est cohérente avec les handlers de dots existants (qui appellent `showSlide(index) + startAutoRotate()`).

### Critères de succès

1. **Slide produit** : naming centré, plus grand qu'avant, monté par rapport au pied. Catégorie discrète juste sous, cliquable et envoie sur `/categorie-produit/{slug}/`.
2. **Hover desktop sur slide produit active** : pill outline blanche apparaît autour du naming (transition fluide). Le naming lui-même ne bouge pas (padding interne déjà réservé). Aucun jump de layout.
3. **Stretched link** : un clic n'importe où sur la slide (sauf sur la catégorie) → fiche produit. Un clic sur la catégorie → page catégorie. Pas de double déclenchement, pas de `<a>` imbriqué dans le HTML.
4. **Slide promo** : si `titre` rempli → pill avec le titre dans la même position que le naming produit. Si `sous_titre` rempli → s'affiche sous, non cliquable. Si les deux vides → image pure.
5. **Dots** : un par slide globale (`$total_slides`), cliquables, le dot actif a fond blanc, les autres sont en bordure.
6. **Flèches** : prev à gauche des dots, next à droite. Click → slide précédente / suivante avec wrap-around. Auto-rotation reprend après clic.
7. **Position des contrôles** : tout est groupé `‹ ● ● ● ● ● ›` centré au pied du carousel, avec un peu d'air entre les flèches et les dots.
8. **Mobile (≤ 768 px)** : tout reste visible et accessible au toucher (cible tactile minimum), naming ajusté, contrôles plus compacts.
9. **Accessibilité** : `aria-label` sur l'overlay link, sur la catégorie (auto via le texte), sur chaque dot et chaque flèche. Tab clavier passe sur l'overlay puis la catégorie de la slide active uniquement (les autres slides ont `pointer-events: none`).
10. **Pas de régression** sur le bug du fade, le carousel auto-rotation, le bandeau orientation, ou le bandeau Mon Projet.
11. **`product-name-formatter.js`** : continue de splitter `.carousel-product-name` en `.product-firstname` + `.product-restname`. Si pas le cas, ajouter le sélecteur.

### Pièges à éviter

- ❌ **Ne pas inventer** de nouvelles classes hors de cette liste : `carousel-slide-link`, `carousel-slide-info`, `carousel-pill`, `carousel-product-category`, `carousel-promo-title`, `carousel-promo-subtitle`, `carousel-controls`, `carousel-arrow`, `carousel-arrow-prev`, `carousel-arrow-next`.
- ❌ **Ne pas modifier** le markup ni le CSS du **bandeau orientation** (Phase A déjà mergée).
- ❌ **Ne pas toucher** à `.carousel-overlay`, `.carousel-slide`, `.carousel-slide-img`, `.carousel-slide-product`, `.carousel-slide-promo` (sauf retirer le `<a>` direct sur la slide produit pour passer au stretched link).
- ❌ **Ne pas oublier** le mapping `category_name` (singulier) — pas `Suspensions` mais `Suspension`.
- ❌ **Ne pas dupliquer** un `<h1>` ou `<h2>` dans le markup — le naming reste un `<p>`, le H1 unique est dans le bandeau orientation.
- ❌ **Ne pas inventer** un nouveau JS de carousel — réutiliser `homepage-carousel.js` qui fonctionne déjà.

### Tests fonctionnels après commit

1. Hard refresh sur `test.atelier-sapi.fr` → naming agrandi, catégorie sous, dots + flèches en bas centré.
2. Hover sur le naming d'une slide produit → pill outline apparaît avec transition douce.
3. Clic sur l'image (zone hors catégorie/pill) → fiche produit.
4. Clic sur la catégorie → page catégorie correspondante (`/categorie-produit/suspensions/` etc.).
5. Clic flèche gauche/droite → slide précédente/suivante avec animation fade existante.
6. Clic sur un dot → atterrit sur la slide correspondante.
7. Auto-rotation toutes les 5s reprend correctement après interaction manuelle.
8. Mobile (≤ 768 px) : naming lisible, contrôles compacts mais cliquables.
9. Slide promo avec `titre` + `sous_titre` configurés en admin → s'affichent à la place du naming/catégorie.
10. Slide promo sans `titre` ni `sous_titre` → image pure, comme aujourd'hui.

Si tout OK : Robin te dit « go prod » → fast-forward merge `test-theme-sapi-maison` → `master` → workflow GHA.

---

## ✅ [TÂCHE] Header opaque permanent + carousel plein écran sous header — EXÉCUTÉ (7 mai 2026)

**Statut :** ✅ Code poussé sur `test-theme-sapi-maison` (commit `4a017b7`). 🟡 En attente test Robin sur `test.atelier-sapi.fr`.

### ✅ Retour Claude Code (7 mai 2026, commit `4a017b7`)

**Fichiers modifiés (2) :**
- `style.css` :
  - `:root` → ajout de `--header-height: 74px` (desktop)
  - `@media (max-width: 768px) :root` → override `--header-height: 68px` (mobile)
  - `.site-header` : `background: rgba(255,255,255,0.97)` → `var(--color-white, #fff)` — blanc 100% opaque, pas de fluctuation visible.
  - Suppression complète des deux blocs `.home .site-header` (le transparent par défaut + le `.is-scrolled` opaque). Le `.site-header` standard reprend le relais sur la home, exactement comme sur les autres pages.
  - `.carousel-container` : `height: 90vh` → `height: calc(100vh - var(--header-height, 74px))` + `min-height: 480px` (garde-fou viewport très court).
- `front-page.php` :
  - Suppression du bloc JS qui togglait `.is-scrolled` sur le header au scroll (lignes 717-736 de l'ancienne version) — devenu inutile.
  - Conservation du bloc 1 (repositionnement du bandeau Mon Projet sous le carousel) — à traiter en phase ultérieure.

**Conformité brief :**
- ✅ Header blanc opaque permanent partout (home + autres pages).
- ✅ `--header-height` exposée en variable CSS, utilisée par `.carousel-container`.
- ✅ Carousel = `100vh - hauteur header`, plus aucune barre noire ni débordement.
- ✅ JS scroll sur le header retiré ; plus de classe `.is-scrolled` ajoutée nulle part en runtime.
- ✅ Repositionnement du bandeau Mon Projet préservé (bloc 1).
- ✅ Pages WooCommerce, `.site-header--simplified`, et autres pages : non touchées.
- ✅ `position: sticky` conservé sur `.site-header` (pas remplacé par fixed).
- ✅ Aucune régression M22 / refonte hero.

**⚠️ Écart par rapport à la spec — mobile à 68px (pas 60px)**

La spec proposait `--header-height: 60px` en mobile. À l'inspection :
- `padding: 10px×2` sur `.site-header .header-inner` (mobile) = 20px
- `.cart-link { min-height: 48px }` (global, applicable mobile aussi)
- Total réel ≈ **68px** (cart-link domine, pas le logo qui est à 40px)

À 60px, le carousel commencerait ~8px sous le bas réel du header → bande blanche visible. J'ai donc retenu **68px**. Si Robin veut le 60 strict, à corriger après mesure réelle sur device.

**ℹ️ Note sur `critical-css-homepage.css`**

Ce fichier contient bien les anciennes règles `.home .site-header { transparent }` + `.is-scrolled`, comme suspecté par la spec. Vérification faite : il n'est référencé dans **aucun** PHP, JS, ou functions.php du projet. C'est un artefact dormant non chargé en runtime. **Pas modifié** par prudence (ne pas toucher à un fichier potentiellement utilisé par un outil externe sans en être sûr). À nettoyer / régénérer dans une tâche dédiée si Robin veut.

**À tester par Robin sur `test.atelier-sapi.fr` :**
1. Hard refresh sur la home → header blanc opaque dès le chargement, plus de logo flottant sur l'image.
2. Le carousel commence pile sous le header, son bas touche le bas de l'écran (à la scrollbar près).
3. Scroller sur la home → header reste strictement identique, plus aucune bascule au scroll.
4. Mobile (DevTools responsive ≤ 768 px) → header à ~68px, carousel ajusté.
5. Naviguer vers `/mes-creations/`, `/sur-mesure/`, fiche produit → header inchangé visuellement (était déjà opaque).
6. Inspect DOM → plus de classe `is-scrolled` qui apparaît / disparaît au scroll.
7. Bandeau Mon Projet → toujours repositionné juste sous le carousel.

Si validation OK → "go prod" → fast-forward merge `test-theme-sapi-maison` → `master` → workflow GHA.

---

### 📋 Brief original (archivé)

**Date :** 2026-05-07
**Priorité :** haute
**Branche :** `test-theme-sapi-maison` (déploiement auto sur `test.atelier-sapi.fr`). **Pas de merge direct sur master.** Workflow : commits sur `test-theme-sapi-maison` → Robin valide sur `test.atelier-sapi.fr` → fast-forward merge vers `master` → Robin déclenche le workflow GHA pour la prod.
**Mockup de référence (à recharger) :** `mockups/home-deux-portes/index.html` — vient d'être mis à jour pour refléter le rendu cible (header sticky blanc opaque permanent + carousel `calc(100vh - header)`).

### Contexte

Robin a vu le rendu actuel sur test après la refonte hero. Trois constats :
- Le carousel reste à `90vh` (Claude Code ne l'a pas passé à plein écran).
- Le header reste **transparent** au-dessus du carousel et bascule en opaque au scroll (comportement historique de la home). Ça fait flotter le logo sur l'image, et l'image passe **sous** le header.
- Sur les autres pages du site, le header est **déjà opaque blanc** (la version standard `.site-header`).

Décision de Robin (« option A » validée en chat) : **harmoniser**. Le header devient opaque blanc partout, y compris sur la home. Le carousel commence pile sous le header (touche le bas du header) et fait `calc(100vh - hauteur-header)` pour remplir exactement le reste du viewport.

### À faire — modifications précises

#### 1. `style.css` — Supprimer les surcharges home du header

**Supprimer entièrement** les blocs aux lignes 1102-1115 (à vérifier — ils ressemblent à) :

```css
/* Menu transparent par-dessus le carousel sur la homepage */
.home .site-header {
  position: fixed;
  width: 100%;
  background: rgba(255, 255, 255, 0.35) !important;
  box-shadow: none !important;
  transition: background 0.3s ease, box-shadow 0.3s ease;
}

/* Après le carousel : redevient opaque */
.home .site-header.is-scrolled {
  background: rgba(255, 255, 255, 1) !important;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12) !important;
}
```

Ces deux blocs sont à supprimer entièrement. Le `.site-header` standard (lignes 825+) prendra le relais sur la home : sticky, fond blanc, ombre légère — exactement comme sur les autres pages.

#### 2. `style.css` — Passer le fond du `.site-header` à 100% blanc

Dans le bloc `.site-header` (~ligne 825), changer le fond :

```css
/* AVANT */
background: rgba(255, 255, 255, 0.97);

/* APRÈS */
background: var(--color-white, #fff);
```

Robin veut du **blanc 100% opaque**, pas du 97%. Pas de fluctuation visible quand on scroll.

#### 3. `style.css` — Définir une variable `--header-height` dans `:root`

Ajouter dans le bloc `:root` (au début du fichier, là où sont définies les autres variables CSS) :

```css
:root {
  /* ...autres variables... */
  --header-height: 74px;
}

@media (max-width: 768px) {
  :root {
    --header-height: 60px;
  }
}
```

Valeurs basées sur l'inspection actuelle : padding `12px×2 = 24px` + logo max `50px` = ~74px desktop. Sur mobile le padding tombe (vérifier le breakpoint dans `.site-header .header-inner` ~ligne 1498), 60px est une approximation raisonnable. **Si Claude Code constate des hauteurs différentes après inspection, ajuster les valeurs.**

#### 4. `style.css` — Carousel : `calc(100vh - var(--header-height))`

Trouver la règle `.carousel-container` (~ligne 11467) :

```css
/* AVANT */
.carousel-container {
  position: relative;
  width: 100%;
  height: 90vh;
  overflow: hidden;
}

/* APRÈS */
.carousel-container {
  position: relative;
  width: 100%;
  height: calc(100vh - var(--header-height, 74px));
  min-height: 480px; /* fallback si viewport très court */
  overflow: hidden;
}
```

#### 5. `front-page.php` — Supprimer le toggle `is-scrolled` du header

Dans le bloc `<script>` à la fin du fichier (vers les lignes 679+), il y a deux fonctions :
1. **Repositionner le bandeau Mon Projet** sous le carousel — **À GARDER pour cette tâche**.
2. **Toggle `is-scrolled` sur le header** quand on scroll au-delà du carousel — **À SUPPRIMER**.

Concrètement, supprimer ce bloc (et lui seul) :

```js
// 2. Menu : transparent sur le carousel, opaque après
const header = document.querySelector('.site-header');
if (header && carousel) {
  function updateHeaderState() {
    // ...la fonction entière...
    if (carouselBottom < scrollThreshold) {
      header.classList.add('is-scrolled');
    } else {
      header.classList.remove('is-scrolled');
    }
  }
  window.addEventListener('scroll', updateHeaderState, { passive: true });
  updateHeaderState();
}
```

**Conserver** le bloc 1 (repositionnement bandeau Mon Projet) : il sera traité dans une tâche ultérieure (séparation des concerns).

#### 6. Vérifications croisées

Vérifier qu'aucune autre référence à `.is-scrolled` ne traîne dans le projet (sinon les ajuster). Suspect : `critical-css-homepage.css` (à vérifier — probablement à régénérer ou à mettre à jour si tu y trouves les anciens styles transparent + scrolled).

### Critères de succès

1. **Sur la home** : header blanc opaque dès le chargement, plus de logo qui flotte sur l'image, plus de bascule au scroll.
2. **Sur les autres pages** : header inchangé visuellement (il était déjà opaque).
3. **Carousel sur la home** : touche le bas du header en haut, touche le bas de l'écran en bas. Pas de barre noire, pas de débordement, exactement `100vh - hauteur-header` de hauteur.
4. **Pas de scroll vertical au chargement** : le header + le carousel + le pli du bandeau orientation occupent ~100vh + bandeau juste sous, pas de scroll horizontal.
5. **Mobile (≤ 768 px)** : header à 60px, carousel ajusté à `100vh - 60px`. Lisible.
6. **JS** : plus aucune référence à `is-scrolled` côté JS. Le repositionnement du bandeau Mon Projet fonctionne toujours (il sera traité dans une tâche ultérieure).
7. **Pas de régression sur les autres pages** : Mes créations, fiches produit, Sur mesure, Contact, etc. — header strictement identique au comportement actuel.

### Pièges à éviter

- ❌ **Ne pas toucher** au script de repositionnement du bandeau Mon Projet (sera vu plus tard).
- ❌ **Ne pas toucher** au comportement du header sur les pages WooCommerce (panier, checkout, single-product) ni sur le `.site-header--simplified` (qui a sa propre logique).
- ❌ **Ne pas remplacer** `position: sticky` par `position: fixed` sur `.site-header` — sticky garde la place dans le flux, ce qui simplifie le calcul `100vh - header`.
- ⚠️ **Si la hauteur réelle du header diffère** de 74px / 60px (ex. à cause d'un padding responsif intermédiaire à 1024px), ajuster les variables CSS en conséquence (et ajouter un breakpoint si nécessaire).

### Tests fonctionnels que Robin fera après ton commit

1. Hard refresh sur `test.atelier-sapi.fr` → header blanc opaque, logo + menu en couleur sombre, ombre sous le header.
2. Le carousel commence pile sous le header, et son bas touche pile le bas de l'écran (à 1px près, avec scrollbar éventuelle).
3. Scroller un peu → header reste identique, juste le bandeau orientation apparaît.
4. Tester sur mobile (DevTools responsive ≤ 768 px) → header à ~60px, carousel ajusté.
5. Naviguer vers `/mes-creations/`, `/sur-mesure/`, une fiche produit → header inchangé partout (déjà opaque).
6. Inspect → plus de classe `is-scrolled` qui apparaît/disparaît au scroll. Plus de listener `scroll` qui modifie le header.

Si tout OK : Robin te dit « go prod » → fast-forward merge `test-theme-sapi-maison` → `master` → Robin déclenche le workflow GHA.

---

## ✅ [TÂCHE] Refonte hero homepage — H1/H2 sortis du carousel + bandeau « Deux portes » — DÉJÀ IMPLÉMENTÉE (7 mai 2026)

**Statut :** ✅ Code en place dans `front-page.php` et `style.css`. Brief envoyé directement à Claude Code par Robin en parallèle de cette spec — Claude Code a implémenté l'ensemble de la phase A (carousel image pure + naming centré bas + bandeau orientation + deux portes). Spec ci-dessous **conservée pour archive uniquement**, ne pas réexécuter.

**À faire côté Robin :**
- Confirmer la branche d'implémentation (`test-theme-sapi-maison` ou autre).
- Tester sur `test.atelier-sapi.fr` selon les critères de succès listés plus bas.
- Si OK : « go prod » → merge fast-forward `test-theme-sapi-maison` → `master` → workflow GHA.

---

### 📦 Spec d'origine (archivée, NE PAS REJOUER)

**Date :** 2026-05-07
**Priorité :** haute
**Branche :** `test-theme-sapi-maison` (déploiement auto sur `test.atelier-sapi.fr`). **Pas de merge direct sur master.** Workflow : commits sur `test-theme-sapi-maison` → Robin valide sur `test.atelier-sapi.fr` → fast-forward merge vers `master` → Robin déclenche le workflow GHA pour la prod.

### ⚠️ Cette tâche REMPLACE la tâche M22 (« Slides produit du carousel cliquables »)

La M22 est bloquée depuis le 7 mai sur la décision UX du signal de cliquabilité (pill « Découvrir » refusée, 3 pistes A/B/C non tranchées). Robin a depuis ouvert une réflexion plus large sur la home, et la résolution change de nature : on ne se contente plus d'ajouter un signal sur la slide produit, on **réorganise toute la zone hero**. Cette nouvelle tâche embarque la résolution M22 et la dépasse.

**Acquis M22 à conserver** (commits `7360f4d` + `63233fb` déjà sur `test-theme-sapi-maison`) :
- ✅ Slide produit en `<a>` cliquable couvrant toute la zone
- ✅ `aria-label="Découvrir <nom produit>"` sur le `<a>`
- ✅ `pointer-events: none` sur slides non-actives, `pointer-events: auto` sur `.active`
- ✅ Pause autoplay au touch mobile (touchstart → pause, touchend → reprise après 3s)
- ✅ Reset link sur `a.carousel-slide-product` (text-decoration, color, cursor)

**À supprimer / défaire de la M22** :
- ❌ Tout le badge `<span class="carousel-cta-discover">` (le pill « Découvrir cette création » + sa flèche SVG)
- ❌ Toutes les règles CSS `.carousel-cta-discover` (badge blanc, hover orange, etc.)
- ❌ Le passage de `.carousel-content` en `display: flex; flex-direction: column; align-items: flex-end; gap: 0.5rem` — `.carousel-content` est repensé entièrement (voir spec)

### Mockup de référence validé par Robin

`mockups/home-deux-portes/index.html` (dans le repo). C'est la cible visuelle. Les couleurs exactes, les tailles de typo, les espacements, et le comportement hover sont tous incarnés dans ce fichier — l'utiliser comme source de vérité visuelle.

### Décisions éditoriales déjà actées (à ne pas remettre en question)

- **H1/H2 sortis du carousel** vers un nouveau bandeau « d'orientation » placé juste sous le carousel.
- **Carousel devient image pure** : juste image + overlay + naming produit centré en bas + dots. Plus aucun H1/H2 dans le carousel.
- **Naming produit** : centré en bas, une seule ligne, format `[firstname uppercase Montserrat] [restname Square Peg cursive]` (réutilise les classes existantes `.product-firstname` + `.product-restname`).
- **Toute la slide produit reste cliquable** (acquis M22, à préserver).
- **Couleurs CTA des deux portes** :
  - Porte A (Catalogue, action achat principale) → **orange** (`var(--gradient-cta)`)
  - Porte B (Conseil, invitation artisanale secondaire) → **bois** (`var(--color-wood)`)
  - Cohérent avec la règle du `design_system.md` (« Orange = action principale, achat ; Bois = invitation artisanale, secondaire »).
- **Wording exact** :
  - H1 : `Luminaires en bois · Atelier Sâpi`
  - H2 : `Fabriqués à la main, à la commande, dans mon atelier à Lyon` (texte EXACTEMENT tel quel, ne pas reformuler)
  - Porte A — title : `Voir toutes mes créations`
  - Porte A — sub : `Suspensions, lampadaires, appliques, lampes à poser. Tous les luminaires conçus et fabriqués par Robin.`
  - Porte A — CTA : `Explorer le catalogue` (+ flèche →)
  - Porte B — title : `Trouver ma lumière idéale`
  - Porte B — sub : `Quelques questions, je vous oriente vers la création qui correspond à votre intérieur.`
  - Porte B — CTA : `Démarrer mon projet` (+ flèche →)

### Lecture préalable obligatoire

Avant toute modification, lire :
1. **Mockup cible** : `mockups/home-deux-portes/index.html` — ratio H1/H2, position naming, espacements, hover.
2. **`front-page.php`** lignes ~313–405 : le carousel actuel (slides promo, slides produit, hero text global).
3. **`style.css`** :
   - Section carousel `~11456–11700` (toutes les règles `.carousel-*`)
   - Section M22 ajoutée par Claude Code `~11710–11760` (`a.carousel-slide-product`, `.carousel-cta-discover`)
4. **`assets/product-name-formatter.js`** : voir si `.carousel-product-name` est déjà dans les sélecteurs ; sinon **l'ajouter** pour que le nom soit splitté automatiquement en `.product-firstname` + `.product-restname`.
5. **`design_system.md`** (memory locale) : règles boutons (pill 50px, couleurs orange/bois), variables CSS.

### À faire — modifications précises

#### 1. `front-page.php` — Carousel : retirer hero text global, repositionner naming

**1.1 — Supprimer le hero text global** (lignes ~398–403, juste avant le `</section>` du carousel) :
```html
<!-- Hero Text global — visible sur TOUTES les slides, y compris promo. À ne pas masquer. -->
<div class="carousel-hero-text">
  <h1 class="carousel-hero-title">Luminaires en bois · Atelier Sâpi</h1>
  <h2 class="carousel-hero-subtitle">Fabriqués à la main, à la commande, dans mon atelier à Lyon</h2>
</div>
```
Tout ce bloc est **supprimé** (le contenu sera relogé dans le bandeau orientation, voir étape 2).

**1.2 — Slides produit : remplacer la zone `.carousel-content` par un naming centré bas.**

Avant (post-M22) :
```html
<a class="carousel-slide carousel-slide-product..." href="..." aria-label="...">
  ...image + overlay...
  <div class="carousel-content">
    <span class="carousel-cta-discover">Découvrir cette création <svg>...</svg></span>
    <p class="carousel-product-name"><?php echo esc_html($product['name']); ?></p>
  </div>
</a>
```

Après :
```html
<a class="carousel-slide carousel-slide-product<?php echo $is_first ? ' active' : ''; ?>" 
   href="<?php echo esc_url($product['url']); ?>" 
   aria-label="Découvrir <?php echo esc_attr($product['name']); ?>">
  <?php echo wp_get_attachment_image($product['image_id'], 'full', false, $img_attr); ?>
  <div class="carousel-overlay"></div>
  <p class="carousel-product-name"><?php echo esc_html($product['name']); ?></p>
</a>
```

Note : on garde une `<p class="carousel-product-name">` qui contient juste le nom brut. Le splitting firstname/restname est fait par `product-name-formatter.js` côté client. Pas de span en dur dans le PHP.

**1.3 — Slides promo : inchangées** (image + overlay seulement, déjà cliquables si URL).

#### 2. `front-page.php` — Nouveau bandeau « Deux portes »

À insérer **juste après** le `</section>` de fermeture du carousel et **avant** le `<!-- Hero Bento Grid -->` (autour de la ligne 405) :

```html
<!-- Bandeau d'orientation : H1 + H2 + Deux portes -->
<section class="bandeau-orientation">
  <h1 class="bandeau-h1">Luminaires en bois · Atelier Sâpi</h1>
  <p class="bandeau-h2">Fabriqués à la main, à la commande, dans mon atelier à Lyon</p>

  <div class="bandeau-portes">

    <a class="porte porte--catalogue" href="<?php echo esc_url(home_url('/mes-creations/')); ?>">
      <div class="porte-label">Catalogue</div>
      <div class="porte-title">Voir toutes mes créations</div>
      <p class="porte-sub">Suspensions, lampadaires, appliques, lampes à poser. Tous les luminaires conçus et fabriqués par Robin.</p>
      <span class="porte-cta porte-cta--orange">
        <span>Explorer le catalogue</span>
        <svg width="16" height="16" viewBox="0 0 20 20" fill="none" aria-hidden="true">
          <path d="M4 10H16M16 10L10 4M16 10L10 16" stroke="currentColor" stroke-width="2"/>
        </svg>
      </span>
    </a>

    <a class="porte porte--conseil" href="#robin-conseil"
       onclick="<?php if (defined('SAPI_ROBIN_V2') && SAPI_ROBIN_V2) : ?>if(window.sapiRobinOpen){window.sapiRobinOpen('homepage');return false;}<?php else : ?>var bar=document.getElementById('mon-projet-bar');if(bar){bar.scrollIntoView({behavior:'smooth',block:'start'});var t=document.getElementById('mon-projet-toggle');if(t&&t.getAttribute('aria-expanded')==='false')t.click();}return false;<?php endif; ?>">
      <div class="porte-label">Conseil</div>
      <div class="porte-title">Trouver ma lumière idéale</div>
      <p class="porte-sub">Quelques questions, je vous oriente vers la création qui correspond à votre intérieur.</p>
      <span class="porte-cta porte-cta--wood">
        <span>Démarrer mon projet</span>
        <svg width="16" height="16" viewBox="0 0 20 20" fill="none" aria-hidden="true">
          <path d="M4 10H16M16 10L10 4M16 10L10 16" stroke="currentColor" stroke-width="2"/>
        </svg>
      </span>
    </a>

  </div>
</section>
```

Note sur la Porte B : on réutilise exactement la même logique d'ouverture que le room picker actuel (`window.sapiRobinOpen('homepage')` si SAPI_ROBIN_V2, sinon scroll + toggle du bandeau Mon Projet). Pas de nouveau JS à inventer.

#### 3. `style.css` — Carousel : mettre à jour `.carousel-product-name`

L'ancienne règle (positionnée en bas-droite) est à **remplacer** par un positionnement centré en bas, une seule ligne, avec le pattern firstname/restname.

```css
/* Naming produit du carousel — centré en bas, une seule ligne */
.carousel-product-name {
  position: absolute;
  bottom: 60px;             /* au-dessus des dots */
  left: 50%;
  transform: translateX(-50%);
  z-index: 2;
  margin: 0;
  color: var(--color-white);
  font-size: clamp(20px, 2.2vw, 32px);
  line-height: 1;
  white-space: nowrap;
  pointer-events: none;
  text-shadow: 0 2px 15px rgba(0, 0, 0, 0.5);
  text-align: center;
}

.carousel-product-name .product-firstname {
  font-family: var(--font-body, 'Montserrat', sans-serif);
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.18em;
  font-size: 0.55em;
  vertical-align: baseline;
  margin-right: 0.4em;
  opacity: 0.95;
}

.carousel-product-name .product-restname {
  font-family: var(--font-display, 'Square Peg', cursive);
  font-size: 1.5em;
  line-height: 1;
  vertical-align: baseline;
  text-transform: lowercase; /* "l'incandescent" pas "L'INCANDESCENT" */
}

@media (max-width: 768px) {
  .carousel-product-name { bottom: 50px; font-size: clamp(18px, 4vw, 24px); }
}
```

Modifier également la règle `.carousel-dots` pour qu'elle reste **sous** le naming (pas de chevauchement) :
```css
.carousel-dots {
  position: absolute;
  bottom: 22px;             /* abaissé pour laisser le naming au-dessus */
  /* ...reste inchangé... */
}
```

#### 4. `style.css` — Carousel : supprimer les blocs orphelins

Supprimer les règles CSS suivantes (entièrement, ainsi que leurs media queries associées) :

- `.carousel-hero-text` (la zone H1/H2 globale) et toutes ses media queries
- `.carousel-hero-title` et ses media queries
- `.carousel-hero-subtitle` et ses media queries
- `.carousel-content` (zone bottom-right qui contenait le nom du produit, on n'en a plus besoin)
- `.carousel-cta-discover` (badge M22 abandonné) et toutes ses règles hover/mobile

Le bloc « SLIDES EN AVANT » avec `a.carousel-slide-promo` et `a.carousel-slide-product` reste **inchangé**.

#### 5. `style.css` — Nouveau bandeau orientation

À ajouter dans une nouvelle section dédiée du fichier (juste après la section carousel, avant le bento) :

```css
/* ========================================
   BANDEAU ORIENTATION — Hero deux portes
   Phase A refonte home (mai 2026)
   ======================================== */

.bandeau-orientation {
  padding: 80px 40px 90px;
  background: var(--color-warm, #FBF6EA);
  text-align: center;
}

.bandeau-h1 {
  font-family: var(--font-body, 'Montserrat', sans-serif);
  font-size: clamp(15px, 1.7vw, 24px);
  font-weight: 600;
  color: var(--color-wood-dark);
  text-transform: uppercase;
  letter-spacing: 0.1em;
  margin: 0 0 18px;
  line-height: 1.25;
}

.bandeau-h2 {
  font-family: var(--font-display, 'Square Peg', cursive);
  font-size: clamp(44px, 6.5vw, 88px);
  color: var(--color-wood);
  margin: 0 auto 60px;
  line-height: 0.95;
  font-weight: 400;
  max-width: 1100px;
}

.bandeau-portes {
  display: flex;
  justify-content: center;
  gap: 24px;
  flex-wrap: wrap;
  max-width: 880px;
  margin: 0 auto;
}

.porte {
  flex: 1 1 360px;
  max-width: 420px;
  padding: 36px 32px;
  border-radius: 16px;
  background: var(--color-white, #fff);
  box-shadow: var(--shadow-card);
  text-decoration: none;
  color: inherit;
  transition: transform 0.4s var(--ease-smooth), box-shadow 0.4s var(--ease-smooth);
  text-align: left;
  cursor: pointer;
}

@media (hover: hover) {
  .porte:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-card-hover);
  }
}

.porte-label {
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.22em;
  text-transform: uppercase;
  color: var(--color-wood);
  opacity: 0.6;
  margin-bottom: 12px;
}

.porte-title {
  font-size: 22px;
  font-weight: 600;
  color: var(--color-wood-dark);
  margin-bottom: 10px;
  line-height: 1.2;
}

.porte-sub {
  font-size: 14px;
  color: rgba(74, 63, 53, 0.7);
  margin-bottom: 24px;
  line-height: 1.55;
}

.porte-cta {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 13px 28px;
  border-radius: 50px;
  font-size: 12px;
  font-weight: 600;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  transition: all 0.3s var(--ease-smooth);
}

.porte-cta--orange {
  background: var(--gradient-cta);
  color: var(--color-white, #fff);
  box-shadow: 0 4px 15px rgba(227, 91, 36, 0.25);
}

@media (hover: hover) {
  .porte:hover .porte-cta--orange {
    box-shadow: 0 6px 20px rgba(227, 91, 36, 0.35);
  }
}

.porte-cta--wood {
  background: var(--color-wood);
  color: var(--color-white, #fff);
}

@media (hover: hover) {
  .porte:hover .porte-cta--wood {
    background: var(--color-wood-dark);
  }
}

.porte-cta svg {
  flex-shrink: 0;
  width: 16px;
  height: 16px;
}

@media (max-width: 768px) {
  .bandeau-orientation { padding: 60px 20px 70px; }
  .bandeau-portes { flex-direction: column; align-items: stretch; }
  .porte { padding: 28px 24px; max-width: none; }
}
```

#### 6. `assets/product-name-formatter.js` — Ajouter le sélecteur

Vérifier que `.carousel-product-name` figure bien dans les tableaux `selectors` du fichier. Si absent, l'ajouter (pour que le nom du produit soit auto-splitté en firstname + restname). Référence : `design_system.md` mémoire `« Pour tout nouveau contexte affichant un nom de produit, ajouter le sélecteur dans product-name-formatter.js (tableaux selectors) »`.

#### 7. JS / autre — aucun autre changement

- `assets/homepage-carousel.js` : ne pas toucher. Le sélecteur `.carousel-slide` continue de fonctionner.
- Bandeau Mon Projet repositionné par script en bas de `front-page.php` (lignes ~679+) : **ne pas toucher pour cette tâche.** Le bandeau Mon Projet reste sous le bandeau orientation (redondance acceptée temporairement). Sera traité en phase B.

### Critères de succès

1. **Carousel** : aucun H1, aucun H2, aucun pill « Découvrir » dans la zone carousel. Juste image + overlay + naming centré bas + dots.
2. **Naming produit** : centré en bas du carousel, une seule ligne, format `VINCENT l'incandescent` (firstname Montserrat uppercase + restname Square Peg cursive). Splittage automatique par `product-name-formatter.js`.
3. **Slide produit cliquable** : toute la zone est cliquable (acquis M22 préservé), `aria-label` correct.
4. **Slide promo** : zéro régression, comportement strictement identique à avant.
5. **Bandeau orientation** sous le carousel : H1 (petit, majuscules, espacé) + H2 (grand, Square Peg, ratio identique au carousel actuel) + 2 cards Portes côte à côte.
6. **Porte A** : pill orange (`var(--gradient-cta)`), URL → `/mes-creations/`, ouvre la page catalogue.
7. **Porte B** : pill bois (`var(--color-wood)`), au clic → ouvre Robin Conseiller V2 (réutilise `window.sapiRobinOpen('homepage')`) avec fallback vers le bandeau Mon Projet historique si V1.
8. **Responsive mobile (≤ 768 px)** : portes empilées en colonne, padding réduit, tailles de typo qui scalent correctement.
9. **Hover desktop sur portes** : `translateY(-2px)` + `box-shadow` plus marquée. Cohérent avec les autres bento cards du site.
10. **Pas de régression** sur le bug du fade carousel (1ère slide ne réapparaît pas pendant le fondu).
11. **SEO** : H1 unique sur la page, dans le bandeau orientation, contenu identique à l'ancien (`Luminaires en bois · Atelier Sâpi`).
12. **Bandeau Mon Projet** (sous le carousel via script) : reste en place, fonctionne toujours. La cohabitation temporaire avec la Porte B est attendue (sera traitée en phase B).

### Tests fonctionnels que Robin fera après ton commit

1. Hard refresh sur `test.atelier-sapi.fr` → carousel s'affiche en mode image pure, naming bien centré bas, dots juste sous.
2. Clic sur une slide produit n'importe où → navigation vers `/mes-creations/<slug>/`.
3. Hover sur une slide produit (desktop) → curseur main, pas d'effet zoom (acquis M22 préservé : pas de zoom au hover).
4. Bandeau orientation visible sous le carousel : H1 en majuscules wood-dark, H2 énorme en Square Peg wood, 2 portes côte à côte.
5. Hover sur Porte A (orange) → ombre se renforce, lift léger.
6. Clic sur Porte A → atterrissage sur `/mes-creations/`.
7. Clic sur Porte B (bois) → ouvre la modale Robin Conseiller (V2) avec `target=homepage`. Si V1, scroll vers le bandeau Mon Projet et l'ouvre.
8. Mobile (≤ 768 px) : portes empilées verticalement, padding et typo OK, naming carousel toujours visible et lisible.
9. Inspect : un seul `<h1>` sur la page (dans `.bandeau-h1`). Plus aucun `<h1>` ou `<h2>` dans le carousel.
10. SEO Yoast / View Source : le H1 reste `Luminaires en bois · Atelier Sâpi`, le H2 dans le bandeau reste `Fabriqués à la main, à la commande, dans mon atelier à Lyon`.

Si tout OK : Robin te dit « go prod » → fast-forward merge `test-theme-sapi-maison` → `master` → Robin déclenche le workflow GHA.

### Pièges à éviter

- ❌ **Ne pas inventer** de nouvelles classes CSS hors de `bandeau-orientation`, `bandeau-h1`, `bandeau-h2`, `bandeau-portes`, `porte`, `porte--catalogue`, `porte--conseil`, `porte-label`, `porte-title`, `porte-sub`, `porte-cta`, `porte-cta--orange`, `porte-cta--wood`. Ce sont les seules nouvelles classes autorisées.
- ❌ **Ne pas modifier** la structure du carousel au-delà de ce qui est spécifié (slides promo intactes, dots intactes, fade intact).
- ❌ **Ne pas toucher** au bandeau Mon Projet ni à son script de repositionnement.
- ❌ **Ne pas reformuler** le wording du H1/H2 ni des portes (texte exact à respecter au caractère près, accents inclus).
- ❌ **Ne pas dupliquer** le H1 ailleurs sur la page (Yoast vérifie ça en SEO).
- ❌ **Ne pas oublier** d'ajouter `.carousel-product-name` à `product-name-formatter.js` — sinon le naming ne sera pas splitté en firstname + restname.

---

## ✅ [TÂCHE] M22 — Slides produit du carousel homepage cliquables — REMPLACÉE PAR LA TÂCHE CI-DESSUS (7 mai 2026)

**Statut :** 🔁 Cette tâche est **dépréciée** au profit de la nouvelle « Refonte hero homepage ». Les acquis (slide cliquable + accessibilité + pause autoplay touch) sont préservés dans la nouvelle tâche, le pill « Découvrir » et les 3 pistes design (A/B/C) sont abandonnés. La nouvelle tâche embarque une refonte plus large qui résout le blocage UX par changement d'approche : le H1/H2 quitte le carousel, le naming centré bas devient le seul élément textuel, et toute la slide reste cliquable.

---

### Brief original M22 (archivé)

## 🟠 [TÂCHE] M22 — Slides produit du carousel homepage cliquables — EN PAUSE DESIGN (7 mai 2026)

**Statut :** ✅ Cliquabilité + accessibilité techniquement en place (commits `7360f4d` + `63233fb` sur `test-theme-sapi-maison`). 🟠 **Bloqué côté UX/design** — Robin a refusé le bouton pill ET les effets hover. Décision design à prendre avec Cowork avant de finaliser.

### 🔁 Retour à Cowork — décision design en suspens (7 mai 2026)

Robin a vu la version live sur `test.atelier-sapi.fr` (screenshot fourni : H1+H2 centrés en bas, pill `DÉCOUVRIR CETTE CRÉATION →` en bas-droite, nom du produit "VINCENT L'incandescent" sous le pill, barre `DÉMARRER MON PROJET` juste sous le carousel).

**Ce qui a été refusé par Robin :**
1. ❌ Effets hover desktop (zoom 1.02 image + bascule orange du badge) → **déjà supprimés** (commit `63233fb`).
2. ❌ Le bouton pill `DÉCOUVRIR CETTE CRÉATION →` en bas-droite → toujours en place côté code, à remplacer.

**Ce qui reste OK et ne bouge pas :**
- ✅ Toute la mécanique : `<a>` cliquable couvre la slide, `aria-label`, `pointer-events: none` sur slides non-actives, pause autoplay au touch mobile, slide promo inchangées.
- ✅ Le nom du produit en bas-droite (typo brand : firstname Square Peg + restname Montserrat).
- ✅ Le `cursor: pointer` desktop suffit déjà à signaler la cliquabilité, mais il faut un signal **permanent et mobile-friendly** en plus (vu qu'il n'y a pas de cursor sur mobile).

**Trois pistes proposées à Robin (pour reprise avec Cowork) — il a la liberté de déplacer le nom du produit aussi :**

**Piste A — Légende photo en bas-gauche (ma reco)**
Déplacer le bloc nom du produit + une mini-flèche `→` en bas-GAUCHE, façon cartel de musée / légende d'œuvre. Le nom garde sa typo brand. La flèche `→` signale le clic. Équilibre visuellement le H1/H2 centré.
- Pour : convention éditoriale forte, cohérent avec le ton artisan ("chaque pièce a un nom, comme une œuvre signée"), aucun chrome supplémentaire.
- Contre : il faut s'assurer que la zone bas-gauche ne soit pas masquée par le contenu d'aucune des 8 photos ambiance.

**Piste B — Tag compact en haut-droite**
Bloc `VINCENT L'incandescent ↗` discret en TOP-right de la slide. Libère totalement le bas (déjà chargé : H1/H2 + barre DÉMARRER MON PROJET juste sous).
- Pour : libère le bas, top-right est une convention "voir plus / sortir vers".
- Contre : top-right est moins regardé que le bas (l'œil termine sa lecture du hero en bas). Moins de force commerciale.

**Piste C — Bloc centré sous le H2**
Ajouter une 3e ligne discrète `→ Vincent, l'incandescent` sous le subtitle Square Peg.
- Pour : visible au centre, dans l'axe de lecture.
- Contre : tasse trois niveaux de texte au centre, écrase la hiérarchie H1>H2, casse l'impact du H1+H2 hero.

**Pistes que j'ai écartées et pourquoi :**
- Soulignement du nom du produit → interfère avec la typo Square Peg cursive (rendu bizarre).
- Mini-ligne `Voir la création →` sous le nom dans la position actuelle bas-droite → ajoute un 3e niveau de texte tassé près du H2 italique.
- Indicateur dans un cadre/contour interne sur l'image → trop "designer", pas dans l'esprit éditorial.
- Custom cursor qui change → desktop only, ne résout rien sur mobile.

**Ma recommandation :** Piste A (légende bas-gauche). Ça respecte le minimalisme du design existant, donne une vraie identité éditoriale aux slides produit, et la flèche `→` à côté du nom dit clairement "clique pour ouvrir".

**À faire dès que Robin tranche :**
1. Implémenter la piste retenue (modifier `front-page.php` + `style.css` + supprimer la classe `.carousel-cta-discover`).
2. Tester sur mobile (DevTools + vrai téléphone si possible).
3. Si OK validation Robin → fast-forward merge `test-theme-sapi-maison` → `master` → workflow GHA.

---

### ✅ État technique actuel sur `test-theme-sapi-maison`

**Commits poussés :**
- `7360f4d` — M22 initial (cliquabilité + pill button + hover effects)
- `63233fb` — Suppression hover effects (zoom image + bascule orange badge)

**Fichiers à finaliser quand le design sera tranché :**
- `front-page.php` (~ligne 388-396) : bloc `.carousel-content` avec le `<span class="carousel-cta-discover">` à supprimer/remplacer.
- `style.css` (~ligne 11710-11750) : règles `.carousel-cta-discover` + `a.carousel-slide-product` à adapter selon piste retenue.

### ✅ Retour Claude Code (6 mai 2026, commit `7360f4d`)

**Fichiers modifiés (3) :**
- `front-page.php` : boucle `foreach ($carousel_products as $product)` → `<div class="carousel-slide">` remplacé par `<a class="carousel-slide carousel-slide-product" href="..." aria-label="...">`. Ajout d'un badge CTA `<span class="carousel-cta-discover">Découvrir cette création <svg→></span>` au-dessus du nom dans `.carousel-content`.
- `style.css` :
  - `.carousel-slide:not(.active) { pointer-events: none }` + `.carousel-slide.active { pointer-events: auto }` → bloque les clics fantômes / focus clavier sur les slides empilées invisibles.
  - `.carousel-content` passé en `display: flex; flex-direction: column; align-items: flex-end; gap: 0.5rem` pour empiler proprement CTA + nom.
  - `a.carousel-slide-product` : reset link (text-decoration, color, cursor), zoom 1.02 au hover desktop sur l'image (cohérent slides promo).
  - `.carousel-cta-discover` : badge blanc semi-opaque (`rgba(255,255,255,0.92)`) + texte sombre + ombre légère pour rester lisible sur les 8 photos ambiance. Hover desktop : passe en orange (`var(--color-orange)`) + ombre orange.
  - Mobile : `min-height: 44px` sur le CTA pour la cible tactile, padding ajusté.
- `assets/homepage-carousel.js` : ajout pause autoplay au `touchstart`, reprise 3 s après `touchend` (BONUS du brief — UX mobile).

**Conformité brief :**
- ✅ Pattern réutilisé (pas réinventé) — duplication de la logique `<a>` des slides promo.
- ✅ Pas de conditionnel `$has_url` côté produits (`$product['url']` toujours défini via `get_permalink()` ligne 57).
- ✅ Slides promo non touchées — zéro régression.
- ✅ JS de rotation non modifié (juste augmenté avec touch handlers).
- ✅ H1 unique préservé dans `.carousel-hero-title`.
- ✅ Bandeau Mon Projet non touché.
- ✅ `aria-label="Découvrir <nom produit>"` sur chaque `<a>`.
- ✅ `pointer-events: none` sur les slides non-actives → pas de focus clavier sur les slides invisibles.
- ✅ SVG flèche cohérent avec `.storytelling-link` du même fichier.

**À tester par Robin sur `test.atelier-sapi.fr` :**
1. Hard refresh → carousel s'affiche normalement, hero text global visible.
2. Hover sur une slide produit (desktop) → zoom léger image + badge CTA passe en orange.
3. Clic sur une slide produit → URL `/mes-creations/<slug>/` correcte.
4. Mobile (DevTools responsive ou vrai téléphone) → tap sur slide = navigation, badge CTA lisible, autoplay pause pendant le touch.
5. Inspect → `pointer-events: none` confirmé sur les slides sans `.active`.
6. Slides promo (s'il y en a d'actives en admin) → comportement strictement identique à avant.
7. VoiceOver / NVDA → seul le `<a>` actif est annoncé (Tab passe direct dessus, pas sur les slides invisibles).

Si validation OK → "go prod" → fast-forward merge `test-theme-sapi-maison` → `master` → workflow GHA.

---

### 📋 Brief original (archivé)

**Date :** 2026-05-06
**Priorité :** haute
**Branche :** `test-theme-sapi-maison` (déploiement auto sur `test.atelier-sapi.fr`). Pas de merge direct sur master. Workflow : commits sur `test-theme-sapi-maison` → Robin valide sur test → fast-forward merge vers `master` → Robin déclenche le workflow GHA pour la prod.
**Mobile-first :** OUI. Toute la spec doit être pensée mobile d'abord, le desktop suit.

### Contexte business

Audit GA4 : 91 % (avril) → ~86 % (mai) des visiteurs France ne voient jamais une fiche produit. La home a déjà été optimisée (suppression intro screen, suppression quick-view, refonte grille catégories) — taux home→fiche passé de 8,7 % à ~14 %. Mais le carousel fullscreen, qui est l'élément le plus visible et le plus haut de la page, **affiche 8 produits sans aucun lien cliquable vers leurs fiches** (alors que les slides "promo" du même carousel sont déjà cliquables depuis la tâche du 5 mai). C'est le quick win le plus évident pour continuer à pousser le taux home→fiche.

L'objectif est purement structurel : ouvrir un chemin direct visible-image-au-clic sans toucher au design global du carousel ni au comportement de rotation.

### À lire d'abord (état actuel — pour ne pas réécrire hors charte)

1. **`front-page.php`** — section `<!-- Full Page Carousel -->` (~ligne 332-402). Voir la boucle `foreach ($carousel_products as $product)` (~ligne 371-391) : actuellement `<div class="carousel-slide">` avec image + overlay + `.carousel-content > .carousel-product-name`. La variable `$product['url']` contient déjà l'URL de la fiche (déjà construite via `get_permalink()` plus haut dans le fichier).

2. **Pattern déjà existant pour les slides promo** (~ligne 342-369) — la précédente tâche a déjà introduit le pattern conditionnel `<?php if ($has_url) : ?> <a class="carousel-slide carousel-slide-promo" href="..."> <?php else : ?> <div class="carousel-slide"> <?php endif; ?>`. **C'est exactement le pattern à dupliquer pour les slides produit** — pas réinventer.

3. **`style.css`** : bloc `SLIDES EN AVANT` ajouté lors de la précédente tâche (~ligne 11680-11695) avec `a.carousel-slide-promo` + zoom hover desktop. La nouvelle classe `a.carousel-slide-product` doit reprendre la même base.

4. **`assets/homepage-carousel.js`** : sélecteur `.carousel-slide` qui fonctionne déjà avec `<a>` ou `<div>` indifféremment. **Ne pas toucher** à la logique de rotation.

### À faire — modifications précises

**1. `front-page.php` — Slides produit cliquables**

Modifier la boucle `foreach ($carousel_products as $product)` (~ligne 371-391) pour qu'elle génère un `<a class="carousel-slide carousel-slide-product" href="<?php echo esc_url($product['url']); ?>">` à la place du `<div class="carousel-slide">`. Pas de conditionnel `$has_url` ici : `$product['url']` est toujours défini (vu la construction du tableau plus haut dans le fichier).

À l'intérieur de la slide, conserver l'image et l'overlay tels quels. Le bloc `.carousel-content` doit rester (avec le nom du produit) et un **CTA visible** doit être ajouté juste au-dessus du nom : un `<span class="carousel-cta-discover">Découvrir cette création <svg>→</svg></span>`. Le SVG flèche est optionnel mais cohérent avec le reste du site (cf. `.storytelling-link` dans le même fichier).

**Accessibilité :** ajouter `aria-label="Découvrir <?php echo esc_attr($product['name']); ?>"` sur le `<a>` pour les lecteurs d'écran.

**2. `style.css` — Styles + protection contre clics fantômes**

Ajouter dans le bloc `SLIDES EN AVANT` (juste après les règles `a.carousel-slide-promo` existantes, ~ligne 11695) :

- `a.carousel-slide-product` : reset `text-decoration: none`, `color: inherit`, `cursor: pointer` (pattern identique aux slides promo).
- **Critique — protection contre clics fantômes** : `.carousel-slide:not(.active) { pointer-events: none; }` *(à ajouter dans le CSS du carousel principal, ~ligne 11480-11507)*. Sans cette règle, les `<a>` empilés en `position: absolute opacity: 0` capturent les clics au focus clavier ou peuvent provoquer des navigations fantômes.
- `.carousel-slide.active { pointer-events: auto; }` pour expliciter.
- `a.carousel-slide-product:hover .carousel-slide-img` : zoom léger desktop (cohérent slides promo, `transform: scale(1.02)`, `transition: transform 0.6s var(--ease-smooth)`).
- `.carousel-cta-discover` : badge visible, fond blanc semi-transparent OU pill orange `var(--gradient-cta)`, à toi de choisir le rendu le plus lisible sur fond photo. **Important :** doit rester lisible sur les 8 photos ambiance (qui ont des dominantes variées). Préférer un fond opaque ou semi-opaque avec ombre légère.
- Marges + tailles cohérentes avec `.carousel-product-name` existant. Sur le bloc `.carousel-content`, passer en `display: flex; flex-direction: column; align-items: flex-end; gap: 0.5rem;` pour empiler proprement CTA + nom.

**Mobile (≤ 768 px)** :
- Le CTA `.carousel-cta-discover` doit avoir une **hauteur tactile minimum de 44 px** (recommandation Apple/Google).
- Le bloc `.carousel-content` reste positionné en bas-droite (déjà géré dans le media query existant `~ligne 11697-11707`).
- Pas de hover : retirer le zoom au touch (`@media (hover: hover)` autour du zoom).
- Le `<a>` couvre toute la slide → l'utilisateur peut taper n'importe où sur l'image, pas que sur le CTA. Le CTA est juste là pour signaler le clic.

**3. `assets/homepage-carousel.js` — Pause autoplay au touch (BONUS facultatif)**

UX mobile : si l'utilisateur veut viser une slide précise sur mobile, l'autoplay 5s est frustrant (la slide change pendant qu'il tape). Ajouter une pause au `touchstart` sur le carousel, redémarrage au `touchend` après un délai de 3 s. **À ne faire que si trivial** (sinon laisser tel quel — la fonctionnalité principale est ailleurs).

### Critères de succès

1. **Desktop** : clic sur l'image d'une slide produit → atterrissage sur la fiche correspondante. Hover → zoom léger sur l'image + CTA visible. Le hero text global (H1) reste visible et inchangé.
2. **Mobile** : tap sur la slide active → atterrissage sur la fiche. CTA `.carousel-cta-discover` lisible et > 44 px de haut. Pas de zoom au touch.
3. **Slides promo** : zéro régression — elles continuent de fonctionner exactement comme avant la tâche.
4. **Slides non-actives** : ne capturent aucun clic fantôme (vérifier dans Inspecteur → cliquer sur position d'une slide non-active ne déclenche pas de navigation).
5. **H1 unique** : la home garde son seul `<h1>` dans `.carousel-hero-title`. Aucun H1/H2 ajouté dans les slides.
6. **Auto-rotation** : continue de fonctionner toutes les 5 s, indépendamment du nouveau tag `<a>`.
7. **Accessibilité** : navigation clavier OK (Tab atterrit sur la slide active uniquement, pas sur les non-actives grâce à `pointer-events: none`). `aria-label` présent.
8. **Pas de modification structurelle hors scope** : ne pas toucher au bento, aux collections, aux autres sections de la home.

### Tests fonctionnels que Robin fera après ton commit

1. Hard refresh sur `test.atelier-sapi.fr` → carousel s'affiche normalement, hero text global visible.
2. Hover sur une slide produit (desktop) → zoom léger + curseur main + CTA bien visible.
3. Clic sur une slide produit → URL `/mes-creations/<slug>/` correcte.
4. Test mobile (DevTools responsive) → CTA lisible, tap = navigation.
5. Test slide promo cliquable (s'il y en a d'actives dans l'admin) → comportement inchangé.
6. Inspect → confirmer `pointer-events: none` sur les slides non-active.
7. Tester en mode lecteur d'écran (VoiceOver / NVDA) → seul le `<a>` actif est annoncé.

Si tout OK : Robin te dit "go prod" → fast-forward merge `test-theme-sapi-maison` → `master` → Robin déclenche le workflow GHA.

### Pièges à éviter (basés sur les feedbacks passés)

- ❌ **Ne pas réécrire la structure du carousel** ni introduire de nouvelles classes hors charte (`.carousel-product-card`, etc.). Réutiliser strictement les classes existantes (`.carousel-slide`, `.carousel-slide-img`, `.carousel-overlay`, `.carousel-content`, `.carousel-product-name`) + 2 nouvelles (`carousel-slide-product`, `carousel-cta-discover`).
- ❌ **Ne pas refaire le JS de rotation** — il fonctionne avec `<a>` ou `<div>`.
- ❌ **Ne pas dupliquer le H1** dans les slides — le H1 unique est dans `.carousel-hero-title`.
- ❌ **Ne pas toucher au bandeau Mon Projet** repositionné par le script en bas de `front-page.php`.

---

## ✅ [TÂCHE] Système « Slides en avant » — Carousel homepage configurable — MERGÉ SUR MASTER (5 mai 2026)

**Date :** 2026-05-05
**Priorité :** normale
**Branche :** `master` (merge fast-forward `46e972d..e61dea2`)
**Statut :** ✅ Validé par Robin sur test ("ça fonctionne très bien"). ✅ Mergé sur master. 🟡 Workflow GitHub Actions à lancer par Robin pour pousser sur `atelier-sapi.fr`.

---

### ✅ Retour Claude Code (5 mai 2026, commit `e61dea2`)

**Modifications :** 2 fichiers, +96 / -20 lignes — strictement comme la spec.

1. **`front-page.php`** :
   - Bloc PHP `$promo_slides` ajouté juste avant `// Star du moment —` (lit le repeater ACF `slides_en_avant` sur `page_on_front`, filtre `active` + image + dates avec `current_time('Y-m-d')` pour fuseau Paris)
   - Markup carousel réécrit avec compteur `$slide_index` global → seule la toute première slide (peu importe son type) reçoit `.active` + `loading="eager"` + `fetchpriority="high"`
   - Slides promo : `<a class="carousel-slide carousel-slide-promo">` si URL, sinon `<div>` ; pas de surcharge `alt` (utilise le natif médiathèque)
   - Hero text global inchangé, toujours visible sur toutes les slides
   - Condition de rendu passée à `if ($total_slides > 0)` pour gérer le cas "que des slides promo, pas de produits"

2. **`style.css`** :
   - Bloc `SLIDES EN AVANT` ajouté juste avant `/* Mobile Responsive */` (~ligne 11678)
   - Reset `text-decoration` + `color: inherit` sur `a.carousel-slide-promo`
   - Léger zoom au hover desktop sur `.carousel-slide-img` (cohérent bento)

3. **`assets/homepage-carousel.js`** : non touché (fonctionne avec `<a>` ou `<div>` indifféremment grâce au sélecteur `.carousel-slide`).

**Garde-fou bug fade :** la règle `.carousel-slide:first-child` supprimée dans `46e972d` n'a pas été réintroduite — la régression est évitée.

**Test fonctionnel à faire côté Robin sur `test.atelier-sapi.fr`** (hard refresh) :
1. **Sans aucune slide promo configurée** → carousel identique à la prod actuelle (8 slides produits, hero text global visible). C'est le cas par défaut qui doit fonctionner immédiatement.
2. Créer dans l'admin (page Accueil → repeater "Slides en avant") une slide avec : `active` ON, image, URL vers un produit, dates vides → vérifier qu'elle apparaît en première position, est cliquable sur toute la zone, hero text global toujours visible.
3. Tester slide **sans URL** → visible mais non cliquable, pas de hover zoom.
4. Tester `active = false` ou date_debut future ou date_fin passée → slide absente du DOM.
5. Tester plusieurs slides promo → toutes affichées en début de carousel, dans l'ordre du repeater.
6. Inspecter la première slide → `loading="eager"` + `fetchpriority="high"` (les autres `loading="lazy"`).
7. Inspecter une slide promo → `alt` provient bien de la médiathèque (pas du code).
8. Vérifier mobile (≤ 768 px) → pas de régression.

Si tout OK : tu me dis "go prod" et je merge `test-theme-sapi-maison` → `master`, puis je te dirai de lancer le workflow Actions.

---

### 📦 Spec d'origine

**Branche :** `test-theme-sapi-maison` (déploiement auto sur `test.atelier-sapi.fr`). **Pas de merge direct sur master.** Workflow : commits sur `test-theme-sapi-maison` → Robin valide sur `test.atelier-sapi.fr` → fast-forward merge vers `master` → Robin déclenche le workflow GHA pour la prod.
**Dépendance :** la tâche « Fix bug fade carousel » doit être déjà mergée (c'est le cas au moment de la rédaction).

### Contexte

Robin doit pouvoir insérer des slides « en avant » (promo, événements saisonniers comme Fête des Mères, Noël, soldes…) **avant** les 8 slides produits actuelles du carousel homepage, sans toucher au code, avec :
- Activation/désactivation au clic
- Fenêtre de dates (visible auto entre date début et date fin)
- Slide cliquable si une URL est fournie, sinon slide non interactive

**Choix éditorial validé par Robin : pas de titre H1/H2 par slide.** Le hero text global de la home (« Luminaires en bois · Atelier Sâpi » / « Fabriqués à la main, à la commande, dans mon atelier à Lyon ») reste **immuable et toujours visible** sur toutes les slides, y compris les slides promo. Si Robin veut un message custom sur une slide promo, il l'incruste directement dans l'image qu'il téléverse. Avantages : H1 unique pour le SEO, liberté typographique totale sur l'image, code plus simple.

### ACF déjà configuré (par Robin, à ne PAS recréer en PHP)

Field group **« Slides Carousel d'accueil »** attaché à la page Accueil (front page WP). Un seul champ Repeater :

| Champ Repeater | Nom | Type | Notes |
|---|---|---|---|
| Slides en avant | `slides_en_avant` | Repeater (Bloc layout) | Conteneur principal |

**Sous-champs (dans cet ordre) :**

| # | Nom | Type | Notes |
|---|---|---|---|
| 1 | `active` | True/False | Default coché. Switch ON/OFF stylisé. |
| 2 | `image` | Image | Obligatoire. Return format = **ID** (entier). |
| 3 | `url` | URL | Optionnel. Vide = slide non cliquable. |
| 4 | `date_debut` | Date Picker | Return format `Y-m-d`. Optionnel. Vide = active immédiatement. |
| 5 | `date_fin` | Date Picker | Return format `Y-m-d`. Optionnel. Vide = pas de limite. |

### À faire

#### 0. Lecture préalable obligatoire

**Avant toute modification**, lire :
- `front-page.php` lignes ~1–80 (queries produits) et lignes ~313–345 (markup carousel + hero text global)
- `style.css` lignes ~11456–11682 (toute la section `HOMEPAGE FULLSCREEN CAROUSEL`)
- `assets/homepage-carousel.js` (intégralité, court)

Objectif : comprendre la structure existante pour la **réutiliser**, pas la recréer. Aucune nouvelle classe CSS à inventer hormis `carousel-slide-promo` (modificateur).

#### 1. `front-page.php` — Récupération des slides en avant

Insérer ce bloc PHP **juste avant** la section commentée `// Star du moment —` (vers la ligne 81), donc après la construction de `$carousel_products` :

```php
// Slides "en avant" — ACF Repeater sur la front page
// Filtrage : actives ET dans la fenêtre temporelle ET avec image valide.
$promo_slides = [];
$front_page_id = (int) get_option('page_on_front');
if ($front_page_id && function_exists('get_field')) {
  $raw_slides = get_field('slides_en_avant', $front_page_id) ?: [];
  $today = current_time('Y-m-d');
  foreach ($raw_slides as $slide) {
    if (empty($slide['active'])) continue;
    if (empty($slide['image']))  continue;
    if (!empty($slide['date_debut']) && $today < $slide['date_debut']) continue;
    if (!empty($slide['date_fin'])   && $today > $slide['date_fin'])   continue;
    $promo_slides[] = [
      'image_id' => (int) $slide['image'],
      'url'      => trim((string) ($slide['url'] ?? '')),
    ];
  }
}
```

Note : `current_time('Y-m-d')` plutôt que `date('Y-m-d')` pour respecter le fuseau horaire WP (Paris).

#### 2. `front-page.php` — Modification du rendu HTML du carousel

Le markup actuel (lignes ~313–345) :

```php
<!-- Full Page Carousel -->
<?php if (!empty($carousel_products)) : ?>
<section class="homepage-carousel-fullscreen">
  <div class="carousel-container">
    <div class="carousel-slides">
    <?php foreach ($carousel_products as $index => $product) : ?>
      <div class="carousel-slide<?php echo $index === 0 ? ' active' : ''; ?>">
        ...
      </div>
    <?php endforeach; ?>
  </div>

    <!-- Hero Text -->
    <div class="carousel-hero-text">
      <h1 class="carousel-hero-title">Luminaires en bois · Atelier Sâpi</h1>
      <h2 class="carousel-hero-subtitle">Fabriqués à la main, à la commande, dans mon atelier à Lyon</h2>
    </div>
  </div>
</section>
<?php endif; ?>
```

À transformer en :

```php
<!-- Full Page Carousel -->
<?php
$total_slides = count($promo_slides) + count($carousel_products);
$slide_index = 0; // compteur global pour déterminer la première slide active
?>
<?php if ($total_slides > 0) : ?>
<section class="homepage-carousel-fullscreen">
  <div class="carousel-container">
    <div class="carousel-slides">

      <?php foreach ($promo_slides as $promo) :
        $is_first = $slide_index === 0;
        $has_url  = $promo['url'] !== '';
        $classes  = 'carousel-slide carousel-slide-promo';
        if ($is_first) $classes .= ' active';

        // Pas de surcharge du 'alt' : on utilise le texte alternatif natif de la médiathèque WP.
        $img_attr = [
          'class'   => 'carousel-slide-img',
          'loading' => $is_first ? 'eager' : 'lazy',
          'sizes'   => '100vw',
        ];
        if ($is_first) $img_attr['fetchpriority'] = 'high';
      ?>
        <?php if ($has_url) : ?>
          <a class="<?php echo esc_attr($classes); ?>" href="<?php echo esc_url($promo['url']); ?>">
        <?php else : ?>
          <div class="<?php echo esc_attr($classes); ?>">
        <?php endif; ?>
            <?php echo wp_get_attachment_image($promo['image_id'], 'full', false, $img_attr); ?>
            <div class="carousel-overlay"></div>
        <?php if ($has_url) : ?>
          </a>
        <?php else : ?>
          </div>
        <?php endif; ?>
        <?php $slide_index++; ?>
      <?php endforeach; ?>

      <?php foreach ($carousel_products as $product) :
        $is_first = $slide_index === 0;
      ?>
        <div class="carousel-slide<?php echo $is_first ? ' active' : ''; ?>">
          <?php
            $img_attr = [
              'class'   => 'carousel-slide-img',
              'alt'     => esc_attr($product['name']) . ' — Luminaire artisanal en bois',
              'loading' => $is_first ? 'eager' : 'lazy',
              'sizes'   => '100vw',
            ];
            if ($is_first) $img_attr['fetchpriority'] = 'high';
            echo wp_get_attachment_image($product['image_id'], 'full', false, $img_attr);
          ?>
          <div class="carousel-overlay"></div>
          <div class="carousel-content">
            <p class="carousel-product-name"><?php echo esc_html($product['name']); ?></p>
          </div>
        </div>
        <?php $slide_index++; ?>
      <?php endforeach; ?>

    </div>

    <!-- Hero Text global — visible sur TOUTES les slides, y compris promo. À ne pas masquer. -->
    <div class="carousel-hero-text">
      <h1 class="carousel-hero-title">Luminaires en bois · Atelier Sâpi</h1>
      <h2 class="carousel-hero-subtitle">Fabriqués à la main, à la commande, dans mon atelier à Lyon</h2>
    </div>
  </div>
</section>
<?php endif; ?>
```

Points de vigilance :
- `$slide_index` est un compteur **global** : seule la toute première slide (peu importe son type) reçoit `active`, `loading="eager"` et `fetchpriority="high"`.
- La condition `if ($total_slides > 0)` remplace `if (!empty($carousel_products))` pour gérer le cas (improbable mais possible) où il n'y aurait que des slides promo et pas de produits.
- **Le hero text global reste TOUJOURS visible** sur toutes les slides — pas de logique pour le masquer. C'est un choix de design (H1 unique pour le SEO + cohérence brand).
- **Le `alt` des slides promo n'est PAS surchargé** dans `$img_attr` : `wp_get_attachment_image()` utilise automatiquement le texte alternatif renseigné dans la médiathèque WP au moment de l'upload. **Ne pas ajouter d'`alt` dans le tableau `$img_attr` des slides promo.** Robin renseignera lui-même un alt pertinent à chaque image qu'il téléversera.

#### 3. `style.css` — Ajouts CSS minimaux

À ajouter à la fin de la section carousel (juste après la règle `.carousel-dot.active` vers la ligne 11680) :

```css
/* ========================================
   SLIDES EN AVANT (carousel promo)
   ======================================== */

/* Slide promo cliquable — supprime le soulignement <a> et héritages couleur */
a.carousel-slide-promo {
  text-decoration: none;
  color: inherit;
  cursor: pointer;
}

/* Léger zoom au survol pour les slides promo cliquables (cohérent avec les bento cards) */
@media (hover: hover) {
  a.carousel-slide-promo:hover .carousel-slide-img {
    transform: scale(1.02);
    transition: transform 0.6s var(--ease-smooth);
  }
}
```

Pas d'autre CSS à toucher. Les classes `.carousel-slide`, `.carousel-slide-img`, `.carousel-overlay` existent déjà et fonctionnent qu'on les pose dans un `<div>` ou un `<a>`.

#### 4. JS — Aucun changement

`assets/homepage-carousel.js` fonctionne déjà avec n'importe quel nombre de slides. Ne pas y toucher.

### Critères de succès

1. **Aucune slide promo configurée** ou aucune dans la fenêtre temporelle → carousel inchangé (8 slides produits, hero text global visible). **Comportement par défaut idem prod actuelle.**
2. Une slide promo `active = true`, dates ouvertes, avec URL → elle apparaît en **première position** dans le carousel, est cliquable (toute la zone, click navigue vers l'URL), avec le hero text global toujours visible par-dessus.
3. Slide promo **sans URL** → visible mais non cliquable (curseur normal, pas de hover zoom).
4. **Date de début dans le futur** ou **date de fin passée** ou **`active = false`** → slide complètement absente du DOM.
5. Plusieurs slides promo configurées → toutes affichées en début de carousel, dans l'ordre du repeater.
6. Première slide (peu importe son type) a bien `loading="eager"` + `fetchpriority="high"`. Les autres `loading="lazy"`.
7. Le `alt` des images promo provient bien de la médiathèque WP (vérifier en inspectant le HTML d'une slide promo : l'attribut `alt` doit afficher le texte alternatif que Robin a saisi à l'upload de l'image, pas du texte généré par le code).
8. Le hero text global (« Luminaires en bois · Atelier Sâpi » / « Fabriqués à la main… ») reste visible et inchangé sur toutes les slides, y compris les slides promo.
9. Le bug du fade reste corrigé (régression à éviter).
10. Pas de régression mobile (≤ 768 px).
11. Le clic sur les dots fonctionne toujours (il continue de fonctionner mécaniquement, mais à vérifier visuellement).

### Notes importantes

- **Branche** : `test-theme-sapi-maison` exclusivement. Pas de master direct.
- **Pas de mockup HTML inventé** : réutiliser strictement les classes existantes documentées plus haut. La seule classe nouvelle est le modificateur `carousel-slide-promo`.
- **Test fonctionnel** : Robin créera une slide test dans l'admin après le déploiement sur test (image avec ou sans texte gravé dessus, URL vers un produit, dates vides) et on validera ensemble avant merge master.

---

## ✅ [TÂCHE] Fix bug fade carousel homepage — slide 1 réapparaît en transition — MERGÉ SUR MASTER (5 mai 2026)

**Date :** 2026-05-05
**Priorité :** haute
**Branche :** `master` (merge fast-forward `a382f61..46e972d`)
**Statut :** ✅ Validé par Robin sur test. ✅ Mergé sur master. 🟡 Robin a déclaré avoir lancé le workflow GitHub Actions AVANT le merge master — **il doit le relancer** pour que le fix carousel soit effectivement en prod.

---

### ⚠️ Note importante (5 mai 2026)

Quand Robin a écrit "J'ai poussé en production !", `origin/master` ne contenait pas encore le commit `46e972d` — il était uniquement sur `test-theme-sapi-maison`. Le workflow Actions a donc déployé la version de master sans le fix.

J'ai mergé `46e972d` sur `master` après coup et poussé. **Robin doit relancer le workflow GitHub Actions** pour que le fix carousel soit effectivement déployé sur `atelier-sapi.fr`.

Pour la prochaine fois : c'est Claude Code qui merge sur master, puis Robin lance le workflow. C'est le séquencement normal du CLAUDE.md du repo.

---

### ✅ Retour Claude Code — fix initial (5 mai 2026, commit `46e972d`)

Suppression du bloc `.carousel-slide:first-child { opacity: 1; z-index: 1 }` (lignes 11494-11498 + ligne vide) dans `style.css`. Diff -5 lignes seulement.

Validé par Robin sur `test.atelier-sapi.fr` (cross-fade propre, plus d'image parasite). Mergé sur master fast-forward.

---

### 📦 Spec d'origine

**Date :** 2026-05-05
**Priorité :** haute
**Branche :** `master` (hotfix visuel léger, validé par Robin)

### Contexte

Le carousel plein écran de la homepage (`.homepage-carousel-fullscreen` dans `front-page.php` lignes 313+, JS dans `assets/homepage-carousel.js`, CSS dans `style.css` à partir de la ligne 11456) a un bug visible à chaque transition à partir de la 2ᵉ slide : pendant le fondu entre la slide en cours et la suivante, l'image de la **slide 1** réapparaît brièvement avant d'être recouverte par la nouvelle slide. Robin l'a signalé comme « ça saute à une autre image pendant le fondu ».

### Cause racine

Trois règles CSS se télescopent dans `style.css` (lignes 11480–11512) :

```css
.carousel-slide               { opacity: 0; transition: opacity 1s var(--ease-smooth); }
.carousel-slide:first-child   { opacity: 1; z-index: 1; }   /* ← redondant et fautif */
.carousel-slide.active        { opacity: 1; z-index: 1; }
```

Le PHP (`front-page.php` ligne 319) ajoute déjà `class="carousel-slide active"` à la première slide via `$index === 0`. Donc la règle `:first-child` est **redondante** pour l'état initial, mais elle reste appliquée **en permanence** à la slide 1, même après que le JS lui a retiré la classe `.active` pour passer à la slide suivante. Résultat : la slide 1 reste à `opacity: 1` et `z-index: 1` tout le temps, et redevient visible dès qu'une autre slide s'efface au-dessus d'elle.

### À faire

**Un seul fichier à modifier : `style.css`.**

Supprimer le bloc lignes 11494–11498 (règle `.carousel-slide:first-child` + sa ligne vide qui suit) :

```css
.carousel-slide:first-child {
  opacity: 1;
  z-index: 1;
}

```

**Avant :**
```css
.carousel-slide {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  opacity: 0;
  transition: opacity 1s var(--ease-smooth);
  display: flex;
  align-items: flex-end;
  justify-content: flex-start;
  overflow: hidden;
}

.carousel-slide:first-child {
  opacity: 1;
  z-index: 1;
}

.carousel-slide-img {
  ...
```

**Après :**
```css
.carousel-slide {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  opacity: 0;
  transition: opacity 1s var(--ease-smooth);
  display: flex;
  align-items: flex-end;
  justify-content: flex-start;
  overflow: hidden;
}

.carousel-slide-img {
  ...
```

C'est tout. La règle `.carousel-slide.active { opacity: 1; z-index: 1; }` plus bas (ligne 11509) gère déjà tout l'état actif (initial + transitions) puisque le PHP marque la 1ʳᵉ slide en `.active` au rendu.

### Critères de succès

1. Sur la homepage en local/test, ouvrir le carousel et laisser tourner la rotation auto (toutes les 5 s) sur au moins 4 transitions consécutives.
2. Aucune image « parasite » ne réapparaît pendant le fondu : on doit voir un cross-fade propre entre la slide qui sort (fade out) et celle qui entre (fade in), avec uniquement ces deux images impliquées.
3. État initial inchangé : la 1ʳᵉ slide est bien visible au chargement (sans flash, sans clignotement), z-index correct.
4. Cliquer sur les dots fonctionne toujours.
5. Pas de régression sur mobile (tester sur largeur ≤ 768 px).

### Notes

- **NE PAS modifier** `assets/homepage-carousel.js` ni `front-page.php` — le bug est purement CSS.
- **NE PAS toucher** aux blocs `.carousel-overlay`, `.carousel-hero-text`, `.carousel-content`, etc. juste en-dessous.
- Hotfix → master direct (pas de feature branch nécessaire), commit unitaire avec message clair.

---

## ✅ [TÂCHE] Galerie Inspiration — Phase 2 — MERGÉ SUR MASTER (28 avril 2026)

**Statut :** ✅ Validé par Robin sur test. ✅ Mergé sur `master` (fast-forward `7c94b79..a382f61`, 5 commits). 🟡 En attente du déclenchement manuel du workflow GitHub Actions par Robin → prod `atelier-sapi.fr/inspiration/`.

---

### ✅ Merge master (28 avril 2026)

Fast-forward `test-theme-sapi-maison` → `master` des 5 commits de la phase 2 :
- `c24cbec` Phase 2 cards intercalées + CTA final
- `80cf801` Passe #2 distribution + radius cohérent
- `91a6612` Hotfix variable `$visible_cards`
- `ca638bb` Corrections contenu C1/C3/C5/C6
- `a382f61` Harmonisation ton C1 + C4 ("je")

+752/−31 lignes au total. Pushé sur `origin/master`.

**À Robin :** lancer le workflow GitHub Actions pour déployer sur `atelier-sapi.fr`.

---

### ✅ Corrections contenu cards (28 avril 2026, commit `ca638bb`)

4 modifications de contenu suite aux retours Robin :

- **C1** — picto remplacé par `assets/icons/picto-french.svg` (déjà présent dans le thème, charge via `<img>` avec `loading="lazy"` et `decoding="async"`). Note : Cowork mentionnait que l'icône France était "déjà sur la fiche produit" — en pratique le fichier SVG existe dans `assets/icons/` mais n'a pas d'usage actif référencé dans le code PHP/CSS du thème (peut-être un legacy). On l'utilise directement depuis sa source.
- **C3** — Titre : "Besoin d'aide pour choisir ?" / Texte : "En quelques questions, Robin vous accompagne." Bouton "Démarrer le configurateur" inchangé.
- **C5** — Titre : "Et une création sur mesure ?" / Texte : "Dimension spécifique, forme nouvelle, couleur favorite ?" Lien "Me contacter" inchangé.
- **C6** — "assemblage à la main" → "ponçage à la main".

C2, C4, C7 finale non modifiées (comme demandé).

---

### 🩹 Hotfix passe #2 (28 avril 2026, commit `91a6612`)

Régression introduite dans la passe #2 : lors du refactor `$visible_cards` → `$cards_at`, la boucle d'affichage référençait encore l'ancienne variable. `isset($visible_cards[$i])` retournait toujours `false` (PHP ne lève pas d'erreur), donc aucune card ne s'affichait visuellement (les photos s'affichaient normalement). Remplacé `$visible_cards` par `$cards_at` à 2 endroits dans la boucle de rendu (page-inspiration.php lignes 238-239).

---

### ✅ Retour Claude Code — passe #2 (28 avril 2026, commit `80cf801`)

**2 fichiers modifiés, +30 / −22 lignes :**

**Fix 1 — Distribution proportionnelle des cards** ([page-inspiration.php](page-inspiration.php)) :
- Remplacé les positions hardcodées `[4, 9, 14, 19, 24, 29]` par un calcul dynamique : `floor($total_tiles * $i / ($nb_cards + 1))`.
- Avec 84 photos + 6 cards = 90 items, positions calculées : ~12, 25, 38, 51, 64, 77 → 2 cards par colonne en desktop (3 cols), 3 cards par colonne en mobile/tablette (2 cols).
- Ordre logique des cards (C1 → C6) préservé, seules les positions changent.
- Garde-fou anti-collision si deux cards tombent à la même position (cas très peu de photos), elles décalent d'un cran.
- Suppression du pré-filtrage `$visible_cards` devenu redondant : la formule garantit positions ≤ total_tiles.

**Fix 2 — Border-radius cohérent** ([assets/inspiration.css](assets/inspiration.css)) :
- Inspecté le radius des cards modernes du thème : `.product-card-cinetique` (cards produit grille shop) et `.bento-card` (homepage) utilisent **`var(--radius-lg)` = 16px**. `.product-specs-table` aussi. C'est le standard moderne.
- Aligné `.inspiration-tile`, `.inspiration-tile-img`, `.inspiration-tile-overlay`, `.inspiration-card` (toutes variantes y compris `--final`) sur `var(--radius-lg, 16px)`.
- Conservé `var(--radius, 5px)` sur les contrôles UI internes : `.inspiration-card__button`, `.inspiration-card__form-input`, `.inspiration-card__form-button`. Cohérent avec le pattern du site (cards en gros radius, contrôles en petit radius).
- Pas de nouveau token créé — réutilisation de l'existant.

**Pas de changement JS** — `assets/inspiration.js` et `assets/robin-conseiller.js` restent identiques à la passe #1.

**À tester par Robin sur `test.atelier-sapi.fr/inspiration/` :**
- Cards visiblement réparties dans **toutes** les colonnes (desktop 3 cols ET mobile/tablette 2 cols), pas toutes en colonne 1.
- Border-radius des photos + cards **identique visuellement** à celui des cards produit (page boutique, archive catégorie). Robin peut comparer côte à côte.
- Aucune régression : hover, masonry, lazy loading, formulaire newsletter, trigger Robin Conseiller, CTA final pleine largeur fonctionnent toujours.

**Si validation OK → merge `test-theme-sapi-maison` → `master`.**

---

### 📦 Passe #2 — spec d'origine

**Statut initial :** Passe #1 implémentée (commit `c24cbec`). 🔴 Robin a testé : 2 problèmes à corriger avant merge master. Passe #2 demandée.

---

### 🔴 Retours Robin — passe #2 à faire

**Problème 1 — Toutes les cards se retrouvent dans la 1re colonne du masonry.**

Cause : avec CSS columns, le navigateur remplit col 1 d'abord (DOM items 1 à N/3), puis col 2, puis col 3. Les positions hardcodées `[4, 9, 14, 19, 24, 29]` ont un espacement de 5 → avec 70-90 photos, les 6 cards tombent toutes dans le premier tiers du DOM = colonne 1.

**Fix demandé : distribution proportionnelle au total d'items** (au lieu de positions fixes hardcodées). Approche :

```php
$nb_cards = 6;
$total_items = count($photos) + $nb_cards;
$card_keys = ['c1', 'c2', 'c3', 'c4', 'c5', 'c6'];
$cards_at = [];
for ($i = 1; $i <= $nb_cards; $i++) {
    $pos = (int) floor($total_items * $i / ($nb_cards + 1));
    $cards_at[$pos] = $card_keys[$i - 1];
}
```

Ainsi avec 90 items (84 photos + 6 cards) → positions calculées à ~13, 26, 39, 51, 64, 77.
- Desktop 3 cols (col 1 = items 1-30, col 2 = 31-60, col 3 = 61-90) → 2 cards par colonne ✅
- Tablette/mobile 2 cols → 3 cards par colonne ✅

L'ordre de placement des 6 cards (C1 → C6) reste celui défini dans la spec (réassurance/histoire/CTA/newsletter/réassurance/histoire). Seules les **positions** changent (calcul dynamique), pas l'ordre des contenus.

**À NE PAS faire** : ne pas changer l'ordre logique des cards (C1 d'abord, C6 en dernier intercalée). Ne pas randomiser leur ordre.

---

**Problème 2 — Border-radius des photos et cards trop petit, pas cohérent avec le reste du site.**

Robin remarque que les coins des `.inspiration-tile` et `.inspiration-card` sont visiblement plus arrondis ailleurs sur le site (cards catégorie, cards produit, etc.).

**Fix demandé :**
1. **Inspecter le `border-radius` utilisé sur les autres composants du site** : cards de catégorie sur la homepage, cards produit sur les pages catégorie, cards "Star du moment", cards de la grille shop, etc.
2. **Identifier la valeur ou le token CSS** réellement utilisé (probablement plus grand que `--radius` actuel — peut-être 16px, 20px, 24px, ou un autre token comme `--radius-lg`).
3. **Aligner** `.inspiration-tile` ET `.inspiration-card` (toutes variantes y compris `--final`) sur cette même valeur. Garder la cohérence : si les cards catégorie utilisent token `--radius-card` ou similaire, l'utiliser ici aussi. Si c'est un radius hardcodé, soit créer un token dans le design system, soit hardcoder la même valeur.
4. **Ne pas créer un nouveau token au passage** — réutiliser l'existant. Si nécessaire, ajouter un `--radius-card` dans les variables CSS globales (mais demander à Robin avant de toucher au design system global).

**À éviter :** ne pas mettre un radius "à la louche" sans aller vérifier ce que fait le site existant. La règle est *cohérence avec l'existant*, pas estimation.

---

### Critères de succès passe #2

- Cards visiblement réparties dans les 3 colonnes (desktop) et 2 colonnes (mobile/tablette), pas toutes en colonne 1.
- Border-radius des photos + cards de la galerie inspiration **identique** à celui des cards produit / cards catégorie ailleurs sur le site (à vérifier visuellement par Robin).
- Aucune régression sur le reste de la page (le hover, le masonry, le lazy loading, le formulaire newsletter, le trigger Robin Conseiller, le CTA final pleine largeur doivent continuer de fonctionner).

---

### 🔴 Retours Robin — corrections de contenu (à intégrer dans la même passe #2)

**Card C1 — "Fait main en France" — changer le pictogramme :**

L'icône actuelle (un outil/marteau) ne correspond pas. **Réutiliser l'icône France** qui est déjà présente sur la fiche produit (probablement un drapeau ou une silhouette de France). Aller l'inspecter dans le template/CSS de la fiche produit (probablement `single-product.php` ou un partial), récupérer le SVG ou la classe d'icône, et l'appliquer sur la card C1 à la place du picto actuel. Garder la même taille / le même cercle de fond beige.

---

**Card C3 — "Pas sûr du modèle..." — réécriture complète :**

Remplacer **titre + texte** par :
- **Titre :** "Besoin d'aide pour choisir ?"
- **Texte :** "En quelques questions, Robin vous accompagne."
- Bouton inchangé : "Démarrer le configurateur" → trigger `[data-robin-open="bandeau"]`

(Note : "questions" au pluriel — c'était une coquille dans le retour de Robin, on corrige.)

---

**Card C5 — "Sur-mesure possible" — réécriture :**

Remplacer **titre + texte** par :
- **Titre :** "Et une création sur mesure ?"
- **Texte :** "Dimension spécifique, forme nouvelle, couleur favorite ?"
- Lien inchangé : "Me contacter" → page contact

---

**Card C6 — "Du bois, de la lumière, et beaucoup de patience" — correction :**

Texte actuel :
> "Découpe laser de précision, assemblage à la main, finitions soignées. Chaque pièce est unique."

Texte cible (remplacer "assemblage" par "ponçage") :
> "Découpe laser de précision, **ponçage** à la main, finitions soignées. Chaque pièce est unique."

(Le ponçage est en effet l'étape manuelle, pas l'assemblage — précision artisanale.)

---

Ne PAS modifier les autres cards (C2, C4, C7 finale).

---

### 📦 Passe #1 (commit `c24cbec`) — pour mémoire

---

### ✅ Retour Claude Code (28 avril 2026, commit `c24cbec`)

**5 fichiers modifiés/créés, +745 / −28 lignes :**

- `page-inspiration.php` (modifié) — refactor pour insérer les 6 cards aux positions 4/9/14/19/24/29 après `shuffle($photos)`, plus la 7e card finale après la boucle. Closures `$render_card()` et `$render_photo()` pour garder la logique compacte.
- `assets/inspiration.css` (modifié) — ajout d'une nouvelle section "CARDS INTERCALÉES" avec `.inspiration-card` + 5 variantes (`--reassurance`, `--story`, `--story-dark`, `--cta`, `--newsletter`, `--final`). Responsive : padding/font-size adaptés mobile vs ≥1024px. `column-span: all` sur la card finale.
- `assets/inspiration.js` (NEW) — handler submit AJAX du form newsletter, timeout 8s, validation email côté client, retours UX `success`/`error` via classes CSS + `aria-live="polite"`.
- `functions.php` (modifié) — handler AJAX `sapi_inspiration_brevo_subscribe` (~30 lignes après le bloc coupon BIENVENUE10), enqueue conditionnel JS + `wp_localize_script` (`sapiInspiration` avec `ajaxUrl` + `nonce`). `sapi_get_brevo_api_key` recopiée du snippet avec garde `function_exists`.
- `assets/robin-conseiller.js` (modifié) — listener click générique `[data-robin-open]` ajouté avant le check `.robin-pill` existant. Permet d'ouvrir la modale depuis n'importe quel élément avec cet attribut, sans dépendre des classes du système Robin.

**Décisions techniques clés :**

1. **Logique d'insertion robuste** — Pré-filtrage `$visible_cards` qui tient compte des cards déjà placées avant : une card à position `pos` est visible si `pos ≤ nb_photos + nb_cards_visibles_avant_elle`. Pas de répétition cyclique au-delà de 30 tuiles. Avec ~24 produits × ~3-4 photos ambiance/detail, on est largement >30 tuiles, donc les 6 cards seront toutes visibles.

2. **Brevo — handler dédié plutôt que mutualisation** — Réponse au besoin de Robin "savoir d'où viennent les inscrits". Nouveau handler `sapi_inspiration_brevo_subscribe` qui inscrit dans la liste #6 avec `SOURCE = "Galerie Inspiration"` (vs `SOURCE = "popup"` côté snippet cookies). Aucun impact sur le snippet popup existant — Robin n'a rien à recoller dans Code Snippets.

3. **Robin Conseiller — trigger générique** — Au lieu de surcharger les classes `.robin-pill` existantes (risque de conflit visuel), ajout d'un sélecteur `[data-robin-open]` au listener click global de `robin-conseiller.js`. Pattern réutilisable ailleurs dans le site. Le bouton C3 ouvre la modale en mode "bandeau" (`data-robin-open="bandeau"`).

4. **Card finale `column-span: all`** — La card est rendue comme tout dernier enfant du conteneur `.inspiration-gallery`, donc CSS columns interrompt le flow et la place en pleine largeur en bas. `display: block; width: 100%;` ajoutés en plus pour garantir le rendu sur Safari.

5. **A11y** — `aria-label` sur le bouton C3, `<label>` sr-only sur l'input newsletter, `aria-live="polite"` sur le status form, `:focus-visible` outline orange cohérent, `prefers-reduced-motion` respecté (transitions/transform désactivés).

6. **Pas de carte BIENVENUE10** — Comme demandé, déjà couvert par le popup cookies, pas de redondance.

**À tester par Robin sur `test.atelier-sapi.fr/inspiration/` :**
- Les 6 cards apparaissent bien aux positions 4, 9, 14, 19, 24, 29 dans la grille (chacune une seule fois).
- 7e card en bas, pleine largeur sur desktop ET mobile, bouton "Voir la boutique" pointe sur la bonne URL.
- Card C3 : clic sur "Démarrer le configurateur" → la modale Robin Conseiller s'ouvre.
- Card C4 : tester un email valide → message succès vert "Merci, vous êtes inscrit·e !" + le contact apparaît dans Brevo liste #6 avec `SOURCE = "Galerie Inspiration"`.
- Card C4 : tester un email invalide → message erreur rouge.
- Card C5 : lien "Me contacter" → `/contact/`.
- Mobile iPhone Safari : cards lisibles, pas de débordement, formulaire utilisable (input + bouton stack vertical sur mobile).
- Hover desktop sur cards : léger lift translateY(-2px) + ombre douce.
- Pas de régression sur les photos phase 1 (overlay hover, masonry, lazy loading).

**Si validation OK → merge `test-theme-sapi-maison` → `master` à faire (Robin déclenchera ensuite le workflow GitHub Actions).**

---

### 📦 Spec d'origine

## [TÂCHE] Galerie Inspiration — Phase 2 : cards intercalées + CTA final

**Date :** 2026-04-28
**Priorité :** normale
**Branche :** `test-theme-sapi-maison` (workflow standard — auto-deploy test, merge master après validation Robin)

**Contexte :**
La page `/inspiration/` (phase 1) est en prod et fonctionne. Phase 2 : intercaler des **cards 100% textuelles** (pas de photos) dans la galerie pour rassurer, raconter et convertir. L'objectif business reste le même : convertir le trafic de cette page (où 91% des visiteurs ne voient aucune fiche produit aujourd'hui sur le site global).

Décisions Robin déjà prises :
- 7 cards au total (6 intercalées à positions fixes + 1 CTA final pleine largeur)
- Pas de photos, uniquement typo + fond coloré + éventuels pictos SVG simples
- Newsletter card → **liste Brevo #6** (réutiliser l'intégration existante du popup cookies)
- Pas de carte "code promo BIENVENUE10" (déjà couverte par le popup cookies, on ne dédouble pas)

---

### Les 6 cards intercalées

À insérer à **positions fixes** dans le tableau final après `shuffle($photos)`. Chaque card occupe une "tuile" du masonry (donc soumise à `break-inside: avoid` comme les photos).

| # | Catégorie | Position | Contenu |
|---|-----------|----------|---------|
| C1 | Réassurance | **4** | **Titre :** "Fait main en France" • **Texte :** "Chaque luminaire est conçu, découpé et assemblé dans l'atelier." • **Style :** fond `--color-warm` (beige), texte `--color-dark`, petit picto SVG (atelier ou drapeau, simple) |
| C2 | Histoire | **9** | **Accroche en *Square Peg* (display) :** "Assemblez, Éclairez, Admirez !" • **Sous-titre Montserrat :** "Le slogan de l'Atelier Sâpi : trois étapes, un luminaire qui vous ressemble." • **Style :** fond `--color-orange`, texte clair |
| C3 | CTA | **14** | **Titre :** "Pas sûr du modèle pour votre pièce ?" • **Texte :** "Robin Conseiller vous oriente en 3 questions." • **Bouton :** "Démarrer le configurateur" → URL du Robin Conseiller (à confirmer avec Robin) • **Style :** fond `--color-dark`, bouton accent orange |
| C4 | Marketing — Newsletter | **19** | **Titre :** "Recevez les coulisses de l'atelier" • **Texte court :** "Nouveautés, projets en cours, et inspirations directement dans votre boîte mail." • **Champ email + bouton "Je m'abonne"** → liste Brevo #6 (réutiliser l'intégration existante du popup cookies, cf. snippet PHP / endpoint Brevo déjà en place) • **Style :** fond `--color-warm`, formulaire inline |
| C5 | Réassurance | **24** | **Titre :** "Sur-mesure possible" • **Texte :** "Une dimension, une teinte ou une essence spécifique ? Parlons-en." • **Lien :** "Me contacter" → page contact • **Style :** fond clair, ton conversationnel |
| C6 | Histoire | **29** | **Accroche en *Square Peg* :** "Du bois, de la lumière, et beaucoup de patience" • **Texte court Montserrat :** "Découpe laser de précision, assemblage à la main, finitions soignées. Chaque pièce est unique." • **Style :** fond `--color-dark`, texte clair, gros titre Square Peg |

**Cas où la galerie a moins de 30 photos :** insérer les cards dans l'ordre, en sautant les positions hors borne. Ne PAS répéter les cards en cycle si la galerie dépasse 30 (chaque card doit apparaître une seule fois sur la page).

---

### La 7e card — CTA final pleine largeur

**Position :** dernier enfant du conteneur masonry, **après toutes les photos et cards intercalées**.

**Contenu :**
- Titre : "Découvrir tous les modèles"
- Sous-titre : "L'ensemble du catalogue Atelier Sâpi"
- Bouton : "Voir la boutique" → `/shop/` (ou page boutique principale, à confirmer)

**Style :**
- **Pleine largeur** : `column-span: all` (CSS columns) pour traverser les 2/3 colonnes du masonry
- Fond `--color-dark`, texte clair, bouton accent orange
- Padding généreux (60-80px vertical), centré
- Hover : léger lift / ombre douce

**Important :** avec CSS columns + `column-span: all`, le navigateur interrompt le flow des colonnes et place la card à l'endroit où elle est dans le DOM. Donc elle DOIT être le **tout dernier enfant** du conteneur `.inspiration-grid` pour apparaître en fin de galerie. Vérifier le rendu sur Safari (qui a parfois des bizarreries avec `column-span: all`).

---

### Implémentation côté template

**Modifs côté `page-inspiration.php` :**

1. Définir un tableau associatif `$cards_at` avec les 6 cards intercalées (clé = position cible, valeur = identifiant de card).
2. Après le `shuffle($photos)`, construire le tableau final : pour chaque index, soit la card si la position est dans `$cards_at`, soit la prochaine photo de la pile shufflée.
3. Foreach sur les items pour rendre tuile par tuile.
4. **Après le foreach**, ajouter la 7e card (CTA final) directement dans le HTML.

Approche pseudo-code :
```php
$photos_pile = $photos_shuffled;
$cards_at = [4 => 'c1', 9 => 'c2', 14 => 'c3', 19 => 'c4', 24 => 'c5', 29 => 'c6'];
$total = count($photos_shuffled) + count($cards_at);

for ($i = 1; $i <= $total; $i++) {
  if (isset($cards_at[$i])) {
    echo render_inspiration_card($cards_at[$i]);
  } else {
    echo render_inspiration_photo(array_shift($photos_pile));
  }
}
// Puis card #7 finale, hors loop
echo render_inspiration_cta_final();
```

**Modifs côté `assets/inspiration.css` :**

- Ajouter classes préfixées `.inspiration-card`, `.inspiration-card--reassurance`, `.inspiration-card--story`, `.inspiration-card--cta`, `.inspiration-card--newsletter`, `.inspiration-card--final`
- Padding 32-48px sur cards intercalées, 60-80px vertical sur card finale
- Typo cohérente avec `--font-display` (Square Peg) pour les accroches émotionnelles, `--font-body` (Montserrat) pour le reste
- `break-inside: avoid` sur cards intercalées (déjà global sur `.inspiration-tile`, vérifier que les cards en héritent ou ajouter explicitement)
- `column-span: all` sur `.inspiration-card--final` + reset `display: block` / `width: 100%` pour garantir le rendu pleine largeur
- Hover desktop : `transform: translateY(-2px)` + ombre douce, transition 0.25s `--ease-smooth`
- A11y : `:focus-visible` outline orange (cohérent avec le reste de la page)

**Intégration Brevo (card C4) :**

Réutiliser l'intégration newsletter existante (popup cookies — snippet PHP "Exécuter partout", endpoint Brevo liste #6). Le formulaire de la card doit envoyer vers le même endpoint et déclencher le même handler. Si l'intégration actuelle est un form HTML simple avec `action` Brevo, copier le pattern. Si c'est un appel AJAX/fetch, exposer une fonction réutilisable.

**Hiérarchie HTML cible (à respecter, ne pas inventer de nouvelles classes hors préfixe `.inspiration-`) :**

```html
<article class="inspiration-card inspiration-card--reassurance">
  <div class="inspiration-card__inner">
    <span class="inspiration-card__icon" aria-hidden="true"><!-- SVG --></span>
    <h3 class="inspiration-card__title">Fait main en France</h3>
    <p class="inspiration-card__text">Chaque luminaire est conçu...</p>
  </div>
</article>
```

Adapter pour chaque type (newsletter avec `<form>`, CTA avec `<a>` englobant, etc.).

---

### À NE PAS faire

- Ne pas toucher au layout/CSS de la grille de catégories ni d'autres composants du site (cf. `feedback_claude_code_refonte_grille.md`).
- Ne pas créer de nouveaux tokens design — réutiliser `--color-dark`, `--color-warm`, `--color-orange`, `--font-display`, `--font-body`, `--ease-smooth`, `--radius`.
- Ne pas dupliquer la logique du popup cookies pour la newsletter — réutiliser l'intégration Brevo liste #6 existante.
- Ne pas inclure de carte "code promo" (BIENVENUE10 déjà géré ailleurs, pas de redondance).
- Ne pas committer/pousser sans accord explicite Robin.

### Critères de succès

- 6 cards apparaissent à positions 4, 9, 14, 19, 24, 29 dans la galerie (chacune une seule fois).
- 7e card en fin de galerie, pleine largeur, bien centrée, sur desktop ET mobile.
- Toutes les cards textuelles, sans photo, cohérentes visuellement avec le design system.
- Card newsletter : inscription effective dans la liste Brevo #6 (testée avec un email de test).
- Aucune régression sur la phase 1 (photos, hover, masonry, lazy loading).
- Mobile (iPhone Safari) : cards lisibles, pas de débordement, formulaire newsletter utilisable.
- A11y : `:focus-visible` actif, `aria-label` sur les liens/boutons CTA, `<label>` (visible ou sr-only) sur le champ email.

### Questions à poser à Robin avant de commencer

1. **URL exacte du Robin Conseiller** pour le bouton de la card C3 (`/conseiller/`, `/configurateur/`, autre ?).
2. **URL exacte de la page contact** pour le lien de la card C5.
3. **URL exacte de la boutique principale** pour le bouton de la card finale C7 (`/shop/`, `/boutique/`, autre ?).
4. **Endpoint / pattern d'intégration Brevo** déjà en place côté popup cookies (form HTML direct ou AJAX ?) — à inspecter dans le snippet existant.

---

## ✅ [TÂCHE] Nouvelle page : Galerie Inspiration — DÉPLOYÉ EN PROD (28 avril 2026)

**Statut :** ✅ Validé par Robin sur test. ✅ Mergé sur `master` (commits `207a3ea` + hotfix `7c94b79`). ✅ Workflow GitHub Actions lancé par Robin → prod `atelier-sapi.fr/inspiration/`.

---

### ✅ Hotfix direct master (28 avril 2026, commit `7c94b79`)

Robin a demandé de limiter la galerie aux 4 catégories produits principales (Suspensions, Lampes à poser, Lampadaires, Appliques) pour exclure les photos hors-catalogue. Ajout d'un `tax_query` sur `product_cat` avec les slugs `suspensions`, `appliques`, `lampadaires`, `lampesaposer` dans le `WP_Query` de `page-inspiration.php`. Vu la trivialité (~7 lignes, aucun impact CSS), Robin a autorisé un commit direct sur `master`. `test-theme-sapi-maison` rebasée pour rester alignée.

### ✅ Merge master (28 avril 2026)

Fast-forward `test-theme-sapi-maison` → `master` du commit `207a3ea` (Galerie Inspiration). Pushé sur `origin/master`.

**À Robin :** lancer le workflow GitHub Actions pour déployer sur `atelier-sapi.fr` (deux commits à déployer : `207a3ea` + `7c94b79`).

---

### ✅ Retour Claude Code (28 avril 2026, commit `207a3ea`)

**Branche utilisée :** `test-theme-sapi-maison` (validée par Robin — workflow standard).

**Fichiers créés / modifiés :**
- `page-inspiration.php` (NEW) — template `Galerie Inspiration`. WordPress le charge automatiquement via la hiérarchie de templates parce que la page a le slug `inspiration` (pas besoin d'assigner le template depuis l'admin, le champ "Modèle" peut rester sur "Modèle par défaut").
- `assets/inspiration.css` (NEW) — CSS autonome, toutes les classes préfixées `.inspiration-*`. Tokens design system réutilisés (`--color-dark`, `--color-warm`, `--color-orange`, `--ease-smooth`, `--font-display`, `--font-body`, `--radius`).
- `functions.php` (modifié) — enqueue conditionnel CSS via `is_page('inspiration')` + fallback `is_page_template('page-inspiration.php')`.

**Logique du template :**
1. `WP_Query` sur tous les produits publiés (`posts_per_page` = 200, `fields` = ids, `no_found_rows`). Volume contrôlé ~24 produits, marge confortable.
2. Pour chaque produit, parcourt l'ACF `galerie_produit` et ne retient que les photos avec `type_photo` ∈ {`ambiance`, `detail`}. Tout le reste exclu (studio WC, taille, client, fabrication, etc.).
3. `shuffle($photos)` → ordre aléatoire à chaque chargement (pas de mise en cache de l'ordre).
4. Helper PHP `inspiration_format_product_name()` qui reproduit la logique de `product-name-formatter.js` (split premier mot / reste) → sortie HTML directe avec `.product-firstname` + `.product-restname`. Pas de FOUC, et le formatter JS ignore les éléments déjà formatés.
5. `wp_get_attachment_image()` size `large` pour ratio natif + srcset auto. Premières 6 = `loading="eager"` (LCP), reste = `lazy`. `decoding="async"` partout. `fetchpriority="high"` sur la 1ère image.
6. Alt = meta `_wp_attachment_image_alt` de la médiathèque, fallback sur le nom du produit.
7. Lien `<a>` englobant la tuile vers `get_permalink($product_id)`.

**CSS — masonry CSS columns :**
- Mobile + tablette (< 1024px) : `column-count: 2` + gap 16px.
- Desktop (≥ 1024px) : `column-count: 3` + gap 24px.
- `break-inside: avoid` (+ vendors) sur chaque tuile pour éviter les coupures.
- **Desktop** : overlay sombre (`rgba(50, 50, 50, 0.55)`) qui apparaît au hover/focus, nom centré qui fade-in + zoom léger sur l'image (`scale(1.03)`, transition 0.35s `--ease-smooth`).
- **Mobile/tablette** : pas de hover → dégradé subtil (transparent → `rgba(50,50,50,0.55)`) sur les ~40% du bas, nom permanent en bas, font-size réduit (0.875rem).
- `prefers-reduced-motion: reduce` désactive transitions et zoom hover.
- `:focus-visible` outline orange pour la navigation clavier.

**SEO :**
- Yoast gère title/meta description automatiquement (Robin peut les éditer côté admin).
- Page indexable par défaut (Yoast).
- `<h1>` = titre WP de la page (`the_title()`).
- Si Robin ajoute du contenu dans l'éditeur WP, il s'affiche dans `.inspiration-content` sous le H1.

**Performance :**
- 1 seule requête SQL principale (WP_Query avec fields=ids + no_found_rows).
- ACF `get_field` par produit (mis en cache par ACF).
- Lazy au-delà des 6 premières → critique vu qu'on charge potentiellement 100+ images.
- Aucun JS additionnel : le formatter JS existant suffit (et il ignore les éléments déjà formatés en PHP).

**Points de jugement / écarts mineurs vs spec :**
- **Hero/intro discret ajouté** (`<h1>` + zone `the_content()`). Spec ne le mentionnait pas explicitement, mais nécessaire pour SEO/a11y. Si Robin veut une page 100% galerie sans titre visible, je peux retirer ou masquer la section `.inspiration-intro`.
- **`posts_per_page` = 200** au lieu de `-1`. Règle absolue du CLAUDE.md interdit `-1`, et 200 couvre largement les ~24 produits actuels avec marge d'évolution.
- **Pas de structure duale** (`wp-content/themes/theme-sapi-maison/`) : tout est au root du repo localement. Pas de `rsync` nécessaire dans cet environnement. **À noter pour Cowork :** le CLAUDE.md du thème mentionne une structure duale qui n'est pas active dans le checkout actuel — soit la doc est désactualisée, soit l'environnement local diffère. À clarifier hors-tâche.

**À tester par Robin sur `test.atelier-sapi.fr/inspiration/` :**
- Toutes les photos `ambiance` + `detail` apparaissent (et seulement celles-là).
- Ordre change à chaque rechargement.
- Masonry 3 cols desktop / 2 cols tablette + mobile, pas de coupure de tuile.
- Hover desktop : overlay + nom modèle bien formaté (premier mot Montserrat bold uppercase, reste Square Peg).
- Mobile (iPhone Safari) : nom visible en bas en permanence, lisible, pas envahissant.
- Clic = arrivée sur la bonne fiche produit.
- Pas de régression CSS sur les autres pages du site (page produit, catégories, homepage, panier).
- Performance perçue OK (les images au-delà de la vue ne se chargent qu'au scroll).

**Si validation OK → merge `test-theme-sapi-maison` → `master` à faire (Robin déclenchera ensuite le workflow GitHub Actions pour la prod).**

---

### 📦 Spec d'origine

**Date :** 2026-04-28
**Priorité :** normale
**Branche :** à confirmer avec Robin (probablement `master` puisque c'est une nouvelle page indépendante, pas lié à `feature/refonte-fiche-produit`). Demander avant de commencer.

**Contexte :**
On veut une page d'inspiration visuelle qui montre l'ensemble des ambiances et détails des luminaires en un seul coup d'œil. Objectif business : créer un point d'entrée alternatif vers les fiches produits (le goulot GA4 actuel = 91% des visiteurs ne voient jamais une fiche produit). Une galerie léchée style "lookbook" qui pousse au clic.

Pas de lien dans le menu/footer pour le moment — la page existe mais reste accessible uniquement via URL directe. On verra plus tard pour la mise en avant.

**À faire :**

1. **Page WordPress déjà créée par Robin** : titre "Galerie Inspiration", slug `inspiration`, état Publié, modèle "Modèle par défaut", contenu vide.
   → **À faire côté thème : créer le fichier `page-inspiration.php`** dans le thème. WordPress le matchera automatiquement au slug `inspiration` via le template hierarchy — aucune manip supplémentaire requise côté admin pour Robin.
   → Le contenu de la page (vide) sera ignoré, c'est le template qui rend tout.

2. **Récupérer les images** : pour tous les produits publiés (`product` post_type, status `publish`), parcourir l'ACF `galerie_produit` et ne garder que les photos dont le type est `ambiance` **ou** `detail`. Exclure tout ce qui est studio WC. (Cf. mémoire interne : seul `ambiance` existe depuis le 15 avril, plus de `ambianceH`/`ambianceV`.)

3. **Layout — masonry CSS columns** qui respecte le **ratio original** de chaque image. Donc :
   - Utiliser le size `full` (ou `large` si full > 2000px) — **pas** de focal point ni de crop ici, on veut le format natif.
   - `column-count` responsive :
     - Desktop (≥ 1024px) : **3 colonnes**
     - Tablette (768–1023px) : **2 colonnes**
     - Mobile (< 768px) : **2 colonnes** (choix Robin)
   - `column-gap` cohérent avec le design system existant (cf. variables CSS du thème).
   - `break-inside: avoid` sur chaque tuile pour éviter les coupures.

4. **Interaction par tuile** :
   - **Au survol (desktop)** : overlay sombre semi-transparent (cohérent avec l'esthétique du site) avec uniquement le **nom du modèle** centré, formaté via la fonction JS de formatage du nom produit déjà présente dans le thème (celle qui sépare le modèle de la taille — chercher `formatProductName` ou équivalent dans les fichiers JS du thème). **Si la fonction n'est pas réutilisable côté template, reproduire l'équivalent en PHP au moment du rendu HTML** — pas de duplication de logique inutile, juste rendre le même résultat.
   - **Au clic** : lien `<a>` englobant la tuile, vers la fiche du produit parent (`get_permalink( $product_id )`).
   - **Mobile (pas de hover)** : afficher le nom du modèle de manière permanente mais discrète, par exemple en bas de tuile avec un fond subtil sur les derniers ~30% de la hauteur (dégradé transparent → sombre). Garder lisible mais pas envahissant.

5. **Ordre d'affichage** : **aléatoire à chaque chargement** de la page (`shuffle()` PHP côté serveur après collecte des images). Pas de mise en cache de l'ordre.

6. **SEO** :
   - Title et meta description configurables via Yoast (laisser le champ libre, ne pas hardcoder).
   - Indexation autorisée.
   - Image `alt` = texte alt déjà saisi dans la médiathèque WordPress (récupérer via `get_post_meta( $attachment_id, '_wp_attachment_image_alt', true )`).

7. **Performance** :
   - `loading="lazy"` sur toutes les images **sauf les 6 premières** (LCP).
   - `decoding="async"` partout.
   - Vu qu'on charge potentiellement 100+ images, c'est essentiel — pas de compromis sur le lazy.

**À NE PAS faire :**
- Ne pas réécrire ni toucher au CSS de la grille de catégories existante ni d'autres composants du site. Cette page doit être totalement autonome côté CSS (préfixer toutes les classes avec `.inspiration-` ou équivalent pour éviter les collisions).
- Ne pas créer de nouveaux tokens design — réutiliser les variables CSS existantes du thème (couleurs, espacements, typo).
- Ne pas committer sur prod ni pousser quoi que ce soit sans accord explicite de Robin. Pousser d'abord sur la branche test si on déploie.
- Ne pas utiliser `wp_get_attachment_image()` avec un size cropped/focal point pour les images de la galerie : on veut les ratios natifs.

**Critères de succès :**
- La page `/inspiration/` charge et affiche toutes les photos `ambiance` + `detail` des produits publiés.
- Ratios originaux respectés, masonry propre desktop + tablette + mobile (2 colonnes mobile).
- Survol desktop = overlay avec nom modèle bien formaté ; mobile = nom visible en permanence en bas de tuile.
- Clic = arrivée sur la bonne fiche produit.
- Lazy loading actif au-delà des 6 premières images.
- Aucune régression CSS/JS sur les autres pages du site.
- Page chargée en < 3s même avec 100+ images (grâce au lazy).

---

## ✅ [TÂCHE] Fix responsive modal Colissimo point relais — mobile — MERGÉ SUR MASTER (24 avril 2026)
**Date :** 2026-04-24
**Priorité :** normale
**Branche :** `master` (merge fast-forward `e66547a..e1c12d7`) — **workflow GitHub Actions à lancer par Robin**
**Statut :** ✅ Validé iPhone Safari. ✅ Mergé sur master. 🟡 En attente du déclenchement manuel du workflow GitHub Actions → prod.

---

### ✅ Merge master (24 avril 2026)

Décision finale : pas de passe #2, on pousse tel quel. Fast-forward `test-theme-sapi-maison` → `master` du seul commit manquant (`e1c12d7` Colissimo) — les 3 commits Express Payment (`4afe1a1`, `4b2dacb`, `e66547a`) étaient déjà sur master.

**À Robin :** lancer le workflow GitHub Actions pour déployer sur `atelier-sapi.fr`.

---

### ✅ Retour Claude Code — passe #1 (24 avril 2026, commit `e1c12d7`)

Bloc CSS ajouté dans `style.css` à la fin de la section PAGE SUIVI COLISSIMO (après la ligne 23546, juste avant `/* PAGE STAR DU MOMENT */`) — identique à la spec de la tâche : `@media (max-width: 768px)` avec `!important` sur `#lpc_widget_container`, `.widget_colissimo_contenu`, map, liste, filtres.

Commit + push sur `test-theme-sapi-maison` → auto-deploy `test.atelier-sapi.fr`. Validé par Robin sur iPhone Safari. En attente de la passe #2 avant merge master.

---

### 📦 Spec d'origine

**Date :** 2026-04-24
**Priorité :** normale
**Branche :** `test-theme-sapi-maison` — **déployer test avant prod**

**Contexte :**
Sur mobile (Safari iPhone confirmé), le modal de sélection d'un point relais Colissimo au checkout est quasi inutilisable : la map est écrasée en petite bande en bas de l'écran, la liste prend toute la place. Constaté sur test ET prod — c'est un problème structurel du plugin, pas un bug propre au site.

**Cause racine — dimensions fixes en px et 3 media queries cosmétiques uniquement :**

Le plugin "Colissimo Officiel" (classes `lpc_*` + `widget_colissimo_*`) utilise des dimensions fixes :

```css
.widget_colissimo_contenu  { width: 1000px; height: 574px; }
.widget_colissimo_map      { width: 598px; height: 483px; float: right; top: -57px; }
```

Et **seulement 3 `@media screen` en tout**, toutes sur l'input d'adresse (border-radius / margin-top). Rien sur la map ni sur la liste. Sur un viewport mobile, ces dimensions fixes cassent complètement le layout.

Une tâche complémentaire a été créée dans le gestionnaire en ligne (**S22** — section siteweb) pour évaluer à terme un remplaçant au plugin (Boxtal, Sendcloud, etc.). En attendant, on patche en CSS.

**À faire — ajouter ce bloc CSS dans `style.css`, à la fin de la section PAGE SUIVI COLISSIMO (ligne ~23232, avant le commentaire suivant) :**

```css
/* ==========================================================================
   Modal choix point relais Colissimo — Fix responsive mobile
   ==========================================================================
   Le plugin utilise des dimensions fixes en px (container 1000x574, map
   598x483 floatée à droite) et n'a que 3 media queries cosmétiques. Sur
   mobile, la map est écrasée et la liste prend tout l'écran.
   On force une répartition lisible : liste limitée à 40vh, map au moins 
   300px / 50vh. Fallback : tâche S22 pour évaluer un autre plugin à terme.
*/
@media (max-width: 768px) {
  /* Container + contenu : plein écran, hauteur auto */
  #lpc_widget_container {
    width: 100% !important;
    max-width: 100vw !important;
  }

  .widget_colissimo_contenu {
    width: 100% !important;
    max-width: 100vw !important;
    height: auto !important;
    box-sizing: border-box !important;
  }

  /* Map : pleine largeur, hauteur confortable, non-floatée */
  #widget_colissimo_map,
  .widget_colissimo_map {
    width: 100% !important;
    height: 50vh !important;
    min-height: 300px !important;
    float: none !important;
    top: 0 !important;
    position: relative !important;
    border: none !important;
  }

  /* Liste : pleine largeur, scrollable, limitée à 40vh */
  #widget_colissimo_liste,
  .widget_colissimo_liste {
    width: 100% !important;
    max-height: 40vh !important;
    float: none !important;
    overflow-y: auto !important;
  }

  /* Zones adresse + filtres : pleine largeur */
  #widget_colissimo_table_adresse,
  #widget_colissimo_filtres {
    width: 100% !important;
  }
}
```

**Procédure :**

1. Ajouter sur `test-theme-sapi-maison`, commit + push → auto-deploy test.
2. Robin teste sur son iPhone Safari à `https://test.atelier-sapi.fr/validation-de-la-commande/` :
   - Ajouter un produit physique (ex. Gaston) au panier
   - Aller au checkout, choisir "Livraison en point relais"
   - Cliquer "Choisir le point de retrait" → modal s'ouvre
   - Vérifier : map visible (min 300px), liste scrollable, adresse / filtres lisibles, pas de débordement horizontal
3. Si OK : merger `test-theme-sapi-maison` sur `master` → prod.

**Critères de succès :**
- Map clairement visible et interactive sur iPhone
- Liste des points relais scrollable verticalement, sans prendre tout l'écran
- Pas de scroll horizontal dans le modal
- Aucun impact sur la version desktop (les règles sont sous `@media (max-width: 768px)`)

---

## 🟡 [TÂCHE] Fix affichage boutons Apple Pay / Google Pay — panier + checkout — EN COURS (24 avril 2026)
**Date :** 2026-04-24
**Priorité :** normale
**Branche :** `test-theme-sapi-maison` (commits `4afe1a1` boutons + `4b2dacb` titre masqué + `e66547a` passes #3+#4) — **pas encore sur prod**
**Statut :** Panier ✅ validé. Checkout 🟡 passes #3 + #4 déployées sur test, en attente de validation visuelle Robin avant merge master.

---

### ✅ Ce qui a été fait

Bloc CSS ajouté dans `style.css` juste avant `/* Payment Methods */` (désormais ligne ~18246) — identique à la spec d'origine (reset `padding-left` sur le `<ul>`, neutralisation des `margin-left: 1px / width: 99%` WooPayments sur les `<li>`, `grid-template-columns: 1fr` sous 768px).

Commit + push sur `test-theme-sapi-maison` → auto-deploy `test.atelier-sapi.fr`. **PAS de commit sur `master`** (la queue disait "branche master" mais la procédure exigeait "test avant prod" — seule branche test permet le déploiement test selon CLAUDE.md).

### ✅ Résultat panier (`/mon-panier/`) — validé Robin
Apple Pay + Google Pay empilés, centrés, alignés avec le bouton vert "VALIDER LA COMMANDE". Nickel.

### ❌ Résultat checkout (`/validation-de-la-commande/`) — bug résiduel
Le fix "boutons" fonctionne (plus de débordement, plus de chevauchement avec "Ou continuez ci-dessous"), **mais** un bug pré-existant devient visible maintenant que les boutons occupent toute la largeur proprement : le **titre `Validation de commande express`** (rendu par WooCommerce Blocks / WooPayments, pas par le thème) se place AU MÊME NIVEAU que les boutons et se retrouve caché dessous — visible en fantôme à gauche/droite du bloc G Pay sur desktop, et visible entre les deux boutons quand les deux s'affichent (le "m" de "commande" apparaît entre Apple Pay et G Pay).

Sans DOM live pour inspecter, je ne peux pas identifier la classe exacte du titre ni la règle qui cause la superposition. Deux pistes probables :
1. Le `<ul>` des boutons ou le `<h2>` du titre a une position absolue / un margin-top négatif côté plugin.
2. Le titre est rendu dans un wrapper que le thème n'a pas prévu de stacker.

---

### ✅ Retour Claude Code — passes #3 + #4 appliquées (24 avril 2026, commit `e66547a`)

Les deux blocs CSS des passes #3 (masquage `<li>` Apple Pay via `@supports not (-webkit-appearance: -apple-pay-button)`) et #4 (border-top + border-radius 4px + padding 16px + neutralisation des marges résiduelles sur `.content` et `.event-buttons`) ont été ajoutés dans `style.css` à la suite du bloc existant du fix Express Payment (juste après la règle `display: none` sur le `title-container`, avant `/* Payment Methods */`).

Poussés sur `test-theme-sapi-maison` — auto-deploy test en cours. **Rien mergé sur master.**

En attente de validation visuelle Robin sur `https://test.atelier-sapi.fr/validation-de-la-commande/` (hard refresh) :
- Chrome/Firefox : un seul bouton G Pay collé en haut, 16px symétrique haut/bas, 4 coins arrondis.
- Safari (si dispo) : 2 boutons côte à côte, cadre fermé.
- Panier inchangé.

Si validé : merge `test-theme-sapi-maison` → `master` des 3 commits (`4afe1a1` + `4b2dacb` + `e66547a`) en un seul passage.

---

### 🟢 Passe #4 — fermer le cadre + resserrer le vide (24 avril 2026, passe Cowork #4)

**Observation Robin après passe #3 :** la règle `@supports` masque bien le li Apple Pay sur Chrome/Firefox/Edge — OK. **MAIS** Robin, testant sur Safari desktop (où les 2 boutons s'affichent correctement), constate deux défauts visuels résiduels qu'il avait déjà mentionnés :

1. **Cadre ouvert en haut au centre** — quand on a masqué le `title-container` (passe #2), on a aussi retiré ses `::before`/`::after` qui dessinaient les DEUX segments du top-border (`::before` = segment top-left avec `border-top+left+radius 4px 0 0 0`, `::after` = segment top-right symétrique). Le `.content` n'a qu'un `border-width: 0 1px 1px` + `border-radius: 0 0 4px 4px` → coins hauts droits, pas de bordure supérieure. Visuellement les bords gauche/droit remontent verticalement sans se rejoindre en haut. Robin perçoit ça comme une "interruption au centre".

2. **Vide asymétrique autour des boutons** — mesures desktop Safari :
   - 20px en haut (padding-top du `.content`)
   - Boutons 56px
   - 37px en bas (margin-bottom: 20px du `<ul>` + padding-bottom: 16px du content + 1px border)

Le décalage vient de `margin-top: 5px` résiduel sur `.content` (prévu à l'origine pour laisser passer le title absolu au-dessus) + `margin-bottom: 20px` sur le `<ul>`.

**À faire — ajouter ce bloc CSS dans `style.css`, à la suite du fix passe #3 :**

```css
/* Passe #4 : fermer le cadre et resserrer les paddings
   - Le title-container masqué (passe #2) a supprimé les ::before/::after qui
     dessinaient le top-border du cadre. On ferme avec un border-top continu
     et on passe le border-radius à 4px uniforme (coins hauts arrondis aussi).
   - On enlève le margin-top: 5px résiduel (qui laissait passer le title absolu)
     et le margin-bottom: 20px du ul, pour obtenir 16px en haut + 16px en bas
     autour des boutons — symétrique et sobre. */
.wc-block-components-express-payment--checkout .wc-block-components-express-payment__content {
  border-top-width: 1px !important;
  border-top-style: solid !important;
  border-top-color: color-mix(in srgb, currentcolor 20%, transparent) !important;
  border-radius: 4px !important;
  margin-top: 0 !important;
  padding: 16px !important;
}

.wc-block-components-express-payment--checkout .wc-block-components-express-payment__event-buttons {
  margin-bottom: 0 !important;
}
```

**Procédure :**

1. Ajouter sur `test-theme-sapi-maison`, commit + push → auto-deploy test.
2. Robin valide sur Safari desktop (les 2 boutons) + sur Chrome mobile (uniquement G Pay, masquage li Apple Pay de la passe #3) :
   - Cadre fermé avec 4 coins arrondis uniformes (4px).
   - 16px de vide au-dessus des boutons, 16px en-dessous — symétrique.
   - Pas de régression sur le panier.
3. Si OK : merger `test-theme-sapi-maison` sur `master` → prod (tous les fixes en un seul merge : boutons panier, boutons checkout, titre masqué, Apple Pay masqué sur navigateurs non-Safari, cadre fermé).

---

### 🟢 Passe #3 — diagnostic complet + fix (24 avril 2026, passe Cowork #3)

**Mesure DOM faite sur test, avec 1 produit au panier :**

Le wrapper externe arrondi visible sur les captures de Robin n'est PAS spécifique au bloc Express Payment — c'est la grande carte checkout `.wc-block-components-main.wc-block-checkout__main.wp-block-woocommerce-checkout-fields-block` qui englobe TOUT le tunnel (Express Payment + Coordonnées + Livraison + …) avec `border: 1px solid #F1F1F1; border-radius: 16px; padding: 32px`. Sa bordure est **continue**, pas interrompue. Le diagnostic "bordure interrompue" de la passe précédente était une extrapolation à partir du vide vertical.

**Le vrai coupable du vide vertical — le `<li>` Apple Pay vide :**

Dans le `<ul class="wc-block-components-express-payment__event-buttons">` il y a TOUJOURS deux `<li>` :
- `#express-payment-method-woocommerce_payments_express_checkout_applePay`
- `#express-payment-method-woocommerce_payments_express_checkout_googlePay`

Sur un navigateur qui ne supporte pas Apple Pay (Chrome, Firefox, Edge — la majorité du trafic), Stripe rend quand même un iframe vide de ~8px dans le li Apple Pay. Or WooPayments impose `min-height: 48px` sur chaque `<li>` → le li Apple Pay occupe visuellement 48px alors qu'il est vide.

Résultat en layout mobile (1fr, notre override) : ul = 48px (Apple Pay vide) + 12px (gap) + 48px (G Pay visible) = **108px dont 60px de vide au-dessus de G Pay**. C'est exactement ce que Robin voit sur les captures.

WooPayments a bien une règle `@supports not (-webkit-appearance: -apple-pay-button) { #…applePay:has(#express-checkout-button-preview-applePay) { display: none } }` — mais elle dépend d'un élément preview qui n'est pas toujours rendu dans cette config (c'est pour ça que la protection native ne marche pas ici).

**À faire — ajouter ce bloc CSS dans `style.css`, dans le même bloc que le fix Express Payment déjà en place :**

```css
/* Masquer le <li> Apple Pay quand le navigateur ne supporte pas Apple Pay
   (Chrome, Firefox, Edge, ...). Stripe rend un iframe vide de 8px dans ce li
   et WooPayments impose min-height: 48px → d'où un vide de ~60px au-dessus de
   Google Pay en layout mobile (1fr). La règle :has() native de WooPayments ne
   protège pas dans cette config car elle dépend d'un élément preview parfois
   absent. Safari (qui supporte -apple-pay-button) voit le li normalement. */
@supports not (-webkit-appearance: -apple-pay-button) {
  #express-payment-method-woocommerce_payments_express_checkout_applePay {
    display: none !important;
  }
}
```

**Procédure :**

1. Ajouter la règle sur la branche `test-theme-sapi-maison`.
2. Commit + push → auto-deploy test.
3. Robin valide sur `https://test.atelier-sapi.fr/validation-de-la-commande/` (Chrome desktop + Chrome mobile) : plus de vide au-dessus de Google Pay, le bouton est collé au haut du ul comme attendu.
4. Idéalement test également sur Safari iOS si dispo (pour vérifier que les 2 boutons s'affichent bien côte à côte — comportement normal). Si pas de Safari sous la main, on peut valider côté Chrome et assumer que Safari marche (la `@supports` est le mécanisme officiel CSS pour ce cas).
5. Si OK : merger `test-theme-sapi-maison` sur `master` → prod.

---

### 🟢 Diagnostic complété + fix validé par Robin (24 avril 2026, passe Cowork #2)

**Cause racine du bug résiduel :**

Le sélecteur cible est `.wc-block-components-express-payment__title-container`. Mesuré sur test.atelier-sapi.fr :

- `title-container` : `position: absolute; top: -4px; left: 0; right: 0` (posé par WooPayments)
- `<h2 class="wc-block-components-express-payment__title">` : **font-size: 22.4px** (hérité d'une règle `h2` du thème — WC Blocks prévoit une taille plus petite par défaut)
- Résultat : title-container fait 97px de haut et recouvre entièrement les boutons (overlap de ~73px mesuré).

Le `<h2>` est le seul enfant direct du title-container (les `::before` / `::after` sont les lignes décoratives de part et d'autre du titre).

**Fix retenu — Option 1 (masquer le titre) :**

Le titre "Validation de commande express" est redondant — Apple Pay / G Pay sont universellement reconnus et la barre "Ou continuez ci-dessous" juste en dessous assure la transition visuelle vers le formulaire de paiement classique.

**À faire — ajouter cette règle CSS dans `style.css`, dans le même bloc que le fix Express Payment déjà en place (juste avant `/* Payment Methods */`) :**

```css
/* Masquer le titre "Validation de commande express" — posé en position:absolute
   par WooPayments et recouvre les boutons à cause d'un h2 à 22.4px (héritage du
   thème). Apple Pay / Google Pay sont self-explanatory ; la barre
   "Ou continuez ci-dessous" assure la séparation visuelle. */
.wc-block-components-express-payment--checkout .wc-block-components-express-payment__title-container {
  display: none !important;
}
```

**Procédure :**

1. Ajouter la règle dans `style.css` sur la branche `test-theme-sapi-maison`.
2. Commit + push → auto-deploy test.
3. Robin valide sur `https://test.atelier-sapi.fr/validation-de-la-commande/` (desktop + mobile) : les boutons sont visibles seuls, aucun texte fantôme, la barre "Ou continuez ci-dessous" est toujours là juste en-dessous du bloc boutons.
4. Si OK : merger `test-theme-sapi-maison` sur `master` pour déploiement prod (fix panier + fix checkout en un seul merge).

---

### 📋 État des critères de succès

- ✅ Panier centré, aligné avec VALIDER LA COMMANDE
- ⏸️ Checkout desktop ≥ 768px : deux boutons côte à côte sans débordement → OK fonctionnellement, bug visuel du titre à régler
- ⏸️ Checkout mobile < 768px : boutons empilés sans débordement → OK fonctionnellement, bug visuel du titre à régler
- ✅ Aucun impact sur les `<ul>` / `<ol>` du reste du site

---

### 📦 Contexte original (conservé pour référence)

**Contexte :**
Les boutons Express Payment (Apple Pay / Google Pay) générés par WooPayments s'affichent mal sur deux pages :

- **Panier (`/mon-panier/`)** : boutons empilés verticalement mais légèrement décalés vers la droite, pas bien centrés dans la carte "Total panier".
- **Checkout (`/validation-de-la-commande/`)** : sur mobile étroit, les deux boutons essaient de se mettre côte à côte et débordent du conteneur, passant visuellement par-dessus la barre "Ou continuez ci-dessous" juste en dessous.

**Cause racine identifiée :**

Trois règles CSS qui se télescopent.

1. **Thème (`style.css` l. 16745-16749)** — règle "prose style" générique :
   ```css
   .page-default .entry-content ul,
   .page-default .entry-content ol {
     padding-left: 1.5rem;   /* = 24px */
     margin-bottom: 1.25rem;
   }
   ```
   Spécificité `(0,0,2,1)` → écrase le reset `padding: 0` du bloc WC (spécificité `(0,0,2,0)`) et colle un `padding-left: 24px` sur le `<ul class="wc-block-components-express-payment__event-buttons">`. C'est ce qui décale les boutons vers la droite sur le panier.

2. **WooPayments (plugin)** — règle suspecte avec `!important` :
   ```css
   .wc-block-components-express-payment .wc-block-components-express-payment__event-buttons > li {
     margin-left: 1px !important;
     width: 99% !important;
   }
   ```
   Résultat : les `<li>` ne remplissent pas 100% de leur colonne grid et sont décalés de 1px.

3. **WooPayments (plugin) — checkout uniquement** :
   ```css
   .wc-block-components-express-payment--checkout .wc-block-components-express-payment__event-buttons {
     display: grid;
     grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
     gap: 12px;
   }
   ```
   `auto-fit` avec `minmax(150px, 1fr)` force deux colonnes dès que ~300 px sont disponibles. Combiné au `padding-left: 24px` du thème qui mange l'espace, ça déborde sur mobile étroit.

**À faire — ajouter ce bloc CSS dans `style.css`, juste AVANT le commentaire `/* Payment Methods */` (ligne 18219) :**

```css
/* ==========================================================================
   Fix Express Payment (Apple Pay / Google Pay) — WooPayments + WC Blocks
   ==========================================================================
   Le sélecteur `.page-default .entry-content ul` du thème écrase le reset
   de WooCommerce Blocks sur le <ul> des boutons Express Payment et y ajoute
   un padding-left asymétrique. WooPayments pose aussi des règles
   `> li { margin-left: 1px; width: 99% }` qui décalent les boutons.
   Enfin sur mobile le grid auto-fit force 2 colonnes qui débordent.
   On remet à zéro le padding, on neutralise le margin/width bizarres, et
   on force 1 colonne sous 768px sur la page checkout.
*/
.wc-block-components-express-payment .wc-block-components-express-payment__event-buttons {
  padding-left: 0 !important;
  margin-bottom: 20px !important;
}

.wc-block-components-express-payment .wc-block-components-express-payment__event-buttons > li {
  margin-left: 0 !important;
  width: 100% !important;
}

@media (max-width: 768px) {
  .wc-block-components-express-payment--checkout .wc-block-components-express-payment__event-buttons {
    grid-template-columns: 1fr !important;
  }
}
```

**Procédure de déploiement :**

1. Créer la modif en local sur `master` (ne PAS commit tout de suite).
2. Pusher sur `test.atelier-sapi.fr` pour validation visuelle.
3. Attendre OK de Robin sur test (desktop + mobile, panier + checkout).
4. Commit + push sur prod.

**Critères de succès — tester avec 1 produit au panier :**

- **Page `/mon-panier/`** : les deux boutons Apple Pay + Google Pay sont centrés horizontalement dans la carte "Total panier", alignés avec le bouton vert "VALIDER LA COMMANDE" juste en dessous, sans décalage vers la droite.
- **Page `/validation-de-la-commande/` desktop (≥ 768px)** : les deux boutons s'affichent côte à côte, chacun remplissant sa moitié de ligne, sans déborder du conteneur, avec 12px de gap entre les deux.
- **Page `/validation-de-la-commande/` mobile (< 768px)** : les deux boutons s'empilent verticalement, chacun pleine largeur, aucun débordement horizontal, aucun chevauchement avec la barre "Ou continuez ci-dessous" juste en dessous.
- Aucun impact visuel sur les listes `<ul>` / `<ol>` du reste du site (règle `.page-default .entry-content ul` inchangée).

**Sélecteurs cibles pour vérification visuelle :**
- `.wc-block-components-express-payment--cart` → panier
- `.wc-block-components-express-payment--checkout` → checkout
- `.wc-block-components-express-payment__event-buttons` → le `<ul>` qui contient les boutons
- `.wc-block-components-express-payment-continue-rule--checkout` → la barre "Ou continuez ci-dessous" juste en dessous du bloc checkout

---

## ✅ [TÂCHE] Popup cookies — tracking GA4 + accélérer l'animation écran 1 — TERMINÉE (23 avril 2026)
**Date :** 2026-04-21
**Priorité :** normale
**Branche :** master (Code Snippet uniquement — snippet modifié en local, non commité)
**Statut :** Snippet modifié dans [snippet-sapi-cookie-popup.php](../snippet-sapi-cookie-popup.php). Robin l'a collé dans Code Snippets WP et confirmé la mise en ligne sur **test + prod** le 23/04/2026.

**Résultat :**

### 1. Tracking GA4 — 4 events dataLayer
- Helper `sapiTrack(payload)` + garde-fou `window.dataLayer = window.dataLayer || [];` en tête de script.
- `cookie_consent` / `cookie_action: 'accept' | 'deny'` → pushé dans `handleCookieChoice(action)` **avant** `callComplianz()`. Couvre aussi le clic hors-popup (qui appelle `handleCookieChoice('deny')`).
- `popup_email` / `email_action: 'submit'` → pushé dans les branches `.then` **et** `.catch` du fetch Brevo (après réponse AJAX, avant `goToConfirm`).
- `popup_email` / `email_action: 'skip'` → pushé dans le handler du bouton "Non merci" avant `closePopup`.

### 2. Animation écran 1 accélérée
- JS (`state`) : `stagger` 15ms → **12ms**, `pauseBetweenPhrases` 0.3s → **0.25s**, `delay` initial inchangé (0.2s).
- CSS signature `animation-delay` 2.7s → **1.4s**
- CSS body (message + boutons) `animation-delay` 3.4s → **1.8s**
- Boutons visibles en ~2-2.2s (fade inclus) au lieu de ~4s. Effet lettre-par-lettre conservé.

**Note itération :** première passe avait aussi accéléré les paramètres de lettre-par-lettre à 8ms/0.15s — Robin a demandé de revenir à une accélération plus douce sur le lettre-par-lettre, en gardant les délais signature/body accélérés. Valeurs finales = celles ci-dessus.

---

## ✅ [TÂCHE] Popup écran 2 — refonte visuelle : mettre le -10% en évidence — TERMINÉE (21 avril 2026)
**Date :** 2026-04-21
**Priorité :** normale
**Branche :** master (Code Snippet uniquement — snippet modifié en local, non commité)
**Statut :** Snippet modifié dans [snippet-sapi-cookie-popup.php](../snippet-sapi-cookie-popup.php). **Pas de commit** (conformément à la consigne) — Robin colle le code complet dans le plugin Code Snippets WP lui-même.

**Résultat :**
- HTML écran 2 (`#sapi-screen-promo`) : remplacement de `#sapi-promo-title` + `#sapi-promo-text` par le discount badge (`.sapi-discount-badge` / `.sapi-discount-number` / `.sapi-discount-label`), subtitle "Ça vous dit ?" (`.sapi-promo-subtitle`) et body (`.sapi-promo-body`). Form, input, disclaimer RGPD et bouton skip intacts.
- Texte bouton submit : "Je veux mon code →" → "C'est parti →"
- CSS : ajout des 4 nouvelles classes dans la section ECRAN 2 : PROMO. Square Peg 4.5rem orange pour le `−10%`, label bois uppercase Montserrat 600, subtitle Montserrat 700 1.25rem, body gris 0.875rem. Ancien CSS `#sapi-promo-title` / `#sapi-promo-text` supprimé.
- Mobile (<600px) : `.sapi-discount-number` réduit à 3.5rem dans la media query existante.

**À faire côté Robin :**
- Coller le code complet du snippet dans Code Snippets WP (remplacer le snippet `sapi-cookie-popup` existant).
- Test bout en bout : vider cookies `sapi_promo_dismissed` + `cmplz_*` → ouvrir popup → accepter/refuser cookies → vérifier écran 2 (`-10%` en héros orange, subtitle "Ça vous dit ?", bouton "C'est parti →") → soumettre email → écran 3 + cookie `sapi_pending_coupon` posé.

---

## [TÂCHE — archive]
## [TÂCHE] Popup écran 2 — refonte visuelle : mettre le -10% en évidence
**Date :** 2026-04-21
**Priorité :** normale
**Branche :** master (Code Snippet uniquement, modification du snippet existant `sapi-cookie-popup` dans Code Snippets)

**Contexte :**
L'écran 2 du popup cookies (offre -10% en échange de l'email) n'est pas assez accrocheur. Le "10%" est noyé dans le texte. On veut que le pourcentage devienne l'élément visuel principal, visible au premier coup d'œil.

**Maquette validée par Robin :** `popup_screen2_proposal.html` dans le dossier Cowork (comparaison actuel vs proposition).

**À faire — modifier le HTML + CSS + wording de l'écran 2 (`sapi-screen-promo`) dans le snippet `sapi-cookie-popup` :**

1. **Remplacer le HTML de l'écran 2** par cette structure :
```html
<!-- Discount hero -->
<div class="sapi-discount-badge">
  <span class="sapi-discount-number">−10%</span>
  <div class="sapi-discount-label">sur votre première commande</div>
</div>
<p class="sapi-promo-subtitle">Ça vous dit ?</p>
<p class="sapi-promo-body">Laissez-moi votre email, la réduction s'appliquera automatiquement.</p>
```

Le formulaire email, le bouton, le disclaimer RGPD et le lien "Non merci" restent identiques (ne pas toucher au `<form>`, au `<input>`, au disclaimer ni au skip).

2. **Modifier le texte du bouton submit** : remplacer `JE VEUX MON CODE →` par `C'EST PARTI →`

3. **Ajouter le CSS** pour les nouveaux éléments :
```css
/* ── Écran 2 : discount badge ── */
.sapi-discount-badge {
  text-align: center;
  margin-bottom: 6px;
}
.sapi-discount-number {
  font-family: 'Square Peg', cursive;
  font-size: 4.5rem;       /* ~72px — le héros visuel */
  line-height: 1;
  color: var(--color-orange);
  display: block;
}
.sapi-discount-label {
  font-family: 'Montserrat', sans-serif;
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  color: var(--color-wood);
  margin-top: 2px;
}
.sapi-promo-subtitle {
  font-family: 'Montserrat', sans-serif;
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--color-wood-dark, #3a3a3a);
  margin: 12px 0 6px;
  text-align: center;
}
.sapi-promo-body {
  font-size: 0.875rem;
  color: #777;
  line-height: 1.5;
  text-align: center;
  margin-bottom: 20px;
}
```

4. **Supprimer le CSS des anciens éléments** `#sapi-promo-title` et `#sapi-promo-text` (remplacés par les nouveaux).

5. **Mobile (<600px)** : vérifier que le `−10%` ne déborde pas. Si besoin réduire `font-size` à `3.5rem` en media query.

**⚠️ Ne PAS toucher :**
- L'écran 1 (cookies animé)
- L'écran 3 (confirmation)
- Le JS de soumission email / AJAX Brevo
- Le cookie `sapi_pending_coupon` / `sapi_promo_dismissed`
- Le disclaimer RGPD

**Critères de succès :**
- Le `-10%` en gros (Square Peg orange) est la première chose visible à l'ouverture de l'écran 2
- "sur votre première commande" en petites capitales bois sous le chiffre
- "Ça vous dit ?" en titre Montserrat bold
- Texte body simplifié mentionnant l'application automatique
- Bouton "C'EST PARTI →"
- Desktop + mobile : pas de débordement, bien centré
- La soumission email fonctionne toujours (test bout en bout)
- Aucune erreur JS console

**Important :** Ne pas committer — Robin copiera le snippet modifié dans Code Snippets lui-même.

---

## ✅ [TÂCHE] Hook WC : ajouter le client à la liste Brevo #12 "Commande récente" à chaque commande — TERMINÉE (21 avril 2026)
**Date :** 2026-04-21
**Priorité :** normale
**Branche :** merge `test-theme-sapi-maison` → `master` fait (fast-forward `c57abcd..5ff4799`), master pushé. **En attente : Robin lance le workflow GitHub Actions pour déployer sur atelier-sapi.fr.**
**Statut :** Hook ajouté dans [functions.php](../functions.php) (après la fonction `sapi_brevo_newsletter_sync_optin`, lignes ~4312-4378).

**Résultat :**
- Nouvelle fonction `sapi_brevo_commande_recente_sync($order_id)` branchée sur `woocommerce_store_api_checkout_order_processed` (Blocks, prioritaire) et `woocommerce_checkout_order_processed` (fallback classique), priorité 20.
- POST `https://api.brevo.com/v3/contacts` avec `listIds: [12]`, `updateEnabled: true`, attributs `PRENOM` + `NOM` (si présents dans le billing).
- Clé via `BREVO_API_KEY` (wp-config.php). Timeout 10s. Erreurs loguées `[sapi-brevo-commande-recente]`, jamais bloquant pour la commande.
- **Aucun flag d'idempotence** côté meta : chaque commande (re)pousse vers #12, Brevo dédoublonne via `updateEnabled`. Le client ressortira de #12 après l'automation (email avis Google) → pourra ré-entrer à la commande suivante.
- Pas de condition opt-in : tous les clients passent par la file d'attente post-achat.

**À faire côté Robin :**
1. Tester sur test.atelier-sapi.fr : passer une commande test (virement) → vérifier dans Brevo que le contact apparaît dans la liste #12 avec `PRENOM` + `NOM`.
2. Désactiver la sync Brevo native vers liste #7 côté plugin (si pas déjà fait).
3. Configurer l'automation Brevo liste #12 : +14j → email avis Google → ajout #7 → retrait #12.
4. Une fois validé : "Go prod" → Claude merge test-theme-sapi-maison → master + push + Robin lance le workflow GitHub Actions.

---

## [TÂCHE — archive]
## [TÂCHE] Hook WC : ajouter le client à la liste Brevo #12 "Commande récente" à chaque commande
**Date :** 2026-04-21
**Priorité :** normale
**Branche :** master

**Contexte :**
On met en place un tunnel post-achat unifié pour tous les canaux (WC, Etsy, direct). À chaque commande WooCommerce, le client doit être ajouté à la liste Brevo **#12 "Commande récente"**. Une automation Brevo prendra le relais : attente 14 jours → email demande d'avis Google → ajout à liste #7 "Clients" → retrait de #12. Ce hook remplace la sync Brevo native vers #7 (qui va être désactivée côté plugin).

**Important :** la liste #12 sert de **file d'attente** — un client peut y être ajouté plusieurs fois (après chaque commande). L'automation Brevo le retire de #12 après envoi de l'email, ce qui permet la ré-entrée à la commande suivante.

**À faire :**

1. **Ajouter un hook dans `functions.php`** (ou en snippet Code Snippets, à voir avec Robin) qui fire à la **création de commande** :
   - Hook `woocommerce_store_api_checkout_order_processed` (WC Blocks — c'est le checkout du site, reçoit l'objet `$order`)
   - Hook `woocommerce_checkout_order_processed` en fallback (checkout classique, reçoit `$order_id`)
   - ⚠️ Ne PAS utiliser `woocommerce_order_status_completed` — ce statut n'est JAMAIS atteint sur ce site (cycle de vie custom).

2. **Appel API Brevo** `POST https://api.brevo.com/v3/contacts` avec :
   ```json
   {
     "email": "<billing email>",
     "listIds": [12],
     "updateEnabled": true,
     "attributes": {
       "PRENOM": "<billing first name>",
       "NOM": "<billing last name>"
     }
   }
   ```
   - Clé API via constante `BREVO_API_KEY` dans wp-config.php (même pattern que le popup cookies et le hook newsletter opt-in)
   - Erreurs loguées via `error_log` avec préfixe `[sapi-brevo-commande-recente]`, ne bloque jamais la commande

3. **Pattern à réutiliser** : le hook `sapi_brevo_newsletter_sync_optin()` dans `functions.php` (lignes ~4230+) fait exactement la même chose pour la liste #6. Reprendre le même pattern (guards, API call, error handling) en changeant `listIds: [12]` et en supprimant la condition sur `_sapi_newsletter_optin` — ici on ajoute TOUS les clients, pas seulement les opt-in.

4. **Pas de flag d'idempotence** : contrairement au hook newsletter (qui a `_sapi_newsletter_brevo_synced`), ici on veut que chaque commande pousse vers la liste #12 même si le client y est déjà. Brevo gère le dédoublonnage avec `updateEnabled: true`.

**Critères de succès :**
- Commande test sur le site → le client apparaît dans la liste #12 dans Brevo
- 2e commande avec le même email → toujours dans la liste #12 (pas d'erreur)
- Aucune erreur PHP dans les logs
- La commande n'est PAS bloquée si l'API Brevo échoue

**Test :**
Passer une commande test (virement) sur atelier-sapi.fr, vérifier dans Brevo que le contact apparaît dans la liste #12 avec PRENOM + NOM.

---

## ✅ [TÂCHE] Auto-application du coupon BIENVENUE10 au panier + refonte écran 3 popup — TERMINÉE & DÉPLOYÉE EN PROD (21 avril 2026)
**Date :** 2026-04-21
**Priorité :** normale
**Branches :** master (snippet `fb96b91` + merge thème `c57abcd`) — pushed master, workflow GitHub Actions lancé par Robin, validé en prod sur atelier-sapi.fr

**Contexte :**
Robin a activé une automation Brevo qui envoie un email de bienvenue avec le code BIENVENUE10. Pour réduire la friction (pas de copier-coller), on applique aussi le coupon automatiquement dans le panier via un cookie posé par le popup cookies. L'email garde son rôle de backup/fidélisation.

**Résultat :**
1. **Snippet popup cookies** — commit `fb96b91` sur master :
   - Écran 3 refondu : plus d'affichage du code, plus de bouton Copier. Nouveau wording "C'est noté ! Votre réduction de 10% s'appliquera automatiquement…"
   - JS pose cookie `sapi_pending_coupon=BIENVENUE10` (365j) à la soumission email
   - Code CSS/HTML/JS inutilisé supprimé (btnCopy, clipboard fallback, #sapi-promo-code, etc.)

2. **Thème** [functions.php](../functions.php) — commit `c4deaf3` sur test-theme-sapi-maison (pushed, déployé sur test.atelier-sapi.fr) :
   - Hook `sapi_auto_apply_welcome_coupon` sur `woocommerce_add_to_cart` + `woocommerce_cart_loaded_from_session`
   - Lit le cookie, applique `BIENVENUE10` (valeur forcée en dur côté serveur = sécurité contre cookie manipulé)
   - Supprime le cookie après tentative (succès ou refus → pas de spam de notices)
   - Filter `woocommerce_coupon_success_message` : message personnalisé "Bienvenue à l'Atelier Sâpi ! Votre réduction de 10% a été appliquée. 🌿"

**Configuration WC du coupon (confirmée par Robin) :**
- Remise 10%, publié, pas d'expiration
- Limite 1 utilisation par utilisateur (identifié par email)
- "Exclure articles en promo" : coché (pas de cumul avec promos produits)

**Déploiement (fait le 2026-04-21) :**
1. ✅ Test validé sur test.atelier-sapi.fr (snippet + hook)
2. ✅ Merge `test-theme-sapi-maison` → `master` (commit merge `c57abcd`), push master
3. ✅ Robin a lancé le workflow GitHub Actions pour déployer en prod
4. ✅ Test final en prod OK : coupon auto-appliqué, message de bienvenue affiché

---


## ✅ [TÂCHE] Ajouter l'attribut SOURCE = "popup" dans l'appel API Brevo du snippet popup cookies — TERMINÉE (21 avril 2026)
**Date :** 2026-04-21
**Priorité :** normale
**Branche :** master (Code Snippet uniquement)
**Statut :** Code modifié + commité sur master (commit `fbd5f31`). Déployé par Robin dans Code Snippets. **Testé et validé le 2026-04-21 : l'attribut `SOURCE = "popup"` est bien reçu par Brevo.** Faux positif initial : Robin ne voyait pas l'attribut car il n'est pas affiché par défaut dans la vue liste des contacts Brevo — il faut ouvrir la fiche détaillée ou ajouter la colonne SOURCE.

**Résultat :**
- Modif dans [snippet-sapi-cookie-popup.php:72-74](../snippet-sapi-cookie-popup.php#L72-L74) : ajout du bloc `attributes => [ 'SOURCE' => 'popup' ]` dans le body JSON `wp_json_encode()` envoyé à `https://api.brevo.com/v3/contacts`.
- Aucune autre modification (hook checkout `functions.php` intact, pas de création d'attribut côté Brevo).
- Note workflow : le fichier `snippet-sapi-cookie-popup.php` n'était jamais tracké en git avant — le commit crée le fichier (797 lignes, changement logique = 3 lignes). Robin publie lui-même le snippet via le plugin Code Snippets WP (les snippet-*.php à la racine sont des mirrors locaux, pas déployés via git).

**À faire côté Cowork / Robin :**
- Robin colle le code complet dans le plugin Code Snippets (déjà fait ou en cours selon notre échange).
- Robin crée l'attribut `SOURCE` dans Brevo (admin Contacts > Attributs) s'il ne l'a pas déjà fait.
- Robin ajoute la condition `SOURCE = "popup"` dans l'automation email de bienvenue liste #6.
- Test de bout en bout : vider cookies `sapi_promo_dismissed` + `cmplz_*`, soumettre email via popup, vérifier dans Brevo que le contact a `SOURCE = popup`.

---

## [TÂCHE ORIGINALE — archive]
## [TÂCHE] Ajouter l'attribut SOURCE = "popup" dans l'appel API Brevo du snippet popup cookies
**Date :** 2026-04-21
**Priorité :** normale
**Branche :** master (Code Snippet uniquement)

**Contexte :**
On a mis en place une automation Brevo qui envoie un email de bienvenue avec le code BIENVENUE10 quand un contact est ajouté à la liste #6. Problème : le déclencheur "Ajouté à une liste" fire pour tout ajout (popup, checkout, import manuel). On veut que l'email ne parte que pour les inscrits via le popup cookies. Pour ça, on ajoute un attribut Brevo `SOURCE` = `"popup"` dans l'appel API du snippet, ce qui permettra à Robin de mettre une condition dans l'automation Brevo.

**État actuel :**
Le snippet `sapi-cookie-popup` (Code Snippets, "Exécuter partout") fait un POST vers `https://api.brevo.com/v3/contacts` avec `email`, `listIds: [6]`, `updateEnabled: true`. Il n'envoie actuellement aucun attribut.

**À faire :**

1. **Dans le handler AJAX `sapi_brevo_subscribe`** du snippet popup cookies : ajouter `"attributes"` dans le body JSON envoyé à l'API Brevo :
   ```json
   {
     "email": "...",
     "listIds": [6],
     "updateEnabled": true,
     "attributes": {
       "SOURCE": "popup"
     }
   }
   ```
   C'est tout. Une seule ligne à ajouter dans le tableau PHP qui est ensuite `json_encode()`.

2. **Ne PAS toucher** au hook opt-in checkout dans `functions.php` — celui-ci ne doit pas envoyer `SOURCE` (ou alors `SOURCE: "checkout"` si on veut tracer, mais pas obligatoire pour l'instant).

3. **Ne PAS créer l'attribut SOURCE dans Brevo** — Robin le crée lui-même côté admin Brevo.

**Critères de succès :**
- Le snippet popup cookies envoie bien `attributes.SOURCE = "popup"` dans l'appel API Brevo
- Aucune régression : l'email est toujours capturé, le contact est toujours ajouté à la liste #6
- Pas d'erreur PHP dans les logs

**Test :**
- Vider les cookies `sapi_promo_dismissed` et `cmplz_*`
- Passer par le popup cookies, soumettre un email
- Vérifier dans Brevo que le contact a bien l'attribut SOURCE = "popup"

---

## ✅ Investigation 5 URLs malformées GSC 404 — TERMINÉE (20 avril 2026)
**Date :** 2026-04-20
**Priorité :** basse
**Statut :** Investigation terminée. **Verdict : Cas C — résidu historique, aucune action nécessaire.**
**Contexte :** Audit du rapport GSC "Pages non indexées" (tâche Cowork M26). Sur les 11 URLs en 404, 5 sont des URLs malformées / wildcards qu'on ne s'explique pas :

1. **2 URLs templates Complianz avec placeholders non remplacés** :
   - `https://atelier-sapi.fr/wp-content/uploads/complianz/css/banner-{banner_id}-{type}.css?v=85`
   - `https://atelier-sapi.fr/wp-content/uploads/complianz/css/banner-{banner_id}-{type}.css?v=37`

   ⚠️ **ATTENTION : Complianz est bien installé et actif sur le site** (il gère toute la logique RGPD en arrière-plan, seul son habillage visuel est remplacé par le Code Snippet custom — cf. `project_cookie_popup.md` côté Cowork). **Ne PAS désinstaller Complianz. Ne PAS ajouter un Disallow global sur `/wp-content/uploads/complianz/`** qui risquerait de bloquer les CSS légitimes des bannières.

   Le problème ici c'est que les `{banner_id}` et `{type}` apparaissent littéralement dans l'URL au lieu d'être remplacés par des vraies valeurs (ex. `banner-1-optin.css`). Google a donc récupéré une URL template quelque part (preload, link dans le `<head>`, commentaire HTML, CSP, ou sitemap du plugin).

2. **3 URLs wildcards reportées par GSC en 404** :
   - `https://atelier-sapi.fr/wp-content/themes/hello-theme-child-master/*`
   - `https://atelier-sapi.fr/wp-content/plugins/*`
   - `https://atelier-sapi.fr/wp-*.php`

   Ces URLs avec `*` littéral ne sont pas de vraies URLs. Probablement un ancien `robots.txt` mal formé (où des patterns de Disallow seraient mal écrits et interprétés comme des URLs), ou un sitemap historique.

**À faire :**

### 1. Récupérer le robots.txt actuel
- Consulter `https://atelier-sapi.fr/robots.txt` pour voir le contenu actuel
- Identifier s'il est servi par un fichier physique à la racine (FTP O2switch) ou par un plugin (Yoast/Rank Math/WP par défaut)
- Vérifier qu'aucune règle actuelle n'écrit les wildcards avec une syntaxe qui pourrait être mal comprise par GSC (ex. `Disallow: /wp-content/plugins/*` au lieu de `Disallow: /wp-content/plugins/`)
- Si règles douteuses trouvées : les normaliser

### 2. Tracer l'origine des URLs Complianz avec `{placeholders}`
- Ouvrir une page du site en front, View Source, chercher la string `{banner_id}` ou `{type}` dans le HTML
- Si trouvée : identifier dans quel contexte elle apparaît (`<link rel="preload">`, `<script>`, commentaire HTML, tag `<style>`, etc.)
- Regarder aussi le code du plugin Complianz côté serveur (`/wp-content/plugins/complianz-gdpr/...`) pour comprendre d'où sort ce template
- Vérifier s'il existe une mise à jour du plugin qui corrigerait ce bug (changelog officiel)
- Vérifier les réglages Complianz pour voir s'il y a une option qui force l'exposition du template

### 3. Auditer les sitemaps
- Lister les sitemaps exposés (`sitemap_index.xml`, `sitemap.xml`, `wp-sitemap.xml`, plus ceux générés par Yoast/Rank Math)
- Vérifier qu'aucune URL avec `{placeholder}` ou `*` ne traîne dedans

### 4. Conclusion possible (selon ce qui sera trouvé)
- Cas A : bug plugin Complianz identifié → rapport + lien vers ticket/PR upstream si dispo, attendre correctif
- Cas B : URLs présentes dans le source HTML → localiser le bout de template qui les génère, fixer
- Cas C : résidu historique Google sans source vivante → laisser Google les oublier naturellement, aucune action nécessaire

**Critères de succès :** un rapport court dans ce fichier indiquant :
- Contenu actuel du robots.txt
- Origine identifiée (ou non) des URLs `{banner_id}` et des wildcards
- Décision recommandée (fix à faire / laisser couler)

**Retour attendu :** rapport ajouté en fin de ce fichier. Pas d'action corrective lancée sans validation Robin au préalable.

### RAPPORT D'INVESTIGATION (20 avril 2026)

#### 1. Robots.txt actuel — PROPRE

```
User-agent: *
Disallow: /wp-content/uploads/wc-logs/
Disallow: /wp-content/uploads/woocommerce_transient_files/
Disallow: /wp-content/uploads/woocommerce_uploads/
Disallow: /*?add-to-cart=
Disallow: /*?*add-to-cart=
Disallow: /wp-admin/
Allow: /wp-admin/admin-ajax.php
Disallow: /*?wc-ajax=*
Disallow: /wp-json/complianz/
Disallow: /*?PageSpeed=*

User-agent: *
Disallow:
Sitemap: https://atelier-sapi.fr/sitemap_index.xml
```

**Analyse :** Aucune règle Disallow ne contient les patterns `hello-theme-child-master/*`, `wp-content/plugins/*` ou `wp-*.php`. Les `*` utilisés dans `/*?add-to-cart=` etc. sont des wildcards robots.txt valides (syntaxe Google), pas des URLs littérales. **Le robots.txt n'est pas la source des URLs 404 wildcards.**

Servi par Yoast SEO (bloc `START YOAST BLOCK`). Pas de fichier physique suspect.

#### 2. URLs Complianz `{banner_id}` — AUCUNE SOURCE ACTIVE

Pages testées en View Source :
- `atelier-sapi.fr/` (homepage)
- `atelier-sapi.fr/nos-creations/` (shop)
- `atelier-sapi.fr/mes-creations/gaston-le-chardon/` (fiche produit)

**Résultat : aucune occurrence de `{banner_id}`, `{type}`, `banner-{` ou `complianz/css/banner` dans le HTML source d'aucune de ces pages.**

**Explication probable :** Complianz génère dynamiquement ses fichiers CSS bannière (`banner-1-optin.css`, `banner-1-deny.css`, etc.) et les précharge via un tag `<link rel="preload">` ou `<link rel="stylesheet">` dans le `<head>`. **Avant l'activation du popup cookies custom** (15 avril 2026), Complianz rendait sa bannière native et injectait probablement un template non résolu (`banner-{banner_id}-{type}.css`) dans le HTML à un moment donné — soit un bug connu du plugin, soit une race condition où le CSS est préchargé avant que les variables soient résolues côté PHP.

Depuis que le snippet custom masque la bannière Complianz (`display: none !important`) et la remplace par le popup Sâpi, Complianz ne génère plus ces tags `<link>` dans le `<head>` (ou ils sont neutralisés). **La source n'existe plus dans le code actuel.** Google a gardé ces URLs dans son index suite à un crawl antérieur au 15 avril.

#### 3. URLs wildcards (`*`) — RÉSIDU HISTORIQUE

- **`/wp-content/themes/hello-theme-child-master/*`** : `hello-theme-child-master` est l'**ancien nom du thème** (avant la migration vers `theme-sapi-maison`). Ce thème n'existe plus sur le serveur. Google a crawlé cette URL à une époque où le thème existait (ou via un lien dans un ancien sitemap/robots.txt). Le `*` est probablement une notation interne GSC pour dire "plusieurs URLs sous ce chemin", pas une URL littérale avec un astérisque.

- **`/wp-content/plugins/*`** et **`/wp-*.php`** : même logique. Ce sont soit des notations de regroupement GSC, soit des résidus d'un crawl de bot qui testait des chemins courants WordPress. Aucune source vivante dans le robots.txt, les sitemaps ou le HTML.

#### 4. Sitemaps — TOUS PROPRES

5 sitemaps Yoast vérifiés, aucune URL malformée :
- `post-sitemap.xml` — 67 URLs ✅
- `page-sitemap.xml` — 15 URLs ✅
- `product-sitemap.xml` — 33 URLs ✅
- `category-sitemap.xml` — 2 URLs ✅
- `product_cat-sitemap.xml` — 6 URLs ✅

Pas de sitemap WordPress natif (`wp-sitemap.xml` redirige vers le même index Yoast). Aucune URL avec placeholder ou wildcard dans aucun sitemap.

#### 5. Décision recommandée

**Cas C : résidu historique Google sans source vivante → laisser Google les oublier naturellement.**

- Aucune action corrective nécessaire côté code ou serveur
- Le robots.txt est propre et bien formé
- Les sitemaps sont propres
- Les URLs Complianz `{banner_id}` ne sont plus générées (source éliminée par le popup custom)
- Les URLs wildcards sont des artefacts d'un ancien thème ou de crawlers
- Google les retirera de son rapport au fil de ses prochains crawls (quelques semaines à quelques mois)

**Aucune action à lancer. Surveillance passive — si ces URLs persistent dans GSC après 2-3 mois, on pourra demander une suppression manuelle via l'outil GSC "Suppressions".**

---

## 🔧 Bug GA4 `purchase` = 0 — INVESTIGATION TERMINÉE, SNIPPET PRÊT (17 avril 2026)
**Date :** 2026-04-17
**Priorité :** haute
**Statut :** Snippet créé dans `snippet-ga4-purchase-datalayer.php` (racine repo, non commité). Prêt à activer dans Code Snippets.

### Cause racine identifiée : Hypothèse A confirmée

**GTM4WP ne détecte pas la page order-received comme un endpoint WooCommerce.** Son module WC ne se charge pas du tout sur cette page.

**Preuves :**
- `gtm4wp.reading.articleLoaded` fire sur la page → GTM4WP charge son module "article/blog" au lieu du module WooCommerce
- `post_type: "page"` dans le dataLayer (via Site Kit) → la page est vue comme une page standard
- Aucun `purchase` ni `gtm4wp.orderCompleted` → le module WC de GTM4WP ne s'exécute pas

**Cause technique :** GTM4WP vérifie le type de page lors de `wp_head` via `is_order_received_page()`. Avec le checkout WC Blocks (le site utilise le checkout Blocks, confirmé lors de la tâche newsletter), le contexte WC peut ne pas être initialisé au moment où GTM4WP fait sa vérification. Google Site Kit injecte aussi ses propres meta (`googlesitekit_post_type`) ce qui peut interférer avec la détection.

**L'hypothèse C (meta `_gtm4wp_tracked`)** est un facteur secondaire — même si la détection marchait, les commandes pré-28/03 auraient pu être marquées. Mais c'est secondaire car la détection de page est cassée de toute façon.

### Solution : snippet custom `snippet-ga4-purchase-datalayer.php`

**Approche :** bypasse complètement GTM4WP pour le `purchase` event. Hooks directement sur `woocommerce_thankyou` (qui fire bien — le template custom `thankyou.php` l'appelle ligne 142) et pousse l'event GA4 `purchase` avec les données ecommerce directement dans `window.dataLayer`.

**Ce que fait le snippet :**
1. Hook `woocommerce_thankyou` (priorité 5, avant GTM4WP)
2. Récupère les données commande (transaction_id, value, tax, shipping, currency, items, coupon)
3. Pour les variations : remonte au produit parent pour le `item_name`, met la variation dans `item_variant`
4. Push `{ ecommerce: null }` (clear) puis `{ event: 'purchase', ecommerce: {...} }` dans `window.dataLayer`
5. Idempotence : meta `_sapi_ga4_purchase_tracked` sur la commande (ne re-pousse pas au rechargement)
6. Ne tracke pas les commandes échouées (`has_status('failed')`)

**Format du push :** compatible avec la balise GTM existante `GA4 - purchase` (déclencheur custom `purchase`) :
```js
{ event: 'purchase', ecommerce: { transaction_id, value, tax, shipping, currency, items: [{ item_id, item_name, price, quantity, item_category, item_variant }] } }
```

### Activation par Robin
1. Ouvrir Code Snippets dans l'admin WordPress
2. Créer un nouveau snippet, coller le contenu de `snippet-ga4-purchase-datalayer.php`
3. Emplacement : **"Exécuter partout"** (pas "Frontend only" — même piège que le snippet cookies)
4. Activer sur **test.atelier-sapi.fr** d'abord
5. Passer une commande test → vérifier dans la console navigateur que `window.dataLayer` contient un event `purchase` avec les bonnes données
6. Vérifier dans GA4 DebugView (ou Realtime) que l'event `purchase` arrive
7. Si OK → activer sur prod

### Interaction avec GTM4WP
- Le snippet n'entre PAS en conflit avec GTM4WP : GTM4WP ne pousse rien (son module WC ne se charge pas), donc pas de doublon
- Si un jour GTM4WP corrige son bug de détection, il y aurait un doublon `purchase` → à ce moment-là, désactiver ce snippet
- Le meta `_sapi_ga4_purchase_tracked` est indépendant du meta GTM4WP (`_gtm4wp_order_completed` ou similaire) — pas d'interférence

### Investigation optionnelle (pas bloquant)
Pour confirmer définitivement la cause et éventuellement la corriger dans GTM4WP :
- Lire le code de GTM4WP sur le serveur (`/wp-content/plugins/duracelltomi-google-tag-manager/integration/woocommerce.php`) pour voir exactement quel check de page il fait
- Tester en désactivant temporairement Google Site Kit → le purchase push GTM4WP se déclenche-t-il alors ?
- Vérifier si une mise à jour de GTM4WP existe (le bug WC Blocks est connu dans la communauté)

---

## [TÂCHE] Investiguer bug GA4 `purchase` = 0 (GTM4WP + WooCommerce)

### Contexte
Sur GA4 (propriété Atelier Sâpi), l'événement `purchase` remonte **0 events** sur la période 18 mars → 16 avril 2026, alors que WooCommerce a enregistré **6 commandes réelles** sur la période (total ~1 177 €). Le tracking e-commerce a été ajouté dans GTM le 28/03/2026 (version 17 du container "Ajout tracking e-commerce GA4").

**Ce qui marche :**
- `view_item` : 2430 events
- `add_to_cart` : 4 events
- `begin_checkout` : 2 events

**Ce qui ne marche pas :**
- `purchase` : 0 events

### Stack technique concerné
- **GTM Container** : GTM-WZVZ8DFX (13 balises, 14 déclencheurs)
- **Plugin WordPress** : GTM4WP (Google Tag Manager for WordPress par Thomas Geiger)
- **Balise GTM** : `GA4 - purchase` (type Événement GA4, déclencheur custom `purchase`) — créée le 28/03
- **URL de confirmation** : `/validation-de-la-commande/order-received/{ID}/` (slug français custom, pas `/checkout/order-received/`)
- **Cycle de vie commande** : statuts custom type "Colissimo livré" — le statut `wc-completed` n'est **jamais atteint** (cf. tâche Brevo)

### Diagnostic déjà effectué (par Cowork, 17/04/2026)

**1. Réglages GTM4WP → Intégration → WooCommerce — tous OK :**
- ✅ Track e-commerce : coché
- ✅ Données de la commande dans la couche de données : coché
- ✅ Contenu du panier dans la couche de données : coché
- ✅ Clear ecommerce object before new event : coché
- ✅ Fire view_item on parent product : coché
- "Ne pas marquer les commandes comme suivies" : décoché (= GTM4WP marque bien les commandes après tracking)
- "Ne suivez que les commandes plus jeunes que (experimental)" : 30

**2. Réglages GTM4WP → Intégration → Google Consent Mode :** **désactivé**. Donc pas de blocage par consent mode.

**3. Inspection dataLayer sur `/validation-de-la-commande/order-received/9096/` (en navigateur admin connecté) :**
Le dataLayer contient seulement :
- `gtm.start`, `event: "gtm.js"`
- `event: "gtm4wp.reading.articleLoaded"` ← **suspect : cet event est pour les articles de blog, pas les pages WC**
- `["set", "developer_id.dZTNiMT", true]`
- `["config", "GT-PLVQDZCF", {googlesitekit_post_type: "page"}]` ← **suspect aussi : `post_type: "page"` alors que c'est une page WC endpoint**
- `event: "gtm.dom"`, `event: "gtm.load"`

**Aucun `event: "purchase"` ni `gtm4wp.orderCompleted` n'est poussé sur cette page.**

Note : la commande 9096 a peut-être déjà été "marquée comme suivie" par GTM4WP lors d'une précédente visite, ce qui pourrait expliquer le non-re-push. **Mais ça n'explique pas le 0 event total sur GA4** (6 commandes, aucune n'a tracké).

### Hypothèses à investiguer

**A. GTM4WP ne détecte pas la page comme une order-received WC**
- Le site utilise un slug français `/validation-de-la-commande/` au lieu de `/checkout/`
- GTM4WP devrait utiliser `is_wc_endpoint_url('order-received')` qui est slug-agnostic, mais un conflit ou filter peut casser cette détection
- La présence de `gtm4wp.reading.articleLoaded` et `post_type: "page"` suggère que GTM4WP voit cette page comme une page standard, pas un endpoint WC

**B. Conflit avec un autre plugin ou theme**
- Google Site Kit est aussi installé (`googlesitekit_post_type` dans le dataLayer) — pourrait interférer
- Le theme peut avoir des templates override ou des filtres qui masquent le endpoint
- Possibilité de conflit avec les hooks Brevo Newsletter récents (voir tâche précédente)

**C. Le `_gtm4wp_tracked` meta existe déjà sur les commandes orphelines**
- Si GTM4WP a tenté de tracker les commandes pré-28/03 (avant que les balises GTM n'existent), il les a marquées comme suivies dans les meta → ne re-tirera plus
- Vérifier le meta `_gtm4wp_tracked` (ou similaire) sur les commandes 8853, 8866, 8899, 9096, 9151

### À faire

**1. Identifier le hook GTM4WP qui pousse le `purchase` event**
Lire `/wp-content/plugins/duracelltomi-google-tag-manager/` (nom complet du plugin GTM4WP) et trouver :
- Le fichier qui gère le tracking WooCommerce (probablement `/integration/woocommerce.php` ou similaire)
- La fonction qui pousse `purchase` dans le dataLayer
- Les conditions de déclenchement (hook WC utilisé, check de page)

**2. Vérifier pourquoi le push ne se fait pas**
Trois tests possibles :
- Grep sur `is_wc_endpoint_url` dans le plugin GTM4WP : est-ce que la détection est bonne ?
- Grep sur `_gtm4wp_` dans la base de données (ou dans un export SQL) pour voir si les commandes ont des meta liées
- Tester en désactivant temporairement Google Site Kit : le purchase push se déclenche-t-il alors ?

**3. Proposer une solution** (selon la cause identifiée) :
- Si c'est une meta `_gtm4wp_tracked` pré-existante : purger cette meta sur les commandes récentes pour retester
- Si c'est un conflit de détection de page : proposer un snippet Code Snippets qui force le push `purchase` via hook `woocommerce_thankyou` (compatible avec les slugs français)
- Si c'est un bug du plugin : documenter + snippet de workaround

**4. Ne PAS modifier le code du plugin GTM4WP directement** — toute correction doit passer par :
- Un snippet Code Snippets custom (préférence Robin : `feedback_snippets.md` en mémoire Cowork)
- Ou une configuration plus fine dans les réglages du plugin

### Contexte dataLayer attendu pour un push `purchase` correct
Format GA4 cible (pour référence, ce que devrait contenir le dataLayer sur order-received) :
```js
{
  event: 'purchase',
  ecommerce: {
    transaction_id: '9151',
    value: 145.42,
    tax: 0,
    shipping: 0,
    currency: 'EUR',
    items: [ { item_id, item_name, price, quantity } ]
  }
}
```

### Critères de succès
- Identifier la cause racine du bug (hypothèse A, B, C, ou autre)
- Proposer un correctif (snippet ou config) **sans committer** sans l'accord de Robin
- Documenter ici le résultat de l'investigation pour Cowork

### Important
- Ne PAS committer sans accord explicite de Robin
- Ne PAS modifier le plugin GTM4WP directement
- Si snippet custom nécessaire → le créer en fichier de travail à la racine du repo (non commité), Robin l'activera manuellement dans Code Snippets

---

---

## ✅ Articles blog — Images 2:3 + blockquote card + FAQ accordion — À TESTER SUR TEST.ATELIER-SAPI.FR (16 avril 2026)

Commit `106bf0b` sur `test-theme-sapi-maison` (pushé, auto-deploy en cours sur test.atelier-sapi.fr). **Non mergé dans master** — en attente validation Robin.

### Ce qui a été fait

**1. Images blog — recadrage 2:3 portrait (`style.css` lignes ~10815-10833)**
- Desktop : `width: 100%` + `max-width: 100%` + `aspect-ratio: 2/3` + `object-fit: cover` → prend toute la largeur du conteneur (800px)
- Mobile (< 600px) : `width: calc(100% + 40px)` + `margin: -20px` horizontal + `border-radius: 0` → déborde du padding, pleine largeur écran
- `border-radius: 12px` conservé desktop
- Compatible Media Focus Point (`object-position` inline sur les `<img>` interagit avec `object-fit: cover`)

**2. Blockquote en card (`style.css` lignes ~10835-10862)**
- Fond `--color-warm`, bordure gauche 3px orange (`--color-orange`), border-radius 12px, ombre légère
- `p` en italique, `cite` / `footer` en bold couleur bois
- Scope : `.single-post-content blockquote` uniquement — pas d'impact sur `.artisan-quote-cinetique` ou `.presse-quote`

**3. FAQ Yoast accordion (CSS + JS intégrés au thème)**
- CSS (lignes ~10900-10950) : question en `flex` avec chevron `::after` qui pivote à l'ouverture, réponse masquée par défaut (`max-height: 0`), transition 0.35s sur `max-height` + `padding-top`, état `.faq-open` déclenche `max-height: 1000px`
- JS : `assets/faq-accordion.js` (commit `5227651`), enqueue conditionnel dans `functions.php` sur `is_single()` + `get_post_type() === 'post'`. Un seul item ouvert à la fois, accessibilité clavier (`Enter` / `Space`), `role="button"` + `aria-expanded` ajoutés dynamiquement. Pas de jQuery.

**Note :** initialement mis dans un snippet Code Snippets, migré dans le thème à la demande de Robin (feature permanente du rendu éditorial = mieux dans le thème, versionné git, pas de dépendance plugin).

### Critères à valider par Robin sur test.atelier-sapi.fr

- [ ] Un article de blog → les images sont en portrait 2:3, pleine largeur 800px desktop
- [ ] Le focal point s'applique bien (les images sont recadrées sur le point défini)
- [ ] Mobile : les images débordent en pleine largeur écran, pas de border-radius
- [ ] Blockquote (s'il y en a dans un article) → fond warm, bordure orange à gauche
- [ ] FAQ : questions repliées par défaut, clic déplie avec chevron qui tourne, un seul item ouvert à la fois
- [ ] Aucun impact sur les autres pages (fiche produit, catégories, etc.)

### Prochaine étape
Une fois validé par Robin → merge `test-theme-sapi-maison` → `master` + Robin lance le workflow GitHub Actions.

---

## 🚀 Newsletter checkout : bascule opt-out → opt-in + sync Brevo liste #6 — EN PRODUCTION (16 avril 2026)

Validé en conditions réelles : commande test sur atelier-sapi.fr (Robin Garnier) → contact ajouté à la liste **#6 "Les nouveautés Sâpi"** dans la minute, avec `PRENOM` + `NOM` remplis. Historique Brevo trace l'event "Contact ajouté à la liste (#6)" avec timestamp — suffisant comme preuve de consentement RGPD.

**Commits sur `master`** (déployés) : `17e87ac` (bascule + hook), `e01b11b` (MAJ queue), `2ec9783` (change trigger de `order_status_completed` → `checkout_order_processed`), **`36b455d` (fix final : ajout hook `woocommerce_store_api_checkout_order_processed` pour WC Blocks, le checkout du site)**.

### Changements

**`functions.php` (lignes ~4181-4290)**
- Champ WC `sapi-maison/newsletter-optin` (ex-`optout`), label *"Je souhaite recevoir des nouvelles de l'atelier et de jolies idées pour m'inspirer"*, `default: false`, `type: checkbox`, `location: order`.
- Hook `woocommerce_set_additional_field_value` → sauvegarde dans meta `_sapi_newsletter_optin`.
- Hook `woocommerce_before_pay_action` → sauvegarde `_sapi_newsletter_optin = 'yes'` si `$_POST['sapi_newsletter_optin']` coché.
- **Hook `woocommerce_checkout_order_processed`** (priorité 20) → fonction `sapi_brevo_newsletter_sync_optin($order_id)`. Choix du trigger "commande créée" (et pas "Terminée") car Robin n'utilise jamais le statut `wc-completed` dans son cycle de vie commande (statuts finaux custom type "Colissimo livré"). Le consentement étant donné au submit, on pousse vers Brevo dès la création.
  - Également rappelé depuis `woocommerce_before_pay_action` pour couvrir le cas où la case est cochée seulement au retry paiement.
  - Si meta `_sapi_newsletter_optin !== 'yes'` → return (pas de push).
  - Idempotence : flag `_sapi_newsletter_brevo_synced = 'yes'` après succès, double-appel ignoré.
  - POST `https://api.brevo.com/v3/contacts` avec `email`, `listIds: [6]`, `updateEnabled: true`, `attributes.PRENOM / .NOM` si dispos côté billing.
  - Clé lue via `defined('BREVO_API_KEY')` (même constante wp-config que le snippet popup cookies).
  - Erreurs loguées dans `error_log` avec préfixe `[sapi-brevo-newsletter]`, ne bloque jamais la commande.

**`woocommerce/checkout/form-pay.php` (ligne 99-102)**
- `name="sapi_newsletter_optin"` + libellé positif *"Je souhaite recevoir des nouvelles de l'atelier et de jolies idées pour m'inspirer (facultatif)"*.

**Grep final** : plus aucune occurrence de `newsletter_optout` / `newsletter-optout` dans le code (seulement dans ce fichier de doc).

### Pièges rencontrés pendant la mise en prod (pour mémoire)

1. **Statut "Terminée" jamais atteint** : Robin utilise un cycle de vie commande custom (statuts type "Colissimo livré") et ne met jamais les commandes en `wc-completed`. → Abandon du hook `woocommerce_order_status_completed`, bascule sur la création de commande.

2. **`woocommerce_checkout_order_processed` ne fire PAS pour WC Blocks** : le checkout du site utilise WC Blocks (Store API), qui a son propre hook `woocommerce_store_api_checkout_order_processed` (reçoit l'objet order, pas l'ID). Le hook classique est resté en fallback compat, mais c'est le hook Blocks qui fait le vrai travail.

3. **Diagnostic rapide quand ça ne pousse pas** : ouvrir la commande en admin WC → voir si le champ "Je souhaite recevoir des nouvelles…" affiche "Oui". Si oui, la meta est bien posée, c'est un problème de hook. Si vide, problème de sauvegarde (WC additional field config).

### Points à surveiller pour évolutions futures

- Attributs Brevo `PRENOM` / `NOM` — confirmés corrects sur le compte Brevo d'Atelier Sâpi (pas `FIRSTNAME` / `LASTNAME`).
- Flag `_sapi_newsletter_brevo_synced` sur la commande = preuve que l'API Brevo a répondu en succès (visible en admin WC dans les meta).

### Ce qui n'a PAS été fait (volontairement)
- Aucune migration des anciennes meta `_sapi_newsletter_optout`. Ces données d'opt-out historiques dorment (décision Cowork).
- Pas de rattrapage des commandes existantes : le hook ne fire que sur les nouvelles commandes à partir du déploiement.

---

## 📊 Config Brevo + état RGPD — état des lieux 16 avril 2026 (pour Cowork)

Audit réalisé pendant la mise en prod du champ opt-in newsletter. **À lire par Cowork** pour comprendre la situation Brevo/RGPD actuelle et prioriser les actions marketing.

### Architecture actuelle des listes Brevo

| # | Nom | Contacts | Rôle | À utiliser ? |
|---|-----|----------|------|--------------|
| **#6** | Les nouveautés Sâpi | 315 | Newsletter — opt-in explicite (popup cookies + checkbox checkout) | ✅ **Pour campagnes marketing** |
| **#7** | Clients | 111 | Tous les clients ayant commandé (opt-in ou pas) | ⚠️ **Transactionnel uniquement** (confirmations, factures, livraisons) |
| #5 | Atelier Sâpi_NonSubscribers_(Do_Not_Delete) | 47 | Liste technique — contacts non opt-in d'une ancienne intégration (probablement plugin Sendinblue historique) | ❌ Ne pas toucher |
| #10 | WooCommerce | 0 | Liste parent auto-créée par le plugin Brevo WooCommerce, jamais utilisée | ❌ Ne pas toucher |
| #11 | WooCommerce_NonSubscribers_(Do_Not_Delete) | 67 | Liste technique miroir de #10 | ❌ Ne pas toucher |

Les listes en "Do_Not_Delete" sont des artefacts techniques des intégrations Brevo — les supprimer casse la sync. Recommandation Robin : déplacer #5, #10, #11 dans un dossier "Archives" pour les sortir de la vue principale sans les supprimer.

### Config de la sync Brevo ↔ WooCommerce (corrigée aujourd'hui)

- **Avant** : trigger sur "Commande terminée" (`wc-completed`) → ne firait **jamais** car Robin n'utilise pas ce statut (cycle de vie custom : En attente → En cours → Colissimo livré, sans passer par Terminée).
- **Après** : trigger sur "Ordre créé" → chaque nouvelle commande pousse le client dans la liste #7.
- Toggle *"Importez les contacts en tant qu'inscrits"* : **OFF** (bien). Les clients arrivent en #7 mais sont automatiquement blocklistés côté marketing → conforme RGPD.
- Toggle *"Afficher un champ opt-in au moment du paiement"* : **OFF** (bien). On a notre propre champ custom via le thème, pas besoin de double.

### Découverte importante pour Cowork : consentement historique = zone grise RGPD

Jusqu'au matin du 16 avril 2026, le champ newsletter du checkout était un **opt-OUT pré-coché** (*"Je ne souhaite PAS recevoir…"*). Conséquences :
- **Clients qui n'ont rien coché** = pas de consentement explicite au sens RGPD (qui exige un acte positif). **Zone grise, ne PAS les considérer comme opt-in** pour des envois marketing.
- **Clients qui ont coché la case opt-OUT** = refus explicite → blocklisté.
- **Clients post-16 avril qui cochent le nouvel opt-IN** = consentement valide → liste #6.

**Décision Robin (16 avril 2026)** : ne pas débloquer en masse les anciens clients blocklistés par Brevo lors de l'import automatique du plugin. Seulement débloquer au cas par cas ceux dont Robin a un consentement explicite (ex. ami, client qui a dit "oui envoie-moi tes trucs" par email).

### Recommandation stratégique pour Cowork : campagne de ré-engagement

Pour récupérer du consentement sur la base historique (les 111 clients en liste #7 + d'autres contacts), Robin peut lancer une **campagne single-shot de ré-engagement / double opt-in** via Brevo :
- Email unique *"Voulez-vous recevoir nos nouveautés ?"* — sans contenu commercial, juste la demande
- Bouton CTA *"Oui, je m'abonne"* → Brevo ajoute automatiquement à la liste #6
- Les non-cliqueurs sont laissés tranquilles

C'est le seul moyen propre de constituer une base newsletter opt-in rétroactivement sans risque CNIL. Brevo a des templates tout faits pour ça.

### Pièges techniques pour futures tâches code (pour Claude Code)

- **WC Blocks ≠ checkout classique** pour les hooks : utiliser `woocommerce_store_api_checkout_order_processed` (objet `$order`) et pas `woocommerce_checkout_order_processed` (classique).
- Le statut `wc-completed` n'est **jamais atteint** sur ce site. Ne jamais hook là-dessus. Préférer des triggers basés sur la création de commande ou des événements de paiement.
- La constante `BREVO_API_KEY` est définie dans `wp-config.php` de prod (et de test normalement aussi). Pattern standard pour tous les appels Brevo côté theme.

---

## 🚀 Popup cookies custom — EN PRODUCTION (16 avril 2026)
Snippet `sapi-cookie-popup` activé sur **atelier-sapi.fr** (Code Snippets, emplacement "Exécuter partout"). Flux complet testé et validé par Robin : popup cookies → promo email → code `BIENVENUE10` → confirmation Brevo liste #6.

**Fichier de référence dans le repo :** `snippet-sapi-cookie-popup.php` (racine, non commité — fichier de travail uniquement).

**Fonctionnalités finales :**
- Écran 1 cookies : animation lettre par lettre (Square Peg), 3 phrases avec pauses, signature "Robin" orange
- Clic extérieur = Refuser (écran 1 uniquement)
- Écran 2 promo : titre "Puisque vous êtes là…", 10% en gras, form email, disclaimer, skip "Non merci"
- Écran 3 confirmation : code `BIENVENUE10`, bouton Copier, **message orange "Notez-le bien !"**, bouton "J'ai noté mon code" (fermeture explicite, pas d'auto-close)
- Cookie `sapi_promo_dismissed=1` (1 an) posé dans les deux cas (skip ou email soumis)
- Brevo dédoublonne via `updateEnabled: true`

**Piège découvert :** Code Snippets "Frontend only" bloque les handlers AJAX (admin-ajax = contexte admin). Mettre sur **"Exécuter partout"** — le `if (is_admin()) return;` du snippet protège l'injection HTML.

**Clé API Brevo :** constante `BREVO_API_KEY` déjà définie dans `wp-config.php` de prod (et de test). Resolver gère aussi `SAPI_BREVO_API_KEY`, `SIB_API_KEY`, `SENDINBLUE_API_KEY`, `sib_api_key_v3`, `mailin_options`.

**Rollback :** Code Snippets → désactiver le snippet (toggle). Effet immédiat.

**Découverte pendant le test :**
- Code Snippets "Frontend only" **empêche le handler AJAX de se charger** (admin-ajax.php est un contexte admin). Il faut mettre le snippet en **"Exécuter partout"** — le `if (is_admin()) return;` du snippet protège l'injection HTML.
- Clé API Brevo résolue via constante `BREVO_API_KEY` définie dans `wp-config.php` (déjà présente en prod, à vérifier si ajout nécessaire en test).

**Resolver de clé API (ordre) :**
1. Constantes wp-config : `BREVO_API_KEY`, `SAPI_BREVO_API_KEY`, `SIB_API_KEY`, `SENDINBLUE_API_KEY`
2. Option `sib_api_key_v3`
3. Option sérialisée `mailin_options[api_key_v3|api_key|access_key|apikey]`

---

## [ARCHIVE Étape 2] Snippet 3 écrans

### Contenu ajouté

**PHP (nouveau)**
- Handler AJAX `sapi_brevo_subscribe` (nopriv + priv) : nonce, sanitize email, POST vers `api.brevo.com/v3/contacts` avec `listIds: [6]` et `updateEnabled: true`
- Resolver `sapi_get_brevo_api_key()` avec 3 sources : constante `SAPI_BREVO_API_KEY` (wp-config) > option `sib_api_key_v3` > option sérialisée `mailin_options`
- Nonce `sapi_brevo_nonce` + `admin-ajax.php` injectés dans le JS

**HTML (3 écrans)**
- Écran 1 (`sapi-screen-cookie`) : contenu existant cookies
- Écran 2 (`sapi-screen-promo`) : titre "Puisque vous êtes là…", form email, disclaimer, bouton "Non merci"
- Écran 3 (`sapi-screen-confirm`) : code `BIENVENUE10` + bouton copier + message
- Gestion visibilité via `data-screen` sur l'overlay ("cookie" | "promo" | "confirm")

**CSS**
- Transition 0.35s opacity entre écrans (classe `.sapi-screen--fading`)
- Styles promo form (input pill, bouton pleine largeur)
- État erreur sur input (bordure orange)
- Bloc code promo (fond `rgba(147,125,104,0.12)`, border-radius 8px)
- Bouton "Copier" outline bois → plein bois quand copié
- Mobile : code 1.3rem, reste inchangé

**JS**
- `switchScreen(to)` : fade-out courant, swap attribut, fade-in cible
- `handleCookieChoice(action)` : après choix cookies → promo (ou close si promo déjà dismissed)
- Overlay click = Refuser **uniquement si écran 1** (désactivé sur promo/confirm)
- Form submit : validation email locale → fetch AJAX → succès ou erreur → toujours écran confirm (ne pas bloquer l'utilisateur)
- Skip / email soumis → cookie `sapi_promo_dismissed=1` (1 an)
- Écran confirm : auto-close après 5s + bouton copier avec fallback `execCommand`

### Flux utilisateur
1. Visiteur sans consentement cookies → écran 1 → clic Accepter/Refuser → **écran 2 promo**
2. Visiteur avec consentement mais sans promo dismissed → **écran 2 directement**
3. Visiteur avec consentement + promo dismissed → rien (popup retiré)
4. Sur écran 2 : submit email → AJAX Brevo → écran 3 code → auto-close 5s
5. Sur écran 2 : "Non merci" → close direct + cookie posé

### ⚠️ Vérifications à faire par Robin
1. **Clé API Brevo** : vérifier qu'elle est bien stockée dans l'option `sib_api_key_v3`. Sinon, définir `define('SAPI_BREVO_API_KEY', 'xxx');` dans wp-config.php
2. **Liste Brevo #6** : vérifier que c'est bien la bonne liste (sinon changer le `[6]` dans le handler PHP)
3. **Tester le flux** :
   - Vider cookies `cmplz_*` et `sapi_promo_*`
   - Clic Accepter → écran promo apparaît
   - Email bidon → "Email invalide" inline (pas d'alert)
   - Email valide → écran code, vérifier dans Brevo dashboard que le contact est ajouté à la liste 6
   - Bouton "Copier" → feedback "Copié ✓", tester le collage
   - Auto-close 5s OK
   - Rechargement → ni popup cookies ni popup promo ne réapparaissent

### Notes
- L'automation email Brevo côté Robin sera configurée plus tard (pour l'instant le code est affiché directement)
- Si l'API Brevo échoue : le code s'affiche quand même (non-bloquant), warning console uniquement
- Si la clé API n'est pas trouvée côté serveur : retour 500 mais le code s'affiche toujours côté user

---

## [ARCHIVE] [TÂCHE] Popup cookies custom — Étape 2 : capture email + code promo
**Date :** 2026-04-15
**Priorité :** normale
**Branche :** master (Code Snippet uniquement, modification du snippet existant `sapi-cookie-popup`)

### Contexte
L'étape 1 (popup cookies custom animé) est terminée et validée. On ajoute maintenant un second écran dans le même popup : après le choix cookies (accepter ou refuser), le popup se transforme en offre promo −10% en échange d'un email. Le contact est ajouté à la liste Brevo #6. Le code promo `BIENVENUE10` s'affiche directement dans le popup (l'automation email Brevo sera configurée plus tard — pour l'instant, affichage direct du code).

### À faire

**1. Modifier le snippet `sapi-cookie-popup` existant**

Après le clic sur Accepter ou Refuser (écran 1), ne pas fermer le popup immédiatement. À la place, transition vers l'écran 2.

**2. Comportement clic extérieur**
- Écran 1 : clic extérieur = Refuser (comportement actuel, à conserver)
- Écran 2 : **désactiver le clic extérieur**. L'utilisateur doit cliquer explicitement "Je veux mon code" ou "Non merci" pour fermer.

**3. HTML écran 2**
```html
<div id="sapi-promo-screen">
  <p id="sapi-promo-title">Puisque vous êtes là…</p>
  <p id="sapi-promo-text">
    Pour votre première commande, je vous offre 10%.<br>
    Laissez votre email, je vous envoie le code.
  </p>
  <form id="sapi-promo-form">
    <input type="email" id="sapi-promo-email" placeholder="votre@email.fr" required>
    <button type="submit" id="sapi-promo-submit">Je veux mon code →</button>
  </form>
  <p id="sapi-promo-disclaimer">
    En cliquant, vous acceptez de recevoir les actualités de l'Atelier Sâpi. Désinscription à tout moment.
  </p>
  <button id="sapi-promo-skip">Non merci</button>
</div>
```

**4. Écran de confirmation (après soumission réussie)**
```html
<div id="sapi-promo-confirm">
  <p>Votre code :</p>
  <p id="sapi-promo-code">BIENVENUE10</p>
  <button id="sapi-promo-copy">Copier le code</button>
  <p id="sapi-promo-confirm-text">Valable sur votre première commande 🎁</p>
</div>
```
Fermeture automatique après 5 secondes.

**5. CSS — Charte Atelier Sâpi**
- Transition entre écran 1 et écran 2 : fade-out écran 1 puis fade-in écran 2 (0.4s)
- `#sapi-promo-title` : Montserrat, `font-size: 1.3rem`, `font-weight: 700`, `color: var(--color-wood-dark)`, `margin-bottom: 1rem`
- `#sapi-promo-text` : Montserrat, `font-size: 0.95rem`, `color: var(--color-wood-dark)`, `opacity: 0.85`, `line-height: 1.6`
- `#sapi-promo-email` : pleine largeur, `border: 1.5px solid var(--color-wood)`, `border-radius: 50px`, `padding: 0.65rem 1.25rem`, Montserrat, fond blanc
- `#sapi-promo-submit` : fond `var(--color-wood)`, texte blanc, `border-radius: 50px`, `padding: 0.65rem 1.5rem`, pleine largeur, `margin-top: 0.75rem`
- `#sapi-promo-disclaimer` : `font-size: 0.72rem`, `color: var(--color-wood)`, `opacity: 0.65`, `text-align: center`, `margin-top: 0.5rem`
- `#sapi-promo-skip` : texte `var(--color-wood)`, `opacity: 0.6`, pas de fond ni bordure, `font-size: 0.85rem`, `display: block`, `margin: 1rem auto 0`, `text-decoration: underline`
- `#sapi-promo-code` : `font-size: 1.6rem`, `font-weight: 700`, `color: var(--color-wood)`, `letter-spacing: 0.15em`, `text-align: center`, `background: rgba(139,115,85,0.1)`, `border-radius: 8px`, `padding: 0.5rem 1rem`
- `#sapi-promo-copy` : outline bois, pill 50px, petit (0.8rem)

**6. JS — Intégration Brevo via WordPress AJAX**

Ne pas appeler l'API Brevo directement en JS (clé API exposée). Créer un endpoint AJAX WordPress dans le même snippet :

```php
// Handler AJAX (dans le snippet PHP)
add_action('wp_ajax_nopriv_sapi_brevo_subscribe', 'sapi_brevo_subscribe');
add_action('wp_ajax_sapi_brevo_subscribe', 'sapi_brevo_subscribe');
function sapi_brevo_subscribe() {
    // Vérifier nonce
    // Récupérer l'email POST
    // Récupérer la clé API Brevo depuis les options du plugin Brevo
    //   → chercher dans le plugin Brevo (/wp-content/plugins/mailin/) le nom de l'option qui stocke la clé API
    // Appeler l'API Brevo : POST https://api.brevo.com/v3/contacts
    //   body: { "email": $email, "listIds": [6], "updateEnabled": true }
    // Retourner JSON success/error
    wp_die();
}
```

En JS, appel via `fetch` vers `wp_ajax_url` (à localiser via `wp_localize_script` ou inline).

**7. Cookie de dismissal**
- Si l'utilisateur clique "Non merci" ou que l'email est soumis avec succès : poser cookie `sapi_promo_dismissed=1` (durée 1 an)
- Au chargement : si ce cookie existe, ne pas afficher l'écran 2 (mais l'écran 1 cookies s'affiche quand même si pas de consentement)

**8. Gestion des erreurs**
- Email invalide : message inline sous le champ, pas d'alert()
- Erreur API Brevo : afficher quand même le code promo (ne pas bloquer l'utilisateur)
- En cas d'erreur, logger en `console.warn` uniquement

### Critères de succès
- Après clic Accepter/Refuser, transition fluide vers l'écran 2
- Clic en dehors du popup sur l'écran 2 : aucun effet
- Soumission email valide → contact ajouté dans Brevo liste #6 (vérifier dans le dashboard Brevo)
- Code `BIENVENUE10` affiché dans l'écran de confirmation
- Bouton "Copier le code" fonctionne
- "Non merci" ferme le popup proprement
- Cookie `sapi_promo_dismissed` posé dans les deux cas (email soumis ou skip)
- L'écran 2 ne réapparaît pas à la visite suivante
- Aucune erreur JS console

### Important
Ne pas committer sans accord de Robin. Tester sur test.atelier-sapi.fr.

---

## ✅ Popup cookies custom — Étape 1 terminée (15 avril 2026)
Snippet dans `snippet-sapi-cookie-popup.php` (racine repo, **non commité**). Activé dans Code Snippets (Frontend only) sur test.atelier-sapi.fr. Tous les critères validés par Robin.

**Évolutions finales après itérations avec Robin :**
- Phrase d'accueil animée **lettre par lettre** (fondu pur, stagger 15ms, pause 300ms entre chaque phrase séparée par `<br>`)
- 1ère ligne "Bienvenue sur mon site !" en **plus gros** (1.25em) via `<span class="sapi-cookie-line-intro">`
- JS récursif pour splitter les chars même dans les spans imbriqués
- Signature "Robin" : **orange** (`--color-orange`), **alignée à droite**, plus petite (0.75rem)
- Message body sans "Mêmes règles pour tout le monde", avec retour à la ligne avant "Votre accord ?"
- **Clic/tap en dehors du popup = Refuser** (actif dès l'ouverture, même pendant l'animation)
- Fix anti-flash : overlay `hidden` par défaut + CSS `[hidden] { display: none !important }`, JS retire l'attribut uniquement si pas de consentement
- Languette "Gérer le consentement" Complianz **désactivée côté admin** (réglage "Gérer les options d'affichage" → masqué partout)

**Séquence animation (~3s total) :**
- Phrase : 0.2s → ~2.6s (86 chars × 15ms + 2 pauses × 300ms)
- Signature : 2.7s
- Body (message + boutons) : 3.4s

**Contenu du snippet :**
- Masque `.cmplz-cookiebanner` (+ variantes container) via CSS
- Injecte HTML + CSS + JS inline via `wp_footer` (priorité 100), frontend only
- Détection du consentement déjà donné côté JS : cookies `cmplz_banner-status=dismissed` ou `cmplz_consent_status=allow|deny` (supporte variantes régionales)
- Animation 3 phases : phrase (0.3s) → signature (1.5s) → body (3s), fade-in + translateY
- Charte : `--color-warm`, `--color-wood`, `--color-wood-dark`, font Square Peg pour la phrase, Montserrat uppercase pour signature et boutons, border-radius 16px popup / 50px boutons
- Mobile < 600px : padding réduit, boutons flex:1 côte à côte
- Support `prefers-reduced-motion`
- Intégration Complianz : `cmplz_accept_all()` / `cmplz_deny_all()` avec fallback `cmplz_set_consent()` sur les 4 catégories
- Fermeture = fade-out 0.35s puis `removeChild`

**À vérifier sur test :**
1. Bannière native Complianz invisible
2. Popup centré desktop + mobile, animation 3 phases OK
3. Clic Accepter → cookie `cmplz_banner-status=dismissed` posé, catégories allow
4. Clic Refuser → même cookie posé, catégories deny
5. Rechargement après choix → popup ne réapparaît pas
6. Aucune erreur console

**Important :** si les fonctions `cmplz_accept_all` / `cmplz_deny_all` n'existent pas dans la version Complianz installée, le JS tombe en fallback sur `cmplz_set_consent()`. Si rien ne marche, il faudra aller lire `/wp-content/plugins/complianz-gdpr/assets/js/` sur le serveur pour identifier la vraie API (pas accessible en local).

---

---

## [ARCHIVE] [TÂCHE] Popup cookies custom — Étape 1 : refonte visuelle
**Date :** 2026-04-15
**Priorité :** normale
**Branche :** master (Code Snippet uniquement, pas de modification thème)

### Contexte
Le popup Complianz actuel est une modale générique. On veut le remplacer par un popup sur-mesure dans la charte Atelier Sâpi, avec une animation en 2 phases. La logique de consentement Complianz reste intacte — on remplace uniquement l'apparence visuelle.

### À faire
Créer un nouveau Code Snippet PHP (plugin Code Snippets, frontend only) nommé `sapi-cookie-popup` qui :

**1. Masque la bannière Complianz native**
```css
.cmplz-cookiebanner { display: none !important; }
```

**2. Injecte le popup custom via `wp_footer`**, uniquement si Complianz n'a pas encore de consentement enregistré. Pour vérifier : chercher dans le code Complianz (`/wp-content/plugins/complianz-gdpr/`) la fonction ou le cookie qui indique si le consentement est déjà donné.

**3. Structure HTML du popup**
```html
<div id="sapi-cookie-overlay">
  <div id="sapi-cookie-popup">
    <!-- Phase 1 : phrase animée -->
    <p id="sapi-cookie-phrase">
      Bienvenue sur mon site.<br>
      Je fabrique des luminaires à la main.<br>
      Je respecte aussi le RGPD.
    </p>
    <!-- Signature -->
    <p id="sapi-cookie-signature">Robin</p>

    <!-- Phase 2 : explication + boutons (apparaît après délai) -->
    <div id="sapi-cookie-body">
      <p id="sapi-cookie-message">
        Mêmes règles pour tout le monde ! J'utilise quelques cookies pour vérifier que le site fonctionne, et pour comprendre ce qui vous plaît. Votre accord ?
      </p>
      <div id="sapi-cookie-buttons">
        <button id="sapi-cookie-deny">Refuser</button>
        <button id="sapi-cookie-accept">Accepter</button>
      </div>
    </div>
  </div>
</div>
```

**4. CSS — Charte Atelier Sâpi**
- `#sapi-cookie-overlay` : fond semi-transparent sombre, couvre toute la page, `z-index: 99999`
- `#sapi-cookie-popup` : centré (desktop + mobile), fond `var(--color-warm)` ou `#FAF7F2`, `border-radius: 16px`, `padding: 2.5rem`, `max-width: 520px`, `width: 90%`
- `#sapi-cookie-phrase` : font Square Peg (déjà chargée sur le site), taille ~1.6rem, couleur `var(--color-wood-dark)`, `line-height: 1.6`
- `#sapi-cookie-signature` : Montserrat, `font-weight: 600`, `letter-spacing: 0.1em`, `color: var(--color-wood)`, `margin-top: 1rem`
- `#sapi-cookie-message` : Montserrat, taille 0.9rem, couleur `var(--color-wood-dark)`, `opacity: 0.85`
- `#sapi-cookie-deny` : outline bois (`border: 1.5px solid var(--color-wood)`), fond transparent, texte `var(--color-wood)`, `border-radius: 50px`, `padding: 0.65rem 1.5rem`
- `#sapi-cookie-accept` : fond `var(--color-wood)`, texte blanc, `border-radius: 50px`, `padding: 0.65rem 1.5rem`
- Les deux boutons côte à côte, centrés, `gap: 1rem`

**5. Animation CSS (séquence)**
- `#sapi-cookie-phrase` : `opacity: 0` → `opacity: 1`, fade-in sur 1s, démarre à `animation-delay: 0.3s`
- `#sapi-cookie-signature` : même fade-in, `animation-delay: 1.5s`
- `#sapi-cookie-body` : fade-in sur 0.8s, `animation-delay: 3s`
- Le popup lui-même : fade-in rapide (0.3s) dès l'ouverture de la page

**6. JS — Intégration Complianz**
Lire le code source Complianz pour trouver les fonctions JS correctes (chercher dans `/wp-content/plugins/complianz-gdpr/assets/js/`). Probablement `cmplz_accept_all()` et `cmplz_deny_all()` ou équivalent.

- Clic `#sapi-cookie-accept` → appel fonction accept Complianz → fermeture popup (fade-out)
- Clic `#sapi-cookie-deny` → appel fonction deny Complianz → fermeture popup (fade-out)
- Fermeture = `#sapi-cookie-overlay` disparaît en fondu puis `display: none`

### Critères de succès
- La bannière Complianz native est invisible
- Le popup custom s'affiche bien centré desktop et mobile
- L'animation se déroule en 3 phases : phrase → signature → explication+boutons
- Cliquer Accepter ou Refuser ferme le popup et enregistre le choix dans Complianz (vérifier que le cookie Complianz est bien posé après le clic)
- Le popup ne s'affiche plus si le visiteur a déjà donné son consentement
- Aucune erreur JS en console

### Important
Ne pas committer sans accord de Robin. Créer le snippet et indiquer qu'il est prêt à être activé et testé sur test.atelier-sapi.fr.

---

## ❌ Masquage header/bandeau/fil d'ariane au chargement — ANNULÉ (15 avril 2026)
Commits `19f74ee` + `2e6b007` implémentés puis annulés par commit `551fef4` sur demande de Robin. L'effet visuel n'était pas concluant. Retour au comportement précédent : header + bandeau + breadcrumb visibles dès le chargement de la fiche produit. Mergé dans `test-theme-sapi-maison`.

---

## 🚀 Refonte fiche produit poussée en PRODUCTION (15 avril 2026)
43 commits mergés de `test-theme-sapi-maison` dans `master` (`762baaa` → `7b74ab6`). 4 fichiers modifiés : `style.css`, `woocommerce/single-product.php`, `assets/robin-conseiller.js`, `assets/product-name-formatter.js`. Toute la refonte de la fiche produit est sur la branche prod.

**Actions requises côté Robin :**
1. Lancer le workflow GitHub Actions "Deploy to Production"
2. Vider les caches : WP Super Cache + Autoptimize + Redis

**Inclus dans ce déploiement :**
- Suppression de l'intro screen
- Slideshow ambiance plein-largeur (autoplay, barres de progression, sticky desktop)
- Hero cards (galerie + infos) avec chevauchement du slideshow
- Galerie mobile scroll-snap + dots overlay
- Section "Fabriqué avec passion" refondue en bloc compact
- Pill Robin "Comment choisir ?" déplacé dans les variations
- Bandeau Mon Projet : pill + chips sans wrap
- Variations côte à côte sur mobile
- Sticky dynamique (top calculé selon header + bandeau V1/V2)
- Nombreux ajustements d'espacement mobile

---

## ✅ Refonte section "Fabriqué avec passion" en bloc compact (15 avril 2026)
Commits `bddc7e6`→`7b74ab6` sur `feature/refonte-fiche-produit`, mergés dans `test-theme-sapi-maison`. Changements :
- **HTML** : nouvelle structure `.product-atelier-compact` avec inner flex (photo | texte), numéro de section + H2 "Fabriqué avec passion" + phrase + 2 liens
- **Texte** : "Conçu et assemblé à Lyon, par Robin, artisan créateur."
- **CSS card** : inner en flex avec photo 160px desktop / 110px mobile, fond blanc, border-radius 16px, shadow-card
- **Largeur section** : max-width 1200px (aligné sur `.product-why`, `.product-testimonials`)
- **Wrapper legacy neutralisé** : `.product-atelier.product-atelier-cinetique.product-atelier-compact` remet padding/background/border à 0 pour éviter le double-box
- **Padding section** : 0 en haut, 4rem en bas (2.5rem mobile)
- **H2 margin-bottom** : 1rem
- **Liens** : couleur bois (`var(--color-wood)`), hover `--color-wood-dark`

## ✅ Mobile : variations côte à côte (15 avril 2026)
Commit `beb026e` sur `feature/refonte-fiche-produit`, mergé dans `test-theme-sapi-maison`. `.variations tbody` en `display: flex; flex-direction: row; gap: 0.75rem`, chaque `.variations tr` en `flex: 1; min-width: 0`. Desktop inchangé.

## ✅ Mobile fiche produit — dots overlay + pill Robin + espacement variations (15 avril 2026)
Commits `c756eb1`→`670bddc` sur `feature/refonte-fiche-produit`, mergés dans `test-theme-sapi-maison`. Changements :
- **Dots overlay** : wrapper `.gallery-main-wrap` en position relative, dots en sibling (hors de `.gallery-main` à cause du bug Safari iOS sur `position: absolute` dans `overflow-x: auto`), `bottom: 10px` centré, fond `rgba(0,0,0,0.2)` + border-radius pour lisibilité, dots blancs
- **Pill Robin** : injecté via hook `woocommerce_before_single_variation` (après `</table>` des variations, avant `.single_variation_wrap`). `#robin-product-pill { margin: 0.5rem 0 1rem }` pour l'espacement avec les variations
- **Gap label→swatches mobile** : 0.75rem → 0.15rem, padding tr réduit à 0.5rem 0.75rem, force padding/margin 0 sur `.label` et `th.label` pour neutraliser le plugin WC-swatches

## ✅ Fiche produit mobile — Réduction des espacements (1ère passe, 15 avril 2026)
Commit `1d7140e` sur `feature/refonte-fiche-produit`, mergé dans `test-theme-sapi-maison`. 6 ajustements dans `style.css` :
- Gap cards : 1.5rem → 0.25rem (`.product-hero-container` @768px)
- Padding hero : 1rem 0 2rem → 0.5rem 0 1rem (`.product-hero-v2` @768px)
- Padding card galerie : 0 → 0.5rem (`.product-gallery-v2` @600px)
- Padding card infos : 2rem → 1rem, gap : 1.25rem → 0.75rem (`.product-info-v2` @600px)
- Padding mobile header : 1rem 1rem 0 → 0.5rem 0.75rem 0 (`.product-gallery-mobile-header` @600px)
- Robin pill : padding 0.4rem 0.75rem + font-size 0.75rem (`.robin-pill` @600px)

---

## ✅ Galerie mobile scroll-snap corrigée (15 avril 2026)
Commits `079ef14`→`7203fa7` sur `feature/refonte-fiche-produit`, mergés dans `test-theme-sapi-maison`. Changements :
- **Slides dans `.gallery-main`** : image principale + galerie WC + ACF (ambiance 1, taille, accessoires) + vidéo — tous en `.gallery-slide-extra`, masqués sur desktop (`display: none`), visibles en scroll-snap sur mobile
- **Override CSS mobile** : `position: relative !important` + `flex: 0 0 100% !important` pour contrer les `!important` du CSS de base
- **Vidéo** : `style="display: none;"` inline retiré, visibilité gérée en CSS
- **Dots de pagination** : générés en PHP, mis à jour au scroll via JS (rAF), style 8px gris/wood
- Desktop : aucun changement, thumbnails cliquables inchangés

## ✅ Sticky slideshow desktop + ajustements (15 avril 2026)
Commits `32411c4`→`70c72f4` sur `feature/refonte-fiche-produit`, mergés dans `test-theme-sapi-maison`. Changements :
- **Slideshow sticky desktop** : reste en fond pendant le scroll, wrapper `.product-intro-wrapper`, z-index 2 (< hero 10 < mon-projet-bar 998)
- **Top sticky dynamique** : JS mesure `header.offsetHeight + bandeau.offsetHeight`, injecté via `--slideshow-sticky-top` (compatible V1 `#mon-projet-bar` et V2 `#robin-bandeau`)
- **Hauteur slideshow plein écran** : `calc(100dvh - header - bandeau)` via `--slideshow-height`
- **Card galerie sticky dynamique** : même calcul via `--gallery-sticky-top` (remplace le `top: 100px` en dur)
- **Pause + masquage barres** : IntersectionObserver sur le hero (seuil 25%), pause autoplay + fondu barres quand les cards recouvrent le slideshow
- **Chevauchement augmenté** : -15vh desktop, -10vh mobile, barres à `calc(15vh + 10px)`
- **Galerie hero** : première photo ambiance ajoutée dans les thumbnails (ordre : vidéo > WC > ambiance 1 > taille/accessoires)
- **Pill Robin** : "Comment choisir ?" (PHP + JS robin-conseiller.js)
- **Bandeau Mon Projet** : label en pill fond wood, chips sans wrap

## ✅ Hero cards + galerie mobile scroll-snap (15 avril 2026)
Commits `ec38cd3`→`1cea243` sur `feature/refonte-fiche-produit`, mergés dans `test-theme-sapi-maison`. Changements :
- **Chevauchement hero/slideshow** : margin-top -8vh desktop, -6vh mobile, z-index 10
- **Cards** : galerie et infos avec fond blanc, border-radius 16px, box-shadow
- **Thumbnails filtrés** : seuls types `taille`/`accessoires` restent dans la galerie hero (les autres sont dans le slideshow)
- **Mobile scroll-snap** : galerie en slider horizontal natif, thumbnails/flèches masqués, swipe JS désactivé < 600px
- **Slideshow mobile Stories** : tap gauche/droite pour naviguer, barres fines 3px en haut
- **Slideshow desktop** : barres remontées au-dessus du chevauchement (bottom: calc(8vh + 20px)), largeur 20%
- **Sticky bar** : observe slideshow + hero, n'apparaît que quand les deux sont hors écran
- **Pill Robin** : texte raccourci "Comment choisir ?" (PHP + JS)
- **Bandeau Mon Projet** : label en pill (fond wood, texte blanc), chips sans retour à la ligne
- **Mobile** : titre/description centrés dans la card

## ✅ Slideshow ambiance fiche produit (15 avril 2026)
Commit `85baae1` sur `feature/refonte-fiche-produit`, mergé dans `test-theme-sapi-maison`, déployé sur test.atelier-sapi.fr. Photos ACF filtrées par type (ambiance → vue de dessous → detail → fabrication), crossfade 0.6s, autoplay 4.5s/slide, barres de progression style showcase cards (visibles en permanence, largeur 33%, centrées). S'arrête sur la dernière slide. 80vh desktop / 60vh mobile (dvh pour iOS Safari). Si aucune photo disponible, le bloc ne s'affiche pas. `wp_get_attachment_image()` size `full` pour le focal point.

## ✅ Suppression intro screen fiche produit (15 avril 2026)
Commit `a2bb1b9` sur `feature/refonte-fiche-produit`. Supprimé : variables PHP (`$ambiance_intro_photos`, `$ambiance_intro`), HTML du bloc intro screen, JS d'animation scroll-to-reveal (~120 lignes), CSS complet (~160 lignes), sélecteur `.product-intro-title` du product-name-formatter.js. Aucune référence restante à `product-intro-screen`, `ambiance_intro` ou `sapi-intro-active` dans le code exécutable. La page produit charge directement sur le hero. Mergé dans `test-theme-sapi-maison` et poussé — auto-deploy sur test.atelier-sapi.fr.

## ✅ Pinterest — Événement invalide "s.o." : rien à supprimer (15 avril 2026)
Aucune trace de `pintrk` ou "s.o." dans le code du thème ni dans le plugin Pinterest for WooCommerce. Envoyé seulement 2 fois le 23 mars via JS — probablement un test ponctuel (console navigateur, ancien snippet désactivé, ou interface Pinterest). La source n'existe plus dans le code actuel, l'événement ne se reproduira pas.

## ✅ Pinterest CAPI — Couverture IP/User Agent à 26-27% : normal (15 avril 2026)
Investigation du code plugin : tous les événements CAPI passent par un chemin unique (`wp_remote_request()` → capturé par `http_request_args`). Le snippet intercepte bien 100% des appels CAPI. Le 26-27% s'explique par le full-page caching (WP Super Cache) : les pages servies depuis le cache ne déclenchent pas PHP, donc seul le Tag JavaScript fire (pas le CAPI serveur). Les ~27% de pages non cachées exécutent PHP et le CAPI avec les paramètres enrichis. C'est le maximum atteignable avec cette architecture — comportement normal et attendu.

## ✅ Pinterest CAPI — Enrichissement des paramètres manquants (14 avril 2026)
Snippet créé dans `snippet-pinterest-capi-enrichment.php` et activé dans Code Snippets. Intercepte les requêtes HTTP vers `api.pinterest.com/v5/.../events` via le filtre `http_request_args` pour enrichir `user_data` avec IP, User Agent, External ID (SHA-256 email) et Click ID (`_epik`). Corrige aussi un bug du plugin qui écrase IP et User Agent quand l'email est disponible. Vérifier sous 24-48h dans Pinterest Business Manager que les 4 paramètres passent à "bon état".

Il existe déjà un snippet Pinterest actif dans Code Snippets (`Filtre les catégories lors de la synchronisation Pinterest`) qui utilise le filtre `pinterest_for_woocommerce_should_include_product_in_feed`. Le nouveau snippet doit suivre le même pattern.

**À faire :**
1. Lire le code source du plugin Pinterest for WooCommerce (dans `/wp-content/plugins/pinterest-for-woocommerce/`) pour identifier le ou les filtres permettant d'enrichir les données des événements CAPI (chercher : `apply_filters`, `event_data`, `conversion`, `capi`)
2. Créer un snippet PHP à ajouter dans Code Snippets (pas dans functions.php) qui enrichit les événements CAPI avec les 4 paramètres manquants :
   - **IP Address** : lire `$_SERVER['HTTP_X_FORWARDED_FOR']` ou `$_SERVER['REMOTE_ADDR']`
   - **User Agent** : lire `$_SERVER['HTTP_USER_AGENT']`
   - **External ID** : SHA-256 de l'email du client (si connecté via `wp_get_current_user()` ou en checkout via billing email)
   - **Click ID (_epik)** : capturer le paramètre `_epik` dans l'URL à l'arrivée sur le site, le stocker en cookie `_epik`, puis le passer dans les événements CAPI
3. Le snippet doit être sans effet de bord : si un paramètre n'est pas disponible (visiteur anonyme, pas de `_epik`), ne rien envoyer plutôt que d'envoyer une valeur vide

**Critères de succès :**
- Le snippet s'active dans Code Snippets sans erreur PHP
- Après activation, Pinterest "Qualité des Conversions" → les 4 paramètres passent de "à améliorer" à "bon état" (vérifier sous 24-48h)
- Aucun impact sur le frontend, le tunnel de commande, ou les performances

## ✅ Branche feature/refonte-fiche-produit créée (14 avril 2026)
Branche `feature/refonte-fiche-produit` créée depuis `master` et poussée sur GitHub. Aucune modification de code. Prête pour le chantier de refonte de la fiche produit.

## ✅ Showcase slideshow : barres de progression (14 avril 2026)
Commits `40e10ff`→`92ce679`. Ajout de barres de progression style Stories Instagram en bas de la zone photo des showcase cards (pages catégorie). Une barre par slide, la barre active se remplit progressivement sur 1,8s, les précédentes sont pleines, les suivantes vides. Apparaissent uniquement au hover, reset au mouseleave. Barres centrées, largeur 1/3 de la zone photo, 4px d'épaisseur, fond sombre semi-transparent pour contraste sur fond clair et sombre. Barres créées en JS (pas de markup PHP). Mergé master `762baaa`. **Robin doit lancer le workflow GitHub Actions + vider les caches (Autoptimize + WP Super Cache + Redis).**

## ✅ Fix mobile showcase photos (13 avril 2026)
Commit `ec756d6`. `min-height: 250px` ajouté sur `.sapi-showcase-card .showcase-photo` dans le media query `max-width: 600px`. Cause : `.showcase-bg` en `position: absolute` + flex column → `flex-basis: 0` écrasait le `height`. Mergé master `a3efd94`, déployé en prod ✅

## ✅ Fix fermeture mutuelle panier/recherche (13-14 avril 2026)
Commits `0195b0e`→`a7e46c2`. Les panneaux mini-cart et search modal pouvaient rester ouverts simultanément (IIFEs indépendantes). Fix : chaque `openX()` ferme l'autre via `.click()` sur son bouton `.close`, ce qui déclenche le vrai handler (focus trap, vidage champ, nettoyage résultats). Mergé master `1191e18`, déployé en prod ✅

## ✅ Bug homepage prod — cache Autoptimize (14 avril 2026)
Le panneau panier et la recherche s'affichaient visibles au chargement sur la homepage en production. Cause : Autoptimize servait du CSS optimisé/critique sans les règles `visibility: hidden` des panneaux. Résolu en vidant les caches (WP Super Cache + Autoptimize + Redis).

## ✅ Focal point — wp_get_attachment_image() sur toutes les images (13 avril 2026)
Commits `7a2ed29`→`7ca09cb`, mergé master `9370500`. Remplacé tous les `<img>` manuels convertibles par `wp_get_attachment_image()` pour le plugin Media Focus Point. Déployé en prod ✅

## ✅ Suppression complète du système quick-view (13 avril 2026)
Commit `65cb699`. Supprimé : `quick-view.js` (589 lignes), modale HTML, boutons Aperçu, ~550 lignes CSS. Mergé master, déployé en prod ✅

## ✅ Showcase cards — diaporama photos au hover (13 avril 2026)
Commits `8d4b95b`→`63cc106`. Jusqu'à 6 photos alternance ambiance/détail, crossfade au hover. Mergé master, déployé en prod ✅

---

## ✅ Terminées (avant le 13 avril)

- Refonte filtres mobile — dropdown custom + harmonisation (12 avril 2026)
- Harmonisation boutons CTA (12 avril 2026)
- Modale réalisations sur mesure — refonte complète (12 avril 2026)
- Cards réassurance — page Mes créations (12 avril 2026)
- Page sur mesure V2 + fixes (12 avril 2026)
- Refonte complète slider réalisations sur mesure (12 avril 2026)
- Audit + nettoyage page sur mesure (12 avril 2026)
- Fix dots doublés sur mobile (12 avril 2026)
- HOTFIX PROD — Bug onglets sur mesure mobile (11-12 avril 2026)
- Refonte grille catégories — Showcase split (11 avril 2026)
- Fix page Mes créations (11 avril 2026)
- Accessoires : photo produit WooCommerce (11 avril 2026)
- Bandeau Robin V2 — revert vers version réassurance (11 avril 2026)
- Harmonisation des ombres cards (11 avril 2026)

*(purgé le 8 avril 2026 — tâches précédentes)*
