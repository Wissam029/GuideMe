<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once 'config/db.php';

$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

if ($userId <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "User not logged in"
    ]);
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT user_skills, other_skills FROM userpersonalinfo WHERE user_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

$userSkills = trim($row['user_skills'] ?? '');
$otherSkills = trim($row['other_skills'] ?? '');

$hasSkills = ($userSkills !== "" || $otherSkills !== "");

echo json_encode([
    "status" => "success",
    "has_skills" => $hasSkills
]);