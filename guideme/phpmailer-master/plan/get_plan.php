<?php
require "../config/db.php";
session_start();

header('Content-Type: application/json; charset=UTF-8');

// ✅ التأكد من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "User not logged in"]);
    exit;
}

$user_id = (int) $_SESSION['user_id'];

// ✅ تنظيف وإجبار plan_id يكون رقم
$plan_id = isset($_GET['plan_id']) ? (int) $_GET['plan_id'] : 0;

if ($plan_id <= 0) {
    echo json_encode(["error" => "Invalid plan_id"]);
    exit;
}

// ✅ جلب الخطة مع التأكد أنها تخص نفس المستخدم
$sql = "
SELECT 
    pd.id,
    pd.day_number,
    pd.title,
    pd.description,
    pd.task,
    IF(pr.id IS NULL, 0, 1) AS completed
FROM plan_days pd

-- Join with plans table to ensure ownership (security check)
JOIN plans pl 
    ON pd.plan_id = pl.id

-- Join with progress table to get completion status
LEFT JOIN progress pr 
    ON pd.id = pr.plan_day_id 
    AND pr.user_id = ?

WHERE pd.plan_id = ?
AND pl.user_id = ?

ORDER BY pd.day_number ASC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(["error" => "Prepare failed"]);
    exit;
}

// user_id مرتين (للتحقق من الملكية + completed)
$stmt->bind_param("iii", $user_id, $plan_id, $user_id);

$stmt->execute();
$result = $stmt->get_result();

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

// إذا ما رجع بيانات → غالبًا المستخدم يحاول يدخل خطة مو له
if (empty($data)) {
    echo json_encode(["error" => "No plan found or unauthorized"]);
    exit;
}

echo json_encode($data);

$stmt->close();
$conn->close();