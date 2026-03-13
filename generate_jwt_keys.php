<?php
$dir = __DIR__ . '/config/jwt';
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}
$passphrase = '2cc193c58be384fa3a44f4ce8c2d06f0ce55a513601c0b466d8f319581e03516'; // From .env
$key = openssl_pkey_new([
    'private_key_bits' => 4096,
    'private_key_type' => OPENSSL_KEYTYPE_RSA,
]);
openssl_pkey_export($key, $privateKey, $passphrase);
file_put_contents($dir . '/private.pem', $privateKey);
$publicKey = openssl_pkey_get_details($key)['key'];
file_put_contents($dir . '/public.pem', $publicKey);
echo "Keys generated successfully.\n";
