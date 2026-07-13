<?php
require "../config/db.php";
session_start();

header("Content-Type: application/json");

error_reporting(0);
ini_set('display_errors', 0);

if (empty($_SESSION['user_id'])) {
    echo json_encode(["exists" => false]);
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$plan_id = isset($_GET['plan_id']) ? (int) $_GET['plan_id'] : 0;

if ($plan_id <= 0) {
    echo json_encode(["exists" => false]);
    exit;
}

/* ✅ تأكد أن الخطة للمستخدم + الكويز له */

$stmt = $conn->prepare("
SELECT q.id 
FROM quizzes q
INNER JOIN plans p ON q.plan_id = p.id
WHERE q.user_id = ? 
  AND q.plan_id = ?
  AND p.user_id = ?
ORDER BY q.id DESC 
LIMIT 1
");
$stmt->bind_param("iii", $user_id, $plan_id, $user_id);
$stmt->execute();

$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode([
        "exists" => true,
        "quiz_id" => (int)$row['id']
    ]);
} else {
    echo json_encode([
        "exists" => false
    ]);
}
?>