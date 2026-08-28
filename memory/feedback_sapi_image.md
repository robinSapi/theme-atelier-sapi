---
name: Toujours utiliser sapi_image() pour les images statiques
description: Les images hardcodées dans le code doivent passer par sapi_image() pour le srcset automatique
type: feedback
---

Toujours utiliser `sapi_image()` pour ajouter une image statique dans un template PHP.

**Why:** Les images écrites en dur (`<img src="...uploads/photo.jpg">`) ne bénéficient pas du srcset WordPress. Le navigateur charge l'image en taille originale même sur mobile, ce qui plombe les performances (PageSpeed signalait 2 004 Kio d'économies possibles).

**How to apply:** À chaque fois qu'on ajoute une image "statique" (pas un produit, pas un champ ACF) dans un template :
```php
<?php echo sapi_image('2025/05/photo.jpg', 'large', ['alt' => 'Description', 'class' => 'ma-classe', 'loading' => 'lazy']); ?>
```
- Le helper retrouve l'attachment par filename, génère le srcset automatique
- Utiliser `'large'` pour les images standard, `'full'` pour les hero plein écran
- Les images ACF/WooCommerce ont déjà leur srcset, pas besoin du helper
- Si on remplace une photo, utiliser le plugin "Enable Media Replace" pour garder le même nom de fichier
