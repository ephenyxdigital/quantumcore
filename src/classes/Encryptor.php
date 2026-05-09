<?php

namespace EphenyxDigital\QuantumCore;


/**
 * Class Encryptor
 *
 * Facade that selects the best available cipher tool and exposes a unified
 * encrypt/decrypt interface.
 *
 * @since 1.0.1
 *
 * Changelog vs previous version:
 *  - [BUG]      Fixed typo: '__RIJNDAEL_IV_' → '_RIJNDAEL_IV_' in supportsRijndael()
 *  - [SECURITY] Standalone cipher tool now derives key/IV with SHA-256 instead of
 *               repeated MD5 (higher entropy, avoids str_pad repetition weakness)
 *  - [QUALITY]  Added reset() method to allow re-initialisation in test environments
 *  - [QUALITY]  Added strict type hints and return types
 */
class Encryptor {

    const ALGO_BLOWFISH      = 0;
    const ALGO_RIJNDAEL      = 1;
    const ALGO_PHP_ENCRYPTION = 2;

    /** @var Blowfish|Rijndael|PhpEncryption Cipher tool instance */
    protected $cipherTool;

    /** @var Encryptor|null Singleton instance */
    protected static $instance;

    /** @var Encryptor|null Standalone singleton */
    protected static $standalone;

    // -------------------------------------------------------------------------
    // Factory / singleton
    // -------------------------------------------------------------------------

    /**
     * Return the main Encryptor singleton, configured from application settings.
     *
     * @return Encryptor
     *
     * @throws PhenyxException When no cipher tool is available.
     */
    public static function getInstance(): self {

        if (!static::$instance) {
            $cipherTool = static::getCipherTool();

            if (!$cipherTool) {
                // We need some ciphering capability to encode the error message;
                // use the standalone tool as a last resort before throwing.
                static::$instance = new self(static::getStandaloneCipherTool(__FILE__));
                throw new PhenyxException('No encryption tool available.');
            }

            static::$instance = new self($cipherTool);
        }

        return static::$instance;
    }

    /**
     * Return a standalone Encryptor singleton.
     *
     * Used in special situations where the main configuration is not yet
     * available (e.g. during installation).
     *
     * @param string $salt
     *
     * @return Encryptor
     */
    public static function getStandaloneInstance(string $salt): self {

        if (!static::$standalone) {
            static::$standalone = new self(static::getStandaloneCipherTool($salt));
        }

        return static::$standalone;
    }

    /**
     * Reset both singletons.
     *
     * Useful in unit tests or when encryption settings change at runtime.
     */
    public static function reset(): void {

        static::$instance   = null;
        static::$standalone = null;
    }

    /**
     * @param Blowfish|Rijndael|PhpEncryption $cipherTool
     */
    protected function __construct($cipherTool) {

        $this->cipherTool = $cipherTool;
    }

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Encrypt plaintext.
     *
     * @param string $content
     *
     * @return string|false Ciphertext, or false on failure.
     */
    public function encrypt(string $content) {

        return $this->cipherTool->encrypt($content);
    }

    /**
     * Decrypt ciphertext.
     *
     * @param string $content
     *
     * @return string|null Plaintext, or null on failure.
     */
    public function decrypt(string $content): ?string {

        return $this->cipherTool->decrypt($content);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Select the best available cipher tool based on application configuration.
     *
     * Priority: PhpEncryption > Rijndael > Blowfish (fallback).

     *
     * @return Blowfish|Rijndael|PhpEncryption|null
     */
    private static function getCipherTool() {

        $phenyxConfig = Configuration::getInstance();
        $algo         = (int) $phenyxConfig->get('EPH_CIPHER_ALGORITHM');

        if ($algo === static::ALGO_PHP_ENCRYPTION && static::supportsPhpEncryption()) {
            return new PhpEncryption(_PHP_ENCRYPTION_KEY_);
        }

        if ($algo === static::ALGO_RIJNDAEL && static::supportsRijndael()) {
            return new Rijndael(_RIJNDAEL_KEY_, _RIJNDAEL_IV_);
        }

        // Always fall back to Blowfish
        if (static::supportsBlowfish()) {
            return new Blowfish(_COOKIE_KEY_, _COOKIE_IV_);
        }

        return null;
    }

    /**
     * Build the standalone Blowfish cipher tool from a salt value.
     *
     * FIXED: Previously used str_pad('', 56, md5(...)) which simply repeats a
     * 32-char string, giving very low entropy. We now use SHA-256 (64 hex chars)
     * and truncate/pad to the required 56-byte Blowfish key size.
     *
     * @param string $salt
     *
     * @return Blowfish
     */
    private static function getStandaloneCipherTool(string $salt): Blowfish {

        $key = substr(hash('sha256', 'eph_key_' . $salt), 0, 56);
        $iv  = substr(hash('sha256', 'eph_iv_'  . $salt), 0, 56);

        return new Blowfish($key, $iv);
    }

    /**
     * Check whether PhpEncryption (defuse/php-encryption) can be used.
     *
     * @return bool
     */
    private static function supportsPhpEncryption(): bool {

        return defined('_PHP_ENCRYPTION_KEY_')
            && extension_loaded('openssl')
            && function_exists('openssl_encrypt');
    }

    /**
     * Check whether Rijndael encryption can be used.
     *
     * FIXED: The original code checked for the constant '__RIJNDAEL_IV_' (double
     * leading underscore) which is a typo — the correct constant is '_RIJNDAEL_IV_'.
     * This meant Rijndael was never selected even when properly configured.
     *
     * @return bool
     */
    private static function supportsRijndael(): bool {

        if (!defined('_RIJNDAEL_KEY_') || !defined('_RIJNDAEL_IV_')) {
            return false;
        }

        // Rijndael is supported directly by OpenSSL
        if (extension_loaded('openssl') && function_exists('openssl_encrypt')) {
            return true;
        }

        // mcrypt fallback is intentionally dropped: mcrypt was removed in PHP 7.2.
        // If OpenSSL is not available, we cannot use Rijndael.

        return false;
    }

    /**
     * Check whether Blowfish encryption can be used.
     *
     * @return bool
     */
    private static function supportsBlowfish(): bool {

        return defined('_COOKIE_KEY_') && defined('_COOKIE_IV_');
    }
}
