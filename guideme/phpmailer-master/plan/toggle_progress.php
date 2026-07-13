<?php
require "../config/db.php";
session_start();

header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "Not logged in"]);
    exit;
}

$user_id = (int) $_SESSION['user_id'];

$data = json_decode(file_get_contents("php://input"), true);
$plan_day_id = isset($data['plan_day_id']) ? (int)$data['plan_day_id'] : 0;

if ($plan_day_id <= 0) {
    echo json_encode(["error" => "Invalid plan_day_id"]);
    exit;
}

/* تأكد أن اليوم تابع لخطة تخص نفس المستخدم */
$stmt = $conn->prepare("
    SELECT pd.id
    FROM plan_days pd
    JOIN plans p ON pd.plan_id = p.id
    WHERE pd.id = ?
    AND p.user_id = ?
");

if (!$stmt) {
    echo json_encode(["error" => "Prepare failed"]);
    exit;
}

$stmt->bind_param("ii", $plan_day_id, $user_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$stmt->close();

/* تحقق هل اليوم مكتمل مسبقًا */
$stmt = $conn->prepare("
    SELECT id 
    FROM progress
    WHERE user_id = ?
    AND plan_day_id = ?
");

$stmt->bind_param("ii", $user_id, $plan_day_id);
$stmt->execute();

$check = $stmt->get_result();

if ($check->num_rows > 0) {
    $stmt->close();
    $stmt = $conn->prepare("
        DELETE FROM progress
        WHERE user_id = ?
        AND plan_day_id = ?
    ");
    $stmt->bind_param("ii", $user_id, $plan_day_id);
    $stmt->execute();

    echo json_encode([
        "success" => true,
        "completed" => false
    ]);
} else {
    $stmt->close();

    $stmt = $conn->prepare("
        INSERT INTO progress (user_id, plan_day_id)
        VALUES (?, ?)
    ");
    $stmt->bind_param("ii", $user_id, $plan_day_id);
    $stmt->execute();

    echo json_encode([
        "success" => true,
        "completed" => true
    ]);
}

$stmt->close();
$conn->close();