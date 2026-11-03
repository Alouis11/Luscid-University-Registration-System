<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("Location: index.html");
    exit;
}

$userID = $_SESSION["user_id"];

// Fetch User Info
$userStmt = $pdo->prepare("SELECT First_Name, Last_Name FROM User WHERE User_ID = :user_id");
$userStmt->execute([':user_id' => $userID]);
$user = $userStmt->fetch();

// Fetch Grades grouped/ordered by Semester
$sql = "SELECT 
            cs.CRN,
            cs.Section_No,
            c.Course_Name,
            c.Course_Credits,
            e.Grade,
            s.Semester_Name,
            s.Year,
            s.Start_Date
        FROM Enrollment e
        JOIN Course_Section cs ON e.CRN = cs.CRN
        JOIN Course c ON cs.Course_ID = c.Course_ID
        JOIN Semester s ON cs.Semester_ID = s.Semester_ID
        WHERE e.Student_ID = :student_id
        ORDER BY s.Start_Date DESC, c.Course_Name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([':student_id' => $userID]);
$grades = $stmt->fetchAll();

// Group grades by semester
$transcript = [];
foreach ($grades as $row) {
    $semKey = $row['Semester_Name'] . ' ' . $row['Year'];
    $transcript[$semKey][] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Unofficial Transcript</title>
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
                    <li><a href="account_settings.php">Account Settings</a></li>
                    <li><a href="grades.php" class="active">View Unofficial Transcript</a></li>
                    <li><a href="degree_audit.php">View Degree Audit</a></li>
                    <li><a href="courses.php">Register for Classes</a></li>
                </ul>
            </nav>
        </aside> -->

        <main class="main-content">
            <h1 class="welcome-header">Unofficial Transcript</h1>
            <p style="margin-bottom: 20px;"><strong>Student:</strong> <?= htmlspecialchars($user['First_Name'] . ' ' . $user['Last_Name']) ?></p>

            <?php if (empty($transcript)): ?>
                <p>No grade records available.</p>
            <?php else: ?>
                <?php foreach ($transcript as $semesterName => $courses): ?>
                    <h2 class="section-title"><?= htmlspecialchars($semesterName) ?></h2>
                    <table>
                        <thead>
                            <tr>
                                <th>CRN</th>
                                <th>Section No</th>
                                <th>Course Name</th>
                                <th>Credits</th>
                                <th>Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($courses as $course): ?>
                                <tr>
                                    <td><?= htmlspecialchars($course['CRN']) ?></td>
                                    <td><?= htmlspecialchars($course['Section_No']) ?></td>
                                    <td><?= htmlspecialchars($course['Course_Name']) ?></td>
                                    <td><?= htmlspecialchars($course['Course_Credits']) ?></td>
                                    <td><strong><?= htmlspecialchars($course['Grade'] ?? 'N/A') ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>