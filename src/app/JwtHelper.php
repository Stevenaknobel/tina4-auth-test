<?php
/*
namespace Tina4;

use Nowakowskir\JWT\JWT;
use Nowakowskir\JWT\TokenEncoded;
use Nowakowskir\JWT\TokenDecoded;

function createToken(array $payload): string
{
    // Read the private key to sign the JWT
    $privateKey = file_get_contents(__DIR__ . "/../../keys/private.pem");

    // Header: Specify the signing algorithm (RS256 in this case)
    $header = [
        "alg" => "RS256", // RSA signing algorithm
        "typ" => "JWT"
    ];

    // Create a JWT instance with the header and payload
    $jwt = new JWT(
        $header,            // Header (algorithm and type)
        $payload,           // Payload (the claims to include, e.g., user_id, username)
        $privateKey,        // Private key used for signing
        JWT::ALGORITHM_RS256 // The signing algorithm
    );

    // Encode the JWT
    return $jwt->encode();
}

function decodeToken(string $token): array
{
    // Read the public key for decoding and verifying the JWT
    $publicKey = file_get_contents(__DIR__ . '/../../keys/public.pem');

    // Create a JWT instance from the encoded token
    $jwt = new JWT(
        new TokenEncoded($token),
        $publicKey,
        JWT::ALGORITHM_RS256
    );

    // Decode the token and get the payload (the claims)
    return $jwt->decode()->getPayload();
}*/