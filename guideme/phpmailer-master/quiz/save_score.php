<?php
require "../config/db.php";
session_start();

header("Content-Type: application/json");

if (empty($_SESSION['user_id'])) {
    echo json_encode(["error" => "Not logged in"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$user_id = (int) $_SESSION['user_id'];
$quiz_id = isset($data['quiz_id']) ? (int)$data['quiz_id'] : 0;
$score = isset($data['score']) ? (int)$data['score'] : 0;

if ($quiz_id <= 0) {
    echo json_encode(["error" => "No quiz_id"]);
    exit;
}

/* ✅ تحقق من صحة السكور */
if ($score < 0 || $score > 100) {
    echo json_encode(["error" => "Invalid score"]);
    exit;
}

/* ✅ تحديث فقط إذا يخص المستخدم */
$stmt = $conn->prepare("
UPDATE quizzes 
SET score = ?
WHERE id = ? AND user_id = ?
");
$stmt->bind_param("iii", $score, $quiz_id, $user_id);
$stmt->execute();

echo json_encode(["success" => true]);
?>