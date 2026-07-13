<?php
// -----------------------------------------------------------------------------
// إعدادات التطوير
// هذا الجزء يشغل عرض كل الأخطاء أثناء التطوير حتى نعرف أي مشكلة بسرعة.
// -----------------------------------------------------------------------------
error_reporting(E_ALL);
ini_set('display_errors', 1);

// -----------------------------------------------------------------------------
// ملفات أساسية يحتاجها هذا الملف:
// auth_guard.php -> يتأكد أن المستخدم مسجل دخول
// db.php         -> ينشئ اتصال قاعدة البيانات
// -----------------------------------------------------------------------------
require_once __DIR__ . "/config/secure_session.php";
require_once "auth_guard.php";
require_once __DIR__ . "/config/db.php";

// -----------------------------------------------------------------------------
// Auto Loader لمكتبة PDF Parser
// إذا استدعينا أي class من مكتبة قراءة الـ PDF، يتم تحميل ملفه تلقائيًا.
// -----------------------------------------------------------------------------
spl_autoload_register(function ($class) {
  $prefix = 'Smalot\\PdfParser\\';
  $base_dir = __DIR__ . '/libs/pdfparser/src/Smalot/PdfParser/';

  $len = strlen($prefix);
  if (strncmp($prefix, $class, $len) !== 0) {
    return;
  }

  $relative_class = substr($class, $len);
  $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

  if (file_exists($file)) {
    require_once $file;
  }
});

// -----------------------------------------------------------------------------
// ضبط التوقيت المحلي للمشروع + جلب user_id من الـ session
// -----------------------------------------------------------------------------
date_default_timezone_set('Asia/Riyadh');

$user_id = $_SESSION['user_id'] ?? 0;

// -----------------------------------------------------------------------------
// حماية إضافية: إذا ما فيه مستخدم داخل session نوقف التنفيذ مباشرة
// -----------------------------------------------------------------------------
if (!$user_id) {
  die("User session is missing.");
}

// -----------------------------------------------------------------------------
// parseSkillsText
// تحول نص المهارات المخزن في قاعدة البيانات مثل:
// "HTML;CSS;JavaScript"
// إلى array داخل PHP مع تنظيف القيم الفارغة وحذف التكرار.
// -----------------------------------------------------------------------------
function parseSkillsText(?string $skillsText): array
{
  if (!$skillsText) return [];

  $skills = explode(';', $skillsText);
  $cleaned = [];

  foreach ($skills as $skill) {
    $skill = trim($skill);
    if ($skill !== '') {
      $cleaned[] = $skill;
    }
  }

  $cleaned = array_values(array_unique($cleaned));
  sort($cleaned);
  return $cleaned;
}

// -----------------------------------------------------------------------------
// skillsToText
// عكس الدالة السابقة:
// تحول array المهارات إلى نص مفصول بـ ; حتى نخزنه في قاعدة البيانات.
// -----------------------------------------------------------------------------
function skillsToText(array $skills): string
{
  $cleaned = [];

  foreach ($skills as $skill) {
    $skill = trim($skill);
    if ($skill !== '') {
      $cleaned[] = $skill;
    }
  }

  $cleaned = array_values(array_unique($cleaned));
  return implode(';', $cleaned);
}

// -----------------------------------------------------------------------------
// ensureUserPersonalInfoRow
// تتأكد أن للمستخدم صف موجود داخل جدول userpersonalinfo
// وإذا لم يوجد، يتم إنشاء صف جديد له.
// -----------------------------------------------------------------------------
function ensureUserPersonalInfoRow(mysqli $conn, int $user_id): void
{
  $stmt = $conn->prepare("SELECT user_id FROM userpersonalinfo WHERE user_id=? LIMIT 1");
  if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();

    if (!$exists) {
      $insert = $conn->prepare("INSERT INTO userpersonalinfo (user_id) VALUES (?)");
      if ($insert) {
        $insert->bind_param("i", $user_id);
        $insert->execute();
        $insert->close();
      }
    }
  }
}

// -----------------------------------------------------------------------------
// getSystemSkillsFromCareers
// تجلب المهارات الرسمية الموجودة داخل النظام من جدول careers
// من عمودي foundation_skills و technical_skills
// ثم تجمعها في array واحدة وتحذف التكرار.
// -----------------------------------------------------------------------------
function getSystemSkillsFromCareers(mysqli $conn): array
{
  $system_skills = [];

  $skillsQuery = "SELECT beginner_level, intermediate_level FROM careers";
  $skillsResult = $conn->query($skillsQuery);

  if ($skillsResult) {
    while ($skillsRow = $skillsResult->fetch_assoc()) {
      foreach (['beginner_level', 'intermediate_level'] as $column) {
        if (!empty($skillsRow[$column])) {
          $parts = explode(';', $skillsRow[$column]);
          foreach ($parts as $skill) {
            $skill = trim($skill);
            if ($skill !== '') {
              $system_skills[] = $skill;
            }
          }
        }
      }
    }
  }

  return array_values(array_unique($system_skills));
}

// -----------------------------------------------------------------------------
// sendToN8nWebhook
// ترسل البيانات إلى n8n بصيغة JSON وترجع:
// - payload   : البيانات المرسلة
// - response  : الرد القادم من n8n
// - httpCode  : كود الاستجابة
// - curlError : أي خطأ أثناء الإرسال
// -----------------------------------------------------------------------------
function sendToN8nWebhook(string $webhookUrl, array $payload): array
{
 $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE);
$response = '';
$httpCode = 0;
$curlError = '';
  
  if (function_exists('curl_init')) {
    $ch = curl_init($webhookUrl);
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_POST => true,
      CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
      CURLOPT_POSTFIELDS => $jsonPayload,
      CURLOPT_TIMEOUT => 60,
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
      $curlError = curl_error($ch);
    }
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

    
         
    curl_close($ch);

  } else {
    $context = stream_context_create([
      'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\n",
        'content' => $jsonPayload,
        'timeout' => 60
      ]
    ]);

    $response = @file_get_contents($webhookUrl, false, $context);

    if (isset($http_response_header) && is_array($http_response_header)) {
      foreach ($http_response_header as $headerLine) {
        if (preg_match('/HTTP\/\S+\s+(\d+)/', $headerLine, $matches)) {
          $httpCode = (int) $matches[1];
          break;
        }
      }
    }
  }

  return [
    'payload' => $jsonPayload,
    'response' => $response,
    'httpCode' => $httpCode,
    'curlError' => $curlError,
  ];
}


// -----------------------------------------------------------------------------
// extractMatchedAndUnmatchedFromResults
// تستقبل results القادمة من n8n
// ثم تقسمها إلى:
// - matched   -> مهارات مطابقة
// - unmatched -> مهارات غير مطابقة
// -----------------------------------------------------------------------------
function extractMatchedAndUnmatchedFromResults(array $results): array
{
  $matched = [];
  $unmatched = [];

  foreach ($results as $item) {
    $matchStatus = strtolower(trim((string)($item['match_status'] ?? '')));
    $matchedSystemSkill = trim((string)($item['matched_system_skill'] ?? ''));
    $extractedSkill = trim((string)($item['extracted_skill'] ?? ''));

    if ($matchStatus === 'match' && $matchedSystemSkill !== '') {
      $matched[] = $matchedSystemSkill;
    } elseif ($matchStatus === 'non-match' && $extractedSkill !== '') {
      $unmatched[] = $extractedSkill;
    }
  }

  $matched = array_values(array_unique(array_filter($matched)));
  $unmatched = array_values(array_unique(array_filter($unmatched)));

  return [$matched, $unmatched];
}

// -----------------------------------------------------------------------------
// saveSkillsToUserPersonalInfo
// تحفظ النتائج النهائية في جدول userpersonalinfo:
// - المهارات المطابقة داخل user_skills
// - المهارات غير المطابقة داخل other_skills
// مع الحفاظ على المهارات القديمة وعدم تكرارها.
// -----------------------------------------------------------------------------
function saveSkillsToUserPersonalInfo(mysqli $conn, int $user_id, array $matched, array $unmatched): bool
{
  ensureUserPersonalInfoRow($conn, $user_id);

  $currentUserSkillsText = '';
  $currentOtherSkillsText = '';

$stmt = $conn->prepare("SELECT user_skills, other_skills FROM userpersonalinfo WHERE user_id=? LIMIT 1");
  if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($dbUserSkills, $dbOtherSkills);

    if ($stmt->fetch()) {
      $currentUserSkillsText = $dbUserSkills ?? '';
      $currentOtherSkillsText = $dbOtherSkills ?? '';
    }
    $stmt->close();
  }

  $currentUserSkills = parseSkillsText($currentUserSkillsText);
  $currentOtherSkills = parseSkillsText($currentOtherSkillsText);

  $finalUserSkills = array_values(array_unique(array_merge($currentUserSkills, $matched)));
  $finalOtherSkills = array_values(array_unique(array_merge($currentOtherSkills, $unmatched)));

  $finalUserSkillsText = skillsToText($finalUserSkills);
  $finalOtherSkillsText = skillsToText($finalOtherSkills);

  $update = $conn->prepare("UPDATE userpersonalinfo SET user_skills=?, other_skills=? WHERE user_id=?");
  if (!$update) {
    return false;
  }

  $update->bind_param("ssi", $finalUserSkillsText, $finalOtherSkillsText, $user_id);
  $ok = $update->execute();
  $update->close();

  return $ok;
}


/*
|--------------------------------------------------------------------------
| Handle POST actions
|--------------------------------------------------------------------------
| هذا القسم هو قلب التنفيذ في الصفحة.
| أي form أو زر يرسل POST إلى profile.php سيتم التعامل معه هنا
| حسب قيمة action.
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $action = $_POST['action'] ?? '';

  // -------------------------------------------------------------------------
  // save_profile
  // - full_name
  // - academic_status
  // -------------------------------------------------------------------------
  if ($action === 'save_profile') {
    $new_name = trim($_POST['full_name'] ?? '');
    $new_status = trim($_POST['academic_status'] ?? '');

    if ($new_name !== '' && in_array($new_status, ['Student', 'Graduate'])) {
  $stmt = $conn->prepare("UPDATE userlogininformation SET username=?, academic_status=? WHERE user_id=?");
      if ($stmt) {
        $stmt->bind_param("ssi", $new_name, $new_status, $user_id);
        $stmt->execute();
        $stmt->close();
      }

      $_SESSION['full_name'] = $new_name;
      $_SESSION['academic_status'] = $new_status;

      header("Location: profile.php");
      exit();
    }
  }

  // -------------------------------------------------------------------------
  // upload_cv
  // -------------------------------------------------------------------------
  if ($action === 'upload_cv') {
    ensureUserPersonalInfoRow($conn, $user_id);

    if (!isset($_FILES['cv_file']) || $_FILES['cv_file']['error'] !== UPLOAD_ERR_OK) {
      $_SESSION['profile_message'] = ['type' => 'error', 'text' => 'Failed to upload CV.'];
      header("Location: profile.php");
      exit();
    }

    $file = $_FILES['cv_file'];
    $originalName = $file['name'] ?? '';
    $tmpPath = $file['tmp_name'] ?? '';
    $fileSize = (int)($file['size'] ?? 0);

    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowed = ['pdf'];

    if (!in_array($ext, $allowed, true)) {
      $_SESSION['profile_message'] = ['type' => 'error', 'text' => 'Only PDF files are allowed.'];
      header("Location: profile.php");
      exit();
    }

    if ($fileSize > 10 * 1024 * 1024) {
$_SESSION['profile_message'] = ['type' => 'error', 'text' => 'File is too large. Maximum size is 10MB.'];
      header("Location: profile.php");
      exit();
    }

    $uploadDir = __DIR__ . '/uploads/CV/';
    if (!is_dir($uploadDir)) {
      mkdir($uploadDir, 0777, true);
    }

    $safeName = 'user_' . $user_id . '_' . time() . '.' . $ext;
    $targetPath = $uploadDir . $safeName;
    $relativePath = 'uploads/CV/' . $safeName;

    if (!move_uploaded_file($tmpPath, $targetPath)) {
      $_SESSION['profile_message'] = ['type' => 'error', 'text' => 'Could not save the uploaded file.'];
      header("Location: profile.php");
      exit();
    }
$stmt = $conn->prepare("UPDATE userpersonalinfo SET cv_file_name=?, cv_file_path=?, cv_status=? WHERE user_id=?");
    if ($stmt) {
      $status = 'Uploaded';
      $stmt->bind_param("sssi", $originalName, $relativePath, $status, $user_id);
      $stmt->execute();
      $stmt->close();
    }

    $_SESSION['profile_message'] = ['type' => 'success', 'text' => 'CV uploaded successfully.'];
    header("Location: profile.php");
    exit();
  }


  // -------------------------------------------------------------------------
  // extract_cv_text
  // هذا المسار خاص بزر Extract Skills:
  // 1) يجيب ملف السيفي
  // 2) يقرأ نص الـ PDF
  // 3) ينظف النص
  // 4) يرسله إلى n8n
  // 5) يستقبل النتائج
  // 6) يحفظها في قاعدة البيانات
  // -------------------------------------------------------------------------
  if ($action === 'extract_cv_text') {
    $cv_file_path = '';

    $stmt = $conn->prepare("SELECT cv_file_path FROM userpersonalinfo WHERE user_id=? LIMIT 1");
    if ($stmt) {
      $stmt->bind_param("i", $user_id);
      $stmt->execute();
      $stmt->bind_result($db_cv_file_path);

      if ($stmt->fetch()) {
        $cv_file_path = $db_cv_file_path ?? '';
      }
      $stmt->close();
    }

    if (empty($cv_file_path)) {
      $_SESSION['profile_message'] = ['type' => 'error', 'text' => 'No CV found to extract from.'];
      header("Location: profile.php");
      exit();
    }

    $fullPath = __DIR__ . '/' . ltrim($cv_file_path, '/');

    if (!file_exists($fullPath)) {
      $_SESSION['profile_message'] = ['type' => 'error', 'text' => 'CV file not found on server.'];
      header("Location: profile.php");
      exit();
    }

    // ---------------------------------------------------------------------
    // محاولة قراءة السيفي وإرساله إلى n8n
    // إذا حصل خطأ سيتم التقاطه في catch
    // ---------------------------------------------------------------------
    try {
      // إنشاء parser من مكتبة قراءة الـ PDF
      $parser = new \Smalot\PdfParser\Parser();
      $pdf = $parser->parseFile($fullPath);
      $text = trim($pdf->getText());

      // تنظيف النص:
      // حذف المسافات والأسطر الزائدة قبل إرساله إلى n8n
      $text = preg_replace('/\s+/', ' ', $text);
 
      if ($text === '') {
  $_SESSION['profile_message'] = [
    'type' => 'error',
    'text' => 'The CV text could not be read. Please upload a clearer PDF and try again.'
  ];

  header("Location: profile.php");
  exit();
}
      // رابط الويبهوك الحالي في n8n
      $webhookUrl = 'http://localhost:5678/webhook-test/extract_cv_skills';
      // جلب المهارات الرسمية من قاعدة البيانات لإرسالها مع نص السيفي
      
      $system_skills = getSystemSkillsFromCareers($conn);

      // تجهيز البيانات التي ستُرسل إلى n8n
      // source_type = cv لأن المصدر هنا هو السيفي
      $payload = json_encode([
        'source_type' => 'cv',
        'user_id' => $user_id,
        'cv_text' => $text,
        'system_skills' => $system_skills
      ], JSON_UNESCAPED_UNICODE);

     $response = '';
$httpCode = 0;
$curlError = '';
      
      if (function_exists('curl_init')) {
        $ch = curl_init($webhookUrl);
        curl_setopt_array($ch, [
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_POST => true,
          CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
          CURLOPT_POSTFIELDS => $payload,
          CURLOPT_TIMEOUT => 60,
        ]);

        $response = curl_exec($ch);

if ($response === false) {
  $curlError = curl_error($ch);

  $_SESSION['profile_message'] = [
    'type' => 'error',
    'text' => 'Connection error. Please try again later.'
  ];

  curl_close($ch);
  header("Location: profile.php");
  exit();
}

$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($httpCode !== 200) {
  $_SESSION['profile_message'] = [
    'type' => 'error',
    'text' => 'Extraction service error. Please try again later.'
  ];

  curl_close($ch);
  header("Location: profile.php");
  exit();
}

curl_close($ch);
      } else {
        $context = stream_context_create([
          'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => $payload,
            'timeout' => 60
          ]
        ]);
        $response = @file_get_contents($webhookUrl, false, $context);
        if (isset($http_response_header) && is_array($http_response_header)) {
          foreach ($http_response_header as $headerLine) {
            if (preg_match('/HTTP\/\S+\s+(\d+)/', $headerLine, $matches)) {
              $httpCode = (int) $matches[1];
              break;
            }
          }
        }
      }

      if ($response === false || $response === '') {
        $_SESSION['profile_message'] = [
          'type' => 'error',
          'text' => 'Could not connect to the skill extraction service. Please make sure n8n is running, then try again.'
        ];

        header("Location: profile.php");
        exit();
      }

      // تحويل الرد من JSON إلى array داخل PHP
      $decoded = json_decode($response, true);

      if (isset($decoded['error']) || ($decoded['status'] ?? '') === 'error') {
  $_SESSION['profile_message'] = [
    'type' => 'error',
    'text' => 'Skill extraction failed. Please try again.'
  ];
  header("Location: profile.php");
  exit();
}
      if (!is_array($decoded) || !isset($decoded['results']) || !is_array($decoded['results'])) {
        $_SESSION['profile_message'] = [
          'type' => 'error',
          'text' => 'The extraction service returned an unexpected response. Please try again.'
        ];

        header("Location: profile.php");
        exit();
      }

      // تقسيم النتائج القادمة من n8n إلى:
      // - matchedSkills
      // - unmatchedSkills
      [$matchedSkills, $unmatchedSkills] = extractMatchedAndUnmatchedFromResults($decoded['results']);

      // حفظ المهارات النهائية في قاعدة البيانات
      if (!saveSkillsToUserPersonalInfo($conn, $user_id, $matchedSkills, $unmatchedSkills)) {
        $_SESSION['profile_message'] = [
          'type' => 'error',
          'text' => 'Skills were extracted, but could not be saved. Please try again.'
        ];

        header("Location: profile.php");
        exit();
      }

      $_SESSION['profile_message'] = [
        'type' => 'success',
        'text' => 'CV skills extracted and saved successfully.'
      ];

      // تحديث حالة السيفي بعد نجاح الاستخراج
      $statusStmt = $conn->prepare("UPDATE userpersonalinfo SET cv_status=? WHERE user_id=?");
      if ($statusStmt) {
        $cvExtractedStatus = 'Extracted';
        $statusStmt->bind_param("si", $cvExtractedStatus, $user_id);
        $statusStmt->execute();
        $statusStmt->close();
      }

      header("Location: profile.php");
      exit();
    } catch (Throwable $e){
  error_log($e->getMessage());

  $_SESSION['profile_message'] = [
    'type' => 'error',
    'text' => 'Failed to read the CV file. Please upload a clear PDF and try again.'
  ];

  header("Location: profile.php");
  exit();
}
  }


  if ($action === 'delete_my_career') {

    // Get user's plan ids
    $planIds = [];
    $stmt = $conn->prepare("SELECT id FROM plans WHERE user_id=?");
    if ($stmt) {
      $stmt->bind_param("i", $user_id);
      $stmt->execute();
      $result = $stmt->get_result();
      while ($row = $result->fetch_assoc()) {
        $planIds[] = (int)$row['id'];
      }
      $stmt->close();
    }

    // Delete quizzes
    $stmt = $conn->prepare("DELETE FROM quizzes WHERE user_id=?");
    if ($stmt) {
      $stmt->bind_param("i", $user_id);
      $stmt->execute();
      $stmt->close();
    }

    // Delete progress
    $stmt = $conn->prepare("DELETE FROM progress WHERE user_id=?");
    if ($stmt) {
      $stmt->bind_param("i", $user_id);
      $stmt->execute();
      $stmt->close();
    }

    // Delete plan_days by plan_id
    if (!empty($planIds)) {
      $ids = implode(',', $planIds);
      $conn->query("DELETE FROM plan_days WHERE plan_id IN ($ids)");
    }

    // Delete plans
    $stmt = $conn->prepare("DELETE FROM plans WHERE user_id=?");
    if ($stmt) {
      $stmt->bind_param("i", $user_id);
      $stmt->execute();
      $stmt->close();
    }

    // Delete selected career
    $stmt = $conn->prepare("DELETE FROM user_career WHERE user_id=?");
    if ($stmt) {
      $stmt->bind_param("i", $user_id);
      $stmt->execute();
      $stmt->close();
    }

    $_SESSION['profile_message'] = [
      'type' => 'success',
      'text' => 'Your career, learning plan, quizzes, and progress have been deleted successfully.'
    ];

    header("Location: profile.php");
    exit();
  }
  if ($action === 'delete_holland_result') {

    // Get user's plan ids
    $planIds = [];
    $stmt = $conn->prepare("SELECT id FROM plans WHERE user_id=?");
    if ($stmt) {
      $stmt->bind_param("i", $user_id);
      $stmt->execute();
      $result = $stmt->get_result();
      while ($row = $result->fetch_assoc()) {
        $planIds[] = (int)$row['id'];
      }
      $stmt->close();
    }

    $stmt = $conn->prepare("DELETE FROM quizzes WHERE user_id=?");
    if ($stmt) {
      $stmt->bind_param("i", $user_id);
      $stmt->execute();
      $stmt->close();
    }

    $stmt = $conn->prepare("DELETE FROM progress WHERE user_id=?");
    if ($stmt) {
      $stmt->bind_param("i", $user_id);
      $stmt->execute();
      $stmt->close();
    }

    if (!empty($planIds)) {
      $ids = implode(',', $planIds);
      $conn->query("DELETE FROM plan_days WHERE plan_id IN ($ids)");
    }

    $stmt = $conn->prepare("DELETE FROM plans WHERE user_id=?");
    if ($stmt) {
      $stmt->bind_param("i", $user_id);
      $stmt->execute();
      $stmt->close();
    }

    $stmt = $conn->prepare("DELETE FROM user_career WHERE user_id=?");
    if ($stmt) {
      $stmt->bind_param("i", $user_id);
      $stmt->execute();
      $stmt->close();
    }

    // Delete Holland result
    $stmt = $conn->prepare("DELETE FROM hollandresult WHERE user_id=?");
    if ($stmt) {
      $stmt->bind_param("i", $user_id);
      $stmt->execute();
      $stmt->close();
    }

    $_SESSION['profile_message'] = [
      'type' => 'success',
      'text' => 'Your test result and all related data have been deleted successfully.'
    ];

    header("Location: profile.php");
    exit();
  }

  // -------------------------------------------------------------------------
  // process_manual_skill
  // هذا المسار خاص بالإضافة اليدوية للمهارات:
  // 1) يستقبل manual_skill من الواجهة
  // 2) يرسلها إلى n8n
  // 3) يستقبل النتائج
  // 4) يحفظها في قاعدة البيانات
  // -------------------------------------------------------------------------
  if ($action === 'process_manual_skill') {
    header('Content-Type: application/json; charset=utf-8');

    // المهارات اليدوية القادمة من الواجهة
    // قد تكون skill واحدة أو عدة مهارات مفصولة بـ ;
    $manual_skill = trim($_POST['manual_skill'] ?? '');

    if ($manual_skill === '') {
      http_response_code(400);
      echo json_encode([
        'status' => 'error',
        'message' => 'Manual skill is required.'
      ], JSON_UNESCAPED_UNICODE);
      exit();
    }

    $system_skills = getSystemSkillsFromCareers($conn);
    $webhookUrl = 'http://localhost:5678/webhook-test/extract_cv_skills';

    // إرسال المهارات اليدوية إلى n8n
    // source_type = manual لأن المصدر هنا هو الإدخال اليدوي
    $result = sendToN8nWebhook($webhookUrl, [
      'source_type' => 'manual',
      'user_id' => $user_id,
      'manual_skill' => $manual_skill,
      'system_skills' => $system_skills
    ]);

    if ($result['response'] === false || $result['response'] === '') {
      http_response_code(500);
      echo json_encode([
        'status' => 'error',
        'message' => 'Failed to send manual skill to n8n.',
        'curl_error' => $result['curlError']
      ], JSON_UNESCAPED_UNICODE);
if (!empty($result['curlError'])) {
  error_log($result['curlError']);
}
      exit();
    }

    // تحويل رد n8n إلى array
    $decoded = json_decode($result['response'], true);

    if (!is_array($decoded) || !isset($decoded['results']) || !is_array($decoded['results'])) {
      http_response_code(500);
      echo json_encode([
        'status' => 'error',
        'message' => 'Invalid response from n8n.',
        'raw_response' => $result['response']
      ], JSON_UNESCAPED_UNICODE);
      exit();
    }

    [$matchedSkills, $unmatchedSkills] = extractMatchedAndUnmatchedFromResults($decoded['results']);

    if (!saveSkillsToUserPersonalInfo($conn, $user_id, $matchedSkills, $unmatchedSkills)) {
      http_response_code(500);
      echo json_encode([
        'status' => 'error',
        'message' => 'Failed to save manual skill result to database.'
      ], JSON_UNESCAPED_UNICODE);
      exit();
    }

    http_response_code(200);
    echo json_encode([
      'status' => 'success',
      'matched_skills' => $matchedSkills,
      'unmatched_skills' => $unmatchedSkills
    ], JSON_UNESCAPED_UNICODE);
    exit();
  }

  // -------------------------------------------------------------------------
  // remove_saved_skill
  // يحذف مهارة من:
  // - user_skills
  // - other_skills
  // حسب اسم المهارة المختارة
  // -------------------------------------------------------------------------
  if ($action === 'remove_saved_skill') {
    $skill_name = trim($_POST['skill_name'] ?? '');

    if ($skill_name !== '') {
      $currentUserSkillsText = '';
      $currentOtherSkillsText = '';

      $stmt = $conn->prepare("SELECT user_skills, other_skills FROM userpersonalinfo WHERE user_id=? LIMIT 1");
      if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($db_user_skills, $db_other_skills);

        if ($stmt->fetch()) {
          $currentUserSkillsText = $db_user_skills ?? '';
          $currentOtherSkillsText = $db_other_skills ?? '';
        }
        $stmt->close();
      }

      $userSkills = parseSkillsText($currentUserSkillsText);
      $otherSkills = parseSkillsText($currentOtherSkillsText);

      $updatedUserSkills = array_values(array_filter(
        $userSkills,
        fn($s) => strcasecmp(trim($s), $skill_name) !== 0
      ));

      $updatedOtherSkills = array_values(array_filter(
        $otherSkills,
        fn($s) => strcasecmp(trim($s), $skill_name) !== 0
      ));

      $updatedUserSkillsText = skillsToText($updatedUserSkills);
      $updatedOtherSkillsText = skillsToText($updatedOtherSkills);

      $updateStmt = $conn->prepare("UPDATE userpersonalinfo SET user_skills=?, other_skills=? WHERE user_id=?");
      if ($updateStmt) {
        $updateStmt->bind_param("ssi", $updatedUserSkillsText, $updatedOtherSkillsText, $user_id);
        $updateStmt->execute();
        $updateStmt->close();
      }
    }

    header("Location: profile.php");
    exit();
  }

  
}

/*
|--------------------------------------------------------------------------
| Load user account info
|--------------------------------------------------------------------------
| هذا القسم يجلب البيانات الأساسية من userlogininformation
| مثل الاسم والإيميل والحالة الدراسية لعرضها في البروفايل.
|--------------------------------------------------------------------------
*/
$email = '';
$full_name = $_SESSION['full_name'] ?? 'Your Name';
$academic_status = $_SESSION['academic_status'] ?? 'Student';

$stmt = $conn->prepare("SELECT email, username, academic_status FROM userlogininformation WHERE user_id=? LIMIT 1");
if ($stmt) {
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $stmt->bind_result($db_email, $db_username, $db_status);

  if ($stmt->fetch()) {
    $email = $db_email ?? '';
    $full_name = $db_username ?? $full_name;
    $academic_status = $db_status ?? $academic_status;
  }
  $stmt->close();
}
// -----------------------------------------------------------------------------
// Load Real Holland Results & Career Match Info
// -----------------------------------------------------------------------------
$avatar_path = $_SESSION['avatar_path'] ?? '';
$selected_career = 'No career selected yet';
$holland_code = 'N/A';
$career_summary = "Please take the Holland test to generate your career summary.";
$test_full_result = "No results found.";
$saved_skills = [];
$cv_file_name = 'No CV uploaded yet';
$cv_file_path = '';


// 1. جلب أحدث نتيجة من جدول hollandresult
$stmtResult = $conn->prepare("
    SELECT top3_code, summary_text 
    FROM hollandresult 
    WHERE user_id = ? 
    ORDER BY result_id DESC 
    LIMIT 1
");

if ($stmtResult) {
  $stmtResult->bind_param("i", $user_id);
  $stmtResult->execute();
  $stmtResult->bind_result($db_code, $db_summary);

  if ($stmtResult->fetch()) {
    $holland_code = !empty($db_code) ? $db_code : 'N/A';
    $career_summary = !empty($db_summary) ? $db_summary : "Summary not available.";
  }
  $stmtResult->close();
}

// 2. جلب اسم الوظيفة ونسبة المطابقة من جدول user_career
$stmtCareerMatch = $conn->prepare("
    SELECT career_title, holland_match 
    FROM user_career 
    WHERE user_id = ? 
    ORDER BY career_user_id DESC 
    LIMIT 1
");

if ($stmtCareerMatch) {
  $stmtCareerMatch->bind_param("i", $user_id);
  $stmtCareerMatch->execute();
  $stmtCareerMatch->bind_result($db_career_title, $db_match);

  if ($stmtCareerMatch->fetch()) {
    $selected_career = !empty($db_career_title) ? $db_career_title : 'No career selected yet';
    $holland_match_score = !empty($db_match) ? $db_match : 0;
  }
  $stmtCareerMatch->close();
}

// 3. تجهيز النص النهائي للـ Modal
$test_full_result = "Holland Code: " . $holland_code . "\n";
$test_full_result .= "-----------------------------------\n\n";
$test_full_result .= "AI Analysis Summary:\n" . $career_summary;

// 4. جلب بقية معلومات الملف الشخصي والـ CV من جدول userpersonalinfo
$stmtInfo = $conn->prepare("
    SELECT user_skills, other_skills, cv_file_name, cv_file_path
    FROM userpersonalinfo
    WHERE user_id=?
    LIMIT 1
");

if ($stmtInfo) {
  $stmtInfo->bind_param("i", $user_id);
  $stmtInfo->execute();
  $stmtInfo->bind_result($db_skills, $db_other_skills, $db_cv_file_name, $db_cv_file_path);

  if ($stmtInfo->fetch()) {

    $user_skills_array = parseSkillsText($db_skills ?? '');
    $other_skills_array = parseSkillsText($db_other_skills ?? '');
    $saved_skills = array_values(array_unique(array_merge($user_skills_array, $other_skills_array)));
    sort($saved_skills);

    if (!empty($db_cv_file_name)) {
      $cv_file_name = $db_cv_file_name;
    }
    if (!empty($db_cv_file_path)) {
      $cv_file_path = $db_cv_file_path;
    }
  }
  $stmtInfo->close();
}
$has_saved_skills = !empty($saved_skills);

// التحقق هل هناك صورة شخصية موجودة أم لا
$avatar_exists = ($avatar_path && file_exists(__DIR__ . "/" . $avatar_path));




// رسالة مؤقتة تظهر للمستخدم بعد أي عملية نجاح أو فشل
$flash = $_SESSION['profile_message'] ?? null;
unset($_SESSION['profile_message']);

$initials = "U";
if ($full_name && $full_name !== "Your Name") {
  $parts = preg_split('/\s+/', trim($full_name));
  $initials = strtoupper(substr($parts[0] ?? 'U', 0, 1) . substr($parts[1] ?? '', 0, 1));
  $initials = trim($initials) ?: "U";
}
?>
<!--
===============================================================================
واجهة البروفايل
هذا الجزء هو HTML/CSS/JS الخاص بعرض الصفحة للمستخدم
===============================================================================
-->
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>GuideMe | Profile</title>
  <style>
    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background:
        radial-gradient(circle at top left, rgba(101, 67, 92, 0.12), transparent 35%),
        #f4f8ff;
      color: #1e293b;
    }

    /* Navbar */
    .topbar {
      position: sticky;
      top: 0;
      z-index: 9999;
      background: rgba(255, 255, 255, 0.92);
      backdrop-filter: blur(10px);
      border-bottom: 1px solid #e5edff;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 18px 6%;
      gap: 20px;
      flex-wrap: wrap;
    }

    .brand {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 26px;
      font-weight: 800;
      color: #65435c;
    }

    .dot {
      width: 34px;
      height: 34px;
      border-radius: 50%;
      background: #65435c;
      display: inline-block;
    }

    .nav {
      display: flex;
      gap: 24px;
      flex-wrap: wrap;
      align-items: center;
    }

    .nav a {
      text-decoration: none;
      color: #475569;
      font-size: 15px;
      font-weight: 600;
      padding: 9px 12px;
      border-radius: 999px;
      transition: .2s;
    }

    .nav a:hover,
    .nav a.active {
      background: #f4eef3;
      color: #65435c;
    }

    .nav a.logout {
      background: #65435c;
      color: #fff;
      padding: 10px 18px;
    }

    .nav a.logout:hover {
      background: #54354c;
      color: #fff;
    }

    /* Page */
    .wrap {
      max-width: 1120px;
      margin: 42px auto 60px;
      padding: 0 24px;
    }

    /* Header Card */
    .profile-header {
      background: #ffffff;
      border: 1px solid #e5edff;
      border-radius: 28px;
      box-shadow: 0 14px 35px rgba(15, 23, 42, 0.07);
      padding: 36px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 24px;
      flex-wrap: wrap;
    }

    .header-left {
      display: flex;
      align-items: center;
      gap: 20px;
    }

    .avatar {
      width: 96px;
      height: 96px;
      border-radius: 50%;
      background: #f4eef3;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      border: 1px solid #eadce7;
      flex-shrink: 0;
    }

    .avatar img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    .initials {
      font-weight: 900;
      color: #65435c;
      font-size: 32px;
    }

    .name {
      font-size: 32px;
      font-weight: 800;
      margin: 0 0 8px;
      color: #1e293b;
    }

    .email {
      color: #64748b;
      font-size: 14px;
      margin: 0 0 6px;
    }

    .status-text {
      color: #334155;
      font-size: 14px;
      font-weight: 700;
      margin: 0 0 8px;
    }

    .subline {
      color: #64748b;
      font-size: 14px;
      line-height: 1.7;
      margin: 0;
    }

    .header-actions {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
    }

    /* Buttons */
    .btn {
      display: inline-block;
      background: #65435c;
      color: #fff;
      padding: 12px 20px;
      border-radius: 999px;
      text-decoration: none;
      font-weight: 800;
      font-size: 14px;
      border: none;
      cursor: pointer;
      transition: .2s;
      box-shadow: 0 10px 22px rgba(101, 67, 92, .16);
    }

    .btn:hover {
      transform: translateY(-1px);
      box-shadow: 0 14px 28px rgba(101, 67, 92, .22);
    }

    .btn.secondary {
      background: #f4eef3;
      color: #65435c;
      box-shadow: none;
    }

    .btn.light {
      background: #f1f5f9;
      color: #334155;
      box-shadow: none;
    }

    .btn.danger {
      background: #fff1f2;
      color: #be123c;
      box-shadow: none;
    }

    .btn.small {
      padding: 8px 14px;
      font-size: 13px;
    }

    .btn.disabled {
      opacity: .55;
      cursor: not-allowed;
      transform: none;
    }

    /* Layout */
    .grid {
      display: grid;
      grid-template-columns: 1.05fr .95fr;
      gap: 22px;
      margin-top: 22px;
    }

    .left-column,
    .right-column {
      display: grid;
      gap: 22px;
      align-content: start;
    }

    /* Cards */
    .card,
    .edit-panel {
      background: #ffffff;
      border: 1px solid #e5edff;
      border-radius: 26px;
      box-shadow: 0 14px 35px rgba(15, 23, 42, 0.07);
      padding: 26px;
    }

    .edit-panel {
      display: none;
      margin-top: 22px;
    }

    .edit-panel.active {
      display: block;
    }

    .card-title {
      margin: 0 0 16px;
      font-size: 21px;
      color: #65435c;
      font-weight: 900;
    }

    .card-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 16px;
      flex-wrap: wrap;
    }

    /* Career */
    .career-box,
    .test-summary,
    .cv-box {
      background: #fbfdff;
      border: 1px solid #e5edff;
      padding: 18px;
      border-radius: 20px;
    }

    .career-top {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      flex-wrap: wrap;
    }

    .career-name {
      font-size: 25px;
      font-weight: 900;
      margin: 0;
      color: #1e293b;
    }

    .test-code {
      font-size: 30px;
      font-weight: 900;
      margin: 0 0 8px;
      color: #65435c;
    }

    .actions-row {
      margin-top: 16px;
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }

    /* CV status */
    .cv-box {
      border-style: dashed;
    }

    .status-row {
      display: grid;
      gap: 10px;
    }

    .status-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      border-bottom: 1px solid #edf2ff;
      padding-bottom: 10px;
    }

    .status-item:last-child {
      border-bottom: none;
      padding-bottom: 0;
    }

    .status-label {
      font-size: 13px;
      color: #64748b;
      font-weight: 800;
    }

    .status-value {
      font-size: 14px;
      color: #1e293b;
      font-weight: 900;
      text-align: right;
    }

    /* Skills */
    .skills-wrap {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
    }

    .skill-chip,
    .manual-preview-chip {
      display: flex;
      align-items: center;
      gap: 8px;
      background: #f4eef3;
      color: #65435c;
      border: 1px solid #eadce7;
      border-radius: 999px;
      padding: 9px 13px;
      font-size: 13px;
      font-weight: 800;
    }
    .skill-chip form {
      margin: 0;
    }
    .remove-chip-btn {
      border: none;
      background: transparent;
      cursor: pointer;
      font-size: 15px;
      font-weight: 900;
      line-height: 1;
      padding: 0;
      color: inherit;
    }

    .section-subtitle {
      font-size: 13px;
      font-weight: 900;
      color: #334155;
      margin: 0 0 12px;
    }

    .stack {
      display: grid;
      gap: 18px;
    }

    /* Inputs */
    .search-box input,
    .manual-box input,
    .edit-form input,
    .edit-form select {
      width: 100%;
      padding: 14px 15px;
      border: 1px solid #dbe4f5;
      border-radius: 16px;
      font-size: 14px;
      outline: none;
      background: #fff;
    }

    .search-box input:focus,
    .manual-box input:focus,
    .edit-form input:focus,
    .edit-form select:focus {
      border-color: #65435c;
      box-shadow: 0 0 0 4px rgba(101, 67, 92, .08);
    }

    .edit-form {
      display: grid;
      gap: 16px;
    }

    .edit-label {
      font-size: 13px;
      font-weight: 900;
      color: #334155;
      margin-bottom: 8px;
      display: block;
    }

    /* Skills popup list */
    .skills-list {
      margin-top: 10px;
      max-height: 240px;
      overflow: auto;
      border: 1px solid #e5edff;
      border-radius: 18px;
      background: #fbfdff;
      padding: 8px;
    }

    .skill-option {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 10px;
      padding: 11px 10px;
      border-bottom: 1px solid #edf2ff;
    }

    .skill-option:last-child {
      border-bottom: none;
    }

    .skill-option-name {
      font-size: 14px;
      font-weight: 800;
      color: #1e293b;
    }

    #manualSkillsPreview:empty {
      display: none;
    }

    .manual-preview-chip button {
      border: none;
      background: transparent;
      color: inherit;
      cursor: pointer;
      font-size: 14px;
      font-weight: 900;
      line-height: 1;
      padding: 0;
    }

    /* Modal */
    .modal-backdrop {
      position: fixed;
      inset: 0;
      background: rgba(15, 23, 42, .45);
      display: none;
      align-items: center;
      justify-content: center;
      padding: 20px;
      z-index: 99999;
    }

    .modal {
      width: 100%;
      max-width: 620px;
      background: #fff;
      border: 1px solid #e5edff;
      border-radius: 28px;
      box-shadow: 0 24px 70px rgba(15, 23, 42, .22);
      padding: 28px;
    }

    .modal h3 {
      margin: 0 0 14px;
      font-size: 23px;
      font-weight: 900;
      color: #65435c;
    }

    .modal pre {
      background: #fbfdff;
      border: 1px solid #e5edff;
      border-radius: 18px;
      padding: 18px;
      white-space: pre-wrap;
      font-family: Arial, sans-serif;
      font-size: 14px;
      line-height: 1.8;
      color: #334155;
      max-height: 400px;
      overflow: auto;
      margin: 0;
    }

    .modal-actions {
      margin-top: 18px;
      display: flex;
      justify-content: flex-end;
    }

    /* Message */
    .message {
      margin-top: 10px;
      padding: 13px 15px;
      border-radius: 16px;
      font-size: 13px;
      font-weight: 800;
      display: none;
    }

    .message.success {
      display: block;
      background: #eefaf1;
      color: #1f7a3e;
      border: 1px solid #cbe8d3;
    }

    .message.error {
      display: block;
      background: #fff1f1;
      color: #b42318;
      border: 1px solid #f3c7c7;
    }

    @media (max-width:980px) {
      .grid {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width:700px) {
      .topbar {
        padding: 16px 20px;
      }

      .profile-header {
        padding: 24px 20px;
      }

      .header-left {
        align-items: flex-start;
      }

      .name {
        font-size: 25px;
      }

      .card,
      .edit-panel {
        padding: 20px;
      }
    }
  </style>
</head>

<body>
  <?php include 'navbar.php'; ?>
  <?php include 'career_guidance_bot/chat_widget.php'; ?>

  <main class="wrap">

    <?php if ($flash): ?>
      <div class="message <?php echo htmlspecialchars($flash['type']); ?>" style="display:block; margin-bottom:16px;">
        <?php echo htmlspecialchars($flash['text']); ?>
      </div>
    <?php endif; ?>

    <section class="profile-header">
      <div class="header-left">
        <div class="avatar">
          <?php if ($avatar_exists): ?>
            <img src="<?php echo htmlspecialchars($avatar_path); ?>" alt="Profile Photo">
          <?php else: ?>
            <div class="initials"><?php echo htmlspecialchars($initials); ?></div>
          <?php endif; ?>
        </div>

        <div>
          <h1 class="name"><?php echo htmlspecialchars($full_name); ?></h1>
          <p class="email"><?php echo htmlspecialchars($email); ?></p>
          <p class="status-text">Academic Status: <?php echo htmlspecialchars($academic_status); ?></p>
          <p class="subline">Your profile, skills, CV, and test setup are managed here.</p>
        </div>
      </div>

      <div class="header-actions">
        <button class="btn secondary" type="button" onclick="toggleEditPanel()">Edit Profile</button>
      </div>
    </section>

    <section class="edit-panel" id="editPanel">
      <h2 class="card-title" style="margin-bottom:16px;">Edit Profile</h2>

      <form method="POST" class="edit-form">
        <input type="hidden" name="action" value="save_profile">

        <div>
          <label class="edit-label" for="full_name">Full Name</label>
          <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($full_name); ?>" required>
        </div>

        <div>
          <label class="edit-label" for="academic_status">Academic Status</label>
          <select id="academic_status" name="academic_status" required>
            <option value="Student" <?php echo $academic_status === 'Student' ? 'selected' : ''; ?>>Student</option>
            <option value="Graduate" <?php echo $academic_status === 'Graduate' ? 'selected' : ''; ?>>Graduate</option>
          </select>
        </div>

        <div class="actions-row" style="margin-top:4px;">
          <button class="btn" type="submit">Save Changes</button>
          <button class="btn light" type="button" onclick="toggleEditPanel()">Cancel</button>
        </div>
      </form>
    </section>

    <div class="grid">
      <div class="left-column">

        <section class="card">
          <h2 class="card-title">Career Overview</h2>

          <div class="career-box">
            <div class="career-top">
              <p class="career-name"><?php echo htmlspecialchars($selected_career); ?></p>

            </div>


          </div>

          <div class="actions-row">

            <?php if ($selected_career !== 'No career selected yet'): ?>

              <button class="btn primary" type="button"
                onclick="alert('You already have a selected career. Please delete it first before choosing a new one.')">
                Choose Career
              </button>

            <?php elseif ($holland_code === 'N/A'): ?>

              <button class="btn primary" type="button"
                onclick="alert('Please complete the Holland test first before choosing a career.')">
                Choose Career
              </button>

           <?php else: ?>

  <?php if (!$has_saved_skills): ?>
    <a href="career_choice.php" class="btn"
      onclick="return confirm('You have not added any skills yet.\n\nAdding skills helps the system provide more accurate career suggestions and skill gap analysis.\n\nPress OK to continue choosing a career without skills.\nPress Cancel to go back and add skills.');">
      Choose Career
    </a>
  <?php else: ?>
    <a href="career_choice.php" class="btn">Choose Career</a>
  <?php endif; ?>
  <?php endif; ?>

            <form method="POST" onsubmit="return confirm('Deleting your career will also delete your learning plan, quizzes, and progress. Your Holland test result will remain saved. Do you want to continue?');">
              <input type="hidden" name="action" value="delete_my_career">
              <button class="btn secondary">Delete My Career</button>
            </form>

          </div>
        </section>

        <section class="card">
          <h2 class="card-title">Test Result</h2>

          <div class="test-summary">
            <p class="test-code"><?php echo htmlspecialchars($holland_code); ?></p>
          </div>

          <div class="actions-row">
            <button class="btn primary" type="button" onclick="openResultModal()">View Full Result</button>
            <form method="POST" onsubmit="return confirm('Deleting your test result will also delete your career, learning plan, quizzes, and progress. Do you want to continue?');">
              <input type="hidden" name="action" value="delete_holland_result">
              <button class="btn secondary">Delete Result</button>
            </form>
          </div>

        </section>


      </div>

      <div class="right-column">
        <!-- ===================================================================
           قسم My Skills
           - Saved Skills        : جميع المهارات المحفوظة
           
           =================================================================== -->
        <section class="card">
          <div class="card-head">
            <h2 class="card-title" style="margin:0;">My Skills</h2>
            <button class="btn secondary" type="button" onclick="openSkillsModal()">+ Add Skill</button>
          </div>

          <div id="skillMessage" class="message"></div>

          <div class="stack">
            <div>

              <div class="skills-wrap" id="savedSkillsWrap">
                <?php if (!empty($saved_skills)): ?>
                  <?php foreach ($saved_skills as $skill): ?>
                    <span class="skill-chip">
                      <?php echo htmlspecialchars($skill); ?>
                      <form method="POST" onsubmit="return confirm('Remove this skill?');">
                        <input type="hidden" name="action" value="remove_saved_skill">
                        <input type="hidden" name="skill_name" value="<?php echo htmlspecialchars($skill); ?>">
                        <button class="remove-chip-btn" type="submit">&times;</button>
                      </form>
                    </span>
                  <?php endforeach; ?>
                <?php else: ?>
                  <p class="empty-note" id="savedSkillsEmpty">No saved skills yet.</p>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </section>

        <!-- ===================================================================
           قسم CV & Skill Extraction
           - يعرض آخر ملف CV
           - يحتوي زر رفع السيفي وزر استخراج المهارات
           =================================================================== -->
        <section class="card">
          <h2 class="card-title">Extract Skills from CV</h2>

          <div class="cv-box">
            <div class="status-row">
              <div class="status-item">

                <span class="status-label">Latest CV</span>
                <span class="status-value"><?php echo htmlspecialchars($cv_file_name); ?></span>
              </div>
            </div>

            <form method="POST" enctype="multipart/form-data" id="cvUploadForm" style="display:none;">
              <input type="hidden" name="action" value="upload_cv">
              <input type="file" name="cv_file" id="cvFileInput" accept=".pdf" onchange="document.getElementById('cvUploadForm').submit()">
            </form>

            <form method="POST" id="extractCvForm" style="display:none;">
              <input type="hidden" name="action" value="extract_cv_text">
            </form>

            <div class="actions-row" style="margin-top:16px;">
              <button class="btn" type="button" onclick="document.getElementById('cvFileInput').click()">Upload / Replace CV</button>
             <button class="btn secondary" type="button"
  onclick="this.disabled=true; this.textContent='Extracting...'; document.getElementById('extractCvForm').submit();">
  Extract Skills
</button>
            </div>
          </div>
        </section>

      </div>
    </div>
    <div style="margin-top:30px; text-align:center;">
      <a href="auth/logout.php" class="btn danger">Logout</a>
    </div>
  </main>

  <div class="modal-backdrop" id="resultModal">
    <div class="modal">
      <h3>Full Test Result</h3>
      <pre><?php echo htmlspecialchars($test_full_result); ?></pre>
      <div class="modal-actions">
        <button class="btn secondary" type="button" onclick="closeResultModal()">Close</button>
      </div>
    </div>
  </div>

  <div class="modal-backdrop" id="skillsModal">
    <div class="modal">
      <h3>Add Skill</h3>

      <div class="stack">
        <div>
          <p class="section-subtitle">Search from career skills</p>
          <div class="search-box">
            <input type="text" id="skillSearchPopup" placeholder="Search for a skill..." onkeyup="filterPopupSkills()">
          </div>

          <div class="skills-list" id="skillsPopupContainer">
            <p class="empty-note">Loading skills...</p>
          </div>
        </div>

        <div>
          <p class="section-subtitle">Add manually</p>
          <div class="manual-box">
            <input type="text" id="manualSkill" placeholder="Type a skill then press Enter, comma, or semicolon">
          </div>

          <div class="skills-wrap" id="manualSkillsPreview" style="margin-top:10px;"></div>

          <div class="actions-row">
            <button class="btn" type="button" onclick="addManualSkill()">Add New Skill</button>
          </div>

          <div class="helper-text">
            You can add more than one skill at once. Type a skill, then press Enter, comma, or semicolon to add it as a separate tag before sending.
          </div>
        </div>
      </div>

      <div class="modal-actions">
        <button class="btn secondary" type="button" onclick="closeSkillsModal()">Close</button>
      </div>
    </div>
  </div>

  <script>
    let allCareerSkills = [];
    let savedSkills = <?php echo json_encode(array_values($saved_skills ?? [])); ?>;

    
    // فتح نافذة عرض نتيجة الاختبار
    function openResultModal() {
      document.getElementById("resultModal").style.display = "flex";
    }
    // إغلاق نافذة نتيجة الاختبار
    function closeResultModal() {
      document.getElementById("resultModal").style.display = "none";
    }
    // فتح نافذة إضافة المهارات
    function openSkillsModal() {
      document.getElementById("skillsModal").style.display = "flex";
      if (allCareerSkills.length === 0) {
        loadCareerSkills();
      }
    }
    // إغلاق نافذة إضافة المهارات
    function closeSkillsModal() {
      document.getElementById("skillsModal").style.display = "none";
    }

    // فتح/إغلاق لوحة تعديل البروفايل
    function toggleEditPanel() {
      document.getElementById("editPanel").classList.toggle("active");
    }

    function showSkillMessage(text, type = "success") {
      const box = document.getElementById("skillMessage");
      box.className = "message " + type;
      box.textContent = text;
      setTimeout(() => {
        box.className = "message";
        box.textContent = "";
      }, 3000);
    }

    

    function loadCareerSkills() {
      const container = document.getElementById("skillsPopupContainer");
      container.innerHTML = '<p class="empty-note">Loading skills...</p>';

      fetch("get_skills_to_profile.php")
        .then(response => response.json())
        .then(data => {
          allCareerSkills = Array.isArray(data) ? data : [];
          renderSkills(allCareerSkills);
        })
        .catch(() => {
          container.innerHTML = '<p class="empty-note">Failed to load skills.</p>';
        });
    }

    function renderSkills(skills) {
      const container = document.getElementById("skillsPopupContainer");

      if (!skills.length) {
        container.innerHTML = '<p class="empty-note">No skills found.</p>';
        return;
      }

      let html = "";

      skills.forEach(skill => {
        const added = savedSkills.some(s => s.toLowerCase() === skill.toLowerCase());

        html += `
      <div class="skill-option">
        <span class="skill-option-name">${escapeHtml(skill)}</span>
        <button
          class="btn secondary btn.small ${added ? 'disabled' : ''}"
          type="button"
          ${added ? 'disabled' : ''}
          onclick="addSkill('${encodeURIComponent(skill)}')">
          ${added ? 'Added' : 'Add'}
        </button>
      </div>
    `;
      });

      container.innerHTML = html;
    }

    function addSkill(skillEncoded) {
      const skill = decodeURIComponent(skillEncoded);

      fetch("save_user_skill.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded"
          },
          body: "skill=" + encodeURIComponent(skill)
        })
        .then(res => res.text())
        .then(result => {
          const clean = result.trim();

          if (clean === "success" || clean === "exists") {
            if (!savedSkills.some(s => s.toLowerCase() === skill.toLowerCase())) {
              savedSkills.push(skill);
              addSkillToUI(skill);
            }

            renderSkills(allCareerSkills);
            filterPopupSkills();
            showSkillMessage(clean === "exists" ? "Skill already exists." : "Skill added successfully.");
          } else {
            showSkillMessage("Failed to add skill.", "error");
          }
        })
        .catch(() => {
          showSkillMessage("Failed to add skill.", "error");
        });
    }
    // إضافة مهارة إلى الواجهة مباشرة (واجهة فقط)
    function addSkillToUI(skill) {
      const wrap = document.getElementById("savedSkillsWrap");
      const emptyNote = document.getElementById("savedSkillsEmpty");

      if (emptyNote) {
        emptyNote.remove();
      }

      const chip = document.createElement("span");
      chip.className = "skill-chip";

      chip.innerHTML = `
    ${escapeHtml(skill)}
    <form method="POST" onsubmit="return confirm('Remove this skill?');">
      <input type="hidden" name="action" value="remove_saved_skill">
      <input type="hidden" name="skill_name" value="${escapeHtml(skill)}">
      <button class="remove-chip-btn" type="submit">&times;</button>
    </form>
  `;

      wrap.appendChild(chip);
    }
    // فلترة المهارات داخل نافذة البحث
    function filterPopupSkills() {
      const input = document.getElementById("skillSearchPopup");
      const filter = input.value.toLowerCase().trim();
      const rows = document.querySelectorAll(".skill-option");

      rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(filter) ? "flex" : "none";
      });
    }

    // -----------------------------------------------------------------------------
    // دوال خاصة بتحويل المهارات اليدوية إلى bubbles / tags قبل الإرسال
    // -----------------------------------------------------------------------------
    function escapeHtml(value) {
      return String(value)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
    }

    const pendingManualSkills = [];

    function normalizeManualSkillValue(value) {
      return value.replace(/\s+/g, ' ').trim();
    }

    function renderManualSkillPreview() {
      const wrap = document.getElementById("manualSkillsPreview");
      if (!wrap) return;

      wrap.innerHTML = "";

      pendingManualSkills.forEach((skill, index) => {
        const chip = document.createElement("span");
        chip.className = "manual-preview-chip";
        chip.innerHTML = `${escapeHtml(skill)} <button type="button" onclick="removePendingManualSkill(${index})">&times;</button>`;
        wrap.appendChild(chip);
      });
    }

    function removePendingManualSkill(index) {
      pendingManualSkills.splice(index, 1);
      renderManualSkillPreview();
    }

    function collectManualSkillsFromInput() {
      const input = document.getElementById("manualSkill");
      if (!input) return;

      const raw = input.value.trim();
      if (!raw) return;

      const parts = raw
        .split(/[;,\n]/)
        .map(normalizeManualSkillValue)
        .filter(Boolean);

      parts.forEach((skill) => {
        const exists = pendingManualSkills.some(s => s.toLowerCase() === skill.toLowerCase());
        if (!exists) {
          pendingManualSkills.push(skill);
        }
      });

      input.value = "";
      renderManualSkillPreview();
    }

    function setupManualSkillInput() {
      const input = document.getElementById("manualSkill");
      if (!input) return;

      input.addEventListener("keydown", function(e) {
        if (e.key === "Enter" || e.key === "," || e.key === ";") {
          e.preventDefault();
          collectManualSkillsFromInput();
        }
      });

      input.addEventListener("blur", function() {
        collectManualSkillsFromInput();
      });
    }

    // إرسال المهارات اليدوية إلى الـ backend ثم حفظها وعرضها
    function addManualSkill() {
      collectManualSkillsFromInput();

      if (pendingManualSkills.length === 0) {
        alert("Please type at least one skill first.");
        return;
      }

      const joinedSkills = pendingManualSkills.join(";");

      fetch("profile.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded"
          },
          body: "action=process_manual_skill&manual_skill=" + encodeURIComponent(joinedSkills)
        })
        .then(async (res) => {
          const text = await res.text();
          try {
            return JSON.parse(text);
          } catch (e) {
            throw new Error(text || "Invalid JSON response");
          }
        })
        .then(data => {
          console.log("Manual skill webhook response:", data);

          if (data.status !== "success") {
            throw new Error(data.message || "Failed to save manual skills.");
          }

          pendingManualSkills.length = 0;
          renderManualSkillPreview();

          const input = document.getElementById("manualSkill");
          if (input) input.value = "";

          showSkillMessage("Manual skills added successfully.");
          closeSkillsModal();
          window.location.reload();
        })
        .catch((err) => {
          console.error(err);
          showSkillMessage("Failed to add manual skills.", "error");
        });
    }

    window.addEventListener("click", function(e) {
      const resultModal = document.getElementById("resultModal");
      const skillsModal = document.getElementById("skillsModal");

      if (e.target === resultModal) closeResultModal();
      if (e.target === skillsModal) closeSkillsModal();
    });

    setupManualSkillInput();
  </script>
</body>

</html>