<?php

    function encryptDataFunc($encryptData, $keyValue) {
        $key = md5($keyValue);
        $iv = "\x0\x0\x0\x0\x0\x0\x0\x0\x0\x0\x0\x0\x0\x0\x0\x0";
        // Encrypt
        $ciphertext = openssl_encrypt(json_encode($encryptData), 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        // Convert to Base64
        $base64Cipher = base64_encode($ciphertext);
        $hexCipher = bin2hex(base64_decode($base64Cipher));
        return $hexCipher;
    }

    function decryptDataFunc($decryptData, $keyValue) {
        $key = md5($keyValue);
        $iv = "\x0\x0\x0\x0\x0\x0\x0\x0\x0\x0\x0\x0\x0\x0\x0\x0";
        // Convert Hex back to Base64
        $encryptedBase64 = base64_encode(hex2bin($decryptData));

        $decrypted = openssl_decrypt(base64_decode($encryptedBase64), 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        
        return json_decode($decrypted);
    }
?>