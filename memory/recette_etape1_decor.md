# Recette — Étape 1 : le décor et la sélection sortent d'un seul calcul

**Branche :** `test-theme-sapi-maison` · **Date :** 26/08/2026
**Durée :** 20 à 25 minutes en prenant son temps.

---

## Ce que tu es en train de vérifier

Avant, deux choses étaient calculées séparément et pouvaient se contredire :
- **la sélection** — les produits, décidés par six critères ;
- **le décor** — le titre et la pill, décidés par la seule pièce.

Ta capture du couloir en était la preuve : le conseil disait « appliques pour ton couloir », le titre disait « ton entrée », le carrousel montrait des suspensions.

Maintenant les deux sortent de la **même fonction serveur**. Cette recette vérifie deux choses, et elles n'ont pas la même importance :

1. **Que rien n'est cassé** — le scroll, la chorégraphie, le chemin normal. C'est le plus important, et c'est le bloc A.
2. Que le titre suit bien la sélection. C'est le bloc B.

> ⚠️ **Si un test du bloc A échoue, arrête tout et dis-le moi.** Ne continue pas les autres blocs : le reste n'a aucune valeur si l'acquis est perdu.

---

## Préparation

**Vider le projet mémorisé entre chaque scénario.** Sans ça tu testes le projet précédent sans le savoir — c'est exactement le piège qui nous a coûté deux allers-retours cet après-midi.

Console du navigateur (F12, onglet Console) :

```js
localStorage.removeItem('sapiProject'); location.reload();
```

Garde cette ligne sous la main, tu vas la retaper souvent.

**Ouvre aussi l'onglet Réseau** (F12 → Réseau, filtre `admin-ajax`). Tu y verras si une requête part ou non — plusieurs tests reposent là-dessus.

**Deux tailles d'écran :** desktop, et mobile réel ou simulé en 390 × 844. Le mobile n'est pas optionnel : c'est là que la mise en page est la plus tendue.

---

## Bloc A — Rien n'est cassé (prioritaire)

### A1 · Le chemin normal, celui de 95 % des visiteurs

Projet vidé. Home → clique une carte-pièce **Salon**.

- [ ] La photo apparaît, la phrase s'écrit lettre par lettre, le bouton blanc arrive **après** la phrase
- [ ] Le titre affiche **« Ma sélection pour ton salon »** — exactement comme avant
- [ ] Onglet Réseau : **aucune requête `admin-ajax`** n'est partie

> **Pourquoi ce dernier point compte :** au chargement, aucun critère n'est connu à part la pièce. Le moteur renvoie alors quatre catégories, donc le titre reste neutre — et rien ne doit être demandé au serveur. Si tu vois une requête ici, ma déduplication ne fait pas son travail et chaque visiteur paie un aller-retour pour rien.

### A2 · Le scroll, ton acquis

Toujours sur le salon.

- [ ] La molette descend : la photo se floute, le texte remonte, les cards apparaissent
- [ ] Les **trois positions d'arrêt** fonctionnent (texte → carrousel → catalogue)
- [ ] En remontant, tout se rejoue à l'envers sans à-coup
- [ ] Sur mobile, le doigt **sur le carrousel** fait défiler les cards horizontalement, et le doigt **à côté** fait défiler la page

### A3 · Les autres pièces

Passe rapidement sur **Cuisine**, **Chambre**, **Escalier**.

- [ ] Chaque pièce a sa photo, sa phrase, son titre
- [ ] Aucun titre vide, aucun « ta pièce » qui traîne

---

## Bloc B — Le correctif

### B1 · Le titre suit la sélection

Projet vidé. Va sur **Salon**, ouvre la modale (« Décrire mon projet en détail »), réponds au questionnaire en choisissant **sortie au plafond**.

- [ ] À la fermeture, le titre devient **« Mes suspensions pour ton salon »**
- [ ] Le carrousel ne contient que des suspensions
- [ ] La pill en haut dit toujours « Mon conseil pour ton salon »

### B2 · La simultanéité — le test le plus important du bloc

Recommence, en regardant **l'instant du changement**. Le titre et les cards font un fondu ensemble.

- [ ] Le titre ne change **ni avant, ni après** les cards
- [ ] À aucun moment tu ne lis un titre qui contredit les produits affichés, même une fraction de seconde

> **Pourquoi :** c'était tout le défaut. Un titre qui change trop tôt annonce des produits qui ne sont pas encore là ; trop tard, il ment sur ceux qui y sont. Les deux se font dans le même creux du fondu, volontairement.

### B3 · Ton scénario du couloir

Projet vidé. Depuis la home, utilise le **champ texte libre** et écris :

> *une petite applique pour mon couloir*

- [ ] Le chat propose une sélection et **un seul bouton**, « Voir la sélection pour mon projet »
- [ ] Il mène à une page dont le libellé du carrousel dit **« Mes appliques pour… »**
- [ ] Le carrousel montre bien des **appliques**, pas des suspensions

> C'est le test de ta capture. Deux précisions : le libellé « Voir les appliques » a existé une journée, il est mort avec la décision de renvoyer vers toi les pièces hors périmètre — un seul bouton dès que la pièce est connue. Et le décalage restant, le site qui dit « entrée » là où tu as écrit « couloir », est **attendu** : c'est le sujet du vocabulaire, qu'on n'a pas tranché.

### B3 bis · La salle de bain, hors périmètre

Projet vidé. Champ texte libre : *une lampe pour ma salle de bain*.

- [ ] L'IA **ne pose aucune question** sur la vasque ou la prise
- [ ] Elle dit que c'est toi qui dois répondre, et affiche l'écran de contact
- [ ] Aucun bouton « Voir la sélection » nulle part
- [ ] Après cet échange, le projet mémorisé ne contient **pas** de pièce approchante — surtout pas `cuisine`

> **Le piège exact que la relecture a trouvé :** une autre règle du prompt, plus ancienne et marquée « très important », encourageait à proposer des modèles approchants. Elle annulait celle qu'on venait d'écrire. Une salle de bain serait repartie avec `piece: cuisine`, un slug parfaitement valide — et le bouton « Voir la sélection » se serait rallumé au retour.

### B3 ter · Une question légitime ne doit pas pousser vers la sortie

Projet vidé. Champ texte libre : *je cherche une applique* — sans dire la pièce.

- [ ] Robin demande pour quelle pièce
- [ ] **Aucune barre de boutons** ne s'affiche sous sa question
- [ ] Tu réponds « pour mon salon » → le bouton « Voir la sélection » apparaît

> **Pourquoi :** montrer « En parler à Robin » en bouton plein sous une question, c'est répondre « laisse tomber » à quelqu'un à qui on demande de préciser. La barre attend qu'il y ait vraiment quelque chose à proposer.

### B4 · Quand la catégorie n'est PAS certaine

Projet vidé. Salon, questionnaire, choisis **« je ne sais pas »** pour la sortie.

- [ ] Le titre reste **« Ma sélection pour ton salon »**, sans nom de catégorie

> **Pourquoi :** quatre catégories sont possibles. Annoncer « mes suspensions » serait un mensonge — dans l'autre sens que celui de ta capture, mais un mensonge quand même. Le titre ne se mouille que s'il est sûr.

### B5 · Le cas « pas de sortie électrique »

Salon, questionnaire, **pas de sortie électrique**.

- [ ] Titre neutre (trois catégories possibles : lampadaires, lampes à poser, appliques)

Puis la même chose en **Cuisine** :

- [ ] Regarde ce que donne le titre et **note-le**

> En cuisine, tes règles retirent les lampes à poser. S'il ne reste plus qu'une catégorie, le titre se nommera. Je ne sais pas si tes règles en prod retirent aussi les lampadaires — c'est réglable dans l'admin du Conseiller, et ce test me le dira.

---

## Bloc C — Les textes longs

### C1 · Le pire cas de longueur

**Chambre d'enfant**, questionnaire, **pas de sortie** → le titre le plus long possible.

Sur mobile 390 px :

- [ ] Le titre tient sur **deux lignes maximum**
- [ ] Il ne chevauche **ni** la phrase au-dessus, **ni** les cards en dessous
- [ ] La photo des cards reste lisible

> **Pourquoi ce test existe :** j'ai failli livrer un titre qui faisait 517 px de large pour 327 px disponibles. La formulation courte ramène à 404 px — soit exactement le pire cas qui existait **déjà** avant ce chantier. Ce test vérifie que je ne me suis pas trompé dans ce calcul.

### C2 · L'escalier

**Escalier** → « ta cage d'escalier », le possessif le plus long.

- [ ] Titre lisible sur mobile, deux lignes maximum

---

## Bloc D — Quand ça se passe mal

### D1 · Le serveur ne répond pas

Salon, ouvre le questionnaire. **Avant de le terminer**, passe l'onglet Réseau en mode **hors ligne**. Termine le questionnaire.

- [ ] Le titre **ne devient pas vide** et n'affiche pas « undefined »
- [ ] Il garde simplement son ancienne valeur
- [ ] La page ne plante pas

> **Pourquoi :** c'est le mode de panne le plus vicieux. Un titre vide sur une page par ailleurs normale ne ressemble pas à un bug, ça ressemble à un oubli de contenu — et personne ne le signale.

### D2 · Deux affinages coup sur coup

Salon → questionnaire (plafond) → referme → rouvre → questionnaire (au mur).

- [ ] Le titre finit sur **« Mes appliques pour ton salon »**, pas sur l'ancien
- [ ] Pas de clignotement entre les deux

---

## Bloc E — Accessibilité (30 secondes)

Sur une page où le titre s'est nommé, inspecte la balise `<section class="mescreations-immersion">`.

- [ ] Son `aria-label` dit la même chose que le titre visible

---

## Grille à me renvoyer

| Bloc | Vert / Rouge | Ce que tu as vu |
|---|---|---|
| A1 chemin normal | | |
| A2 scroll | | |
| A3 autres pièces | | |
| B1 titre suit | | |
| B2 simultanéité | | |
| B3 couloir | | |
| B4 catégorie incertaine | | |
| B5 cuisine (titre observé) | | |
| C1 texte long mobile | | |
| C2 escalier | | |
| D1 hors ligne | | |
| D2 double affinage | | |
| E aria-label | | |

---

## Ce qui doit te faire arrêter immédiatement

- Le scroll perd sa fluidité ou une position d'arrêt disparaît
- Une requête `admin-ajax` part au simple clic sur une carte-pièce
- Un titre vide, ou qui affiche `undefined`
- Le titre nomme une catégorie que le carrousel ne montre pas

Les trois premiers sont des régressions sur des acquis. Le quatrième est le défaut d'origine qui reviendrait sous une autre forme.
