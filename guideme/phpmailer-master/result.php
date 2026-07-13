<?php
session_start();

if (empty($_SESSION['user_logged_in']) || empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include "config/db.php";

$user_id = (int)$_SESSION['user_id'];

$dbResult = null;

$stmt = mysqli_prepare($conn, "
    SELECT top3_code, scores_json, percentages_json, summary_text
    FROM hollandresult
    WHERE user_id = ?
    ORDER BY result_id DESC
    LIMIT 1
");

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$res = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($res);

if ($row) {
    $dbResult = [
        "top3_code" => $row["top3_code"],
        "scores" => json_decode($row["scores_json"], true),
        "percentages" => json_decode($row["percentages_json"], true),
        "summary_text" => $row["summary_text"],
        "_saved_to_db" => true
    ];
}

mysqli_stmt_close($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Personality Test Results</title>

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
      width: min(1050px, 92%);
      margin: 0 auto;
      padding: 45px 0;
    }

    .result-container {
      background: #ffffff;
      border: 1px solid #e5edff;
      border-radius: 28px;
      padding: 34px;
      box-shadow: 0 18px 45px rgba(101, 67, 92, 0.12);
    }

    .page-header {
      text-align: center;
      margin-bottom: 30px;
    }

    .page-header h2 {
      margin: 0;
      font-size: 34px;
      font-weight: 800;
      color: #65435c;
    }

    .page-header p {
      margin: 10px auto 0;
      max-width: 620px;
      color: #64748b;
      line-height: 1.7;
      font-size: 15px;
    }

    .cards-grid {
      display: grid;
      grid-template-columns: 1fr;
      gap: 18px;
    }

    .card {
      background: #ffffff;
      border: 1px solid #e5edff;
      border-radius: 22px;
      padding: 24px;
      box-shadow: 0 10px 25px rgba(15, 23, 42, 0.06);
    }

    .card h3 {
      margin: 0 0 16px;
      font-size: 22px;
      color: #65435c;
    }

    .muted {
      color: #64748b;
      line-height: 1.6;
    }

    .top-code-card {
      background: #ffffff;
      border: 1px solid #eaddea;
      text-align: center;
    }

    .badge {
      display: inline-block;
      margin-top: 12px;
      padding: 12px 20px;
      border-radius: 999px;
      background: #65435c;
      color: #ffffff;
      font-size: 24px;
      font-weight: 800;
      letter-spacing: 3px;
      box-shadow: 0 10px 22px rgba(101, 67, 92, 0.25);
    }

    .code-help {
      margin-top: 14px;
      font-size: 13px;
      color: #64748b;
    }

    .kvs {
      list-style: none;
      padding: 0;
      margin: 0;
      display: grid;
      gap: 10px;
    }

    .kvs li {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 14px;
      padding: 14px 16px;
      border: 1px solid #e5edff;
      border-radius: 16px;
      background: #f8fbff;
    }

    .kvs .label {
      font-weight: 700;
      color: #334155;
    }

    .kvs span:last-child {
      font-weight: 800;
      color: #65435c;
    }

    .summary {
      white-space: pre-line;
      line-height: 1.8;
      color: #334155;
      background: #f8fbff;
      border: 1px solid #e5edff;
      border-radius: 18px;
      padding: 18px;
    }

    .btn-row {
      display: flex;
      gap: 14px;
      flex-wrap: wrap;
      justify-content: center;
      margin-top: 28px;
    }

    .btn {
      display: inline-block;
      padding: 14px 24px;
      border-radius: 16px;
      background: #65435c;
      color: #ffffff;
      font-weight: 800;
      text-decoration: none;
      border: none;
      cursor: pointer;
      transition: 0.2s ease;
    }

    .btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 22px rgba(101, 67, 92, 0.22);
    }

    .btn.secondary {
      background: #ffffff;
      color: #65435c;
      border: 1px solid #d7e2ff;
    }

    #err {
      text-align: center;
      color: #ef4444;
      font-weight: 600;
      margin-top: 18px;
    }

    @media (max-width: 700px) {
      .result-container {
        padding: 24px 18px;
      }

      .page-header h2 {
        font-size: 28px;
      }

      .kvs li {
        flex-direction: column;
        align-items: flex-start;
      }

      .btn-row {
        flex-direction: column;
      }

      .btn {
        width: 100%;
        text-align: center;
      }
    }
  </style>
</head>

<body>

<?php include "navbar.php"; ?>
<?php include 'career_guidance_bot/chat_widget.php'; ?> 
<div class="page-wrapper">
  <div class="result-container">

    <div class="page-header">
      <h2>Personality Test Results</h2>
      <p>
        Your Holland personality result helps identify the career directions that best match your interests, strengths, and preferred work style.
      </p>
    </div>

    <div class="cards-grid">

      <div class="card top-code-card">
        <div class="muted">Top 3 Holland Code</div>
        <span class="badge" id="top3">—</span>
        <div class="code-help">
          R = Realistic, I = Investigative, A = Artistic, S = Social, E = Enterprising, C = Conventional
        </div>
      </div>

      <div class="card">
        <h3>Percentages</h3>
        <ul class="kvs" id="percentList"></ul>
      </div>

      <div class="card">
        <h3>AI Summary</h3>
        <div class="summary" id="summaryText">—</div>
      </div>

    </div>

    <div class="btn-row">
      <a href="career_choice.php" class="btn" id="choosePathBtn">
        Choose Your Career Path
      </a>

      <a href="test.php" class="btn secondary">
        Retake Test
      </a>
    </div>

    <p class="muted" id="err"></p>

  </div>
</div>

<script>
document.getElementById("choosePathBtn").addEventListener("click", async function (e) {
  e.preventDefault();

  try {
    const res = await fetch("check_user_skills.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      credentials: "same-origin"
    });

    const data = await res.json();

    if (data.status !== "success") {
      alert("Error checking skills");
      return;
    }

    if (!data.has_skills) {
    const goProfile = confirm(
        "You have not added any skills yet.\n\nAdding skills helps the system provide more accurate career suggestions and skill gap analysis.\n\nPress OK to go to your Profile and add your skills.\nPress Cancel to continue without skills"
    );

    if (goProfile) {
        window.location.href = "profile.php";
        return;
    }

    window.location.href = "career_choice.php";
    return;
}

window.location.href = "career_choice.php";

  } catch (err) {
    console.error(err);
    alert("Network error");
  }
});
</script>

<script>
const LABELS = {
  R: "Realistic",
  I: "Investigative",
  A: "Artistic",
  S: "Social",
  E: "Enterprising",
  C: "Conventional"
};

function safeParse(jsonStr) {
  try {
    return JSON.parse(jsonStr);
  } catch {
    return null;
  }
}

function addRow(listEl, label, value) {
  const li = document.createElement("li");
  li.innerHTML = `<span class="label">${label}</span><span>${value}</span>`;
  listEl.appendChild(li);
}

const dbResult = <?php echo json_encode($dbResult, JSON_UNESCAPED_UNICODE); ?>;

const raw = localStorage.getItem("holland_result");
const localData = raw ? safeParse(raw) : null;

const data = localData  || dbResult;

const top3El = document.getElementById("top3");
const percentList = document.getElementById("percentList");
const summaryEl = document.getElementById("summaryText");
const errEl = document.getElementById("err");

if (!data) {
  errEl.textContent = "No result found. Please complete the test first.";
  top3El.textContent = "—";
  summaryEl.textContent = "—";
} else {
  const percentagesRaw =
    typeof data.percentages === "string"
      ? (safeParse(data.percentages) || {})
      : (data.percentages || {});

  const top3 = data.top3_code || data.top3 || "—";

  const summary =
    data.summary ||
    data.summary_text ||
    (typeof data.output === "string" ? data.output : "") ||
    "—";

  top3El.textContent = top3;

  percentList.innerHTML = "";

  const sorted = Object.entries(percentagesRaw)
    .filter(([k]) => LABELS[k] !== undefined)
    .map(([k, v]) => [k, parseFloat(String(v).replace("%", "").trim())])
    .filter(([_, v]) => !Number.isNaN(v))
    .sort((a, b) => b[1] - a[1]);

  if (sorted.length === 0) {
    errEl.textContent = "Percentages data is missing.";
  } else {
    errEl.textContent = "";
    sorted.forEach(([key, value]) => {
      addRow(percentList, `${LABELS[key]} (${key})`, `${value}%`);
    });
  }

  summaryEl.textContent = summary ? summary : "—";

  setTimeout(() => {
    try {
      if (data._saved_to_db) return;

      const answers =
        data.answers ||
        safeParse(localStorage.getItem("holland_answers") || "[]") ||
        [];

      const payload = {
        test_version: data.test_version || "v1",
        top3_code: top3,
        scores: data.scores || {},
        percentages: Object.fromEntries(sorted.map(([k, v]) => [k, v])),
        summary_text: summary || "",
        answers: answers
      };

      fetch("save_holland.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        credentials: "same-origin",
        body: JSON.stringify(payload)
      })
      .then(r => r.text())
      .then(t => {
        let res = null;
        try { res = JSON.parse(t); } catch {}

       if (res && res.ok) {
  localStorage.removeItem("holland_result");
  localStorage.removeItem("holland_answers");
  window.location.reload();
}
      })
      .catch(() => {});
    } catch (_) {}
  }, 0);
}
</script>

</body>
</html>