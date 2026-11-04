<?php
session_start();
require_once '../db.php';

// Check if student is logged in
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("Location: index.html");
    exit;
}

$userID = $_SESSION["user_id"];

// Fetch all departments
$stmt = $pdo->query("
    SELECT *
    FROM Department
    ORDER BY Dept_Name
");

$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
