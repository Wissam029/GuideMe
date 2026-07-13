<?php
require "../config/secure_session.php";

if (
    empty($_SESSION['user_logged_in']) || 
    $_SESSION['user_logged_in'] !== true || 
    empty($_SESSION['user_id'])
) {
    header("Location: ../auth/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Dashboard</title>

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
    margin-bottom: 28px;
    box-shadow: 0 14px 35px rgba(15, 23, 42, 0.07);
}

.hero h1 {
    margin: 0 0 10px;
    font-size: 38px;
    color: #65435c;
}

.hero p {
    margin: 0;
    color: #64748b;
    line-height: 1.7;
}

.section {
    margin-bottom: 30px;
}

.big-card {
    background: #ffffff;
    border: 1px solid #e5edff;
    border-radius: 28px;
    padding: 26px;
    box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);
}

.big-card h2 {
    margin-top: 0;
    margin-bottom: 18px;
    color: #65435c;
}

.skill-group {
    margin-top: 24px;
}

.skill-group h3 {
    color: #65435c;
    margin-bottom: 14px;
}

.inner-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 16px;
}

.card {
    background: rgba(255,255,255,0.95);
    border: 1px solid #e5edff;
    border-radius: 24px;
    padding: 24px;
    box-shadow: 0 10px 25px rgba(15, 23, 42, 0.06);
    transition: 0.2s ease;
}

.card:hover {
    transform: translateY(-3px);
}

.card h3 {
    margin: 0 0 10px;
    font-size: 20px;
    color: #331a4d;
}

.card p {
    margin: 0 0 12px;
    color: #64748b;
}

.progress-bar {
    width: 100%;
    background: #e6efff;
    border-radius: 999px;
    overflow: hidden;
    height: 12px;
    margin: 14px 0 10px;
}

.progress-fill {
    background: linear-gradient(90deg, #65435c, #8b6f86);
    height: 12px;
    border-radius: 999px;
}

button {
    margin-top: 12px;
    padding: 11px 18px;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 700;
}

.start {
    background: #65435c;
    color: white;
}

.continue {
    background: #f1e8f0;
    color: #65435c;
}

.passed-card {
    border-color: #65435c;
}

.empty-message {
    background: #ffffff;
    border: 1px dashed #e5edff;
    color: #64748b;
    padding: 18px;
    border-radius: 18px;
}

.modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15,23,42,0.45);
    backdrop-filter: blur(4px);
    padding: 20px;
    z-index: 999999;
}

.modal-content {
    background: white;
    padding: 28px;
    margin: 100px auto;
    max-width: 420px;
    border-radius: 24px;
    box-shadow: 0 20px 45px rgba(15,23,42,0.18);
}

.modal-content h3 {
    margin-top: 0;
    color: #65435c;
}

input {
    width: 100%;
    padding: 12px 14px;
    margin: 8px 0 12px;
    border: 1px solid #cbd5e1;
    border-radius: 12px;
    background: #f8fbff;
    outline: none;
}

input:focus {
    border-color: #65435c;
    box-shadow: 0 0 0 3px rgba(101, 67, 92, 0.12);
}

.modal-error {
    display: none;
    margin: 4px 0 14px;
    padding: 12px 14px;
    border-radius: 14px;
    background: #fee2e2;
    color: #991b1b;
    font-size: 14px;
    font-weight: 700;
    line-height: 1.5;
}

.modal-actions {
    display: flex;
    gap: 10px;
}

.secondary-btn {
    background: #e2e8f0;
    color: #334155;
}

@media (max-width: 768px) {
    .main-content {
        padding: 28px 18px 45px;
    }

    .hero h1 {
        font-size: 32px;
    }

    .inner-grid {
        grid-template-columns: 1fr;
    }
}
</style>
</head>

<body>

<?php include '../navbar.php'; ?>
    <?php include '../career_guidance_bot/chat_widget.php'; ?>


<main class="main-content">
    <div class="container">

        <section class="hero">
            <h1>Dashboard</h1>
            <p>Track your learning progress, completed skills, and start new skills based on your career path.</p>
        </section>

        <section class="section">
            <div class="big-card">

                <h2>My Skills Overview</h2>

                <div class="skill-group">
                    <h3>In Progress Skills</h3>
                    <div id="plans" class="inner-grid"></div>
                </div>

                <div class="skill-group">
                    <h3>Completed Skills</h3>
                    <div id="passedSkills" class="inner-grid"></div>
                </div>

                <div class="skill-group">
                    <h3>Not Started Skills</h3>
                    <div id="notStarted" class="inner-grid"></div>
                </div>

            </div>
        </section>

    </div>
</main>

<div id="modal" class="modal">
    <div class="modal-content">
        <h3 id="selectedSkill"></h3>

        <input type="number" id="weeks" placeholder="Number of weeks" min="1" max="12">
        <input type="number" id="days" placeholder="Days per week" min="1" max="7">

        <div id="planModalError" class="modal-error"></div>

        <div class="modal-actions">
            <button class="start" onclick="createPlan()">Create Plan</button>
            <button class="secondary-btn" onclick="closeModal()">Cancel</button>
        </div>
    </div>
</div>

<script>
let currentSkill = "";
window.skillsList = [];

function escapeHTML(text) {
    return String(text)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function showPlanModalError(message) {
    const errorBox = document.getElementById("planModalError");
    errorBox.innerText = message;
    errorBox.style.display = "block";
}

function clearPlanModalError() {
    const errorBox = document.getElementById("planModalError");
    errorBox.innerText = "";
    errorBox.style.display = "none";
}

fetch("dashboard_data.php")
.then(res => res.json())
.then(data => {

    if (data.error) {
        document.getElementById("plans").innerHTML =
            `<div class="empty-message">${escapeHTML(data.error)}</div>`;
        return;
    }

    let plansHTML = "";

    if (!data.plans || data.plans.length === 0) {
        plansHTML = `<div class="empty-message">No plan yet. Start by creating one.</div>`;
    } else {
        data.plans.forEach(plan => {
            let percent = Number(plan.percent) || 0;
            let planId = Number(plan.plan_id) || 0;

            plansHTML += `
            <div class="card">
                <h3>${escapeHTML(plan.skill)}</h3>
                <p>${percent}% completed</p>

                <div class="progress-bar">
                    <div class="progress-fill" style="width:${percent}%"></div>
                </div>

                <button class="continue" onclick="goToPlan(${planId})">
                    Continue Learning
                </button>
            </div>`;
        });
    }

    document.getElementById("plans").innerHTML = plansHTML;

    let passedHTML = "";

    if (!data.passed || data.passed.length === 0) {
        passedHTML = `<div class="empty-message">No passed skills yet.</div>`;
    } else {
        data.passed.forEach(item => {
            let score = Number(item.best_score) || 0;

            passedHTML += `
            <div class="card passed-card">
                <h3>${escapeHTML(item.skill)}</h3>
                <p>Best Score: ${score}%</p>

                <div class="progress-bar">
                    <div class="progress-fill" style="width:${score}%"></div>
                </div>
            </div>`;
        });
    }

    document.getElementById("passedSkills").innerHTML = passedHTML;

    let notHTML = "";

    if (!data.not_started || data.not_started.length === 0) {
        notHTML = `<div class="empty-message">No new skills.</div>`;
    } else {
        window.skillsList = data.not_started;

        data.not_started.forEach((skill, index) => {
            notHTML += `
            <div class="card">
                <h3>${escapeHTML(skill)}</h3>
                <button class="start" onclick="startSkill(${index})">
                    Start Now
                </button>
            </div>`;
        });
    }

    document.getElementById("notStarted").innerHTML = notHTML;
})
.catch(() => {
    document.getElementById("plans").innerHTML =
        `<div class="empty-message">Failed to load dashboard data.</div>`;
});

function goToPlan(plan_id) {
    if (!plan_id || plan_id <= 0) {
        return;
    }

    window.location.href = "../plan/plan.php?plan_id=" + encodeURIComponent(plan_id);
}

function startSkill(index) {
    const skill = window.skillsList[index];

    if (!skill) {
        return;
    }

    fetch("../skills/check_plan.php", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify({ skill: skill })
    })
    .then(res => res.json())
    .then(data => {
        if (data.error) {
            openModal(skill);
            showPlanModalError(data.error);
            return;
        }

        if (data.exists) {
            window.location.href = "../plan/plan.php?plan_id=" + encodeURIComponent(data.plan_id);
        } else {
            openModal(skill);
        }
    })
    .catch(() => {
        openModal(skill);
        showPlanModalError("Failed to check the plan.");
    });
}

function openModal(skill) {
    currentSkill = skill;
    document.getElementById("selectedSkill").innerText = skill;
    document.getElementById("weeks").value = "";
    document.getElementById("days").value = "";
    clearPlanModalError();
    document.getElementById("modal").style.display = "block";
}

function closeModal() {
    document.getElementById("modal").style.display = "none";
    clearPlanModalError();
}

function createPlan() {
    clearPlanModalError();

    let weeks = Number(document.getElementById("weeks").value);
    let days = Number(document.getElementById("days").value);

    if (!currentSkill) {
        showPlanModalError("Please select a skill.");
        return;
    }

    if (!weeks || weeks < 1 || weeks > 12) {
        showPlanModalError("Please enter weeks between 1 and 12.");
        return;
    }

    if (!days || days < 1 || days > 7) {
        showPlanModalError("Please enter days between 1 and 7.");
        return;
    }

    window.location.href =
        `../plan/plan.php?skill=${encodeURIComponent(currentSkill)}&weeks=${encodeURIComponent(weeks)}&days=${encodeURIComponent(days)}`;
}

window.onclick = function(e) {
    let modal = document.getElementById("modal");

    if (e.target === modal) {
        closeModal();
    }
}
</script>

</body>
</html>