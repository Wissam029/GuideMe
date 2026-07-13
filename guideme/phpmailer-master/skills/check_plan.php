<?php
require "../config/db.php";
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["exists" => false]);
    exit;
}

$user_id = $_SESSION['user_id'];

$data = json_decode(file_get_contents("php://input"), true);

$skill = trim($data['skill'] ?? '');

// 🧠 نجيب كل الخطط ونقارن بشكل نظيف
$sql = "SELECT id, skill FROM plans WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

function normalize($text) {
    return trim(strtolower($text));
}

$found_plan_id = null;

while ($row = $result->fetch_assoc()) {

    if (normalize($row['skill']) === normalize($skill)) {
        $found_plan_id = $row['id'];
        break;
    }
}

if ($found_plan_id) {
    echo json_encode([
        "exists" => true,
        "plan_id" => $found_plan_id
    ]);
} else {
    echo json_encode([
        "exists" => false
    ]);
}