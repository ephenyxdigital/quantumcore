<?php
/**
 * Autoloader des alias de compatibilité ascendante.
 *
 * Pendant la phase de migration vers le namespace EphenyxDigital\QuantumCore,
 * cet autoloader gère deux cas :
 *
 *  1. Code EXTERNE qui utilise encore les noms courts non-namespacés
 *     (ex. "new Adapter_Configuration()") → on crée un alias vers la classe
 *     namespacée pour ne rien casser.
 *
 *  2. Code INTERNE déjà migré qui référence une classe pas encore migrée
 *     (ex. "implements Core_Business_ConfigurationInterface" dans un fichier
 *     namespacé alors que l'interface est encore globale) → on crée un alias
 *     dans l'autre sens : EphenyxDigital\QuantumCore\X → \X.
 *
 * Les deux mécanismes sont paresseux : aucun alias n'est créé tant que le
 * symbole n'est pas effectivement référencé.
 *
 * Chargé automatiquement via la directive "files" du composer.json.
 */

\spl_autoload_register(static function (string $class): void {
    static $map = null;

    if ($map === null) {
        $mapFile = __DIR__ . '/aliases-map.php';
        $map = \is_file($mapFile) ? require $mapFile : [];
    }

    // Cas 1 : nom court → version namespacée
    // Avec prepend=true, on intercepte AVANT Composer pour court-circuiter
    // un éventuel ancien classmap legacy qui pointerait encore vers le fichier
    // sans déclencher la nouvelle classe namespacée.
    if (isset($map[$class])) {
        $fqn = $map[$class];
        // Force l'autoload de la classe namespacée si elle n'est pas encore chargée.
        if (!\class_exists($fqn, false) && !\interface_exists($fqn, false) && !\trait_exists($fqn, false)) {
            \spl_autoload_call($fqn);
        }
        if (\class_exists($fqn, false) || \interface_exists($fqn, false) || \trait_exists($fqn, false)) {
            \class_alias($fqn, $class);
        }
        return;
    }

    // Cas 2 : version namespacée → nom court (pendant la transition)
    $prefix = 'EphenyxDigital\\QuantumCore\\';
    if (\strncmp($class, $prefix, \strlen($prefix)) === 0) {
        // Garde-fou : si la classe FQN est deja chargee (par PSR-4 ou par
        // une recursion via le case 1), on ne fait rien -> evite le fatal
        // "Cannot redeclare class".
        if (\class_exists($class, false) || \interface_exists($class, false) || \trait_exists($class, false)) {
            return;
        }
        $shortName = \substr($class, \strlen($prefix));
        if (
            \class_exists($shortName, true)
            || \interface_exists($shortName, false)
            || \trait_exists($shortName, false)
        ) {
            // Re-verifier apres l'autoload de $shortName : il peut avoir
            // declenche le case 1 qui charge $class FQN puis cree l'alias
            // $shortName -> $class. Dans ce cas, $class est deja la, on sort.
            if (\class_exists($class, false) || \interface_exists($class, false) || \trait_exists($class, false)) {
                return;
            }
            \class_alias($shortName, $class);
        }
    }
}, true, true);  // throw=true, prepend=true → registre AVANT Composer
