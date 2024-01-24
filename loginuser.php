<?php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';
use Firebase\JWT\JWT;

session_start();

if (isset($_SERVER["REQUEST_METHOD"])) {
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $request = json_decode(file_get_contents('php://input'), true);

        if (isset($request)) {
            if ($request['email'] && $request['password']) {
                $mysqli = require __DIR__ . "/database.php";

                $sql = sprintf( "SELECT * FROM users WHERE email = '%s'",
                    $mysqli->real_escape_string($request['email']));

                $result = $mysqli->query($sql);

                $user = $result->fetch_assoc(); // is grabbing the user like this unsafe?
            }
            else {
                $response['status_code'] = 400;

                if (!$request['email']) { $response['message'] = "Please enter an email."; }
                elseif (!$request['password']) { $response['message'] = "Please enter a password."; }

                echo json_encode($response);
                exit;
            }

            if ($user) {
                if (password_verify($request['password'], $user["password_hash"])) {
                    session_regenerate_id();

                    if (!isset($_SERVER['SECRET_KEY'])) {
                        $response['status_code'] = 500;
                        $response['message'] = "SECRET KEY ERROR";

                        echo json_encode($response);
                        exit;
                    }

                    $secretKey  = $_SERVER['SECRET_KEY'];
                    $issuedAt   = new DateTimeImmutable();
                    $expire     = $issuedAt->modify('+1 minutes')->getTimestamp();      // Add 60 seconds
                    $serverName = "releasesApp";

                    $data = [
                        'iat'  => $issuedAt->getTimestamp(),         // Issued at: time when the token was generated
                        'iss'  => $serverName,                       // Issuer
                        'nbf'  => $issuedAt->getTimestamp(),         // Not before
                        'exp'  => $expire,                           // Expire
                        'sub' => $user['id'],                        // User ID
                        'userName' => $user['username'],             // User name
                    ];

                    // Encode the array to a JWT string.
                    $response['auth_token'] = JWT::encode(
                        $data,
                        $secretKey,
                        'HS512'
                    );

                    header("Accept: application/json");
                    header('Content-Type: application/json; charset=utf-8');
                    header("Authorization: Bearer " . $response['auth_token']);

                    $response['status_code'] = 200;

                }
                else {
                    $response['status_code'] = 401;
                    $response['message'] = "Invalid login.";
                }

                echo json_encode($response);
                exit;

            }

            $response['status_code'] = 401;
            $response['message'] = "Account not found.";

            echo json_encode($response);
            exit;
        }

    }
    else {
        $response['status_code'] = 405;
        $response['message'] = "INVALID REQUEST METHOD.";

        echo json_encode($response);
        exit;
    }

}