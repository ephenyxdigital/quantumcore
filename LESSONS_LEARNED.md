# Migration namespace `EphenyxDigital\QuantumCore` — Leçons apprises

_Date : 2026-05-07. Tentative interrompue avant complétion à cause d'erreurs en cascade en prod (pas d'environnement de dev local PHP)._

## Résumé exécutif

Migration de **`ephenyxdigital/quantumcore`** depuis le legacy `classmap` vers du PSR-4 avec namespace racine plat `EphenyxDigital\QuantumCore`. Le travail a été fait en grande partie (256 fichiers migrés, 1802 fichiers de phenyx.io adaptés), mais le déploiement sur Plesk a déclenché une succession d'erreurs subtiles de résolution de classe que nous n'avons pas pu prévenir faute de pouvoir tester localement.

**Cause racine du blocage** : tester chaque fix directement en prod, sans `php -l` ni autoload Composer fonctionnel localement.

## Pré-requis ABSOLUS pour ré-essayer la migration

1. **PHP CLI installé sur la machine de dev** (`php -v` doit fonctionner)
2. **Composer fonctionnel localement** (`php composer.phar dump-autoload`)
3. **Environnement de staging** ou au minimum un site local fonctionnel d'ephenyx_io
4. **Branche git dédiée** pour pouvoir rollback proprement
5. **Capacité à exécuter** `find src -name "*.php" -exec php -l {} \;` pour détecter les erreurs syntaxe avant déploiement

Sans ces 5 points, **ne pas tenter la migration**.

## Pièges techniques rencontrés

### Piège n°1 — Strings de noms de classes

Dans un fichier avec `namespace EphenyxDigital\QuantumCore;`, ce code casse :

```php
$class = 'DbPDO';
$instance = new $class();   // PHP cherche \DbPDO global → introuvable
```

**Fix** : utiliser `::class` qui résout au FQN à la compilation :

```php
$class = DbPDO::class;       // = 'EphenyxDigital\QuantumCore\DbPDO'
$instance = new $class();    // OK
```

**Fichiers concernés rencontrés** :
- `src/classes/db/Db.php` : `getClass()` retournait `'DbPDO'`
- `src/classes/cache/CacheApi.php` : `const APIS_DEFAULT = 'FileBased';`
- `src/classes/Performer.php` : `$controllers['installer'] = 'InstallerController';`
- 9 autres fichiers avec `$class = 'XYZ';` puis `new $class()`

### Piège n°2 — Classes globales PHP non préfixées

Dans un namespace, `new PDO(...)` cherche `EphenyxDigital\QuantumCore\PDO`. Il faut soit :

```php
new \PDO(...)        // FQN explicite
```

ou ajouter en haut du fichier :

```php
use PDO;
use Exception;
use DateTime;
use ReflectionClass;
// etc.
```

**137 classes globales** étaient utilisées dans nos 256 fichiers : `PDO`, `Exception`, `DateTime`, `RecursiveDirectoryIterator`, `RecursiveIteratorIterator`, `ZipArchive`, `DOMDocument`, `SimpleXMLElement`, `Imagick`, `Language`, `Link`, `CMS`, `Product`, `Group`, `User`, etc.

### Piège n°3 — Classes natives en lowercase

Mon regex `[A-Z][A-Za-z0-9_]*` ratait `stdClass`, `mysqli`, `finfo`, `tidy` (commencent par minuscule). Il faut une whitelist explicite.

### Piège n°4 — `use` doublons en case-insensitive

PHP est **case-insensitive** sur les noms de classes. Donc :

```php
use ZIPARCHIVE;
use ZipArchive;   // ← Fatal: name already in use
```

Détection à faire en `lower()` lors du scan.

### Piège n°5 — `use` doublons global vs namespacé

Si le fichier original avait déjà `use Detection\DeviceDetect;`, ajouter `use DeviceDetect;` global crée un conflit. Toujours scanner les `use` existants AVANT d'ajouter.

### Piège n°6 — Fin de fichier mangée par bindfs

Le système de fichiers virtio/bindfs **tronquait silencieusement les gros fichiers** lors d'écritures Python. Workaround : écrire dans `/tmp` puis `cp /tmp/file destination`.

Conséquence : une douzaine de fichiers s'étaient retrouvés tronqués à mi-classe sans aucun signal d'erreur (`open(..., 'w')` réussissait mais le contenu était amputé).

### Piège n°7 — Composer autoloader prioritaire sur aliases.php

Par défaut, `spl_autoload_register(...)` ajoute en queue. Composer s'enregistre avec `prepend=true` donc passe en tête. Pour qu'`aliases.php` court-circuite un ancien classmap legacy sur prod, il faut aussi :

```php
spl_autoload_register($fn, true, true);  // throw=true, prepend=true
```

### Piège n°8 — composer.lock désynchronisé

`composer.lock` de phenyx.io référençait encore l'ancien `phenyxdigitale/digitalcore: 1.7.9` au lieu de `ephenyxdigital/quantumcore`. Un `composer install` sur prod réinstallait donc l'ancienne version non-migrée → écrasait notre travail.

**Fix prévu mais non réalisé** : pousser une branche `dev-namespace-migration` sur `ephenyxdigital/quantumcore` GitHub et référencer `"ephenyxdigital/quantumcore": "dev-namespace-migration"` dans `composer.json` de phenyx.io.

## Architecture de la migration (à reconstituer)

### composer.json de quantumcore

```json
{
    "autoload": {
        "psr-4": {
            "EphenyxDigital\\QuantumCore\\": [
                "src/Adapter/", "src/Controller/", "src/Core/Business/",
                "src/Core/Business/CMS/", "src/Core/Business/Email/",
                "src/Core/Business/Payment/",
                "src/Core/Foundation/Database/",
                "src/Core/Foundation/Database/EntityManager/",
                "src/Core/Foundation/Exception/",
                "src/Core/Foundation/Filesystem/",
                "src/Core/Foundation/IoC/",
                "src/classes/", "src/classes/License/", "src/classes/Referrer/",
                "src/classes/cache/", "src/classes/company/",
                "src/classes/composer/", "src/classes/composer/shortcodes/",
                "src/classes/db/", "src/classes/exception/",
                "src/classes/geographical/", "src/classes/helper/",
                "src/classes/logs/", "src/classes/plugins/",
                "src/classes/range/", "src/classes/revslider/",
                "src/classes/topmenu/", "src/classes/tree/"
            ],
            "ReCaptcha\\": "src/classes/ReCaptcha/"
        },
        "files": [
            "digitalcore.composer.php",
            "src/aliases.php"
        ]
    }
}
```

### Autoloader d'alias paresseux (`src/aliases.php`)

Critique : doit être registré avec `prepend=true` pour intercepter les noms courts AVANT Composer.

```php
\spl_autoload_register(static function (string $class): void {
    static $map = null;
    if ($map === null) {
        $mapFile = __DIR__ . '/aliases-map.php';
        $map = \is_file($mapFile) ? require $mapFile : [];
    }
    if (isset($map[$class])) {
        $fqn = $map[$class];
        if (!\class_exists($fqn, false) && !\interface_exists($fqn, false) && !\trait_exists($fqn, false)) {
            \spl_autoload_call($fqn);
        }
        if (\class_exists($fqn, false) || \interface_exists($fqn, false) || \trait_exists($fqn, false)) {
            \class_alias($fqn, $class);
        }
        return;
    }
    // Cas inverse pendant transition (FQN → global)
    $prefix = 'EphenyxDigital\\QuantumCore\\';
    if (\strncmp($class, $prefix, \strlen($prefix)) === 0) {
        $shortName = \substr($class, \strlen($prefix));
        if (\class_exists($shortName, true) || \interface_exists($shortName, false) || \trait_exists($shortName, false)) {
            \class_alias($shortName, $class);
        }
    }
}, true, true);  // ← prepend=true : critique
```

## Méthodologie suggérée pour la prochaine tentative

### Phase 0 — Préparation (1 jour)

1. Cloner phenyx.io en local avec PHP fonctionnel
2. Créer une branche `migration-namespace` sur les 2 repos (quantumcore + phenyx.io)
3. S'assurer que `php -l` et `php composer.phar dump-autoload` marchent localement
4. **Créer un cas de test minimal** : page index.php qui charge `Configuration::getInstance()` et `Db::getInstance()` — si ça marche, le socle est OK

### Phase 1 — Migration de quantumcore (4 heures)

Reprendre le script `tools/add-namespace.php` MAIS :
- Faire le scan AVANT et logger TOUTES les classes globales utilisées
- Ajouter les `use` statements pour TOUTES (pas juste celles avec `[A-Z]`)
- Inclure une whitelist des classes natives lowercase (`stdClass`, `mysqli`, etc.)
- Détecter les `use` doublons en `.lower()`
- Détecter les patterns string : `= 'ClassName';`, `return 'ClassName';`, `const X = 'ClassName';`
- Tester avec `php -l` après chaque lot

### Phase 2 — Vérification locale exhaustive (1 jour)

```bash
# Linter syntaxe
find vendor/ephenyxdigital/quantumcore/src -name "*.php" -exec php -l {} \;

# Composer doit pouvoir générer l'autoload
php composer.phar dump-autoload --optimize

# Tester l'app : ouvrir la home page, ajouter au panier, login admin
```

Si UNE seule erreur surgit en local, **fix avant de déployer**. Pas comme nous on l'a fait.

### Phase 3 — Pousser et publier sur GitHub

1. `git push origin migration-namespace` sur quantumcore
2. Mettre à jour phenyx.io/composer.json : `"ephenyxdigital/quantumcore": "dev-migration-namespace"`
3. `composer update ephenyxdigital/quantumcore` localement → met à jour composer.lock proprement

### Phase 4 — Staging puis prod

Déployer d'abord sur un staging et tester avant la prod.

## Fichiers à conserver pour reproduire

Tous dans `D:\Sauvegarde\quantumcore-namespace-migration\` :

| Fichier | Utilité |
|---|---|
| `vendor/ephenyxdigital/quantumcore/` (complet) | Code migré |
| `vendor/ephenyxdigital/quantumcore/src/aliases.php` | Autoloader d'alias |
| `vendor/ephenyxdigital/quantumcore/src/aliases-map.php` | Map des 255 alias |
| `vendor/ephenyxdigital/quantumcore/tools/add-namespace.php` | Script |
| `vendor/ephenyxdigital/quantumcore/NAMESPACE_MIGRATION.md` | Plan |
| `AUDIT_QUANTUMCORE.md` | Audit phenyx.io |
| `MIGRATION_USES_REPORT.md` | Rapport `use` |
| `LESSONS_LEARNED.md` (ce fichier) | À lire AVANT de reprendre |

## Pourquoi ce rollback

Migrer un codebase de cette ampleur (256 classes vendor + 1812 fichiers app, ~37 000 références à transformer) **sans environnement de dev local fonctionnel** est trop risqué. Chaque déploiement révèle une nouvelle erreur subtile (PDO, stdClass, ZipArchive, doublons de `use`...) qu'on aurait détectée en 30 secondes avec `php -l` localement.

Le rollback préserve un site en production stable. La migration n'est pas perdue : tous les artefacts ci-dessus permettent de la reprendre avec un meilleur outillage.

## Prochaine étape

Quand on reprendra :

1. **Lire ce document complet** avant tout
2. **Ne pas re-écrire le script de migration de zéro** — partir de `tools/add-namespace.php` et l'enrichir avec les patterns identifiés
3. **Tester sur un sous-arbre d'abord** (ex. juste `src/Adapter/`) avant les 256 fichiers
4. **Vérifier `php -l` après chaque transformation**

Bonne chance pour la suite 🍀
