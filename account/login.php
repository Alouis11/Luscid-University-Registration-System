<?php
    require_once '../db.php';
    
    const MAX_LOGIN_ATTEMPTS = 3;

    session_start();

    // LOGIN REQUEST HANDLING //

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        header("Content-Type: application/json");
        
        // Attempt Login with given parameters
        $email = $_POST["user-email"];
        $password = $_POST["user-password"];

        if (empty($_POST["user-email"]) || empty($_POST["user-password"])) {
            http_response_code(400);
            echo json_encode([
                "success" => false, 
                "message" => "Email and password are required."
            ]);
            exit;
        }

        $stmt = $pdo->prepare("SELECT User_ID, Password, Is_Locked, Login_Attempts FROM Login WHERE Email = ? LIMIT 1;");
        $stmt->execute([$email]);

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($result) < 1) {  // account doesn't exist
            echo json_encode([
                "success" => false, 
                "message" => "Invalid Email or Password." 
            ]);
            exit;
        }

        $account = $result[0];

        if ($account["Is_Locked"]) {
            echo json_encode([
                "success" => false,
                "message" => "This account is locked.",
                "accountLocked" => true
            ]);
            exit;
        }

        if (!password_verify($password, $account["Password"])) {
            // Increment Login_Attempts
            $incr_stmt = $pdo->prepare("UPDATE Login SET Login_Attempts = Login_Attempts + 1 WHERE User_ID = ?");
            $incr_stmt->execute([$account["User_ID"]]);

            // Lock account if attempts reach a threshold
            if (($account["Login_Attempts"] + 1) >= MAX_LOGIN_ATTEMPTS) {
                $lock_stmt = $pdo->prepare("UPDATE Login SET Is_Locked = TRUE WHERE User_ID = ?");
                $lock_stmt->execute([$account["User_ID"]]);
            }
        
            echo json_encode([
                "success" => false,
                "message" => "Invalid Email or Password."
            ]);
            exit;
        }

        $_SESSION["user_id"] = $account["User_ID"];
        $_SESSION["logged_in"] = true;

        // Reset login attempts on successful login
        $reset_stmt = $pdo->prepare("UPDATE Login SET Login_Attempts = 0 WHERE User_ID = ?");
        $reset_stmt->execute([$account["User_ID"]]);

        echo json_encode(["success" => true]);
        exit;
    }
?>


<!DOCTYPE html>

<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>College Portal - Login</title>

        <link rel="stylesheet" href="../styles.css">
    </head>

    <body>
        <header class="banner">
            <div class="logo">Luscid University Portal</div>
            <nav class="menu">
                <a href="./register.php">Register</a>
            </nav>
        </header>

        <div class="modal screen-center">
            <h1>Luscid University Portal</h1>
            <p>Welcome back. Please sign in to continue.</p>

            <div id="login-error" class="notif-red" hidden></div>

            <form id="login-form">
                <!-- Email -->
                <label for="email">Email</label>
                <input type="text" id="email" placeholder="Enter email" required>
                <!-- Password -->
                <label for="password">Password</label>
                <input type="password" id="password" placeholder="Enter password" required>

                <a href="reset_password.php" style="font-size:0.75em;">Forgot Password?</a>

                <!-- Login -->
                <button type="submit" for="login-form">Login</button>
            </form>
        </div>
    </body>

    <script type="module">
        import {xhrPostRequest} from "../index.js";

        // Form Submit
        function submitForm(event) {
            event.preventDefault();
            xhrPostRequest("login.php",
                {
                    "user-email": document.getElementById("email").value,
                    "user-password": document.getElementById("password").value
                },
                (responseObj) => {
                    if (responseObj.success) {
                        window.location.href = "../dashboard/student.php";
                    } else {
                        var errNotif = document.getElementById("login-error")
                        errNotif.hidden = false;
                        errNotif.textContent = responseObj.message;

                        if ("accountLocked" in responseObj)
                            errNotif.innerHTML += ` To perform a password reset, <a href="reset_password.php">click here</a>.`;
                    }
                }
            );
        }

        document.getElementById("login-form").addEventListener("submit", (event) => submitForm(event));
    </script>
</html>
