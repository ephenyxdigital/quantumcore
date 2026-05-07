<?php

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Encoding;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Defuse\Crypto\Key;

/**
 * Class PhpEncryptionEngine
 *
 * OpenSSL-based encryption engine built on defuse/php-encryption.
 * Exposes static utility helpers for key generation and installation tasks,
 * in addition to the standard encrypt/decrypt interface.
 *
 * @since 1.0.1
 *
 * Changelog vs previous version:
 *  - [SECURITY] randomCompat() replaced with randomBytes() using random_bytes() exclusively.
 *               mcrypt_create_iv() has been removed — mcrypt was dropped in PHP 7.2.
 *  - [SECURITY] decrypt() now re-throws non-tamper exceptions instead of swallowing them.
 *  - [QUALITY]  Added strict PHP 7+ type hints and return types throughout.
 *  - [QUALITY]  Removed RandomCompat_strlen() dependency (no longer needed with random_bytes).
 *  - [QUALITY]  Deprecated randomCompat() — use randomBytes() instead.
 */
class PhpEncryptionEngine {

    /** @var Key */
    protected $key;

    /**
     * @param string $hexString  ASCII-safe key string (as produced by createNewRandomKey()).
     */
    public function __construct(string $hexString) {

        $this->key = self::loadFromAsciiSafeString($hexString);
    }

    // -------------------------------------------------------------------------
    // Instance methods
    // -------------------------------------------------------------------------

    /**
     * Encrypt plaintext.
     *
     * @param string $plaintext
     *
     * @return string Ciphertext
     *
     * @throws EnvironmentIsBrokenException
     */
    public function encrypt(string $plaintext): string {

        return Crypto::encrypt($plaintext, $this->key);
    }

    /**
     * Decrypt ciphertext.
     *
     * Returns false when the ciphertext has been modified or the wrong key was
     * used. Re-throws any other exception so callers are aware of system errors.
     *
     * @param string $cipherText
     *
     * @return string|false Plaintext, or false on tampered/invalid ciphertext.
     *
     * @throws EnvironmentIsBrokenException
     */
    public function decrypt(string $cipherText) {

        try {
            return Crypto::decrypt($cipherText, $this->key);

        } catch (WrongKeyOrModifiedCiphertextException $e) {
            return false;

        } catch (EnvironmentIsBrokenException $e) {
            throw $e;
        }
    }

    // -------------------------------------------------------------------------
    // Static utility helpers (used during key generation / installation)
    // -------------------------------------------------------------------------

    /**
     * Generate a new random key and return it as an ASCII-safe string.
     *
     * @return string
     *
     * @throws EnvironmentIsBrokenException
     */
    public static function createNewRandomKey(): string {

        return Key::createNewRandomKey()->saveToAsciiSafeString();
    }

    /**
     * Load a Key object from an ASCII-safe string.
     *
     * @param string $asciiKey
     *
     * @return Key
     *
     * @throws \Defuse\Crypto\Exception\BadFormatException
     * @throws EnvironmentIsBrokenException
     */
    public static function loadFromAsciiSafeString(string $asciiKey): Key {

        return Key::loadFromAsciiSafeString($asciiKey);
    }

    /**
     * Encode raw bytes into a checksummed ASCII-safe string with a header.
     *
     * @param string $header
     * @param string $bytes
     *
     * @return string
     *
     * @throws EnvironmentIsBrokenException
     */
    public static function saveBytesToChecksummedAsciiSafeString(string $header, string $bytes): string {

        return Encoding::saveBytesToChecksummedAsciiSafeString($header, $bytes);
    }

    /**
     * Encode raw bytes into a checksummed ASCII-safe string using the current key version header.
     *
     * @param string $buf Raw bytes
     *
     * @return string
     *
     * @throws EnvironmentIsBrokenException
     */
    public static function saveToAsciiSafeString(string $buf): string {

        return Encoding::saveBytesToChecksummedAsciiSafeString(
            Key::KEY_CURRENT_VERSION,
            $buf
        );
    }

    /**
     * Generate cryptographically secure random bytes.
     *
     * This replaces the old randomCompat() method which relied on the deprecated
     * mcrypt extension and the now-unnecessary random_compat polyfill.
     * PHP 7.0+ ships random_bytes() natively and this is the only source we need.
     *
     * @param int $length Number of bytes to generate.
     *
     * @return string Raw random bytes.
     *
     * @throws \Exception If the system PRNG is unavailable (should never happen on PHP 7+).
     */
    public static function randomBytes(int $length): string {

        if ($length <= 0) {
            throw new \InvalidArgumentException('Length must be a positive integer.');
        }

        return random_bytes($length);
    }

    /**
     * @deprecated Use randomBytes() instead. Kept for backwards compatibility only.
     *
     * @return string
     *
     * @throws \Exception
     */
    public static function randomCompat(): string {

        return self::randomBytes(Key::KEY_BYTE_SIZE);
    }
}
