<?php
session_start();
require "config/db.php";

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT career_title, overall_match, holland_match, skills_match, strengths, gaps, selected_at
    FROM user_career
    WHERE user_id = ?
    ORDER BY selected_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$careers = [];
while ($row = $result->fetch_assoc()) {
    $careers[] = $row;
}

function decodeJsonArray($json) {
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

function listToHtml($array) {
    if (empty($array)) {
        return "<span class='empty'>None</span>";
    }

    $html = "<ul>";
    foreach ($array as $item) {
        $html .= "<li>" . htmlspecialchars($item) . "</li>";
    }
    $html .= "</ul>";

    return $html;
}

function isSkillCompleted($conn, $user_id, $skill) {
    $stmt = $conn->prepare("
        SELECT 
            p.id AS plan_id,
            COUNT(pd.id) AS total_days,
            SUM(CASE WHEN pr.id IS NOT NULL THEN 1 ELSE 0 END) AS completed_days,
            MAX(q.score) AS best_score
        FROM plans p
        JOIN plan_days pd ON p.id = pd.plan_id
        LEFT JOIN progress pr 
            ON pd.id = pr.plan_day_id 
            AND pr.user_id = ?
        LEFT JOIN quizzes q
            ON q.plan_id = p.id
            AND q.user_id = p.user_id
        WHERE p.user_id = ?
          AND LOWER(TRIM(p.skill)) = LOWER(TRIM(?))
        GROUP BY p.id
        ORDER BY p.id DESC
        LIMIT 1
    ");

    $stmt->bind_param("iis", $user_id, $user_id, $skill);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        $totalDays = (int) $row['total_days'];
        $completedDays = (int) $row['completed_days'];
        $bestScore = (int) $row['best_score'];

        $planCompleted = ($totalDays > 0 && $completedDays >= $totalDays);
        $quizPassed = ($bestScore >= 80);

        return $planCompleted && $quizPassed;
    }

    return false;
}

function getDynamicCareerData($conn, $user_id, $strengths_json, $gaps_json) {
    $strengths = decodeJsonArray($strengths_json);
    $gaps = decodeJsonArray($gaps_json);

    $updatedStrengths = $strengths;
    $updatedGaps = [];

    foreach ($gaps as $skill) {
        if (isSkillCompleted($conn, $user_id, $skill)) {
            if (!in_array($skill, $updatedStrengths)) {
                $updatedStrengths[] = $skill;
            }
        } else {
            $updatedGaps[] = $skill;
        }
    }

    $totalSkills = count($strengths) + count($gaps);

    $skillsMatch = 0;
    if ($totalSkills > 0) {
        $skillsMatch = round((count($updatedStrengths) / $totalSkills) * 100);
    }

    return [
        "strengths" => $updatedStrengths,
        "gaps" => $updatedGaps,
        "skills_match" => $skillsMatch
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Career Path</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background:
        radial-gradient(circle at top left, rgba(101,67,92,0.12), transparent 35%),
        #f4f8ff;
    color: #1e293b;
}

.main-content {
    padding: 42px 24px 60px;
}

.container {
    max-width: 1120px;
    margin: auto;
}

.hero {
    background: #ffffff;
    border: 1px solid #e5edff;
    border-radius: 28px;
    padding: 36px;
    margin-bottom: 26px;
    box-shadow: 0 14px 35px rgba(15, 23, 42, 0.07);
    position: relative;
    overflow: hidden;
}

.hero::after {
    content: "";
    position: absolute;
    width: 190px;
    height: 190px;
    right: -70px;
    top: -70px;
    background: rgba(101, 67, 92, 0.12);
    border-radius: 50%;
}

.hero h1 {
    margin: 0 0 10px;
    font-size: 38px;
    color: #65435c;
}

.hero p {
    margin: 0;
    max-width: 620px;
    line-height: 1.7;
    color: #64748b;
}

.career-card {
    background: rgba(255,255,255,0.92);
    border: 1px solid #e5edff;
    border-radius: 26px;
    padding: 28px;
    margin-bottom: 24px;
    box-shadow: 0 12px 30px rgba(15, 23, 42, 0.07);
}

.career-top {
    display: flex;
    justify-content: space-between;
    gap: 20px;
    align-items: flex-start;
    margin-bottom: 22px;
}

.career-title {
    margin: 0;
    font-size: 28px;
    color: #331a4d;
}

.date {
    color: #64748b;
    font-size: 14px;
    margin-top: 8px;
}

.match-badge {
    background: #65435c;
    color: #ffffff;
    padding: 13px 20px;
    border-radius: 999px;
    font-weight: 700;
    white-space: nowrap;
}

.stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}

.stat-box {
    background: #f8fbff;
    border: 1px solid #e5edff;
    border-radius: 20px;
    padding: 20px;
    text-align: center;
}

.stat-box h3 {
    margin: 0;
    font-size: 30px;
    color: #65435c;
}

.stat-box p {
    margin: 7px 0 0;
    color: #64748b;
    font-size: 14px;
}

.lists {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
}

.list-box {
    background: #fbfdff;
    border-radius: 20px;
    padding: 22px;
    border: 1px solid #e5edff;
}

.list-box h3 {
    margin: 0 0 14px;
    color: #331a4d;
}

ul {
    margin: 0;
    padding-left: 22px;
}

li {
    margin-bottom: 8px;
    line-height: 1.6;
}

.empty {
    color: #94a3b8;
}

.no-data {
    background: white;
    text-align: center;
    padding: 45px;
    border-radius: 24px;
    color: #64748b;
    border: 1px solid #e5edff;
    box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);
}

.no-data h2 {
    color: #65435c;
    margin-top: 0;
}

@media (max-width: 768px) {
    .main-content {
        padding: 28px 18px 45px;
    }

    .hero {
        padding: 28px;
    }

    .hero h1 {
        font-size: 32px;
    }

    .career-top {
        display: block;
    }

    .match-badge {
        display: inline-block;
        margin-top: 16px;
    }

    .stats,
    .lists {
        grid-template-columns: 1fr;
    }
}
</style>
</head>

<body>

 <header>
  <?php include 'navbar.php'; ?>
  </header>
  <?php include 'career_guidance_bot/chat_widget.php'; ?> 
<main class="main-content">
    <div class="container">

        <section class="hero">
            <h1>Career Path</h1>
            <p>
                View your selected career path, matching results, strengths, and skill gaps based on your profile.
            </p>
        </section>

        <?php if (empty($careers)): ?>

            <div class="no-data">
                <h2>No Career Path Found</h2>
                <p>You have not selected a career path yet.</p>
            </div>

        <?php else: ?>

            <?php foreach ($careers as $career): ?>

                <?php
                $dynamicData = getDynamicCareerData(
                    $conn,
                    $user_id,
                    $career['strengths'],
                    $career['gaps']
                );

                $updatedStrengths = $dynamicData['strengths'];
                $updatedGaps = $dynamicData['gaps'];
                $updatedSkillsMatch = $dynamicData['skills_match'];

                $updatedOverallMatch = round(
                    (((float) $career['holland_match']) * 0.5) +
                    (((float) $updatedSkillsMatch) * 0.5)
                );
                ?>

                <div class="career-card">

                    <div class="career-top">
                        <div>
                            <h2 class="career-title">
                                <?= htmlspecialchars($career['career_title']) ?>
                            </h2>
                            <div class="date">
                                Selected at: <?= htmlspecialchars($career['selected_at']) ?>
                            </div>
                        </div>

                        <div class="match-badge">
                            Overall Match: <?= htmlspecialchars($updatedOverallMatch) ?>%
                        </div>
                    </div>

                    <div class="stats">
                        <div class="stat-box">
                            <h3><?= htmlspecialchars($updatedOverallMatch) ?>%</h3>
                            <p>Overall Match</p>
                        </div>

                        <div class="stat-box">
                            <h3><?= htmlspecialchars($career['holland_match']) ?>%</h3>
                            <p>Holland Match</p>
                        </div>

                        <div class="stat-box">
                            <h3><?= htmlspecialchars($updatedSkillsMatch) ?>%</h3>
                            <p>Skills Match</p>
                        </div>
                    </div>

                    <div class="lists">
                        <div class="list-box">
                            <h3>Strengths</h3>
                            <?= listToHtml($updatedStrengths) ?>
                        </div>

                        <div class="list-box">
                            <h3>Skill Gaps</h3>
                            <?= listToHtml($updatedGaps) ?>
                        </div>
                    </div>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>
</main>

</body>
</html>