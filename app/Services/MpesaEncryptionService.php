<?php

namespace App\Services;

use RuntimeException;

class MpesaEncryptionService
{
    private string $key;

    public function __construct()
    {
        $raw = config('services.mpesa.encryption_key');
        if (empty($raw)) {
            throw new RuntimeException('MPESA_ENCRYPTION_KEY is not configured.');
        }
        // Support base64: prefix or raw 32-byte hex
        if (str_starts_with($raw, 'base64:')) {
            $this->key = base64_decode(substr($raw, 7));
        } else {
            $this->key = $raw;
        }
        if (strlen($this->key) < 16) {
            throw new RuntimeException('MPESA_ENCRYPTION_KEY must be at least 16 bytes.');
        }
    }

    /** Encrypt plaintext → base64(iv + tag + ciphertext) */
    public function encrypt(string $plaintext): string
    {
        $iv = random_bytes(12); // GCM standard IV
        $tag = '';
        $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $this->key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);
        if ($ciphertext === false) {
            throw new RuntimeException('M-Pesa encryption failed.');
        }

        return base64_encode($iv.$tag.$ciphertext);
    }

    /** Decrypt base64(iv + tag + ciphertext) → plaintext */
    public function decrypt(string $cipherblob): string
    {
        $raw = base64_decode($cipherblob, true);
        if ($raw === false || strlen($raw) < 29) { // 12 IV + 16 tag + 1 min ciphertext
            throw new RuntimeException('M-Pesa cipherblob is malformed.');
        }
        $iv = substr($raw, 0, 12);
        $tag = substr($raw, 12, 16);
        $ciphertext = substr($raw, 28);
        $plain = openssl_decrypt($ciphertext, 'aes-256-gcm', $this->key, OPENSSL_RAW_DATA, $iv, $tag);
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
}
