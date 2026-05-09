# Audit — Utilisation de quantumcore dans phenyx.io

_Date : 2026-05-07_

## Périmètre

Scan de tout le code PHP applicatif de `D:\Sites webs\phenyx.io` **hors** :

- `vendor/` (dépendances Composer, dont quantumcore lui-même)
- `phenyxDigital/vendor/` (ancienne copie de digitalcore)
- `node_modules/`, `packs/`, `content/`, `ressource/`, `NewsLetter/`

## Volumétrie

| Métrique | Valeur |
|---|---|
| Fichiers PHP scannés | 6,066 |
| Fichiers contenant ≥1 référence à quantumcore | 1,812 |
| Total occurrences | 35,948 |
| Classes quantumcore référencées | 161 / 256 |
| Classes JAMAIS utilisées dans phenyx.io | 95 |

## Top 25 des classes les plus utilisées

| Classe | Occurrences | Fichiers |
|---|---:|---:|
| `Tools` | 8,941 | 715 |
| `Db` | 3,793 | 620 |
| `Plugin` | 2,930 | 553 |
| `Page` | 2,920 | 296 |
| `DbQuery` | 2,055 | 443 |
| `Configuration` | 1,857 | 449 |
| `Context` | 1,784 | 551 |
| `Validate` | 1,481 | 405 |
| `PhenyxException` | 942 | 233 |
| `License` | 771 | 123 |
| `Zone` | 735 | 227 |
| `Hook` | 640 | 291 |
| `PhenyxObjectModel` | 528 | 299 |
| `Meta` | 503 | 123 |
| `RevLoader` | 491 | 24 |
| `Composer` | 451 | 328 |
| `CacheApi` | 435 | 56 |
| `Country` | 407 | 140 |
| `PhenyxController` | 238 | 158 |
| `PhenyxCollection` | 222 | 128 |
| `Company` | 220 | 83 |
| `Media` | 216 | 53 |
| `PhenyxDatabaseException` | 202 | 43 |
| `MySQL` | 171 | 24 |
| `BackTab` | 162 | 51 |

Ces classes constituent le « noyau dur » d'utilisation. La compatibilité ascendante via `aliases.php` est **critique** pour ces classes.

## Patterns à risque détectés

| Pattern | Occurrences | Statut après migration |
|---|---:|---|
| Nom de classe entre `''` ou `""` | 1,655 | ✅ Couvert par les alias paresseux |
| Callable `[ClassName, method]` | 36 | ✅ Couvert par les alias |
| String assignée à une variable | 14 | ✅ Couvert par les alias |
| `class_exists`/`is_a`/`is_subclass_of` litéral | 13 | ✅ Couvert par les alias |
| `ReflectionClass()` litéral | 0 | n/a |

### Détail : `class_exists` / `is_a` / `is_subclass_of`

**`Context`** (8 occurrences)

- `./includes/classes/Language.php:275`
- `./includes/classes/Language.php:291`
- `./includes/plugins/ph_paypal/api/ApiPaypalPlus.php:9`
- `./includes/plugins/ph_paypal/controllers/front/BraintreeSubmitController.php:15`
- `./includes/plugins/ph_paypal/controllers/front/PlusPatchController.php:12`

**`Meta`** (4 occurrences)

- `./includes/plugins/ph_ecommerce/controllers/admin/AdminTopMenuController.php:1398`
- `./includes/plugins/ph_ecommerce/controllers/admin/AdminTopMenuController.php:1425`
- `./includes/plugins/ph_ecommerce/controllers/admin/AdminTopMenuController.php:1681`
- `./includes/plugins/ph_ecommerce/controllers/admin/AdminTopMenuController.php:1708`

**`RevSliderFront`** (1 occurrences)

- `./includes/plugins/revslider/addons/revslider-liquideffect-addon/framework/base.class.php:16`

## Verdict

> **La migration namespace de quantumcore n'introduit aucune régression bloquante dans phenyx.io.**

Les ~37 000 références sont toutes couvertes par l'autoloader d'alias paresseux (`src/aliases.php`) — aucun changement n'est requis dans phenyx.io pour que le code continue de fonctionner.

## Découvertes annexes

### 1. Doublon de digitalcore

Il existe **deux copies** de digitalcore dans le projet :

- `vendor/ephenyxdigital/quantumcore/` ← celui qu'on vient de migrer (`composer.json` parent dépend de `^2`)
- `phenyxDigital/vendor/phenyxdigitale/digitalcore/` ← ancienne version, NON migrée

Recommandation : déterminer si l'ancienne copie est encore utilisée. Si non, la supprimer.

### 2. Classes quantumcore jamais utilisées

95 classes sur 256 ne sont jamais référencées dans phenyx.io. Sample :

```
Adapter_Configuration
Adapter_Database
Adapter_EntityMapper
Adapter_EntityMetaDataRetriever
AddConfToFile
CacheApiInterface
ComposerAddElementBox
ComposerAutocomplete
ComposerAutomapModel
ComposerBackenEditor
ComposerEditLayout
ComposerFrontendEditor
ComposerLoopQueryBuilder
ComposerNavbar
ComposerPostSeetings
ComposerSeetings
ComposerShortCodeSettings
ComposerShortCodeUniversalAdmin
ComposerShortCode_abstract
ComposerShortCode_vc_accordion
ComposerShortCode_vc_accordion_tab
ComposerShortCode_vc_blockcontact
ComposerShortCode_vc_button
ComposerShortCode_vc_button2
ComposerShortCode_vc_column
ComposerShortCode_vc_column_inner
ComposerShortCode_vc_column_text
ComposerShortCode_vc_custom_heading
ComposerShortCode_vc_facebook
ComposerShortCode_vc_flickr
```

Beaucoup sont des classes spécialisées de RevSlider ou de composants particuliers. Garde-les si tu prévois d'utiliser ces fonctionnalités.

## Étapes suggérées

1. **Régénérer l'autoload** : `php composer.phar dump-autoload` à la racine de phenyx.io
2. **Tester l'application** sur les pages les plus critiques (Tools, Db, Configuration sont massivement utilisés)
3. **Optionnel** : décider du sort de `phenyxDigital/vendor/phenyxdigitale/digitalcore/`
4. **Optionnel à plus long terme** : migrer progressivement les usages vers les FQN namespacés (`new \EphenyxDigital\QuantumCore\Configuration()`) pour pouvoir un jour retirer les alias