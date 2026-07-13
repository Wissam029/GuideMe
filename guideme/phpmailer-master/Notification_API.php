<?php

/**
 * ==========================================
 * API Security
 * ==========================================
 * This part protects the API page.
 * n8n must send the correct x-api-key header,
 * otherwise the page will stop and return Unauthorized.
 */
$secret ='Z7@qL9!vX3#nT5$KpR8^bH2*YfD6&cWmQ4sE';
$headers = getallheaders();
if (!isset($headers['x-api-key']) || $headers['x-api-key'] !== $secret) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(["error" => "Unauthorized"]);
    exit();
}

/**
 * ==========================================
 * GuideMe - Notification Data API
 * ==========================================
 *
 * This file returns raw user data as JSON for n8n.
 * It does not send emails and does not make decisions.
 */

require_once __DIR__ . "/config/db.php";
date_default_timezone_set('Asia/Riyadh');

/**
 * ==========================================
 * SQL Query: Collect Notification Data
 * ==========================================
 */
$sql = "
SELECT 
    u.user_id,
    u.username,
    u.email,
    u.last_login,

    h.top3_code,

    c.career_title,
    c.gaps,
    c.completion_email_sent,

    plan_data.planned_skills,
    plan_data.planned_skills_count,

    quiz_data.quiz_skills,
    quiz_data.quiz_skills_count,

    latest.skill AS last_quiz_skill,
    latest.created_at AS last_quiz_date,
    latest.score AS last_quiz_score

FROM userlogininformation u

LEFT JOIN (
    SELECT h1.*
    FROM hollandresult h1
    INNER JOIN (
        SELECT user_id, MAX(created_at) AS latest_created_at
        FROM hollandresult
        GROUP BY user_id
    ) h2
    ON h1.user_id = h2.user_id
    AND h1.created_at = h2.latest_created_at
) h
ON u.user_id = h.user_id

LEFT JOIN user_career c
    ON u.user_id = c.user_id

LEFT JOIN (
    SELECT 
        user_id,
        GROUP_CONCAT(DISTINCT skill SEPARATOR '|||') AS planned_skills,
        COUNT(DISTINCT skill) AS planned_skills_count
    FROM plans
    GROUP BY user_id
) plan_data
ON u.user_id = plan_data.user_id

LEFT JOIN (
    SELECT 
        q.user_id,
        GROUP_CONCAT(DISTINCT q.skill SEPARATOR '|||') AS quiz_skills,
        COUNT(DISTINCT q.skill) AS quiz_skills_count
    FROM quizzes q
    INNER JOIN plans p
        ON q.user_id = p.user_id
        AND q.skill = p.skill
    GROUP BY q.user_id
) quiz_data
ON u.user_id = quiz_data.user_id

LEFT JOIN (
    SELECT q1.user_id, q1.skill, q1.created_at, q1.score
    FROM quizzes q1
    INNER JOIN (
        SELECT user_id, MAX(created_at) AS max_date
        FROM quizzes
        GROUP BY user_id
    ) latest_q
    ON q1.user_id = latest_q.user_id
    AND q1.created_at = latest_q.max_date
) latest
ON u.user_id = latest.user_id
";

$result = $conn->query($sql);

if (!$result) {
    die("Database error: " . $conn->error);
}

/**
 * ==========================================
 * Prepare JSON Data
 * ==========================================
 */
$users = [];

while ($row = $result->fetch_assoc()) {

    // Convert gaps from JSON text to PHP array
    $gaps = [];
    if (!empty($row['gaps'])) {
        $decoded_gaps = json_decode($row['gaps'], true);

        if (is_array($decoded_gaps)) {
            $gaps = $decoded_gaps;
        }
    }

    // Convert planned skills text into array
    $planned_skills = [];
    if (!empty($row['planned_skills'])) {
        $planned_skills = explode('|||', $row['planned_skills']);
    }

    // Convert quiz skills text into array
    $quiz_skills = [];
    if (!empty($row['quiz_skills'])) {
        $quiz_skills = explode('|||', $row['quiz_skills']);
    }

    /**
     * ==========================================
     * Stage-based Data Cleaning
     * ==========================================
     *
     * If the user has not completed the personality test,
     * the agent should not receive career, gaps, plan, or quiz data.
     *
     * If the user completed the personality test but did not select a career,
     * the agent should not receive gaps, plan, or quiz data.
     */

    if (empty($row['top3_code'])) {
        $row['career_title'] = null;

        $gaps = [];
        $planned_skills = [];
        $quiz_skills = [];

        $row['last_quiz_skill'] = null;
        $row['last_quiz_date'] = null;
        $row['last_quiz_score'] = null;
    }

    if (!empty($row['top3_code']) && empty($row['career_title'])) {
        $gaps = [];
        $planned_skills = [];
        $quiz_skills = [];

        $row['last_quiz_skill'] = null;
        $row['last_quiz_date'] = null;
        $row['last_quiz_score'] = null;
    }
    // If the user has selected a career but has no skill gaps,
// there is no development plan needed, so plan and quiz data should be null.
if (!empty($row['career_title']) && count($gaps) === 0) {
    $planned_skills = [];
    $quiz_skills = [];

    $row['last_quiz_skill'] = null;
    $row['last_quiz_date'] = null;
    $row['last_quiz_score'] = null;
}

    $users[] = [
        "user_id" => $row['user_id'],
        "username" => $row['username'],
        "email" => $row['email'],

        "completion_email_sent" => (int)$row['completion_email_sent'],
        "top3_code" => $row['top3_code'],
        "career_title" => $row['career_title'],

        "gaps_count" => count($gaps),
        "gaps" => $gaps,
        "planned_skills_count" => count($planned_skills),
        "planned_skills" => $planned_skills,

        "quiz_skills_count" => count($quiz_skills),
        "quiz_skills" => $quiz_skills,
        
        "last_login" => $row['last_login'],
        "last_quiz_skill" => $row['last_quiz_skill'],
        "last_quiz_date" => $row['last_quiz_date'],
        "last_quiz_score" => $row['last_quiz_score']
    ];
}

/**
 * ==========================================
 * Final API Response
 * ==========================================
 */
$data = [
    "source" => "GuideMe Notification API",
    "generated_at" => date("Y-m-d H:i:s"),
    "users" => $users
];

header('Content-Type: application/json');
echo json_encode($data, JSON_PRETTY_PRINT);
exit();
?>