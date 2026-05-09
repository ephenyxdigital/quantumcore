<?php

namespace EphenyxDigital\QuantumCore;



use Defuse\Crypto\Crypto;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Defuse\Crypto\Key;

/**
 * Class PhpEncryption
 *
 * Thin wrapper around defuse/php-encryption for use as the primary cipher tool.
 *
 * @since 1.9.1.0
 *
 * Changelog vs previous version:
 *  - [SECURITY] decrypt() now distinguishes between a tampered ciphertext
 *               (WrongKeyOrModifiedCiphertextException → returns null + logs)
 *               and a broken environment (EnvironmentIsBrokenException → re-thrown).
 *  - [QUALITY]  Added strict PHP 7+ type hints and return types throughout.
 *  - [QUALITY]  Documented the difference between this class and PhpEncryptionEngine.
 *
 * NOTE — PhpEncryptionEngine vs PhpEncryption:
 *   Both classes wrap defuse/php-encryption. PhpEncryptionEngine additionally
 *   exposes static utility helpers (createNewRandomKey, randomBytes, etc.) that
 *   are used during key generation / installation. PhpEncryption is the runtime
 *   cipher tool used by Encryptor. Consider merging them in a future refactor.
 */
class PhpEncryption {

    /** @var Key */
    protected $key;

    /**
     * @param string $asciiKey  The ASCII-safe key string produced by Key::saveToAsciiSafeString().
     *
     * @throws \Defuse\Crypto\Exception\BadFormatException
     * @throws EnvironmentIsBrokenException
     */
    public function __construct(string $asciiKey) {

        $this->key = Key::loadFromAsciiSafeString($asciiKey);
    }

    /**
     * Encrypt plaintext.
     *
     * @param string $plaintext
     *
     * @return string Ciphertext
     *
     * @throws EnvironmentIsBrokenException If the system lacks the required cryptographic primitives.
     */
    public function encrypt(string $plaintext): string {

        return Crypto::encrypt($plaintext, $this->key);
    }

    /**
     * Decrypt ciphertext.
     *
     * Returns null when the ciphertext has been tampered with or the key is
     * wrong (i.e. a cookie from a different installation / old key).
     *
     * Re-throws EnvironmentIsBrokenException because that signals a system-level
     * problem that must not be silently swallowed.
     *
     * @param string $ciphertext
     *
     * @return string|null Plaintext, or null if decryption fails due to invalid data.
     *
     * @throws EnvironmentIsBrokenException
     */
    public function decrypt(string $ciphertext): ?string {

        try {
            return Crypto::decrypt($ciphertext, $this->key);

        } catch (WrongKeyOrModifiedCiphertextException $e) {
            // Tampered or outdated cookie — not a system error, return null gracefully.
            // Log at warning level so it is visible without being fatal.
            if (function_exists('error_log')) {
                error_log('[PhpEncryption] Decryption failed (wrong key or modified ciphertext): ' . $e->getMessage());
            }

            return null;

        } catch (EnvironmentIsBrokenException $e) {
            // The host environment is missing required crypto capabilities.
            // Propagate this so the caller can handle it appropriately.
            throw $e;
        }
    }
}
