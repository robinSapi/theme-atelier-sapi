# Tasks — Coordination Cowork ↔ Claude Code

> Historique des tâches terminées archivé dans `claude_code_queue_archive.md` (nettoyé le 2026-06-03).

---

## [TÂCHE] Anti-spam formulaires de contact — time-trap + filtre junk + rate limit Conseiller
**Date :** 2026-08-05
**Priorité :** haute
**Branche :** test uniquement (`test-theme-sapi-maison`), Robin valide avant prod. Fichier concerné : `functions.php` + `page-contact.php` (thème, pas de snippet).

**Contexte :** nuit du 4→5 août, rafale de spam de bot sur les formulaires de contact (email `testing@example.com`, message `555`/`20`, noms aléatoires type `UZJglwlz`, ~11 envois entre 02h21 et 03h31). Les 3 formulaires ont DÉJÀ nonce + honeypot (champ `website`) + rate limit 5/h/IP (sauf le Conseiller). Le bot les contourne : il **change d'IP** à chaque envoi (défait le rate limit par IP) et **ignore le honeypot** (parse le form, saute le champ caché). Il faut donc ajouter une couche que la rotation d'IP ne casse pas, **sans friction pour les vrais clients** et **sans dépendance externe** (pas de captcha pour l'instant, on le garde en réserve).

Les 3 handlers concernés dans `functions.php` / `page-contact.php` :
1. `page-contact.php` (POST classique) → mail `[Atelier Sapi] Nouveau message de …` — a déjà honeypot + rate limit.
2. `sapi_ajax_robin_contact()` (AJAX) → mail `[Mon Projet] Demande de contact` — a déjà honeypot + rate limit.
3. `sapi_ajax_guide_contact()` (AJAX) → mail `[Robin Conseiller] Message de …` — a honeypot mais **PAS de rate limit** (à ajouter).

**À faire :**

**1. Time-trap (piège temporel) signé — sur les 3 formulaires.**
Un bot poste en < 1 s, un humain jamais. Rejeter toute soumission trop rapide.
- Générer au **rendu** du formulaire un timestamp signé côté serveur pour éviter qu'un bot n'envoie simplement `time()` courant : `$ts = time();` + `$sig = hash_hmac('sha256', $ts, wp_salt('auth'));`. Transmettre `sapi_ts` + `sapi_tsig` (deux champs cachés pour `page-contact.php`, deux valeurs ajoutées aux données localisées `wp_localize_script` pour les 2 formulaires AJAX, renvoyées dans le POST).
- À la soumission : vérifier que `$sig` correspond (rejet silencieux sinon), puis que `time() - $ts` est **≥ 3 s** ET **≤ 3600 s**. Hors bornes → rejet silencieux (`wp_send_json_error(['message' => 'Spam'])` côté AJAX, ou traité comme le honeypot côté page-contact). Le seuil de 3 s est prudent : le questionnaire du Conseiller et la page contact prennent bien plus longtemps à remplir. Choisir un `$ts` cohérent pour l'AJAX = l'instant de chargement de page (dwell time naturel avant envoi).

**2. Filtre anti-junk — sur les 3 formulaires (rester CONSERVATEUR, ne jamais bloquer un vrai client).**
Après sanitize, rejeter (silencieusement, comme un spam) si :
- le domaine de l'email est un domaine de test/réservé : `example.com`, `example.org`, `example.net`, `test.com` (liste en dur, facile à étendre).
- le message est **vide, purement numérique** (`/^\s*\d+\s*$/`) ou **plus court que 5 caractères** hors espaces. (Un vrai message de projet fait toujours plus.)
- ⚠️ NE PAS filtrer sur la présence d'URL ni sur des mots-clés : un client légitime peut coller un lien Pinterest ou un nom de modèle. On veut zéro faux positif.

**3. Rate limit manquant sur le Conseiller.**
Ajouter dans `sapi_ajax_guide_contact()` (après le honeypot) l'appel déjà utilisé ailleurs :
`if (!sapi_check_form_rate_limit('guide_contact')) { wp_send_json_error(['message' => 'Trop de messages envoyés. Réessayez plus tard.']); return; }`

**Critères de succès :**
- Une soumission normale (humain, > 3 s, message réel, email valide) passe sur les 3 formulaires.
- Une soumission instantanée (< 3 s) est rejetée.
- Un email `@example.com` ou un message `555` / `20` est rejeté.
- Le Conseiller a maintenant un rate limit actif.
- Le honeypot et les nonces existants restent en place (on ajoute, on ne retire rien).
- Rejets **silencieux** côté bot (pas d'indice exploitable), messages d'erreur clairs seulement pour les vrais cas (email invalide, etc.).
- Console 0 erreur ; aucun impact sur la home ni le catalogue.

### 📐 ÉTAT DES LIEUX + PLAN CLAUDE CODE (2026-08-06) — EN ATTENTE décision périmètre (Robin demande à Cowork)

**Rien codé.** Audit de `functions.php` : les appels WC dangereux sont dans des **callbacks sur hooks toujours joués** (piège : `is_product()`/`is_shop()`/`is_cart()`/`WC()` sont elles-mêmes des fonctions WC → un `if (is_product())` non gardé plante aussi).

**🔴 A — 6 points FATALS (front + admin écran blanc sans WC) :**
| # | Fonction | Hook | Ligne |
|---|---|---|---|
| A1 | `sapi_maison_structured_data` | wp_head | 1618 (+ WC_Shipping_Zones) |
| A2 | `sapi_maison_open_graph` | wp_head | 1681 |
| A3 | `sapi_maison_meta_description` | wp_head | 1724/1736 (elseif) |
| A4 | filtre `document_title_parts` | `<title>` de CHAQUE page | 2237 |
| A5 | `sapi_render_mini_cart_contents` | header.php:121 (toutes pages) | 1958 — garde header.php **inefficace** |
| A6 | callback `admin_init` (checkout Elementor) | admin_init | 1500 — **déclencheur exact de l'incident** |

**🟠 B — risque moindre (ne plante que si appelé) :** endpoint REST public `sapi/v1/products/search` (l.5894, atteignable publiquement) ; 3 handlers AJAX panier (add_to_cart/buy_now/update_mini_cart_qty) ; recently-viewed (l.598, déjà protégé de fait par `is_singular('product')`).

**🟢 C — déjà gardés (~13 blocs) :** enqueue assets, footer cross-sells, cart count, fragments, body_class, modale conseiller, coupon welcome, endpoint order-received. On n'y touche pas.

**PLAN :** gardes **additives** (early-return `class_exists('WooCommerce')`/`function_exists()` ou `class_exists &&`), **zéro changement de comportement quand WC est actif**. A6→garde ; A1/A2→garde en tête ; A3→`class_exists &&` sur 2 elseif ; A4→garde en tête du filtre ; A5→early-return propre (mini-panier vide neutre) au lieu de la garde inefficace.

**❓ DÉCISION À TRANCHER (Robin ↔ Cowork) :** périmètre **A seul** (les 6 fatals) OU **A + B** (recommandé : + endpoint REST public + 3 AJAX panier + recently-viewed explicite → thème 100% résilient). Sur **go** → je code par sous-étapes sur `test-theme-sapi-maison`, puis test de résilience (désactiver WC sur test) + smoke-test, puis cherry-pick sélectif vers master.

### ✅ FAIT SUR TEST (2026-08-06) — périmètre A+B validé par Robin, 6 commits atomiques
- `6bf268d` A6 : garde `wc_get_page_id` sur callback admin_init (le déclencheur).
- `7822ca3` A1+A2 : garde `class_exists('WooCommerce')` en tête de `structured_data` + `open_graph` (wp_head).
- `5456d5a` A3 : `class_exists &&` sur les elseif `is_product_category`/`is_shop` de `meta_description` (homepage reste couverte).
- `06085cd` A4 : garde en tête du filtre `document_title_parts` (joué sur chaque `<title>`).
- `ad75dcf` A5 : early-return neutre dans `sapi_render_mini_cart_contents` (la garde de header.php `function_exists('sapi_render_mini_cart_contents')` était toujours vraie → inefficace ; mini-panier vide sans lien shop si WC absent).
- `27612a1` B : endpoint REST public `sapi_product_search` + 3 handlers AJAX panier (add_to_cart/buy_now/update_mini_cart_qty, erreur gracieuse) + garde explicite recently-viewed.

Gardes **additives** (early-return / `class_exists &&`) → **zéro changement quand WC actif**. Accolades équilibrées. Smoke-test WC actif : pages 200, 0 erreur PHP.

**⚠️ RESTE (Robin) : TEST DE RÉSILIENCE.** Robin désactive WooCommerce sur test → doit constater : **front + /wp-admin accessibles** (mode dégradé, sans blocs boutique), **plus aucun `undefined function` du thème dans debug.log**. Puis réactive WC → tout identique.

**Décision Robin : périmètre = functions.php uniquement** (les templates `woocommerce/*` ne rendent que si WC est actif → risque assumé).

**✅ POUSSÉ EN PROD (master) le 2026-08-06 — cherry-pick sélectif des 6 commits (functions.php uniquement, fast-forward). Déployé + audit prod OK (pages 200, 0 erreur PHP, mini-panier + endpoint REST OK).**
- Branche locale `prod-wc-hardening` = `origin/master` + les 6 commits de blindage, cherry-pick **propre** (0 conflit).
- Diff vs master vérifié = **functions.php uniquement**, 27 ajouts / 3 suppr., **que des gardes additives** (aucune autre modif de test). Accolades équilibrées.
- Recette pour recréer si besoin : `git checkout -B prod-wc-hardening origin/master && git cherry-pick 6bf268d 7822ca3 5456d5a 06085cd ad75dcf 27612a1`.
- **Sur go Robin** → `git push origin prod-wc-hardening:master` (fast-forward) puis Robin lance le workflow prod. **Rien ne part sans son go.**

---

**Note :** si le spam persiste après déploiement prod malgré cette couche, l'étape suivante est Cloudflare Turnstile (captcha invisible, gratuit, non-Google, RGPD ok). Ne pas l'implémenter maintenant.

### ✅ RÉPONSE CLAUDE CODE — FAIT SUR TEST (2026-08-05, commit `68d1561`, en attente validation Robin)

**Les 3 couches sont en place, ajoutées au nonce + honeypot + rate limit existants (rien retiré).**

**Helpers ajoutés (functions.php, à côté de `sapi_check_form_rate_limit`) :**
- `sapi_time_trap_new()` → `['ts','sig']` (timestamp + HMAC-sha256 avec `wp_salt('auth')`), à générer au rendu.
- `sapi_time_trap_valid($ts,$sig,$min=3,$max=DAY_IN_SECONDS)` → `hash_equals` sur la signature + délai humain.
- `sapi_is_junk_contact($email,$message,$require_message=false)` → domaines test (example.com/.org/.net, test.com) + message numérique-seul / <5 car. ; **ne filtre jamais** sur URL ni mots-clés.

**Câblage par formulaire :**
- **page-contact.php** (POST, `fullname`/`email`/`message` tous requis) : time-trap (elseif après honeypot) + junk (`require_message=true`) + 2 champs cachés signés dans le form.
- **sapi_ajax_robin_contact()** (single-product, email requis / **message optionnel**) : time-trap + junk (`require_message=false`) + champs cachés dans le form + append JS (`sapi_ts`/`sapi_tsig`).
- **sapi_ajax_guide_contact()** : time-trap + **rate limit `guide_contact` (celui qui manquait)** + junk (`require_message=false`).

**⚠️ 2 points à connaître :**
1. **`sapi_ajax_guide_contact()` est ORPHELIN** : aucun JS ne l'appelle et son nonce `sapi-guide-contact` **n'est créé nulle part** (c'est le contact du quiz V1 retiré en Tâche 7) → il rejette déjà 100% des appels au nonce. J'y ai quand même mis les 3 couches (défense, sans le retirer, comme demandé), mais **il ne protège rien d'actif** aujourd'hui. Les 2 formulaires **réellement vivants** sont page-contact + robin_contact. Si vous voulez, on pourra le supprimer proprement plus tard.
2. **Borne haute du time-trap : 24 h (DAY_IN_SECONDS) et non 3600 s.** Raison = **zéro faux positif** (règle cardinale) : le `$ts` signé est rendu dans le HTML ; si la page /contact ou produit est servie en **cache** (LiteSpeed o2switch), un vrai client pourrait dépasser 1 h → rejeté à tort. Le nonce de ces mêmes formulaires tolère déjà ~12-24 h, on s'aligne. **Le vrai levier anti-bot reste la borne basse à 3 s** (inchangée). Si vous préférez 3600 s strict, c'est un seul paramètre à changer — dites-le.

**À tester par Robin sur test :**
- Envoi normal (humain, > 3 s, vrai message, email valide) → passe sur `/contact` et sur « Contacter Robin » (fiche produit).
- Email `@example.com` ou message `555`/`20` → rejeté (silencieux côté robin_contact = la fiche affiche quand même « envoyé » mais **aucun mail** ne part ; côté /contact = message « Spam détecté »).
- Honeypot + nonces toujours actifs ; home + catalogue inchangés ; console 0 erreur.
- ⚠️ Je n'ai **pas** de moyen de simuler un vrai bot ici — la validation « le spam s'arrête » se fera à l'usage après prod. Si ça persiste → Turnstile (réserve).

### 🔎 AUDIT COMPLET DES FORMULAIRES + NETTOYAGE (2026-08-05, commit `863fcc3`) — demandé par Robin

**Combien de questionnaires ?** UN seul actif : le **Robin Conseiller** (parcours à étapes, modale, boutique/fiches). L'ancien « quiz V1 » a été retiré (Tâche 7) ; ses handlers restaient en code mort (supprimés ci-dessous).

**Tous les formulaires visibles sont fonctionnels** (aucun cassé). Inventaire + anti-spam :
| Formulaire | Fonctionnel | Anti-spam |
|---|---|---|
| Contact `/contact` | ✅ | nonce+honeypot+rate limit + time-trap+junk |
| « Contacter Robin » (fiche produit) | ✅ | idem |
| « Échangeons ensemble » (modale Conseiller, `megafilter_surmesure`) | ✅ | nonce+honeypot+rate limit **+ time-trap+junk AJOUTÉS ce commit** |
| Sur-mesure `/sur-mesure` | ✅ | nonce+honeypot+rate limit **+ time-trap+junk AJOUTÉS (commit `d23b9bd`)** |
| Newsletter (home + popup cookie) | ✅ | nonce+honeypot+rate limit |
| Inspiration (email→Brevo, `/inspiration`) | ✅ | nonce (pas de honeypot/rate limit, mais email seul, pas de message libre — risque faible) |
| Recherches (404/search/room-pickers) | ✅ | GET WordPress, hors périmètre spam |

→ Découverte : le **contact de la modale Conseiller** était un **4ᵉ formulaire de contact vivant** non listé dans la tâche. Il est maintenant au même niveau que les autres.

**✅ CODE MORT SUPPRIMÉ (~473 lignes, 0 appelant, 0 référence, validé Robin)** : `sapi_ajax_guide_contact`, `sapi_ajax_guide_refine`, `sapi_ajax_conseils_products`, `sapi_ajax_robin_conseil_step`, `sapi_ajax_robin_filter_products` (+ leurs `add_action`). Helpers partagés **conservés** (dont `sapi_guide_pick_four`/`diversify_format`, gardés exprès). Vérifié : tous les `wp_ajax_*` restants pointent vers une fonction existante ; accolades équilibrées.

**Reste (optionnel, non fait)** : ajouter honeypot + rate limit au formulaire Inspiration (risque faible). À voir si Robin veut.

### ✅ POUSSÉ EN PROD (master) le 2026-08-05 — cherry-pick SÉLECTIF
Robin a validé les 4 formulaires sur test (les 4 mails de test reçus = zéro faux positif). **Go prod donné, uniquement les modifs formulaires.**
- Cherry-pick propre de **3 commits** sur master (par-dessus le catalogue) : `68d5af7` (anti-spam 3 formulaires) + `68f84ab` (contact modale + suppression 5 handlers morts) + `ed5b0de` (sur-mesure). Fast-forward.
- Diff master vérifié = **exactement 5 fichiers** (functions.php, page-contact.php, single-product.php, sapi-modal-conseiller.js, page-sur-mesure.php) — **aucun** autre commit de test (immersion, état B, Tâche 5, emails, mode vacances **restent sur test**).
- Contrôles : helpers présents, 5 handlers morts retirés, handlers vivants intacts, accolades équilibrées, tous les `wp_ajax_*` résolvent.
- **⚠️ RESTE À ROBIN : lancer le workflow GitHub Actions « Deploy to Production »** (manuel) pour mettre en ligne. Rien n'est déployé tant qu'il ne l'a pas lancé.

---

> **REFONTE FILTRAGE CONSEILLER — décisions d'architecture (11/06/2026).** Les tâches ci-dessous REMPLACENT les anciennes (qui supposaient un filtrage en double PHP/JS, désormais périmé).
> **Cap :** filtrage 100% côté serveur (PHP), un seul cerveau, **suppression du filtrage JavaScript** (le JS ne fait plus qu'afficher). Le filtre serveur est appelé à **2 moments** : (1) au chargement de `/mes-creations/` avec une pièce, (2) à la fermeture de la modale (questionnaire terminé OU abandonné en cours). L'IA (Sonnet) n'ajoute qu'un **commentaire** en fin de questionnaire.
> **Source de vérité du comportement voulu :** `assets/guide-filtrage-simulateur.html` (simulateur jouable + éditeur de règles, à ouvrir). Doc d'appui : `assets/guide-filtrage-impact.html`.
> **Toutes les tâches :** branche test uniquement, jamais master, Robin valide avant prod.

## [✅ FAIT — sur test] Immersion = via le room-picker (approche simple)
L'immersion s'active sur `?piece=` valide. En pratique ces URLs viennent du room-picker (cartes = liens `?piece=`). La **reprise auto** (qui ajoutait `?piece=` sans clic pour les revenants) a été **retirée** → un revenant arrive sur le room-picker. Pas de cookie (approche cookie abandonnée car sur-compliquée + souci cache prod). Seul compromis assumé : un lien `?piece=` partagé/favori affiche l'immersion (indistinguable d'un vrai clic). Aucun impact cache prod.

## [BUG ✅ CORRIGÉ — sur test] Le commentaire IA en fin de modale ne s'écrit jamais
Cause : le commentaire Sonnet (`sapi_megafilter_advice` → `advice_text`) était bien calculé, mais son lieu d'affichage (carte « Mon projet » + `sapi-cards-conseiller.js`) a été supprimé pendant la refonte (4b/7). **Fix** : il s'affiche désormais dans la **phrase de l'immersion** — la modale émet `sapi:advice-loading` (dès le début du calcul → loader 3 points qui remplace la phrase générique) puis `sapi:advice-ready` (texte → tapé à la machine, ou repli générique si vide). En attente de validation Robin.

## [TÂCHE 1] Architecture — filtrage serveur unique (fondation)
**Priorité : HAUTE — à faire en premier, les autres en dépendent.**
**⚠️ Demander un PLAN + un état des lieux AVANT de coder** : la branche test est en cours de refonte et instable. Plan validé par Robin, puis implémentation par sous-étapes.

Objectif : une seule fonction PHP qui, à partir des réponses (pièce, taille, sortie, hauteur, style…), renvoie la sélection filtrée + classée. Appelée à 2 moments :
- au **chargement** de `/mes-creations/?piece=X` → rendu serveur de la sélection au niveau pièce ;
- à la **fermeture de la modale** → AJAX avec les réponses données (partielles ou complètes ; endpoint `sapi_robin_filter_products` existe déjà sur master).

Retirer au JS (`sapi-cards-conseiller.js`) son rôle de moteur de filtrage : plus aucune règle métier dupliquée côté navigateur, il ne fait qu'afficher ce que le serveur renvoie.

Important pour la suite : faire en sorte que TOUTES les règles vivent dans **une config unique** (étendre `$sapi_filter_rules`) lue par ce filtre — c'est ce qui rendra possible la page admin (tâche 5).

**Critères de succès :** une seule logique de filtrage (PHP) ; le JS n'applique aucune règle ; les 2 points d'appel marchent ; fermer la modale en cours donne une sélection cohérente.

## 📐 RÉPONSE CLAUDE CODE — ÉTAT DES LIEUX + PLAN Tâche 1 (11/06, EN ATTENTE DU GO DE ROBIN)

### État des lieux du filtrage actuel (résumé)
Le filtrage est **hybride et les règles sont TRIPLIQUÉES** :
- **PHP** (functions.php) : `sapi_guide_get_categories` (cats par sortie + cuisine), `sapi_guide_get_ampoule_filter` (ampoule par pièce), `sapi_guide_query_products` (format vertical/horizontal + ampoule, WP_Query), `sapi_guide_collect_results` (variation essence/taille + images), `sapi_guide_pick_four` (les 4 retenus + `diversify_format`). **Règles EN DUR**, ne lisent PAS `$sapi_filter_rules`.
- **`$sapi_filter_rules`** (l.325) : config… **lue UNIQUEMENT par le JS** (localisée), pas par le PHP.
- **JS** (`sapi-cards-conseiller.js`) : `getAcceptedCategories`/`getAmpouleFilter`/`isVerticalAllowed`/`cardMatchesAnswers`/`computeEffectiveAnswers` (élargissement progressif) + `window.sapiMegaFilter` → `shop.js applyFilters` filtre la grille **dans le navigateur**. C'est ce qu'il faut supprimer.
- **2 endpoints AJAX** : `sapi_ajax_guide_results` (parcours complet → produits + texte IA Sonnet) et `sapi_ajax_robin_filter_products` (renvoie juste des IDs filtrés — **existe mais n'est appelé par aucun JS aujourd'hui** → c'est le candidat pour « moment 2, fermeture modale »). Aujourd'hui la modale, à la fermeture, **re-filtre en JS** (`sapiShopRefilter`), pas via cet endpoint.
- **Manque côté PHP vs simulateur** : la **couche PRIORITÉ/classement** (rang ampoule/catégorie/format + ordre d'importance + souple/strict) n'existe PAS en PHP — c'est la grande nouveauté. L'**élargissement progressif** n'existe qu'en JS.
- **Déjà aligné** : le **hero immersif état B que je viens de faire utilise DÉJÀ le filtrage serveur** (`sapi_guide_query_products`) au chargement = moment 1. Et j'ai déjà **neutralisé `sapiMegaFilter` en mode immersion** (catalogue laissé au serveur). Donc /mes-creations/ en état B est déjà à moitié sur le nouveau modèle.

### Plan Tâche 1 — moteur serveur unique (sous-étapes livrables sur test)
1. **Config unique** : étendre `$sapi_filter_rules` pour contenir TOUTE la config du simulateur (objet `C`) : cats par sortie (+ secondaire), ampoule par pièce **+ préférée**, format préféré par pièce, `cuisineRemove`, `grandeSkipAmpoule`, règles vertical/horizontal, **catégorie prioritaire par sortie**, **ordre d'importance** [catégorie>ampoule>format], prio on/off, mode souple/strict, style→essence, map escalier, grandeExclut2Tailles. En PHP d'abord, **structurée pour basculer en option WordPress (DB) en Tâche 5**. Devient la **seule source**, lue par PHP ET (le strict minimum) par le JS d'affichage.
2. **Moteur PHP unique** `sapi_conseiller_filter($answers)` qui reproduit EXACTEMENT le pipeline du simulateur : normalise (escalier→taille) → catégories (lit la config) → filtre dur (catégorie + format + ampoule) sur le catalogue → **classement priorité** (rang→score lexicographique selon l'ordre d'importance, souple/strict) → renvoie **la sélection classée** (slider immersion) **+ les 4 picks** (modale via `pick_four`). Refactorer `get_categories`/`get_ampoule_filter`/`query_products`/`collect_results` pour **lire la config** (plus de règles en dur) + ajouter la couche priorité.
3. **Brancher les 2 appels** : (a) **chargement** immersion → remplacer l'appel actuel par le moteur (sélection classée) ; (b) **fermeture modale** → faire que `sapi_ajax_robin_filter_products` appelle le moteur et renvoie la sélection ordonnée ; brancher `sapi-modal-conseiller.js` (fermeture terminée OU abandonnée) pour appeler cet endpoint et **mettre à jour la sélection** ; le JS ne fait QUE rendre.
4. **Couper le filtrage JS** : retirer `getAcceptedCategories`/`getAmpouleFilter`/`cardMatchesAnswers`/`computeEffectiveAnswers`/`sapiMegaFilter` de `sapi-cards-conseiller.js` et la dépendance de `shop.js` ; le JS masque/affiche selon les **IDs renvoyés par le serveur**.
5. **Vérifs** : sélection immersion == simulateur ; fermeture modale == simulateur ; zéro règle JS résiduelle ; catalogue intact ; console 0 erreur.

### Questions à trancher AVANT que je code
1. **Élargissement progressif vs mode souple** : le simulateur « souple » ne fait que CLASSER (n'exclut jamais), mais ne relâche pas les **filtres durs** (catégorie/ampoule/format) si 0 produit. On **garde** un repli qui relâche les filtres durs quand 0 résultat (comme l'actuel `computeEffectiveAnswers` JS + les fallbacks de `query_products`), ou on accepte « 0 résultat → carte sur-mesure » ? (Reco : garder un repli serveur léger.)
2. **Config en DB tout de suite ou en dur d'abord ?** Reco : array PHP en Tâche 1, basculé en option WordPress en Tâche 5 (sinon on code deux fois).
3. **Sortie du moteur** : pour l'immersion = liste classée complète ; pour la modale = les 4 picks. On renvoie les deux ? (le simulateur produit les deux). 
4. **⚠️ Branche test « instable »** : la note dit que la branche test est en cours de refonte. **Mon état B immersion EST-il cette refonte, ou une autre fenêtre Claude bosse en parallèle sur le filtrage ?** À confirmer pour ne pas se marcher dessus (cf. [[feedback_multi_claude_coordination]]).

### 👉 Action Robin
Valider/ajuster ce plan + répondre aux 4 questions. Sur « go » → je code la Tâche 1 par sous-étapes (1→5), push test, validation à chaque étape. Puis Tâches 2 (règles), 3 (priorités), 4 (room-picker), 5 (admin), 6-7 (IA + nettoyage).

### ✅ Avancement (validé Robin sur test)
- **T1 moteur FONCTIONNELLEMENT COMPLET** : config unique `$sapi_filter_rules` (toute la config du simulateur) ; `sapi_conseiller_rank_products()` (couche priorité, mécanique du simulateur) ; filtre dur PHP (`get_categories`/`get_ampoule_filter`/`query_products`/`collect_results`) **lit la config** (plus de règles en dur) ; **moment 1** (chargement immersion) + **moment 2** (fermeture modale → endpoint `sapi_ajax_immersion_selection` → re-filtre+classe serveur → remplace le slider) **OK terminé ET abandonné**. Markup card = source unique (`sapi_immersion_render_product_card`). Commits `bdeaae2`, `cd20893`, `8c799ac`.
- **⏳ Reste T1 « supprimer le filtrage JS »** : COUPLÉ à la Tâche 4 (le filtre JS `sapi-cards-conseiller.js` ne sert plus qu'à l'**état A** sans `?piece=`). À supprimer **avec** le room-picker (Tâche 4) qui remplace l'état A. Ne pas le retirer avant, sinon l'état A casse.
- **🔧 À LISSER PLUS TARD (demandé Robin)** : la mise à jour du slider au moment 2 **flashe** (remplacement sec des cards) → faire une **transition douce** (fade-out/fade-in) au lieu d'un swap brutal.
- **✅ MOMENT 2 FIABILISÉ + VALIDÉ** : la modale émet `sapi:conseiller-closed` (réponses finales) à **chaque** fermeture (fin + abandon) ; l'immersion l'écoute (on n'écoute plus le `subscribe` sapiProject, dont le notify dépendait du flush `pendingNotify` du resume → « ne se recharge pas tout le temps »). Baseline de dédup = sélection serveur (pièce seule) ; signature brûlée seulement si succès AJAX. **Changement de pièce** (projet recommencé) → **rechargement page** `?piece=<nouvelle>` (décor + sélection cohérents) ; même pièce + affinages → AJAX slider seul.
- **✅ T2 (règles) FAIT + VALIDÉ** : vertical autorisé dès plafond haut, cuisine retire lampe à poser ET lampadaire, **question « table » supprimée** du parcours (colonne analytics `table_reponse` conservée). Commits `04a02df`, `0f6b477`.
- **✅ T3 (priorités) de fait FAIT** : le classement par priorité (mécanique du simulateur) est en place et **s'applique réellement** depuis le fix config.
- **⚠️ PIÈGE MAJEUR corrigé (commit `e1704ab`)** : `$sapi_filter_rules` était une **variable LOCALE** de la fonction d'enqueue → `global $sapi_filter_rules` renvoyait vide → tout le filtre PHP utilisait les **valeurs de repli (anciennes règles)** : priorité no-op, `cuisine_remove`/`vertical_haute` jamais lus. **Symptôme trompeur : "rien de cassé" = en fait "rien ne s'applique".** Fix : config dans une **fonction** `sapi_conseiller_get_rules()` lue partout (enqueue + filtre + endpoint), zéro global. **RÈGLE : une config partagée PHP doit vivre dans une fonction, jamais en variable locale d'enqueue.**

## [TÂCHE 2] Règles de filtrage (dans le filtre serveur)
**Priorité : HAUTE — après tâche 1.** Comportement détaillé : voir le simulateur (`guide-filtrage-simulateur.html`).

Appliquer dans le filtre serveur :
- **Vertical** : autorisé dès `hauteur === 'haute'` (toutes pièces, toutes tailles). `confortable` garde la règle actuelle (entrée ou petite pièce). Escalier : vertical OK, horizontal exclu. Horizontal exclu en petite pièce + plafond haut. Boule toujours autorisée.
- **Supprimer la question « table »** du parcours (aucun effet sur la sélection, vérifié). Retirer l'étape de `inc/guide-data.php`, les références de libellés/`valid_keys` dans `functions.php`, et les références dans les JS de la modale. **GARDER** la colonne analytics `table_reponse` (historique) — juste arrêter de l'alimenter.
- **Cuisine : retirer lampes à poser ET lampadaires** des catégories (généralise l'actuel « pas de lampe à poser » en cuisine).

**Critères de succès :** comportement identique au simulateur (sections « Hauteur et format », « Taille », « Pièce »).

## [TÂCHE 3] Priorités — couche de préférence (dans le filtre serveur)
**Priorité : normale — après tâches 1-2.** Source de vérité : simulateur (sections Priorité, Pièce, Où installer).

La préférence ne fait que **CLASSER**, elle n'exclut jamais. Chaque produit reçoit un rang par critère, combinés en un score, **mode souple** (préférés en tête, on complète avec les autres si trop peu).
- **Ampoule** (par pièce) : chaleureux (salon/chambre/chambre-enfant/entrée) → préféré `ampoule_entouree` ; travail (cuisine/bureau) → `ampoule_degagee`. Map explicite (ne pas se reposer sur l'ordre d'un tableau).
- **Catégorie** (par sortie) : une catégorie prioritaire optionnelle par sortie (surtout utile pour « je ne sais pas » → les 4 catégories).
- **Format** (par pièce) : un format préféré optionnel par pièce (boule / horizontal / vertical).
- **Ordre d'importance** réglable des 3 critères ; défaut : **catégorie > ampoule > format**.

Mécanisme : un `priority_rank` (0/1) par critère → score lexicographique selon l'ordre d'importance → tri stable de la sélection ; en souple, compléter avec les rangs suivants.

**Critères de succès :** reproduire le simulateur (tester salon plafond haut, cuisine, sortie « je ne sais pas »).

## [TÂCHE 4] Room-picker sur /mes-creations/ pour l'arrivée sans projet
**Priorité : normale.** **Demander un plan + un mockup avant de coder.**

Mettre un room-picker sur `/mes-creations/` pour le visiteur qui arrive sans pièce. Choisir une pièce → charge la page avec cette pièce → déclenche l'appel filtre serveur (tâche 1, moment 1) → sélection au niveau pièce. Chaque arrivée passe ainsi par le même chemin serveur.

**Précision Robin (11/06) — gating de l'immersion :** l'expérience immersion (plein écran) ne doit s'afficher QUE si on arrive **depuis un room-picker** (donc avec une pièce). Si on arrive autrement sur `/mes-creations/` (sans pièce), afficher le **room-picker vierge**, PAS l'immersion (sinon c'est trop envahissant).

**Critères de succès :** arrivée sans pièce → room-picker vierge (pas d'immersion) ; arrivée avec pièce depuis un room-picker → immersion + sélection serveur au niveau pièce ; aucune régression depuis la home.

### 📐 PLAN Tâche 4 (11/06 — décisions Robin prises, EN ATTENTE DU GO)
**Décisions Robin :** (1) le room-picker **EST le hero** de l'état A ; (2) revenant avec projet sauvegardé → **reprise AUTO** : redirection directe vers `?piece=<pièce>` au chargement (pas de bande, pas de choix) ; (3) on **garde** le champ texte libre. Pour repartir de zéro : « Décrire mon projet » (change la pièce, recharge). Catalogue bare toujours atteignable car présent sous le hero immersif.
**Maquette :** `mockups/mes-creations-room-picker-etatA-v1.html` (toggle nouveau/revenant en haut).
**Sous-étape 4a — état A serveur (la feature visible) :**
- Dans `archive-product.php`, quand `$imm_piece === ''` : remplacer le hero artisan + les cartes conseiller cachées (`.conseiller-card--conseil/--mon-projet`, pilotées en JS) par un **room-picker serveur** identique à la home (signature Robin + titre + 7 cartes `<a href="?piece=<slug>">` + « ou » + freetext). **Factoriser** `$room_choices`/`$room_icons` de `front-page.php` dans des helpers partagés (`sapi_room_choices()`/`sapi_room_icon_svg()`), pas de copier-coller.
- Catalogue complet conservé dessous (inchangé).
- Reprise auto : **script inline dans `wp_head`** (uniquement état A `/mes-creations/` sans pièce) qui lit `localStorage['sapiProject']`, et si `answers.piece` est dans la whitelist → `location.replace('?piece='+piece)` **avant le paint** (zéro flash). Sinon le room-picker s'affiche. `STORAGE_KEY='sapiProject'`.
- Freetext : garder le comportement home (`?freetext=` → auto-ouvre la modale chat). **Vérifier** que l'auto-ouverture ne dépend pas de `sapi-cards-conseiller.js` (sinon la déplacer avant la coupe JS).
**✅ 4a + 4b CODÉS + SUR TEST (en attente validation Robin).**
- 4a : room-picker serveur en hero de l'état A + reprise auto.
- 4b : `assets/sapi-cards-conseiller.js` **SUPPRIMÉ** (tout le moteur de filtrage JS). Filtrage 100% serveur. Config `rules` plus exposée au JS. Conseils génériques par pièce déplacés sur `sapi-modal-conseiller` (global `SAPI_CARDS_CONSEILLER` conservé, clés genericAdvice+fallbackAdvice). Dépendance modale → `['sapi-project']`. **→ Termine la Tâche 1 step 4 (« supprimer le filtrage JS »).**
- Dead markup restant : `.conseiller-cards-zone` (branche état B, caché CSS, inerte) → à nettoyer en **Tâche 7**.
**Sous-étape 4b — couper le filtrage JS (= fin Tâche 1 step 4) :**
- Retirer de `sapi-cards-conseiller.js` : `getAcceptedCategories`/`getAmpouleFilter`/`cardMatchesAnswers`/`computeEffectiveAnswers`/`sapiMegaFilter` + la dépendance `shop.js applyFilters`. Le catalogue état A reste **complet** (plus de filtrage navigateur).
- ⚠️ `sapi-cards-conseiller.js` fait aussi : délégation `data-action="open-modal"`, ouverture modale depuis room-cards/forms, peuplement carte « Mon projet ». Vérifier ce qui reste utilisé (la modale écoute `sapi:open-modal` elle-même → découplée). Garder un handler minimal « clic → dispatch sapi:open-modal » si encore nécessaire ailleurs.
**Vérifs :** sans pièce → room-picker (carte=lien) ; clic → `?piece=` → immersion ; catalogue intact ; revenant → bande « Reprendre » ; freetext → modale ; **home inchangée** ; console 0 erreur ; zéro règle de filtrage JS résiduelle.

## [TÂCHE 5] Page admin WordPress — piloter les règles de filtrage (proposition 2)
**Priorité : normale — APRÈS tâches 1-3 (le filtre serveur et ses règles doivent exister et être centralisés).**
**Prérequis :** toutes les règles dans une config unique persistable (cf. tâche 1). Aujourd'hui certaines sont en dur → il faut d'abord les rassembler et les stocker (options WordPress / DB) pour qu'une page puisse les éditer.

Objectif : une page dans l'admin WordPress (comme le dashboard de stats du Conseiller) où Robin édite lui-même les règles, et ça s'applique au site en direct, sans repasser par du code :
- catégories par sortie ; ampoules acceptées + préférée par pièce ; règles de format ; priorités (ampoule/catégorie/format) + ordre d'importance ; exclusions par pièce (cuisine).
- Le filtre serveur (tâche 1) lit ces réglages depuis la DB au lieu de valeurs en dur.

**Le simulateur `assets/guide-filtrage-simulateur.html` EST la maquette de cette page** (mêmes réglages, même organisation) → s'en servir comme cahier des charges UI.

**Demander un plan avant de coder.** Soigner : ne pas laisser créer des combinaisons incohérentes (ex. retirer la catégorie qu'impose une sortie). Prévoir un « réinitialiser aux valeurs par défaut ».

**Critères de succès :** Robin modifie une règle dans l'admin → effet immédiat sur le filtrage du site, sans toucher au code.

### 📐 PLAN Tâche 5 (11/06 — décisions Robin : TOUT éditable + APERÇU LIVE intégré ; EN ATTENTE DU GO)
**Principe clé :** l'aperçu live n'embarque PAS de moteur JS (on vient de le supprimer du front, pas question de recréer 2 cerveaux). L'aperçu appelle le **vrai moteur PHP** avec les règles en cours d'édition, injectées via un hook `apply_filters('sapi_conseiller_rules', …)` le temps de la requête. Garantit zéro divergence aperçu/prod.
**Découpage (sous-étapes livrables sur test) :**
1. **5.1 — Socle config en DB (sans changement de comportement) :** renommer l'array actuel en `sapi_conseiller_default_rules()` ; `sapi_conseiller_get_rules()` = deep-merge de `get_option('sapi_conseiller_rules', [])` PAR-DESSUS les défauts, puis `apply_filters('sapi_conseiller_rules', $merged)`. Option vide → 100% défauts → comportement identique.
2. **5.2 — Page admin (menu + affichage) :** page sous le menu Conseiller (cap `manage_options`), formulaire pré-rempli reproduisant les sections du simulateur : ampoule_by_piece, ampoule_skip_when_grande, cats_by_sortie (+ secondaire), cuisine_remove/exclusions, prefs (ampoule/format/cat), prio+importance+mode, règles format booléennes, grande_exclut_2_tailles, style_essence, escalier_map. Listes de slugs valides = source unique (catégories WooCommerce, ampoules/formats/sorties/pièces depuis guide-data).
3. **5.3 — Sauvegarde + garde-fous :** POST nonce+cap, sanitization stricte (chaque slug validé contre sa whitelist), garde-fous anti-incohérence (ex. ne pas retirer la catégorie imposée par une sortie), bouton « Réinitialiser aux défauts » (supprime l'option).
4. **5.4 — Aperçu live (server-side) :** endpoint AJAX `sapi_admin_filter_preview` : reçoit l'état NON SAUVEGARDÉ du formulaire + des réponses (pièce + sortie/taille/etc.), pose le filtre `sapi_conseiller_rules` = règles draft, lance get_categories+query_products+rank, renvoie la sélection classée (vignettes+noms, + rang/score en debug). UI admin façon simulateur (pickers réponses → sélection).
**Vérifs :** option absente → défauts (diff nul) ; éditer une règle → effet immédiat sur immersion + moment 2 ; aperçu live == site ; reset OK ; sanitization rejette un slug inconnu ; 0 combinaison incohérente sauvegardable.

**✅ TÂCHE 5 FAITE + VALIDÉE SUR TEST (aperçu live OK, édition OK, pas de corruption).** Fichier `inc/conseiller-rules-admin.php` (require sous `is_admin()`).
- ⚠️ **PIÈGE serveur (O2switch/WAF)** : des cases à cocher multiples avec `name="...[]"` (même nom) sont **fusionnées → 1 seule valeur conservée**. Solution : **nom UNIQUE par case** `rules[map][ligne][option]=1`, sélection = clés cochées. À réutiliser pour tout futur formulaire admin avec cases multiples.
- ⚠️ Clé de ligne `''` (sortie par défaut) → `rules[map][][]` lue comme index numérique : encoder en sentinel `__empty`.
- Sous-menu enregistré en **priorité 11** (après le menu parent). Reset = `delete_option('sapi_conseiller_rules')`. 5.1 socle DB (sapi_conseiller_default_rules + get_rules merge option + apply_filters) ; 5.2/5.3 page sous-menu « Règles de filtrage » (priorité 11), formulaire schema-driven, sauvegarde admin-post (nonce+cap), sanitization whitelist, garde-fous, reset ; 5.4 aperçu live via endpoint `sapi_admin_filter_preview` (règles draft injectées par filtre → vrai moteur). Libellés clarifiés (éclairage principal/appoint). Pas de binaire PHP local → vérifié par équilibrage accolades ; blast radius = admin seul.

## [TÂCHE 6] Règle IA — suspension principale en grande pièce
**✅ FAIT (sur test)** — règle « RÈGLES SUSPENSION PRINCIPALE EN GRANDE PIÈCE » ajoutée dans `assets/guide-prompt-regles.txt`.
**Priorité : basse.** Éditer `assets/guide-prompt-regles.txt` : ajouter une règle pour que, quand une suspension est proposée comme éclairage **principal** dans une **grande pièce**, l'IA avertisse honnêtement qu'un seul luminaire peut ne pas suffire et suggère un complément (lampadaire, applique) ou un ensemble sur-mesure. (Le savoir + l'exemple existent déjà mais restent suggestifs ; une règle explicite rend l'avertissement fiable.)

## [TÂCHE 7] Nettoyage legacy (quand le nouveau flux est en prod)
**✅ FAIT (cœur) SUR TEST :**
- Quiz V1 mort retiré : `sapi_ajax_guide_results` (+ 2 add_action) + `sapi_guide_build_system_prompt` (−198 l). Gardés : nonce `sapi-guide-results` (partagé), `sapi_guide_check_rate_limit` (partagé), `sapi_guide_pick_four` + code `diversify_format` (grappe).
- Dead markup `.conseiller-cards-zone` retiré de l'état B d'archive-product.php (−123 l), hero artisan conservé.
- Filtrage JS déjà supprimé en 4b.
**⏸️ RESTE (optionnel, inoffensif — non fait par prudence) :**
- Dictionnaires `'table'` résiduels dans des fonctions LIVE (prompts IA `sapi_megafilter_build_freetext_prompt`, KEY_LABELS, export admin) — clés jamais lues depuis le retrait de la question, mais éditer des prompts IA sans pouvoir tester la sortie = risque non justifié. À faire si Robin veut, en testant l'IA.
- Helpers devenus orphelins par le retrait du quiz V1 : `sapi_guide_build_filter_context`, `sapi_guide_call_claude` (vs `_refine` utilisé). Morts mais inoffensifs.
- CSS mort : règles `.conseiller-cards-zone` / `.mes-creations-section-divider` (éléments retirés). Inoffensif.
**Priorité : basse, en dernier.** Une fois la refonte stable et validée : retirer le quiz V1 mort (`sapi_ajax_guide_results` + `sapi_guide_build_system_prompt`, plus appelé par aucun JS) et le filtrage JS s'il est entièrement remplacé. À faire prudemment, en vérifiant qu'aucun appel ne subsiste.
**⚠️ NE PAS supprimer le code « grappe »** (`diversify_format` dans `sapi_guide_pick_four`) : il est orphelin mais c'est une idée à conserver et à réactiver plus tard — voir l'idée ci-dessous.

## [IDÉE — à explorer plus tard] Grappe / multi-ampoules comme rampe vers le sur-mesure
Le mode « grappe » (montrer un produit de chaque format = de la diversité, + afficher la carte sur-mesure) est aujourd'hui orphelin (l'option a été retirée du questionnaire). Robin veut le **garder** : il doit servir de **support pour orienter vite le visiteur vers le sur-mesure**. L'objectif : que l'IA / le parcours puisse proposer rapidement une composition multi-ampoules ou un ensemble sur-mesure quand c'est pertinent, et le mécanisme « un de chaque format » est un bon véhicule pour donner à imaginer. À recâbler dans le nouveau système (porte d'entrée à définir) le moment venu. Le code `diversify_format` dans `sapi_guide_pick_four` est conservé exprès pour ça.

---

## [✅ FAIT — 2026-07-16] Charte — pousser la refonte du logo et des polices
**Date :** 2026-07-16
**Priorité :** normale

### ✅ Résultat Claude Code (2026-07-16)
- **Vérif avant commit** : `git status` propre — le `.ai` est bien dans `_local/` (gitignoré), rien d'interdit (`_local/`, `.DS_Store`, `.ai`, `charte-en-ligne.zip`) stagé. ✅
- **Commit** `dea72a2` « Logo régénéré depuis le vectoriel + zone de protection + Montserrat », `git push origin main` → OK.
- **Déploiement GHA** confirmé (fichier live re-daté ~30 s après le push). Critères testés :
  - `/` → 200, **identique** à `index.html` poussé ✅
  - `download/logo/logo-sapi-noir-zone.svg` → 200 (`image/svg+xml`) ✅
  - `download/logo-sapi.zip` → 200 (356 Ko) ✅
  - `download/polices-sapi.zip` → 200 (492 Ko) ✅
  - `download/square-peg.zip` → **404** (renommé, bien supprimé par la synchro FTP) ✅
  - `_local/` → **404** ✅
  - anciens PNG (`logo-sapi-noir-1000.png`) → **404** (supprimés), nouveaux PNG 1200 → 200 ✅
- **🔒 SSL** : l'AutoSSL O2switch est **désormais émis** → `https://charte.atelier-sapi.fr` répond en 200 avec **certificat valide** (le point en attente de la tâche précédente est résolu).

<details><summary>Consigne d'origine (archivée)</summary>

**⚠️ Dépôt séparé, PAS le thème :** `~/Atelier Sapi Claude Cowork/business/docs/charte-graphique/`
(dépôt `robinSapi/charte-atelier-sapi`). Ne rien toucher dans `theme-atelier-sapi`.

**Contexte :** Robin a validé. Tout est écrit et vérifié côté fichiers, il ne reste qu'à
committer et pousser. Le workflow GHA déploie tout seul sur `charte.atelier-sapi.fr`.

**Ce qui change :**
- Logo entièrement régénéré depuis le master vectoriel (`.ai` converti en SVG).
  4 déclinaisons (noir, bois, gris, creme) x (SVG nu + SVG avec zone + PNG 1200/600/300).
- Zone de protection : carré = 2x le diamètre du disque, disque centré, marge D/2.
  Ratio vérifié à 0,500 sur les 5 images.
- Montserrat ajoutée au pack (4 TTF : Light, Regular, Bold, Black).
- Anciens PNG au lettrage évidé supprimés, filtres CSS retirés de la page.
- Section Logo : les cartes n'ont plus de padding, elles SONT le carré de protection.

**À faire :**
1. `cd business/docs/charte-graphique/`
2. `git status` — vérifier qu'il n'y a NI `_local/`, NI `.DS_Store`, NI `.ai`, NI `charte-en-ligne.zip`.
   (contrôlé côté Cowork : c'est propre, mais revérifier avant de committer)
3. `git add -A`
4. Commit : « Logo régénéré depuis le vectoriel + zone de protection + Montserrat »
5. `git push origin main`
6. Suivre le run Actions. En cas d'échec, rapporter le log ici sans retenter en boucle.

**Critères de succès :**
- `https://charte.atelier-sapi.fr` à jour : section Logo avec 6 cartes à l'échelle
- `download/logo/logo-sapi-noir-zone.svg` → 200
- `download/logo-sapi.zip` et `download/polices-sapi.zip` → 200
- `download/square-peg.zip` → **404 attendu** (renommé en polices-sapi.zip)
- `/_local/` → toujours 404

**Rappel :** `main` de ce dépôt est directement en ligne. Le push publie.

</details>

---

## [✅ FAIT — 2026-07-17] Charte — pousser en prod : photos, survol, carrousel, palette

**Date :** 2026-07-17
**Priorité :** normale

### ✅ Résultat Claude Code (2026-07-17)
**⚠️ Déployé en 2 commits — Cowork a enrichi les fichiers PENDANT mon travail** (ajout suppression Print + palette Affinity + README, après mon 1er commit). J'ai donc complété avec un 2e commit pour que le live corresponde à l'état final voulu. Le résultat en ligne est complet et correct.
- **Commit 1** `14bd66c` « Photos, doctrine de survol, carrousel de densité, dérivées en bande » (48 fichiers : photos, survol, carrousel, dérivées en bande, montserrat/square-peg séparés).
- **Commit 2** `88eb2be` « Retrait de la rubrique Print + palette Affinity (.clr) » (le delta arrivé après : `index.html` sans Print, `download/palette-sapi.clr`, `README.md`).
- **Vérif avant chaque commit** : `.DS_Store` (dans `download/fonts/`) et `.ai` (dans `_local/`) bien **ignorés**, absents du staging. Rien d'interdit poussé. ✅
- **Déploiement GHA** confirmé pour les 2 push (fichier live re-daté à chaque fois). Critères finaux testés (cert SSL valide) :
  - `/` → 200, **identique** à `index.html` final poussé ✅
  - `download/square-peg.zip` + `download/montserrat.zip` → **200** ✅
  - `download/polices-sapi.zip` → **404** (pack combiné retiré par la synchro FTP) ✅
  - `download/fonts/SquarePeg-Regular.woff2` → **404** ; `…SquarePeg-Regular.ttf` → 200 ✅
  - `download/palette-sapi.clr` → **200** (1670 o ; non matché par les exclusions `*.zip`, monte bien, pas besoin de toucher `deploy.yml`) ✅
  - `img/regle-zone.jpg` → 200 ; `img/regle-2-3.jpg` + `img/sapi-art.jpg` → **404** ✅
  - **Print retiré** : plus qu'**1** occurrence de « print » dans le HTML live = le garde-fou `localStorage` (attendu), aucun onglet Print ✅
  - `_local/` → **404** ✅
- **Contrôle assets** : tous les fichiers (img/ + download/) référencés dans le `index.html` live répondent **200** — aucun lien cassé.
- **⚠️ Rendu visuel non vérifié** (pas de navigateur dans le bac à sable, comme signalé par Cowork) : contrastes/largeurs/carrousel/survol + le garde-fou `sapi-charte-ctx=print` (page pas blanche) à contrôler à l'œil. Structure + tous les assets OK, mais le visuel reste à valider par Robin.

<details><summary>Consigne d'origine (archivée)</summary>

**⚠️ Dépôt séparé, PAS le thème :** `~/Atelier Sapi Claude Cowork/business/docs/charte-graphique/`
(dépôt `robinSapi/charte-atelier-sapi`, branche `main`). Ne rien toucher dans `theme-atelier-sapi`.

**Contexte :** Robin a validé. Tout est écrit et vérifié côté fichiers, il ne reste qu'à
committer et pousser. Le workflow GHA déploie seul sur `charte.atelier-sapi.fr`.
48 fichiers en attente depuis le commit `dea72a2`.

**Ce qui change :**
- **Survol** : doctrine posée. Bouton = la couleur fonce + l'ombre s'ouvre, aucun mouvement.
  Carte = elle lévite, la couleur ne bouge pas. Inerte = rien. Boutons en aplat, plus de dégradé.
- **Couleurs** : une seule pastille par couleur, coupée 66/34. La dérivée de survol est la
  tranche droite (hex + « SURVOL », copiable au clic, token en `title`). Le bloc « 1 DÉRIVÉE »
  et les cartes `span 2` sont supprimés → **la grille des couleurs est devenue uniforme**.
- **Photos** : section refaite. 4 exemples segmentés, 3 overlays côte à côte, carrousel de
  densité pleine largeur (scroll-snap), lightbox qui conserve la bordure rouge des faux.
- **Règle de cadrage** : « le luminaire au centre, entier dans la moitié centrale ».
  Nouvelle image `img/regle-zone.jpg`. `img/regle-2-3.jpg` et `img/sapi-art.jpg` supprimées.
- **Section Formats refondue** : grille en 3 colonnes explicites (l'`auto-fit` écrasait les cartes),
  Pinterest réuni en une seule carte pleine largeur avec ses 3 ratios (2:3 référence, carré, long).
  Sous-titre supprimé : il énonçait encore l'ancienne règle des 2/3 et contredisait la section Photos.
  Nouvelle règle « Le centrage » + `img/regle-centrage.jpg`.
- **Les 3 exemples d'overlay repassés en 4:5** (ils étaient en 4:3), régénérés depuis `situation.jpg`.
- ⚠️ **Règle du nom de produit appliquée aux images** : prénom Montserrat 700 CAPITALES + surnom
  Square Peg, sur une seule ligne de base, rapport 2,12× (la charte affiche 2,13×). Les visuels
  violaient la règle que la charte énonce dans sa propre section Polices.
- **Polices** : woff2 remplacés par des TTF. `polices-sapi.zip` (pack combiné) abandonné au
  profit de **deux packs séparés** : `square-peg.zip` + `montserrat.zip`.
- Textes raccourcis à l'essentiel (2129 mots), tirets cadratins retirés partout.
- **Print supprimé** : Robin abandonne la rubrique. L'onglet, la section 09, l'entrée de menu,
  les 2 encarts « manque » disséminés dans Logo et Couleurs, le contexte `print` des 14 sections
  partagées et les 6 règles CSS orphelines (`.gap`, `.gap-t`, `.gap-list`) sont retirés.
  **Garde-fou ajouté** : le contexte est mémorisé en `localStorage`, et `'print'` dort dans le
  navigateur de quiconque a cliqué l'onglet. Sans lui la page s'ouvrait **vide**, sans rien en
  console pour l'expliquer. Il retombe sur `web` si le contexte mémorisé n'existe plus.
- **Nouvelle carte de téléchargement : la palette Affinity** (`download/palette-sapi.clr`,
  18 couleurs nommées, là où l'ancienne en avait 17 anonymes dont 2 fantômes).
  ⚠️ Elle est **générée depuis la constante `GAMMES` d'`index.html`** : ne jamais l'éditer à la
  main, sinon la charte et le nuancier divergent. Le `.clr` du Drive a été remplacé par le même
  fichier (ancien archivé dans `_local/`).
- README remis d'aplomb (il décrivait 12 PNG et des woff2 qui n'existent plus).

**À faire :**
1. `cd business/docs/charte-graphique/`
2. `git status` — vérifier qu'il n'y a NI `_local/`, NI `.DS_Store`, NI `.ai`, NI `charte-en-ligne.zip`.
   (contrôlé côté Cowork : c'est propre, mais revérifier avant de committer)
3. `git add -A` — inclut 4 suppressions (`polices-sapi.zip`, les 2 woff2, `sapi-art.jpg`)
4. Commit : « Photos, survol, dérivées en bande, palette Affinity, print retiré »
5. `git push origin main`
6. Suivre le run Actions. En cas d'échec, rapporter le log ici sans retenter en boucle.

**Critères de succès :**
- `https://charte.atelier-sapi.fr` à jour
- `download/square-peg.zip` et `download/montserrat.zip` → **200**
- `download/polices-sapi.zip` → **404 attendu** (le pack combiné est abandonné ; le workflow
  synchronise et doit le retirer du serveur — si il répond encore 200, le signaler)
- `download/fonts/SquarePeg-Regular.woff2` → **404 attendu**
- `img/regle-zone.jpg` et `img/regle-centrage.jpg` → 200 ; `img/regle-2-3.jpg` et `img/sapi-art.jpg` → 404
- `download/palette-sapi.clr` → **200**. Le workflow exclut `*.zip` et ré-inclut `download/*.zip` ;
  `.clr` n'est matché par aucune exclusion, donc il devrait monter. À vérifier quand même : si 404,
  ajouter une exception dans `deploy.yml`.
- **Plus aucun onglet « Print »** dans la barre de contexte : il ne reste que Site web et Réseaux
  sociaux. Et la page ne doit **pas** être blanche même avec `sapi-charte-ctx=print` en localStorage
  (c'est le cas de Robin) : tester en console avec
  `localStorage.setItem('sapi-charte-ctx','print')` puis recharger.
- `/_local/` → toujours 404

**⚠️ Point non vérifié côté Cowork :** le **rendu**. Pas de navigateur dans le bac à sable
(playwright ne peut pas installer Chromium sans sudo). Les contrastes, la largeur des textes,
l'équilibre des accolades et le parse JS sont vérifiés ; **le visuel ne l'est pas**.
Si quelque chose s'affiche de travers après déploiement, c'est là qu'il faut regarder en premier.

**Rappel :** `main` de ce dépôt est directement en ligne. Le push publie.

---

## [✅ FAIT — 2026-07-17] Charte — pousser la refonte des visuels et de la densité

**Date :** 2026-07-17
**Priorité :** normale

### ✅ Résultat Claude Code (2026-07-17)
- **Vérif avant commit** : `.ai` (dans `_local/`) et `.DS_Store` (dans `download/fonts/`) bien **ignorés** ; rien de `_local/` (ni `.bak.html`) stagé. 26 fichiers stagés (conforme), dont les 4 suppressions génériques. ✅
- **Commit** `5187b4a` « Photos de Robin, compositions réseaux, densité en 8 cartes », `git push origin main` → OK (un seul commit cette fois, pas de modif concurrente de Cowork).
- **Déploiement GHA** confirmé (fichier live re-daté ~20 s après le push). Critères testés (cert SSL valide) :
  - `/` → 200, **identique** à l'`index.html` poussé ✅
  - les **8 photos par modèle** (`packshot-leon`, `packshot-merveilleuse`, `detail-claudine`, `vue-dessous-olivia`, `vue-dessous-gaston`, `situation-sebastien`, `situation-alice`, `situation-charlie`) → **200** ✅
  - les **8 compositions réseaux** `img/compo-*.jpg` référencées et servies → **200** ✅
  - `img/regle-centrage.jpg` + `img/regle-zone.jpg` → 200 ✅
  - anciennes génériques (`packshot.jpg`, `detail.jpg`, `situation.jpg`, `vue-dessous.jpg`) → **404** (retirées par la synchro FTP) ✅
  - `_local/` → **404** ✅
- **Section Densité** : `.m-edito` (éditoriale) et `.m-split` (scindée) présentes dans le HTML live → 8 cartes en place ✅
- **Intégrité liens** : tous les assets référencés dans l'`index.html` live répondent **200** — aucun lien cassé.
- **Perf** : somme des photos référencées ~3,4 Mo (conforme à l'estimation Cowork ~3,6 Mo). Si la page traîne, l'option « redescendre 1600→1200px » reste en réserve.
- **⚠️ Rendu visuel non vérifié** (pas de navigateur dans le bac à sable) : hauteurs `.m-edito`/`.m-split` **estimées** → surveiller un éventuel débordement clippé en silence par `overflow:hidden` ; contraste des 3 critères photo sur aplat orange (3,63:1, sous le seuil 4,5 — réserve assumée par Robin). À valider à l'œil par Robin.

<details><summary>Consigne d'origine (archivée)</summary>

**⚠️ Dépôt séparé, PAS le thème :** `~/Atelier Sapi Claude Cowork/business/docs/charte-graphique/`
(dépôt `robinSapi/charte-atelier-sapi`, branche `main`). Ne rien toucher dans `theme-atelier-sapi`.

**Contexte :** Robin a validé (« c'est parfait, on pousse en prod »). **26 fichiers** en attente
depuis `88eb2be`. Tout est écrit et contrôlé côté Cowork, il ne reste qu'à committer et pousser.

**Ce qui change :**
- **8 photos fournies par Robin remplacent les anciennes**, nommées **par modèle** :
  `packshot-leon`, `packshot-merveilleuse`, `detail-claudine`, `vue-dessous-olivia`,
  `vue-dessous-gaston`, `situation-sebastien`, `situation-alice`, `situation-charlie`.
  Les 4 génériques (`packshot.jpg`, `detail.jpg`, `situation.jpg`, `vue-dessous.jpg`) sont supprimées.
- **Les 4 catégories de visuels passent en carré.**
- **8 compositions réseaux publiées** ajoutées en galerie dans Formats (`img/compo-*.jpg`).
  35 Mo compressés à 601 Ko.
- **Les 5 images fabriquées régénérées** (`regle-zone`, `regle-centrage`, `overlay-noir/blanc/faux`)
  avec une recette unique : courbe `(1-(1-t)³)²`, rampe pure sans palier, dégradé qui s'arrête au
  luminaire, nom de produit en une seule couleur.
- **Carrousel de densité : 5 → 8 cartes.** Deux mises en page nouvelles (`.m-edito` éditorial à
  vignette, `.m-split` texte/photo 50-50) et deux cas nouveaux (la primaire en aplat sombre,
  l'orange répété quatre fois). Données produit tirées du **catalogue réel** (Olivia Ø700/750 g/120 €,
  Claudine Ø650/135 €) — aucun chiffre inventé.
- **Section Formats refondue** : grille 3 colonnes, Pinterest réuni en une carte pleine largeur avec
  ses 3 ratios, sous-titre supprimé, règle « Le centrage » ajoutée.
- **Les 3 critères photo en aplat orange** (choix de Robin, voir la réserve).
- **Infractions de la charte contre elle-même, corrigées** : 2 overlays pleins cadres qui mangeaient
  le luminaire dans les mockups, 6 noms de produit ayant perdu leurs deux polices, 1 prix en
  monospace (3e police interdite).
- **Le hero était illisible depuis le premier jour.** « ATELIER SÂPI » était en `--wood` sur le fond
  sombre : **1,36:1**. Le bois est un ton moyen (luminance 0,21), il ne peut porter aucun petit texte
  sur ce fond — même sans la photo il plafonnerait à 3,13. Passé en crème à 90% → **4,68:1**.
  La date en bas à droite était à **2,84:1** (opacité 50%) → passée à 85% → **4,75:1**.
  ⚠️ Conséquence assumée : le hero perd son accent bois.

**À faire :**
1. `cd business/docs/charte-graphique/`
2. `git status` — vérifier qu'il n'y a NI `_local/`, NI `.DS_Store`, NI `.ai`.
   ⚠️ `_local/` contient des `.bak.html` et des PNG de travail : ils doivent rester gitignorés.
3. `git add -A` — inclut 4 suppressions (les photos génériques)
4. Commit : « Photos de Robin, compositions réseaux, densité en 8 cartes »
5. `git push origin main`
6. Suivre le run Actions. En cas d'échec, rapporter le log ici sans retenter en boucle.

**Critères de succès :**
- `https://charte.atelier-sapi.fr` à jour
- `img/packshot-leon.jpg` et `img/compo-achetez-lampadaire.jpg` → **200**
- `img/packshot.jpg`, `img/detail.jpg`, `img/situation.jpg`, `img/vue-dessous.jpg` → **404 attendu**
  (le workflow synchronise et doit les retirer du serveur ; s'ils répondent encore 200, le signaler)
- Section Densité : **8 cartes**, dont une éditoriale et une scindée
- Section Formats : la galerie des 8 compositions s'affiche sous l'onglet **Réseaux sociaux**

**⚠️ Non vérifié côté Cowork — à regarder en premier si ça casse :**
- **Le rendu.** Pas de navigateur dans le bac à sable (playwright ne peut pas installer Chromium sans
  sudo). Contrastes, largeurs de texte, accolades, parse JS et intégrité des liens sont vérifiés ;
  **le visuel ne l'est pas**. Les hauteurs de `.m-edito` et `.m-split` sont **estimées** (marges de
  12 à 168px selon les cartes). Si une carte déborde, c'est là — un débordement de 42px est déjà
  passé inaperçu aujourd'hui parce que `overflow:hidden` clippe en silence.
- **`img/` pèse 3,6 Mo.** Si la page traîne, redescendre les photos de 1600 à 1200px.

**Réserve assumée par Robin :** les 3 critères photo sont en aplat `#E35B24`. Le corps de texte y est
à **3,63:1 pour un seuil de 4,5** — aucune couleur de texte ne passe sur cet orange. Le titre (19px
gras) et le chiffre passent, au seuil « grand texte » de 3,0. Robin a tranché en connaissance de
cause. Même situation sur tous les boutons blanc-sur-orange des maquettes.

**Rappel :** `main` de ce dépôt est directement en ligne. Le push publie.

</details>

---

## [✅ FAIT — 2026-07-17] Charte — corriger le hero, illisible depuis le premier jour

**Date :** 2026-07-17
**Priorité :** normale — **2 lignes de CSS, rien d'autre**

### ✅ Résultat Claude Code (2026-07-17)
- **Diff conforme** : un seul fichier (`index.html`), 2 lignes dans le `<style>`, rien d'autre.
  - `.hero .eyebrow` : `color:var(--wood)` → `color:var(--creme);opacity:.9` ✅
  - `.hero .date` : `opacity:.5` → `opacity:.85` ✅
- **Commit** `c907c5e` « Hero lisible : ourlet et date remontés au-dessus de 4,5:1 », `git push origin main` → OK.
- **Déploiement GHA** confirmé (fichier live re-daté ~10 s après le push). Les 2 règles corrigées sont **présentes telles quelles dans le HTML live** ; page live == `index.html` poussé ; arbre propre, HEAD=origin/main.
- **⚠️ Rendu non vérifié à l'œil** (pas de navigateur) : les valeurs de contraste (4,68:1 / 4,75:1) sont celles calculées par Cowork ; à confirmer visuellement par Robin que « ATELIER SÂPI » (crème, plus bois) et la date sont bien lisibles.
- 📝 Observation hors-scope (non touchée) : `.hero .date` reste en `font-family:ui-monospace…` (une police monospace) — la charte interdit une 3ᵉ police. Pas dans le périmètre de cette tâche ; à signaler si tu veux qu'on l'aligne plus tard.

<details><summary>Consigne d'origine (archivée)</summary>

**⚠️ Dépôt séparé, PAS le thème :** `~/Atelier Sapi Claude Cowork/business/docs/charte-graphique/`
(dépôt `robinSapi/charte-atelier-sapi`, branche `main`).

**Contexte :** Robin a signalé que « ATELIER SÂPI » était illisible dans l'en-tête. Mesuré : **1,36:1**.
Un second échec a été trouvé au passage, qu'il n'avait pas signalé : la date en bas à droite, à
**2,84:1**. Seul `index.html` a changé depuis `5187b4a`, et uniquement dans le `<style>`.

**Ce qui change :**
- `.hero .eyebrow` : `color:var(--wood)` → `color:var(--creme);opacity:.9` → **1,36 → 4,68:1**
- `.hero .date` : `opacity:.5` → `opacity:.85` → **2,84 → 4,75:1**

**Pourquoi le bois ne pouvait pas rester :** sa luminance (0,21) est trop proche de celle du hero
(0,15 — `#323232` recouvert de la photo à 28%). Même en retirant la photo, il plafonnerait à **3,13**
sur le noir. Ce n'était pas un réglage à ajuster : le bois est un ton moyen, il ne porte aucun petit
texte sur fond sombre. **Conséquence assumée par Robin : le hero perd son accent bois.**

**À faire :**
1. `cd business/docs/charte-graphique/`
2. `git status` — un seul fichier modifié, `index.html`
3. `git add index.html`
4. Commit : « Hero lisible : ourlet et date remontés au-dessus de 4,5:1 »
5. `git push origin main`
6. Suivre le run Actions.

**Critères de succès :**
- `https://charte.atelier-sapi.fr` : « ATELIER SÂPI » lisible dans l'en-tête, en crème et non en bois
- La date en bas à droite du hero est lisible

**Rappel :** `main` de ce dépôt est directement en ligne. Le push publie.

</details>

---

## [TÂCHE] Page /catalogue B2B — Temps 1 (page web seule, sans PDF)
**Date :** 2026-08-04
**Priorité :** normale
**⚠️ Demander un PLAN + état des lieux AVANT de coder.** Ne rien committer sans le go de Robin. Travail sur **branche test** (jamais master), Robin valide sur test avant prod. Demander la branche exacte à utiliser.

**Brief complet :** `business/docs/brief-catalogue-temps1-page.md` (à lire en entier). Le Temps 2 (export PDF) est documenté séparément dans `business/docs/brief-catalogue-temps2-pdf.md` — **hors périmètre ici**, mais le Temps 1 doit poser les fondations qu'il réutilisera.

**Contexte :** catalogue produits sans prix pour prescripteurs B2B (architectes, décorateurs) qui le transmettent à leur client final. ~40 produits, 4 catégories (Suspensions, Appliques, Lampadaires, Posables). Découpé en 2 temps : la page d'abord, le PDF plus tard.

**À faire (résumé, détail dans le brief) :**
- Template autonome `page-catalogue.php`, **étanchéité** (aucun chemin vers le reste du site) : pas de `get_header/footer/nav`, ne pas se reposer sur `wp_head()` (imprimer à la main les seuls CSS/JS nécessaires), logo image non cliquable, aucune fiche cliquable vers `/produit/`, aucun lien sortant, aucun prix (ni HTML ni JSON), `noindex,nofollow`.
- Ordre de page : en-tête -> section « Histoire de l'atelier » (ACF) -> section « Deux bois au choix » Peuplier/Okoumé (ACF) -> 4 boutons filtres catégories (affichage client) -> grille produits (carte : galerie + titre + desc courte + bouton « Fiche technique ») -> bloc export PDF **désactivé/non câblé au Temps 1** -> bouton « Nous contacter » (mailto ou form intégré).
- Modale fiche technique : description complète + tableau caractéristiques à partir des **clés ACF/attributs Woo réels (lire le code, ne rien inventer)** ; établir le mapping caractéristiques (réutilisé au Temps 2) ; fermeture croix/clic extérieur/Échap ; mobile OK.
- **Fondations pour le Temps 2** : isoler la source de données produits (requête appelable indépendamment du rendu) et le mapping caractéristiques ; créer les champs ACF « Histoire » et « Bois ».

**Critères de succès :** voir la « Recette Temps 1 » à la fin du brief. En particulier : audit HTML remonte zéro `<a href>`/`<link rel>` interne, zéro `og:url`, zéro prix ; filtres catégories OK ; modale OK ; `noindex` vérifié ; mobile OK.

### ✅ RÉPONSE CLAUDE CODE — Temps 1 CODÉ + POUSSÉ SUR TEST (2026-08-04, en attente validation Robin)

**Branche :** `test-theme-sapi-maison`. Code livré, mais **la page n'existe pas encore côté WordPress** — voir le pas-à-pas ci-dessous (action Robin).

**Fichiers créés/modifiés :**
- `inc/catalogue-data.php` (NEW) — fondation Temps 2 : source de données unique `sapi_catalogue_get_products()` (produits groupés par catégorie, **zéro prix**, images en IDs d'attachment) + mapping caractéristiques `sapi_catalogue_specs_schema()` / `sapi_catalogue_get_product_specs()` (aligné sur `single-product.php` + `acf-champs-fiche-technique.csv`, aucune clé inventée) + `sapi_catalogue_categories()` + groupe ACF `group_catalogue_b2b` (Histoire / Bois / accroche).
- `page-catalogue.php` (NEW) — template autonome « Catalogue B2B » (pas de get_header/footer/wp_head/wp_footer ; `<head>` manuel + `noindex,nofollow` ; logo image morte ; en-tête → Histoire → Bois → 4 filtres → grille cartes → bloc PDF **masqué** → FAB contact mailto `contact@atelier-sapi.fr` ; modale fiche technique).
- `assets/catalogue.css` (NEW) — feuille autonome (tokens recopiés, Square Peg local, Montserrat→fallback système, responsive).
- `assets/catalogue.js` (NEW) — galerie (nav + dots + swipe), filtres client-side (`.is-filtered-out`), modale (ouverture par clonage du `<template>` de la carte, fermeture croix/clic extérieur/Échap, piège de focus, lock scroll).
- `functions.php` — 1 ligne : `require_once .../inc/catalogue-data.php` (front + admin).

**Étanchéité — audit STATIQUE passé** (le HTML rendu reste à auditer une fois la page publiée) :
- 0 `get_header/get_footer/wp_head/wp_footer/wp_nav_menu` appelés.
- Unique `<a href>` = le mailto contact (autorisé par le brief). 0 `<link rel>`, 0 `og:`, 0 JSON-LD dans le template.
- 0 prix / `get_price` / permalink dans template + data + JS.
- `<meta name="robots" content="noindex, nofollow">` présent.
- **Durcissement** : descriptions produit passées par `sapi_catalogue_safe_html()` → `strip_shortcodes` + `wp_kses` sans `<a>` (empêche un lien interne éditorial de fuiter dans la modale).
- Équilibrage accolades/parenthèses/crochets OK sur les 2 fichiers PHP (pas de binaire PHP local pour lint, comme d'habitude).

**✅ AUDIT DU HTML RENDU — page publiée `/catalogue` (fetch live 2026-08-04, commit `2bb3d57`) :**
- `<a href>` interne : **0** (uniquement Google Fonts externe + mailto contact).
- Prix (€, woocommerce-Price, data-price, "price") : **0**.
- Fuites wp_head (canonical, og:, JSON-LD, rel REST/RSS/oEmbed/shortlink, wp-json) : **0** → `<head>` manuel étanche confirmé.
- `<meta robots noindex,nofollow>` présent. Bloc PDF `hidden`. 27 produits, tableaux specs présents.
- **Formatter câblé** (commit `2bb3d57`) : titres carte+modale en `.product-name` + `product-name-formatter.js` chargé ; Montserrat via Google Fonts (comme le site). Le rendu prénom/surnom est JS (invisible au curl) → à confirmer à l'œil.

**✅ VALIDÉ PAR ROBIN SUR TEST (2026-08-04)** — page publiée + testée en navigateur : rendu, titres prénom/surnom, filtres, modale, galeries. Page live : `test.atelier-sapi.fr/catalogue`.

**Retouches cosmétiques post-validation (commit `8487112`) :**
- Image de carte : remplit tout le carré (fix spécificité `.cat-gallery__slide .cat-gallery__img` vs la règle globale `img{height:auto}`).
- Clic n'importe où sur la carte → ouvre la fiche (flèches/dots de galerie exclus). Curseur pointer sur la carte.
- Modale desktop refaite en **colonne verticale** : photo paysage 3/2 en haut (grande, pleine largeur), titre + desc + specs dessous, scroll d'un bloc. Dialog 820px ; photo 4/3 sur mobile.

**Commits (branche test) :** `eefb25a` (page + fondation) → `2bb3d57` (formatter + Montserrat) → `0a6341d` (audit) → `8487112` (cosmétique).

---

### 📨 RETOUR À COWORK — Temps 1 catalogue B2B : TERMINÉ + VALIDÉ SUR TEST

**Statut :** ✅ livré, validé par Robin sur `test.atelier-sapi.fr/catalogue`. **Pas encore en prod.**

**Ce qui est en place :** page `/catalogue` étanche (0 lien interne, 0 prix, 0 fuite wp_head, `noindex` — audité sur le HTML live), 27 produits en 4 catégories, sections Histoire/Bois éditables en ACF, filtres, modale fiche technique, formatter des noms, bloc PDF **masqué** (Temps 2).

**Fondations posées pour le Temps 2 (PDF)** — à réutiliser tel quel, ne pas redévelopper :
- Source de données unique **sans prix** : `sapi_catalogue_get_products()` (dans `inc/catalogue-data.php`).
- Mapping caractéristiques : `sapi_catalogue_specs_schema()` / `sapi_catalogue_get_product_specs()`.
- Champs ACF Histoire/Bois (groupe `group_catalogue_b2b`) — même source pour le PDF.

**Actions côté Robin / Cowork avant d'aller plus loin :**
1. **Remplir le contenu ACF** de la page Catalogue (bloc « Catalogue B2B — contenus » : Histoire, intro Bois, textes/images Peuplier & Okoumé). Sans ça = valeurs par défaut.
2. **Go prod** quand Robin le décide → merge `test-theme-sapi-maison` → `master` (uniquement sur son ordre), puis Robin lance le workflow GitHub Actions.
3. **Décider du Temps 2 (export PDF)** : quand Cowork/Robin veut l'enchaîner, ouvrir la tâche à partir de `business/docs/brief-catalogue-temps2-pdf.md` — les fondations ci-dessus sont prêtes.

**Points à trancher restés ouverts (mineurs, si Cowork veut affiner) :** bloc PDF actuellement masqué (le rendre visible-désactivé possible au Temps 2) ; adresse contact = `contact@atelier-sapi.fr`.

**Écarts assumés (à confirmer) :**
1. **Titres produit** affichés bruts (pas passés dans `product-name-formatter.js`) — choix d'étanchéité/simplicité pour cette page B2B autonome. Si tu veux le rendu prénom/surnom, je câble le formatter (self-contained) au tour suivant.
2. **Montserrat** non self-hosté dans le thème → fallback police système sur cette page autonome (Square Peg, lui, est local). Si tu veux Montserrat garanti, je l'ajoute en @font-face local.

**👉 ACTION ROBIN — créer la page dans WordPress :**
1. WP admin → **Pages → Ajouter** : titre « Catalogue », **permalien `/catalogue`**.
2. Panneau **Attributs de page → Modèle → « Catalogue B2B »**, puis **Publier**.
3. La page publiée fait apparaître le bloc ACF **« Catalogue B2B — contenus »** : remplir Accroche, Histoire (titre/texte/image), Bois (intro + textes/images Peuplier & Okoumé). Vide = valeurs par défaut.
4. Ouvrir `test.atelier-sapi.fr/catalogue` et valider : filtres, galeries, bouton « Fiche technique » (modale : description + tableau caractéristiques), fermeture croix/clic/Échap, mobile.
5. Retour : ce qui va / ne va pas → j'ajuste. (Le bloc PDF est volontairement masqué : Temps 2.)

---

## [TÂCHE] Catalogue B2B — Temps 2 : export PDF
**Date :** 2026-08-04
**Priorité :** normale
**⚠️ Demander un PLAN + état des lieux AVANT de coder.** Ne rien committer sans le go de Robin. Même branche que le Temps 1 : **`test-theme-sapi-maison`** (jamais master). Robin valide sur test. **Mise en prod groupée** : Temps 1 + Temps 2 seront mergés ensemble vers master quand Robin dira go (ne pas merger avant).

**Brief complet :** `business/docs/brief-catalogue-temps2-pdf.md` (à lire en entier).

**Réutiliser les fondations du Temps 1 (ne PAS redévelopper)** :
- Source de données sans prix : `sapi_catalogue_get_products()` (`inc/catalogue-data.php`).
- Mapping caractéristiques : `sapi_catalogue_specs_schema()` / `sapi_catalogue_get_product_specs()`.
- Champs ACF Histoire/Bois (groupe `group_catalogue_b2b`) → mêmes contenus dans le PDF.

**À faire (résumé, détail dans le brief) :**
- **mPDF** via Composer dans le thème, police(s) de marque enregistrées (`fontdata`). CSS PDF **distincte** (mPDF ne gère qu'un sous-ensemble CSS).
- **Route REST** (ex. `/wp-json/sapi/v1/catalogue-pdf`) recevant les catégories cochées ; rejoue la même requête produits que la page. **Un seul PDF combiné** : page de garde → page Histoire (ACF) → page Bois Peuplier/Okoumé (ACF) → une section par catégorie cochée (une page/produit) → page contact en texte non cliquable.
- **Étanchéité PDF** : aucun prix, aucun lien, aucune URL cliquable. Mention « Document non contractuel, ne constitue pas une offre de prix ».
- Gabarit fiche : titre + SKU, visuel principal, 2-3 vignettes, description intégrale (débordement propre sur 2e page, pas de troncature), tableau caractéristiques (mapping Temps 1), pied = réf + n° de page. Champs vides = tiret, pas masqués.
- **Perf/cache** : images `large` (1024px) principal / `medium` vignettes, JPEG ~80, cible < 25 Mo. Cache fichier dans `wp-content/uploads/catalogues/` (dossier non indexable), clé = hash catégories + timestamp dernière modif produit ; invalidation sur `save_post`, `updated_post_meta` (ACF), remplacement image, modif description. **Pré-générer** « toutes catégories » + 4 mono-catégorie (hook/cron) ; combinaisons rares à la demande avec loader explicite. Vérifier `max_execution_time` / `memory_limit` sur o2switch mutualisé.
- **Câbler le bloc export** en bas de `/catalogue` (actuellement masqué) : cases 4 catégories + bouton « Télécharger en PDF » → endpoint. Choix **indépendant** du filtre d'affichage du haut (libellé clair).

**Critères de succès :** voir la « Recette Temps 2 » à la fin du brief. En particulier : PDF combiné = exactement les catégories cochées ; pages Histoire/Bois présentes ; 0 prix, 0 lien/URL cliquable ; mention non contractuel ; poids complet mesuré < 25 Mo ; pré-génération OK ; invalidation cache testée ; dossier catalogues non indexable.

### ✅ RÉPONSE CLAUDE CODE — Temps 2 PDF CODÉ + RECETTE TECHNIQUE OK SUR TEST (2026-08-04)

**Décisions Robin (validées) :** mPDF installé via **étape Composer en CI** (vendor/ gitignoré) ; limites serveur **détectées** ; bloc export **affiché+câblé** ; **polices de marque** TTF (charte).

**Infra & diagnostic serveur (test, o2switch) :** PHP 8.3, **mémoire 512 Mo**, **max_execution 600 s**, GD dispo, tempDir inscriptible. mPDF 8.3.1 OK.

**Mesures réelles :** PDF **toutes catégories = 3,9 Mo, 2,06 s** (pic 58 Mo) — largement < 25 Mo. Mono-catégorie (appliques) = 0,78 Mo.

**Recette (Recette Temps 2 du brief) :**
- [x] Endpoint `GET /wp-json/sapi/v1/catalogue-pdf?cats=…` → 200 `application/pdf`, `%PDF-1.4`, pièce jointe.
- [x] PDF combiné = catégories cochées (clé de cache par sélection).
- [x] Pages Histoire + Bois présentes (lisent l'ACF Temps 1).
- [x] **0 prix** : données Temps 1 sans prix + audit flux PDF décompressés (0 « euro/EUR/prix ») + audit page Temps 1 (mêmes descriptions, 0 €).
- [x] **0 lien/URL cliquable** : **0 annotation `/URI`** dans le PDF ; contact en texte brut.
- [x] Mention « Document non contractuel, ne constitue pas une offre de prix » (pied + page contact).
- [x] Poids complet **3,9 Mo < 25 Mo**.
- [x] Pré-génération (cron single event) : toutes + 4 mono-catégorie.
- [x] Invalidation : `save_post_product`, add/edit/delete attachment, meta produit + ACF Histoire/Bois → bump stamp + purge + pré-gén différée.
- [x] Dossier `uploads/catalogues/` **non accessible en direct** (vrai fichier caché → 404 ; upload normal → 200) **+ noms non devinables** (`wp_hash`). `mpdf-tmp` → 404.
- [x] Bloc export **indépendant** du filtre d'affichage (cases dédiées, libellé clair).

**Fichiers :** `composer.json`, `.gitignore` (+vendor/), `.github/workflows/deploy-test.yml`+`deploy-prod.yml` (étape composer + exclude scopé), `inc/catalogue-pdf.php` (infra+générateur+cache+endpoint+pregen), `assets/pdf-fonts/*.ttf`, `assets/pdf-logo.png`, `page-catalogue.php` (bloc câblé), `assets/catalogue.js`+`.css` (bouton). SKU ajouté à `inc/catalogue-data.php`.
Commits : `51058bc` (infra) → `1efeab9` (polices) → `c02c6c8` (générateur) → `4a1f7fd` (cache/endpoint/pregen/bouton) → `a0a1b56` (durcissement cache).

**✅ VALIDÉ PAR ROBIN SUR TEST (2026-08-05)** — après itérations de mise en page.
**Ajustements post-recette (validés) :**
- Fiche produit remaniée « Option A » : grande photo paysage en haut + vignettes + description + caractéristiques en 2 colonnes équilibrées, **1 produit/page** (31 pages toutes catégories).
- Tailles disponibles + essences lues dans les variations (pa_taille / pa_materiau) → **dans le tableau caractéristiques** (lignes Dimensions/Bois). Pastilles retirées (mPDF ne les rend pas proprement).
- Nom produit prénom/surnom en NOIR ; **surnom Square Peg 34pt EN STYLE INLINE**. ⚠️ **PIÈGE mPDF** : les sélecteurs descendants (`.prod-name .pr { font-size }`) **ne sont pas appliqués** → toute taille de police sur span imbriqué doit être **inline**, sinon retombe au défaut (~10pt). Symptôme : « la taille ne change pas ».
- Mention pied revue : « Document non contractuel remis à titre de présentation » (plus « offre de prix »).
- Photos alignées à gauche, coins droits (mPDF n'arrondit ni les `<img>`, ni proprement via background dans les tableaux → abandonné).
- Animation de chargement au téléchargement (fetch→blob + spinner).
- **Busting cache par version** : constante `SAPI_CATALOGUE_PDF_VERSION` (=14) dans la clé de cache → à incrémenter à chaque évolution de mise en page.

**✅ NETTOYAGE FAIT** : routes temporaires `catalogue-pdf-selftest` / `catalogue-pdf-preview` retirées. Ne reste que l'endpoint public `catalogue-pdf`.

### 📨 RETOUR À COWORK — Catalogue B2B (Temps 1 + 2) : EN PROD ✅ (2026-08-05)

**Statut : LIVE et validé par Robin sur `atelier-sapi.fr`.**

**Ce qui est en ligne :**
- **Page `/catalogue`** (prescripteurs B2B — architectes/décorateurs) : catalogue produits **sans prix, sans lien, `noindex`**, à transmettre au client final sans court-circuiter le prescripteur. Sections Histoire + Deux bois (Peuplier/Okoumé) éditables en ACF, filtres par catégorie, fiche technique en modale.
- **Export PDF** : bouton « Télécharger en PDF » (choix des catégories) → un PDF A4 de marque, 1 produit/page, sans prix ni lien, mention « document non contractuel ».
- **Audit prod OK** : page 200 / `noindex` / 0 lien interne / 0 prix ; endpoint PDF 200 (`application/pdf`).

**À noter côté Cowork / Robin :**
1. Le **contenu ACF** (Histoire de l'atelier, textes/images des deux bois) est à remplir/soigner en prod si ce n'est pas déjà fait (sinon valeurs par défaut). C'est éditable sans code, depuis la page Catalogue.
2. La page est **volontairement non indexable** (pas dans Google, pas de lien depuis le site) — c'est le but. Le lien `atelier-sapi.fr/catalogue` se **partage à la main** aux prescripteurs.
3. **Déploiement sélectif** : seul le catalogue est parti en prod. **Tout le reste du travail sur test reste sur test** (immersion /mes-creations, état B, room-picker, admin « Règles de filtrage » Tâche 5, emails WooCommerce, mode vacances…) — leur mise en prod est une décision séparée, à planifier quand Robin voudra.
4. Idée business (si utile) : Cowork peut préparer un petit **message-type** pour envoyer le catalogue aux prescripteurs, et référencer le lien dans les docs commerciales.

---

**✅ POUSSÉ EN PROD (master) le 2026-08-05 — commit `113a1ef`.** Déploiement **sélectif** : cherry-pick propre en UN commit par-dessus master (16 fichiers = 100% catalogue), **PAS** un merge de test (qui aurait envoyé immersion / état B / Tâche 5 / emails / mode vacances, non validés prod). master ne contient qu'un commit de plus que l'avant-catalogue. **Reste à Robin :** (1) lancer le workflow GitHub Actions « Deploy to Production » (manuel, régénère vendor via Composer) ; (2) créer la page `/catalogue` en prod (template + ACF) ; (3) vérifier `atelier-sapi.fr/catalogue` + bouton PDF.

<details><summary>Ancien « RESTE avant prod » (archivé)</summary>

**GO PROD (Temps 1 + Temps 2 groupés)** — sur ordre explicite de Robin uniquement :
1. Merge `test-theme-sapi-maison` → `master` + push.
2. Robin lance le workflow GitHub Actions prod (qui régénère `vendor/` via l'étape Composer).
3. Robin crée/complète la page `/catalogue` en prod (template + ACF) si pas déjà répliqué.
**Diagnostic serveur test o2switch (utile pour la prod)** : PHP 8.3, mémoire 512 Mo, max_execution 600 s, GD ok — génération synchrone viable. Vérifier que la prod a les mêmes ordres de grandeur.

</details>

---

## [TÂCHE] Catalogue B2B — Corrections post-prod (fonts, cache, robustesse PDF)
**Date :** 2026-08-05
**Priorité :** normale
**⚠️ Demander un PLAN + état des lieux AVANT de coder.** Ne rien committer sans le go de Robin. Développer sur **`test-theme-sapi-maison`**, valider sur test, puis **cherry-pick sélectif vers master** (même pattern que le déploiement initial du catalogue, commit `113a1ef`) — pas de merge de test. ⚠️ Le catalogue vit désormais sur deux branches (couture cherry-pick), rester scopé aux fichiers du catalogue.

**Contexte :** le catalogue B2B (Temps 1 + 2) est en prod et validé. Trois corrections de qualité identifiées en revue. Le reste (pré-génération, noindex, distribution) est assumé par Robin, hors périmètre.

### Point 2 — Self-héberger les polices (retirer Google Fonts externe)
**Problème :** la page `/catalogue` charge Montserrat depuis Google Fonts (`<link>` externe) → requête sortante qui écorne l'étanchéité + friction RGPD connue en France (IP visiteur envoyée à Google).
**À faire :** self-héberger Montserrat (les graisses réellement utilisées) en `@font-face` local dans `assets/catalogue.css`, supprimer le `<link>` Google Fonts du `<head>` de `page-catalogue.php`. Square Peg est déjà local, s'aligner sur le même schéma. Fichiers de police dans le thème (ex. `assets/fonts/`).
**Succès :** re-audit du HTML live = **0 `<link>`/requête externe** (plus aucun appel Google) ; rendu Montserrat identique à l'œil ; les TTF du PDF (`assets/pdf-fonts/`) restent cohérents avec les graisses de la page.

### Point 3 — Busting de cache PDF automatique (fin du compteur manuel)
**Problème :** `SAPI_CATALOGUE_PDF_VERSION = 14` s'incrémente **à la main** à chaque évolution de mise en page. Oubli = vieux PDF servi silencieusement, bug difficile à diagnostiquer.
**À faire :** dériver la version de la clé de cache d'un **hash (ou filemtime) des fichiers qui déterminent le rendu PDF** (`inc/catalogue-pdf.php` + la CSS/template PDF + éventuellement la CSS des polices). La clé de cache devient : hash catégories + timestamp dernière modif produit + hash template PDF. Supprimer la dépendance au compteur manuel (ou le garder en override d'urgence seulement).
**Succès :** modifier le template PDF (sans toucher aucune constante) → le prochain téléchargement régénère automatiquement ; deux générations sans changement de code = cache réutilisé (pas de régénération inutile).

### Point 4 — Durcir la robustesse du gabarit PDF (pièges mPDF)
**Problème :** mPDF ignore les sélecteurs descendants (piège documenté : la taille du surnom Square Peg doit être en style inline sinon retour à ~10pt). Chaque futur ajustement du gabarit risque de rouvrir ce type de régression silencieuse.
**À faire :** (a) centraliser/commenter les styles fragiles du gabarit produit (tailles de police des noms, etc.) dans un helper unique en inline, avec un commentaire d'avertissement, pour ne pas les redécouvrir à chaque édition ; (b) ajouter un **self-check de non-régression** léger (endpoint admin-only OU commande, pas de route publique) qui régénère un PDF échantillon et **assert les invariants machine-vérifiables** : `%PDF`, 0 annotation `/URI`, 0 occurrence de prix (€/EUR), nombre de pages attendu, présence des pages Histoire + Bois. Retirer la route après si besoin, ou la garder gated admin.
**Succès :** le self-check passe ; les invariants d'étanchéité (0 `/URI`, 0 prix) sont vérifiables à la demande sans refaire l'audit à la main ; les réglages de police du gabarit sont regroupés et commentés.

**Rappel transverse :** ces corrections ne doivent rien changer au rendu validé par Robin (mise en page « Option A », 1 produit/page, mention « document non contractuel remis à titre de présentation »). Re-mesurer le poids du PDF toutes catégories après coup (doit rester < 25 Mo, était à 3,9 Mo).

---

## [TÂCHE] Blinder les appels WooCommerce du thème (anti-fatal quand WC est absent/inactif)
**Date :** 2026-08-05
**Priorité :** haute
**Branche :** `test-theme-sapi-maison` d'abord, valider sur test, puis cherry-pick sélectif vers master (pas de merge de test). Ne rien committer sans le go de Robin. Demander un PLAN + état des lieux AVANT de coder.

**Contexte :** le 5 août 2026, une mise à jour du plugin **Broken Link Checker** a provoqué une erreur fatale qui a désactivé WooCommerce dans la foulée. Résultat : le thème a planté tout le site (front + admin, écran blanc) car `functions.php` appelle des fonctions WooCommerce **sans vérifier que WC est chargé**. L'erreur exacte remontée par WordPress :
`Uncaught Error: Call to undefined function wc_get_page_id() in .../theme-sapi-maison/functions.php` (dans le callback `admin_init` de migration checkout, `wc_get_page_id('checkout')` — ~ligne 1500 sur master).
Le site n'a été rétabli que par une restauration UpdraftPlus. Objectif : que la seule indisponibilité de WooCommerce ne puisse **plus jamais** faire tomber le thème.

**À faire :**

1. **Corriger le déclencheur exact.** Dans le callback `add_action('admin_init', function () { $page_id = wc_get_page_id('checkout'); ... })`, ajouter en première ligne un garde : `if (!function_exists('wc_get_page_id')) return;`.

2. **Auditer et blinder les autres appels WooCommerce non gardés** exécutés au chargement de `functions.php` ou sur des hooks toujours joués (`after_setup_theme`, `init`, `admin_init`, `wp_enqueue_scripts`, `widgets_init`, etc.). Cibler les appels nus à `wc_*()`, `WC()->...`, `is_cart()`, `is_checkout()`, `wc_get_page_id()`, etc. qui ne sont pas déjà protégés par `class_exists('WooCommerce')` / `function_exists(...)`. Certains le sont déjà (ex. l 532, 672, 774) — les laisser ; il s'agit de couvrir ceux qui ne le sont pas. Pattern de garde à réutiliser : `if (!class_exists('WooCommerce')) return;` en tête de callback, ou `function_exists()` autour de l'appel isolé.

3. **Ne pas modifier le comportement** quand WooCommerce EST actif (cas nominal) : les gardes ne doivent s'activer que si WC est absent. Zéro changement fonctionnel visible en prod.

**Critères de succès :**
- Test de résilience : WooCommerce désactivé → le site (front + `/wp-admin`) reste accessible, plus d'écran blanc, plus d'`undefined function` dans `debug.log` provenant du thème. Le site tourne en mode dégradé (sans les blocs boutique) mais **ne plante pas**.
- WooCommerce réactivé → comportement identique à aujourd'hui (panier, checkout, compteur, etc. inchangés).
- Console 0 erreur ; aucun impact sur la home ni le catalogue.

**🧪 TEST DE RÉSILIENCE (Robin, 2026-08-06) :** WC désactivé → **/wp-admin accessible ✅** (objectif principal atteint, plus de blocage back-office). **Front (home) reste cassé** : identifié = `front-page.php` (~12 appels non gardés `wc_get_product`/`wc_price` lignes 38/56/58/113/118/120/165/170/172/240/275/277, blocs produits de la home). `header.php`/`footer.php` déjà gardés. **Décision Robin : ON LAISSE — périmètre clôturé à functions.php, front-page.php assumé.** Si un jour on veut la home résiliente : encadrer ces blocs d'un `if (class_exists('WooCommerce'))` (petit follow-up, 1 fichier).

---

## [TÂCHE] Catalogue — page prix publics `/catalogue-prix` + export PDF tarif pro (admin)
**Date :** 2026-08-24
**Priorité :** normale
**Branche :** `test-theme-sapi-maison`, valider sur test, puis **cherry-pick sélectif vers master** (même pattern que `113a1ef`, pas de merge de test).
**⚠️ Demander un PLAN + un état des lieux AVANT de coder.** Rien ne part sans le go de Robin.
**Brief complet côté Cowork :** `business/docs/brief-catalogue-pro-tarife.md` (à lire, il contient les arbitrages et le texte par défaut des mentions légales).

**Contexte :** le catalogue B2B `/catalogue` est en prod, sans aucun prix, et sert aux prescripteurs qui montrent la gamme à leur client final. Robin a besoin de deux choses en plus : une version de cette page **avec les prix publics TTC**, et un **tarif professionnel remisé** à envoyer aux revendeurs (Muse, Ankorstore, prospects salons).

**Décision d'architecture — le point à ne pas rater :** le tarif professionnel n'existe **jamais sur le web**. Aucune page, aucun endpoint public, aucun lien secret ne contient de prix remisé. Le taux est saisi par Robin dans l'admin au moment de générer un PDF, et ne vit que le temps de cette génération. Conséquence directe : Robin peut sortir un PDF à -12% pour un client historique et à -30% pour un prospect, sans que le site ait à savoir qui est qui.

Trois objets, trois niveaux :

| Objet | Contenu | Accès |
|---|---|---|
| `/catalogue` (existant) | zéro prix | public, **strictement inchangé** |
| `/catalogue-prix` (nouveau) | PVP TTC par variation | public, `noindex` |
| PDF tarif pro (nouveau) | prix HT remisé + PVP TTC | admin seul |

**Contraintes fermes :**
- `inc/catalogue-data.php` porte une garantie documentée « aucune donnée de prix n'est jamais produite par cette couche ». **Ne pas la casser.** Les prix vivent dans un fichier séparé qui enrichit la couche existante.
- `/catalogue` ne doit pas bouger d'un octet dans son rendu.
- Le catalogue vit sur deux branches (couture cherry-pick) : rester scopé aux fichiers du catalogue.
- Prix WooCommerce = **PVP TTC**, TVA 20% (confirmé par Robin).

---

### Lot 1 — Couche de données prix (`inc/catalogue-data-pro.php`, nouveau fichier)

- `sapi_catalogue_round_ht($price)` — arrondi à **l'euro inférieur** (74,17 → 74). Un seul endroit.
- `sapi_catalogue_ht_from_ttc($ttc, $rate)` — `floor($ttc / 1.20 * (1 - $rate))`. Le taux est **toujours un argument**, jamais une constante : c'est ce qui rend l'export à taux variable possible.
- `sapi_catalogue_product_pricing($product)` — tableau de prix par variation : croise `pa_taille` × essence (`pa_materiau` / `pa_bois` / `pa_essence`) ; retourne pour chaque combinaison le libellé, le PVP TTC et le SKU de variation ; produit simple = une ligne. **Ne calcule aucun prix HT** : la conversion se fait au rendu du PDF, avec le taux du moment. Réutiliser `sapi_catalogue_product_sizes()` et `sapi_catalogue_product_essences()` qui existent déjà.
- `sapi_catalogue_normalize_product_priced($product)` — appelle `sapi_catalogue_normalize_product()` puis **ajoute** la clé `pricing`. Aucune duplication de la logique specs / galerie / descriptions.
- `sapi_catalogue_get_products()` gagne un argument optionnel `$include_ids` restreignant le résultat à une liste d'IDs produit, groupement et ordre conservés. **Modification additive** : sans l'argument, comportement strictement identique.

⚠️ Pas de paramètre `$with_prices = false` sur la fonction publique. Une fonction séparée, appelée explicitement. Un défaut finit toujours par se retourner contre soi.

### Lot 2 — Page `/catalogue-prix`

- Champ ACF `catalogue_affiche_prix` (true/false) sur le template `page-catalogue.php`, lu **une seule fois** en tête dans `$show_prices`. `/catalogue` garde le flag à false.
- Robin créera une seconde page WordPress, même template, flag à true, slug `catalogue-prix`.
- Rendu quand le flag est actif :
  - carte produit : « À partir de XX € TTC » sous la description courte
  - modale fiche technique : tableau complet des variations (Variation / Prix TTC)
  - section mentions légales en début de page, sous l'accroche (nouveau champ ACF `catalogue_mentions_legales`, textarea)
- `noindex, nofollow` déjà en place dans le template. Vérifier aussi l'exclusion du sitemap Yoast : la page reprend les descriptions des fiches produit, risque de cannibalisation.
- CSS : classes `cat-price-*` dans `assets/catalogue.css`, portées par `body.has-prices`. Pas de nouveau fichier.

### Lot 3 — Export PDF tarif pro

**Page admin** `Produits > Catalogue PRO`, capacité `manage_woocommerce` :

| Champ | Type | Défaut |
|---|---|---|
| Taux de remise (%) | nombre | 30 |
| Produits à inclure | liste à cocher groupée par catégorie | tous cochés |
| Établi pour | texte libre | vide |
| Date d'export | date | aujourd'hui |
| Valable jusqu'au | date | 31/12 de l'année en cours |
| Mentions légales | textarea | texte par défaut (voir le brief Cowork) |

- **Sélecteur produits** : ~40 lignes, case + nom + SKU, un « tout / rien » sur chaque en-tête de catégorie, compteur « 12 produits sélectionnés », export refusé à zéro produit. L'ordre du PDF reste celui du site (catégorie puis `menu_order`), pas l'ordre de cochage.
- ⚠️ **Piège o2switch déjà rencontré en Tâche 5** : des cases multiples en `name="...[]"` sont fusionnées par le WAF, une seule valeur survit. Utiliser un **nom unique par case** (`produits[<id>]=1`), sélection = clés cochées.
- **Persistance** : chaque export enregistre les valeurs en option WP et les repropose au suivant, **sauf `Établi pour`** qui repart toujours vide (ne jamais envoyer à Ankorstore un tarif au nom de Muse).
- **Garde-fou** : au-delà de 35% de remise, confirmation explicite avant génération.
- **Journal des exports** en option WP (date, taux, client, nombre de produits), 20 dernières entrées affichées sous le formulaire.

**Génération** (`inc/catalogue-pdf-pro.php`), en réutilisant `sapi_catalogue_pdf_new_mpdf()` et la CSS existante :

- Page de garde : « Tarif professionnel », « Établi pour X » (bloc masqué si le champ est vide), date d'export, date de validité. **Le taux n'apparaît nulle part** : le revendeur voit ses prix, pas la remise qu'on lui consent par rapport à un autre.
- Page mentions légales juste après, rendue depuis le champ libre (`wpautop` + `wp_kses` sur un jeu de balises sûr).
- Par produit : tableau de prix pleine largeur sous les vignettes, avant les caractéristiques. Colonnes **Variation / Prix pro HT / PVP conseillé TTC**.
- Filigrane discret « Tarif professionnel — confidentiel » sur les pages produit.
- Bump `SAPI_CATALOGUE_PDF_VERSION` (14 → 15). ⚠️ Si le Point 3 de la tâche « Corrections post-prod » (busting automatique par hash) est déjà fait, le bump manuel n'a plus lieu d'être, et le hash doit couvrir `catalogue-pdf-pro.php`.

**Cache** — le PDF pro ne passe **pas** par le cache public :
- clé propre `wp_hash(ids triés + stamp + version + taux + client + dates + mentions)`. Le texte des mentions **doit** entrer dans la clé, sinon une correction resservirait l'ancien fichier.
- même dossier protégé `uploads/catalogues/`, préfixe `pro-`
- purge automatique des `pro-*.pdf` de plus de 30 jours : ils portent des noms de clients
- pas de pré-génération : chaque sélection est quasi unique, le cache ne sert qu'au ré-export identique après correction d'une coquille

**Route** — **pas de route REST publique**. Un `admin_post_sapi_catalogue_pro_pdf` avec vérification de nonce et de capacité. La route publique existante `/sapi/v1/catalogue-pdf` ne change pas et reste sans prix.

**Temps de génération** : le diagnostic du Temps 2 (PHP 8.3, 512 Mo, `max_execution_time` 600 s, GD ok) conclut à une génération synchrone viable. Rester en synchrone. Relever les mêmes valeurs sur la prod avant le cherry-pick.

---

**Critères de succès :**
1. `curl` sur `/catalogue` → aucune occurrence de `€` ni de prix, **y compris dans les `<template>` de fiche technique**. Rendu identique à aujourd'hui.
2. `curl` sur `/catalogue-prix` → PVP TTC présents, **aucun prix HT, aucun taux**.
3. `/wp-json/sapi/v1/catalogue-pdf` → PDF toujours sans prix, inchangé.
4. `admin_post` pro appelé non connecté → refus.
5. Deux exports au même périmètre mais à taux différents → deux fichiers distincts, prix corrects dans les deux.
6. Un export à 8 produits ne contient que ces 8 produits, dans l'ordre du site, catégories vides absentes.
7. Modifier les mentions légales et ré-exporter à périmètre identique → le nouveau texte apparaît (cache bien invalidé).
8. Contrôle **au rendu du PDF, pas au XML** : rasteriser une page produit et lire les prix à l'œil (règle acquise sur le logo de la charte).
9. Cas limite d'arrondi vérifié : PVP se terminant par 9 €, taux à décimale.
10. `/catalogue-prix` absent du sitemap, `noindex` effectif.

---

### 📐 ADDENDUM (2026-08-24) — Gabarit PDF : le tableau de prix fait déborder la fiche sur une 2ᵉ page

**Constat :** avec la section prix ajoutée, chaque fiche produit déborde d'un peu sur une seconde page. Le bloc photo actuel (héro 167 × 100 mm + rangée de 3 vignettes 50 × 33 mm) consomme à lui seul ~137 mm des 265 mm utiles. Décisions de Robin ci-dessous.

#### A. Bande de 3 photos à hauteur fixe — **PDF PRO UNIQUEMENT**

Remplacer héro + vignettes par **une seule bande horizontale de 3 photos de taille identique**, pleine largeur de la carte, hauteur fixe.

Ordre imposé :
1. **La photo produit = l'image mise en avant WooCommerce** (`get_post_thumbnail_id()`). ⚠️ Ce n'est **pas** la première image de `gallery_ids` : la galerie du catalogue vient de `sapi_get_product_photo_ids_with_fallback()` et commence par une ambiance. Il faut donc aller chercher la featured image **explicitement**, et la dédoublonner de la suite si elle figure aussi dans la galerie.
2. La 1ʳᵉ photo d'ambiance
3. La 2ᵉ photo d'ambiance

**Repli** si moins de deux ambiances disponibles : compléter avec une **photo de détail**. Si le compte n'y est toujours pas, laisser l'emplacement vide plutôt que de répéter une image.

**Hauteur de bande : 65 mm** (voir le calcul de budget en D). Emplacements d'environ 55 × 65 mm, légèrement portrait, ce qui convient à des luminaires qui sont des objets verticaux.

⚠️ **Recadrage obligatoire.** `sapi_catalogue_pdf_img_tag()` ajuste en « contain » : à hauteur fixe, une portrait sort étroite et une paysage large, et la bande part en dents de scie. Recadrer les 3 images **au même ratio** dans `sapi_catalogue_pdf_image()`, qui repasse déjà par `wp_get_image_editor` (ajouter un `crop`, pas seulement un `resize`). Sans ça, ce n'est pas une bande.

#### B. Marges réduites — **LES DEUX PDF**

- Marges de page : `margin_top` / `margin_bottom` de 16 → **12 mm**, `margin_left` / `margin_right` de 15 → **10 mm**.
- Padding de `.prod-card` : `4.5mm 6mm` → **`3mm 4mm`**.

⚠️ Le pied de page vit dans la marge basse. Vérifier `margin_footer` après réduction : à 12 mm de marge basse avec un footer en 7,5 pt, ça passe, mais si le footer se fait rogner ou chevaucher le contenu, remonter `margin_bottom` à 14 plutôt que de bricoler le footer.

Gain : ~8 mm en hauteur, ~3 mm de padding, et la largeur utile passe de 180 à 190 mm.

#### C. Catégorie alignée en haut à droite, sur la ligne du nom — **LES DEUX PDF**

Aujourd'hui `.cat-tag` est un `div` au-dessus du nom (~4 mm perdus), et `prod-head` porte le nom à gauche + **`Réf. SKU` à droite**. La cellule de droite est donc déjà occupée.

⚠️ **Conflit à trancher, ne pas empiler les deux.** Le SKU figure **déjà dans le pied de page** de chaque fiche produit (`SetHTMLFooter`, `'Réf. ' . $p['sku']`). Donc : **retirer le `Réf.` de l'en-tête** et donner la cellule de droite à la catégorie. Le SKU reste lisible en pied de page, l'information n'est pas perdue.

Résultat : une seule ligne d'en-tête, nom à gauche (Montserrat + Square Peg, styles inline conservés), catégorie à droite dans le style `.cat-tag` actuel (8,5 pt bold, capitales, `#E35B24`), alignée en bas comme le nom.

#### D. Budget vertical après les trois changements

| Poste | Avant | Après (PDF pro) |
|---|---|---|
| Bloc photo | 137 mm | 65 mm |
| Marges page (haut + bas) | 32 mm | 24 mm |
| Padding carte | 9 mm | 6 mm |
| Ligne catégorie | ~4 mm | 0 (fusionnée) |

Environ **87 mm récupérés** sur la fiche pro, pour un tableau de prix qui en demande 25 au plus. La marge est confortable, c'est délibéré : elle absorbe les descriptions longues.

Sur le **PDF public**, le gain est d'environ 15 mm (marges + padding + ligne catégorie), sans changement de photos.

### ✅ ADDENDUM TRAITÉ (2026-08-24) — commits `a8d78d0` + `869e2cb`

**A, B, C faits.** Le gabarit de carte produit est extrait en fonction partagée `sapi_catalogue_pdf_product_card_html()` : les deux générateurs ne divergent plus que par le bloc photo et le tableau de prix. `sapi_catalogue_pdf_image()` gagne un argument `$crop` optionnel (recadrage centré via `wp_get_image_editor->resize(w, h, true)`, entrant dans le nom du fichier de cache image) ; sans lui la bande partait en dents de scie. Bande à 57 × 65 mm (l'addendum disait « environ 55 » — 57 remplit mieux la largeur utile de la carte). Ordre : `get_post_thumbnail_id()` lu **explicitement** puis dédoublonné, 2 ambiances, repli sur les détails, emplacement laissé vide sinon.

**Correction en cours de route sur le pied de page.** L'addendum avertissait du risque. Mesure sur le PDF réellement généré : la ligne de pied se pose à (`margin_footer` − 4,3 mm) du bord de feuille. Avec `margin_footer` à 7, elle tombait à **2,7 mm** — sous la zone non imprimable de la plupart des imprimantes. Correctif : garder les 9 mm par défaut (pied à 4,7 mm, exactement là où Robin l'avait validé) et descendre `margin_bottom` à **14** plutôt que 12. On perd 2 mm des 8 visés, sur une fiche qui en récupère ~87.

**Vérifié par machine sur test** (PDF public, catégorie **lampadaires** = le cas le plus dense) :
- **11 pages** = garde + Histoire + Deux bois + **7 lampadaires** + contact → **exactement une page par fiche, aucun débordement**.
- Marge gauche mesurée dans le flux : **15,9 mm avant → 10,9 mm après**. Le resserrement a bien pris.
- Pied de page : 2,7 mm en v16 → **4,7 mm en v17**, identique à l'ancien gabarit.
- 0 lien `/URI`, 0 occurrence de `€`/`EUR`/`prix`/`TTC`/`HT`. Le PDF public reste sans prix.

`SAPI_CATALOGUE_PDF_VERSION` : 15 → 16 → **17**.

**✅ TOUT VALIDÉ PAR ROBIN au rendu (2026-08-24) :**
- Bande de 3 photos : régularité conforme, et photo produit en position 1 = bien l'image mise en avant, pas une ambiance.
- **Toutes les fiches du tarif pro tiennent sur une page**, avec de la marge en plus. L'objectif de l'addendum est atteint.
- **PDF public re-validé** malgré son changement d'apparence (marges resserrées, en-tête sur une ligne, SKU retiré du haut et conservé en pied).

### 🎨 Retouches design du PDF pro (2026-08-24) — commit `703a1d5`, v18

Deux incohérences relevées par Robin sur la fiche du tarif pro :
1. **Titre du tableau de prix désaccordé** des titres de section de la fiche technique : `letter-spacing` 1px au lieu de .5px, `padding-bottom` 1mm au lieu de `0.4mm 0`. Déclarations désormais recopiées à l'identique depuis `.spec-block .sec`. ⚠️ **Recopiées et non héritées** : mPDF gère mal les sélecteurs descendants, on ne peut pas réutiliser la classe `.sec` dans `.pro-prices`. Un commentaire d'avertissement dans les deux feuilles rappelle que toute retouche de l'une doit être répercutée sur l'autre.
2. **Description déplacée AVANT le tableau de prix** — on présente l'objet, puis on le chiffre. L'argument du gabarit partagé devient `after_description` (au lieu de `after_photos`). Le filet en tête du tableau est remplacé par une marge haute de 3,5 mm : une respiration, pas une coupure.

Ordre final de la carte : en-tête → photos → description → prix → caractéristiques.

**Vérifié :** le PDF public n'est pas affecté (il passe une chaîne vide à `after_description`). Régénéré en v18, ses 33 flux de contenu décompressés ont la **même empreinte SHA-256** qu'en v17 — seules les métadonnées `CreationDate`/`ModDate` diffèrent. 11 pages, 0 lien, 0 prix.

### ✅ RÉSOLU — Bande photo du PDF pro refaite (commits `5635c03` + `f70c707`, v20 puis v21)

**Composition, d'après le schéma de Robin :** 3 grands carrés (photo produit = image mise en avant, puis 2 ambiances) + 1 colonne de 2 petits carrés (détail en haut, accessoire en bas). Toutes carrées, même hauteur, la bande occupe toute la largeur utile.

**Géométrie déduite, rien en dur** : `3S + 3g + s = W` avec `2s + g = S`, soit `S = (2W − 5g)/7`. À 181 mm utiles (A4 − marges − padding de carte) et 3 mm d'écart : grands carrés **49,57 mm**, petits **23,29 mm**, bande haute de 49,57 mm. Rapport grand/petit **2,13**, contre 2,12 sur le schéma de Robin (ses 52 mm supposaient les 190 mm de la page, sans le padding de la carte).

**Correctif de fond de l'étirement :** `sapi_catalogue_pdf_image()` retrouve sa forme d'origine (ne recadre plus, préserve le ratio). Le recadrage vit dans `sapi_catalogue_pdf_square_image()`, qui **n'utilise pas** `resize($w, $h, true)` — on découpe soi-même le plus grand carré disponible avec `crop()`, donc la sortie est carrée **par construction**, et les dimensions sont **relues** sur le fichier produit au lieu d'être affirmées. Cible plafonnée au côté disponible : jamais de suragrandissement.

**Point focal branché :** Robin a l'extension **Media Focus Point** (wpcompany v2.0.5), confirmée sur le serveur de test. Adaptateur `sapi_catalogue_focal_point_from_mfp()` accroché au filtre `sapi_catalogue_focal_point`, passant par `MFP_Background($id, false)` — l'API publique documentée depuis la v1.3 — et non par sa méta, qui casserait à la première mise à jour. Appel entouré d'un tampon de sortie : un `echo` de l'extension corromprait le flux binaire du PDF. Sans extension ou sans point focal posé, retour à 50/50 et recadrage centré.

**Règle des emplacements vides :** chaque emplacement affiche une photo de **son type** ou reste blanc, sans repli d'un type sur un autre (consigne Robin pour l'accessoire, appliquée à tous). À rediscuter si trop de colonnes de droite sortent vides.

**Vérifié :** PDF public inchangé (même empreinte de contenu en v19, v20 et v21 — la bande ne concerne que le PDF pro), pages publiques 200.

<details><summary>Diagnostic d'origine (archivé)</summary>

**Cause racine :** `WP_Image_Editor::resize($w, $h, true)` **n'agrandit jamais**. WordPress (`image_resize_dimensions()`, branche crop) renvoie `(min(cible_w, source_w), min(cible_h, source_h))` — pas la cible. Le recadrage part du format `large` (plafonné à 1024 px) et vise 570×650 : dès qu'une source fait moins de 650 px de haut, la sortie n'a pas le ratio demandé. `sapi_catalogue_pdf_image()` **affirme** ensuite les dimensions demandées (`$w = $crop['w']`) au lieu de lire celles obtenues, et le HTML force `width:57mm; height:65mm` → l'image est étirée pour remplir la case.

**Mesuré sur les 205 images de galerie du catalogue :** ratios réels de **0,56 à 2,13** ; **20 images sur 205** ne peuvent pas atteindre le ratio cible ; pire cas source 1024×481 → sortie 570×481 → **étirement de +35 % en hauteur**.

**Acquis, quelle que soit la mise en page retenue :** lire les dimensions **réelles** du fichier produit (`getimagesize` sur la sortie) et ne jamais imposer largeur ET hauteur sur une image dont le ratio n'est pas garanti. C'est cette double faute qui déforme, pas le choix de composition.

**Exigences Robin :** aucune déformation (rédhibitoire) ; rognage toléré mais ratio d'origine préféré ; **la première photo doit être carrée**. Trois compositions lui ont été proposées — il a répondu par un schéma, mis en œuvre ci-dessus.

</details>

### 📐 ADDENDUM 2 (2026-08-24) — Bande photo du PDF pro : disposition « 3 grands + rangée de 5 »

**Mesuré sur le PDF pro que tu as livré** (`tarifs-pro-atelier-sapi-test-4`, 31 pages, rasterisé) :
- Les fiches produit s'arrêtent entre **220,0 et 226,2 mm**. Avec `margin_bottom` à 14, il reste donc **56,8 mm de blanc** sous la fiche la plus dense. Il y a de la place pour une bande nettement plus haute.
- **L'emplacement « accessoire » est vide sur toutes les pages contrôlées.** La bande n'affiche que 4 images sur 5 : 3 grands carrés + 1 petit en haut à droite, et un blanc en bas à droite. La règle « chaque emplacement affiche une photo de son type ou reste blanc » produit ce trou en pratique, pas seulement en théorie. **Elle est remplacée ci-dessous.**

#### A. Nouvelle géométrie — décision Robin

Bande à **deux rangées**, plus de colonne latérale :
- **Rangée haute : 3 grands carrés.** `S = (181 − 2×3)/3` = **58,33 mm**
- **Rangée basse : 5 petits carrés.** `s = (181 − 4×3)/5` = **33,80 mm**
- Écart de 3 mm partout, **hauteur de bande = 95,13 mm**

Les grands passent donc de 49,6 à 58,3 mm : les photos principales grossissent **et** on gagne des emplacements (8 au lieu de 5).

⚠️ **Marge de sécurité réduite.** Le supplément est de 46,5 mm pour 56,8 mm disponibles : il ne reste que **~10 mm** sur la fiche la plus dense. **Vérifier le nombre de pages sur les 27 produits**, pas seulement sur un lampadaire. Si une fiche déborde, réduire la rangée basse plutôt que les grands.

#### B. Ordre de remplissage et types

Les types ne sont plus des réservations, ce sont des **préférences avec repli sur l'ambiance**. Une case ne reste jamais vide parce que la photo de son type manque.

| Position | Type préféré | Repli |
|---|---|---|
| 1 (grand) | photo produit = `get_post_thumbnail_id()` | ambiance |
| 2, 3 (grands) | ambiance | détail |
| 4, 5, 6 (petits) | ambiance | détail |
| 7 (petit) | **détail** | ambiance |
| 8 (petit, dernier) | **accessoire** | ambiance |

Détail et accessoire restent **groupés en fin de rangée**, dans cet ordre. Dédoublonnage global : une même image n'apparaît jamais deux fois dans la bande.

#### C. Moins de 8 photos — rangée alignée à gauche (décision Robin)

Les petits carrés **gardent leurs 33,80 mm** et la rangée se remplit **de la gauche vers la droite**, laissant le blanc à droite. Pas de recentrage, pas d'agrandissement (agrandir ferait monter la bande à 104 mm, au-dessus du budget).

Conséquences à coder explicitement :
- « L'accessoire est en dernier » signifie **dernière case affichée**, pas case n°8. À 6 photos, la rangée basse compte 3 cases : ambiance, détail, accessoire.
- **Zéro petit disponible → la rangée basse disparaît** et la bande retombe à 58,33 mm. On ne laisse pas une rangée vide.
- Moins de 3 photos au total : les grands s'alignent aussi à gauche, même règle, sans redimensionnement.

#### D. Photo produit — la faire exister visuellement

Sur le rendu, la case 1 est un détourage sur blanc posé sur le fond crème de la carte (`#fffdfb`), sans bordure : **elle se lit comme un vide, pas comme une photo**. Ajouter un filet clair (le `#ece2d3` de la carte convient) autour de **cette case uniquement**, ou un fond très légèrement grisé. Les autres cases n'en ont pas besoin, leurs photos portent leur propre cadre.

#### E. Vérification

- **Compter les pages sur les 27 produits.** Une seule fiche à 2 pages invalide la disposition.
- Contrôle **au rendu rasterisé** : aucune case vide sur un produit qui a 8 photos ou plus, blanc uniquement en fin de rangée basse, et aucune image répétée dans une même bande.
- Vérifier qu'un produit sans photo de détail ni d'accessoire affiche bien 8 ambiances et **aucun trou**.
- Acquis à ne pas perdre : ratio préservé, recadrage carré par `crop()` avec dimensions **relues** sur le fichier produit, point focal via `MFP_Background()`.
- Le **PDF public ne change pas** : la bande ne concerne que le PDF pro. Contrôler que son empreinte de contenu reste identique, comme aux versions précédentes.
- Bump de `SAPI_CATALOGUE_PDF_VERSION`.

---

**🛑 PAS DE CHERRY-PICK VERS `master` POUR L'INSTANT — décision Robin.** D'autres modifications sont à venir sur le catalogue. Tout reste sur `test-theme-sapi-maison`. Au moment du passage en prod, ne pas oublier : la page `/catalogue-prix` est du contenu en base, **à recréer à la main** sur atelier-sapi.fr, avec son exclusion du sitemap Yoast.

---

#### E. Vérification

- **Cas le plus dense à tester en priorité : un lampadaire.** C'est la catégorie qui porte le plus de lignes de caractéristiques (`hauteur_totale`, `hauteur_ampoule`, `interrupteur` en plus du tronc commun), avec la description la plus longue disponible. Si celui-là tient sur une page, les autres tiennent.
- **Contrôle au rendu rasterisé, pas au XML** : compter les pages, et vérifier à l'œil que la bande de 3 photos est bien régulière (mêmes largeurs, pas de dents de scie).
- ⚠️ **Le PDF public change d'apparence** (marges + en-tête). Il est en prod et validé : le faire re-valider par Robin au rendu avant le cherry-pick vers master.
- Bump de `SAPI_CATALOGUE_PDF_VERSION` obligatoire, le gabarit des deux PDF change.

---

### 🚧 AVANCEMENT — Lots 1+2 faits sur `test-theme-sapi-maison` (2026-08-24)

Robin a donné le go pour **les lots 1+2 seulement** : il veut voir la page avant que je parte sur l'admin. **Lot 3 (admin + PDF pro) en attente de sa validation visuelle.**

**Fait :**
- `inc/catalogue-data-pro.php` (NEW) — arrondis, `sapi_catalogue_ht_from_ttc($ttc, $rate)` (taux en argument), `sapi_catalogue_product_pricing()`, `sapi_catalogue_normalize_product_priced()`, `sapi_catalogue_get_products_priced()`, `sapi_catalogue_format_price()`, groupe ACF `group_catalogue_prix`.
- `inc/catalogue-data.php` — SEULE modification : argument optionnel `$include_ids` (additif). La garantie « zéro prix » de la couche est intacte.
- `page-catalogue.php` — `$show_prices` lu une fois, « À partir de X € TTC » sur la carte, tableau Variation/Prix TTC dans la fiche technique, bloc mentions légales, bloc PDF masqué quand le flag est actif.
- `assets/catalogue.css` — classes `cat-price-*` portées par `body.has-prices`.
- `inc/catalogue-pdf.php` — correctif `sapi_catalogue_page_id()` (voir ci-dessous).

**Deux pièges traités, tous deux signalés par Robin :**
1. **`sapi_catalogue_page_id()` serait tombé dès la création de `/catalogue-prix`.** `get_posts()` sans `orderby` retombe sur date DESC → la page la plus récente aurait gagné, et le PDF public aurait lu les ACF Histoire/Bois de la page prix. Corrigé par `meta_query` avec branche **`NOT EXISTS`** (indispensable : la page `/catalogue` a été créée avant l'existence du champ, elle n'a aucune ligne de meta, un `!=` seul ne matche pas une meta absente) + `orderby ID ASC`.
2. **`floor()` nu sur `ttc / 1.20 * (1 - taux)` perd un euro.** Corrigé en `floor(round($x, 6))`. Vérifié par balayage (PVP 20→600 € pas 0,10 × taux 0→50% pas 0,1 pt, ~2,9 M de cas, référence en rationnels exacts) : **82 divergences avec `floor()` nu, 0 avec le correctif.** Jeu de test du critère n°9, au taux par défaut de 30% : PVP 108 → 63, 204 → 119, 216 → 126, 396 → 231, 408 → 238, 420 → 245, 432 → 252.

**Décisions prises avec Robin :**
- Bloc « Télécharger en PDF » **masqué** sur `/catalogue-prix` (l'endpoint public produit un PDF sans prix, incohérent). Un PDF prix public reste possible plus tard : même gabarit que le tarif pro, colonne HT retirée.
- Champ ACF `catalogue_mentions_legales` laissé **vide par défaut**. Le texte du brief est celui du **PDF pro** (HT, MOQ, confidentialité) et ne doit pas être recyclé sur une page TTC grand public — Robin le remplira.
- PVP de référence = **prix régulier** (repli sur le prix actif) : une promo temporaire ne doit pas devenir la base d'un tarif revendeur.
- **Essences exposées = Peuplier et Okoumé uniquement** (décision Robin). La base contient une 3ᵉ essence, « Peuplier teinté noir » (+50 € sur Vincent l'incandescent), que `/catalogue` n'a jamais affichée : `sapi_catalogue_product_essences()` codait en dur deux libellés et ignorait le reste en silence. On garde ce périmètre. La table est extraite dans `sapi_catalogue_essence_labels()` (source unique) et la couche prix s'y aligne — sans ce filtre, `/catalogue-prix` aurait affiché un prix sur un bois que la fiche technique juste en dessous ne mentionne pas.

**Constaté sur test** (JSON des variations des fiches produit) : les prix **varient fortement selon l'essence** — Vincent l'incandescent, 18x33cm : Peuplier 85 € / Okoumé 105 €. Le tableau croisé taille × essence est donc nécessaire, pas de simplification possible. Aucune promo en cours (prix régulier = prix actif partout). Détail cosmétique sans gravité : les libellés de `pa_taille` sont irréguliers (« 50 cm », « 70cm », « 90 cm ») — à uniformiser dans les attributs WooCommerce si Robin le souhaite, ça n'a aucun impact sur le code.

**✅ LOTS 1+2 VALIDÉS PAR ROBIN sur test (2026-08-24).** Page `/catalogue-prix` créée par Robin sur test.atelier-sapi.fr, 27 produits, 27 tableaux de prix, `noindex` effectif, bloc PDF absent, `has-prices` actif. `/catalogue` re-audité après coup : **0 occurrence de `€`**, ligne « Bois » inchangée — la non-régression tient.

**Itération visuelle demandée et livrée** : le tableau des prix est passé de la liste « une ligne par combinaison » à un **tableau croisé** (essences en lignes, dimensions en colonnes). Ex. Vincent l'incandescent : Peuplier 85/110/115 €, Okoumé 105/135/135 € pour 18x33 / 25x40 / 32x33 cm. Sur les 27 produits : 0 case vide, toutes les combinaisons existent en base. Trois choix intégrés : lignes ordonnées par la table du catalogue (Peuplier puis Okoumé) et non par ordre de création des variations ; combinaison absente = « — » (jamais un prix déduit par symétrie) ; défilement horizontal confiné au tableau, la page ne part jamais en travers sur mobile.

**Commits sur `test-theme-sapi-maison`** : `cb5c884` (lots 1+2), `d8c5295` (filtre essences), `0ba0ca9` (tableau croisé).

---

### 🚧 LOT 3 livré sur `test-theme-sapi-maison` (2026-08-24) — commit `111b126`

`inc/catalogue-pdf-pro.php` (génération, cache, route) + `inc/catalogue-pro-admin.php` (formulaire). `SAPI_CATALOGUE_PDF_VERSION` 14 → 15.

**Conforme au brief :** page de garde sans jamais le taux, page mentions, une page par produit avec tableau Variation / Prix pro HT / PVP conseillé TTC, filigrane « Tarif professionnel — confidentiel » sur les pages produit seulement. Sélecteur produit à nom unique par case (`produits[<id>]`, piège WAF o2switch de la Tâche 5), « tout / rien » par catégorie, compteur, export refusé à zéro produit, persistance sauf « Établi pour », garde-fou > 35%, journal des 20 derniers exports. Cache à clé propre incluant le hash des mentions, préfixe `pro-`, purge à 30 jours, pas de pré-génération. Route `admin_post` gardée par capacité + nonce, **aucune route REST ajoutée**.

**Vérifié sur test après déploiement :**
- `/catalogue` 200, **0 occurrence de `€`** ; `/catalogue-prix` 200. Aucun fatal.
- `admin_post` pro **non connecté → refusé** (404, aucun PDF servi). Page admin non connectée → redirection vers `wp-login`.
- Endpoint public `/wp-json/sapi/v1/catalogue-pdf` : 200 `application/pdf`, régénéré après le bump de version, audit des flux décompressés = **16 pages, 0 lien `/URI`, 0 occurrence de `€`/`EUR`/`prix`/`TTC`/`HT`**. Le PDF public reste strictement sans prix.

**⏳ Reste à faire par Robin (nécessite une session admin, je ne peux pas la simuler) :** lancer un export depuis `Produits > Catalogue PRO` et contrôler **au rendu du PDF, pas au XML** (règle acquise sur le logo de la charte). Valeurs attendues pour Vincent l'incandescent au taux par défaut de 30% — 18x33cm : Peuplier 49 € HT / 85 € TTC, Okoumé 61 € HT / 105 € TTC ; 25x40cm : 64 / 110 et 78 / 135 ; 32x33cm : 67 / 115 et 78 / 135. Vérifier aussi : deux exports à taux différents donnent deux fichiers distincts, un export à 8 produits ne contient que ces 8 produits dans l'ordre du site, et une correction des mentions suivie d'un ré-export au même périmètre fait bien apparaître le nouveau texte (invalidation du cache).

**Itération Robin (commit `1b7df05`)** : le PDF pro reprend désormais les **pages d'intro du PDF public** (Histoire de l'atelier, Deux bois au choix) et la page mentions porte le titre **« Tarif professionnel »**, mis en page comme elles. Les blocs Histoire/Bois ont été **extraits en helpers partagés** (`sapi_catalogue_pdf_intro_fields()` / `_histoire_html()` / `_bois_html()`) plutôt que recopiés — un seul gabarit, une seule source ACF, lue sur la page sans prix. Vérifié : les 20 fragments de balisage sont identiques et dans le même ordre qu'avant extraction ; le PDF public reste servi à l'octet près. Ordre du document : garde → Histoire → Deux bois → Tarif professionnel → produits.

⚠️ Le titre « Tarifs professionnels » en tête du texte par défaut des mentions a été retiré (doublon avec le titre de page). Si Robin a déjà lancé un export avant cette itération, sa version enregistrée contient encore cette ligne : à supprimer à la main dans le champ.

**Questions toujours ouvertes pour Robin :** (a) cherry-pick vers `master` maintenant ou à la fin ? (b) rappel — la page `/catalogue-prix` devra être **recréée à la main en prod** (contenu en base, pas en code), ainsi que son exclusion du sitemap Yoast. (c) le PDF pro n'a pas de page contact finale (le brief ne la prévoyait pas) — à confirmer.

---

## 📨 RETOUR À COWORK — Catalogue prix + Tarifs professionnels : LIVRÉ SUR TEST ✅ (2026-08-24)

**Statut : terminé et validé par Robin sur `test.atelier-sapi.fr`. VOLONTAIREMENT PAS EN PROD** — Robin a d'autres modifications du catalogue à demander, le passage sur `master` attendra qu'elles soient faites.

### Ce qui existe maintenant

Trois objets, trois niveaux de confidentialité, exactement comme le brief le prévoyait :

| Objet | Contenu | Qui y a accès |
|---|---|---|
| `/catalogue` | zéro prix | public — **strictement inchangé**, audité après chaque modification |
| `/catalogue-prix` | prix publics TTC par variation | public, `noindex`, lien à transmettre à la main |
| PDF « Tarifs professionnels » | prix revendeur HT remisés + PVP conseillés | Robin seul, depuis l'admin |

**Le point d'architecture à retenir, y compris côté commercial : le tarif remisé n'existe nulle part sur le web.** Aucune page, aucun lien secret, aucune adresse à protéger. Le taux de remise est saisi par Robin au moment de générer le PDF et disparaît ensuite. Conséquence directe : un tarif à ‑12 % pour un revendeur historique et un autre à ‑30 % pour un prospect de salon, sans que l'un puisse jamais deviner l'existence de l'autre, et sans que le site ait à savoir qui est qui.

### Comment Robin s'en sert

`wp-admin` → **Produits → Catalogue PRO**. Il règle le taux, coche les produits à inclure, saisit éventuellement « Établi pour [nom du revendeur] », et le PDF se télécharge.

Détails utiles à connaître pour l'accompagner :
- **« Établi pour » repart toujours vide** à chaque ouverture. C'est délibéré : on n'envoie jamais à Ankorstore un tarif encore au nom de Muse. Tout le reste (taux, sélection de produits, dates, mentions) est mémorisé d'un export à l'autre.
- **Au-delà de 35 % de remise**, une confirmation est demandée avant génération.
- **Un journal des 20 derniers exports** (date, client, taux, nombre de produits, auteur) s'affiche sous le formulaire. C'est la réponse le jour où un revendeur affirme qu'on lui avait promis autre chose.
- Le taux **n'apparaît nulle part** dans le document produit.

### ⚠️ Ce qui reste à faire, et qui n'est pas du code

1. **Compléter les mentions légales.** Le texte du brief est pré-rempli dans l'admin, mais cinq crochets `[à compléter]` attendent : minimum de commande, frais de port, délai de fabrication, conditions de règlement, capital social. Tant qu'ils sont là, ils partiront tels quels chez le revendeur. C'est à remplir **une seule fois**, la valeur est ensuite conservée.
2. **Vérifier la cohérence des PVP avant le premier envoi.** Le tarif pro affiche le prix public conseillé à côté du prix d'achat. Si le prix du site et celui d'Etsy divergent aujourd'hui sur un modèle, le PDF expose l'écart au revendeur. Le brief le signalait déjà — ça n'a pas été vérifié.
3. **Le jour du passage en prod** : la page `/catalogue-prix` est du **contenu en base, pas du code**. Elle devra être recréée à la main sur `atelier-sapi.fr` (template « Catalogue B2B », slug `catalogue-prix`, bouton « Afficher les prix publics » activé), avec son **exclusion du sitemap Yoast** — sinon elle cannibalise les fiches produit, dont elle reprend les descriptions.

### Constats de terrain, potentiellement utiles côté business

- **Les prix varient fortement selon l'essence** : sur Vincent l'incandescent, 85 € en peuplier contre 105 € en okoumé pour la même taille, jusqu'à 80 € d'écart sur certaines lignes. Le tableau des prix croise donc taille × essence.
- **Une troisième essence existe en base, « Peuplier teinté noir » (+50 €), que `/catalogue` n'a jamais affichée.** Décision de Robin : on garde ce périmètre à deux bois. À reconsidérer si elle devient commercialement significative — c'est un choix business, plus un oubli technique.
- **Aucune promotion en cours** sur le catalogue au moment du développement. Le tarif se base sur le prix régulier, pas sur le prix actif : une promo de saison ne contaminera jamais un tarif annuel envoyé à un revendeur.

### Idée pour Cowork

La mécanique est prête, il manque le commercial. Cowork peut préparer un **message-type d'envoi du tarif à un revendeur** (Muse, Ankorstore, prospects salons), et un second pour la page `/catalogue-prix` à destination des décorateurs qui veulent les prix publics. Le lien de la page se partage à la main, elle n'est ni indexée ni liée depuis le site.


---

### ✅ ADDENDUM 2 TRAITÉ (2026-08-24) — commit `5f69e89`, v22

Géométrie conforme au calcul de l'addendum : grands **58,33 mm** `(W−2g)/3`, petits **33,80 mm** `(W−4g)/5`, bande **95,13 mm**, les deux rangées faisant exactement 181 mm. Supplément **+45,56 mm** pour 56,8 mm disponibles.

Ordre de remplissage vérifié par simulation sur 7 cas : à 8 photos les deux dernières cases sont détail puis accessoire ; **à 6 photos la rangée basse donne bien ambiance / détail / accessoire** (l'exemple de l'addendum) ; sans détail ni accessoire, 8 ambiances et aucun trou ; à 3 photos la rangée basse disparaît ; dédoublonnage effectif.

Filet `#ece2d3` sur la seule photo produit, épaisseur retranchée de l'image pour que la case garde son côté exact.

**⏳ Vérification du nombre de pages : impossible de mon côté**, la génération du tarif pro demande une session admin. Projection à partir de la mesure de Robin (fiche la plus dense du PDF pro à 226,2 mm) : **271,8 mm attendus pour une limite à 283 mm**, soit ~11 mm de marge. Mesuré sur le PDF public, l'écart entre la fiche la plus dense et la 4ᵉ n'est que de 5,5 mm : les fiches sont très homogènes, si la plus dense passe les autres passent. **Fiches à contrôler en priorité : LÉON (la plus dense), puis DALIDA, VINCENT et MYRIAM.**

---

### 🔴 À CORRIGER — deux constats faits en auditant le PDF (2026-08-24)

**1. Du balisage HTML s'affiche en toutes lettres dans « Poids » — EN PRODUCTION.**
Cinq fiches du catalogue prescripteurs affichent littéralement `<p>35 Kilos le matin<br /> 22 le soir</p>` dans la ligne Poids : **Olivia La gardiena (×2), Charlie Le pissenlit, Claudine La turbine, Vincent L'incandescent**. Vérifié sur `atelier-sapi.fr/catalogue` **et** sur test. C'est visible sur la page, dans le PDF public et dans le tarif pro.
Deux problèmes distincts : (a) le contenu du champ ACF `poids` est une valeur de test, à corriger côté Robin ; (b) `sapi_catalogue_get_product_specs()` ramène la valeur brute et le rendu l'échappe, donc toute mise en forme saisie dans un champ de caractéristique ressortira en balises visibles. Correctif proposé : `wp_strip_all_tags()` sur les valeurs de specs. **Non fait, en attente du go de Robin** (ça touche `/catalogue`, qui est gelé).

**2. ⚠️ Mes audits « 0 occurrence de € » sur les PDF ne prouvaient rien.**
mPDF sous-ensemble ses polices : les flux de contenu ne contiennent que des index de glyphes, aucun texte en clair. Chercher la chaîne `€` dans un flux décompressé ne pouvait donc jamais rien trouver, quel que soit le contenu réel du document. Restaient valables : le contrôle des annotations `/URI`, le comptage de pages, et les comparaisons d'empreintes entre versions.
**Corrigé :** un extracteur passant par les CMap `/ToUnicode` embarquées a été écrit (`scratchpad/pdftext.py`). Sur le PDF public complet : 33 423 caractères extraits, « Culot » 27 fois et « Peuplier » 28 fois — soit bien les 27 fiches, l'extraction fonctionne — et **0 occurrence de `€`, `Prix`, `TTC`, `HT`**. L'étanchéité du PDF public est donc désormais **réellement** vérifiée, et non plus supposée. À réutiliser pour tout audit futur.


---

### ✅ PDF PUBLIC — bande photo « 2 grandes + 4 moyennes » (2026-08-24, commit `b7cab95`, v23)

Demande de Robin après constat que les fiches publiques descendaient au pire à 259,6 mm, laissant 64 à 68 mm de blanc — presque un quart de page.

**Contrainte structurante rappelée par Robin :** la taille des carrés n'est pas libre, elle est imposée par la largeur. Pour occuper de la hauteur, on met **moins d'images par rangée**, jamais des images plus grandes.

| Profil | Rangée haute | Rangée basse | Bande |
|---|---|---|---|
| `pro` | 3 × 58,33 mm | 5 × 33,80 mm | 95,13 mm |
| `public` | 2 × **87,87 mm** | 4 × **42,20 mm** | **133,53 mm** |

Largeurs utiles et écarts **mesurés par Robin sur le rendu** (179,2 mm et 3,46 mm côté public), pas déduits. ⚠️ Le profil pro garde 181/3, valeurs sous lesquelles sa bande a été validée à l'œil : **l'écart de 1,8 mm entre les deux mesures reste à trancher**, il n'a pas été touché.

**Les deux PDF divergent volontairement** — les publics ne sont pas les mêmes, ne pas chercher à les unifier. Le moteur, le recadrage carré au point focal, le dédoublonnage et les règles de repli restent partagés ; seuls le nombre de cases par rangée et l'ordre de remplissage changent, via un profil.

**Ordre public :** 2 ambiances en grand, puis packshot, ambiance, détail, accessoire. La fiche s'ouvre sur des mises en situation et non sur un détourage — c'est un document qui doit séduire. Le filet clair suit désormais le **packshot où qu'il atterrisse** (case 1 en pro, case 3 en public), et non plus la première case.

**Correctif trouvé en simulant :** les réserves de fin sont servies sur leur type **strict**, sans repli, et repliées seulement une fois les cases de tête pourvues. Sinon un produit sans accessoire voyait le repli lui prendre la **première** ambiance, reléguée en dernière petite case au lieu d'ouvrir la fiche en grand. Vérifié sur 8 cas, profils pro et public, sans régression du pro.

**Mesuré sur test après déploiement :** 11 pages sur les lampadaires, soit **une fiche par page, aucun débordement**. Bas du contenu 262,2 → **259,1 mm**, 23,9 mm de dégagement sous le plancher de 283. Poids du catalogue complet 3,91 → **6,21 Mo** (+59 %, six images carrées au lieu de quatre en « contain ») — **très loin du plafond de 25 Mo**. Étanchéité revérifiée avec l'extracteur `/ToUnicode` : 33 423 caractères, 0 occurrence de `€`, `Prix`, `TTC`, `HT`.

L'ancien gabarit « grande photo + 3 vignettes » et ses règles CSS ont été retirés — plus rien ne les appelait, git les conserve.

**⏳ Reste : la seconde revalidation du PDF public au rendu par Robin**, qu'il assume.


---

### ✅ Deux photos de détail dans les deux PDF (2026-08-24, commit `a3f57f2`, v24)

Demande de Robin, avec repli sur ambiance. Le nombre **total** de cases étant imposé par la géométrie, la 2ᵉ case de détail prend la place d'une ambiance dans chaque profil :

| Profil | Composition |
|---|---|
| `pro` | packshot + **4** ambiances + **2 détails** + 1 accessoire (était 5 ambiances + 1 détail) |
| `public` | 2 ambiances en grand + packshot + **2 détails** + 1 accessoire (était 2 ambiances + packshot + 1 ambiance + 1 détail) |

**Correctif trouvé en simulant :** le repli d'une réserve restée vide se pose **à sa place** dans la séquence, plus en fin. Sinon un produit avec une seule photo de détail voyait l'ambiance de repli passer **après** l'accessoire, alors que celui-ci doit rester la dernière case affichée (règle de l'addendum 2 : détail et accessoire groupés en fin, dans cet ordre). Vérifié sur 7 cas × 2 profils : avec 2 détails, avec 1 seul, sans aucun, sans détail ni accessoire — l'accessoire termine toujours la séquence quand il existe.

**Vérifié sur test :** 11 pages sur les lampadaires (une fiche par page), 0 lien `/URI`, et étanchéité confirmée à l'extracteur `/ToUnicode` — 0 `€`, 0 `Prix`, 0 `TTC`, 0 `HT`, « Culot » 7 fois pour 7 lampadaires.

**⏳ En attente de Robin :** (1) revalidation au rendu des DEUX PDF — le pro n'a pas encore été vu dans sa version « 3 grands + 5 petits », contrôler LÉON en priorité ; (2) l'écart de 1,8 mm entre les deux largeurs utiles (179,2 mesuré vs 181 en constante côté pro) ; (3) le champ « Poids » qui affiche du balisage en prod ; (4) le cherry-pick vers `master`.


---

## 🚀 CHERRY-PICK PROD FAIT (2026-08-24) — `master` = commit `a92516c`

**⚠️ Robin avait dit « main » : `origin/main` est ABANDONNÉE** — 2 commits, restés au premier import, 2707 commits derrière. La branche de prod est bien **`master`**, conformément au CLAUDE.md. C'est elle qui a reçu le cherry-pick.

**Un seul commit par-dessus `master`**, même pattern que `113a1ef` — surtout **pas** un merge de `test-theme-sapi-maison`, qui aurait embarqué immersion, room-picker, Tâche 5, emails WooCommerce et front-page, non validés pour la prod.

**8 fichiers, 1952 insertions.** Les 7 fichiers catalogue sont repris **à l'octet près** depuis test (vérifié par diff). `functions.php` ne cède que **13 lignes de `require`, purement additives** (0 suppression) — ses 806 lignes d'écart avec test appartiennent à d'autres chantiers. Vérifié avant push : aucun fichier hors catalogue dans le diff, 0 commit perdu.

**⏳ Ce qui reste à faire par Robin, dans l'ordre :**
1. **Lancer le workflow GitHub Actions « Deploy to Production »** — il est en `workflow_dispatch` uniquement, le push sur `master` ne déploie donc RIEN tout seul. L'étape Composer y régénère `vendor/` (mPDF), qui est gitignoré.
2. **Créer la page `/catalogue-prix` en prod** : c'est du contenu en base, pas du code. Template « Catalogue B2B », slug `catalogue-prix`, champ « Afficher les prix publics » activé. Puis l'exclure du sitemap Yoast.
3. **Compléter les mentions légales** dans `Produits > Catalogue PRO` — les cinq crochets `[à compléter]` partiraient tels quels chez un revendeur.
4. **Vérifier `atelier-sapi.fr/catalogue`** : rendu inchangé, et le PDF public qui, lui, change d'apparence.

**Restent ouverts, sans urgence :** l'écart de 1,8 mm entre les deux largeurs utiles (179,2 mesuré vs 181 en constante côté pro) ; et le champ « Poids » de 5 fiches qui affiche `<p>35 Kilos le matin</p>` en toutes lettres — le contenu est à corriger côté Robin, et un `wp_strip_all_tags()` sur les valeurs de specs reste à décider.


---

### ✅ POIDS — retiré de la fiche technique, porté par la matrice de prix (2026-08-25, commit `8acd8c8`, v25)

**Le point de départ était un faux diagnostic de ma part.** J'avais dit à Robin d'aller corriger le champ « Poids » dans l'admin produit. **Il n'existe pas** : le champ ACF `poids` a été retiré des groupes de champs, mais la valeur est restée en base. `get_field('poids')` la lit toujours alors que plus aucun écran ne permet de l'éditer — visible en façade, introuvable en coulisses.

**Et le problème était bien plus large que les 5 blagues.** Audit de la ligne « Poids » sur les 36 fiches en prod : **28 affichaient « — »**, 5 la blague, 3 un poids correct. Cause : le repli interrogeait `$product->get_weight()` sur le produit **parent**, qui n'a jamais de poids sur un variable. Le code cherchait au seul endroit où l'information ne se trouve pas — alors qu'elle est parfaitement renseignée sur les variations (Olivia : 12/12, de 315 g à 2,01 kg).

**Décision Robin :** le catalogue sans prix n'affiche plus de poids **du tout** ; le poids ne réapparaît que là où il y a des prix.

1. **Ligne « Poids » supprimée** des quatre surfaces. `sapi_catalogue_product_weight()` supprimée, plus aucun appelant. La meta orpheline n'est plus jamais lue par la couche catalogue.
2. **Le poids vient des variations** et rejoint le prix : prix et poids varient sur **exactement les mêmes axes** (taille × essence), la matrice de prix est donc son domicile naturel — aucun tableau supplémentaire, aucune fourchette approximative, précision totale.
   - `/catalogue-prix` : sous le prix dans chaque case, en second rang typographique.
   - PDF pro : une 4ᵉ colonne « Poids ». Pour un revendeur, le poids conditionne le port.
3. `sapi_catalogue_format_weight()` : grammes sous le kilo, kilos au-delà.

**Vérifié sur test :** `/catalogue` → 0 ligne Poids, 0 « Kilos le matin », 0 poids affiché. `/catalogue-prix` → 141 poids dans les matrices. Vincent : `Peuplier 60 € / 290 g · 80 € / 480 g · 105 € / 800 g · 145 € / 1,20 kg`.

**⚠️ DEUX SUITES À TRAITER**

**(a) La blague reste sur les pages produit publiques.** `woocommerce/single-product.php:777` lit la même meta orpheline pour sa propre fiche technique (ligne 829). Vérifié en prod : `atelier-sapi.fr/mes-creations/olivia-la-gardiena` et `claudine-la-turbine` affichent toujours `<p>35 Kilos le matin</p>`, balises comprises. **Hors périmètre catalogue, pas corrigé.** Même correctif possible (lire les variations), mais c'est un fichier du site marchand, à décider séparément.

**(b) Un second cherry-pick vers `master` est nécessaire.** `master` porte déjà le catalogue depuis `a92516c`, donc la blague part en prod avec le catalogue tant que ce correctif n'est pas repris. Fichiers concernés : `inc/catalogue-data.php`, `inc/catalogue-data-pro.php`, `inc/catalogue-pdf.php`, `inc/catalogue-pdf-pro.php`, `page-catalogue.php`, `assets/catalogue.css`. `functions.php` n'est PAS concerné cette fois.

