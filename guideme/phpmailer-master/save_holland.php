<?php
session_start();

/* Debug (TEMP): show PHP errors during development */
error_reporting(E_ALL);
ini_set('display_errors', 1);

/* JSON response */
header('Content-Type: application/json; charset=utf-8');

/* CORS (optional) */
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
  exit;
}

/* IMPORTANT: correct path */
include "config/db.php"; // expects $conn (mysqli)

/* Require logged in user_id */
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if ($user_id <= 0) {
  http_response_code(401);
  echo json_encode(['ok' => false, 'error' => 'Not logged in (missing user_id session)'], JSON_UNESCAPED_UNICODE);
  exit;
}

/* Read JSON body */
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Invalid JSON'], JSON_UNESCAPED_UNICODE);
  exit;
}

/* Expected fields */
$test_version = isset($data['test_version']) ? (string)$data['test_version'] : 'v1';
$top3_code    = isset($data['top3_code']) ? (string)$data['top3_code'] : '';
$scores       = $data['scores'] ?? null;
$percentages  = $data['percentages'] ?? null;
$summary_text = $data['summary_text'] ?? ($data['summary'] ?? null);
$answers      = $data['answers'] ?? [];

/* Validate */
if ($top3_code === '' || !is_array($scores) || !is_array($percentages) || !is_array($answers)) {
  http_response_code(400);
  echo json_encode([
    'ok' => false,
    'error' => 'Missing required fields (top3_code/scores/percentages/answers)'
  ], JSON_UNESCAPED_UNICODE);
  exit;
}

$scores_json = json_encode($scores, JSON_UNESCAPED_UNICODE);
$percentages_json = json_encode($percentages, JSON_UNESCAPED_UNICODE);

/* Transaction */
mysqli_begin_transaction($conn);

try {
  /* 1) Insert hollandresult */
  $sqlResult = "
    INSERT INTO hollandresult
      (user_id, test_version, top3_code, scores_json, percentages_json, summary_text)
    VALUES
      (?, ?, ?, ?, ?, ?)
  ";

  $stmt = mysqli_prepare($conn, $sqlResult);
  if (!$stmt) {
    throw new Exception('Prepare hollandresult failed: ' . mysqli_error($conn));
  }

  mysqli_stmt_bind_param(
    $stmt,
    "isssss",
    $user_id,
    $test_version,
    $top3_code,
    $scores_json,
    $percentages_json,
    $summary_text
  );

  if (!mysqli_stmt_execute($stmt)) {
    throw new Exception('Execute hollandresult failed: ' . mysqli_stmt_error($stmt));
  }

  $result_id = mysqli_insert_id($conn);
  mysqli_stmt_close($stmt);


  mysqli_commit($conn);

  echo json_encode(['ok' => true, 'result_id' => $result_id], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  mysqli_rollback($conn);
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}