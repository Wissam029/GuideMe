<?php
require "../config/db.php";

header('Content-Type: application/json');

$user_id = 2;

// تجهيز الاستعلام
$query = "SELECT gaps FROM user_career WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $query);

if (!$stmt) {
    echo json_encode(["error" => "Prepare failed"]);
    exit;
}

// ربط المتغير
mysqli_stmt_bind_param($stmt, "i", $user_id);

// تنفيذ
mysqli_stmt_execute($stmt);

// جلب النتيجة
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);

if (!$row) {
    echo json_encode(["error" => "User not found"]);
    exit;
}

// ✅ أهم خطوة: decode JSON
$gaps = json_decode($row['gaps'], true);

if (!is_array($gaps)) {
    echo json_encode(["error" => "Invalid gaps format"]);
    exit;
}

// تنظيف البيانات
$gaps = array_map(function($s) {
    return trim($s);
}, $gaps);

$gaps = array_unique($gaps);
$gaps = array_values($gaps);

// إرسال النتيجة
echo json_encode([
    "skills" => $gaps
]);