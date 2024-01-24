<?php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';
require "validate_jwt.php";
use DanielZ\MusicCodes\Isrc;

Isrc::treatZeroIdsAsValid(true); // allow 0 id ISRCs

session_start();

if (isset($_SERVER["REQUEST_METHOD"])) {
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        // validate JWT
        if (!empty(getallheaders())) {
            $headers = getallheaders();

            $validation = validate_jwt($headers);

            if(isset($validation['result'])) {
                if ($validation['result'] === true) {
                    // input validations
                    if (empty($_POST["track_name"])) {
                        $response['message'] = "Track name is required.";
                        $response['success'] = false;

                        echo json_encode($response);
                        exit;
                    }

                    if (empty($_POST["artist"])) {
                        $response['message'] = "Main artist is required.";
                        $response['success'] = false;

                        echo json_encode($response);
                        exit;
                    }

                    if (empty($_POST["isrc"])) {
                        $response['message'] = "Please enter the recording's ISRC.";
                        $response['success'] = false;

                        echo json_encode($response);
                        exit;
                    }

                    if (strlen($_POST["isrc"]) != 12) {
                        $response['message'] = "ISRC should be 12 characters long.";
                        $response['success'] = false;

                        echo json_encode($response);
                        exit;
                    }

                    $isrc = new Isrc($_POST["isrc"]); // assume not empty, already checked above

                    if (!$isrc->isValid()) {
                        $response['message'] = "ISRC is not valid.";
                        $response['success'] = false;

                        echo json_encode($response);
                        exit;
                    }

                    if (empty($_POST["release_date"])) {
                        $response['message'] = "Please select a release date.";
                        $response['success'] = false;

                        echo json_encode($response);
                        exit;
                    }

                    // add to database
                    $mysqli = require __DIR__ . "/database.php";

                    $sql = "INSERT INTO releases (owner, track_name, artist,
                      label, isrc, upc,
                      release_date, button_states, progress) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

                    $stmt = $mysqli->stmt_init();

                    try {
                        $stmt->prepare($sql);
                    }
                    catch (mysqli_sql_exception $e) {
                        $response['message'] = "SQL error: " . $mysqli->error;
                        $response['success'] = false;

                        echo json_encode($response);
                        exit;
                    }

                    $stmt->bind_param("ssssssssd",
                        $_POST['owner'],
                        $_POST["track_name"],
                        $_POST["artist"],
                        $_POST["label"],
                        $_POST["isrc"],
                        $_POST["upc"],
                        $_POST["release_date"],
                        $_POST["button_states"],
                        $_POST["release_progress"]
                    );

                    try {
                        $stmt->execute();

                        $response['success'] = true;

                        echo json_encode($response);
                        exit;
                    }
                    catch (mysqli_sql_exception $e) {
                        if ($mysqli->errno === 1062) {
                            $response['message'] = "Release already exists (duplicate ISRC).";
                            $response['success'] = false;

                            echo json_encode($response);
                            exit;
                        }

                        $response['message'] = $mysqli->error . " " . $mysqli->errno;
                        $response['success'] = false;

                        echo json_encode($response);
                        exit;
                    }

                }
                else {
                    $response['message'] = $validation['message'];
                    $response['success'] = false;

                    echo json_encode($response);
                    exit;
                }

            } // maybe add else if needed later

        } // maybe add else if needed later

    }
    else {
        $response['success'] = false;
        $response['message'] = "INVALID REQUEST METHOD.";

        echo json_encode($response);
        exit;
    }

}