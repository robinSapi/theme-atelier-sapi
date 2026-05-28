# Phase 4 — Architecture du swap photos par pièce (reco Claude Code)

**Date :** 2026-05-28
**Branche :** `feature/photos-par-piece`
**Statut :** Investigation Phase 4a — document de reco pour validation par Robin avant Phase 4b
**Prérequis livrés :** Phases 0 (audit), 1 (taxonomies + Gallery vides), 2 (migration 274 photos en dual-write), 3 (helper dual-read + filtres `$piece`/`$essence`)

---

## 1. État des lieux du mécanisme `sapiProject`

### 1.1 Stockage

- **Clé localStorage** : `sapiProject` (constante `STORAGE_KEY` dans `assets/sapi-project.js:23`)
- **Format** :
  ```js
  {
    answers:    { piece: 'salon', taille: 'spacieuse', style: 'moderne', ... },
    labels:     { piece: 'Salon / Salle à manger', ... },
    created_at: 1716000000,  // timestamp Unix (s)
    updated_at: 1716000123,
    session_id: 'mfs_xxx' | null,
    advice_text: string | null,
    action: 'contact' | null,
    contact_kind, contact_subject, contact_message  // bouton "contact" IA
  }
  ```
- **Aucun cookie associé** aujourd'hui. Le PHP n'a aucun moyen de connaître la pièce du visiteur au moment du rendu.
- **Ingestion `?piece=X` au load** : si l'URL contient `?piece=salon`, l'init JS écrit le projet (reset complet si pièce différente de l'existante). Nettoyé de l'URL ensuite via `history.replaceState`.

### 1.2 API publique exposée sur `window.sapiProject`

| Méthode | Rôle |
|---|---|
| `get()` | Retourne le projet complet (jamais null) |
| `hasProject()` | True s'il y a au moins une answer |
| `getAnswer(key)` / `getLabel(key)` | Lecture ciblée |
| `set(answers, labels, extra)` | Remplace entièrement (sortie modale) |
| `update(patchAnswers, patchLabels)` | Patch partiel (null supprime une clé). Invalide `advice_text` si answers changent réellement |
| `clear()` | Wipe localStorage + nettoie `?piece=` de l'URL |
| `subscribe(fn)` | Registre un callback notifié à chaque mutation. Renvoie un `unsubscribe()` |
| `pauseNotifications()` / `resumeNotifications()` | Mute temporaire (utilisé par la modale) |
| `setAdviceText(text)` / `setContactState(payload)` | Mutations ciblées sans toucher aux answers |
| `computeVisibleStepIds()` / `cleanInvisibleAnswers()` | Helpers visibilité (mirror PHP) |

### 1.3 Events / mécanisme de notification

- **Pas d'event `sapi:project-changed`** ni équivalent — le mécanisme officiel = `subscribe(callback)` (registre interne dans `listeners[]`).
- **Sync inter-onglets** : `window.addEventListener('storage', ...)` rappelle `notify()` quand le localStorage change dans un autre onglet (sapi-project.js:417).
- **Events custom existants dans le thème** :
  - `sapi:open-modal` (dispatché par sapi-help-pill.js, écouté par la modale)
  - `sapi:apply-product-selection` (dispatché par la modale, écouté par sapi-product-preselect.js)
  - Aucun event lié au changement de projet.
- **Implication pour Phase 4** : pour brancher un swap JS sur le changement de pièce, le pattern naturel = `sapiProject.subscribe(fn)`. Pas besoin d'inventer un nouvel event.

### 1.4 Pattern de swap live JS existant dans le thème

| Module | Surface | Pattern |
|---|---|---|
| `sapi-hero-live.js` | H1 hero `/mes-creations/` | `subscribe(fn)` → fade-out 125ms → swap `textContent` → fade-in 125ms |
| `sapi-help-pill.js` | Pill "Comment choisir ?" en fiche produit | `subscribe(fn)` → update `textContent` du pill |
| `sapi-cards-conseiller.js` | Cards "Conseil de Robin" / "Mon projet" + grille `/mes-creations/` | `subscribe(fn)` → `render()` complet (re-template HTML + animations) + `refilterGrid()` (toggle `.is-filtered-out` sur les cards produit) |
| `sapi-product-preselect.js` | Variation taille + matière sur fiche produit | **Pas `subscribe`** — applique au load (`init`) + écoute `sapi:apply-product-selection` (dispatché par modale au CTA explicite) |

**Pattern dominant** : `subscribe(fn)` au load → re-render in-place sur changement. Cohérent partout sauf `sapi-product-preselect.js` qui n'a besoin de réagir qu'au load ou à un event explicite (changement de variation = navigation utilisateur, pas changement passif de projet).

**Aucun module ne fait de swap d'images** aujourd'hui — Phase 4 introduit un nouveau type de swap (`img.src` + `img.srcset = ''`).

---

## 2. Contraintes de cache prod

### 2.1 Caches actifs en prod (inféré de l'historique queue + feedback workflow)

- **LiteSpeed Web Server (LSCache au niveau serveur)** : standard sur O2switch. Cache full-page anonyme par défaut.
- **WP Super Cache** : plugin actif, mentionné explicitement dans 3 incidents de cache passés (`queue:9956`, `queue:10071`, `queue:10079`). Cache full-page anonyme également.
- **Autoptimize** : minification + concat CSS/JS, génère des fichiers cachés.
- **Redis Object Cache** : cache d'objets DB. **Pas impactant** pour notre cas (cache de fragments de query, pas de pages).

**Plugin LSCache (côté WP)** : présence non confirmée depuis le repo, mais probable étant donné O2switch. Si présent, il vary par défaut selon `WP-Postpass`, `wordpress_logged_in_*`, `comment_author_*`. **Pas de vary par cookie custom sans config explicite.**

### 2.2 Question clé — un cookie `sapi_piece` serait-il vu par le rendu serveur ?

**Réponse : pas par défaut.**

- **Cache hit anonyme** : la page est servie depuis le cache sans exécuter PHP. Le cookie est ignoré pour le routing cache.
- **Cache miss / bypass** : PHP s'exécute → le cookie est lu normalement. Mais le résultat est cached et servi aux visiteurs suivants en cache hit, **figé à la pièce du premier visiteur post-purge**.

**Pour qu'un cookie influence le rendu serveur cacheé, il faut explicitement :**
- **(A)** Configurer LSCache plugin / WP Super Cache pour **vary** sur le cookie `sapi_piece` (pollue le cache ×6 — une copie par pièce + une par défaut), OU
- **(B)** Exclure de cache les URLs concernées (`/mes-creations/`, `/categorie-produit/*`, `/produit/*`, home) — perd les bénéfices de cache sur 4 surfaces critiques, OR
- **(C)** Utiliser **ESI (Edge Side Includes)** de LSCache pour rendre dynamiquement uniquement le bloc photo, le reste de la page restant cached. Complexe à mettre en place + requiert LSCache plugin activé + balises `<!-- esi:include ... -->` dans les templates.

### 2.3 Verdict viabilité rendu serveur direct (zéro flash)

**Non viable sans config infra** sur la stack actuelle. Plus précisément :

- Sur une infra "WP nu sans cache" → rendu serveur direct via cookie marche parfaitement et zéro flash.
- Sur la stack actuelle (LSCache + WP Super Cache actifs) → le rendu serveur servirait une version figée. La première personne à charger la page après purge cache "gagne" la version par défaut, et les suivants reçoivent cette même version, même avec un cookie différent.
- **Conclusion** : viser le zéro flash exige soit une config infra non triviale (vary cookie ou ESI), soit on accepte un swap JS post-rendu (pattern dominant du thème).

**Recommandation cache** : prioriser une solution **JS swap** compatible cache, et ne pas toucher à l'infra. Si Robin veut vraiment zéro flash plus tard, c'est un investissement séparé (config LSCache).

---

## 3. Surfaces à swapper — mapping aux call-sites Phase 0

D'après les règles de swap actées dans `project_photos_par_piece` (Cowork) :

| # | Surface | Photo type | Call-site Phase 0 | Via helper ? | Refactor ? |
|---|---|---|---|---|---|
| 1 | Cards `/mes-creations/` | `ambiance` ×1 | `archive-product.php:364` | ✅ helper | Ajouter `$piece` |
| 2 | Cards page catégorie (hover) | `ambiance` ×2 | `content-product.php:127` | ✅ helper | Ajouter `$piece` |
| 3 | Fiche produit positions 1+2 carousel ambiance | direct repeater | `single-product.php:104` (lecture directe ligne 104, boucle `foreach $galerie_repeater`) | ❌ direct read | **Refactor partiel** : extraire positions 1+2 du carousel ambiance et utiliser le helper avec `$piece` pour celles-là spécifiquement |
| 4 | Home produit featured (coup de cœur) | `detail` ×1 | `front-page.php:236` | ✅ helper | Ajouter `$piece` |

**Surfaces hors périmètre swap** (confirmer avec Robin) :
- Home carousel principal (`front-page.php:41`) — pas dans les règles
- Home star du moment (`front-page.php:132`) — pas dans les règles
- Home collections (`front-page.php:309`) — pas dans les règles
- Pages catégorie hero/fabrication (`taxonomy-product_cat.php:79/80/178/371`) — pas dans les règles
- Page la-star-du-moment (`page-la-star-du-moment.php:50/51`) — pas dans les règles
- Page inspiration (`page-inspiration.php:30`) — lecture directe, hors périmètre
- Preload LCP carousel (`functions.php:190`) — pas dans les règles
- Bandeau ambiance card sur-mesure (`functions.php:5300`) — pas dans les règles
- Photo client testimonials (`single-product.php:558`) — pas dans les règles

**Périmètre total Phase 4b = 4 call-sites à modifier** (dont 1 refactor partiel sur `single-product.php`). Le périmètre est **plus petit que l'audit complet** (17 indirects + 5 directs), ce qui réduit le risque de régression.

---

## 4. Options d'architecture

### Option A — Cookie `sapi_piece` + rendu serveur direct

**Principe** : JS écrit `sapiProject.answers.piece` dans un cookie ; PHP lit `$_COOKIE['sapi_piece']` et passe `$piece` au helper.

```js
// sapi-project.js : écrire le cookie après chaque update()
function syncPieceCookie() {
  var piece = getAnswer('piece');
  if (piece) document.cookie = 'sapi_piece=' + piece + ';path=/;max-age=2592000;samesite=lax';
  else document.cookie = 'sapi_piece=;path=/;max-age=0';
}
```

```php
// dans chaque call-site Phase 4
$piece = isset($_COOKIE['sapi_piece']) ? sanitize_key($_COOKIE['sapi_piece']) : null;
$ids = sapi_get_product_photo_ids($id, 'ambiance', 1, $piece);
```

**Trade-offs** :
- ✅ Zéro flash après le 1er load (le cookie sync est instantané au DOMContentLoaded du load 1)
- ❌ Premier load tout court : pas de cookie → rendu par défaut (= ambiance générique). Acceptable si on accepte que le visiteur qui démarre un parcours doit voir une 2e page pour voir le swap.
- ❌ **Cache incompatible** sans config infra (voir §2.3). En l'état, ne marche que si l'URL est dans une exclusion de cache.
- ❌ Le swap "in-session" (modale fermée sans reload) **ne fonctionne pas avec cette option seule** — il faut JS en complément pour ce cas.

**Verdict** : non viable seul. Pourrait être un complément d'Option B (D1/D2/D3 ci-dessous) si Robin investit sur la config cache.

---

### Option B — Swap JS post-rendu via AJAX (compatible cache)

**Principe** : PHP rend la version par défaut. JS au load lit `sapiProject.answers.piece`, fait UN appel AJAX qui retourne la map `[product_id => piece_photo_url]` pour toutes les cards de la page, applique en remplaçant `img.src` + `img.srcset = ''`.

```js
// nouveau assets/sapi-photo-swap.js
function swapPhotosForPiece(piece) {
  if (!piece) return;
  var cards = document.querySelectorAll('[data-product-id][data-piece-swap]');
  if (!cards.length) return;
  var ids = Array.from(cards).map(function (c) { return c.dataset.productId; });

  fetch(SAPI_PHOTO_SWAP.ajaxUrl + '?action=sapi_get_piece_photos&piece=' + encodeURIComponent(piece) + '&ids=' + ids.join(',') + '&_wpnonce=' + SAPI_PHOTO_SWAP.nonce)
    .then(function (r) { return r.json(); })
    .then(function (map) {
      cards.forEach(function (card) {
        var url = map[card.dataset.productId];
        if (!url) return;
        var img = card.querySelector('img');
        if (!img) return;
        img.src = url;
        img.srcset = ''; // règle CLAUDE.md #9
      });
    });
}

// init + subscribe pour swap in-session (modale ferme sans reload)
window.sapiProject && window.sapiProject.subscribe(function (project) {
  swapPhotosForPiece(project.answers && project.answers.piece);
});
swapPhotosForPiece(window.sapiProject && window.sapiProject.getAnswer('piece'));
```

```php
// nouveau handler AJAX dans functions.php
add_action('wp_ajax_sapi_get_piece_photos', 'sapi_ajax_get_piece_photos');
add_action('wp_ajax_nopriv_sapi_get_piece_photos', 'sapi_ajax_get_piece_photos');
function sapi_ajax_get_piece_photos() {
  check_ajax_referer('sapi_photo_swap', '_wpnonce');
  $piece = isset($_GET['piece']) ? sanitize_key($_GET['piece']) : '';
  $ids   = isset($_GET['ids']) ? array_map('intval', explode(',', $_GET['ids'])) : [];
  $map = [];
  foreach ($ids as $pid) {
    $photo_ids = sapi_get_product_photo_ids($pid, 'ambiance', 1, $piece);
    if (empty($photo_ids)) continue; // fallback : on n'envoie rien, le default reste
    $url = wp_get_attachment_image_url($photo_ids[0], 'large');
    if ($url) $map[$pid] = $url;
  }
  wp_send_json($map);
}
```

**Trade-offs** :
- ✅ Compatible cache (HTML cacheable, JS s'exécute côté client)
- ✅ Pattern cohérent avec `sapi-hero-live.js`, `sapi-help-pill.js`, `sapi-cards-conseiller.js` (subscribe-based)
- ✅ Swap in-session OK (modal close → subscribe trigger → AJAX → swap)
- ✅ Premier load fonctionne immédiatement si localStorage présent
- ❌ **Micro-flash visible** : le navigateur peint la photo par défaut puis swap après round-trip AJAX (~50-200ms en fonction de la latence O2switch)
- ❌ Round-trip réseau systématique. Mitigation : batcher en 1 appel pour toutes les cards de la page (déjà prévu ci-dessus).
- ⚠️ Le `srcset = ''` perd l'optimisation responsive du navigateur. À évaluer : impact taille image vs flash.

**Verdict** : robuste, compatible cache, pattern thème. **Acceptable si Robin tolère ~150ms de micro-flash sur premier load avec projet existant.**

---

### Option C — Data-attributes pré-rendus + JS sync au load (zéro AJAX)

**Principe** : PHP rend toutes les variantes par pièce dans le HTML, exposées en `data-` attributes. JS lit `localStorage.piece` et applique l'URL correspondante immédiatement au DOMContentLoaded.

```php
// dans archive-product.php (et autres call-sites concernés)
$default_ids = sapi_get_product_photo_ids($product_id, 'ambiance', 1);
$variants_by_piece = [];
foreach (['salon', 'cuisine', 'chambre', 'bureau', 'entree', 'escalier'] as $p) {
  $pids = sapi_get_product_photo_ids($product_id, 'ambiance', 1, $p);
  if (!empty($pids)) {
    $url = wp_get_attachment_image_url($pids[0], 'large');
    if ($url) $variants_by_piece[$p] = $url;
  }
}
$data_attrs = '';
foreach ($variants_by_piece as $p => $url) {
  $data_attrs .= ' data-photo-' . esc_attr($p) . '="' . esc_url($url) . '"';
}
// rendu : <img src="..." data-photo-salon="..." data-photo-cuisine="..." ...>
```

```js
// très tôt au load (ou inline en <head> pour minimiser flash)
(function () {
  try {
    var raw = localStorage.getItem('sapiProject');
    if (!raw) return;
    var piece = JSON.parse(raw).answers && JSON.parse(raw).answers.piece;
    if (!piece) return;
    document.querySelectorAll('img[data-photo-' + piece + ']').forEach(function (img) {
      img.src = img.dataset['photo' + piece.charAt(0).toUpperCase() + piece.slice(1)];
      img.srcset = '';
    });
  } catch (e) {}
})();
// + subscribe pour swap in-session
window.sapiProject && window.sapiProject.subscribe(function (project) { /* idem */ });
```

**Trade-offs** :
- ✅ Compatible cache (HTML statique cacheable, JSON dans data-attrs ne dépend pas du visiteur)
- ✅ Zéro AJAX, zéro round-trip
- ✅ Flash très réduit si le JS s'exécute avant le paint (inline en `<head>`, exécution sync = avant le rendering des `<img>` de la grille). En pratique, ~30-80ms si JS bloquant.
- ❌ **HTML plus lourd** : +6 attributs `data-photo-*` par card. Pour 24 produits sur `/mes-creations/`, ça fait 24×6=144 URLs en plus dans le HTML. ~150 chars/URL = +22 KB par page (avant gzip). Avec gzip = ~3-5 KB. **Acceptable.**
- ❌ **Plus de requêtes DB côté PHP** : pour chaque card, on appelle le helper 6 fois (1 fois par pièce) au lieu de 1. Au total 24×6 = 144 appels helper sur `/mes-creations/` au lieu de 24. **Acceptable si page-cache prend le relais en prod**, problématique sur test sans cache (~+500ms TTFB).
- ❌ Le navigateur peut commencer à fetch `img.src` AVANT que le JS s'exécute. Pour les images "above the fold" (LCP), le swap après fetch initial = waste de bande passante.
- ⚠️ Code PHP plus verbeux (boucle sur 6 pièces dans chaque call-site).

**Verdict** : meilleure latence visuelle qu'Option B, mais plus complexe à implémenter. À considérer si Robin trouve le flash B trop visible en test.

---

### Option D — Hybride cookie + JS (zéro flash post-1er-load + compatible in-session)

**Principe** : combine A et B. Cookie pour le 2e+ load, JS subscribe pour in-session.

- JS écrit cookie `sapi_piece` à chaque update sapiProject
- PHP lit cookie au rendu suivant → zéro flash dès le 2e load
- JS subscribe gère le swap in-session (modale fermée sans reload)

**Pré-requis cache** :
- **D1** : LSCache configuré pour varier sur `sapi_piece` (multiplie le cache ×6 + 1 par défaut = 7 versions par URL). Config infra non triviale.
- **D2** : URLs concernées exclues du page-cache (`/mes-creations/`, `/categorie-produit/*`, `/produit/*`, home). Perte de cache sur 4 surfaces critiques — impact perf prod réel.
- **D3** : ESI (Edge Side Includes) — uniquement les blocs photo rendus dynamiquement, reste de la page cached. Le plus propre techniquement mais le plus complexe à mettre en place (balises ESI dans le markup, config LSCache, tests).

**Trade-offs** :
- ✅ Zéro flash sur 95% des cas (sauf le tout premier load avec projet déjà existant)
- ✅ Pattern subscribe pour in-session = cohérent avec le thème
- ❌ Dépend d'une config infra (D1/D2/D3 tous coûteux)
- ❌ Premier load tout court reste flashy (acceptable — pas de projet à swapper)

**Verdict** : la version "zéro flash idéale" de Robin, mais demande de la config infra. À garder en tête comme **upgrade future** si Robin veut éliminer le micro-flash de B.

---

## 5. Recommandation argumentée

### 5.1 Option recommandée : **Option B (Swap JS via AJAX, compatible cache)**

**Pourquoi B et pas C/D :**

1. **Cohérence pattern** : tous les modules `sapi-*-live.js` existants utilisent `subscribe(fn)` + DOM patching. Option B prolonge ce pattern naturellement (un nouveau module `sapi-photo-swap.js`).
2. **Pas de config infra** : ne demande aucune intervention sur LSCache / O2switch / WP Super Cache. Risque opérationnel minimal.
3. **Robustesse cache** : la page reste cacheable identiquement à aujourd'hui — aucun impact perf prod sur les visiteurs sans projet (= majorité du trafic).
4. **Réversibilité** : si le micro-flash devient un problème ressenti, on peut migrer vers C (data-attributes pré-rendus) sans refactor majeur — on garde le module `sapi-photo-swap.js`, on lui dit juste de lire `data-photo-*` au lieu de faire fetch().
5. **Périmètre maîtrisé** : 4 call-sites front-end à modifier + 1 nouveau handler AJAX + 1 nouveau JS = ~150-200 lignes. Implémentable en une seule session Phase 4b.

**Compromis assumé** : ~50-200ms de micro-flash sur premier load avec projet existant. Atténué par la chaîne d'événements rapide : DOMContentLoaded → lecture localStorage (sync, ~1ms) → fetch AJAX vers `wp_ajax_sapi_get_piece_photos` (~50-150ms sur O2switch en cache hit côté DB) → swap `img.src`. En pratique, l'utilisateur perçoit le swap avant d'avoir mémorisé l'image par défaut.

### 5.2 Découpage Phase 4b proposé

**Ordre des surfaces** (de la moins risquée à la plus visible) :

1. **Chantier 1** — Infrastructure JS + handler AJAX
   - Nouveau `assets/sapi-photo-swap.js` (~80 lignes)
   - Nouveau `wp_ajax_sapi_get_piece_photos` dans `functions.php` (~30 lignes)
   - Wrapper helper `sapi_get_product_photo_ids_with_fallback($id, $type, $limit, $piece)` qui auto-fallback (voir §6). Optionnel mais propre.
   - Enqueue conditionnel : uniquement sur les surfaces qui auront le data-attr `data-piece-swap`.

2. **Chantier 2** — Activation sur `/mes-creations/` (`archive-product.php:364`)
   - Ajouter `data-product-id` + `data-piece-swap` sur la card produit
   - Tester sur test : ouvrir `/mes-creations/?piece=salon`, vérifier le swap

3. **Chantier 3** — Activation sur les cards page catégorie (`content-product.php:127`)
   - Idem chantier 2, sur les cards des pages `/categorie-produit/*`

4. **Chantier 4** — Activation sur home featured (`front-page.php:236`)
   - Idem, type=`detail` au lieu d'`ambiance`

5. **Chantier 5** — Activation sur fiche produit positions 1+2 (`single-product.php:104`)
   - **Plus délicat** : aujourd'hui c'est une lecture directe du repeater, pas via le helper. Il faut refactor partiellement pour identifier les 2 premiers ambiance du repeater et les remplacer par les piece-photos si elles existent.
   - Approche : `sapi_get_product_photo_ids($id, 'ambiance', 2, $piece)` retourne 0, 1 ou 2 IDs. On insère ces IDs en position 1 et 2 du carousel ambiance. Si moins de 2, on complète avec les positions 1-2 de l'ambiance par défaut.

**Test de validation à chaque chantier** : changer la pièce via `?piece=salon`, `?piece=cuisine`, etc. en URL, vérifier visuellement que le swap se produit (et qu'il retombe sur le défaut si la pièce n'a aucune photo taguée).

### 5.3 Gestion du fallback (pièce sans photo taguée)

**Le problème** : aujourd'hui Phase 5 (tagging des photos par Robin via `media_room`) n'est pas faite. Le helper avec `$piece='salon'` retourne `[]` pour la grande majorité des produits. **Sans fallback, le swap aboutit à un trou (image vide).**

**Deux options** :

- **F1** — Auto-fallback dans un wrapper helper (`sapi_get_product_photo_ids_with_fallback`)
  ```php
  function sapi_get_product_photo_ids_with_fallback($post_id, $type, $limit, $piece) {
    $ids = sapi_get_product_photo_ids($post_id, $type, $limit, $piece);
    if (empty($ids) && !empty($piece)) {
      $ids = sapi_get_product_photo_ids($post_id, $type, $limit); // refall sans filtre
    }
    return $ids;
  }
  ```
  - ✅ Centralisé, les call-sites n'ont pas à gérer le cas vide
  - ✅ Silencieux (pas d'erreur si pas de photo taguée)
  - ⚠️ Peut masquer un bug si la taxonomie est mal configurée
  - **Reco** : utiliser ce wrapper dans **tous** les call-sites Phase 4 + dans le handler AJAX. Le helper canonique `sapi_get_product_photo_ids` reste strict (ne fallback pas tout seul), pour préserver la sémantique des autres call-sites.

- **F2** — Fallback explicite dans chaque call-site
  ```php
  $ids = sapi_get_product_photo_ids($id, 'ambiance', 1, $piece);
  if (empty($ids)) $ids = sapi_get_product_photo_ids($id, 'ambiance', 1);
  ```
  - ✅ Explicite, le fallback est visible dans le code
  - ❌ Duplication (×4 surfaces, plus AJAX = 5 endroits)

**Recommandation** : **F1 (wrapper auto-fallback)** dans `functions.php`, à côté du helper canonique. Nom : `sapi_get_product_photo_ids_with_fallback($post_id, $type, $limit, $piece, $essence = null)`. Le wrapper laisse `$piece`/`$essence` faire leur travail dans `sapi_get_product_photo_ids` d'abord, et en cas de retour vide, retire les filtres et re-call. Auto-fallback granulaire : si `$piece='salon'` ET `$essence='peuplier'` retournent vide, on tente d'abord sans `$essence`, puis sans `$piece` non plus.

---

## 6. Décisions à trancher par Robin avant Phase 4b

1. **Accepte-t-il l'Option B (micro-flash ~150ms sur premier load avec projet)** ou veut-il viser l'Option D (zéro flash, mais investir en config cache infra) ?
   - **Reco Claude Code** : B. Le micro-flash est imperceptible sur un swap d'image (pas un layout shift), cohérent avec le pattern thème.

2. **Stratégie de fallback** : auto-fallback dans un wrapper (F1, reco), ou fallback explicite dans chaque call-site (F2) ?
   - **Reco Claude Code** : F1. Évite la duplication, le helper canonique reste sémantiquement strict.

3. **Pour la fiche produit (chantier 5)** : on accepte de refactor partiellement la lecture directe du repeater dans `single-product.php:104` pour utiliser le helper avec `$piece` sur les positions 1+2 du carousel ambiance ?
   - Alternative : laisser le repeater inchangé en fiche produit et ne swapper que les 3 autres surfaces (mes-creations, page catégorie, home featured). Mais l'effet "personnalisation" serait incomplet — la fiche produit est la surface où le swap est le plus engageant.
   - **Reco Claude Code** : oui, refactor. C'est ~20 lignes localisées, gérables.

---

## 7. Annexe — Pourquoi pas d'event custom `sapi:project-changed`

L'audit du code (§1.3) confirme qu'aucun event de ce type n'existe. Le pattern `subscribe(fn)` couvre déjà 100% des consommateurs JS du projet (`sapi-hero-live`, `sapi-help-pill`, `sapi-cards-conseiller`, plus le nouveau `sapi-photo-swap` proposé).

**Faut-il introduire un event** ? Non, sauf si on veut découpler `sapi-project.js` (qui ne saurait plus qui écoute). Le registre de listeners interne est suffisant et plus simple à debug que des events DOM dispersés.

---

## 8. Annexe — Pourquoi le swap n'a pas besoin d'être réactif côté serveur

L'analyse §1 montre que **le seul cas où PHP pourrait connaître la pièce au rendu = via cookie** (ou via `?piece=X` en URL, déjà géré côté JS). Tous les autres scénarios (localStorage seul, modale fermée sans reload, sync inter-onglets) nécessitent du JS.

**Conclusion architecturale** : le swap est **fondamentalement client-side**. Le serveur peut au mieux préparer le terrain (data-attributes, cookie sync, ESI), mais le client a toujours la main sur le swap final. Cela justifie de partir sur Option B (full JS) comme socle, et d'y ajouter du serveur uniquement si l'expérience le justifie plus tard.
