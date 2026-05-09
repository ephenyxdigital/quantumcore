# Migration des références quantumcore — Rapport

_Date : 2026-05-07_

## Résumé

| Métrique | Valeur |
|---|---:|
| Fichiers PHP applicatifs scannés | 6 066 |
| Fichiers modifiés (use ajoutés) | **0** |
| Fichiers sans référence quantumcore | 4 254 |
| Fichiers sans rien à ajouter (use déjà en place ou conflits locaux) | 1 807 |
| Fichiers en erreur (pas de tag `<?php` au début) | 5 |
| **Total `use` ajoutés** | **0** |

## Idempotence vérifiée

Le script peut être ré-exécuté : un nouveau passage en dry-run rapporte 0 modification supplémentaire. Aucun risque de doublons.

## Top 25 classes les plus importées

| Classe | Fichiers |
|---|---:|

## Fichiers en erreur

5 fichiers ne commencent pas par `<?php` — ils n'ont pas pu être traités automatiquement (et n'ont pas besoin de l'être) :

1. `app/cache/class_index.php` — fichier de cache généré
2. `includes/plugins/ph_chatboxpro/views/widget/init.js.php` — fichier JS avec `<?php` inline
3. `includes/plugins/revslider/public/views/revslider-front-page-template.php` — template HTML
4. `includes/plugins/revslider/views/templates/admin/features/add_ons.php` — template HTML
5. `includes/plugins/revslider/views/templates/admin/revslider_sliders/features/add_ons.php` — idem

## Comportement attendu

Avant la migration :

```php
// Fichier sans namespace (espace global)
$cart = new Cart();
// → cherche \Cart, l'autoloader d'alias crée \Cart → \EphenyxDigital\QuantumCore\Cart
```

Après la migration :

```php
use EphenyxDigital\QuantumCore\Cart;

$cart = new Cart();
// → résolu directement vers \EphenyxDigital\QuantumCore\Cart, sans passer par les alias
```

## Prochaines étapes

1. **Régénérer l'autoload** : `php composer.phar dump-autoload` à la racine
2. **Tester** sur les pages les plus critiques
3. **Vérifier** les 5 fichiers en erreur manuellement si nécessaire
4. **Optionnel** : retirer le mécanisme d'alias paresseux dans quantumcore une fois que tout est testé en prod