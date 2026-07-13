<!DOCTYPE html>
<html>
<head>
    <title>Skill Gap</title>

    <style>
        body {
            font-family: Arial;
            background: #0f172a;
            color: white;
            padding: 20px;
        }

        .skills-container {
            display: flex;
            flex-wrap: wrap;
        }

        .skill {
            background: #1e293b;
            padding: 12px 18px;
            margin: 8px;
            border-radius: 10px;
            cursor: pointer;
        }

        .skill:hover {
            background: #38bdf8;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.7);
        }

        .modal-content {
            background: #1e293b;
            padding: 20px;
            margin: 100px auto;
            width: 300px;
            border-radius: 10px;
            text-align: center;
        }

        input {
            width: 80%;
            padding: 8px;
            margin: 10px;
        }

        button {
            padding: 10px;
            margin: 5px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }

        .create-btn { background: #22c55e; color: white; }
        .cancel-btn { background: #ef4444; color: white; }
    </style>
</head>

<body>

<h1>📊 Your Skill Gaps</h1>

<div class="skills-container" id="skills"></div>

<div id="modal" class="modal">
    <div class="modal-content">
        <h3 id="selectedSkill"></h3>

        <input type="number" id="weeks" placeholder="عدد الأسابيع">
        <input type="number" id="days" placeholder="أيام في الأسبوع">

        <br>
        <button class="create-btn" onclick="createPlan()">🚀 إنشاء الخطة</button>
        <button class="cancel-btn" onclick="closeModal()">❌ إلغاء</button>
    </div>
</div>

<script>

let currentSkill = "";

// تحميل المهارات
fetch("get_skills.php")
.then(res => res.json())
.then(data => {
    let html = "";

    data.skills.forEach(skill => {
        html += `
            <div class="skill" onclick="handleSkillClick('${skill}')">
                ${skill}
            </div>
        `;
    });

    document.getElementById("skills").innerHTML = html;
});

// 🔥 عند الضغط على مهارة
function handleSkillClick(skill) {

    fetch("check_plan.php", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify({ skill: skill })
    })
    .then(res => res.json())
    .then(data => {

        if (data.exists) {
            // 🔥 نروح مباشرة للخطة باستخدام plan_id
            window.location.href = "../plan/plan.php?plan_id=" + data.plan_id;
        } else {
            openModal(skill);
        }
    });
}

function openModal(skill) {
    currentSkill = skill;
    document.getElementById("selectedSkill").innerText = skill;
    document.getElementById("modal").style.display = "block";
}

function closeModal() {
    document.getElementById("modal").style.display = "none";
}

function createPlan() {

    let weeks = document.getElementById("weeks").value;
    let days = document.getElementById("days").value;

    if (!weeks || !days || weeks <= 0 || days <= 0) {
        alert("⚠️ أدخل قيم صحيحة");
        return;
    }

    window.location.href =
        `../plan/plan.php?skill=${encodeURIComponent(currentSkill)}&weeks=${weeks}&days=${days}`;
}

</script>

</body>
</html>