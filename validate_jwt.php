<?php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function validate_jwt($headers): array {
    if (isset($headers['Authorization'])) {
        // extract JWT from header
        $jwt = $headers['Authorization'];
        $jwt = str_replace("Bearer ", "", $jwt);

        try {
            // signature exists check (i think JWT library already does this tho)
            $jwtParts = explode(".", $jwt);

            if(!array_key_exists(2, $jwtParts)) {
                return array('result' => false, 'message' => "SIGNATURE MISSING.");
            }

            if (!isset($_SERVER['SECRET_KEY'])) {
                return array('result' => false, 'message' => "SECRET KEY ERROR.");
            }

            // decode JWT; verifies correct typ and alg then returns payload
            $payload = JWT::decode($jwt, new Key($_SERVER['SECRET_KEY'], 'HS512'));

            $payload_array = (array) $payload;

            // verify signature
            $valid_jwt = JWT::encode(
                $payload_array,
                $_SERVER['SECRET_KEY'],
                'HS512'
            );

            if ($valid_jwt === $jwt) {
                return array('result' => true, 'userId' => $payload_array['sub']);
            }

            return array('result' => false, 'message' => "INVALID TOKEN.");
        }
        catch (UnexpectedValueException $e) {
            return array('result' => false, 'message' => $e->getMessage());
        }

    }

    return array('result' => false, 'message' => "AUTHORIZATION ERROR.");

}