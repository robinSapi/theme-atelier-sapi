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

## 🖥️ RECETTE DESKTOP VALIDÉE PAR ROBIN (2026-08-25) — refonte /mes-creations/ + filtrage Conseiller

Robin a passé le parcours complet sur `test.atelier-sapi.fr` en **desktop : tout passe**. Cela lève les 3 points qui restaient en attente de validation depuis juin :
1. **4a + 4b** — room-picker serveur en état A + suppression du filtrage JS (`73d365f`, `682a82a`) ✅
2. **Commentaire IA en fin de modale** — loader 3 points puis texte tapé dans la phrase de l'immersion (`a5cffcc`, `332f1f8`, `0d2c0bd`) ✅
3. **Fondu doux du slider au moment 2** — fin du « flash » signalé par Robin (`664e9d5`) ✅

Vérifié aussi : question « table » absente du parcours (T2), règle cuisine (ni lampe à poser ni lampadaire), admin *Règles de filtrage* + aperçu live == site, reset OK, home et catalogue sans régression.

**⏳ RESTE : la recette MOBILE** (en cours côté Robin). Points de vigilance identifiés en lisant le CSS, à confirmer sur appareil réel :
- `.mescreations-immersion { height: 100vh }` (style.css l.24683) et `.mescreations-immersion-track { height: 250vh }` (l.24675) — **pas de `dvh`**. Sur iOS Safari, `100vh` ignore la barre d'URL → risque de bas de bloc coupé, et le recalcul de `vh` quand la barre se rétracte peut faire sauter le scroll-pinning (`--reveal`). Le projet utilise déjà `height:100vh; height:100dvh` ailleurs (`.mobile-menu-overlay`) — pattern à reprendre si confirmé.
- Scroll lock pendant la machine à écrire = `overflow:hidden` sur html+body (`sapi-mescreations-immersion.js` l.333-335). Connu pour être inopérant au toucher sur iOS ; filet de sécurité 9 s présent (l.349).
- Slider horizontal (cards à 82 % sous 600px) vs scroll vertical de la page pendant le pinning : conflit de geste possible. Flèches masquées si pas d'overflow (l.182-188).

**Pas de cherry-pick vers master avant le feu vert mobile de Robin.**

### 📱 RECETTE MOBILE — 1ʳᵉ passe (2026-08-25) : 4 constats, 4 correctifs livrés sur test

**Validé d'emblée sur iPhone :** scroll bien bloqué pendant la machine à écrire ; révélation au scroll **fluide, sans saut** (le calcul `--reveal` via `window.innerHeight` encaisse bien la barre d'adresse) ; swipe horizontal du slider nickel ; texte lisible.

**Corrigé — commits `fadfb5c` (CSS) + `41cdb9c` (JS) :**

1. **Indice du bas caché par la barre d'adresse iOS.** Ancrages bas décalés de `--safe-bottom: calc(100lvh - 100svh)` = exactement la hauteur de la barre, et `0` partout ailleurs (desktop, barre rétractée). **⚠️ Ne PAS passer le bloc en `100svh`** : il serait plus court que le viewport barre rétractée → bande de fond visible sous le hero. Le bloc reste en `100vh`, seuls les ancrages bougent.
2. **Room-picker de `/mes-creations/` trop gros en mobile.** Trois règles à spécificité `0,2,0` écrasaient les réglages mobiles de la home (`0,1,1`) : cartes en 2 colonnes au lieu de 3, titre `clamp(1.6rem…)` = 25,6px au lieu de 20px, padding vertical `clamp(40px…)`. Bloc mobile aligné sur la home, breakpoint porté de 600 à **768px** (celui de la home). **RÈGLE : un hero qui réutilise des classes globales doit reprendre AUSSI leurs médias mobiles, sinon sa spécificité les annule.**
3. **Densité du slider (mobile + desktop).** Trois causes mesurées sur 390×844 : (a) la phrase tombait sur le **minimum** du clamp desktop — `3.6vw` ne vaut que 14px sur 390px, donc `1.85rem` = 29,6px → ~10 lignes, la moitié de l'écran, collision avec le titre de la sélection ; (b) les flèches étaient **dans le flux flex** : 2×36px + 2×10px = **92px, 24 % de la largeur** → il ne restait que ~27px de la card suivante (d'où la mince bande sur la capture) ; (c) cards à ~60 % texte / 40 % photo. → phrase en `clamp(1.15rem, 5vw, 1.75rem)`, flèches superposées en absolu, textes du slider resserrés et photo agrandie (25vh base / 20vh / 15vh selon la hauteur d'écran). **Tout est préfixé `.mescreations-immersion__slider` : `.product-card-cinetique` est partagé avec le catalogue.**
4. **Texte IA en retard sur la sélection (moment 2).** Deux requêtes de vitesses très différentes lancées à des moments différents : sélection ~300 ms après la fermeture, conseil IA 1 à 4 s plus tard. `sapi:advice-loading` porte désormais les réponses → l'immersion **précharge** sa sélection dès le début du calcul IA (ses 300 ms disparaissent dans les ~1,9 s d'animation de sortie) et ne l'affiche qu'à `sapi:advice-ready`. **Décision Robin : on attend le conseil DANS TOUS LES CAS**, pas de plafond. Sûr par construction — `fetchAdviceFromIA` a un timeout de 25 s et un `.catch` résolvant à `null`, donc l'événement est toujours émis, échec IA compris. Garde-fou du rechargement (changement de pièce) porté de 4 s à **26 s** : à 4 s il coupait une IA simplement lente et la page repartait sans le conseil. Abandon en cours de questionnaire → pas d'appel IA → mise à jour immédiate, comportement inchangé.

**Vérifié côté machine :** les deux JS parsent (jsc), accolades CSS équilibrées, assets bien déployés sur test (`--safe-bottom` ×8, `pendingSelection` ×7, `advice-loading` avec detail).

### 📱 RECETTE MOBILE — 2ᵉ passe (2026-08-25) : 3 acquis, 5 points ouverts

**Rien n'a été codé pour ces 5 points** — Robin reprend la main avec Cowork. Tout ce qui suit est du diagnostic prêt à l'emploi.

**✅ Acquis, ne plus y toucher :** la fluidité du scroll-pinning est **intacte** après les correctifs (c'était le risque principal) ; l'indice « Découvre ta sélection » est bien visible au chargement ; le slider est nettement mieux (photos plus grandes, card suivante visible).

---

**1. ⏳ Room-picker `/mes-creations/` encore moins compact que la home.** — *cause trouvée*

Le correctif `fadfb5c` a aligné les colonnes, le titre et le padding, mais **pas la structure interne de la carte**. En mobile, la home bascule ses cartes en **ligne horizontale** via un bloc préfixé `.home-projet` ([style.css:7969-7986](style.css#L7969), `@media max-width:768px`) :

| | Home mobile (`.home-projet`) | `/mes-creations/` aujourd'hui |
|---|---|---|
| Disposition carte | `flex-direction: row` — icône à gauche, label à droite | colonne (icône au-dessus) |
| Icône | **34px** (svg 18px) | **60px** (svg par défaut) |
| Label | **11,5px**, aligné à gauche | **14px**, centré |
| Padding carte | `0.55rem 0.7rem` | `1rem 0.75rem` |

Ces règles étant préfixées `.home-projet`, elles ne s'appliquent tout simplement pas au hero de `/mes-creations/`. **Piste :** ajouter les mêmes déclarations préfixées `.mescreations-picker-hero` dans son bloc `@media (max-width: 768px)`. L'icône 60→34px est le plus gros gain de hauteur (×7 cartes). **Mieux encore :** factoriser ce bloc mobile en un sélecteur commun aux deux contextes — c'est la 2ᵉ fois que le hero rate un réglage mobile de la home (cf. règle consignée en 1ʳᵉ passe).

**2. ✅ Indice du bas au chargement — corrigé et validé.**

**3. ⏳ « Voir le catalogue complet » maintenant trop haut.** — *régression causée par le correctif 1, cause comprise*

`--safe-bottom = 100lvh - 100svh` est une constante : elle vaut **toujours** la hauteur de la barre d'adresse, même quand la barre est rétractée. Or les deux indices n'apparaissent pas au même moment :
- « Découvre ta sélection » s'affiche **au chargement**, barre **visible** → le décalage est juste ✅
- « Voir le catalogue complet » s'affiche **après le scroll**, barre **rétractée** → le décalage n'a plus lieu d'être → il paraît trop haut ❌

**Deux pistes :**
- **(a) la plus simple, sans risque** — n'appliquer `--safe-bottom` qu'à `.mescreations-immersion__hint--reveal` (et à `__selection`), pas à `__hint--catalogue`, qui n'apparaît que barre rétractée.
- **(b) la plus exacte** — `calc(100dvh - 100svh)` : décalage **dynamique**, égal à la portion de barre réellement visible (0 quand rétractée). ⚠️ `dvh` recalcule pendant le scroll ; ici ce n'est qu'un petit élément absolu, pas le layout — le risque de saut est faible, mais **à vérifier au doigt**, la fluidité du pinning est l'acquis à ne pas perdre.

**4. ⏳ Slider — trois retouches demandées par Robin.**
- **Première photo mal centrée** : il faut de la marge avant la 1ʳᵉ card. Le slider est en `padding: 4px 2px 8px` et hérite du `padding: 0 16px` de `__selection`, pendant que la flèche gauche est superposée à `left: -2px`. **Piste :** `padding-left` sur `.mescreations-immersion__slider` **+ `scroll-padding-left` de la même valeur**, sinon le `scroll-snap-align: start` recalera la card contre le bord et annulera l'effet.
- **Prix à centrer** : `.mescreations-immersion__slider .product-price { text-align: center }` (⚠️ préfixe obligatoire — `.product-card-cinetique` est partagé avec le catalogue).
- **Card plus haute** : marge disponible côté `product-media` (aujourd'hui 25vh base / 20vh sous 840px / 15vh sous 700px). Augmenter en surveillant le point 3 — une card plus haute remonte vers la phrase et peut recréer une collision.

**5. ⏳ Moment 2 — l'ancienne sélection reste visible pendant l'attente du conseil.** — *demande produit de Robin, pas un bug*

La révélation simultanée fonctionne (validé). Mais pendant que l'IA calcule, on reste scrollé sur l'ancienne sélection, ce qui la donne à voir alors qu'elle est périmée.

**Ce que Robin veut :** au déclenchement du recalcul, **la page remonte** pour n'afficher que le décor + les 3 points de chargement, avec le futur texte en grand — puis le visiteur **re-scrolle** pour découvrir la nouvelle sélection. On rejoue la chorégraphie d'arrivée à chaque affinage.

**Piste :** sur `sapi:advice-loading` (ou à la fermeture de la modale), `scrollTo` vers le haut du track pour ramener `--reveal` à 0. À concevoir avec soin :
- interaction avec `lockScroll()`/`unlockScroll()` ([sapi-mescreations-immersion.js:333](assets/sapi-mescreations-immersion.js#L333)) — le scroll est déjà verrouillé pendant la machine à écrire ;
- le swap des cards a lieu à `advice-ready` ; si on remonte à `advice-loading`, le swap se fait hors écran, ce qui est **idéal** (plus besoin du fondu) ;
- ne pas rendre le retour en haut brutal : `behavior: 'smooth'` + respect de `prefers-reduced-motion` (`reduceMotion` existe déjà dans le fichier) ;
- cas du **changement de pièce** : la page se recharge déjà, ne rien ajouter.

---

**État :** les 4 correctifs de la 1ʳᵉ passe sont sur test (`fadfb5c`, `41cdb9c`) et validés par Robin, hors la régression du point 3. **Toujours pas de cherry-pick vers `master`.**

### 🛠️ RECETTE MOBILE — 2ᵉ passe CODÉE PAR COWORK (2026-08-25) — les 4 points ouverts

> **Nouveau process à partir d'ici :** Cowork écrit le code, **Robin commit et push lui-même** (GitHub Desktop) sur `test-theme-sapi-maison`, puis sur `master` quand il estime que c'est bon. Cowork ne lance **aucune** commande git (pas même `git status` : ça laisse un `index.lock` qui bloque GitHub Desktop). Cherry-pick ou manip git → Claude Code.

**Fichiers touchés : 2.** `style.css` et `assets/sapi-mescreations-immersion.js`. Aucun PHP.

**Point 1 — Room-picker `/mes-creations/` mobile : résolu par FACTORISATION, pas par recopie.**
La cause profonde n'était pas « il manque des règles » mais l'ordre de déclaration : `.home-projet .room-card` (l. ~7 970) et `.mescreations-picker-hero .room-card` (l. ~24 640) ont la **même spécificité 0,2,0**, et une media query n'en ajoute pas — donc le bloc du hero, déclaré ~16 700 lignes plus bas, écrase la home en silence. C'est ce qui a produit les deux régressions de suite.
- Le bloc mobile de la home devient un **bloc partagé** (sélecteurs `.home-projet …, .mescreations-picker-hero …`) : disposition ligne, icône 34px (svg 18px), label 11,5px à gauche, padding `0.55rem 0.7rem`, 2 colonnes, gap. **La home ne change pas d'un pixel** — on ajoute un sélecteur à ses règles, on ne les modifie pas.
- Le bloc mobile du hero ne garde plus que ce qui lui est propre (enveloppe, titre, sous-titre) et porte l'avertissement « ne pas redéclarer `.room-card` ici ».
- Le calage **desktop** des cartes du hero est enfermé dans `@media (min-width: 769px)` — sans ça il continuait d'écraser le bloc partagé en mobile.
- Gain de hauteur : 7 cartes passent de 3 rangées à ~117px (icône 60px, disposition colonne) à 4 rangées à ~52px, soit ~375px → ~240px.
- **Effet de bord voulu : il n'est désormais plus possible de régler la carte d'un côté sans l'autre.** Le piège est fermé, pas seulement contourné.

**Point 3 — `--safe-bottom` : option (b) retenue par Robin.** `calc(100lvh - 100svh)` → `calc(100dvh - 100svh)`, `@supports` élargi à `(height: 100dvh) and (height: 100svh)`. `dvh` suit la barre d'adresse en temps réel : décalage plein au chargement (barre visible, « Découvre ta sélection » bien placé), nul après le scroll (barre rétractée, « Voir le catalogue complet » revient à sa place). Le pinning reste piloté par `vh` et `window.innerHeight` côté JS → **non touché**. Bénéfice collatéral : le bloc sélection descend d'autant au moment où il s'affiche, ce qui finance la card plus haute du point 4.
⚠️ **C'est le point à vérifier au doigt en priorité** : `dvh` se recalcule pendant le scroll. Seuls des éléments en position absolue en dépendent, et l'entrée du bloc sélection (opacity + translateY) masque son glissement — mais la fluidité du pinning est l'acquis à ne pas perdre. Si ça bouge, le repli est (a) : n'appliquer `--safe-bottom` qu'à `__hint--reveal` et `__selection`.

**Point 4 — Slider, les 3 retouches.**
- *Marge avant la 1ʳᵉ card* : `padding-left: 20px` **+ `scroll-padding-left: 20px`** sur `.mescreations-immersion__slider` (mobile uniquement). Les deux valeurs doivent rester égales — sans `scroll-padding-left`, `scroll-snap-align: start` recale la card contre le bord au premier snap et annule le décalage. **C'est le seul chiffre à bouger pour ajuster le cadrage.**
- *Prix centré* : `justify-content: center` et **non** `text-align` — `.product-price` est un flex (« À partir de » + montant sur la baseline). Aligné sur ce que fait déjà `.creations-grid` juste en dessous, donc la colonne reste cohérente. **Appliqué desktop + mobile** (comme le prévoyait le diagnostic) : à revoir d'un œil en desktop, c'est une ligne à retirer si tu préfères l'ancien.
- *Card plus haute* : `product-media` 26 → **30vh** (≤768px), 20 → **23vh** (max-height 840), 15 → **17vh** (max-height 700). **Réglage à surveiller** : la card est ancrée par le bas, l'agrandir la fait remonter vers la phrase et peut recréer la collision corrigée en 1ʳᵉ passe. Si ça touche, redescendre le 30vh — c'est le seul levier.
- Tout est préfixé `.mescreations-immersion__slider` (vérifié : **zéro** règle non préfixée, `.product-card-cinetique` reste intact pour le catalogue).

**Point 5 — Chorégraphie rejouée au moment 2.** Nouvelle fonction `rewindToTop()` : remonte en haut du track → `--reveal` repasse à 0 → il ne reste que le décor, les 3 points et le futur texte en grand ; le visiteur re-scrolle pour découvrir la nouvelle sélection. Le swap des cards se fait alors **hors écran**.
- ⚠️ **Déclenché sur `sapi:conseiller-closed`, PAS sur `sapi:advice-loading`** — alors même que ce dernier est « le déclenchement du recalcul ». Raison : à cet instant la modale tient encore le verrou de scroll (`overflow:hidden` sur html+body jusqu'à t+1100 ms de sa séquence de sortie) et un `scrollTo` n'aurait **aucun** effet. `conseiller-closed` est émis après le déverrouillage et **toujours avant** `advice-ready` (`dispatchConseillerClosed()` précède `finishAdvice()` dans `sapi-modal-conseiller.js`) → l'ordre remontée-puis-révélation est garanti par construction, pas par un timing.
- Placé **après** le test de signature : si la sélection ne change pas, la page ne bouge pas.
- **Pas de verrou de scroll** pendant la frappe du conseil, contrairement à la séquence de chargement : bloquer la page juste après une interaction se lit comme un plantage, et le verrou par `overflow` est de toute façon inopérant au toucher sur iOS. Au pire le visiteur scrolle pendant la frappe et découvre la nouvelle sélection un peu plus tôt — **jamais l'ancienne**, qui est le défaut qu'on corrige.
- Le fondu de `applySelectionHtml` est **conservé** : il couvre le cas où le conseil arrive avant la fin du scroll smooth.
- **Abandon en cours de questionnaire : la remontée s'applique aussi**, via la constante `REWIND_ON_ABANDON = true` en tête de fonction. C'est un affinage comme un autre et le défaut y est identique. **Un seul mot à passer à `false`** si tu préfères le réserver au questionnaire terminé.
- Changement de pièce → inchangé (rechargement de page, rien d'ajouté).

**Vérifié côté machine :** les deux JS parsent (`node --check`), accolades CSS équilibrées (3861/3861), aucune règle slider non préfixée, markup du room-picker (`room-card-icon`/`room-card-label`) identique home ↔ `/mes-creations/`.

**À recetter par Robin sur test, dans cet ordre :**
1. **La fluidité du scroll-pinning** (l'acquis à ne pas perdre) — c'est le seul vrai risque du lot, à cause du passage en `dvh`.
2. `/mes-creations/` sans pièce en mobile : room-picker aussi compact que la home, cartes en ligne.
3. **La home en mobile : rigoureusement inchangée** (le bloc partagé la touche par construction, donc c'est le contrôle qui compte).
4. Les deux indices du bas : « Découvre ta sélection » au chargement, « Voir le catalogue complet » après scroll — les deux bien placés.
5. Slider : 1ʳᵉ photo cadrée, prix centré, card plus haute sans collision avec la phrase. **Et le catalogue `/mes-creations/` en dessous, inchangé.**
6. Moment 2 : finir le questionnaire → la page remonte, les 3 points s'affichent, le conseil se tape, on re-scrolle et la sélection est la nouvelle. Puis rejouer en **abandonnant** en cours de questionnaire.
7. Desktop : prix centré dans le slider (seul changement desktop du lot).

**Toujours pas de cherry-pick vers `master`.**

### 🔬 DIAGNOSTIC DISPOSITION (2026-08-25, après recette mobile de la 2ᵉ passe)

Robin a recetté sur iPhone et envoyé 4 captures. Deux défauts, **de natures différentes** — c'est ce qui rend le diagnostic utile.

**A. `--safe-bottom` était écrit à l'envers (ma faute, corrigée).**
Sémantique réelle des unités, contre-intuitive : `svh` = viewport chrome **déployé** (le plus petit), `lvh` = chrome **rétracté** (le plus grand, `== vh` sur iOS), `dvh` = état courant. Donc `100dvh - 100svh` vaut **0 quand la barre masque le bas** et **la hauteur de barre quand elle est rétractée** : l'inverse exact du besoin. Symptômes constatés : chevron de « Découvre ta sélection » coupé barre déployée (capture C), bloc sélection remonté de 90px pour rien barre rétractée (capture D), card coupée par le bas (capture B).
→ **Corrigé en `calc(100vh - 100dvh)`**, `@supports` réduit à `(height: 100dvh)` (plus besoin de garde `svh`, ni de repli desktop où `vh == dvh`). Le sens des trois unités est désormais écrit en toutes lettres dans `style.css` au-dessus de la règle.

**B. Le chevauchement « Voir le catalogue complet » × card est STRUCTUREL — `--safe-bottom` ne peut ni le causer ni le corriger.**
`__selection` et `__scrollhint` portent la **même** variable : elle s'annule dans l'écart qui les sépare. Cet écart vaut, quel que soit l'état de la barre :
`écart = X vh − 24px (bottom du hint) − 36px (hauteur du hint) = X vh − 60px`

| contexte | X | 100vh | écart |
|---|---|---|---|
| desktop | 10vh | ~900 | **+30px** → passe (d'où la recette desktop verte) |
| largeur ≤600 | 8vh | ~750 | **0px** → contact |
| **hauteur ≤840 (l'iPhone de Robin)** | 7vh | ~750 | **−7px** → chevauchement |
| hauteur ≤700 (iPhone SE) | 6vh | ~660 | **−20px** |

Une position en `vh` moins un coût fixe en `px` : la soustraction **diverge** quand l'écran rétrécit. Les valeurs 10/8/7/6vh ne sont pas quatre réglages, ce sont quatre tentatives de rattraper la même divergence. **Aucune cinquième valeur ne referme le problème** — elle le déplace vers l'appareil suivant. À traiter en passant les 3 zones du hero (texte / sélection / indice) **en flux** (flex column) : deux frères en flux ne peuvent pas occuper le même pixel, la garantie devient structurelle.

**C. Piège de préséance : `@media (max-height: 840px)` gagne sur presque tous les iPhones.**
Il est déclaré APRÈS `@media (max-width: 768px)`, et le viewport large d'un iPhone fait ~750-800px de haut, donc < 840. Conséquence : `product-media` y vaut **23vh**, pas les 30vh du bloc mobile — **le réglage 26→30vh de la 2ᵉ passe n'a rien changé sur l'appareil de Robin**, c'est le 20→23vh qui a agi. Deux chiffres qui ressemblent tous deux à « hauteur de card en mobile », un seul actif.

**D. Le texte du conseil IA n'est borné par rien.** Le « max 300 caractères » de `functions.php` (~l. 4019) est une **consigne de prompt**, jamais appliquée à la réception. C'est le seul élément du système sans borne, et la zone texte dimensionne tout le reste du hero.

**Décision Robin : étape 0 seule** (le correctif de signe), recette, puis on reparle de la refonte. Motif : séparer ma bourde du vrai sujet, pour ne pas recetter deux chantiers mêlés. **Attendu après ce commit :** captures C et D réglées, chevauchement B **toujours présent** (c'est normal, hors de portée de ce correctif).

**Plan de refonte gardé sous le coude (5 étapes, CSS mobile uniquement, desktop intouché) :** 1) les 3 zones en flex column, `__inner` perd son `top` animé ; 2) `padding-bottom: calc(100vh - 100svh)` sur la scène **remplace et supprime** `--safe-bottom` (valeur statique → plus aucun recalcul pendant le scroll) ; 3) la photo de card absorbe l'espace restant (`flex:1 1 auto` + `min-height`), ce qui **supprime les 4 hauteurs en vh** ; 4) écrans très courts + borne du texte IA, ce qui **supprime les 2 blocs `max-height`** et le piège C ; 5) facultatif, unifier desktop. Bilan : de 7 chiffres réglés à la main à 1. `applyScroll()` n'est touché par aucune étape — `--reveal` reste calculé sur `window.innerHeight`, validé.
**Risque principal identifié :** aujourd'hui les 3 zones sont hors flux, donc rien de ce qui leur arrive ne peut provoquer de reflow pendant le pinning. Les remettre en flux rend la scène sensible aux variations de hauteur (images de card qui chargent, swap AJAX du moment 2). Parade prévue : hauteur des zones imposée par le conteneur, pas par le contenu. **À vérifier au doigt, pas au raisonnement** — c'est l'acquis principal qui est en jeu.

### ✅ ÉTAPE 0 VALIDÉE PAR ROBIN (2026-08-25) — puis MESURES SUR LE SITE : la refonte en flux est ABANDONNÉE

**Étape 0 validée sur iPhone.** Retour Robin : « les écarts avec la barre du bas et des mouvements fonctionnent très bien ». À retenir : **il apprécie explicitement que les éléments SUIVENT la barre en temps réel**, pas seulement qu'ils soient bien placés. Toute proposition qui figerait ce mouvement est une régression de confort, pas une simplification neutre.

**Puis on a mesuré la vraie page** (test.atelier-sapi.fr, dans des iframes aux dimensions réelles des viewports iOS). Trois découvertes qui renversent le plan de refonte :

**1. Le viewport CSS d'un iPhone n'est PAS la taille de son écran.** Un iPhone 14 « 390×844 » donne un viewport CSS de **390×752** sous Safari. Conséquence directe : `@media (max-height: 840px)` s'applique sur **tous** les iPhones. Le `30vh` de hauteur de card du bloc mobile n'a **jamais** été actif sur un téléphone — c'est le `23vh` qui gouverne. Le commentaire du fichier a été corrigé pour le dire.

**2. Le « grand vide » de 280px n'est PAS une ressource.** Il n'existe que barre **rétractée** et disparaît intégralement barre déployée — c'est exactement la marge que la barre consomme (la sélection porte `--safe-bottom`, le bloc texte non). Il n'y a donc rien à redistribuer : c'est déjà la marge de sécurité du pire état.

**3. Un chevauchement HAUT existe en production, invisible sur l'écran de Robin.** À 390×752 barre déployée (iPhone 12→15 standard), « MA SÉLECTION POUR TA CUISINE » s'écrit **par-dessus** le bouton « Décrire mon projet » (−50px). −69px sur un 13 mini, −19px sur un SE. Robin ne l'a jamais vu parce que son viewport fait 838px.

**➡️ La refonte en flux est ABANDONNÉE, et pour une raison plus forte que le risque de reflow initialement identifié.** Le débat « svh statique vs dvh dynamique » supposait qu'il existe une hauteur de contenu tenant dans les deux états de barre. Les mesures disent le contraire : barre déployée, le budget vertical est **nul ou négatif sur la moitié des iPhones**. Un layout en flux devrait donc **compresser** quelque chose à chaque transition de barre — photo de card qui change de hauteur, phrase qui se recoupe en lignes, tout le bloc qui sursaute. En absolu, la même contrainte se traduit par des blocs qui **glissent** l'un vers l'autre sans jamais se redimensionner : c'est fluide, et c'est précisément ce que Robin vient de valider. **On garde l'absolu, on corrige l'unité.**
(La 3ᵉ voie — `transform: translateY()` sur le seul `__scrollhint` — tient techniquement mais ne sert à rien tant que rien n'est en flux : `bottom` ne provoque aucun reflow de voisin. Gardée en réserve.)

### 📋 LOT 1 — ancrage bas déterministe (PLAN, NON CODÉ, en attente du go de Robin)

**Le correctif de fond :** l'ancrage de la sélection passe de `vh` à **px**. L'écart card ↔ indice valait `X vh − 55,5px` (+3px sur un grand iPhone, −3 sur un iPhone 14, −19 sur un SE) ; il vaut désormais **+15px constant sur tous les écrans et dans les deux états de barre**.
- Supprimé : `bottom: calc(8vh + …)` du bloc `@media (max-width: 600px)`. L'ancrage est décidé en **un seul endroit**.
- Ajouté **en toute fin de `style.css`** : bloc `@media (max-width: 768px)` avec `--hint-band: 33px` et `--selection-bottom: 63px`, plus la compaction de l'intérieur de la bande d'indice (chevron 18→16px, interligne 1,5→1,2, gap 5→3). L'indice **garde ses 24px** d'écart au bord d'écran, et `--safe-bottom` reste porté par les deux blocs → le mouvement validé est intact.
- **Un seul chiffre à ajuster** pour l'air sous les cards : `--selection-bottom`. +1px = +1px d'air.
- ⚠️ **L'EMPLACEMENT EN FIN DE FICHIER EST OBLIGATOIRE.** Les blocs `@media (max-height: 840px/700px)` déclarent `bottom` sur le même élément à spécificité égale ; une media query n'ajoute pas de spécificité, donc le plus bas gagne. Remonté ailleurs → sans effet sur iPhone, symptôme « rien n'a changé du tout ». Leurs `7vh`/`6vh` sont **conservés exprès** : morts sur mobile, vivants sur une fenêtre desktop basse (cas courant), et le desktop est en recette validée.

**Recette Robin, `?piece=cuisine` :**
1. Haut de page, barre déployée puis rétractée : l'indice et son chevron toujours entièrement visibles, à la même distance du bord. Le chevron est un peu plus petit et plus près du texte — voulu.
2. Descendre jusqu'aux cards : « Voir le catalogue complet » **nettement sous** la card, avec de l'air. Le bas de la card n'est plus coupé.
3. **Déployer / rétracter la barre plusieurs fois, cards visibles** : les cards et l'indice glissent **ensemble**, l'air entre eux ne change pas.
4. Scroller lentement haut→bas puis remonter, dans les deux états de barre : aucun à-coup.
5. Desktop + `/nos-creations/` : rigoureusement identiques (contrôle qui a manqué deux fois).

**Vocabulaire pour décrire un problème :** « ça **glisse** » = comportement voulu. « ça **saute** quand la barre change » = reflow, à signaler immédiatement (ne devrait pas arriver, rien n'est passé en flux).

### ⏳ LOT 2 PRÊT, NON CODÉ — tenue du contenu sur écrans courts (le bug invisible chez Robin)

Corrige le chevauchement HAUT constaté à 390×752 / 375×720 / 375×618 barre déployée. Deux blocs `@media (max-width: 768px) and (max-height: 780px | 700px)` : phrase réduite, `scale` du bloc texte à pleine révélation porté de 0,72 à 0,64 (une **transform** : pas de re-coupe de lignes, pas de reflow, et l'état d'arrivée `--reveal: 0` strictement inchangé), photo de card 23→21vh. **Rien ne s'applique au-dessus de 780px de haut → l'écran de Robin n'est pas concerné et ne doit RIEN voir changer.**
⚠️ Robin ne peut pas recetter ce lot sur son téléphone : le test principal est justement que **rien ne bouge** chez lui. La vérification réelle demande un iPhone plus petit (mini, SE, 12→15 non-Plus).
Résultat mesuré lots 1+2 : plus aucun chevauchement, ni haut ni bas, sur 430×838 / 390×752 / 375×720 / 375×618, dans les deux états de barre.
**Résidu connu assumé :** viewport > 840px de haut ET < 768px de large (en pratique : site ajouté à l'écran d'accueil en plein écran sur un grand iPhone) → la card garde 30vh et il reste ~8px de contact barre déployée. Pas de 4ᵉ réglage pour ça tant que ce n'est pas constaté.

---

## 🏗️ REFONTE DU HERO IMMERSIF — MODÈLE À ZONES (plan validé, NON CODÉ, 2026-08-25)

> **Ni le lot 1 ni le lot 2 ci-dessus ne seront codés** : le modèle à zones les rend sans objet. Ils restent consignés pour la trace du raisonnement.

### D'où ça vient
**Le modèle est de Robin**, pas de l'analyse technique. Son objection de départ : « ça me gêne qu'on se base sur un écran iPhone seulement, notre système doit être compatible avec tous les écrans ». Elle est juste, et c'est une objection de **méthode** : raisonner à partir d'appareils mesurés produit des seuils bricolés (`max-height: 840px`, `700px`) qui sont exactement le symptôme d'un modèle qui ne tient pas debout tout seul.

**Sa description :** deux écrans (A = phrase seule, B = phrase + sélection) et des conteneurs qui se partagent la hauteur. Écran A : conteneur 1 centré (pill + texte + bouton) et conteneur 2 collé en bas (indice de scroll). Écran B : conteneur 1 réduit à ~1/3, conteneur 3 au milieu (carrousel), conteneur 2 toujours en bas mais avec un autre texte et une autre action.

**Ce que ça a débloqué :** l'analyse avait écarté le flux au motif que « barre déployée, le budget vertical est nul ou négatif, donc le layout devra comprimer quelque chose à chaque transition de barre ». Robin répond « le texte a dû réduire, **on peut autoriser** ». La taille du texte était tenue pour intouchable ; elle ne l'est pas. Sur ce point comme sur la méthode, il avait raison et l'analyse avait tort.

**Maquette validée par Robin** (ouverte en local sur Mac) : `mockups/immersion-zones-v1.html`. ⚠️ Test local = la **structure** est validée, PAS le comportement de la barre d'adresse iOS, qui ne se vérifiera qu'après portage.

### Les deux mécaniques qui font tenir le modèle
1. **Hauteur constante + décalage par transform.** La couche de contenu a `height: 100svh` (constante) → les zones ne se redistribuent JAMAIS quand la barre bouge. Elle est décalée par `transform: translateY(calc(100dvh - 100svh))` pour épouser la zone visible → le mouvement validé le 25/08 est conservé.
   **L'argument décisif n'est pas « une transform est gratuite »** (`dvh` change en continu pendant l'animation de barre, donc il y a bien un recalcul de style par frame). Le vrai argument : aujourd'hui `--safe-bottom` est une **custom property déclarée sur `.mescreations-immersion`** → chaque frame invalide le style de **tout le sous-arbre** ET déclenche 4 recalculs de position. Le modèle ramène ça à **un élément, une propriété non-layout**. → **Le nouveau modèle est strictement MOINS CHER que ce que Robin a déjà validé comme fluide.** Vérifiable, contrairement au slogan.
   ⚠️ Vérifier la cohérence avec le commentaire l. 24746 : `100dvh - 100svh` (décalage vers le bas depuis le haut) et `100vh - 100dvh` (remontée depuis le bas) donnent **le même bord bas**. Les deux formules concordent — **à écrire dans le CSS, sinon un relecteur « corrigera » un faux bug.**
2. **A et B ne sont pas deux mises en page.** On construit pour B (l'état contraint) ; A s'obtient par `translateY` + `scale` du même bloc de texte. Portée réelle de « si ça tient en B, ça tient partout » : **portrait, largeur ≤ 768px** (voir lot D).

### ⚠️ Ce que le modèle NE tient PAS, et qu'il ne faut pas répéter
« Deux frères en flux ne peuvent pas occuper le même pixel » est vrai des **boîtes**, pas du **rendu**. Formulation juste : *le chevauchement entre zones devient impossible, et les ruptures restantes sont **contenues et silencieuses** au lieu d'être étalées à l'écran.* On passe de 8 nombres gouvernant des collisions **couplées** (bouger la hauteur de card déplaçait la card vers le texte) à 4 nombres à **effet local et visible**. C'est le vrai gain. Ne pas prétendre mieux.

### 🎯 Décisions produit de Robin (à respecter, elles ne sont pas techniques)
- **Ordre de sacrifice quand ça ne tient pas : le TEXTE cède, pas le carrousel.** Le texte réduit jusqu'à son plancher puis se coupe. Le carrousel garde sa place. (À noter : l'analyse recommandait l'inverse ; Robin a tranché autrement, c'est son arbitrage commercial — les créations sont ce qui vend.)
- **Technique de coupe : FONDU, pas ellipse.** Un « … » sur du multi-lignes exige de fixer un **nombre de lignes** = un nombre réglé à la main de plus. Le fondu s'adapte seul à la place disponible. Décision assumée, à juger à l'œil en recette.
- **Paysage petits écrans : BASCULE de mode, pas compromis.** Écran A = texte seul. Écran B = carrousel seul. Plus simple à coder que le compromis, et plus lisible qu'un budget où la card ferait 29px de haut.

### ⚠️ Conséquence structurelle à connaître (dite à Robin avant la recette)
**Dans ce modèle, la hauteur des cards dépend de la longueur de la phrase.** Un conseil long donne des photos plus petites. Aujourd'hui les deux sont indépendants. C'est le prix du modèle, accepté en connaissance de cause.

### 🔴 LA FAUTE RATTRAPÉE : surcharger, ne JAMAIS supprimer
Le premier jet du portage annonçait « supprimer les 4 ancrages `bottom` et les 4 hauteurs `vh` ». **Ça aurait cassé le desktop**, qui est en recette validée : ce sont des **règles de base**, actives en desktop (l. 24929 `__scrollhint`, l. 24983 `__selection`, l. 25036 `product-media`). Un `position: absolute` sans `top` ni `bottom` retombe à sa position statique = en haut du hero, par-dessus le texte. Et il y a **5** ancrages, pas 4 : 24929, 24983, 25130, 25207, 25212.
**`display: contents` protège le desktop de ce qu'on AJOUTE, pas de ce qu'on RETIRE.**
→ Règle du chantier : **toutes** les neutralisations se font par surcharge dans le bloc mobile (`position: static`, `bottom: auto`, `height: auto`, `flex: …`). Zéro suppression de règle de base.
→ `--safe-bottom` : **déplacer sa déclaration sous `@media (min-width: 769px)`** plutôt que la supprimer. Desktop intact à l'identique, coût de recalcul mobile éliminé, aucune règle desktop touchée.

---

## ✅ LOT A CODÉ (2026-08-25) — en attente de recette Robin

**2 fichiers.** `woocommerce/archive-product.php` (le conteneur, ouvert ET fermé) + `style.css` (règle de base `display: contents`, `--safe-bottom` déplacé sous `min-width: 769px`, nouveau bloc mobile en toute fin de fichier).

**Écart assumé par rapport au découpage prévu :** le dimensionnement de la phrase en `svh` a été inclus dans le lot A, alors qu'il était prévu au lot C. Raison : sans lui, la phrase garde son `clamp()` en `vw` (22,4px sur mobile), la zone 1 mange tout le budget et le lot A serait **inévaluable** — Robin verrait des cards minuscules et conclurait à tort que le modèle échoue. La conversion de la pill et du bouton, le fondu de coupe et la borne serveur restent au lot C.

**Points de vigilance intégrés au code (chacun commenté sur place) :**
- `flex: 1 1 0` sur la zone 3 (et non `1 1 auto`) : sinon la hauteur dépendrait du contenu et la zone 1 bougerait pendant les ~220 ms où `swapCards()` vide le slider.
- `position: relative` sur la zone 2 (et non `static`) : ses deux indices sont en absolu, en `static` ils se recolleraient au bas du hero = chevron coupé, le défaut de la 1ʳᵉ passe réintroduit.
- Hauteur explicite sur la zone 2 : des enfants absolus ne mesurent rien, sans hauteur elle ne réserverait que son padding.
- `min-height: 190px` sur la zone 3 : c'est ce plancher qui fait céder le TEXTE en premier plutôt que les cards (décision Robin).
- Repli `height: 100vh` avant `100svh` : si `svh` est inconnu, `height` tomberait à `auto` = rupture dure.
- `will-change` transféré sur la couche, retiré de `__inner` en mobile.
- Nouveau bloc placé en **dernier** du fichier (vérifié : l. 25273, après les `max-height` l. 25231/25238).

**Vérifié machine :** `<div>` du hero équilibrés (10/10), conteneur ouvert 1× et fermé 1×, accolades CSS 3871/3871, aucun commentaire non fermé, les 5 ancrages `bottom` et les hauteurs `vh` de base **toujours présents** (desktop intact).

**Reste à vérifier en recette, non vérifiable machine :** le `padding-top: 64px` contre la hauteur réelle du header en état `.is-scrolled` (la pill peut passer dessous) ; le `backdrop-filter` du bouton à l'intérieur d'un ancêtre promu (le backdrop peut se vider) ; et bien sûr la fluidité du pinning.

### ✅ RECETTE LOT A — VALIDÉE PAR ROBIN + 3 retours traités (2026-08-25)

**Les 5 points de contrôle passent :** fluidité « parfaite », **plus aucun chevauchement**, pill sous le header OK, bouton inchangé, desktop sans changement. **Le modèle à zones tient.**

**A — Le bloc texte débordait en largeur en écran A.** Cause : le bloc est agrandi de 22 % depuis son centre ; à pleine largeur il sortait de 11 % de chaque côté (pill et bouton coupés). Corrigé par `width: 82%` (= 100 / 1,22). ⚠️ **Les deux nombres doivent rester en phase** — c'est écrit dans le CSS. Une largeur en % est constante → aucun recalcul pendant le scroll, contrairement à un padding interpolé.

**B — « Pourquoi c'est la hauteur du carrousel qui dépend du texte et pas l'inverse ? »** Robin a raison, et c'était incohérent avec son propre arbitrage. **Inversé :** la zone 3 est désormais **servie en premier, à hauteur fixe** (`--imm-selection-h: 40svh`, en svh donc constante) et la zone 1 prend le reste. Un conseil long ne rapetisse plus les photos — c'est lui qui se réduit puis se coupe. `--imm-selection-h` devient LE réglage du bloc : « quelle part de l'écran revient au carrousel ».

**C — Titre « Ma sélection » trop collé au-dessus, trop loin des cards.** Deux causes distinctes :
- trop collé au bouton → `padding-top` sur la zone 3 ;
- trop loin des cards → **les cards ne remplissaient pas leur zone** : `.mescreations-immersion__slider-wrap` est en `align-items: center` (règle de base), donc le slider était centré et gardait la hauteur intrinsèque des cards, creusant un vide au-dessus ET en dessous.
→ **Le cœur du LOT B a été avancé ici**, parce que C n'était pas corrigeable sans lui : `align-items: stretch`, slider en `height: 100%`, et la chaîne de hauteurs de la card (`.product-card-cinetique` **et** `a.product-card-link` en flex-colonne, `product-media` en `flex: 1 1 auto` avec plancher 70px, `product-info`/`product-actions` en `flex: 0 0 auto`). ⚠️ **Le lien intermédiaire est indispensable** : sans lui la chaîne est rompue et rien ne se passe. Les hauteurs `vh` de base restent en place pour le desktop, elles sont neutralisées ici.

**Vérifié machine :** accolades 3876/3876, aucune règle de card non préfixée dans le nouveau bloc (zéro fuite vers `/nos-creations/`), les 4 hauteurs `vh` de base toujours présentes.

**Reste du LOT B :** plancher sur `.mescreations-immersion__pcard--sur` (la card la plus haute du slider, en px fixes, sans plancher — c'est elle qui casse en premier).

### ✅ AGRANDISSEMENT DES CARDS (2026-08-25) — propositions 1 et 2 de Robin

Robin : « je trouve les cards toujours trop petites ». Trois propositions ; les deux premières sont faites, la 3ᵉ est une décision produit (voir plus bas).

**Prop. 1 — rééquilibrage du texte des cards.** Tout ce qui est gagné ici part **directement dans la photo**, puisqu'elle absorbe l'espace restant. Levier le moins cher.
- **Bouton « Découvrir » masqué en mobile** : il ne servait à rien, `product-actions` est **à l'intérieur** du `<a class="product-card-link">` qui enveloppe toute la card (vérifié dans `sapi_immersion_render_product_card()`) — taper n'importe où ouvrait déjà la fiche. ~45px rendus.
- **Hiérarchie inversée corrigée** : le nom du modèle (11,5px) était plus petit que le prix (16,8px). C'est le nom qu'on vend. Nom → 0,95rem, prix → 0,9rem, « à partir de » → 0,62rem.
- **Le grand vide au-dessus du prix** venait de `.product-card-cinetique .product-price { margin-top: auto }` (règle de base) : `.product-info` étant en flex-colonne avec `flex: 1`, ce `auto` poussait le prix tout en bas. Neutralisé.
- ⚠️ `!important` nécessaire sur `.product-name` : la règle mobile plus haut en porte un ; à spécificité égale, seul un `!important` **plus bas** gagne.

**Prop. 2 — plus de place au carrousel.** `--imm-selection-h` 40 → **48svh** ; bande d'indice 42+24 → **34+18px** ; `padding-top` de la zone 3 resserré.
⚠️ **Précision utile consignée dans le CSS** : le grand écart entre le bouton « Décrire mon projet » et les cards **n'est pas** un padding — c'est l'espace **inutilisé de la zone 1** (elle prend le reste, son contenu est calé en haut, la marge tombe en bas). Le seul geste qui le réduit est d'augmenter `--imm-selection-h`, qui resserre ET agrandit les cards d'un coup.

**Gain calculé sur 390×752 : ~+120px pour la photo, soit environ le double de sa taille actuelle.**

### ✅ PROP. 3 CODÉE — la zone 1 SORT de l'écran au scroll (2026-08-25)

**Décision Robin, formulée précisément :** « le haut sort jusqu'à ce que le carrousel s'affiche entièrement, ça dépendra donc de la taille de l'écran. »

**Elle SIMPLIFIE le système au lieu de le compliquer** — c'est ce qui la rend meilleure, et je ne l'avais pas vu en la présentant comme un simple arbitrage produit. **Un bloc qui s'en va n'a plus besoin de rétrécir.** Trois réglages disparaissent d'un coup :
- le `scale(1.22)` d'agrandissement au repos ;
- le `width: 82%` qui existait UNIQUEMENT pour compenser le débordement de ce scale (retour A) ;
- `--imm-a-drop`, qui servait à recentrer le bloc au repos.
Le texte est maintenant simplement **centré** dans l'espace disponible, puis il s'en va.

**Mise en œuvre :**
- Zone 1 **hors flux** (`position: absolute`), bornée par `top: 64px` ET `bottom: 52px` → elle ne prend aucune place au carrousel, et **ne peut toujours pas chevaucher l'indice** (elle est bornée, et son `overflow: hidden` coupe un texte trop long au lieu de pousser).
- `translateY(calc(var(--reveal) * -100%))` : **le `-100%` se rapporte à la hauteur de l'élément lui-même**, pas à l'écran. La sortie fait donc exactement ce qu'il faut et **s'adapte seule à chaque taille d'écran** — la demande de Robin obtenue sans mesurer quoi que ce soit en JS. Plus un fondu, pour qu'il ne survole pas le carrousel pendant la traversée.
- Couche en `justify-content: flex-end` : carrousel et indice calés en bas, l'espace libre tombe en haut, là où flotte la zone 1. C'est ce qui fait que le carrousel « s'affiche entièrement ».
- `--imm-selection-h` 48 → **56svh** (la zone 1 ne lui dispute plus la place) et phrase plus généreuse (`clamp(1.05rem, 3.1svh, 1.9rem)`), puisqu'elle n'a plus à cohabiter avec le carrousel.

**Résultat calculé sur 390×752 : card de ~377px dont ~317px de photo**, contre ~180px de card totale avant le lot A. Bande de texte en écran A : 636px.

**⚠️ Conséquence assumée, signalée à Robin avant qu'il tranche :** en écran B, le visiteur ne voit plus le conseil, ni la pill, **ni le bouton « Décrire mon projet »** — qui est l'entrée du questionnaire, donc un chemin de conversion. Robin a choisi cette voie en connaissance de cause. Si les statistiques du Conseiller baissent après la mise en prod, **c'est la première cause à examiner**, et la parade existe : ressortir le bouton de la zone 1 pour l'ancrer au-dessus du carrousel.

**⚠️ Trois nombres doivent rester en phase** (commenté sur place) : `padding-top: 64px` de la couche, `top: 64px` de la zone 1, et `bottom: 52px` de la zone 1 = hauteur de la bande d'indice (34 + 18).

### ✅ CARROUSEL CENTRÉ, PHOTO EN 3/4 (2026-08-25) — suite directe de la prop. 3

Robin, en voyant le grand vide laissé par la zone 1 : « si ça disparaît complètement, il faut centrer le carrousel et lui faire occuper l'espace ! »

**⚠️ ERREUR DE MA PART, corrigée avant de coder — à retenir.** J'avais proposé « photo carrée, ~330px » et Robin l'avait choisie. Le chiffre était faux : **une photo carrée ne peut pas être plus haute que la card n'est LARGE**, et la largeur est plafonnée par l'écran. À 78 % de largeur elle ferait **279px — plus PETITE que les ~315px du moment**, à l'opposé de la demande. Corrigé auprès de Robin avec le tableau des trois combinaisons :

| largeur de card | photo carrée | aperçu de la card suivante |
|---|---|---|
| 78 % (actuel) | 279px | 68px, confortable |
| 88 % | 315px | 25px, mince bande |
| 96 % | 344px | quasi nul |

**Règle générale à garder : photo carrée + grande + aperçu visible → il faut en abandonner un des trois.** Le rapport de forme est le seul levier qui rende de la hauteur SANS toucher à la largeur, donc sans manger l'aperçu.

**Décision Robin après correction : trois quarts, cards inchangées à 78 %.**

**Ce que ça donne (390×752) :** card 279px de large → **photo 372px** (contre ~315 avant, ~150 avant le lot A), card totale 442px, bloc centré avec ~77px d'air en haut et en bas, aperçu de la suivante conservé à 61px.

**Un réglage de moins :** `--imm-selection-h` **supprimé**. La zone 1 étant hors flux, le carrousel occupe tout l'espace libre (`flex: 1 1 auto`) et son bloc y est centré (`justify-content: center`). La taille des cards est désormais donnée par le **rapport de forme de la photo** — un réglage qui se juge à l'œil, au lieu d'un pourcentage d'écran.

**Dégradation prévue sur écran court :** `flex: 0 1 auto` + `min-height: 60px` sur la photo → c'est elle qui cède sur sa hauteur (le ratio n'est plus tenu) plutôt que la card qui déborde. Le nom et le prix restent toujours visibles.

### ✅ CARROUSEL PLEINE LARGEUR, CARD ACTIVE CENTRÉE (2026-08-25)

Robin : « supprimer les marges latérales, les cards coupées au bord de l'écran » + « la card actuelle doit être centrée ».

**Répartition : `12vw` de padding + `76vw` de card + `12vw` = 100.**
⚠️ **Tout est en `vw`, jamais en `%`, et c'est obligatoire** : un `flex-basis` en % se calcule sur la **boîte de contenu** du slider (padding déduit), un padding en % sur la **largeur du parent**. Les deux références diffèrent → en %, `12 + 76 + 12` ne ferait pas 100. En `vw` tout se rapporte à l'écran et l'addition tombe juste. Les trois valeurs doivent toujours totaliser 100.

**Résultat (390px) :** card 296px de large → **photo 395px** (contre 372, et ~150 avant le lot A). Morceau visible de chaque côté : **29px, symétrique**.

**Le padding latéral du slider n'est pas décoratif** : c'est lui qui permet à la **première** et à la **dernière** card de venir au centre. Sans lui, elles resteraient collées à leur bord. Vérifié : première card centrée ⇔ `scrollLeft = 0`.

**JS — `cardOffsets()` renvoie désormais la POSITION DE SCROLL cible, plus l'offset brut.** Les deux diffèrent depuis que la card est centrée en mobile alors qu'elle est calée à gauche en desktop. **Le mode n'est PAS déterminé par un test de largeur d'écran mais par la lecture du `scroll-snap-align` réellement appliqué** (`center` vs `start`) : le JS suit donc automatiquement la feuille de style, sans dupliquer un point de rupture ni risquer un désaccord entre les deux fichiers. `applyScroll()` reste intact.

**⚠️ À vérifier en recette :** que la **dernière** card (la sur-mesure orange) vient bien au centre. Le `padding-right` d'un conteneur de défilement flex est historiquement ignoré par certains navigateurs ; le `scroll-snap-align: center` devrait suffire à rendre la position atteignable, mais ça se constate.

## 🖥️ MODÈLE À ZONES ÉTENDU AU DESKTOP (2026-08-25) — décision Robin

Robin, après validation du mobile : « en desktop, il faut qu'on augmente la hauteur des cards + réduire l'écart entre le bouton et le conteneur du dessous ».

**Constat qui a motivé l'extension plutôt qu'un réglage :** le desktop était devenu la moitié fragile. Mesuré : il ne restait que **~13px** entre le bas des cards et l'indice. Les cards ne pouvaient donc grandir que **vers le haut**, c'est-à-dire en mangeant l'écart que Robin voulait réduire — les deux demandes étaient le même geste, mais avec ~70px de marge avant collision, marge qui **dépend de la longueur du conseil** (3 lignes en cuisine, 4 en chambre d'enfant → négatif). Exactement le mécanisme corrigé sur mobile. Robin a choisi l'extension du modèle.

**Restructuration :** le modèle à zones passe en **règle de base** (`.mescreations-immersion__layer` devient une vraie couche flex partout, `display: contents` supprimé). Le bloc `@media (max-width: 768px)` ne garde plus que les **différences** du mobile : header 64px, zone 1 hors flux qui sort de l'écran, phrase en svh, carrousel pleine largeur en vw, photo en 3/4, bande d'indice resserrée. **Aucune règle du modèle n'est dupliquée entre les deux** — deux copies finissent toujours par diverger.

**⚠️ PIÈGE MAJEUR, rattrapé au calcul avant livraison.** Le passage en flux **à lui seul aurait RÉDUIT** la photo desktop (197 → ~178px), soit l'inverse de la demande. Cause : la zone 1 était visuellement réduite à 72 % par un `scale`, donc elle occupait 28 % de moins que sa hauteur réelle. En flux, elle prend sa hauteur **naturelle** — donc plus de place qu'avant. **Le flux ne crée pas d'espace, il ne fait que supprimer le risque de chevauchement.** Il a fallu dégonfler la zone 1 en parallèle :
- `gap` du bloc texte 18 → 12px ;
- pill : `margin-bottom` 16 → 0 (la marge de `.conseiller-sig--v1`, partagée avec la home, s'**additionnait** au `gap` — neutralisée en préfixé, la home garde la sienne) ;
- bouton : `margin-top` 6 → 0 ;
- phrase : `clamp(1.85rem, 3.6vw, 2.75rem)` → `clamp(1.3rem, 2.2vw, 1.85rem)`, dimensionnée pour l'état ANCRÉ (avant, la taille était celle du repos et le `scale` la réduisait) ;
- bande d'indice : `margin-bottom` 24 → 16px.

**Résultat calculé (1300×790) : photo ~243px contre 197** (+23 %), et **l'écart bouton → titre passe de ~104px à 20px**, réglé par un seul `padding-top` sur la zone 3 — le seul endroit qui le gouverne désormais.

**Ce qui a disparu du desktop :** le `top: calc(50% - reveal*39vh)` animé (propriété de **layout**, donc recalcul à chaque frame de scroll) est remplacé par un `translateY` (compositeur). Le desktop devrait être **plus fluide qu'avant**, pas seulement plus robuste.

**Inertes désormais, à nettoyer plus tard :** les 5 `bottom: calc(… + var(--safe-bottom))` et les 4 hauteurs `product-media` en vh. Neutralisés par les règles de base, laissés en place volontairement — le nettoyage est un chantier à part, après validation de la recette desktop.

**Recette desktop à refaire entièrement** (elle était validée sur l'ancien modèle) : arrivée, scroll complet aller-retour, fluidité, pièce au conseil le plus long (`?piece=chambre-enfant`), flèches du carrousel, dernière card, et `/nos-creations/` inchangé.

### ✅ 4 retours desktop + retour des dots (2026-08-25)

**1. Zone de texte plus large.** `max-width` de la phrase 20 → **27em**. Bénéfice caché : moins de lignes → zone 1 plus courte → cards plus hautes. Reste le garde-fou du `scale` (27em × 1,2 ≈ 930px, très en deçà des 1272px utiles).

**2. Titres produit plus grands.** `product-name` 0,78 → **1,05rem**, prix 1,2 → 1rem, catégorie 0,68 → 0,75rem. Le nom était **plus petit que le prix** : hiérarchie inversée, c'est le nom du modèle qu'on vend. Ajouté aussi le `margin-top: 0` sur le prix en desktop (il n'était qu'en mobile) — la règle de base `margin-top: auto` y creusait un vide.

**3. Plus d'air autour du carrousel.** `padding` de la zone 3 : **24px en haut, 16px en bas**. C'est le seul endroit qui gouverne ces deux écarts maintenant que les zones sont en flux.

**4. Dots remis (mobile + desktop).** Conteneur `[data-immersion-dots]` dans le markup, **contenu généré en JS** : le nombre de cards varie selon la pièce ET change au moment 2, un rendu PHP obligerait à les régénérer côté serveur aussi = deux sources pour la même chose. Ils partagent `cardOffsets()` avec les flèches → un dot cliqué amène la card exactement où une flèche l'aurait amenée, centrée ou non selon ce que le CSS a décidé. Masqués s'il n'y a rien à faire défiler. Reconstruits après `swapCards()`.

**⚠️ ARBITRAGE À CONNAÎTRE — ces trois demandes COÛTENT de la hauteur.** Plus de marges (+20px) et des dots (+24px) se prennent sur la photo, puisque le carrousel absorbe l'espace restant. Compensé en partie par la phrase élargie et par la bande d'indice resserrée (42+24 → 34+14), mais le solde reste négatif : **photo ~199px contre 243px** dans la version que Robin venait de valider.
**Le levier pour revenir à ~239px : masquer le bouton « Découvrir » en desktop aussi**, comme en mobile — `product-actions` est à l'intérieur du `<a>` qui enveloppe toute la card, donc cliquer n'importe où ouvre déjà la fiche. Une ligne. **Robin a tranché : on garde le bouton en desktop, les ~199px lui conviennent.**

**Mobile — carrousel réduit après le retour des dots (décision Robin).** Les dots ajoutaient ~24px au bloc et le carrousel paraissait trop haut. Ratio de la photo `3 / 4` → `4 / 5` : photo 395 → 370px, air 53 → 66px de chaque côté.

### ✅ MOBILE — la hauteur des cards dépend maintenant AUSSI de la hauteur d'écran

Question de Robin, puis constat : « en mobile, il faudrait que la hauteur des cartes dépende aussi de la hauteur de l'écran, non ? » Il avait raison — la hauteur ne venait que de la **largeur** (via le rapport de forme), donc sur un téléphone plus haut l'espace en trop devenait de l'air au lieu d'agrandir la photo. En desktop, à l'inverse, le carrousel absorbait déjà l'espace restant.

**On ne pouvait pas simplement faire remplir la card comme en desktop** : c'est exactement ce qui produit la bande verticale étirée (une vue de pièce recadrée en fente, luminaire hors cadre) — le cadrage écarté plus haut dans ce chantier.

**Solution : `height: clamp(60px, 49svh, 101vw)` sur la photo.**
- `49svh` = la taille voulue, proportionnelle à la **hauteur** d'écran (= les 370px actuels sur un iPhone de 752). **C'est LE chiffre à toucher** pour agrandir ou réduire les cards en mobile.
- `101vw` = **plafond de FORME**, calculé depuis la largeur de card (76vw × 4/3) : la photo ne peut jamais dépasser un format 3/4. ⚠️ **Lié au 76vw** — si la largeur de card change, ce plafond doit suivre.
- `60px` = plancher absolu.

**Comportement mesuré :**

| appareil | photo | forme | ce qui décide |
|---|---|---|---|
| iPhone 14 (390×752) | 368px | 0,80 | la hauteur |
| iPhone Pro Max (430×838) | 411px | 0,80 | la hauteur |
| iPhone SE (375×618) | 303px | 0,94 | la hauteur |
| Android haut (360×900) | 364px | 0,75 | le **plafond de forme** |

Sur l'appareil de Robin, rien ne change (368 ≈ 370). Le gain est sur les grands téléphones ; le garde-fou joue sur les écrans étroits et hauts, où la photo se serait déformée.

---

## ⚓ ANCRAGE AU DÉFILEMENT — 3 positions (demande Robin, 2026-08-25)

Robin veut trois positions d'arrêt : (1) le conseil en grand, (2) le carrousel révélé, (3) le haut du catalogue. Et il proposait de **tout refaire en deux vues plein écran successives**.

### Avis de l'agent : garder l'architecture, ajouter l'ancrage
**Le diagnostic de Robin est juste** — la page n'a aujourd'hui aucun endroit où elle veut s'arrêter ; on peut relâcher le doigt à `--reveal: 0.37` et rester sur un état que personne n'a dessiné. Même méthode que ses deux intuitions précédentes : supprimer un continuum d'états non spécifiés.

**Mais deux vues ne peuvent pas partager la photo de fond.** Trois issues, toutes mauvaises : deux photos (ça se lit « on change d'image », pas « la photo se floute ») ; une photo `fixed` (le comportement iOS qu'on a passé la journée à contourner) ; ou un fond `sticky` dans un conteneur englobant — **soit l'architecture actuelle avec d'autres noms**. La simplification promise n'existe que si on abandonne l'effet.

**Contre-intuitif et vérifiable : l'architecture actuelle est PLUS stable pour l'ancrage que deux vues.** Le track est en `vh`, unité qui ignore la barre d'adresse → les positions d'arrêt ne bougent jamais. Deux sections plein écran auraient leurs points d'ancrage définis par leur hauteur ; en `dvh` ils se déplaceraient quand la barre se rétracte — or sur iOS la barre se rétracte PARCE QU'ON SCROLLE. Boucle où scroller déplace la cible.

**Question décisive posée à Robin :** « si le flou se faisait tout seul en une demi-seconde au lieu de suivre ton doigt, le regretterais-tu ? » → **« Oui, j'y tiens. »** Donc on garde le modèle actuel. (Si la réponse avait été non, la refonte de Robin était la bonne : plus courte, sans track, sans `--reveal`, avec des points d'arrêt gratuits.)

### ⚠️ ERREUR DE L'AGENT, corrigée avant de coder
Il annonçait un **plateau de 150vh** et en déduisait qu'il fallait raccourcir le track à 200vh, présenté comme « une condition, pas un réglage ». **Faux.** Le calcul : track 250vh, scène 100vh → épinglage sur **150vh** ; la révélation finit à 100vh → **plateau = 50vh**, pas 150.
Conséquence : **raccourcir à 200vh donnerait un plateau de ZÉRO** — le hero commencerait à partir à l'instant même où la révélation se termine, sans aucun endroit où se poser. Ce serait une régression de confort, et surtout invisible à la lecture du plan.
→ **Track laissé à 250vh.** Son argument (« la page paraîtra collée entre les points 2 et 3 ») ne vaut que pour un ancrage CSS `mandatory` ; on part sur un ancrage JS, dont on fixe nous-mêmes le seuil d'accroche. La longueur de la page se rejugera à l'œil au lot 2, une fois les aimants en place.
**Leçon : recalculer les chiffres d'un avis avant de bâtir dessus, même quand le reste de l'analyse est solide.**

### ✅ LOT 1 CODÉ — la fondation, SANS aucun aimant
Livré seul et à recetter seul : c'est le seul lot qui touche à la fluidité, et une régression noyée dans un lot double serait indiagnosticable.

**Le cœur : faire coïncider par construction « la révélation est finie » et « le point d'ancrage 2 ».**
Un **repère** de 1px, invisible et hors flux, est posé en CSS à `top: 100vh` dans le track (`[data-immersion-mark]`). `applyScroll()` lit sa position au lieu d'utiliser `window.innerHeight`.
⚠️ **Pourquoi c'est nécessaire** — 4ᵉ épisode de la série des pièges d'unités : sur iOS, `window.innerHeight` **suit** la barre d'adresse (752px) alors que le `100vh` du CSS l'**ignore** (838px). 86px d'écart, invisibles tant qu'ils tombent dans le plateau, mais qui **deviendraient la position d'arrêt elle-même** dès qu'on ancre sur ce point : la page se calerait sur une révélation à ~90 %, texte encore visible, photo pas tout à fait floue. Et l'écart varie avec l'état de la barre → le défaut apparaîtrait et disparaîtrait tout seul.
La distance ne dépend que de `vh`, donc constante : mesurée au chargement, à 600 ms (polices/images) et aux redimensionnements — **jamais à chaque image de scroll**.
`scrollToReveal()` (l'indice « Découvre ta sélection ») utilise la même distance → il amène exactement à la fin de la révélation.

**Trois corrections de détail repérées par l'agent :**
- `overscroll-behavior-x: contain` sur le slider : en butée sur la dernière card, iOS enchaînait sinon sur le scroller parent — un swipe de trop aurait relâché la page vers le catalogue.
  ⚠️ **RÉGRESSION LIVRÉE PUIS CORRIGÉE — à ne pas refaire.** Écrit d'abord en `overscroll-behavior: contain` (sans axe), ce qui **s'applique aux DEUX axes**. Or `overflow-x: auto` fait aussi de l'élément un scroller **vertical** — l'autre axe passe automatiquement de `visible` à `auto` — même quand il n'a rien à faire défiler en hauteur. Le `contain` y bloquait donc la propagation vers la page : **le scroll vertical ne fonctionnait plus dès que le doigt ou le pointeur était sur le carrousel**, en mobile comme en desktop. Constaté par Robin en recette. Toujours borner l'axe sur un scroller à une seule direction.
- `scroll-margin-top` du catalogue borné en mobile (90 → 64px) : le header n'a pas la même hauteur, le catalogue se calait 26px trop bas. Sert aussi à la future 3ᵉ position d'ancrage.
- Commentaire périmé de `archive-product.php` corrigé : il affirmait encore « en desktop cette div n'existe pas / `display: contents` », faux depuis l'extension du modèle. Exactement le type de commentaire qui fait « corriger » un faux bug.

**Règle posée pour le lot 2 :** la scène épinglée ne doit **JAMAIS** être une cible d'ancrage — un sticky est perpétuellement « déjà aligné » pour le moteur, ce qui donne blocage ou tremblement selon le navigateur. Cibles : le track, le repère, le catalogue.

### ✅ LOT 2 CODÉ — les aimants (2026-08-25)

**Un seul fichier : `assets/sapi-mescreations-immersion.js`.** Aucun CSS, aucun markup.

**La règle d'accroche n'est pas la même partout — c'est le cœur du réglage :**
- **DANS la zone de révélation** (du haut du track au repère) : on accroche **toujours** vers l'une des deux extrémités, quelle que soit la distance. Un état à moitié révélé n'est jamais un état voulu.
- **AU-DELÀ** : libre. Le plateau après la révélation est identique au pixel près, donc il n'y a rien à corriger, et le visiteur qui descend vers le catalogue ne doit jamais se sentir retenu. Seule exception : une accroche à l'approche du catalogue (30 % de hauteur d'écran).
→ **C'est cette asymétrie qui évite l'effet « page collante »** que l'agent redoutait avec un ancrage CSS uniforme.

**Pourquoi en JS et pas `scroll-snap-type` :** le CSS ne sait pas **s'abstenir**. Il faut ne pas ancrer dans quatre situations — verrou de la machine à écrire, modale ouverte, remontée du moment 2 en vol, geste ayant fait défiler le carrousel.
**Astuce qui couvre les deux premières d'un coup :** le verrou de scroll est posé par `overflow: hidden` sur `html`, par notre machine à écrire **et** par la modale Conseiller. Un seul test (`scrollLocked()`) suffit.

**Détails qui comptent :**
- **Tous** les scrolls verticaux du fichier passent désormais par `programmaticScrollTo()` (les deux indices + `rewindToTop`) : sans ce drapeau, l'ancreur se déclencherait à la fin de leur animation et se battrait avec elle — deux animations sur le même axe = rebond. Vérifié : plus aucun `window.scrollTo` ni `scrollIntoView` non encadré.
- **Annulation dès que le visiteur reprend la main** (`touchstart`, `wheel`, `keydown`). ⚠️ Un simple `window.scrollTo(x, y)` ne suffit PAS à annuler : `html` porte un `scroll-behavior: smooth` global (style.css l. 128) qui animerait même ce saut. On neutralise la propriété le temps de l'appel (`jumpTo()`). C'est la réponse à l'arbitrage « smooth global vs animations de calage » soulevé par l'agent.
- **Geste dans le carrousel** : on teste le **déplacement** de `scrollLeft`, pas la cible du toucher. Un doigt posé sur les cards qui tire la page vers le bas est un scroll vertical légitime et doit s'ancrer comme les autres ; seul un swipe horizontal effectif désactive l'accroche.
- **`prefers-reduced-motion` → aucun ancrage.** Déplacer la page sous quelqu'un qui a demandé moins d'animation serait à contresens.
- La scène épinglée n'est **jamais** une cible : les positions sont calculées (haut du track, repère, catalogue moins son `scroll-margin-top`).

**Réglages, tous deux nommés en tête de bloc :** `SNAP_IDLE` (150 ms d'immobilité avant de considérer le geste fini) et `SNAP_CATCH` (0,30 hauteur d'écran = distance d'accroche au catalogue).

**À recetter en priorité :** le **moment 2** (fermeture de la modale → relâchement du `overflow` → `rewindToTop()` → arrivée exacte en position 1 → retape du conseil → remplacement des cards). C'est la seule séquence où deux scrolls pourraient partir dans la même image.

### ✅ CORRECTIF — « il manque l'aimant de la 3ᵉ partie » (Robin, recette du lot 2)

**Il n'était pas cassé, il mordait à peine.** L'ancre `#mes-creations-catalogue` existe bien. Le problème était géométrique : **150vh entre les positions 2 et 3** (soit une poussée et demie) pour une distance d'accroche de 30vh. Une poussée normale survolait la fenêtre d'accroche, donc l'aimant ne se déclenchait jamais.

**LE correctif, et il est unique :** l'accroche vise désormais **la plus proche des trois positions sur tout le hero**, au lieu d'une fenêtre étroite autour du catalogue. La bascule 2↔3 se fait donc à mi-chemin (75vh), ce qu'une poussée mobile ordinaire (100 à 200vh) dépasse largement. Au-dessus du hero et une fois entré dans le catalogue, la page redevient libre — on ne retient jamais quelqu'un qui lit.

**⚠️ SUR-CORRECTION LIVRÉE PUIS ANNULÉE — la leçon la plus utile de la séquence.**
J'ai d'abord changé **deux** choses : la règle d'accroche **et** le raccourcissement du track (250 → 200vh), en croyant que l'écart de 150vh entre les positions 2 et 3 était en cause. Robin l'a vu dans la minute : « tu as retiré le stop / pause avant de passer au catalogue normal ! »
Le raccourcissement **supprimait le plateau** (`hauteur - 200vh`), c'est-à-dire la zone après la révélation où la scène reste épinglée et où l'écran ne bouge pas. Cette pause a deux fonctions : scroller un peu sans que rien ne se passe, et **masquer le va-et-vient du hero quand l'aimant ramène un petit geste en arrière**.
→ **Track remis à 250vh.** La règle d'accroche seule suffisait, et elle fonctionne très bien avec cette longueur.

**Ce que ça dit du raisonnement de l'agent :** son conseil de raccourcir reposait sur un chiffre faux (plateau annoncé à 150vh, réel 50vh). J'ai eu raison de vérifier le chiffre, puis tort d'y revenir en croyant avoir trouvé une autre justification. **La longueur du track n'a jamais été le problème** — c'était la règle d'accroche depuis le début. Vérifier un chiffre ne suffit pas : il faut aussi vérifier que la conclusion ne survit pas pour de mauvaises raisons.

### ✅ TROIS RETOURS DE RECETTE (2026-08-25, fin de journée)

**1. Pause trop courte → track 250 → 300vh (plateau 50 → 100vh).**
⚠️ Allonger la pause éloigne mécaniquement la position 3 (l'écart 2→3 passe à 200vh) : sans rien d'autre, il aurait fallu pousser un écran entier avant que la page accepte d'aller au catalogue. **D'où un BIAIS DIRECTIONNEL ajouté à l'aimant** : au lieu de viser la position la plus proche, il suit le sens du geste et bascule vers l'étape suivante dès **35 %** du trajet en descendant (règle symétrique en remontant). **C'est ce qui découple les deux réglages** — sans lui, « pause plus longue » et « catalogue facile à atteindre » sont en opposition directe.

**2. Contraste derrière le conseil → voile local, pas un scrim plus sombre.**
Assombrir le scrim général aurait terni **toute la pièce**, or c'est la photo qui vend. Le voile est un `::before` sur le bloc texte : dégradé ovale flouté, `inset` négatif pour s'éteindre au-delà du texte sans bord visible, `z-index: -1` avec `isolation: isolate` sur le parent pour rester derrière le texte. Il suit le bloc (même transform, même sortie d'écran en mobile) puisqu'il en est le pseudo-élément. ⇦ Le premier `rgba` règle l'intensité.

**3. Plus de tirets longs dans les textes IA — traité sur TROIS niveaux.**
- **La cause racine était dans les exemples** : `guide-prompt-exemples.txt` en contenait **26**. On apprenait littéralement au modèle à en produire. Nettoyés (26 → 0), avec trois règles distinctes : plages chiffrées `10–20` → `10-20`, titres de section → ` - `, incises en phrase → virgule.
- **Règle explicite** ajoutée en fin de `guide-prompt-regles.txt` (les 2 tirets qui y restent sont ceux de la règle elle-même, qui doit montrer ce qu'elle interdit).
- **Garde-fou serveur `sapi_strip_long_dashes()`**, appliqué aux **5** points de sortie de texte IA (conseil final, messages de la modale, messages de contact). ⚠️ **Nécessaire parce qu'une consigne de prompt n'est PAS une contrainte** — précédent dans ce fichier : la limite « max 300 caractères » du conseil, écrite dans le prompt et jamais appliquée nulle part. Le trait d'union normal est préservé (mots composés, sur-mesure, plages).
- Vérifié : les conseils pré-écrits par pièce (`sapi_megafilter_get_generic_advices`) étaient déjà propres.

### ⏳ LOT 2 — spécification d'origine (non codé)
Ancrage **en JavaScript**, pas en CSS : c'est le seul qui sache **s'abstenir**. Il doit être neutralisé dans quatre fenêtres — verrou de la machine à écrire, modale ouverte, `rewindToTop()` en vol, et geste initié dans le carrousel. Prévoir un drapeau « scroll programmatique » honoré par `rewindToTop()`, `scrollToReveal()` et `scrollToCatalogue()`, et l'annulation au `touchstart`. Arbitrer aussi le `scroll-behavior: smooth` global (l. 128) : deux animations de scroll sur le même axe = rebond. Recette dédiée au moment 2, séquence la plus fragile de la page.

---

## LOT A — Le socle : les trois zones en flux (mobile portrait) — spécification

**`woocommerce/archive-product.php`** : un conteneur `.mescreations-immersion__layer` autour des trois zones existantes (`__inner`, `__selection`, `__scrollhint`), aujourd'hui frères directs. Une balise ouvrante, une fermante. **Un `<div>` NU** — voir la contrainte a11y ci-dessous.

**`style.css`, règle de base (hors media query)** : `.mescreations-immersion__layer { display: contents; }`
Un élément en `display: contents` ne génère aucune boîte, ne peut donc pas être bloc conteneur d'un descendant absolu : les zones continuent de se résoudre contre `.mescreations-immersion` (sticky, donc positionné) **exactement comme aujourd'hui**. Corollaire : `position`, `z-index`, `transform`, `overflow`, `will-change` posés sur le conteneur sont **ignorés en desktop** → il faut que TOUT soit déclaré dans le bloc mobile, rien dehors.
⚠️ **Base = `contents`, mobile = override** — et surtout PAS l'inverse (`contents` enfermé dans `min-width: 769px`), qui laisserait le desktop sans règle du tout après une réorganisation du fichier. Cette version dégrade vers le comportement actuel, donc vers le connu.
⚠️ **JAMAIS de `role`, `aria-*`, `tabindex` ni landmark sur ce conteneur.** `display: contents` le retire de l'arbre d'accessibilité et lui vole son rôle sémantique (bug corrigé Chrome 89 / Safari 16 / iOS 17, mais le parc reste). Sur un `div` nu l'impact est nul ; un `aria-label` ajouté un jour disparaîtrait **en desktop seulement** = bug asymétrique par breakpoint, invisible en recette visuelle. À écrire en commentaire dans le markup.

**`style.css`, bloc `@media (max-width: 768px)`** — placé **APRÈS** les blocs `max-height: 840px / 700px` existants, OU ces blocs bornés en `min-width: 769px`. Non négociable : ils redéclarent `__phrase{font-size}`, `__inner{gap}`, `__describe` **sans borne de largeur**, donc un SE en paysage les matche ET matche `max-width: 768px` → à spécificité égale, le dernier déclaré gagne, et le dimensionnement en `svh` serait **silencieusement écrasé par le `clamp()` en `vw`**, précisément dans le cas le plus contraint. Même traitement pour `@media (max-width: 600px)` (l. 25127, redéclare `__selection{bottom}` et `__inner{gap}`).
- **La couche** : `height: 100vh` (repli obligatoire) **puis** `height: 100svh` ; `transform: translateY(calc(100dvh - 100svh))` ; `will-change: transform` ; `display: flex; flex-direction: column` ; `padding-top` = hauteur réelle du header fixe.
  ⚠️ Le repli n'est pas décoratif : si `100dvh` est inconnu, la `transform` est invalidée et tombe → dégradation propre. Mais **si `100svh` est inconnu, `height` tombe à `auto`** → plus de hauteur définie, `flex: 1` ne distribue rien, `height: 100%` des cards ne résout plus = **rupture dure**. Le parc WordPress voit des WebViews d'applications tierces.
- **Zone 1** (`__inner`) : `position: static; bottom: auto; top: auto; flex: 0 0 auto`. Retirer le `top` animé (propriété de layout) au profit d'un `translateY`. **Retirer aussi `will-change: transform, top` (l. 24812) en mobile** : il est transféré sur la couche, on n'empile pas deux calques promus imbriqués.
- **Zone 3** (`__selection`) : `position: static; bottom: auto; flex: 1 1 0; min-height: 0`.
  ⚠️ **`flex: 1 1 0`, PAS `1 1 auto`.** Avec `basis: auto`, la hauteur de la zone dépend encore de son contenu → pendant les ~220 ms où `swapCards()` vide le slider, la base change et **la zone 1 bouge**. `basis: 0` = « exactement ce qui reste », quel que soit le contenu. C'est la vraie expression de « la zone 3 absorbe ».
- **Zone 2** (`__scrollhint`) : `position: static; bottom: auto; flex: 0 0 auto` **et une hauteur réservée**.
  ⚠️ **Les deux indices sont en `position: absolute` à l'intérieur** : sans hauteur explicite, la zone ne réserve que son padding et le chevron déborde sous la barre d'adresse — on réintroduirait littéralement le défaut corrigé en 1ʳᵉ passe.
- **`--safe-bottom`** : déclaration déplacée sous `@media (min-width: 769px)`.

**L'invariant à obtenir, et à vérifier :**
> *La seule chose au monde qui puisse déplacer une frontière de zone est la longueur de la phrase de Robin. Photos, swap AJAX, reformatage des noms, flèches : tout le reste est absorbé.*

**Bonne nouvelle vérifiée dans le CSS :** `.product-image-main` / `.product-image-hover` sont en `position: absolute; inset` (l. 12449-12460) → elles ne contribuent pas à la hauteur intrinsèque de `.product-media` → **le moment de chargement d'une image est structurellement sans effet**. Ce n'est pas une hypothèse. De même, le reformatage des noms par `product-name-formatter.js` fait grandir `.product-info` et la photo rend la hauteur : la card ne change pas de hauteur, les frontières ne bougent pas.

**Recette Robin :** scroller lentement haut→bas puis remonter, barre déployée puis rétractée → aucun à-coup ; le vocabulaire à employer est « ça **glisse** » (voulu) vs « ça **saute** » (reflow, à signaler). Puis desktop et `/nos-creations/` **rigoureusement identiques**.

**⚠️ Vérifier aussi :** le `padding-top` contre la hauteur réelle du header, en état `.is-scrolled` (header opaque) — aujourd'hui `__inner` est centré et ne peut pas toucher le header ; en flux, il est posé à `padding-top` et la pill peut passer dessous. Et le `backdrop-filter` du bouton (l. 24888) à l'intérieur d'un ancêtre promu : combinaison historiquement fragile, le backdrop peut se vider. Risque faible (le bouton vit déjà dans un ancêtre transformé) mais c'est une ligne de recette.

## LOT B — La card en flux (le vrai markup, la couche invisible)

⚠️ **Le markup réel ne ressemble pas à celui de la maquette** : il y a un `<a class="product-card-link">` entre `.product-card-cinetique` et les blocs, et **quatre** enfants (`product-media`, `product-info`, `product-actions`), pas deux. `.product-card-cinetique` n'est PAS `display: flex` en contexte immersion (il ne l'est que sous `.creations-grid`, l. 8434). **C'est la couche que la maquette masque, et celle qui, oubliée, ferait échouer silencieusement toute la promesse.**
- Card **et** lien en flex-colonne ; lien en `flex: 1 1 auto; min-height: 0` ; `product-info` **et** `product-actions` en `flex: 0 0 auto` ; `product-media` en `flex: 1 1 auto; min-height: <plancher>`.
- Neutraliser les 4 hauteurs `product-media` en `vh` **par surcharge** dans le bloc mobile (`height: auto`), jamais par suppression.
- **Plancher sur `.mescreations-immersion__pcard--sur`** : c'est la card la plus haute du slider (24px de padding + ~121px de texte en px fixes, l. 25066) et elle n'a aucun plancher → **c'est elle qui casse en premier**.
- Tout préfixé `.mescreations-immersion__slider` — `.product-card-cinetique` est partagé avec `/nos-creations/`.

**Recette :** `/nos-creations/` et le catalogue sous le hero strictement identiques. Slider : swipe, flèches, dernière card sur-mesure.

## LOT C — Le texte : budget vertical, coupe en fondu, borne serveur

- **Phrase, pill et bouton dimensionnés en `svh`** (préfixés). ⚠️ Dans la maquette les 3 éléments sont en `svh` ; sur le site réel **2 sur 3 sont en px fixes ET partagés** : `.conseiller-sig--v1` (l. 8014 — avatar 34px, accroche 24px, `margin-bottom: 16px` qui s'additionne au `gap`) et `__describe` (13,5px). Plancher réel de la zone 1 ≈ 62px de pill + ~40px de bouton, soit **~40 % plus lourd que la maquette validée par Robin**. Sans cette conversion, « le texte s'adapte au budget vertical » ne vaut que pour un tiers de la zone. `.conseiller-sig--v1` est partagé avec la home et `/conseils-eclaires/` → **préfixer impérativement**.
- **Coupe en fondu** sur les dernières lignes de la phrase (décision Robin, cf. plus haut).
- ⚠️ **`__phrase { min-height: 2.4em }`** (l. 24838) devient un **plancher de flex** dans le nouveau modèle (un `min-height` explicite bat le `min-height: auto`). Nécessaire pour l'état « 3 points » du loader, mais c'est lui qui décide du point de troncature. À traiter consciemment.
- **`functions.php` : troncature dure du conseil IA.** Le « max 300 caractères » (~l. 4019) est une **instruction de prompt**, jamais appliquée — aucun `mb_substr`. Un modèle qui rend 420 caractères n'est pas un cas d'école. **C'est la garde la moins chère de tout le chantier**, et sans elle le modèle n'a aucune borne d'entrée.

## LOT D — Paysage : bascule de mode (décision Robin)

⚠️ **Le cas n'est pas celui qu'on croit.** Un iPhone 14 en paysage fait **844 × 390** : 844 > 768, il **sort** du bloc mobile et garde la mise en page absolue. C'est le **SE / 8 en paysage (667 × 375)** qui **entre** dans le modèle en flux, avec ~330 svh. Budget calculé : après zone 1 au plancher (~165px) et zone 2 (~46px), il reste ~56px pour la zone 3, dont 27 de titre → **29px de slider** pour une card qui ne peut pas descendre sous ~172px. Aucune zone ne se chevauche — la promesse est tenue à la lettre — et le résultat est inutilisable.
→ **Règle `max-width: 768px` ET `max-height: ~500px`** : écran A = texte seul, écran B = carrousel seul. Bascule, pas cohabitation.

---

### Recette finale (appareil réel, texte le plus long)
430×838 · 390×752 · 375×720 · 375×618 · **667×375 (SE paysage — le cas qui décide)** · une **rotation portrait↔paysage pendant que le track est épinglé** · un moment 2 complet avec swap AJAX.

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

**(b) ✅ SECOND CHERRY-PICK FAIT — `master` = `b930f09` (2026-08-25).** 6 fichiers catalogue repris à l'identique de test, `functions.php` non concerné (ses require du catalogue y étaient déjà et n'ont pas bougé). Vérifié avant push : aucun fichier hors catalogue, 0 commit perdu, plus aucune lecture de `get_field('poids')` ni de `sapi_catalogue_product_weight()` dans la couche catalogue.

`master` porte donc maintenant DEUX commits catalogue : `a92516c` (page prix + tarifs pro) puis `b930f09` (poids). **Le workflow « Deploy to Production » est toujours à lancer à la main par Robin** — le push ne déploie rien.


---

## ✅ EN PRODUCTION ET AUDITÉ (2026-08-25) — `master` = `b930f09`

Robin a lancé le déploiement, créé la page `/catalogue-prix` en prod et exclu les deux pages du sitemap Yoast. Audit complet de `atelier-sapi.fr` :

| Contrôle | Résultat |
|---|---|
| `/catalogue` | 200 · 0 `€` · 0 ligne Poids · 0 « Kilos le matin » |
| `/catalogue-prix` | 200 · `has-prices` · 36 fiches · 173 poids dans les matrices · `noindex` · sans bloc PDF |
| PDF public (lampadaires) | 200 `application/pdf` · 2,1 Mo **généré en 2,1 s** — `vendor/`/mPDF bien régénéré par le workflow |
| Étanchéité du PDF (extracteur `/ToUnicode`) | 12 pages · 0 lien `/URI` · **0 `€`, 0 `Prix`, 0 `TTC`, 0 `HT`** · « Culot » 8 fois pour 8 fiches |
| `admin_post` tarif pro, non connecté | refusé |
| Sitemap Yoast | **15 pages, plus aucune entrée catalogue** (étaient 17) |

### ⏳ Ce qui reste ouvert

1. **Mentions légales du tarif pro** — les cinq crochets `[à compléter]` (minimum de commande, port, délai, règlement, capital social) partiraient tels quels chez un revendeur. Contenu, côté Robin.
2. **La blague « 35 Kilos le matin » subsiste sur les pages produit publiques.** `woocommerce/single-product.php:777` lit la même meta ACF orpheline (affichée l.829). Vérifié en prod sur `olivia-la-gardiena` et `claudine-la-turbine`. Hors périmètre catalogue — décision Robin : même correctif (lire les variations) ou plus tard.
3. **Exclusion Yoast ancrée dans le code** — proposée, non faite. Un filtre `wpseo_exclude_from_sitemap_by_post_ids` excluant toute page portant le template `page-catalogue.php`. Le réglage manuel actuel vit en base : il sera à refaire si une page est recréée, ce qui vient précisément d'arriver pour `/catalogue-prix`. Demanderait un 3ᵉ cherry-pick, court.
4. **Écart de 1,8 mm** entre les deux largeurs utiles (179,2 mm mesuré par Robin vs 181 en constante côté profil pro). Non tranché.
5. **Cohérence des PVP site / Etsy** à vérifier avant le premier envoi d'un tarif : le PDF pro affiche le PVP conseillé à côté du prix d'achat, un écart serait exposé au revendeur.


---

## ✅ RECETTE PRODUCTION VALIDÉE PAR ROBIN (2026-08-25) — chantier clos

Les cinq contrôles humains sont passés sur `atelier-sapi.fr` : tarif pro généré depuis la prod, page prix sur mobile, bouton PDF du catalogue, fiches techniques du catalogue prescripteurs, et poids dynamique d'une fiche produit.

**En production :** `/catalogue` (inchangée, sans prix), `/catalogue-prix` (PVP TTC + poids en matrice croisée, `noindex`, hors sitemap), PDF public refondu, et `Produits > Catalogue PRO` pour les tarifs professionnels. `master` = `b930f09`.

### Ce qui reste, hors code

1. **Les cinq crochets `[à compléter]`** des mentions légales du tarif pro (minimum de commande, port, délai, règlement, capital social) — à remplir avant le premier envoi à un revendeur. Robin s'en charge.
2. **Cohérence des PVP site / Etsy** à vérifier avant ce même premier envoi. Robin s'en charge.

### Défaut connu, laissé volontairement (décision Robin)

La meta ACF `poids` reste en base sur 5 produits (Olivia ×2, Charlie, Claudine, Vincent) et continue d'être écrite **dans le code source** de leur fiche produit, puis remplacée par « Faites votre choix » dès le chargement — invisible à l'écran, visible seulement au source ou pour un robot sans JavaScript. La couche catalogue, elle, ne la lit plus nulle part.

⚠️ **Ne pas supprimer cette meta sans corriger le code d'abord.** [single-product.php:829](woocommerce/single-product.php) n'ajoute la ligne « Poids » que si le serveur a trouvé une valeur : c'est cette meta parasite qui fait exister la ligne sur ces 5 produits, et donc qui permet au JavaScript d'y écrire le poids de la variation. La supprimer ferait disparaître la ligne — et le poids dynamique avec. Le correctif propre (rendre la ligne inconditionnelle sur les produits à variations, avec « Faites votre choix » en valeur initiale) a été proposé et **refusé pour l'instant**. Il ferait au passage fonctionner le poids dynamique sur les 36 produits au lieu de 8.

### Non tranché, sans conséquence

Écart de 1,8 mm entre les deux largeurs utiles de la bande photo (179,2 mm mesuré par Robin vs 181 en constante côté profil pro). Robin : « on s'en moque ».


---

## 📨 RETOUR À COWORK — Catalogue prix + Tarifs professionnels : EN PROD ✅ (2026-08-25)

> Ce retour **remplace** celui du 24/08 (« livré sur test, volontairement pas en prod »). Tout est désormais en ligne sur `atelier-sapi.fr`, déployé et recetté par Robin.

### Ce que Robin peut faire dès maintenant

**Envoyer le lien `/catalogue-prix`** à un décorateur ou un architecte qui réclame les prix publics. La page reprend le catalogue avec les PVP TTC en tableau croisé essences × dimensions, et le poids de chaque déclinaison. Elle est `noindex` et retirée du sitemap : elle ne se trouve pas sur Google, le lien se transmet à la main.

**Générer un tarif professionnel** depuis `Produits > Catalogue PRO` : il choisit un taux de remise, coche les produits, saisit éventuellement le nom du revendeur, et récupère un PDF de marque avec prix pro HT, PVP conseillé et poids.

**Le point d'architecture à retenir côté commercial :** le tarif remisé n'existe nulle part sur le web. Le taux est saisi au moment de l'export et disparaît ensuite. Muse peut donc recevoir un -12 % et un prospect de salon un -30 % sans qu'aucun des deux ne puisse deviner l'autre, et sans que le site ait à savoir qui est qui.

### ⚠️ Deux choses à finir AVANT le premier envoi à un revendeur

1. **Les cinq crochets `[à compléter]`** des mentions légales, dans l'admin : minimum de commande, frais de port, délai de fabrication, conditions de règlement, capital social. Tant qu'ils sont là, ils partent tels quels dans le PDF. À remplir **une seule fois**, la valeur est ensuite conservée d'un export à l'autre.
2. **La cohérence des PVP entre le site et Etsy.** Le tarif pro affiche le prix public conseillé juste à côté du prix d'achat : si les deux divergent sur un modèle, le revendeur voit l'écart. Jamais vérifié.

Robin a dit prendre ces deux points en charge. **Cowork peut utilement le relancer dessus** — ce sont les seuls vrais bloquants commerciaux.

### Ce que Cowork peut préparer

La mécanique est prête, il manque le commercial : un **message-type d'envoi du tarif** à un revendeur (Muse, Ankorstore, prospects salons), et un second pour transmettre `/catalogue-prix` à un prescripteur qui veut les prix publics. Le journal des 20 derniers exports (date, client, taux, nombre de produits) est consultable sous le formulaire d'export — utile pour savoir qui a reçu quoi.

### Constats utiles au business

- **Les prix varient fortement selon l'essence** : jusqu'à 80 € d'écart sur une même taille (Vincent l'incandescent, 85 € en peuplier contre 105 € en okoumé). D'où le tableau croisé plutôt qu'une simple liste.
- **Une 3ᵉ essence existe en base, « Peuplier teinté noir » (+50 €)**, que le catalogue n'a jamais affichée. Périmètre volontairement maintenu à deux bois — **c'est un choix commercial à reconsidérer si elle se vend**, pas un oubli technique.
- **Le poids de chaque déclinaison est désormais exposé** dans la matrice de prix et dans le tarif pro. Pour un revendeur, c'est ce qui conditionne le port.
- **Aucune promotion en cours** au moment du développement. Le tarif se base sur le prix régulier : une promo de saison ne contaminera jamais un tarif annuel déjà envoyé.

### Défaut connu, laissé volontairement

Sur 5 fiches produit (Olivia ×2, Charlie, Claudine, Vincent), une ancienne valeur de test — « 35 Kilos le matin, 22 le soir » — subsiste en base et apparaît dans le **code source** de la page, avant d'être remplacée à l'affichage. Invisible à l'écran pour un visiteur. Robin a choisi de ne pas y toucher.

⚠️ **Si le sujet revient : ne pas supprimer cette donnée sans faire corriger le code d'abord.** C'est elle qui fait exister la ligne « Poids » sur ces 5 fiches ; la supprimer seule ferait disparaître le poids dynamique au lieu de le réparer. Le détail technique est consigné plus haut dans ce fichier.

