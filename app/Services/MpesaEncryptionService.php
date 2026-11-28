<?php

namespace App\Services;

use RuntimeException;

class MpesaEncryptionService
{
    /** @var array<string, string> */
    private array $keys = [];

    private string $activeVersion;

    public function __construct()
    {
        $this->activeVersion = (string) config('services.mpesa.active_encryption_key_version', 'v1');
        $configuredKeys = (array) config('services.mpesa.encryption_keys', []);
        foreach ($configuredKeys as $version => $value) {
            if (is_string($value) && $value !== '') {
                $this->keys[(string) $version] = $this->decodeKey($value, 'MPESA_ENCRYPTION_KEYS['.$version.']');
            }
        }

        // Backward compatibility: unversioned ciphertext uses MPESA_ENCRYPTION_KEY.
        $legacy = config('services.mpesa.encryption_key');
        if (is_string($legacy) && $legacy !== '') {
            $this->keys['legacy'] = $this->decodeKey($legacy, 'MPESA_ENCRYPTION_KEY');
            $this->keys[$this->activeVersion] ??= $this->keys['legacy'];
        }

        if (! isset($this->keys[$this->activeVersion])) {
            throw new RuntimeException('The active M-Pesa encryption key version is not configured.');
        }
    }

    /** Encrypt plaintext → base64(iv + tag + ciphertext) */
    public function encrypt(string $plaintext): string
    {
        $iv = random_bytes(12); // GCM standard IV
        $tag = '';
        $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $this->keys[$this->activeVersion], OPENSSL_RAW_DATA, $iv, $tag, '', 16);
        if ($ciphertext === false) {
            throw new RuntimeException('M-Pesa encryption failed.');
        }

        return $this->activeVersion.':'.base64_encode($iv.$tag.$ciphertext);
    }

    /** Decrypt base64(iv + tag + ciphertext) → plaintext */
    public function decrypt(string $cipherblob): string
    {
        $version = 'legacy';
        $encoded = $cipherblob;
        if (preg_match('/^(v[1-9][0-9]*):(.+)$/', $cipherblob, $matches) === 1) {
            [, $version, $encoded] = $matches;
        }
        if (! isset($this->keys[$version])) {
            throw new RuntimeException('The encryption key version for this M-Pesa credential is unavailable.');
        }
        $raw = base64_decode($encoded, true);
        if ($raw === false || strlen($raw) < 29) { // 12 IV + 16 tag + 1 min ciphertext
            throw new RuntimeException('M-Pesa cipherblob is malformed.');
        }
        $iv = substr($raw, 0, 12);
        $tag = substr($raw, 12, 16);
        $ciphertext = substr($raw, 28);
        $plain = openssl_decrypt($ciphertext, 'aes-256-gcm', $this->keys[$version], OPENSSL_RAW_DATA, $iv, $tag);
        if ($plain === false) {
            throw new RuntimeException('M-Pesa decryption failed — ciphertext integrity check failed.');
        }

        return $plain;
    }

    /** Mask a secret — returns "••••" with last 4 chars */
    public function mask(string $plaintext): string
    {
        $suffix = substr($plaintext, -4);

        return '••••'.$suffix;
    }

    public function reencryptForActiveVersion(string $cipherblob): string
    {
        if (str_starts_with($cipherblob, $this->activeVersion.':')) {
            return $cipherblob;
        }

        return $this->encrypt($this->decrypt($cipherblob));
    }

    private function decodeKey(string $value, string $name): string
    {
        if (str_starts_with($value, 'base64:')) {
            $decoded = base64_decode(substr($value, 7), true);
            if ($decoded === false) {
                throw new RuntimeException($name.' must contain valid base64.');
            }
            $value = $decoded;
        }

        if (strlen($value) !== 32) {
            throw new RuntimeException($name.' must decode to exactly 32 bytes for AES-256-GCM.');
        }

        return $value;
    }
}
