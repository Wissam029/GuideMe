<?php
$skill = $_GET['skill'] ?? null;
$weeks = $_GET['weeks'] ?? null;
$days = $_GET['days'] ?? null;
$plan_id = $_GET['plan_id'] ?? null;
?>

<!DOCTYPE html>
<html>

<head>
    <title>Plan</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 40px 24px;
            background: #f4f8ff;
            color: #1e293b;
            font-family: Arial, sans-serif;
        }

        h2 {
            margin: 0 0 24px;
            font-size: 32px;
            font-weight: 700;
            color: #65435c;
        }

        .progress-wrapper {
            background: #ffffff;
            border: 1px solid #dbeafe;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 24px;
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.08);
        }

        .progress-label {
            font-size: 16px;
            font-weight: 700;
            color: #65435c;
            margin-bottom: 12px;
        }

        .progress-bar {
            width: 100%;
            background: #e6efff;
            border-radius: 999px;
            overflow: hidden;
            height: 12px;
        }

        .progress-fill {
            height: 12px;
            background: linear-gradient(90deg, #65435c, #4a2f43);
            width: 0%;
            border-radius: 999px;
        }

        #result {
            display: grid;
            gap: 18px;
        }

        .card {
            background: #ffffff;
            border: 1px solid #dbeafe;
            border-radius: 20px;
            padding: 22px;
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.08);
        }

        .card.done {
            border: 1px solid #7d6576;
            background: #f8fbff;
        }

        .locked {
            opacity: 0.7;
            border: 1px dashed #ae8ca5;
        }

        .card h3 {
            margin: 0 0 14px;
            font-size: 22px;
            display: flex;
            justify-content: space-between;
        }

        .card p {
            font-size: 15px;
            color: #475569;
        }

        .task-box {
            margin-top: 14px;
            padding: 14px;
            background: #f4f8ff;
            border: 1px solid #dbeafe;
            border-radius: 14px;
        }

        .task-box b {
            color: #65435c;
        }

        .locked-message {
            margin-top: 10px;
            color: red;
            font-size: 14px;
        }

        input[type="checkbox"] {
            accent-color: #65435c;
            transform: scale(1.2);
            cursor: pointer;
        }

        .quiz-btn {
            padding: 12px 20px;
            background: #65435c;
            border: none;
            border-radius: 10px;
            color: white;
            cursor: pointer;
        }
    </style>
</head>

<body>

    <header>
        <?php include '../navbar.php'; ?>
    </header>

    <?php include '../career_guidance_bot/chat_widget.php'; ?>

    <h2>Learning Plan</h2>

    <div class="progress-wrapper">
        <div class="progress-label">Progress</div>
        <div class="progress-bar">
            <div id="progressFill" class="progress-fill"></div>
        </div>
    </div>

    <div id="result">Loading...</div>

    <script>
        let urlParams = new URLSearchParams(window.location.search);
        let plan_id = urlParams.get("plan_id");

        if (plan_id) {
            fetch("get_plan.php?plan_id=" + plan_id)
                .then(res => res.json())
                .then(plan => {
                    let html = "";
                    let completedCount = 0;

                    plan.forEach((item, index) => {
                        let isDone = (item.completed == 1);

                        if (isDone) completedCount++;

                        let checked = isDone ? "checked" : "";
                        let locked = false;

                        if (index > 0 && plan[index - 1].completed != 1) {
                            locked = true;
                        }

                        let disabled = locked ? "disabled" : "";

                        html += `
                            <div class="card ${isDone ? 'done' : ''} ${locked ? 'locked' : ''}">
                                <h3>
                                    Day ${item.day_number}
                                    <input type="checkbox" ${checked} ${disabled}
                                    onchange="toggleDay(${item.id})">
                                </h3>

                                <p><b>${item.title}</b></p>
                                <p>${item.description}</p>

                                <div class="task-box">
                                    <b>Task</b>
                                    <p>${item.task}</p>
                                </div>

                                ${locked ? "<p class='locked-message'>Complete the previous day first.</p>" : ""}
                            </div>
                        `;
                    });

                    if (completedCount === plan.length && plan.length > 0) {
                        html += `
                            <div style="margin-top:20px; text-align:center;">
                                <p style="color:#65435c;">Great job! You completed the plan.</p>
                                <button id="quizBtn" class="quiz-btn">Start Quiz</button>
                            </div>
                        `;
                    }

                    // عرض الكروت
                    document.getElementById("result").innerHTML = html;

                    // ✅ يرجع لنفس مكان الصفحة بعد التحديث
                    let scrollPosition = localStorage.getItem("scrollPosition");

                    if (scrollPosition) {
                        setTimeout(() => {
                            window.scrollTo(0, parseInt(scrollPosition));
                            localStorage.removeItem("scrollPosition");
                        }, 100);
                    }

                    let percent = Math.round((completedCount / plan.length) * 100);
                    document.getElementById("progressFill").style.width = percent + "%";

                    if (completedCount === plan.length && plan.length > 0) {
                        document.getElementById("quizBtn").addEventListener("click", async () => {

                            const btn = document.getElementById("quizBtn");

                            btn.innerText = "Generating Quiz...";
                            btn.disabled = true;

                            document.getElementById("result").innerHTML += `
                                <p style="text-align:center; margin-top:10px; color:#65435c;">
                                    ⏳ Generating your quiz, please wait...
                                </p>
                            `;

                            const skill = "<?php echo $skill ?? 'General'; ?>";

                            const check = await fetch(`../quiz/check_quiz.php?plan_id=${plan_id}`);
                            const checkData = await check.json();

                            if (checkData.exists) {
                                window.location.href = "../quiz/quiz.php?quiz_id=" + checkData.quiz_id + "&plan_id=" + plan_id;
                                return;
                            }

                            const res = await fetch("http://localhost:5678/webhook-test/generate-quiz", {
                                method: "POST",
                                headers: {
                                    "Content-Type": "application/json"
                                },
                                body: JSON.stringify({
                                    skills: skill,
                                    plan: plan,
                                    plan_id: plan_id
                                })
                            });

                            const data = await res.json();

                            const save = await fetch("../quiz/save_quiz.php", {
                                method: "POST",
                                headers: {
                                    "Content-Type": "application/json"
                                },
                                body: JSON.stringify({
                                    ...data,
                                    plan_id: plan_id
                                })
                            });

                            const result = await save.json();

                            window.location.href = "../quiz/quiz.php?quiz_id=" + result.quiz_id + "&plan_id=" + plan_id;
                        });
                    }
                });
        }

        else {
            fetch("generate_skill_plan.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    skill: "<?php echo $skill; ?>",
                    weeks: "<?php echo $weeks; ?>",
                    days: "<?php echo $days; ?>"
                })
            })
            .then(res => res.json())
            .then(data => {

                if (!data.plan_id) {
                    document.getElementById("result").innerHTML = "Failed to create the plan.";
                    return;
                }

                window.location.href = "plan.php?plan_id=" + data.plan_id;
            });
        }

        function toggleDay(plan_day_id) {

            // حفظ مكان الصفحة قبل التحديث
            localStorage.setItem("scrollPosition", window.scrollY);

            fetch("toggle_progress.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    plan_day_id: plan_day_id
                })
            })
            .then(() => {
                location.reload();
            });
        }
    </script>

</body>

</html>