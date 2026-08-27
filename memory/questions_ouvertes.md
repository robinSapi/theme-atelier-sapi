# Questions ouvertes et pistes d'amélioration

> Tout ce qui a été **trouvé mais pas corrigé** au 25/08/2026, avec ce que ça coûte de le laisser.
> Les correctifs déjà livrés sont dans `claude_code_queue.md`.
> Rien ici n'est urgent au point de bloquer une mise en prod. Mais rien n'est gratuit non plus.

---

## 1. Décisions qui t'appartiennent

Ce ne sont pas des bugs. Ce sont des choix produit ou catalogue que je ne peux pas prendre à ta place.

### 1.1 Le projet mémorisé n'a aucune date de péremption
`created_at` et `updated_at` sont bien écrits dans le navigateur du visiteur, mais **jamais relus**. Un projet vieux d'une semaine est traité exactement comme celui d'il y a une minute.

*Ce que ça donne :* quelqu'un revient dix jours plus tard, la modale lui reparle de son salon comme si la conversation n'avait jamais été interrompue. C'est ce qui rendait le défaut de la bulle périmée si brutal.

*À trancher :* au bout de combien de temps un projet cesse-t-il d'être « en cours » ? Une journée ? Une semaine ? Et à l'expiration : on efface, ou on demande « c'est toujours pour ton salon ? »

---

### 1.2 Il n'existe pas de pièce « salle de bain »
Ton référentiel compte **sept pièces** : cuisine, bureau, salon, chambre, chambre d'enfant, entrée, escalier. Un visiteur qui écrit « salle de bain » ne peut structurellement pas être compris — l'extraction ne renverra jamais rien.

*Ce que ça donne :* depuis les correctifs, il atterrit sur le formulaire de contact. C'est honnête, mais c'est une vente qui passe par toi au lieu de passer par le site.

*Sujet catalogue, pas code.* Question : y a-t-il d'autres pièces manquantes que les visiteurs nomment ? Salle à manger, couloir, atelier, terrasse ? **Tes recherches internes et les messages de contact le diraient.**

---

### 1.3 On ne peut pas revenir de la conversation vers le questionnaire
Une fois dans le chat avec Robin, il n'existe **aucun chemin de retour** vers le parcours guidé. Ni bouton, ni lien. Seulement la croix.

*Ce que ça donne :* le visiteur qui clique le champ texte par erreur, ou qui préfère finalement les questions aux mots libres, a perdu le questionnaire pour cette session.

*À trancher :* faut-il un « Revenir aux questions » ? Ou considère-t-on que la conversation est plus riche et qu'on ne revient pas en arrière ?

---

### 1.4 La première question a deux visages
Elle s'affiche sur l'écran d'accueil **avec** le champ « ou décris ton projet » en dessous. Si on y revient par « Étape précédente », elle s'affiche **sans**. La seconde porte disparaît sans explication.

*À trancher :* incohérence à corriger, ou choix assumé (on n'offre le texte libre qu'à l'entrée) ?

---

### 1.5 Le bouton « Découvrir » des cartes, en desktop
Il est masqué en mobile — la carte entière est déjà un lien, il ne servait qu'à manger 45 pixels de photo. En desktop il reste, parce qu'il sert d'indication au survol.

*À trancher si tu veux des photos plus grandes en desktop :* le masquer là aussi rendrait ~40 pixels à l'image. Une ligne.

---

## 2. Défauts connus, non corrigés

### 2.0 Le récap produit peut perdre son haut, sans recours
`.modal__body-content` est en `justify-content: center` avec `min-height: 0`. Un débordement se répartit donc **en haut autant qu'en bas** — et le haut n'est pas rattrapable au défilement, `scrollTop` ne pouvant pas être négatif.

*Ce que ça donne :* sur iPhone SE, après la compaction du 26/08, il reste environ **9 pixels de marge**. Si une phrase de conseil part sur trois lignes, le titre « TON PROJET » et sa première pastille disparaissent — et rien ne signale au visiteur qu'il manque quelque chose.

*Ce qu'il faudrait :* un `justify-content: flex-start` scopé sur cet écran en mobile. Le centrage n'a de sens que quand le contenu tient. **Une ligne.**

---

### 2.1 Le conseil de l'IA n'a aucune limite de longueur — ✅ CORRIGÉ le 26/08
`sapi_borner_conseil()` coupe à la dernière **phrase complète** qui tient sous 320 caractères, avec repli au dernier mot entier. Le seuil d'acceptation d'une coupe est volontairement bas (40) : un seuil élevé se retournait contre la règle qu'il servait, en refusant la coupe propre sur le dépassement le plus probable (accroche courte + longue phrase) et en rendant un conseil amputé de 319 caractères là où une première phrase complète de 50 suffisait.

⚠️ **Trois nombres cohabitent désormais sans rien qui les relie** : 300 (la consigne du prompt), 320 (la borne du code), ≤199 (le plus long des replis figés). Si la zone du hero est un jour réduite, la borne ne suivra pas toute seule.

*Texte d'origine, conservé pour mémoire :*
Le prompt dit « 1 à 2 phrases, max 300 caractères ». **C'est une consigne, pas une contrainte** : rien ne l'applique à la réception. Un modèle qui rend 450 caractères passe tel quel.

*Ce que ça donne :* sur mobile, le texte déborde de sa zone et se fait couper. Toute la mise en page du hero est dimensionnée en supposant un texte borné — cette borne n'existe pas.

*Ce qu'il faudrait :* une troncature dure côté serveur, vers 320 caractères. **C'est la garde la moins chère de tout le chantier**, et le précédent existe déjà : la même erreur de raisonnement avait produit le tiret long qu'on a fini par filtrer côté serveur.

---

### 2.2 Le mode paysage sur petits téléphones n'est pas traité
Un iPhone SE ou 8 tenu à l'horizontale entre dans la mise en page mobile avec environ 330 pixels de haut. Après le texte et l'indice, il ne reste qu'une trentaine de pixels pour les cartes, qui en demandent au minimum 170.

*Ce que ça donne :* rien ne se chevauche — la structure tient — mais la carte est réduite à un liseré de photo. Inutilisable.

*Ta décision d'alors :* écran A avec le texte seul, écran B avec le carrousel seul. **Jamais codé.** Un iPhone récent en paysage fait 844 de large et sort du mode mobile : seuls les petits modèles sont concernés.

---

### 2.3 Quand l'IA retire un critère, le visiteur ne le voit pas
L'encart « Filtres appliqués » ne liste que les **ajouts**. Si Robin décide de retirer une contrainte au fil de la conversation, rien ne l'indique.

*Ce que ça donne :* le visiteur croit que son critère tient toujours, et ne comprend pas la sélection qu'il reçoit.

---

### 2.4 Le focus ne revient jamais après fermeture de la modale
Le code qui devait mémoriser l'élément déclencheur cherche une méthode qui n'existe pas sur l'événement reçu : il vaut donc **toujours vide**. La restauration du focus à la fermeture est morte depuis le début.

*Ce que ça donne :* une personne qui navigue au clavier se retrouve renvoyée en haut de la page à chaque fermeture. **Accessibilité.**

---

### 2.5 « Recommencer » désynchronise l'écran et l'adresse
L'action retire `?piece=` de l'URL alors que la page derrière affiche toujours l'immersion de l'ancienne pièce. Sans conséquence tant qu'on ne recharge pas — mais l'écran et l'adresse ne disent plus la même chose, et un rafraîchissement fait disparaître le hero.

---

### 2.6 Un résidu d'affichage assumé
Sur un viewport de plus de 840 pixels de haut **et** moins de 768 de large — en pratique, le site ajouté à l'écran d'accueil en plein écran sur un grand iPhone — la carte garde une ancienne hauteur et il reste environ 8 pixels de contact avec l'indice. Cas jugé trop marginal pour un quatrième réglage.

---

## 3. Ce que ton tableau de bord ne voit pas

**Trois chiffres sont faux depuis toujours**, et tu ne peux pas t'y fier tant qu'ils ne sont pas corrigés.

| Ce que tu lis | La réalité |
|---|---|
| `home_picker` : **toujours 0** | La modale n'est jamais rendue sur la home. Ce point d'entrée ne peut pas être compté. |
| `freetext` : **toujours 0** | Le paramètre d'URL est effacé **avant** d'être lu. Ces parcours sont comptés comme de simples visites `/mes-creations/`. |
| `advice_text` : **jamais rempli** dans les sessions abouties | La session est finalisée **avant** que le conseil ne soit écrit. |
| `matching_product_ids` : **toujours vide** | Un scan du DOM cherchait le markup WooCommerce par défaut, absent de ce thème. Retiré le 27/08 plutôt que réparé : même corrigé, il aurait visé la grille BASSE du catalogue, alors que ce que le visiteur voit comme sa sélection est le carrousel de l'immersion. **La bonne source est le serveur**, qui calcule déjà cette liste dans les deux endpoints IA (`functions.php` ~3218 et ~4090) — il reste à la rattacher à la session. C'est la seule donnée qui dirait si les sélections affichées ont du sens. |

*Ce que ça coûte :* tu ne sais pas combien de visiteurs passent par le champ libre plutôt que par les cartes — alors que c'est précisément le parcours qu'on vient de réparer, et que tu n'as aucun moyen d'en mesurer l'effet.

*Ce qu'il faudrait :* capturer le point d'entrée avant le nettoyage d'URL, et déplacer la finalisation après l'écriture du conseil. **Deux corrections courtes, sans effet visible pour le visiteur.**

---

## 4. Code mort et vestiges

Rien de tout ça ne casse quoi que ce soit. Mais chaque vestige est un piège pour la prochaine lecture — et **deux d'entre eux ont déjà produit des bugs réels** dans ce chantier.

- `data-action="open-modal"` et `data-modal-state` : plus lus par personne. Les deux boutons qui les portent ne marchent que grâce à leur propre écouteur.
- L'entrée `state:'s3'` : plus aucun émetteur depuis la suppression des cards.
- `sapiShopRefilter`, `buildFilterMeta`, `.conseiller-card--mon-projet`, `KEY_LABELS`, `CONTACT_SURMESURE_URL` : vestiges de refontes.
- Le CSS de `.conseiller-card--mon-projet` (~280 lignes) décrit un composant qui n'existe plus.
- L'en-tête du fichier de la modale décrit une architecture de **trois refontes** en retard.
- Les dictionnaires `'table'` dans les prompts IA : la question a été retirée du parcours, les clés ne sont plus lues.
- `.conseiller-cards-zone`, `.mes-creations-section-divider` : règles CSS d'éléments supprimés.

> ⚠️ **Si le plugin WooCommerce Variation Swatches était un jour désactivé**, la présélection poserait bien l'essence — elle passe par le menu caché, que le filtre du thème conserve — **mais la pastille maison ne s'allumerait pas** : `cinetique.js` synchronise le menu vers la pastille **une seule fois à l'init**, sans écouteur `change`, alors que la présélection arrive plus tard. Bonne variation, bon prix, bonne photo, pastille visuellement non sélectionnée.
> Le correctif serait un écouteur `change` dans `cinetique.js`, **pas** un retour en arrière sur la branche `.material-option` retirée le 26/08. Et ne pas toucher au CSS `.material-option` sans traiter ce point d'abord.

> ⚠️ **Le vestige dangereux n'est pas le code mort visible, c'est le repli silencieux.**
> Trois fois dans ce chantier, un objet supprimé lors d'une refonte a laissé un code qui **ne plante pas** : il renvoie une valeur vide et valide, tout continue de tourner, et le comportement est simplement faux. `$sapi_filter_rules` (« rien de cassé » = « rien ne s'applique »), puis `sapiMegaFilter` côté conseil, puis le même côté chat — celui-ci a poussé le sur-mesure à tort dans **chaque** conversation pendant des semaines.
> **À chaque suppression d'un objet global, chercher tous ses lecteurs et vérifier ce que fait leur repli.**

---

## 5. Chantiers ouverts, hors modale

- **Formulaire Inspiration** : il n'a qu'un nonce, ni honeypot ni limite de fréquence, contrairement aux quatre autres formulaires du site. Risque faible (un email, pas de message libre), signalé lors de l'audit anti-spam et jamais traité.
- **Le code « grappe »** (`diversify_format`) est conservé volontairement, orphelin, en attendant d'être recâblé comme rampe vers le sur-mesure.
- **Le `CLAUDE.md` du dossier est périmé** : il dit que je ne touche jamais aux fichiers du thème, ce qui n'est plus vrai depuis le 25/08.

---

## Si je devais n'en retenir que trois

1. **La borne de longueur du conseil IA** (§2.1) — la moins chère, et toute la mise en page du hero repose sur une limite qui n'existe pas.
2. **Le tableau de bord** (§3) — tu viens de réparer le parcours par le texte libre et tu n'as aucun moyen de mesurer si ça change quelque chose.
3. **La péremption du projet mémorisé** (§1.1) — c'est une décision, pas du code, et c'est elle qui commande la qualité de tous les retours de visiteurs.
