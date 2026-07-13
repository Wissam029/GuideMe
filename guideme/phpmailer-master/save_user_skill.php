<?php
require_once __DIR__ . "/config/secure_session.php";

require_once __DIR__ . "/config/db.php";

$user_id = $_SESSION['user_id'] ?? 0;
$newSkill = trim($_POST['skill'] ?? '');
$isManual = ($_POST['type'] ?? '') === 'manual';
$column = $isManual ? 'other_skills' : 'user_skills';

if (!$user_id) exit('not_logged_in');
if ($newSkill === '') exit('empty_skill');

function parseSkillsText(?string $skillsText): array {
    if (!$skillsText) return [];

    $skills = explode(';', $skillsText);
    $cleaned = [];

    foreach ($skills as $skill) {
        $skill = trim($skill);
        if ($skill !== '') {
            $cleaned[] = $skill;
        }
    }

    return array_values(array_unique($cleaned));
}

function skillsToText(array $skills): string {
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

$db_user_skills = '';
$db_other_skills = '';
$rowExists = false;

$stmt = $conn->prepare("SELECT user_skills, other_skills FROM userpersonalinfo WHERE user_id=? LIMIT 1");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($f_user_skills, $f_other_skills);

    if ($stmt->fetch()) {
        $rowExists = true;
        $db_user_skills = $f_user_skills ?? '';
        $db_other_skills = $f_other_skills ?? '';
    }
    $stmt->close();
}

$currentSkillsText = $isManual ? $db_other_skills : $db_user_skills;
$currentSkills = parseSkillsText($currentSkillsText);

foreach ($currentSkills as $skill) {
    if (strcasecmp($skill, $newSkill) === 0) {
        exit('exists');
    }
}

$currentSkills[] = $newSkill;
$updatedText = skillsToText($currentSkills);

if ($rowExists) {
    $sql = "UPDATE userpersonalinfo SET $column=? WHERE user_id=?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("si", $updatedText, $user_id);
        $ok = $stmt->execute();
        $stmt->close();
        echo $ok ? 'success' : 'error';
        exit;
    }
} else {
    $userSkillsValue = $isManual ? '' : $updatedText;
    $otherSkillsValue = $isManual ? $updatedText : '';

    $stmt = $conn->prepare("INSERT INTO userpersonalinfo (user_id, user_skills, other_skills) VALUES (?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iss", $user_id, $userSkillsValue, $otherSkillsValue);
        $ok = $stmt->execute();
        $stmt->close();
        echo $ok ? 'success' : 'error';
        exit;
    }
}

echo 'error';