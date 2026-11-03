<?php
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    require '../vendor/autoload.php';
    require_once '../db.php';

    $filename = "../.private/sensitive_strings.txt";
    $lines = null;
    if (file_exists($filename)) {
        $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }

    function generateAlphanumericCode($length = 6) {
        $bytes = random_bytes(ceil($length / 2));
        $code = substr(bin2hex($bytes), 0, $length);
        return strtoupper($code);
    }

    session_start();

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        header("Content-Type: application/json");

        // Initialize session variables
        $_SESSION["user_id"] = $_SESSION["user_id"] ?? null;
        $_SESSION["is_verified"] = $_SESSION["is_verified"] ?? false;

    
        if (isset($_POST["verif-email"])) {

            // Check if email is assigned to an account
            $stmt = $pdo->prepare("SELECT User_ID, Email FROM Login WHERE Email = ? LIMIT 1");
            $stmt->execute([$_POST["verif-email"]]);

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {  // account doesn't exist
                echo json_encode([
                    "success" => false, 
                    "message" => "This email address is not associated with an account. Please try re-entering."
                ]);
                exit;
            }

            // Success; Send verification code to email
            $gencode = generateAlphanumericCode();

            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = $lines[0];
                $mail->Password = $lines[1];
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('noreply@luscid.edu', 'Luscid University');
                $mail->addAddress($_POST["verif-email"]);

                $mail->isHTML(false);
                $mail->Subject = 'Luscid University Portal - Verification Code';
                $mail->Body = "Your 6-character code is: " . $gencode;
                $mail->send();
            } catch (Exception $e) {
                echo json_encode([
                    "success" => false, 
                    "message" => "An error has occurred when trying to send the email. Please try again later.",
                    "error" => $e,
                ]);
                exit;
            }

            $_SESSION["sent_code"] = $gencode;
            
            // Save matched User_ID & advance client screen
            $_SESSION["user_id"] = $row["User_ID"];
            echo json_encode(["success" => true]);
        }
        
        elseif (isset($_POST["verif-code"])) {

            // Ensure that this stage of the password reset process is allowed to be accessed
            if (!$_SESSION["user_id"]) {
                echo json_encode(["success" => false]);
                header("Location: error.html");
                exit;
            }

            // Make sure there is a valid sent code to check against first
            if (empty($_SESSION["sent_code"])) {
                echo json_encode(["success" => false]);
                header("Location: error.html");
                exit;
            }
            
            // Check if entered code matches sent code
            if ($_POST["verif-code"] != $_SESSION["sent_code"]) {
                echo json_encode([
                    "success" => false, 
                    "message" => "This code is incorrect or has expired. Please try again."
                ]);
                exit;
            }

            // Verify & advance client
            $_SESSION["is_verified"] = true;
            echo json_encode(["success" => true]);
        }

        elseif (!empty($_POST["new-password"])) {

            // Ensure that this stage of the password reset process is allowed to be accessed
            if (!$_SESSION["is_verified"]) {
                echo json_encode(["success" => false]);
                header("Location: error.html");
                exit;
            }

            // Validate password
            if (strlen($_POST["new-password"]) < 8) {
                echo json_encode([
                    "success" => false,
                    "message" => "Password must be at least 8 characters long."
                ]);
                exit;
            }
            
            if (!preg_match("/\d/", $_POST["new-password"])) {
                echo json_encode([
                    "success" => false,
                    "message" => "Password must contain at least 1 number."
                ]);
                exit;
            }

            if (!preg_match("/[^a-zA-Z0-9]/", $_POST["new-password"])) {
                echo json_encode([
                    "success" => false,
                    "message" => "Password must contain at least 1 symbol (non-alphanumeric character)."
                ]);
                exit;
            }
            
            // Check against old password
            $stmt = $pdo->prepare("SELECT Password FROM Login WHERE User_ID = ? LIMIT 1");
            $stmt->execute([$_SESSION["user_id"]]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($_POST["new-password"], $row["Password"])) {
                echo json_encode([
                    "success" => false,
                    "message" => "New password must not match old password."
                ]);
                exit;
            }

            // Unlock Account
            $stmt = $pdo->prepare("UPDATE Login SET Is_Locked = FALSE WHERE User_ID = ?");
            $stmt->execute([$_SESSION["user_id"]]);
            if ($stmt->errorCode() !== '00000') {
                echo json_encode([
                    "success" => false,
                    "message" => $loginStmt->errorInfo
                ]);
                exit;
            }

            // Reset Login_Attempts
            $stmt = $pdo->prepare("UPDATE Login SET Login_Attempts = 0 WHERE User_ID = ?");
            $stmt->execute([$_SESSION["user_id"]]);
            
            // Update password in Login table
            $newpass_hash = password_hash($_POST["new-password"], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE Login SET Password = ? WHERE User_ID = ?");
            $stmt->execute([$newpass_hash, $_SESSION["user_id"]]);

            if ($stmt->rowCount() < 1) {
                echo json_encode([
                    "success" => false,
                    "message" => "Password was not able to be updated. Please try again later."
                ]);
                exit;
            }

            // Finish & Notify Client
            session_destroy();
            echo json_encode(["success" => true]);
        }
        exit;
    }
?>


<!DOCTYPE html>

<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>Password Reset | Luscid University</title>

        <link rel="stylesheet" href="../styles.css">
    </head>

    <body>
        <!-- Global Banner -->
        <header class="banner">
            <!-- Left Side (Logo) -->
            <div class="logo">Luscid University Portal</div>

            <!-- Right Side -->
            <nav class="menu">
                <a href="register.php">Register</a>
                <a href="login.php">Log In</a>
            </nav>
        </header>

        <div class="modal screen-center">
            <h1>Password Reset</h1>

            <!-- Step 1 - Confirm Email -->
            <div id="flow-email">
                <p>Please enter your account's email address.</p>

                <div id="email-error" class="notif-red" hidden></div>

                <form id="email-form">
                    <label for="email">Email</label>
                    <input type="text" id="email" placeholder="Enter email" required>

                    <button type="submit" for="email-form">Confirm</button>
                </form>
            </div>

            <!-- Step 2 - Verify Code Sent to Email -->
            <div id="flow-verify" hidden>
                <p>Please enter the 6-digit code that was sent to your email.</p>

                <div id="verify-error" class="notif-red" hidden></div>

                <form id="verify-form">
                    <label for="verify">Verification Code</label>
                    <input type="text" id="verify" placeholder="6-digit code" required>

                    <button type="submit" for="verify-form">Verify</button>
                </form>
            </div>

            <!-- Step 3 - Enter New Password -->
            <div id="flow-password" hidden>
                <p>Please enter a new password for your account.</p>

                <div id="password-error" class="notif-red" hidden></div>

                <form id="password-form">
                    <label for="password">New Password</label>
                    <input type="password" id="password" placeholder="Enter new password" required>

                    <button type="submit" for="password-form">Change Password</button>
                </form>
            </div>

            <!-- Step 4 - Redirect back to Login -->
            <div id="flow-finished" hidden>
                <p>Password was reset successfully! Please re-login to your account.</p>
                
                <a href="login.php">
                    <button>Back to Login</button>
                </a>
            </div>
        </div>

        <script type="module">
            import {xhrPostRequest} from "../index.js";

            function submitEmail(event) {
                event.preventDefault();
                xhrPostRequest("reset_password.php",
                    {"verif-email": document.getElementById("email").value}, 
                    (responseObj) => {
                        if (responseObj.success) {
                            // next step
                            document.getElementById("flow-email").hidden = true;
                            document.getElementById("flow-verify").hidden = false;
                        } 
                        else if ("message" in responseObj) {
                            var errNotif = document.getElementById("email-error")
                            errNotif.hidden = false;
                            errNotif.textContent = responseObj.message;
                        }
                    }
                );
            }

            function submitCode(event) {
                event.preventDefault();
                xhrPostRequest("reset_password.php",
                    {"verif-code": document.getElementById("verify").value}, 
                    (responseObj) => {
                        if (responseObj.success) {
                            document.getElementById("flow-verify").hidden = true;
                            document.getElementById("flow-password").hidden = false;
                        } 
                        else if ("message" in responseObj) {
                            var errNotif = document.getElementById("verify-error")
                            errNotif.hidden = false;
                            errNotif.textContent = responseObj.message;
                        }
                    }
                );
            }

            function submitNewPassword(event) {
                event.preventDefault();
                xhrPostRequest("reset_password.php",
                    {"new-password": document.getElementById("password").value},
                    (responseObj) => {
                        if (responseObj.success) {
                            document.getElementById("flow-password").hidden = true;
                            document.getElementById("flow-finished").hidden = false;
                        } 
                        else if ("message" in responseObj) {
                            var errNotif = document.getElementById("password-error")
                            errNotif.hidden = false;
                            errNotif.textContent = responseObj.message;
                        }
                    }
                );
            }

            document.getElementById("email-form").addEventListener("submit", (event) => submitEmail(event));
            document.getElementById("verify-form").addEventListener("submit", (event) => submitCode(event));
            document.getElementById("password-form").addEventListener("submit", (event) => submitNewPassword(event));
        </script>
    </body>
</html>