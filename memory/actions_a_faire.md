# Ce qui reste à faire — état au 27/08/2026

> Établi après une journée de douze lots livrés et recettés, et cinq audits de terrain.
> Rien ici ne bloque une mise en production. Mais rien n'est gratuit non plus.
>
> **Deux natures de choses dans cette liste**, et elles ne se traitent pas pareil :
> ce qui est **du code** (je peux le faire), et ce qui est **une décision** (toi seul).
> C'est indiqué à chaque fois.

---

## 🔴 D'abord — ce qui te fait décider dans le vide

Ces trois-là faussent des chiffres sur lesquels tu prends des décisions catalogue et commerciales.

### 1. Le conseil de Robin n'est jamais enregistré · **code**
`advice_text` est vide dans toutes les sessions abouties. La session est finalisée **avant** que le conseil ne soit écrit, et la finalisation ferme la ligne : plus rien ne repart ensuite.

*Ce que ça coûte :* tu ne sais pas ce que Robin raconte réellement à tes visiteurs. C'est le texte le plus visible de tout le parcours, celui qui s'écrit lettre par lettre dans le hero, et tu n'en as aucune trace.

*Effort :* deux instructions à intervertir. **C'est le meilleur rapport de toute la liste.**

### 2. Le plus grand format n'est recommandé à personne · **décision catalogue**
Gaston existe en 50, 70, 90 et 110 cm. Le site associe « grande pièce » au troisième, donc 90. Le 110 n'est proposé qu'aux escaliers ouverts, depuis hier. Idem sur Olivia et Suze.

*La cause :* le site suppose trois tailles par modèle ; ton catalogue en a deux à quatre.

*À trancher :* « grande pièce » doit-il désigner la troisième taille, ou la plus grande disponible ?

### 3. Deux réponses différentes, le même luminaire · **décision catalogue**
Claudine existe en 65 et 85 cm. « Standard » donne 85. « Grand » donne 85 aussi. Depuis que le récap affiche le projet, chaque visiteur lit sa propre réponse juste au-dessus d'une recommandation identique à celle du voisin.

---

## 🟠 Ensuite — le RGPD, ce qui n'a pas été traité

Le chantier d'aujourd'hui a traité la **conservation** et la **minimisation** : IP tronquées, purge à 14 mois, géolocalisation coupée. Il n'a traité ni le consentement, ni l'information, ni les tiers.

### 4. Rien n'attend le consentement · **code + décision**
Les écritures partent dès l'ouverture de la modale. Complianz gère bien le consentement pour GA4 et le marketing, mais **il n'est consulté nulle part** dans le Conseiller.

### 5. Ta politique de confidentialité ne mentionne rien de tout ça · **rédaction**
Ni la table de sessions, ni l'adresse IP, ni la géolocalisation, ni les 14 mois de conservation.

### 6. Le snippet Pinterest envoie l'IP complète · **code**
`snippet-pinterest-capi-enrichment.php`, actif via Code Snippets. Ce chantier n'y a pas touché — on a tronqué l'IP dans la table de sessions, pas dans ce snippet.

### 7. Le droit à l'effacement est intenable en pratique · **code**
Une demande de suppression oblige à retrouver les lignes une par une, 30 par page. Pas de suppression en masse, pas de recherche par email fiable.

### 8. La géolocalisation attend ta décision · **décision**
Coupée aujourd'hui pour trois raisons cumulées : l'appel partait en HTTP non chiffré, le HTTPS d'ip-api est payant, et **leurs conditions interdisent l'usage commercial en offre gratuite**. Trois issues : y renoncer, souscrire, ou changer de fournisseur.

---

## 🟡 Le tableau de bord — les chiffres restants

### 9. `home_picker` restera à zéro pour toujours · **code**
La branche existe, mais la modale n'est chargée que sur les pages boutique et produit. Sur l'accueil, elle n'existe pas. **C'est du code mort**, pas un compteur en panne.

### 10. « Catalogue présenté » ne s'affichera jamais · **code**
Le scan qui remplissait cette colonne a été retiré aujourd'hui — il cherchait un markup absent de ton thème, et même corrigé il aurait visé la mauvaise grille. Le serveur calcule déjà la bonne liste dans les deux points d'entrée IA ; il reste à la rattacher à la session.

### 11. La recherche propose encore la ville · **code**
`location` est gelée depuis la coupure de la géolocalisation. Les anciennes lignes gardent leur ville, les nouvelles seront vides — et le champ de recherche continue de la proposer. Tu croiras que la recherche est cassée.

### 12. Deux horloges cohabitent toujours · **code**
L'affichage est unifié depuis aujourd'hui, mais le **stockage** ne l'est pas : `created_at` vient de MySQL, `contact_submitted_at` de WordPress. Le filtre de période est donc calculé en heure serveur, pas en heure de Paris.

### 13. `ai_freetext_used` et `table_reponse` ne sont lues par personne · **code**
La première est écrite depuis aujourd'hui mais aucun filtre ne l'exploite. La seconde correspond à une question retirée du parcours.

---

## 🟢 Décisions produit en attente

### 14. Un projet mémorisé n'expire jamais · **décision**
`created_at` et `updated_at` sont écrits, jamais relus. Quelqu'un qui revient dix jours plus tard retrouve son projet comme s'il ne l'avait jamais quitté.

*À trancher :* au bout de combien de temps un projet cesse-t-il d'être « en cours » ? Et à l'expiration : on efface, ou on demande « c'est toujours pour ton salon ? »

Cette décision en débloque deux autres : la phrase de la pill qui parle « de ton projet », et le comportement quand un lien reçu contredit un projet en cours.

### 15. « Couloir » s'affiche « entrée » · **décision**
L'extraction rapproche couloir de la pièce la plus proche des sept. Le conseil de Robin reprend le mot du visiteur, le titre affiche le mot du référentiel. Ce n'est pas faux, mais les deux ne se répondent pas.

*Deux issues :* ajouter des pièces, ou faire reprendre le mot du visiteur dans les libellés.

### 16. Y a-t-il d'autres pièces manquantes ? · **décision catalogue**
Le référentiel en compte sept. Salle de bain, garage et terrasse sont désormais renvoyés vers toi. Mais **tes recherches internes et tes messages de contact** diraient si d'autres pièces reviennent : salle à manger, atelier, véranda.

### 17. Où afficher les compromis · **décision**
Le moteur sait quand il a relâché une contrainte, et l'IA le dit maintenant dans son conseil. Mais la donnée brute (`fallback_notes`) remonte jusqu'au hero sans y être affichée — la mise en page est calibrée au pixel et je n'y ajoute rien sans ton accord.

### 18. On ne peut pas revenir du chat au questionnaire · **décision**
Une fois dans la conversation, il n'existe aucun chemin de retour. Seulement la croix.

---

## 🔵 UX et accessibilité

### 19. Le haut du récap produit peut disparaître sans recours · **code**
La modale centre son contenu verticalement. Tout débordement rogne le **haut** autant que le bas — et le haut n'est pas rattrapable en faisant défiler. Sur ton iPhone SE, il reste environ 9 px de marge après la compaction d'aujourd'hui. Le correctif est une ligne.

### 20. Le mode paysage sur petits téléphones · **code**
Un iPhone SE tenu à l'horizontale laisse une trentaine de pixels aux cartes, qui en demandent 170. Rien ne se chevauche, mais la carte est réduite à un liseré. Ta décision d'alors — écran A texte seul, écran B carrousel seul — n'a jamais été codée.

### 21. Le focus ne revient jamais après fermeture de la modale · **code**
Le code qui devait mémoriser l'élément déclencheur cherche une méthode qui n'existe pas sur l'événement reçu : il vaut donc toujours vide. Une personne qui navigue au clavier est renvoyée en haut de page à chaque fermeture. L'attribut `data-action="open-modal"` a été conservé exprès pour permettre cette réparation.

### 22. Le retrait d'un filtre est invisible · **code**
L'encart « Filtres appliqués » du chat ne liste que les ajouts. Si Robin retire une contrainte en cours de conversation, rien ne l'indique.

---

## ⚪ Dette technique

Rien de tout ça ne casse quoi que ce soit. Mais chaque vestige est un piège pour la prochaine lecture, et **deux d'entre eux ont déjà produit des bugs réels** dans ce chantier.

### 23. L'invariant d'ordre des scripts est faux en production · **code**
`readyState === 'loading'` subsiste dans six fichiers. En production, Autoptimize met `defer` partout, et ce test ne se déclenche jamais — l'ordre d'initialisation s'inverse. Aucune conséquence démontrée aujourd'hui, **mais le prochain lot qui lira le projet au démarrage se cassera en prod uniquement**, et la recette sur test ne pourra pas le montrer.

### 24. La table « taille → intention » vit encore en trois exemplaires · **code**
Le JS a été unifié aujourd'hui, le PHP non. Deux recalculs subsistent côté serveur, et ils divergent sur l'escalier : le serveur choisit comme pour une petite pièce là où le JS choisit la moyenne. **C'est le défaut qu'on a corrigé, à un autre étage.**

### 25. L'écran contact affiche « Grand » tout seul · **code**
`buildContactRecap` construit ses pastilles à la main, sans le mot-clé — et ces mêmes valeurs partent dans le mail que tu reçois.

### 26. L'ordre canonique des clés est recopié en dur · **code**
Une fois dans `sapi-modal-conseiller.js`, alors que la source unique est disponible dans le même fichier. Exact aujourd'hui, divergent au premier ajout de question.

### 27. Environ 700 lignes de « Robin V2 » sans aucun appelant · **code**
Un bloc entier de fonctions mortes, qui contient au passage une copie périmée de la conversion escalier → taille.

### 28. Code mort divers · **code**
`sapi_guide_build_filter_context()` (~50 lignes, jamais appelée) · `shop.js` lit encore un objet supprimé, à deux endroits · `.why-sapi-recap`, ~30 lignes de markup qui ne peuvent plus s'afficher · vestiges CSS : `.conseiller-card--mon-projet` (~280 lignes), `.conseiller-cards-zone`, `.mes-creations-section-divider`, et le sélecteur WooCommerce dont on a supprimé le scan aujourd'hui.

### 29. Si le plugin de pastilles est désactivé · **code**
La présélection poserait bien l'essence, mais la pastille ne s'allumerait pas : `cinetique.js` synchronise le menu vers la pastille une seule fois à l'init, sans écouteur. Bonne variation, bon prix, pastille éteinte.

### 30. Le formulaire Inspiration n'a qu'un nonce · **code**
Ni honeypot, ni limite de fréquence, contrairement aux quatre autres formulaires du site.

---

## 📊 SEO — en attente depuis mai

### 31. Le re-check Search Console · **à faire**
Prévu le 26/08, non fait. La baseline de mai comptait 576 clics et cinq quick wins identifiés.

### 32. La cannibalisation · **à faire**
25 produits en doublon relevés dans le dossier M35.

---

## Si je devais n'en faire qu'une

**Le n° 1 — `advice_text`.**

C'est deux instructions à intervertir, et c'est la seule donnée qui te dirait ce que Robin raconte vraiment à tes visiteurs. Tout le reste de cette liste est du poids, des décisions, ou de la conformité. Celle-là est une information que tu n'as jamais eue et qui existe déjà, à deux lignes près.
