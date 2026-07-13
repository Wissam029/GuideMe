<?php
/**
 * ==========================================
 * Mark Completion Email Sent API
 * ==========================================
 * Called by n8n to check all users.
 * If a user completed all gap skills, this updates:
 * completion_email_sent = 1
 */

$secret = 'r9K!xT7@Q2vL#5mZ8^WcH3*YpD6&N4sB';

$headers = getallheaders();

if (!isset($headers['x-api-key']) || $headers['x-api-key'] !== $secret) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(["error" => "Unauthorized"]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(["error" => "Method Not Allowed"]);
    exit();
}

require_once __DIR__ . "/config/db.php";
header('Content-Type: application/json');

$sql = "
SELECT 
    c.user_id,
    c.gaps,
    c.completion_email_sent,

    plan_data.planned_skills_count,
    quiz_data.quiz_skills_count,
    latest.score AS last_quiz_score

FROM user_career c

LEFT JOIN (
    SELECT 
        user_id,
        COUNT(DISTINCT skill) AS planned_skills_count
    FROM plans
    GROUP BY user_id
) plan_data
ON c.user_id = plan_data.user_id

LEFT JOIN (
    SELECT 
        q.user_id,
        COUNT(DISTINCT q.skill) AS quiz_skills_count
    FROM quizzes q
    INNER JOIN plans p
        ON q.user_id = p.user_id
        AND q.skill = p.skill
    GROUP BY q.user_id
) quiz_data
ON c.user_id = quiz_data.user_id

LEFT JOIN (
    SELECT q1.user_id, q1.score
    FROM quizzes q1
    INNER JOIN (
        SELECT user_id, MAX(created_at) AS max_date
        FROM quizzes
        GROUP BY user_id
    ) latest_q
    ON q1.user_id = latest_q.user_id
    AND q1.created_at = latest_q.max_date
) latest
ON c.user_id = latest.user_id
";

$result = $conn->query($sql);

if (!$result) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $conn->error]);
    exit();
}

$updated_users = [];
$checked_users = [];

while ($row = $result->fetch_assoc()) {

    $gaps = [];
    if (!empty($row['gaps'])) {
        $decoded_gaps = json_decode($row['gaps'], true);
        if (is_array($decoded_gaps)) {
            $gaps = $decoded_gaps;
        }
    }

    $gaps_count = count($gaps);
    $planned_skills_count = (int)($row['planned_skills_count'] ?? 0);
    $quiz_skills_count = (int)($row['quiz_skills_count'] ?? 0);
    $last_quiz_score = $row['last_quiz_score'];

    $is_completed =
        (int)$row['completion_email_sent'] === 0 &&
        $gaps_count > 0 &&
        $planned_skills_count === $gaps_count &&
        $quiz_skills_count === $gaps_count &&
        $last_quiz_score !== null &&
        (int)$last_quiz_score >= 80;

    $checked_users[] = [
        "user_id" => $row['user_id'],
        "gaps_count" => $gaps_count,
        "planned_skills_count" => $planned_skills_count,
        "quiz_skills_count" => $quiz_skills_count,
        "last_quiz_score" => $last_quiz_score,
        "is_completed" => $is_completed
    ];

    if ($is_completed) {
        $update = $conn->prepare("
            UPDATE user_career
            SET completion_email_sent = 1
            WHERE user_id = ?
        ");

        $update->bind_param("i", $row['user_id']);
        $update->execute();
        $update->close();

        $updated_users[] = $row['user_id'];
    }
}

echo json_encode([
    "status" => "success",
    "updated_count" => count($updated_users),
    "updated_users" => $updated_users,
    "checked_users" => $checked_users
], JSON_PRETTY_PRINT);

$conn->close();
?>