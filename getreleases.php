<?php
declare(strict_types=1);

require "validate_jwt.php";

session_start();

if (isset($_SERVER["REQUEST_METHOD"])) {
    if ($_SERVER["REQUEST_METHOD"] === "GET") {
        // validate JWT
        if (!empty(getallheaders())) {
            $headers = getallheaders();

            $validation = validate_jwt($headers);

            if ($validation['result'] === true) {
                if (!empty($validation['userId'])) {
                    $mysqli = require __DIR__ . "/database.php";

                    if (isset($_GET['initState'])) {
                        if ($_GET['initState'] == "true") {
                            $num = 10;
                        }
                        else {
                            $num = PHP_INT_MAX;
                        }

                    } // maybe needs an else in the future?

                    $sql = "SELECT * FROM releases WHERE owner=? ORDER BY release_date DESC LIMIT ?";
                    $stmt = $mysqli->stmt_init();

                    try {
                        $stmt->prepare($sql);
                    }
                    catch (mysqli_sql_exception $e) {
                        echo json_encode($mysqli->error);
                        exit;
                    }

                    $stmt->bind_param("ss",
                        $validation['userId'],
                        $num
                    );

                    try {
                        $stmt->execute();

                        $result = $stmt->get_result();

                        $releases = $result->fetch_all(MYSQLI_ASSOC);

                        $response['status_code'] = 200;
                        $response['releases'] = $releases;

                        echo json_encode($response);

                    } catch (mysqli_sql_exception $e) {
                        echo json_encode($mysqli->error . " " . $mysqli->errno);
                        exit;
                    }

                }
                else {
                    $response['message'] = "USER NOT FOUND.";
                    $response['status_code'] = 404;

                    echo json_encode($response);
                    exit;
                }

            }
            else {
                $response['message'] = $validation['message'];
                $response['status_code'] = 401;

                echo json_encode($response);
                exit;
            }

        } // empty headers else maybe

    }
    else {
        $response['message'] = "INVALID REQUEST METHOD";
        $response['status_code'] = 405;

        echo json_encode($response);
        exit;
    }

}