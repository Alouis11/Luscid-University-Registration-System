<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("Location: index.html");
    exit;
}

$userID = $_SESSION["user_id"];
$message = '';
$error = '';

// Check if Phone_No column exists in User table
$hasPhoneCol = false;
$colCheck = $pdo->query("SHOW COLUMNS FROM User LIKE 'Phone_No'");
if ($colCheck && $colCheck->rowCount() > 0) {
    $hasPhoneCol = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName  = trim($_POST['first_name'] ?? '');
    $middleName = trim($_POST['middle_name'] ?? '');
    $lastName   = trim($_POST['last_name'] ?? '');
    $streetNo   = trim($_POST['street_no'] ?? '');
    $streetName = trim($_POST['street_name'] ?? '');
    $city       = trim($_POST['city'] ?? '');
    $state      = trim($_POST['state'] ?? '');
    $country    = trim($_POST['country'] ?? '');
    $zipCode    = trim($_POST['zip_code'] ?? '');
    $phoneNo    = trim($_POST['phone_no'] ?? '');

    if (empty($firstName) || empty($lastName)) {
        $error = "First and Last Name are required.";
    } else {
        try {
            if ($hasPhoneCol) {
                $sql = "UPDATE User SET 
                            First_Name = :first_name, Middle_Name = :middle_name, Last_Name = :last_name,
                            Street_No = :street_no, Street_Name = :street_name, City = :city,
                            State = :state, Country = :country, Zip_Code = :zip_code, Phone_No = :phone_no
                        WHERE User_ID = :user_id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':first_name' => $firstName, ':middle_name' => $middleName, ':last_name' => $lastName,
                    ':street_no' => $streetNo, ':street_name' => $streetName, ':city' => $city,
                    ':state' => $state, ':country' => $country, ':zip_code' => $zipCode,
                    ':phone_no' => $phoneNo, ':user_id' => $userID
                ]);
            } else {
                $sql = "UPDATE User SET 
                            First_Name = :first_name, Middle_Name = :middle_name, Last_Name = :last_name,
                            Street_No = :street_no, Street_Name = :street_name, City = :city,
                            State = :state, Country = :country, Zip_Code = :zip_code
                        WHERE User_ID = :user_id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':first_name' => $firstName, ':middle_name' => $middleName, ':last_name' => $lastName,
                    ':street_no' => $streetNo, ':street_name' => $streetName, ':city' => $city,
                    ':state' => $state, ':country' => $country, ':zip_code' => $zipCode,
                    ':user_id' => $userID
                ]);
            }
            $message = "Account settings updated successfully.";
        } catch (PDOException $e) {
            $error = "Failed to update settings: " . $e->getMessage();
        }
    }
}

// Fetch current user details
$stmt = $pdo->prepare("SELECT * FROM User WHERE User_ID = :user_id");
$stmt->execute([':user_id' => $userID]);
$userData = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Account Settings</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
    <header class="banner">
        <div class="logo">Luscid University Portal</div>
        <nav class="menu">
            <a href="redirect.php">Dashboard</a>
            <a href="../account/logout.php">Log Out</a>
        </nav>
    </header>
    <div class="dashboard-container">
        <!-- <aside class="sidebar">
            <h2>Student Menu</h2>
            <nav>
                <ul>
                    <li><a href="redirect.php">Dashboard</a></li>
                    <li><a href="account_settings.php" class="active">Account Settings</a></li>
                    <li><a href="grades.php">View Unofficial Transcript</a></li>
                    <li><a href="degree_audit.php">View Degree Audit</a></li>
                    <li><a href="courses.php">Register for Classes</a></li>
                </ul>
            </nav>
        </aside> -->

        <main class="main-content">
            <h1 class="welcome-header">Account Settings</h1>

            <?php if ($message): ?>
                <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="form-card">
                <form method="POST" action="account_settings.php">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($userData['First_Name'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="middle_name">Middle Name</label>
                            <input type="text" id="middle_name" name="middle_name" value="<?= htmlspecialchars($userData['Middle_Name'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($userData['Last_Name'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="phone_no">Phone Number</label>
                            <input type="text" id="phone_no" name="phone_no" value="<?= htmlspecialchars($userData['Phone_No'] ?? '') ?>" placeholder="N/A">
                        </div>

                        <div class="form-group">
                            <label for="street_no">Street No</label>
                            <input type="text" id="street_no" name="street_no" value="<?= htmlspecialchars($userData['Street_No'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="street_name">Street Name</label>
                            <input type="text" id="street_name" name="street_name" value="<?= htmlspecialchars($userData['Street_Name'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="city">City</label>
                            <input type="text" id="city" name="city" value="<?= htmlspecialchars($userData['City'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="state">State</label>
                            <input type="text" id="state" name="state" value="<?= htmlspecialchars($userData['State'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="country">Country</label>
                            <input type="text" id="country" name="country" value="<?= htmlspecialchars($userData['Country'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="zip_code">Zip Code</label>
                            <input type="text" id="zip_code" name="zip_code" value="<?= htmlspecialchars($userData['Zip_Code'] ?? '') ?>">
                        </div>
                    </div>
                    <button type="submit" class="btn-submit">Save Changes</button>
                </form>
            </div>
        </main>
    </div>
</body>
</html>