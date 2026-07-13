<?php
require "../config/db.php";
session_start();

header("Content-Type: application/json; charset=UTF-8");

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Not logged in"]);
    exit;
}

// التأكد أن user_id رقم
$user_id = (int) $_SESSION['user_id'];

// دالة تنظيف النصوص
function normalize($text) {
    return trim(mb_strtolower($text, 'UTF-8'));
}

$plans = [];
$all_skills = [];
$passed = [];
$passed_skills_clean = [];


// 1) جلب المهارات المجتازة بدرجة 80 أو أكثر أولاً
$stmt = $conn->prepare("
    SELECT skill, MAX(score) AS best_score
    FROM quizzes
    WHERE user_id = ? AND score >= 80
    GROUP BY skill
    ORDER BY best_score DESC
");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(["error" => "Prepare failed for passed skills query"]);
    exit;
}
$stmt->bind_param("i", $user_id);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(["error" => "Execute failed for passed skills query"]);
    exit;
}

$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $passed[] = [
        "skill" => $row['skill'],
        "best_score" => (float) $row['best_score']
    ];

    $passed_skills_clean[] = normalize($row['skill']);
}
$stmt->close();


// 2) جلب الخطط + التقدم
$stmt = $conn->prepare("
    SELECT 
        p.id AS plan_id,
        p.skill,
        COUNT(pd.id) AS total_days,
        SUM(CASE WHEN pr.id IS NOT NULL THEN 1 ELSE 0 END) AS completed_days
    FROM plans p
    JOIN plan_days pd ON p.id = pd.plan_id
    LEFT JOIN progress pr 
        ON pd.id = pr.plan_day_id AND pr.user_id = ?
    WHERE p.user_id = ?
    GROUP BY p.id, p.skill
");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(["error" => "Prepare failed for plans query"]);
    exit;
}
$stmt->bind_param("ii", $user_id, $user_id);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(["error" => "Execute failed for plans query"]);
    exit;
}

$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $skill_clean = normalize($row['skill']);
    if (in_array($skill_clean, $passed_skills_clean, true)) {
        continue;
    }
    $total_days_row = (int) $row['total_days'];
    $completed_days_row = (int) $row['completed_days'];

    $percent = 0;
    if ($total_days_row > 0) {
        $percent = round(($completed_days_row / $total_days_row) * 100);
    }
    $plans[] = [
        "plan_id" => (int) $row['plan_id'],
        "skill" => $row['skill'],
        "total_days" => $total_days_row,
        "completed_days" => $completed_days_row,
        "percent" => $percent
    ];
}
$stmt->close();


// 3) جلب المهارات من gaps
$stmt = $conn->prepare("
    SELECT gaps
    FROM user_career
    WHERE user_id = ?
    ORDER BY career_user_id DESC
    LIMIT 1
");

if (!$stmt) {
    http_response_code(500);
    echo json_encode(["error" => "Prepare failed for gaps query"]);
    exit;
}

$stmt->bind_param("i", $user_id);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(["error" => "Execute failed for gaps query"]);
    exit;
}

$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    if (!empty($row['gaps'])) {
        $decoded = json_decode($row['gaps'], true);

        if (is_array($decoded)) {
            $all_skills = $decoded;
        }
    }
}

$stmt->close();


// 4) تنظيف المهارات للمقارنة
$planned_skills = array_map(function ($s) {
    return normalize($s);
}, array_column($plans, 'skill'));

$all_clean = array_map(function ($s) {
    return normalize($s);
}, $all_skills);

$not_started = [];
foreach ($all_skills as $index => $skill) {
    $clean_skill = $all_clean[$index];

    if (
        !in_array($clean_skill, $planned_skills, true) &&
        !in_array($clean_skill, $passed_skills_clean, true)
    ) {
        $not_started[] = $skill;
    }
}


// 6) الإحصائيات
$total_plans = count($plans);
$total_days = 0;
$total_completed = 0;

foreach ($plans as $plan) {
    $total_days += $plan['total_days'];
    $total_completed += $plan['completed_days'];
}

$overall_percent = $total_days > 0
    ? round(($total_completed / $total_days) * 100)
    : 0;

echo json_encode([
    "plans" => $plans,
    "not_started" => $not_started,
    "passed" => $passed,
    "stats" => [
        "total_plans" => $total_plans,
        "completed_days" => $total_completed,
        "total_days" => $total_days,
        "overall_percent" => $overall_percent
    ]
], JSON_UNESCAPED_UNICODE);

exit;
?>