<?php
require 'vendor/autoload.php';

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;

$secret = '!ChangeThisMercureHubJWTSecretKey!';
$configuration = Configuration::forSymmetricSigner(
    new Sha256(),
    InMemory::plainText($secret)
);

$token = $configuration->builder()
    ->withClaim('mercure', ['publish' => ['*']])
    ->getToken($configuration->signer(), $configuration->signingKey());

$jwt = $token->toString();

$ch = curl_init('http://127.0.0.1:3002/.well-known/mercure');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'topic' => 'chat_room_test',
    'data' => '{"message":"hello"}',
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $jwt
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "HTTP Code: " . $httpcode . "\n";
echo "Response: " . $response . "\n";
