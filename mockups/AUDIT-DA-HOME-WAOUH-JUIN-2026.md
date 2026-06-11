# Audit esthétique — Home desktop Atelier Sâpi (test.atelier-sapi.fr)
*Direction artistique · juin 2026 · desktop ~1440-1900px*
*Cadrage Robin : « WAOUH, mais surtout sincère, artisan, artisanal, propre et sérieux ». Le wow naît de la matière et du soin, jamais du gadget.*

## 1. Note d'ambiance — Score « wow sincère » : 6,5 / 10

La home est **sincère et propre, mais le wow reste contenu**. Le matériau brut est là (vraies photos de luminaires allumés, macro des cerclages bois d'Olivia, citation « sauce pour les pâtes », ruban de process, Robin sur fond de soleil « Sâpi ») et quand ces images sont grandes, ça touche juste. Le problème : **la page ne laisse presque jamais la matière respirer en grand**. Tout est rangé en sections numérotées 01→06, en cartes propres et égales, sur des bandes crème/blanc qui alternent sagement. C'est rassurant, ordonné, « sérieux », mais c'est l'ordre d'un bon template, pas le geste d'un artisan qui ose poser une seule photo magnifique plein cadre et se taire. **L'artisanal ne mange pas le wow ; c'est le format qui plafonne les deux.** On sent le soin partout, on prend rarement une claque.

**Ce que le benchmark artisanal réussit que Sâpi rate :** Allied Maker, Graypants et Frama partagent un réflexe : donner à **une seule image le droit d'occuper tout l'écran** (hero plein cadre sans habillage, diptyques d'atelier en pleine largeur sur fond sombre, lifestyle warm bord à bord avec un mot en typo géante). Ils ont confiance dans la matière au point de retirer le décor (pas de cartes, pas de bordures, pas de numéros, peu de texte). La lumière chaude des ampoules EST le wow. Sâpi, lui, **emballe** sa matière dans des composants.

**Ce que Sâpi réussit que le benchmark n'a pas :** une **voix incarnée**. Allied Maker et Frama sont superbes mais froids et anonymes. Sâpi a un prénom, un visage, un tutoiement, une vanne sur les pâtes, une adresse à 15 min de Lyon, des prénoms de lampes. **Aucune marque premium ne peut acheter ça.** L'enjeu n'est pas d'ajouter de l'âme, c'est de lui donner de l'échelle.

---

## 2. Section par section

### Hero — carrousel plein écran
**Constat :** structure juste mais qualité perçue la plus faible à l'arrivée : H1 posé très bas (centré-bas), naming card flottante au milieu qui fait gadget UI plus qu'objet, H1 Square Peg blanc élégant mais timide. Le fond gris froid (404 connu) n'aide pas.
**Reco :** hero = **une seule photo lifestyle d'un luminaire allumé, plein cadre, chaude, vrai intérieur**. Texte minimal ancré dans un coin (bas-gauche), pas centré-bas. Réduire/transformer la naming card en simple crédit discret. Overlay bois sombre plutôt que dégradé gris : la lumière du luminaire doit être le point le plus clair de l'écran. **M**
>> Avis Robin : Le naming en bas, c'est pour ne pas cacher la photo. Je veux garder un carrousel, mais ok pour modifier l'overlay et la card naming. Il faut faire un mockup de la nouvelle proposition. Je veux qu'on voit le nom du modèle + cliquable (photo et naming).

### Bandeau réassurance
**Constat :** propre, lisible, cohérent. Fonctionnel mais sans émotion.
**Reco :** garder. Éventuellement un wording plus artisan (« Façonné main en <5 jours »). **S**
>> Avis Robin : Ok pour revoir le wording, propose moi !

### « Pour quelle pièce ? »
**Constat :** le bloc le **plus générique** : 7 chips blanches identiques à icônes beige, titre noir Montserrat, champ libre. Ressemble à un configurateur SaaS, pas à un atelier.
**Reco :** H2 en bois sombre (pas noir pur), fond de bande légèrement texturé (grain bois très subtil) plutôt que crème plat, chips à fond crème plus chaud au hover, icônes en trait plus organique. **M**
>> Avis Robin : ok pour un mockup. Mais il attention : si on modifie le design du site, il faudra appliquer sur tout le site. Aussi, j'aimerai que ce configurateur est un design propre pour que l'utilisateur comprenne que c'est la partie "Conseil de robin".

### Collections
**Constat :** **le plus réussi visuellement.** Grandes cartes portrait, photos chaudes et vraies, débord volontaire de la carte suivante = générosité et mouvement. Bémols : scrim sombre en pied de carte un peu lourd/uniforme (effet « stock card »), titres Montserrat blanc gras sans signature.
**Reco :** alléger le scrim ; envisager le nom de catégorie **hors image**, sous la carte, en petit (façon Graypants/Frama). Garder le débord. **S/M**
>> Avis Robin : Propose moi un mockup (mais pas sûr car c'est un gros changement design de tout le site après. Je trouve aussi qu'on pourrait mettre les cards plus grandes, là elles sont déjà toutes affichées à l'écran.

### Les créations du moment
**Constat :** belle idée éditoriale, STAR card (Olivia) superbe et grande. Deux soucis propreté : (1) « OLIVIA La Gardiena » en blanc sur bois clair très lumineux → contraste limite, manque un voile local ; (2) **incohérence de naming** : « LÉON L'accordéon » a prénom Montserrat + surnom Square Peg, « La Merveilleuse » n'a que le Square Peg → pas la même famille.
**Reco :** homogénéiser le formatage des noms (sélecteur manquant dans `product-name-formatter.js`) ; voile dégradé local sous le texte de la STAR card. **S**
>> Avis Robin : Ok pour le voile local sur Olivia. PAS OK pour l'incohérence de naming, au contraire c'est cohérent, on change rien.

### L'atelier
**Constat :** **le cœur sincère de la page, à moitié manqué.** La photo immersive (mains de Robin tenant un abat-jour) est **noyée sous un voile crème trop opaque (~90 %)** : on ne SENT plus la matière. Texte collé à gauche, moitié droite vide crème. Le ruban process (01→05) est **le meilleur contenu du site** (laser qui rougeoie, mains qui assemblent) mais réduit à 5 petites vignettes étroites — l'inverse d'Allied Maker qui met les macros d'atelier en pleine largeur.
**Reco :** baisser le voile à ~35-45 % OU passer la bande sur **fond bois sombre** ; agrandir le process (vignettes plus grandes ou 2-3 grandes images plein cadre). **M** (voir Geste 2)
>> Avis Robin : OK que cette section ne va pas. La photo n'est pas définitive, mais propose moi un mockup avec tes propositions.

### Ils en parlent (avis + presse)
**Constat :** propre et crédible (5/5 · 27 avis, badge Google). Mais **fond blanc + cartes blanches bordées = le passage le plus corporate**, rupture de chaleur après la bande atelier.
**Reco :** fond crème au lieu de blanc pur ; bordures de cartes remplacées par les ombres douces de la charte (`--shadow-card`). **S**
>> Avis Robin : on peut pas avoir que des fonds crèmes qui se suivent ...

### Bande citation Robin + card atelier
**Constat :** **le pic émotionnel, très réussi** (Robin plein cadre, applique « Sâpi » qui rayonne, citation Square Peg, signature). Deux détails : (1) le bloc citation **recouvre le visage de Robin, ses yeux sont coupés par le texte** → on perd le regard, tout l'intérêt d'un portrait d'artisan ; (2) la card « Venir me voir à l'atelier » affiche un **faux plan schématique** (grille de rues générique) → un peu cheap dans une section haut de gamme.
**Reco :** recomposer pour **dégager le regard de Robin** (texte plus bas ou photo recadrée) ; remplacer le faux plan par une vraie mini-carte stylée (tuile monochrome bois) ou une photo de la devanture/atelier. **S/M**
>> Avis Robin : photo pas définitive, je reverrais avec claude code plus tard la disposition pour que le texte ne couvre pas Robin. Ok pour remplacer la carte : mockup s'il te plait.

### Bento (Carte cadeau + Flash actu)
**Constat :** correct et cohérent. Card Flash actu belle ; image Carte cadeau festive mais peut dater hors saison.
**Reco :** garder, surveiller la saisonnalité du visuel cadeau. **S**
>> Avis Robin : Pas d'accord avec toi, cette section est moche, on a l'impression que ça a été posé là. Je propose une card Carte cadeau plus propre et une section "Actus" avec le dernier article et un CTA voir toutes les actus. Mocktup à faire.

### Newsletter
**Constat :** **le maillon le plus template.** Titre + champ + bouton orange sur fond blanc nu, zéro image/texture/chaleur. Fin tiède après tout ce qui précède.
**Reco :** donner un fond (bois sombre ou crème chaud + photo d'atelier en filigrane), ou intégrer la newsletter DANS la bande atelier pour ne pas finir sur un bloc blanc anonyme. **S/M**
>> Avis Robin : ok pour photo en arrière plan, sur un fond bois sombre.

### Footer
Propre, minimal, Square Peg. Rien à signaler.

---

## 3. Trois gestes forts (sincères, pas gadget)

### Geste 1 — « Une seule image qui respire » : du catalogue à la galerie
**Intention :** retirer le chrome (cartes, bordures, scrims lourds, numéros) sur 2-3 moments clés et laisser **une grande photo de luminaire allumé occuper tout l'écran** (réflexe Allied Maker / Frama). Hero = photo lifestyle plein cadre ; + au moins une **bande respiration pleine largeur** entre deux sections (un luminaire dans un vrai intérieur, lumière chaude, zéro UI, juste un nom discret en bas).
**Sincère parce que :** ça n'ajoute aucun effet, ça en **enlève**. La vraie matière devient le sujet, pas le décor d'un composant.
**Risque :** exige 1-2 photos lifestyle irréprochables HD (justement en 404 sur le test). Prérequis : bons hero shots.

### Geste 2 — « L'atelier sur fond sombre » : le process en clou, pas en frise
**Intention :** transformer la bande L'atelier. Cible : **fond bois sombre (#4A3F35) plein cadre**, 2-3 images de process EN GRAND (laser qui rougeoie, mains qui assemblent, copeaux), lumineuses, comme le diptyque d'atelier d'Allied Maker. Texte de Robin + CTA en blanc par-dessus.
**Sincère parce que :** ces images sont **déjà les plus vraies du site**. On ne les invente pas, on leur donne enfin l'échelle. Le contraste sombre crée le pic « waouh » au bon endroit (la fabrication).
**Risque :** rupture de palette sombre dans un site clair, à doser pour éviter le « luxe froid ». Antidote : garder la lumière chaude des photos + la voix de Robin.

### Geste 3 — « La lumière qui s'allume » : un seul mouvement, vrai et sobre
**Intention :** une seule micro-animation signature, toujours la même grammaire : au scroll, quand une grande photo de luminaire entre dans le viewport, son halo **se révèle en fondu** (éteint/sombre → allumé/chaud, ~0,8 s, ease doux). Possible sur hero + STAR card.
**Sincère parce que :** le geste raconte littéralement le métier (faire de la lumière). Ni parallax décoratif ni effet 3D : la métaphore exacte du produit.
**Risque :** un tic si appliqué partout → réserver à 2-3 images. Exige les deux états de photo (éteint + allumé) = colle au workflow Vizcom de Robin.

---

## Top 3 priorisé (effet / effort)

1. **Geste 2 — L'atelier sur fond sombre + process en grand.** ★★★ / M. Meilleur ratio : transforme la section la plus sincère en pic émotionnel, avec des images qui existent déjà.
2. **Geste 1 — Hero + une respiration plein cadre.** ★★★ / M (conditionné à de bonnes photos). Le « moment d'arrivée », là où se joue le wow de la 1re seconde.
3. **Corrections propreté/sérieux groupées** (naming homogène, voile local STAR card, regard de Robin dégagé, faux plan remplacé, newsletter réchauffée, avis sur fond crème). ★★ / S chacune. Peu coûteux, fait passer de « propre » à « irréprochable ».

Le Geste 3 (lumière qui s'allume) vient en bonus une fois 1 et 2 posés.

---

**Benchmarks visités :** Allied Maker (hero plein cadre + diptyque atelier sur fond noir), Graypants (hero produit dramatique chaud + marquee typo des collections), Frama (diptyques lifestyle warm pleine largeur, typo géante sur l'image, produits sans chrome de carte). Fil rouge : image-first, échelle généreuse, zéro habillage superflu, lumière chaude comme effet. À s'approprier sans rien perdre de la voix d'artisan.

---

## 4. Réponse DA aux retours de Robin (échange du 6/06, avant mockups)

**1. Hero** — carrousel conservé (photo unique abandonnée). Direction proposée : transformer la naming card flottante en **bandeau-crédit ancré en bas** qui semble appartenir à l'image (voile bois qui monte du bas, nom Square Peg, mention « Découvrir [modèle] → »), toute la zone basse cliquable vers la fiche. Lit comme une légende de photo, plus comme un composant UI. ❓ À décider : nom en Square Peg seul, ou prénom Montserrat + surnom Square Peg ?
>> Avis Robin : On utilise TOUJOURS le formater, donc prénom Montserrat + surnom Square Peg.

**2. Bandeau réassurance** — 4 wordings proposés (accroche artisan + sous-ligne) : « Façonné main à Lyon / en moins de 5 jours » · « Chez toi en 48-72h / expédié avec soin » · « Tu changes d'avis ? / retours sous 30 jours » · « Paiement tranquille / transaction sécurisée ». Tutoiement = relie à la voix Conseiller. Option : 4 libellés courts sans sous-ligne si plus simple à intégrer.
>> Avis Robin : OK, mais sur mobile ça va être trop long. On verra plus tard

**3. « Pour quelle pièce ? » / Conseiller** — réchauffe SYSTÉMIQUE (tokens déjà globaux : titre bois sombre, hover crème chaud, ombre charte ; pas de classe one-shot). Pour signaler « ici c'est Robin » : créer une **signature de bloc Conseiller réutilisable** (vignette ronde de Robin + accroche tutoyée Square Peg « Dis-moi pour quelle pièce, je te conseille »), motif transposable partout (modale, fiche produit). ❓ À décider : photo/illustration de Robin exploitable en pastille ronde, ou variante typo ?
>> Avis Robin : ON conserve ce qui est fait, mais propose un mockup ajusté avec ta recommandation. Si c'est bien, on adaptera tout le site en fonction.

**4. Collections** — « nom hors image » + « cards plus grandes » sont compatibles et complémentaires (sortir le nom libère la photo du scrim ; cards plus grandes = carrousel qui dépasse, 2,5-3 visibles). ⚠️ « nom hors image » = vrai changement SITE-WIDE (card partagée catégories + produits) ; agrandir les cards est local/réversible. Mockup montrera les deux pour juger bénéfice/coût.
>> Avis Robin : Ok pour mockup

**5. Créations du moment** — voile local sur Olivia : OK. Naming Léon/La Merveilleuse : **point retiré définitivement** (cohérent, voulu).

**6. L'atelier** — mockup à venir : fond bois sombre #4A3F35, voile baissé à ~35-45 %, ruban process agrandi (2-3 grandes images de fabrication au lieu de 5 vignettes étroites). Travail sur la STRUCTURE (photo non définitive).

**7. Ils en parlent** — pas de fond crème (refus Robin : trop de crème qui se suit). Alternative : garder fond clair MAIS retirer les bordures (le côté corporate) → ombres douces de charte (avis = petits papiers posés) + grain/liseré bois en filigrane. Chaleur par la matière, pas par un aplat.
>> Avis Robin : Mockup

**8. Bande citation Robin** — disposition texte/visage gérée plus tard par Robin via Claude Code. Mockup = remplacer le faux plan, 3 variantes : (a) photo devanture/atelier, (b) vraie mini-carte stylée monochrome bois, (c) carte mixte (tuile + ligne adresse/invitation « à 15 min de Lyon »).
>> Avis Robin : ok

**9. Bento → Cadeau + Actus** — erreur reconnue (section « posée là »). Recompo : card **Carte cadeau intemporelle** (luminaire qui s'allume comme métaphore, ton chaud non saisonnier) + vraie **section Actus** (dernier article : image + titre + chapô + CTA « Voir toutes les actus »). ❓ À décider : 1 seul dernier article, ou 2 côte à côte ?
>> Avis Robin : un seul dernier article

**10. Newsletter** — photo atelier en filigrane sur fond bois sombre : OK. ⚠️ Risque de répétition avec l'atelier (geste 2 aussi sombre) → différencier par la DENSITÉ : atelier = riche en images process ; newsletter = aérée, photo floutée en retrait. Intercaler une section claire entre les deux. Option : newsletter en bois plus chaud/clair que l'atelier.
>> Avis Robin : Mockup

**Mockups à produire (1 par sujet validé) :** 1 Hero · 2 Réassurance · 3 Conseiller · 4 Collections · 5 Atelier · 6 Avis · 7 Carte citation · 8 Bento→Cadeau+Actus · 9 Newsletter. *(Voile Olivia = correction simple, en note, pas de mockup dédié.)*

**4 décisions attendues de Robin avant lancement :** (1) format du nom hero ; (2) photo de Robin pour la pastille Conseiller (sinon variante typo) ; (3) Actus = 1 ou 2 articles ; (4) ordre de production (instinct DA = Atelier → Hero → lot propreté).
