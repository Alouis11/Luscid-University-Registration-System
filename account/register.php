<?php
    require_once '../db.php';

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        header("Content-Type: application/json");

        $userStmt =$pdo->prepare("INSERT INTO User (First_Name, Middle_Name, Last_Name, City, State, Country, Street_No, Street_Name, Zip_Code, User_Type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $loginStmt =$pdo->prepare("INSERT INTO Login (User_ID, Email, Password) VALUES (?, ?, ?)");

        $firstName = $_POST["first-name"];
        $middleName = $_POST["middle-name"];
        $lastName = $_POST["last-name"];

        $city = $_POST["city"];
        $state = $_POST["state"];
        $country = $_POST["country"];

        $streetNo = $_POST["street-no"];
        $streetName = $_POST["street-name"];
        $zipCode = $_POST["zip-code"];

        $userType = 'Student';

        $email = $_POST["email"];
        $password = $_POST["password"];
        
        // Validate password
        if (strlen($_POST["password"]) < 8) {
            echo json_encode([
                "success" => false,
                "message" => "Password must be at least 8 characters long."
            ]);
            exit;
        }
        
        if (!preg_match("/\d/", $_POST["password"])) {
            echo json_encode([
                "success" => false,
                "message" => "Password must contain at least 1 number."
            ]);
            exit;
        }

        if (!preg_match("/[^a-zA-Z0-9]/", $_POST["password"])) {
            echo json_encode([
                "success" => false,
                "message" => "Password must contain at least 1 symbol (non-alphanumeric character)."
            ]);
            exit;
        }

        // Insert into User
        $userStmt->execute([
            $firstName, $middleName, $lastName, 
            $city, $state, $country, 
            $streetNo, $streetName, $zipCode, 
            $userType
        ]);
        
        if ($userStmt->errorCode() !== '00000') {
            echo json_encode([
                "success" => false,
                "message" => $userStmt->errorInfo
            ]);
            exit;
        }
        if ($userStmt->rowCount() < 1) {  // No affected rows
            echo json_encode([
                "success" => false,
                "message" => "Unable to create account. Please try again later."
            ]);
            exit;
        }

        $uid = $pdo->lastInsertId();

        // Insert into Login
        $loginStmt->execute([$uid, strtolower($email), password_hash($password, PASSWORD_DEFAULT)]);

        if ($loginStmt->errorCode() !== '00000') {
            echo json_encode([
                "success" => false,
                "message" => $loginStmt->errorInfo
            ]);
            exit;
        }
        if ($loginStmt->rowCount() < 1) {  // No affected rows
            echo json_encode([
                "success" => false,
                "message" => "Unable to create account. Please try again later."
            ]);
            exit;
        }

        echo json_encode(["success" => true]);
        exit;
    }
?>


<!DOCTYPE html>

<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>Account Registration | Luscid University</title>

        <link rel="stylesheet" href="../styles.css">
    </head>

    <body>
        <header class="banner">
            <div class="logo">Luscid University Portal</div>
            <nav class="menu">
                <a href="./login.php">Log In</a>
            </nav>
        </header>

        <div class="modal screen-center" style="margin-top: 300px;">
            <h1>Account Registration</h1>
            <div id="register-error" class="notif-red" hidden></div>

            <form id="register-form">
                <h2>Personal Info</h2>

                <label>First Name
                    <input type="text" id="fname" placeholder="Enter First Name" required>
                </label>
                <label>Middle Name
                    <input type="text" id="mname" placeholder="Enter Middle Name" required>
                </label>
                <label>Last Name
                    <input type="text" id="lname" placeholder="Enter Last Name" required>
                </label>

                <br>
                <h2>Address</h2>

                <label>City
                    <input type="text" id="city" placeholder="Enter City" required>
                </label>
                <label>State
                    <input type="text" id="state" placeholder="Enter State" required>
                </label>
                <label>Country
                    <input type="text" id="country" placeholder="Enter Country" required>
                </label>

                <label>Street No.
                    <input type="text" id="street-no" placeholder="Enter House/Apartment No." required>
                </label>
                <label>Street Name
                    <input type="text" id="street-name" placeholder="Enter Street Name" required>
                </label>
                <label>Zip Code
                    <input type="text" id="zip-code" placeholder="Enter Zip/Postal Code" required>
                </label>
                
                <br>
                <h2>Account</h2>

                <label>Email Address
                    <input type="email" id="email" placeholder="example@mail.com" required>
                </label>
                <label>Password
                    <input type="password" id="password" placeholder="Enter new password" required>
                </label>
                <label>Confirm Password
                    <input type="password" id="password2" placeholder="Confirm new password" required>
                </label>

                <button type="submit" for="register-form">Register</button>
            </form>

            <div id="register-success" hidden>
                <p>Account successfully created!</p>
                <a href="login.php">
                    <button>Login</button>
                </a>
            </div>
        </div>

        <script type="module">
            import {xhrPostRequest} from "../index.js";

            function registerForAccount(event) {
                event.preventDefault();

                // Client-Side Form Verification
                let pass1 = document.getElementById("password").value;
                let pass2 = document.getElementById("password2").value;
                if (pass1 !== pass2) {
                    var errNotif = document.getElementById("register-error");
                    errNotif.hidden = false;
                    errNotif.textContent = "Passwords do not match. Please re-enter.";
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                    return;
                }

                // Submit request to Server
                xhrPostRequest(
                    "register.php",
                    {
                        "first-name": document.getElementById("fname").value,
                        "middle-name": document.getElementById("mname").value,
                        "last-name": document.getElementById("lname").value,
                        "city": document.getElementById("city").value,
                        "state": document.getElementById("state").value,
                        "country": document.getElementById("country").value,
                        "street-no": document.getElementById("street-no").value,
                        "street-name": document.getElementById("street-name").value,
                        "zip-code": document.getElementById("zip-code").value,
                        "email": document.getElementById("email").value,
                        "password": document.getElementById("password").value
                    }, 
                    (responseObj) => {
                        if (responseObj.success) {
                            document.getElementById("register-form").hidden = true;

                            document.querySelector(".modal.screen-center").style = "";
                            document.getElementById("register-success").hidden = false;
                        } 
                        else if ("message" in responseObj) {
                            var errNotif = document.getElementById("register-error");
                            errNotif.hidden = false;
                            errNotif.textContent = responseObj.message;
                            window.scrollTo({
                                top: 0,
                                behavior: 'smooth'
                            });
                        }
                    }
                );
            }

            document.getElementById("register-form").addEventListener("submit", (event) => registerForAccount(event));
        </script>
    </body>
</html>