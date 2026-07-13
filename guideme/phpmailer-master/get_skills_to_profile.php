<?php

require_once __DIR__ . "/config/db.php";

$allSkills = [];

$sql = "SELECT beginner_level, intermediate_level FROM careers";
$result = mysqli_query($conn, $sql);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
       $beginner_level = preg_split('/\s*-\s*/', $row['beginner_level'] ?? '');
$intermediate_level = preg_split('/\s*-\s*/', $row['intermediate_level'] ?? '');
        $skills = array_merge($beginner_level, $intermediate_level);

        foreach ($skills as $skill) {
            $skill = strtolower(trim($skill));
            if ($skill !== '') {
                $allSkills[] = $skill;
            }
        }
    }
}

$allSkills = array_values(array_unique($allSkills));
sort($allSkills);

header('Content-Type: application/json');
echo json_encode($allSkills);