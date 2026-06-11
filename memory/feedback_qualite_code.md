---
name: Qualité du code — prendre le temps de bien faire
description: Toujours privilégier la meilleure solution même si elle est plus longue, vérifier avant d'affirmer, mesurer avant/après
type: feedback
date_updated: 2026-03-31
---

Toujours implémenter ce qui se fait de mieux aujourd'hui — les standards actuels du secteur, pas juste "ça marche".

**Why:** Robin ne veut pas de solutions rapides ou de contournements. Il accepte que ça prenne plus de temps, et il accepte aussi les changements côté interface WordPress si c'est ce que la bonne approche demande. Robin a corrigé Claude qui allait trop vite en supposant que le serveur était Apache sans vérifier, et qui changeait de plan sans comprendre pourquoi le site était tombé.

**How to apply:**
- Avant de coder, prendre le temps d'analyser le vrai problème — pas juste le symptôme
- **Vérifier ses hypothèses** avant d'affirmer (ex: ne pas dire "le serveur est Apache" sans le vérifier)
- **Un changement à la fois**, mesurer l'impact, puis passer au suivant
- **Vérifier les prix/infos** avant de les annoncer (ex: criticalcss.com = 88€/an, pas 8€)
- Si plusieurs approches existent, choisir celle qui correspond aux standards actuels du secteur
- Ne pas hésiter à proposer un refactoring si le code existant ne suit pas les bonnes pratiques
- Si la bonne approche implique un changement côté Robin (ACF, réglage WordPress, upload de fichier), le dire clairement plutôt que de contourner
- Pas de hack, pas de `setTimeout`, pas de `!important` de facilité — trouver la vraie cause
- Quand une action casse le site, **diagnostiquer la cause** avant de changer de plan
- Si une tâche est complexe, la découper et mettre à jour la mémoire entre chaque étape
- Quand Robin dit "je suis perdu", prendre du recul et **expliquer simplement** avant de continuer
