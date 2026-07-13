<?php
require "../config/db.php";
session_start();

if (empty($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$quiz_id = isset($_GET['quiz_id']) ? (int) $_GET['quiz_id'] : 0;

if ($quiz_id <= 0) {
    echo "❌ Quiz not found";
    exit;
}

$stmt = $conn->prepare("
    SELECT quiz_data, plan_id 
    FROM quizzes 
    WHERE id = ? AND user_id = ?
    LIMIT 1
");
$stmt->bind_param("ii", $quiz_id, $user_id);
$stmt->execute();

$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    echo "❌ Quiz not found";
    exit;
}

$quiz = json_decode($row['quiz_data'], true);
$plan_id_from_db = (int)$row['plan_id'];

if (!$quiz) {
    echo "❌ Invalid quiz data";
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Quiz</title>

<style>
    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        padding: 40px 24px;
        background: #ececf2;
        color: #1e293b;
        font-family: Arial, sans-serif;
    }

    h2 {
        margin: 0 0 24px;
        font-size: 32px;
        font-weight: 700;
        color: #65435c;
    }

    #quiz {
        display: grid;
        gap: 18px;
    }

    .card {
        background: #ffffff;
        padding: 22px;
        margin-bottom: 0;
        border-radius: 20px;
        border: 1px solid #9f8798;
        box-shadow: 0 10px 25px rgba(37, 99, 235, 0.08);
    }

    .card p {
        margin: 0 0 12px;
        color: #334155;
        line-height: 1.7;
        font-size: 15px;
    }

    .card p b {
        color: #0f172a;
        font-size: 19px;
    }

    label {
        display: block;
        margin-bottom: 10px;
        padding: 12px 14px;
        background: #f8fbff;
        border: 1px solid #dbeafe;
        border-radius: 12px;
        cursor: pointer;
        color: #334155;
        transition: 0.2s ease;
    }

    label:hover {
        background: #eef5ff;
    }

    input[type="radio"] {
        margin-right: 8px;
        accent-color: #65435c;
    }

    button {
        padding: 12px 22px;
        background: #65435c;
        border: none;
        color: white;
        border-radius: 12px;
        cursor: pointer;
        margin-top: 16px;
        margin-right: 10px;
        font-size: 15px;
        font-weight: 700;
        transition: 0.2s ease;
    }

    button:hover {
        opacity: 0.95;
        transform: translateY(-1px);
    }

    #submitBtn {
        display: inline-block;
    }

    .correct {
        color: #16a34a;
        font-weight: 700;
    }

    .wrong {
        color: #dc2626;
        font-weight: 700;
    }

    #resultModal {
        display: none;
        position: fixed;
        inset: 0;
        width: 100%;
        height: 100%;
        background: rgba(15, 23, 42, 0.45);
        backdrop-filter: blur(4px);
        justify-content: center;
        align-items: center;
        padding: 20px;
    }

    .modal-box {
        background: #ffffff;
        padding: 30px;
        border-radius: 24px;
        text-align: center;
        position: relative;
        width: 100%;
        max-width: 420px;
        box-shadow: 0 20px 45px rgba(15, 23, 42, 0.18);
    }

    .modal-box h2 {
        margin-bottom: 10px;
        font-size: 28px;
    }

    .modal-box h1 {
        color: #65435c;
        margin: 10px 0;
        font-size: 42px;
    }

    .modal-box p {
        color: #475569;
        font-size: 16px;
        margin-bottom: 18px;
    }

    .close-btn {
        position: absolute;
        top: 14px;
        right: 18px;
        cursor: pointer;
        font-size: 20px;
        color: #64748b;
    }
    .quiz-error {
    display: none;
    margin: 0 0 18px;
    padding: 14px 16px;
    border-radius: 14px;
    background: #fee2e2;
    color: #991b1b;
    font-weight: 700;
    border: 1px solid #fecaca;
}

.card-error {
    border: 2px solid #ef4444 !important;
}
</style>
</head>

<header>
  <?php include '../navbar.php'; ?>
</header>

<body>
<?php include '../career_guidance_bot/chat_widget.php'; ?> 

<h2>Quiz</h2>
<div id="quizError" class="quiz-error"></div>

<div id="quiz"></div>

<button id="submitBtn" onclick="submitQuiz()">Submit Answers</button>

<div id="resultModal">
    <div class="modal-box">
        <span class="close-btn" onclick="closeModal()">✖</span>

        <h2>Quiz Result</h2>
        <h1 id="modalScore"></h1>
        <p id="modalLevel"></p>

        <button onclick="retryQuiz()">Try Again</button>
        <button onclick="goPlan()">Back to Plan</button>
        <button onclick="closeModal()">Show My Answer</button>
    </div>
</div>

<script>
const quiz_id = <?php echo json_encode($quiz_id); ?>;
const plan_id = <?php echo json_encode($plan_id_from_db); ?>;
const data = <?php echo json_encode($quiz, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

let container = document.getElementById("quiz");
let qIndex = 0;

function safeText(value) {
    return String(value)
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}
function showQuizError(message) {
    const errorBox = document.getElementById("quizError");
    errorBox.innerText = message;
    errorBox.style.display = "block";
    errorBox.scrollIntoView({ behavior: "smooth", block: "center" });
}

function clearQuizError() {
    const errorBox = document.getElementById("quizError");
    errorBox.innerText = "";
    errorBox.style.display = "none";

    document.querySelectorAll(".card").forEach(card => {
        card.classList.remove("card-error");
    });
}

function render(section) {
    section.forEach(q => {
        container.innerHTML += `
        <div class="card" id="card-${qIndex}">
            <p><b>${safeText(q.question)}</b></p>

            ${Object.entries(q.options).map(([k,v]) => `
                <label>
                    <input type="radio" name="q${qIndex}" value="${safeText(k)}">
                    ${safeText(k)}) ${safeText(v)}
                </label><br>
            `).join("")}

            <div id="feedback-${qIndex}"></div>
        </div>
        `;
        qIndex++;
    });
}

render(data.section_1_basics);
render(data.section_2_application);
render(data.section_3_challenge);

function submitQuiz() {
    const all = [
        ...data.section_1_basics,
        ...data.section_2_application,
        ...data.section_3_challenge
    ];
     clearQuizError();

for (let i = 0; i < all.length; i++) {
    if (!document.querySelector(`input[name="q${i}"]:checked`)) {
        document.getElementById(`card-${i}`).classList.add("card-error");
        showQuizError("Please answer all questions before submitting the quiz.");
        return;
    }
}
    

    let score = 0;

    all.forEach((q, i) => {
        let selected = document.querySelector(`input[name="q${i}"]:checked`);
        let userAnswer = selected.value;
        let card = document.getElementById(`card-${i}`);
        let feedback = document.getElementById(`feedback-${i}`);

        if (userAnswer === q.answer) {
            score++;
            card.style.border = "2px solid #22c55e";
            feedback.innerHTML = `<p class="correct">Correct</p>`;
        } else {
            card.style.border = "2px solid #ef4444";
            feedback.innerHTML = `
                <p class="wrong">Your answer: ${safeText(userAnswer)}</p>
                <p class="correct">Correct answer: ${safeText(q.answer)}</p>
                <p>Explanation: ${safeText(q.explanation)}</p>
            `;
        }
    });

    document.getElementById("submitBtn").style.display = "none";

    let total = all.length;
    let percent = Math.round((score / total) * 100);

    let level = percent >= 80 ? "Excellent"
          : percent >= 50 ? "Good"
          : "Needs Improvement";

    document.getElementById("modalScore").innerText = `${percent}%`;
    document.getElementById("modalLevel").innerText = level;
    document.getElementById("resultModal").style.display = "flex";

    fetch("save_score.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            quiz_id: quiz_id,
            score: percent
        })
    });
}

function retryQuiz() {
    location.reload();
}

function goPlan() {
    if (!plan_id) {
        closeModal();
        showQuizError("Plan not found.");
        return;
    }

    window.location.href = "../plan/plan.php?plan_id=" + encodeURIComponent(plan_id);
}

function closeModal() {
    document.getElementById("resultModal").style.display = "none";
}
</script>

</body>
</html>