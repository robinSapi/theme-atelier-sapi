# Audit esthétique (Direction Artistique) — Home test.atelier-sapi.fr
*5 juin 2026 — audité en rendu réel : desktop 1440px + mobile 390px, hovers testés, comparé au mockup-15 et aux benchmarks Gantri (gantri.com) et Tala (tala.co.uk), visités en réel.*
*Cadrage Robin : esthétique, cohérence, fluidité (transitions/mouvement + parcours logique). Deux volets : polish actionnable / « si on osait ».*

---

## 1. Note d'ensemble

La home est **à 80 % à la hauteur de la marque** : la structure du mockup-15 est fidèlement transposée, le parcours est limpide (hero → projet → collections → créations → atelier → preuve sociale → news), et les photos produit des Collections sont franchement belles — la suspension sur fond orange est un visuel de niveau éditorial. Ce qui manque, c'est le **dernier centimètre de finition** : un hero qui passe la moitié de son temps sur un dégradé gris vide (bug d'opacité), une page totalement statique au scroll, et trois corps étrangers (avatars Google bruts, photo selfie de Robin, bandeau cadeau aux motifs de Noël en juin) qui cassent la magie au moment précis où elle devait se conclure.

**Ce que le benchmark fait de mieux** : la photographie comme langage unique (Tala : tout est film, chaud, sans UI parasite ; Gantri : direction artistique humaine, gens qui vivent avec les lampes), et une économie de moyens — moins de cards, moins de bordures, plus d'image.

**Ce que Sâpi fait de mieux qu'eux** : la chaleur de la voix (tutoiement, Square Peg, prénoms de produits — Gantri et Tala sont impersonnels), et l'entrée par le projet (« Pour quelle pièce ? ») — Tala enterre son "Shop by room" en bas de page, Sâpi l'ose en section 1. C'est un vrai avantage différenciant, il mérite juste un meilleur habillage.

---

## 2. Volet 1 — POLISH (dans le langage actuel)

### Hero (section 01)
- **P1 — Bug d'opacité du carrousel : slides à vide ~50 % du temps.** (vérifié dans le DOM) : la slide `.carousel-slide.active` est à `opacity: 0` pendant qu'une slide *non active* est à `opacity: 1` — états désynchronisés. Entre deux slides, le hero affiche plusieurs secondes de dégradé gris/blanc vide, texte blanc illisible. LE problème esthétique n°1. → Corriger la synchro classe/opacity dans le JS du carrousel (crossfade par superposition, jamais par trou). **Effort S, à traiter avant tout.** >> NON : je ne vois pas ce bogue moi, tout va bien.
- **P2 — Photo "Irène La Reine" hors gamme.** Froide, grise, encombrée vs Olivia/La Merveilleuse (chaudes). → Critère de sélection slides hero : « lumière chaude dominante + lampe allumée ». Remplacer ou re-grader. **S** >> NON
- **P3 — Pill de naming : deux formats coexistent.** « Suspension Olivia → » vs « OLIVIA La Gardiena ». La flèche texte fait doublon avec les chevrons. → Unifier au format prénom/surnom officiel, sans flèche. **S** >> OUI

### Bandeau réassurance
- **P4 — Bien dosé, rien à dire** (desktop).

### Ton projet (room picker)
- **P5 — Les cards-pièces sont des boîtes blanches muettes**, génériques (un SaaS pourrait avoir les mêmes). Sâpi a 274 photos taguées par pièce (S28) ! → Au hover de chaque card, photo d'ambiance de la pièce en fond (fondue 20-30 % ou remplacement complet avec label blanc). **M** >> NON
- **P6 — Hover des cards timide.** → Remplissage du carré d'icône crème → orange pâle au hover. **S** >> OUI

### Collections (02)
- **P7 — La plus belle section de la page.** Seule retouche : `scale(1.04)` sur 0,6 s au swap hover pour le « souffle ». **S** >> OUI

### Les créations du moment (03)
- **P8 — Déséquilibre droite** : vide sous le prix des 2 petites cards, pieds non alignés avec la star. → Aligner le bas des 3 cards, CTA à padding constant du bas. **S/M** >> OUI
- **P9 — « LA Merveilleuse » : formatter appliqué à un article.** « LA » criard en caps. → Règle dans `product-name-formatter.js` : si le premier mot est un article (La, Le, L'), tout le nom passe en Square Peg. **S** >> OUI mais nuance : il faudrait aussi dissocier les catégories suspensions/appliques/àposer/lampadaires des autres. Pour Ampoule Poire, c'est incohérent aussi d'avoir la distinction.

### L'atelier (04)
- **P10 — La photo de Robin est le maillon faible de toute la page.** Selfie webcam, bonnet, t-shirt orange fluo. La section qui doit prouver le geste artisanal est la moins artisanale visuellement. → Court terme : recadrage + re-grade. Vraie reco : **shooting atelier dédié** (trois-quarts, mains sur la machine, lumière chaude latérale). Meilleur ratio impact/effort de la page avec P1. **M** >> Je m'occuperai de proposer une autre photo !
- **P11 — Cards process à moitié vides au repos + zéro affordance de flip.** → Centrer numéro+label ; micro-affordance (coin replié / ↻ / coin de photo qui dépasse) ; mobile : photo en fond + numéro (pas de flip). **M** >> Desktop : NON. Mobile : Oui. Mais pour l'atelier j'aimerai qu'on mette une grande image en fonction derrière un overlay, avec le même design que la section Storytelling des pages catégories. Une image de fond ça fait immersion.
- **P12 — Lien « Voir l'atelier sur Google Maps » casse l'élan.** → Le déplacer dans la pill « L'atelier · Lyon » (cliquable), supprimer la ligne. **S** >> Là faut trouver une autre solution. La photo de moi ne peux pas renvoyer vers google maps, elle doit envoyer vers la page Artisan. Par contre les visiteurs doivent aussi voir quelque chose qui donne une existence réelle au lieu. Propose moi quelque chose. 

### Ils en parlent (05)
- **P13 — Avatars Google bruts** (disque bleu vif, photos aléatoires) polluent la palette crème. → Initiales sur disque bois/crème, garder nom + date + badge G global. **S/M** >> OUI ! 
- **P14 — Avis en anglais en première position.** → Avis FR d'abord, l'anglais en 3e (preuve d'export). **S** >> OUI
>> Il faut aussi ajouter comme dans le mockup des liens discrets de reassurance vers des références externes que je vais fournir : le progrès, maison actuelle, L'univers de la maison, la région ...

### Bento Idée cadeau / Conseil / Flash actu
- **P15 — Papier cadeau de Noël en juin** (Père Noël visible). Signal « site pas entretenu ». → Photo toutes-saisons : paquet kraft + ruban, étiquette Sâpi gravée laser. Re-shoot 10 min. **S** >> Il faut que je fasse un autre visuel pour cette card, c'est clair ! Des idées à me proposer ?
- **P16 — La citation « sauce pour les pâtes » est le meilleur moment de voix de la page.** Garder absolument. Réduire les guillemets décoratifs géants et les ancrer à la première ligne. **S** >> OUI

### Transversal
- **P17 — La page est 100 % statique au scroll** (vérifié : zéro reveal). Plus gros écart vs benchmark. → Un seul pattern sobre partout : `opacity 0→1 + translateY(16px)` 0,5 s, IntersectionObserver, stagger 60 ms sur les cards. Pas de parallax. **M** *(peut être remplacé par l'idée A du volet 2)* >> On change rien pour le moment
- **P18 — Curseur custom à moitié cassé** (outline orphelin qui traîne + curseur natif visible par-dessus = deux curseurs). → Le finir proprement OU le supprimer. Vote de l'audit : **supprimer** (gimmick d'agence, n'apporte rien à une marque artisanale). **S** >> On supprime.
- **P19 — Hiérarchie CTA bas de page** : « Découvrir l'artisan » orange vs « Toutes les créations » bois = inversé commercialement. → Créations en orange, artisan en bois, « S'inscrire » newsletter en orange comme au mockup (insight newsletter avril : la couleur du CTA compte). **S** >> OUI
- **P20 — Transitions de fonds blanc/crème sèches.** → 1px de bordure `#efe6da` en haut de chaque bande crème. **S** >> OUI

---

## 3. Volet 2 — « SI ON OSAIT »

### A. « La lumière s'allume en scrollant » — mouvement signature
**Intention :** le produit de Sâpi n'est pas le bois, c'est l'ombre projetée. Personne d'autre ne le met en scène.
**Forme :** chaque photo-clé (hero, collections, star) existe en deux états éteint/allumé (le workflow Vizcom produit déjà ces paires). Au scroll, crossfade éteint → allumé sur ~0,8 s quand la section entre dans le viewport. On « allume les lampes » en descendant la page. Hover possible sur la star.
**On jette :** rien — couche sur l'existant, remplace le reveal générique P17 sur les sections à photos.
**Risque :** poids (2 images/slot) → réserver aux 4-6 photos majeures, lazy + preload. Faible, effet mémorable.

>> J'aime beaucoup ! Mais comment on fait pour avoir ces deux version des photos ? Je dois te donner deux url ?

### B. Room picker photographique plein écran
**Intention :** l'entrée projet est l'avantage compétitif n°1 ; aujourd'hui elle ressemble à un formulaire.
**Forme :** bande pleine largeur sur fond photo d'ambiance floutée. Les 6 pièces = chips posées sur l'image ; au survol d'une chip, le fond crossfade vers une ambiance de cette pièce (taxonomie `media_room` déjà en prod). Champ libre inchangé. Wording identique.
**On jette :** les 6 boîtes blanches à icônes filaires.
**Risque :** lisibilité chips sur photo (scrim léger) ; mobile sans hover = fond fixe, dégradation propre. Moyen ; gain de personnalité majeur.

>> Mockup à faire de cette idée

### C. La bande atelier « format cinéma »
**Intention :** la section atelier est un layout brochure ; c'est le moment de vérité artisanal, il mérite le traitement le plus immersif.
**Forme :** bande pleine largeur 60vh, photo cinéma de Robin de profil aux machines (copeaux, lumière latérale). Par-dessus : « Des sculptures lumineuses » en Square Peg blanc + 2 lignes + CTA. Les 5 étapes du process passent DANS la bande en ruban horizontal bas (5 vignettes photo numérotées). Optionnel : première frame d'une vidéo muette 6 s en boucle (la découpe laser qui trace).
**On jette :** le layout 2 colonnes + les 5 cards flip (le contenu migre dans les vignettes).
**Risque :** dépend à 100 % d'un shooting réussi (cf. P10). Sans bonne photo, ne pas lancer.

>> Mockup de cette idée

### D. Le fil de gravure laser — motif identitaire
**Intention :** un élément graphique propriétaire : le trait de découpe.
**Forme :** trait fin continu (1,5px, wood à 30 %) qui serpente verticalement le long de la page comme un chemin de découpe, petits événements aux transitions (silhouette de suspension entre Collections et Créations, ampoule avant l'atelier). Tracé en SVG `stroke-dashoffset` lié au scroll.
**On jette :** rien ; remplace les bordures de transition (P20).
**Risque :** kitsch si trop présent — extrême discrétion requise. À mocker d'abord ; si ça fait « site d'agence 2019 », abandonner sans regret.

>> J'aime pas trop, mais je veux bien un mockup pour voir

### E. Hero « une création, une semaine »
**Intention :** transformer le diaporama subi en rituel : la home met en scène UNE création vedette, changée chaque semaine, comme une vitrine d'atelier.
**Forme :** une seule photo plein écran (éteint → allumé à l'arrivée, cf. A), pill de naming = carte d'identité : « Cette semaine dans l'atelier — OLIVIA La Gardiena — suspension · peuplier · 6 h de fabrication ». Autres créations par chevrons, sans autoplay.
**On jette :** l'autoplay et la pression de 5-8 photos hero parfaites (il n'en faut plus qu'une excellente — règle P2/Irène au passage).
**Risque :** moins de produits au-dessus de la ligne de flottaison (compensé par Collections en section 2). Mini-discipline hebdo de Robin (1 clic ACF).

---

## Top 3 de l'audit (toutes catégories)

1. **P1 — Fix du bug d'opacité du carrousel hero.** Tant que la première impression est un dégradé gris vide une fois sur deux, le reste est secondaire. Effort S, impact maximal.
2. **P10 + idée C — La photo/bande atelier.** Cœur émotionnel de la marque, aujourd'hui son point le plus faible. Un shooting d'une demi-journée + la bande cinéma transforment la crédibilité artisanale de toute la page.
3. **Idée A — « La lumière s'allume en scrollant ».** Mouvement signature : règle la page statique (P17), propriétaire (personne d'autre ne vend des ombres), et les paires éteint/allumé existent déjà dans le workflow Vizcom.
