<?php
session_start();
?>

<!doctype html>
<html lang="en">
  
</html><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>AI Career Paths</title>

<style>
:root{
  --bg:#f4f8ff;
  --text:#1e293b;
  --muted:#64748b;
  --purple:#65435c;
  --purple-dark:#4f3348;
  --blue:#1f57ff;
  --blue-dark:#1646d6;
  --card:#ffffff;
  --border:#e5edff;
  --soft:#f8fbff;
  --chip-bg:#eef3ff;
  --chip-text:#3554b8;
  --success:#22c55e;
  --overlay:rgba(15,23,42,.45);
}

*{
  box-sizing:border-box;
}

body{
  margin:0;
  font-family:Arial, Helvetica, sans-serif;
  background:
    radial-gradient(circle at top left, rgba(101,67,92,0.14) 0, transparent 32%),
    linear-gradient(180deg,#ffffff 0%, var(--bg) 100%);
  color:var(--text);
  min-height:100vh;
}

.page{
  width:min(1180px,92%);
  margin:38px auto 20px;
}

.hero{
  background:#ffffff;
  border:1px solid var(--border);
  border-radius:28px;
  padding:34px;
  box-shadow:0 18px 45px rgba(31,87,255,.08);
  margin-bottom:24px;
}

.hero h2{
  margin:0 0 10px;
  color:var(--purple);
  font-size:34px;
  font-weight:800;
}

.hero p{
  margin:0;
  color:var(--muted);
  line-height:1.7;
  max-width:720px;
}

.state{
  background:#ffffff;
  border:1px dashed #cbd5e1;
  border-radius:18px;
  padding:18px 20px;
  color:var(--muted);
  box-shadow:0 12px 28px rgba(15,23,42,.05);
}

.cards{
  display:grid;
  grid-template-columns:repeat(3,1fr);
  gap:22px;
  align-items:start;
}

.card{
  position:relative;
  background:var(--card);
  border:1px solid var(--border);
  border-radius:24px;
  padding:22px;
  min-height:320px;
  display:flex;
  flex-direction:column;
  box-shadow:0 14px 32px rgba(15,23,42,.06);
  transition:.2s ease;
  z-index:1;
}

.card:hover{
  transform:translateY(-6px);
  box-shadow:0 22px 45px rgba(31,87,255,.12);
}

.title{
  margin:0 0 14px;
  font-size:21px;
  font-weight:800;
  color:var(--purple);
  line-height:1.35;
  padding-right:40px;
}

.meta{
  display:flex;
  flex-wrap:wrap;
  gap:8px;
  margin:0 0 16px;
}

.meta span{
  display:inline-block;
  padding:7px 11px;
  border-radius:999px;
  background:#f1f5ff;
  border:1px solid #dbe6ff;
  font-size:12px;
  color:#4660a8;
  font-weight:700;
}

.preview{
  color:var(--muted);
  font-size:14px;
  line-height:1.8;
  margin:0 0 18px;
  min-height:86px;
}

.more-btn,
.choose,
.back-btn,
.primary{
  border:none;
  border-radius:14px;
  font-weight:800;
  cursor:pointer;
  transition:.15s ease;
}

.more-btn{
  width:100%;
  padding:12px 14px;
  background:#f1f5f9;
  color:var(--purple);
  margin-top:auto;
}

.more-btn:hover{
  background:#e8edf5;
}

.details{
  display:none;
  margin-top:16px;
  border-top:1px solid var(--border);
  padding-top:16px;
}

.section{
  font-weight:800;
  margin:0 0 9px;
  color:var(--purple);
  font-size:14px;
}

.chips{
  display:flex;
  flex-wrap:wrap;
  gap:8px;
  margin:0 0 16px;
}

.chip{
  display:inline-block;
  padding:7px 11px;
  border-radius:999px;
  background:var(--chip-bg);
  color:var(--chip-text);
  font-size:12px;
  font-weight:700;
  line-height:1.3;
}

.desc{
  margin:0 0 16px;
  color:var(--muted);
  line-height:1.8;
  font-size:14px;
}

.choose{
  width:100%;
  padding:13px 16px;
  background:var(--purple);
  color:#fff;
}

.choose:hover{
  background:var(--purple-dark);
}

.footer{
  display:flex;
  gap:14px;
  justify-content:center;
  margin:24px 0 60px;
  flex-wrap:wrap;
}

.back-btn{
  padding:13px 22px;
  background:#eef2f7;
  color:var(--purple);
  min-width:180px;
}

.back-btn:hover{
  background:#65435c;
}

.primary{
  padding:13px 22px;
  background:#65435c;
  color:#fff;
  min-width:250px;
  box-shadow:0 8px 20px rgba(31,87,255,.18);
}



.overlay{
  display:none;
  position:fixed;
  inset:0;
  background:var(--overlay);
  z-index:999;
}

.overlay.show{
  display:block;
}

.card.expanded{
  position:fixed;
  top:50%;
  left:50%;
  transform:translate(-50%,-50%);
  width:min(760px,92vw);
  max-height:85vh;
  overflow:auto;
  z-index:1000;
  box-shadow:0 28px 70px rgba(15,23,42,.32);
}

.card.expanded:hover{
  transform:translate(-50%,-50%);
}

.card.expanded .details{
  display:block !important;
}

.card.expanded .more-btn{
  display:none;
}

.close-btn{
  display:none;
  position:absolute;
  top:16px;
  right:16px;
  width:36px;
  height:36px;
  border-radius:999px;
  background:#fff;
  border:1px solid var(--border);
  cursor:pointer;
  font-size:20px;
  font-weight:800;
  color:var(--muted);
}

.card.expanded .close-btn{
  display:inline-flex;
  align-items:center;
  justify-content:center;
}

.close-btn:hover{
  color:#ef4444;
  border-color:#fecaca;
  background:#fff5f5;
}

.badge{
  display:inline-block;
  width:max-content;
  margin-bottom:12px;
  padding:7px 13px;
  border-radius:999px;
  background:#dcfce7;
  color:#15803d;
  font-size:12px;
  font-weight:800;
}

.choose.current-path{
  background:#dcfce7;
  color:#15803d;
  border:1px solid #bbf7d0;
  cursor:default;
}

.card.selected-card{
  border:2px solid var(--success);
  box-shadow:0 18px 45px rgba(34,197,94,.18);
}

@media(max-width:980px){
  .cards{
    grid-template-columns:repeat(2,1fr);
  }
}

@media(max-width:640px){
  .page{
    width:min(100%,92vw);
    margin:24px auto;
  }

  .hero{
    padding:24px 20px;
    border-radius:22px;
  }

  .hero h2{
    font-size:27px;
  }

  .cards{
    grid-template-columns:1fr;
  }

  .card{
    min-height:auto;
  }

  .footer{
    flex-direction:column;
    align-items:stretch;
    padding:0 16px;
  }

  .back-btn,
  .primary{
    width:100%;
    min-width:unset;
  }

  .card.expanded{
    width:94vw;
    max-height:88vh;
    padding:18px;
  }
}
</style>
</head>
 <header>
  <?php include 'navbar.php'; ?>
  </header>
  <?php include 'career_guidance_bot/chat_widget.php'; ?> 
<body>

<div id="overlay" class="overlay"></div>

<main class="page">

  <section class="hero">
    <h2>Top 3 Suggested Careers</h2>
    <p>
      Based on your Holland code and skills, GuideMe suggests the most suitable career paths for you.
      Review each path, check the strengths and missing skills, then choose the path you want to follow.
    </p>
  </section>

  <div id="state" class="state">Loading suggestions...</div>
  <div id="paths" class="cards"></div>

</main>

<div class="footer">
  <button class="back-btn" id="btnBack" type="button">Back</button>
  <button class="primary" id="btnPrefs" style="display:none;" type="button">Choose Learning Preferences</button>
</div>

<script>
const ROUTES = {
  preferences: "learning_preferences.html",
  profile: "profile.php",
  careerChoice: "career_choice.php"
};

const overlay = document.getElementById("overlay");
let currentExpandedCard = null;

function closeExpandedCard(){
  if (!currentExpandedCard) return;
  currentExpandedCard.classList.remove("expanded");
  overlay.classList.remove("show");
  document.body.style.overflow = "";
  currentExpandedCard = null;
}

function openExpandedCard(card){
  if (currentExpandedCard && currentExpandedCard !== card) {
    currentExpandedCard.classList.remove("expanded");
  }

  currentExpandedCard = card;
  card.classList.add("expanded");
  overlay.classList.add("show");
  document.body.style.overflow = "hidden";
}

const stateEl = document.getElementById("state");
const container = document.getElementById("paths");

let selectedCareerId = null;
let cachedTopJobs = [];

function escapeHtml(str){
  return String(str ?? "")
    .replaceAll("&","&amp;")
    .replaceAll("<","&lt;")
    .replaceAll(">","&gt;")
    .replaceAll('"',"&quot;")
    .replaceAll("'","&#039;");
}

function shortenText(text, maxLength = 180){
  const value = String(text ?? "").trim();
  if (value.length <= maxLength) return value;
  return value.slice(0, maxLength).trim() + "...";
}

function renderChips(arr, emptyText){
  if (!Array.isArray(arr) || !arr.length) {
    return `<span class="chip">${escapeHtml(emptyText)}</span>`;
  }

  return arr
    .slice(0, 10)
    .map(item => `<span class="chip">${escapeHtml(item)}</span>`)
    .join("");
}

function renderCards(topJobs){
  container.innerHTML = topJobs.map((j, index) => `
    <section class="card ${String(j.career_id ?? "") === selectedCareerId ? "selected-card" : ""}" id="card-${index}">
      <button type="button" class="close-btn" data-close="${index}">×</button>

      ${String(j.career_id ?? "") === selectedCareerId ? '<div class="badge">Current Path</div>' : ''}

      <h3 class="title">${escapeHtml(j.career_title ?? "Career")}</h3>

      <div class="meta">
        <span>Overall Match: ${escapeHtml(j.overall_match ?? "-")}%</span>
        <span>Holland Match: ${escapeHtml(j.holland_match ?? "-")}%</span>
        <span>Skills Match: ${escapeHtml(j.skills_match ?? "-")}%</span>
      </div>

      <div class="preview">
        ${escapeHtml(shortenText(j.ai_explanation ?? "No explanation provided."))}
      </div>

      <button class="more-btn" type="button" data-toggle="${index}">
        More Details
      </button>

      <div class="details" id="details-${index}">
        <div class="section">Strengths</div>
        <div class="chips">
          ${renderChips(j.strengths, "No matched skills")}
        </div>

        <div class="section">Missing Skills</div>
        <div class="chips">
          ${renderChips(j.gaps, "No missing skills")}
        </div>

        <div class="section">AI Explanation</div>
        <div class="desc">
          ${escapeHtml(j.ai_explanation ?? "No explanation provided.")}
        </div>

        <button 
          class="choose ${String(j.career_id ?? "") === selectedCareerId ? "current-path" : ""}" 
          type="button"
          data-id="${escapeHtml(j.career_id ?? "")}"
          data-title="${escapeHtml(j.career_title ?? "")}"
          data-overall="${escapeHtml(j.overall_match ?? "")}"
          data-holland="${escapeHtml(j.holland_match ?? "")}"
          data-skills="${escapeHtml(j.skills_match ?? "")}"
          data-strengths='${JSON.stringify(j.strengths || [])}'
          data-gaps='${JSON.stringify(j.gaps || [])}'
          ${String(j.career_id ?? "") === selectedCareerId ? 'disabled' : ''}>
          ${String(j.career_id ?? "") === selectedCareerId ? 'Current Path' : 'Choose This Path'}
        </button>
      </div>
    </section>
  `).join("");
}

async function loadSelectedCareer(){
  try{
    const res = await fetch("api_career.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      credentials: "same-origin",
      body: JSON.stringify({
        action: "get_selected_career"
      })
    });

    const data = await res.json();

    if (data.status === "success") {
      selectedCareerId = data.career_id ? String(data.career_id) : null;
      const hasCareer = selectedCareerId && selectedCareerId !== "0";
      document.getElementById("btnPrefs").style.display = hasCareer ? "inline-block" : "none";
    }
  } catch (e) {
    console.error("Failed to load selected career", e);
  }
}

async function loadSuggestions(){
  await loadSelectedCareer();

  try{
    const res = await fetch("api_career.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      credentials: "same-origin",
      body: JSON.stringify({ mode: "suggestion" })
    });

    const data = await res.json();

    if (data.status === "redirect_to_profile") {
      stateEl.textContent = data.message || "Please update your profile first.";
      setTimeout(() => {
        window.location.href = ROUTES.profile;
      }, 1800);
      return;
    }

    if (data.status === "error") {
      stateEl.textContent = data.message || "System error. Please try again later.";
      return;
    }

    const payload = data.output || data;
    const topJobs = payload.careers || [];
    cachedTopJobs = topJobs;

    if (!topJobs.length) {
      stateEl.textContent = "No suggestions returned.";
      return;
    }

    stateEl.remove();
    renderCards(topJobs);

  } catch (e) {
    stateEl.textContent = "Failed to load suggestions.";
    console.error(e);
  }
}

loadSuggestions();

container.addEventListener("click", (e) => {
  const chooseBtn = e.target.closest("button[data-id]");

  if (chooseBtn) {
    const confirmed = confirm("Are you sure you want to choose this path?");
    if (!confirmed) return;

    const chosen = {
      career_id: chooseBtn.getAttribute("data-id"),
      career_title: chooseBtn.getAttribute("data-title"),
      overall_match: chooseBtn.getAttribute("data-overall"),
      holland_match: chooseBtn.getAttribute("data-holland"),
      skills_match: chooseBtn.getAttribute("data-skills"),
      strengths: chooseBtn.getAttribute("data-strengths"),
      gaps: chooseBtn.getAttribute("data-gaps")
    };

    saveChosenPath(chosen);
    return;
  }

  const toggleBtn = e.target.closest("button[data-toggle]");

  if (toggleBtn) {
    const id = toggleBtn.getAttribute("data-toggle");
    const card = document.getElementById("card-" + id);
    if (card) openExpandedCard(card);
    return;
  }

  const closeBtn = e.target.closest("button[data-close]");

  if (closeBtn) {
    closeExpandedCard();
  }
});

overlay.addEventListener("click", closeExpandedCard);

document.addEventListener("keydown", (e) => {
  if (e.key === "Escape") {
    closeExpandedCard();
  }
});

document.getElementById("btnPrefs").onclick = () => {
  window.location.href = ROUTES.preferences;
};

document.getElementById("btnBack").onclick = () => {
  window.location.href = ROUTES.careerChoice;
};

async function saveChosenPath(chosen){
  console.log("chosen data:", chosen);

  try{
    const res = await fetch("api_career.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      credentials: "same-origin",
      body: JSON.stringify({
        action: "save_selected_career",
        career_id: chosen.career_id,
        career_title: chosen.career_title,
        overall_match: chosen.overall_match,
        holland_match: chosen.holland_match,
        skills_match: chosen.skills_match,
        strengths: chosen.strengths,
        gaps: chosen.gaps
      })
    });

    const data = await res.json();

    if (data.status === "success") {
      selectedCareerId = String(chosen.career_id);
      document.getElementById("btnPrefs").style.display = "inline-block";

      closeExpandedCard();
      renderCards(cachedTopJobs);

      alert("Path saved successfully");
    } else {
      alert(data.message || "Save failed");
    }

  } catch (e) {
    console.error(e);
    alert("Network error while saving");
  }
}
</script>

</body>
</html>