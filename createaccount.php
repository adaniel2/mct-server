<?php
declare(strict_types=1);

if(isset($_SERVER["REQUEST_METHOD"])) {
    if ($_SERVER["REQUEST_METHOD"] === "POST") { // do any of the validations need isset?
        if (!filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
            $response['message'] = "Valid email required.";
            $response['success'] = false;

            echo json_encode($response);
            exit;
        }

        if (empty($_POST["username"])) {
            $response['message'] = "Artist name is required.";
            $response['success'] = false;

            echo json_encode($response);
            exit;
        }

        if (strlen($_POST["password"]) < 8) {
            $response['message'] = "Password must be at least 8 characters.";
            $response['success'] = false;

            echo json_encode($response);
            exit;
        }

        if (!preg_match("/[a-z]/i", $_POST["password"])) {
            $response['message'] = "Must contain at least one letter.";
            $response['success'] = false;

            echo json_encode($response);
            exit;
        }

        if (!preg_match("/[0-9]/", $_POST["password"])) {
            $response['message'] = "Must contain at least one number.";
            $response['success'] = false;

            echo json_encode($response);
            exit;
        }

        if ($_POST["password"] !== $_POST["password_confirmation"]) {
            $response['message'] = "Passwords must match.";
            $response['success'] = false;

            echo json_encode($response);
            exit;
        }

        $password_hash = password_hash($_POST["password"], PASSWORD_DEFAULT);

        $mysqli = require __DIR__ . "/database.php";

        $sql = "INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)";
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

        $stmt->bind_param("sss",
            $_POST["username"],
            $_POST["email"],
            $password_hash);

        try {
            $stmt->execute();

            $response['success'] = true;

            echo json_encode($response);
            exit;
        } catch (mysqli_sql_exception $e) {
            if ($mysqli->errno === 1062) {
                $response['message'] = "Email already in use.";
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
        $response['success'] = false;
        $response['message'] = "INVALID REQUEST METHOD.";

        echo json_encode($response);
        exit;
    }

}