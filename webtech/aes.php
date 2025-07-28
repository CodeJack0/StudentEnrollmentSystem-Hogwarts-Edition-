<?php
// aes.php

// Secret encryption key (must be 32 bytes for AES-256)
define('ENCRYPTION_KEY', 'mySecretEncryptionKey123456789012'); // Change this to a secure key

/**
 * Encrypts plaintext using AES-256-CBC with a random IV.
 *
 * @param string $plaintext The text to encrypt.
 * @param string $key Optional. Custom encryption key. Defaults to ENCRYPTION_KEY.
 * @return string Base64-encoded string containing IV + encrypted data.
 */
function aes_encrypt($plaintext, $key = ENCRYPTION_KEY) {
    $cipher = "AES-256-CBC";
    $iv = openssl_random_pseudo_bytes(16); // 16 bytes = 128-bit IV
    $encrypted = openssl_encrypt($plaintext, $cipher, $key, OPENSSL_RAW_DATA, $iv);
    return base64_encode($iv . $encrypted); // Store IV with encrypted data
}

/**
 * Decrypts ciphertext encrypted with aes_encrypt().
 *
 * @param string $ciphertext The base64-encoded encrypted string.
 * @param string $key Optional. Custom decryption key. Defaults to ENCRYPTION_KEY.
 * @return string|false Decrypted plaintext or false on failure.
 */
function aes_decrypt($ciphertext, $key = ENCRYPTION_KEY) {
    $cipher = "AES-256-CBC";
    $ciphertext = base64_decode($ciphertext);

    if ($ciphertext === false || strlen($ciphertext) <= 16) {
        return false; // Invalid or corrupted data
    }

    $iv = substr($ciphertext, 0, 16);              // Extract IV
    $encrypted = substr($ciphertext, 16);          // Extract ciphertext
    return openssl_decrypt($encrypted, $cipher, $key, OPENSSL_RAW_DATA, $iv);
}
