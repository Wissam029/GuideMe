<?php
require "../config/db.php";
session_start();

header("Content-Type: application/json");

error_reporting(0);
ini_set('display_errors', 0);

if (empty($_SESSION['user_id'])) {
    echo json_encode(["error" => "Not logged in"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["error" => "Invalid JSON"]);
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$plan_id = isset($data['plan_id']) ? (int)$data['plan_id'] : 0;

if ($plan_id <= 0 || empty($data['quiz'])) {
    echo json_encode(["error" => "Missing data"]);
    exit;
}

/* ✅ تأكد أن الخطة تخص نفس المستخدم */
$stmt = $conn->prepare("SELECT skill FROM plans WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $plan_id, $user_id);
$stmt->execute();
$res = $stmt->get_result();

if (!$row = $res->fetch_assoc()) {
    echo json_encode(["error" => "Plan not found"]);
    exit;
}

$skill = $row['skill'];
$quiz_json = json_encode($data['quiz'], JSON_UNESCAPED_UNICODE);

/* ✅ إدخال آمن */
$stmt = $conn->prepare("
INSERT INTO quizzes (user_id, skill, plan_id, quiz_data)
VALUES (?, ?, ?, ?)
");
$stmt->bind_param("isis", $user_id, $skill, $plan_id, $quiz_json);
$stmt->execute();

echo json_encode([
    "quiz_id" => $stmt->insert_id
]);
?>