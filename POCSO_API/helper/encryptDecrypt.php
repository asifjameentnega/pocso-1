<?php

function encrypt($data) {
    // Generate a random IV (16 bytes)
    $iv = openssl_random_pseudo_bytes(16);

    // Encrypt the data
    $encrypted = openssl_encrypt(
        json_encode($data),                      // Data to encrypt
        'aes-256-cbc',                           // Encryption method
        '4bbf9c503a723aa4123965d9ee38b1bf',       // Encryption key
        0,                                           // Options
        $iv                                               // Initialization Vector
    );

    // Combine encrypted data with IV, separated by '::'
    return base64_encode($encrypted . '::' . bin2hex($iv));
}

function decrypt($data) {
    if (empty($data)) {
        return null;  // Or handle the error appropriately
    }
    if (!is_string($data)) {
        return null;  // Or handle the error appropriately
    }
    // Split the encrypted data and IV
    $decodedData = base64_decode($data);
    $parts = explode('::', $decodedData);

    // Check if the expected parts are present
    if (count($parts) != 2) {
        return null;  // Or handle the error, e.g., log it
    }

    $encrypted = $parts[0];
    $ivHex = $parts[1];

    // Check if the IV is valid and non-empty
    if (empty($ivHex)) {
        return null;  // Handle error
    }

    // Decrypt the data
    $iv = hex2bin($ivHex);
    if ($iv === false) {
        return null;  // Handle invalid IV
    }

    $decrypted = openssl_decrypt(
        $encrypted,                                            // Data to decrypt
        'aes-256-cbc',                                  // Encryption method
        '4bbf9c503a723aa4123965d9ee38b1bf',              // Encryption key
        0,                                                  // Options
        $iv                                                      // Initialization Vector
    );

    // If decryption fails, return null or handle the error appropriately
    if ($decrypted === false) {
        return null; // Or handle it with an error message or logging
    }

    // Attempt to decode the decrypted data as JSON
    $decodedData = json_decode($decrypted, true);

    // Check if json_decode failed
    if ($decodedData === null && json_last_error() !== JSON_ERROR_NONE) {
        return null; // Or handle the error, maybe log the error message
    }
    
    return $decodedData;  // Return the decoded JSON (array or object)
}

