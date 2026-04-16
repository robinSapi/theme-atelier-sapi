# Tasks — Coordination Cowork ↔ Claude Code

## 📋 À faire

*(rien en cours)*

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
