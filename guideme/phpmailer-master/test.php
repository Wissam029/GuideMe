<?php
session_start();

if (empty($_SESSION['user_logged_in']) || empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Holland RIASEC Test</title>
  <link rel="stylesheet" href="style.css">
  <style>
    

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    min-height: 100vh;
    background: #f4f8ff;
    color: #1e293b;
    font-family: Arial, sans-serif;
}
.page-wrapper {
    min-height: calc(100vh - 80px);
    padding: 20px 20px;
    display: flex;
    justify-content: center;
    align-items: center;
}
.test-container {
    width: 100%;
    max-width: 760px;
}

h2 {
    margin: 0 0 10px;
    text-align: center;
    font-size: 32px;
    font-weight: 700;
    color: #331a4d;
}

.test-container > p {
    text-align: center;
    color: #475569;
    font-size: 16px;
    margin-bottom: 0;
}

.card {
    background: #ffffff;
    border: 1px solid #dbeafe;
    border-radius: 24px;
    padding: 32px;
    box-shadow: 0 14px 30px rgba(37, 99, 235, 0.10);
    margin-top: 2px;
}

.progress {
    margin: 0 0 18px;
    font-size: 15px;
    color: #65435c;
    font-weight: 600;
    text-align: center;
}

#questionText {
    margin: 0 0 18px;
    text-align: center;
    font-size: 20px;
    font-weight: 600;
    color: #1e293b;
    line-height: 1.6;
}

.choices {
    margin-top: 10px;
}

.choices label {
    display: block;
    margin: 12px 0;
    cursor: pointer;
    padding: 14px 16px;
    border: 1px solid #dbeafe;
    border-radius: 14px;
    background: #f8fbff;
    transition: 0.2s ease;
    color: #334155;
}

.choices label:hover {
    background: #eef4ff;
    border-color: #93c5fd;
}

.choices input {
    margin-right: 10px;
    transform: scale(1.1);
}

.nav {
    display: flex;
    gap: 12px;
    margin-top: 22px;
}

.nav .btn {
    flex: 1;
    text-align: center;
}

.btn {
    background: #65435c;
    color: white;
    border: none;
    padding: 14px 18px;
    border-radius: 12px;
    cursor: pointer;
    font-size: 15px;
    font-weight: 600;
    transition: 0.2s ease;
}

.btn:hover {
    background: #65435c;
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn.secondary {
    background: #e2e8f0;
    color: #1e293b;
    border: none;
}

.btn.secondary:hover {
    background: #cbd5e1;
}

#status {
    margin-top: 14px;
    text-align: center;
    color: #dc2626;
    font-size: 14px;
}

.spinner-small {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 3px solid rgba(0,0,0,.1);
    border-radius: 50%;
    border-top-color: #420d7b;
    animation: spin 1s ease-in-out infinite;
    vertical-align: middle;
    margin-right: 8px;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

@media (max-width: 768px) {
    body {
        padding: 16px;
        align-items: flex-start;
    }

    .test-container {
        max-width: 100%;
    }

    .card {
        padding: 22px;
        border-radius: 20px;
    }

    h2 {
        font-size: 26px;
    }

    #questionText {
        font-size: 18px;
    }

    .nav {
        flex-direction: column;
    }
}
</style>

 
</head>

<body>
  <header>
  <?php include 'navbar.php'; ?>
  </header>
  <?php include 'career_guidance_bot/chat_widget.php'; ?> 
  <div class="page-wrapper">
  <div class="test-container">
    

    <div class="card">
      <h2>Holland RIASEC Career Interest Test</h2>
    <p style="text-align:center;">Indicate how much each statement applies to you (0 to 4).</p>
      <div class="progress" id="progressText">Question 1 / 42</div>
      <!-- ✅ ما نعرض نوع السؤال -->
      <p id="questionText" style="margin:0 0 12px;text-align:center;"></p>

      <div class="choices" id="choicesBox"></div>

      <div class="nav">
        <button class="btn secondary" id="prevBtn" type="button">Previous</button>
        <button class="btn" id="nextBtn" type="button">Next</button>
      </div>

      <p id="status"></p>
    </div>
  </div>
</div>
<script>
  
  const N8N_WEBHOOK_URL = "http://localhost:5678/webhook/b36090d3-e177-496d-b176-802ff7126d92";
const USER_ID = <?php echo (int) $_SESSION['user_id']; ?>;
const ATTEMPT_KEY = "holland_attempt_id_v1";

function getAttemptIdOnce() {
  const saved = sessionStorage.getItem(ATTEMPT_KEY);
  if (saved) return saved;

  const newId = crypto.randomUUID();
  sessionStorage.setItem(ATTEMPT_KEY, newId);
  return newId;
}

const ATTEMPT_ID = getAttemptIdOnce();

  const SCALE = [
    { value: 0, label: "Strongly Disagree" },
    { value: 1, label: "Disagree" },
    { value: 2, label: "Neutral" },
    { value: 3, label: "Agree" },
    { value: 4, label: "Strongly Agree" },
  ];

  const QUESTIONS_BASE = [
    // R (1..7)
    { id: 1, domain: "R", text: "I prefer work that involves direct interaction with tools or tangible objects." },
    { id: 2, domain: "R", text: "I enjoy turning ideas or instructions into practical, usable results." },
    { id: 3, domain: "R", text: "I prefer personally checking that tools or equipment work properly through hands-on experience." },
    { id: 4, domain: "R", text: "I feel comfortable dealing with malfunctions or operational problems and fixing them practically." },
    { id: 5, domain: "R", text: "I enjoy assembling or preparing tools, equipment, or systems before use." },
    { id: 6, domain: "R", text: "I tend to prefer practical application over purely theoretical thinking." },
    { id: 7, domain: "R", text: "I prefer learning through hands-on experience rather than reading or explanations." },

    // I (8..14)
    { id: 8,  domain: "I", text: "I enjoy analyzing information to discover hidden patterns or relationships." },
    { id: 9,  domain: "I", text: "I prefer examining information deeply to understand the root cause of a problem before thinking about solutions." },
    { id: 10, domain: "I", text: "I tend to use logical and analytical thinking to understand complex problems." },
    { id: 11, domain: "I", text: "I enjoy understanding the overall picture and core requirements before starting any task." },
    { id: 12, domain: "I", text: "I prefer studying problems from multiple perspectives to identify their true causes." },
    { id: 13, domain: "I", text: "I enjoy comparing different options and analyzing their advantages and disadvantages before making a decision." },
    { id: 14, domain: "I", text: "I enjoy research and learning even when there is no immediate practical application." },

    // A (15..21)
    { id: 15, domain: "A", text: "I tend to generate new ideas or imagine unconventional ways to express concepts." },
    { id: 16, domain: "A", text: "I care about how users interact with elements and prefer designing details that create meaningful experiences." },
    { id: 17, domain: "A", text: "I am drawn to using digital designs or visuals to express ideas visually." },
    { id: 18, domain: "A", text: "I enjoy transforming abstract ideas into clear artistic concepts that can be visually developed." },
    { id: 19, domain: "A", text: "I prefer expressing ideas through visual sequences or coherent visual stories." },
    { id: 20, domain: "A", text: "I enjoy collaborating with others to exchange perspectives and refine creative ideas." },
    { id: 21, domain: "A", text: "I focus on fine visual details that enhance clarity and visual coherence." },

    // S (22..28)
    { id: 22, domain: "S", text: "I enjoy explaining information or skills to others and helping them learn." },
    { id: 23, domain: "S", text: "I feel satisfied when supporting others and encouraging them to grow and achieve their goals." },
    { id: 24, domain: "S", text: "I prefer work that involves building positive and stable relationships with others." },
    { id: 25, domain: "S", text: "I enjoy teamwork based on mutual understanding and cooperation to achieve shared outcomes." },
    { id: 26, domain: "S", text: "I enjoy offering guidance or advice to others regarding educational or career choices." },
    { id: 27, domain: "S", text: "I prefer facilitating group discussions and participating in organizing them for the benefit of everyone." },
    { id: 28, domain: "S", text: "I enjoy following others’ progress and providing constructive feedback to help them improve." },

    // E (29..35)
    { id: 29, domain: "E", text: "I enjoy taking leadership roles and guiding others toward achieving clear goals." },
    { id: 30, domain: "E", text: "I prefer roles that allow me to influence work direction and achieve tangible results through others." },
    { id: 31, domain: "E", text: "I enjoy taking responsibility for making decisive decisions that impact work progress or project success." },
    { id: 32, domain: "E", text: "I prefer roles that allow me to select suitable people and assign tasks to achieve objectives." },
    { id: 33, domain: "E", text: "I feel confident and satisfied when persuading others with ideas or technical solutions I believe in." },
    { id: 34, domain: "E", text: "I enjoy presenting ideas or proposals in a persuasive way that helps others make decisions." },
    { id: 35, domain: "E", text: "I enjoy directing resources and efforts and making decisions that influence work direction to achieve goals." },

    // C (36..42)
    { id: 36, domain: "C", text: "I prefer work that involves organizing and arranging information in a clear and systematic way." },
    { id: 37, domain: "C", text: "I feel comfortable following structured procedures rather than working randomly." },
    { id: 38, domain: "C", text: "I enjoy documenting work or recording details for future reference." },
    { id: 39, domain: "C", text: "I prefer tasks that require regular and accurate data updates." },
    { id: 40, domain: "C", text: "I tend to ensure that work complies with established rules or policies." },
    { id: 41, domain: "C", text: "I enjoy reviewing fine details to ensure data accuracy or work quality." },
    { id: 42, domain: "C", text: "I prefer working in environments with clear standards and well-defined roles and responsibilities." },
  ];

  // 🔀 Fisher–Yates shuffle
  function shuffleArray(arr) {
    for (let i = arr.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      [arr[i], arr[j]] = [arr[j], arr[i]];
    }
    return arr;
  }

  // ✅ مفاتيح التخزين للجلسة
  const ORDER_KEY = "holland_order_v1";
  const ANSWERS_KEY = "holland_answers_v1";
  const INDEX_KEY = "holland_index_v1";

  // ✅ ترتيب عشوائي مرة واحدة عند بدء الاختبار
  function getOrderOnce() {
    const saved = sessionStorage.getItem(ORDER_KEY);
    if (saved) return JSON.parse(saved);

    const ids = QUESTIONS_BASE.map(q => q.id);
    shuffleArray(ids);
    sessionStorage.setItem(ORDER_KEY, JSON.stringify(ids));
    return ids;
  }

  const ORDER = getOrderOnce();
  const byId = new Map(QUESTIONS_BASE.map(q => [q.id, q]));

  // ✅ استرجاع إجابات سابقة إذا صار Refresh
  const answersMap = new Map();
  const savedAnswersRaw = sessionStorage.getItem(ANSWERS_KEY);
  if (savedAnswersRaw) {
    const obj = JSON.parse(savedAnswersRaw); // { "1":3, "2":0, ...}
    Object.keys(obj).forEach(k => answersMap.set(Number(k), obj[k]));
  }

  // ✅ استرجاع مكان المستخدم
  let idx = 0;
  const savedIdx = sessionStorage.getItem(INDEX_KEY);
  if (savedIdx) idx = Number(savedIdx);

  // عناصر الصفحة
  const progressText = document.getElementById("progressText");
  const questionText = document.getElementById("questionText");
  const choicesBox = document.getElementById("choicesBox");
  const statusEl = document.getElementById("status");
  const prevBtn = document.getElementById("prevBtn");
  const nextBtn = document.getElementById("nextBtn");

  function persistAnswers() {
    const obj = {};
    for (const [qid, choice] of answersMap.entries()) obj[qid] = choice;
    sessionStorage.setItem(ANSWERS_KEY, JSON.stringify(obj));
  }

  function persistIndex() {
    sessionStorage.setItem(INDEX_KEY, String(idx));
  }

  function render() {
    statusEl.textContent = "";

    const qid = ORDER[idx];
    const q = byId.get(qid);

    progressText.textContent = `Question ${idx + 1} / ${ORDER.length}`;
    questionText.textContent = q.text;

    choicesBox.innerHTML = "";
    SCALE.forEach(opt => {
      const id = `q_${q.id}_${opt.value}`;

      const label = document.createElement("label");
      label.setAttribute("for", id);

      const input = document.createElement("input");
      input.type = "radio";
      input.name = `q_${q.id}`;
      input.id = id;
      input.value = String(opt.value);

      const saved = answersMap.get(q.id);
      if (saved !== undefined && saved === opt.value) input.checked = true;

      input.addEventListener("change", () => {
        answersMap.set(q.id, Number(input.value));
        persistAnswers();
      });

      label.appendChild(input);
      label.appendChild(document.createTextNode(` ${opt.label}`));
      choicesBox.appendChild(label);
    });

    prevBtn.disabled = (idx === 0);
    nextBtn.textContent = (idx === ORDER.length - 1) ? "Submit" : "Next";
    persistIndex();
  }

  function currentAnswered() {
    const qid = ORDER[idx];
    return answersMap.has(qid);
  }

  prevBtn.addEventListener("click", () => {
    if (idx > 0) {
      idx--;
      render();
    }
  });

  nextBtn.addEventListener("click", async () => {
    if (!currentAnswered()) {
      statusEl.textContent = "Please select an option before continuing.";
      return;
    }

    if (idx === ORDER.length - 1) {
      statusEl.innerHTML = `
    <div style="margin: 10px 0;">
        <span class="spinner-small"></span> 
        <p style="font-size: 14px; color: #555;">Analyzing 42 answers... This may take up to 1 minute.</p>
    </div>
`;

      //  لضمان 42 إجابة فقط
      const finalAnswersArray = QUESTIONS_BASE.map(q => ({
        question_id: q.id,
        choice: answersMap.get(q.id) ?? 0, 
        domain: q.domain
      }));

      const payload = {
        user_id: USER_ID,
        attempt_id: ATTEMPT_ID,
        test_version: "v1",
        answers: finalAnswersArray
      };

      try {
        const res = await fetch(N8N_WEBHOOK_URL, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(payload)
        });

        if (!res.ok) {
          const txt = await res.text();
          throw new Error(`Server error: ${res.status} - ${txt}`);
        }

        const data = await res.json();
        localStorage.setItem("holland_result", JSON.stringify(data));



        // ✅ بعد الإرسال نمسح بيانات الجلسة عشان اختبار جديد يبدأ من الصفر
        sessionStorage.removeItem(ORDER_KEY);
        sessionStorage.removeItem(ANSWERS_KEY);
        sessionStorage.removeItem(INDEX_KEY);
        sessionStorage.removeItem(ATTEMPT_KEY);

        window.location.href = "result.php";
      } catch (err) {
        console.error(err);
        statusEl.textContent = "Failed to submit. Check webhook URL and make sure n8n is running.";
      }
      return;
    }

    idx++;
    render();
  });

  render();
</script>
</body>
</html>
