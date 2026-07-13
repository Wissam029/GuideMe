<?php
session_start();

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
  header("Location: login.php");
  exit();
}

include "config/db.php";
mysqli_set_charset($conn, "utf8mb4");

/* Fetch careers */
$careers = [];
$sql = "SELECT career_id, job_title FROM careers ORDER BY job_title ASC";
$res = mysqli_query($conn, $sql);

if ($res) {
  while ($row = mysqli_fetch_assoc($res)) {
    $careers[] = $row;
  }
}
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Choose Career</title>

<style>
*{
  box-sizing:border-box;
}

body{
  margin:0;
  font-family:Arial, Helvetica, sans-serif;
  background:#f4f8ff;
  color:#1e293b;
  min-height:100vh;
}

.page-wrapper{
  width:min(1000px, 92%);
  margin:0 auto;
  padding:45px 0 70px;
}

.hero-card{
  background:#ffffff;
  border:1px solid #e5edff;
  border-radius:28px;
  padding:36px;
  box-shadow:0 18px 45px rgba(101,67,92,0.10);
}

.page-header{
  text-align:center;
  margin-bottom:28px;
}

.badge{
  display:inline-block;
  padding:8px 18px;
  border-radius:999px;
  background:#f3eaf1;
  color:#65435c;
  font-size:13px;
  font-weight:800;
  margin-bottom:14px;
}

h1{
  margin:0;
  font-size:34px;
  color:#65435c;
  font-weight:800;
}

.subtitle{
  margin:12px auto 0;
  color:#64748b;
  font-size:16px;
  line-height:1.7;
  max-width:620px;
}

.form-card{
  max-width:760px;
  margin:30px auto 0;
  background:#f8fbff;
  border:1px solid #e5edff;
  border-radius:24px;
  padding:28px;
}

.select-label{
  display:block;
  text-align:left;
  color:#334155;
  font-weight:700;
  margin-bottom:10px;
}

.select-box{
  width:100%;
  padding:16px 18px;
  font-size:16px;
  border-radius:16px;
  border:1px solid #d9e2f3;
  background:#ffffff;
  color:#334155;
  outline:none;
}

.select-box:focus{
  border-color:#65435c;
  box-shadow:0 0 0 4px rgba(101,67,92,0.12);
}

.actions-row{
  display:flex;
  gap:14px;
  margin-top:22px;
}

.btn{
  flex:1;
  padding:15px 18px;
  font-size:16px;
  border-radius:16px;
  border:none;
  background:#65435c;
  color:white;
  font-weight:800;
  cursor:pointer;
  transition:.2s;
}

.btn:hover{
  transform:translateY(-2px);
  box-shadow:0 12px 24px rgba(101,67,92,0.18);
}

.btn:disabled{
  opacity:.65;
  cursor:not-allowed;
  transform:none;
  box-shadow:none;
}

.btn-secondary{
  background:#ffffff;
  color:#65435c;
  border:1px solid #dac9d5;
}

.btn-secondary:hover{
  background:#f7eef5;
}

.msg{
  margin-top:18px;
  font-weight:700;
  min-height:24px;
  text-align:center;
}

.msg.error{
  color:#dc2626;
}

.msg.loading{
  color:#475569;
}

.msg.success{
  color:#16a34a;
}

#cardContainer{
  margin-top:34px;
  display:flex;
  justify-content:center;
}

.analysis-card{
  width:100%;
  max-width:820px;
  background:#ffffff;
  border:1px solid #e5edff;
  border-radius:26px;
  padding:32px;
  text-align:center;
  box-shadow:0 18px 42px rgba(101,67,92,0.10);
}

.analysis-title{
  font-size:32px;
  font-weight:800;
  color:#65435c;
  margin:0 0 12px;
}

.analysis-match{
  display:inline-block;
  font-size:18px;
  font-weight:800;
  color:#ffffff;
  background:#65435c;
  padding:10px 22px;
  border-radius:999px;
  margin-bottom:18px;
}

.analysis-submatches{
  display:flex;
  justify-content:center;
  gap:12px;
  flex-wrap:wrap;
  margin-bottom:22px;
}

.analysis-pill{
  background:#f3eaf1;
  color:#65435c;
  font-size:14px;
  font-weight:800;
  padding:9px 16px;
  border-radius:999px;
}

.analysis-section{
  margin-top:22px;
}

.analysis-section h3{
  margin:0 0 12px;
  font-size:18px;
  color:#334155;
}

.tags{
  display:flex;
  flex-wrap:wrap;
  gap:10px;
  justify-content:center;
}

.tag{
  background:#f4f8ff;
  border:1px solid #e5edff;
  color:#475569;
  padding:8px 14px;
  border-radius:999px;
  font-size:14px;
  font-weight:700;
}

.analysis-desc{
  margin-top:24px;
  line-height:1.8;
  color:#475569;
  text-align:center;
}

.save-btn{
  margin-top:26px;
  max-width:260px;
}

.spinner{
  width:18px;
  height:18px;
  border:3px solid #ddd;
  border-top:3px solid #65435c;
  border-radius:50%;
  animation:spin 0.8s linear infinite;
  display:inline-block;
  vertical-align:middle;
  margin-right:8px;
}

@keyframes spin{
  100%{
    transform:rotate(360deg);
  }
}

@media (max-width:640px){
  .hero-card{
    padding:24px 18px;
  }

  h1{
    font-size:28px;
  }

  .actions-row{
    flex-direction:column;
  }

  .analysis-title{
    font-size:26px;
  }
}
</style>
</head>

<body>

 <header>
  <?php include 'navbar.php'; ?>
  </header>
  <?php include 'career_guidance_bot/chat_widget.php'; ?> 

<main class="page-wrapper">
  <section class="hero-card">

    <div class="page-header">
      <div class="badge">Career Selection</div>
      <h1>Select a Career</h1>
      <p class="subtitle">
        Please choose one career from the list to continue with the gap analysis.
      </p>
    </div>

    <div class="form-card">
      <label for="career" class="select-label">Choose Career</label>

      <select id="career" class="select-box">
        <option value="">-- Select career --</option>
        <?php foreach($careers as $c): ?>
          <option value="<?= htmlspecialchars($c['career_id']) ?>">
            <?= htmlspecialchars($c['job_title']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <div class="actions-row">
        <button type="button" class="btn btn-secondary" onclick="goBack()">Back</button>
        <button type="button" id="analyzeBtn" class="btn" onclick="sendCareer()">Gap Analysis</button>
      </div>

      <div id="msg" class="msg"></div>
    </div>

    <div id="cardContainer"></div>

  </section>
</main>

<script>
function goBack(){
  window.location.href = "career_choice.php";
}

async function sendCareer(){
  const careerId = document.getElementById("career").value;
  const msg = document.getElementById("msg");
  const cardContainer = document.getElementById("cardContainer");
  const analyzeBtn = document.getElementById("analyzeBtn");

  if(!careerId){
    msg.className = "msg error";
    msg.innerText = "Please select a career first";
    return;
  }

  msg.className = "msg loading";
  msg.innerHTML = `<span class="spinner"></span>Loading analysis...`;
  cardContainer.innerHTML = "";
  analyzeBtn.disabled = true;

  try{
    const r = await fetch("api_career.php",{
      method:"POST",
      headers:{"Content-Type":"application/json"},
      credentials:"same-origin",
      body:JSON.stringify({
        mode:"manual",
        chosen_career_id: careerId
      })
    });

    const data = await r.json();

    if(data && (data.output || data.mode || data.career || data.careers)){
      renderCareerCard(data);
      msg.innerText = "";
      msg.className = "msg";
    }else{
      msg.className = "msg error";
      msg.innerText = data.error || "Error loading analysis";
    }

  }catch(e){
    console.error(e);
    msg.className = "msg error";
    msg.innerText = "Analysis service is currently unavailable. Please try again later";
  }finally{
    analyzeBtn.disabled = false;
  }
}

function renderCareerCard(data){
  const cardContainer = document.getElementById("cardContainer");
  const payload = data.output || data;
  const career = payload.career || (payload.careers ? payload.careers[0] : null);

  if(!career){
    document.getElementById("msg").className = "msg error";
    document.getElementById("msg").innerText = "Error loading analysis";
    cardContainer.innerHTML = "";
    return;
  }

  const strengths = (career.strengths || []).length
    ? career.strengths.map(s => `<span class="tag">${escapeHtml(s)}</span>`).join("")
    : `<span class="tag">No strengths found</span>`;

  const gaps = (career.gaps || []).length
    ? career.gaps.map(g => `<span class="tag">${escapeHtml(g)}</span>`).join("")
    : `<span class="tag">No gaps found</span>`;

  cardContainer.innerHTML = `
    <div class="analysis-card">
      <h2 class="analysis-title">${escapeHtml(career.career_title || "")}</h2>

      <div class="analysis-match">
        Overall Match: ${career.overall_match ?? "-"}%
      </div>

      <div class="analysis-submatches">
        <div class="analysis-pill">Holland: ${career.holland_match ?? "-"}%</div>
        <div class="analysis-pill">Skills: ${career.skills_match ?? "-"}%</div>
      </div>

      <div class="analysis-section">
        <h3>Strengths</h3>
        <div class="tags">${strengths}</div>
      </div>

      <div class="analysis-section">
        <h3>Gaps</h3>
        <div class="tags">${gaps}</div>
      </div>

      <div class="analysis-desc">
        ${escapeHtml(career.ai_explanation || "")}
      </div>

      <button type="button" class="btn save-btn" data-career='${escapeHtml(JSON.stringify(career))}'>
        Save and Continue
      </button>
    </div>
  `;
}

async function saveAndContinue(career){
  const confirmed = confirm("Are you sure you want to choose this career path?");
  if(!confirmed) return;

  const msg = document.getElementById("msg");
  msg.className = "msg loading";
  msg.innerHTML = `<span class="spinner"></span>Saving...`;

  try{
    const res = await fetch("api_career.php",{
      method:"POST",
      headers:{"Content-Type":"application/json"},
      credentials:"same-origin",
      body:JSON.stringify({
        action:"save_selected_career",
        career_id:career.career_id,
        career_title:career.career_title,
        overall_match:career.overall_match,
        holland_match:career.holland_match,
        skills_match:career.skills_match,
        strengths:career.strengths,
        gaps:career.gaps
      })
    });

    const data = await res.json();

    if(data.status === "success"){
      msg.className = "msg success";
      msg.innerText = "Saved successfully! Redirecting...";

      setTimeout(() => {
        window.location.href = "dashboard/dashboard.php";
      }, 1000);

    }else{
      msg.className = "msg error";
      msg.innerText = data.message || "Save failed";
    }

  }catch(e){
    console.error(e);
    msg.className = "msg error";
    msg.innerText = "Network error while saving";
  }
}

function escapeHtml(text){
  return String(text)
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}

document.addEventListener("click", (e) => {
  const btn = e.target.closest(".save-btn");
  if(!btn) return;

  const career = JSON.parse(btn.getAttribute("data-career"));
  saveAndContinue(career);
});
</script>

</body>
</html>