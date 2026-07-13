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

$skill = trim($data['skill'] ?? '');
$weeks = isset($data['weeks']) ? (int)$data['weeks'] : 0;
$days  = isset($data['days']) ? (int)$data['days'] : 0;

if ($skill === '' || $weeks <= 0 || $days <= 0) {
    echo json_encode(["error" => "Missing input"]);
    exit;
}

if (strlen($skill) > 100) {
    echo json_encode(["error" => "Skill name too long"]);
    exit;
}

if ($weeks > 12 || $days > 7) {
    echo json_encode(["error" => "Invalid weeks or days"]);
    exit;
}

$payload = json_encode([
    "skill" => $skill,
    "weeks" => $weeks,
    "days" => $days
]);

$ch = curl_init("http://localhost:5678/webhook-test/generate-plan");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode(["error" => curl_error($ch)]);
    curl_close($ch);
    exit;
}

curl_close($ch);

$result = json_decode($response, true);

if (!$result) {
    echo json_encode([
        "error" => "Invalid JSON from AI",
        "raw" => $response
    ]);
    exit;
}

$plan = $result['data']['plan'] ?? $result['plan'] ?? null;

if (!$plan || !is_array($plan)) {
    echo json_encode([
        "error" => "Invalid plan structure",
        "response" => $result
    ]);
    exit;
}

/* حفظ الخطة بعد التأكد أن n8n رجّع خطة صحيحة */
$stmt = $conn->prepare("
    INSERT INTO plans (user_id, skill, weeks, days)
    VALUES (?, ?, ?, ?)
");

if (!$stmt) {
    echo json_encode(["error" => "Prepare plan insert failed"]);
    exit;
}

$stmt->bind_param("isii", $user_id, $skill, $weeks, $days);

if (!$stmt->execute()) {
    echo json_encode(["error" => "Failed to save plan"]);
    exit;
}

$plan_id = $stmt->insert_id;
$stmt->close();

/* حفظ أيام الخطة */
$stmt = $conn->prepare("
    INSERT INTO plan_days
    (plan_id, day_number, title, description, task)
    VALUES (?, ?, ?, ?, ?)
");

if (!$stmt) {
    echo json_encode(["error" => "Prepare plan days insert failed"]);
    exit;
}

foreach ($plan as $item) {
    $day = isset($item['day']) ? (int)$item['day'] : 0;
    $title = trim($item['title'] ?? '');
    $description = trim($item['description'] ?? '');
    $task = trim($item['task'] ?? '');

    if ($day <= 0) {
        continue;
    }

    $stmt->bind_param(
        "iisss",
        $plan_id,
        $day,
        $title,
        $description,
        $task
    );

    $stmt->execute();
}

$stmt->close();
$conn->close();

echo json_encode([
    "success" => true,
    "plan_id" => $plan_id
]);