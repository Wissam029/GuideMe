<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
$raw = file_get_contents("php://input");
$data = json_decode($raw, true) ?? [];
$mode = $data['mode'] ?? 'suggestion';
$chosenCareerId = $data['chosen_career_id'] ?? null;


include "config/db.php"; 
// Require authenticated session
$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if ($userId <= 0) {
  http_response_code(401);
  echo json_encode(["error" => "Not logged in (missing session user_id)"]);
  exit;
}

if (isset($data['action']) && $data['action'] === 'get_selected_career') {
  $stmt = mysqli_prepare($conn, "SELECT career_id FROM user_career WHERE user_id = ? LIMIT 1");
  mysqli_stmt_bind_param($stmt, "i", $userId);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  $row = mysqli_fetch_assoc($res);
  mysqli_stmt_close($stmt);

  echo json_encode([
    "status" => "success",
    "career_id" => $row['career_id'] ?? null
  ]);
  exit;
}

// ===== SAVE SELECTED CAREER =====
if (isset($data['action']) && $data['action'] === 'save_selected_career') {

  $career_id = (int)($data['career_id'] ?? 0);
$career_title = $data['career_title'] ?? '';
$overall_match = (int)($data['overall_match'] ?? 0);
$holland_match = (int)($data['holland_match'] ?? 0);
$skills_match = (int)($data['skills_match'] ?? 0);
  $strengths = $data['strengths'] ?? '';
  $gaps = $data['gaps'] ?? '';

  if ($career_id <= 0 || $career_title === '') {
  http_response_code(400);
  echo json_encode([
    "status" => "error",
    "message" => "Missing career data"
  ]);
  exit;
}

  // تحويل arrays إلى JSON
  if (is_array($strengths)) {
    $strengths = json_encode($strengths);
  }
  if (is_array($gaps)) {
    $gaps = json_encode($gaps);
  }

  // حذف الاختيار القديم (عشان يقدر يغير المسار)
  $stmt = mysqli_prepare($conn, "DELETE FROM user_career WHERE user_id = ?");
  mysqli_stmt_bind_param($stmt, "i", $userId);
  mysqli_stmt_execute($stmt);
  mysqli_stmt_close($stmt);

  // إدخال الجديد
  $stmt = mysqli_prepare($conn, "
    INSERT INTO user_career 
    (user_id, career_id, career_title, overall_match, holland_match, skills_match, strengths, gaps, selected_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
  ");

  mysqli_stmt_bind_param(
    $stmt,
    "iisiiiss",
    $userId,
    $career_id,
    $career_title,
    $overall_match,
    $holland_match,
    $skills_match,
    $strengths,
    $gaps
  );

  if (mysqli_stmt_execute($stmt)) {
    echo json_encode(["status" => "success"]);
  } else {
    echo json_encode([
      "status" => "error",
      "message" => "Database insert failed"
    ]);
  }

  mysqli_stmt_close($stmt);
  exit;
}


/* ====== 2. holland code ====== */
$hollandCode = "";

$stmt = mysqli_prepare($conn, "
    SELECT top3_code
    FROM hollandresult
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($res);
$hollandCode = $row['top3_code'] ?? "";
mysqli_stmt_close($stmt);

/* ====== 3. user skills ====== */
$userSkillsText = "";

$stmt = mysqli_prepare($conn, "
    SELECT user_skills
    FROM userpersonalinfo
    WHERE user_id = ?
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($res);
$userSkillsText = $row['user_skills'] ?? "";
mysqli_stmt_close($stmt);

$skillsRaw = array_values(array_filter(array_map('trim',
    preg_split("/[,\n;\r]+/", $userSkillsText)
)));

/* ====== 4. careers catalog ====== */
/*
$jobs = [];

$q = mysqli_query($conn, "
    SELECT career_id, job_title, holland_code, foundation_skills, technical_skills
    FROM careers
");

while ($row = mysqli_fetch_assoc($q)) {

    $f = preg_split("/[,\n;\r]+/", $row['foundation_skills'] ?? "");
    $t = preg_split("/[,\n;\r]+/", $row['technical_skills'] ?? "");

    $row['foundation_skills'] = array_values(array_filter(array_map('trim', $f)));
    $row['technical_skills']  = array_values(array_filter(array_map('trim', $t)));

    $jobs[] = $row;
}

*/

/* ====== 5. snapshot ====== */
$snapshot = [
    "user_id" => $userId,
    "mode" => $mode,
    "holland_code" => $hollandCode,
    "skills_raw" => $skillsRaw,
    "chosen_career_id" => $chosenCareerId
    //"jobs_catalog" => $jobs
];

if ($mode === "manual" && empty($chosenCareerId)) {
    http_response_code(400);
    echo json_encode([
        "error" => "Missing chosen_career_id"
    ]);
    exit;
}

/* ====== 6. send to n8n ====== */
$n8nWebhook = "http://localhost:5678/webhook/career-match";

$ch = curl_init($n8nWebhook);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($snapshot));

$response = curl_exec($ch);
curl_close($ch);

/* ====== 7. return ====== */
echo $response;