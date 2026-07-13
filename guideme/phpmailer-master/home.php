<?php
session_start();
$showWelcome = false;

if (isset($_SESSION['show_welcome'])) {
    $showWelcome = true;
    unset($_SESSION['show_welcome']); // مهم عشان ما تتكرر
}
require_once __DIR__ . '/config/db.php';

$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

$hasResult = false;

if ($isLoggedIn) {
    $user_id = (int) $_SESSION['user_id'];

    $stmt = $conn->prepare("SELECT result_id FROM hollandresult WHERE user_id = ? LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $hasResult = true;
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>GuideMe | Home</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Inter', Arial, sans-serif;
      background: #f6f9ff;
      color: #1e293b;
      line-height: 1.6;
    }

    a {
      text-decoration: none;
      color: inherit;
    }

    .container {
      width: min(1180px, 92%);
      margin: auto;
    }

    header {
      position: sticky;
      top: 0;
      z-index: 100;
      background: rgba(255,255,255,0.92);
      backdrop-filter: blur(10px);
      border-bottom: 1px solid #e5edff;
    }

    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 13px 22px;
      border-radius: 14px;
      font-weight: 700;
      transition: 0.2s ease;
      border: none;
      cursor: pointer;
    }

    .btn-outline {
      background: #ffffff;
      border: 1px solid #d7e2ff;
      color: #65435c;
    }

    .btn-outline:hover {
      background: #f3f7ff;
    }

    .btn-primary {
      background: #65435c;
      color: white;
      box-shadow: 0 12px 24px rgba(36, 70, 255, 0.20);
    }

    .btn-primary:hover {
      transform: translateY(-1px);
    }

    .disabled-btn {
      background: #c9c9c9 !important;
      color: #ffffff !important;
      cursor: not-allowed !important;
      box-shadow: none !important;
      opacity: 0.85;
    }

    .disabled-btn:hover {
      transform: none !important;
    }

    .result-wrapper {
      position: relative;
      display: inline-flex;
    }

    .warning-icon {
      position: absolute;
      top: -13px;
      right: -10px;
      width: 24px;
      height: 24px;
      border-radius: 50%;
      background: #ef4444;
      color: white;
      display: grid;
      place-items: center;
      font-size: 14px;
      font-weight: 800;
      cursor: pointer;
      box-shadow: 0 6px 14px rgba(239, 68, 68, 0.35);
      z-index: 2;
    }

    .hero {
      position: relative;
      background: #ececf2;
      padding: 80px 0 140px;
      overflow: hidden;
    }

    .hero::after {
      content: "";
      position: absolute;
      bottom: -150px;
      left: 50%;
      transform: translateX(-50%);
      width: 140%;
      height: 300px;
      background: #f6f9ff;
      border-radius: 50%;
      z-index: 0;
    }

    .hero .container {
      position: relative;
      z-index: 1;
    }

    .hero-grid {
      display: grid;
      grid-template-columns: 1.1fr 0.9fr;
      gap: 42px;
      align-items: center;
    }

    .hero h1 {
      font-size: 56px;
      line-height: 1.1;
      margin-bottom: 18px;
      color: #0f172a;
    }

    .hero p {
      font-size: 17px;
      color: #475569;
      max-width: 620px;
      margin-bottom: 28px;
    }

    .hero-buttons {
      display: grid;
      grid-template-columns: 1fr;
      gap: 14px;
      margin-bottom: 16px;
    }

    .hero-stats {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
    }

    .hero-stats .stat {
      text-align: center;
      display: block;
      padding: 12px;
      border-radius: 16px;
    }

    .stat h3 {
      color: #65435c;
      margin-bottom: 4px;
      font-size: 16px;
    }

    .stat span {
      color: #64748b;
      font-size: 12px;
    }

    .hero-card {
      background: #ffffff;
      border-radius: 28px;
      padding: 28px;
      box-shadow: 0 18px 50px rgba(36, 70, 255, 0.12);
      border: 1px solid #e5edff;
    }

    .mini-card {
      background: #f8fbff;
      border: 1px solid #e3ecff;
      border-radius: 22px;
      padding: 22px;
      margin-bottom: 16px;
    }

    .mini-card:last-child {
      margin-bottom: 0;
    }

    .mini-card h3 {
      font-size: 18px;
      color: #0f172a;
      margin-bottom: 8px;
    }

    .mini-card p {
      color: #64748b;
      font-size: 14px;
    }

    .progress {
      height: 10px;
      background: #dbe7ff;
      border-radius: 999px;
      overflow: hidden;
      margin-top: 14px;
    }

    .progress span {
      display: block;
      height: 100%;
      width: 76%;
      background: linear-gradient(135deg, #2ea943, #58c96b);
      border-radius: 999px;
    }

    section {
      padding: 36px 0;
    }

    .section-title {
      text-align: center;
      margin-bottom: 14px;
      color: #0f172a;
      font-size: 38px;
      font-weight: 800;
    }

    .section-subtitle {
      text-align: center;
      color: #64748b;
      max-width: 760px;
      margin: 0 auto 34px;
      font-size: 16px;
    }

    .features-grid,
    .career-grid,
    .why-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 22px;
    }

    .feature-card,
    .career-card,
    .why-card {
      background: #ffffff;
      border: 1px solid #e5edff;
      border-radius: 24px;
      padding: 26px;
      box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05);
      transition: 0.2s ease;
    }

    .feature-card:hover,
    .career-card:hover,
    .why-card:hover {
      transform: translateY(-4px);
    }

    .icon {
      width: 52px;
      height: 52px;
      border-radius: 16px;
      display: grid;
      place-items: center;
      font-size: 24px;
      background: #eef3ff;
      color: #65435c;
      margin-bottom: 16px;
      font-weight: 800;
    }

    .feature-card h3,
    .career-card h3,
    .why-card h3 {
      margin-bottom: 10px;
      font-size: 20px;
      color: #0f172a;
    }

    .feature-card p,
    .career-card p,
    .why-card p {
      color: #64748b;
      font-size: 15px;
    }

    #features {
      position: relative;
      background: #f6f9ff;
      padding: 80px 0 140px;
      overflow: hidden;
    }

    #how-it-works {
      background: #ececf2;
      border-top-left-radius: 120px;
      border-bottom-right-radius: 120px;
      overflow: hidden;
      margin: 70px 0;
    }

    .steps {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 20px;
    }

    .step {
      background: #ffffff;
      border: 1px solid #e5edff;
      border-radius: 24px;
      padding: 24px;
      text-align: center;
      box-shadow: 0 10px 25px rgba(15, 23, 42, 0.05);
    }

    .step-number {
      width: 52px;
      height: 52px;
      margin: 0 auto 14px;
      border-radius: 50%;
      background: #65435c;
      color: white;
      display: grid;
      place-items: center;
      font-size: 20px;
      font-weight: 800;
    }

    .step h3 {
      margin-bottom: 8px;
      font-size: 19px;
    }

    .step p {
      color: #64748b;
      font-size: 14px;
    }

    .cta {
      padding: 70px 0;
    }

    .cta-box {
      background: #65435c;
      color: white;
      border-radius: 32px;
      padding: 48px 32px;
      text-align: center;
      box-shadow: 0 18px 50px rgba(36, 70, 255, 0.22);
    }

    .cta-box h2 {
      font-size: 40px;
      margin-bottom: 12px;
    }

    .cta-box p {
      max-width: 760px;
      margin: 0 auto 24px;
      color: rgba(255,255,255,0.9);
      font-size: 16px;
    }

    .cta-box .btn-primary {
      background: white;
      color: #65435c;
      box-shadow: none;
    }

    .modal,
    .login-modal,
    .no-result-modal {
      display: none;
      position: fixed;
      z-index: 2000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background: rgba(15, 23, 42, 0.55);
      justify-content: center;
      align-items: center;
    }

    .modal-content {
      background: #fff;
      width: 90%;
      max-width: 700px;
      border-radius: 20px;
      padding: 28px;
      box-shadow: 0 18px 40px rgba(0,0,0,0.18);
      position: relative;
      text-align: left;
    }

    .modal-content h2 {
      margin-top: 0;
      color: #1d1b4b;
      margin-bottom: 16px;
    }

    .modal-content ul {
      padding-left: 20px;
      color: #475569;
      line-height: 1.8;
    }

    .close {
      position: absolute;
      top: 14px;
      right: 18px;
      font-size: 28px;
      cursor: pointer;
      color: #64748b;
    }

    .modal-buttons {
      display: flex;
      gap: 12px;
      margin-top: 20px;
      flex-wrap: wrap;
    }

    .login-box,
    .no-result-box {
      background: white;
      width: 90%;
      max-width: 420px;
      border-radius: 24px;
      padding: 32px;
      text-align: center;
      box-shadow: 0 20px 45px rgba(0,0,0,0.20);
    }

    .login-box h2,
    .no-result-box h2 {
      color: #65435c;
      margin-bottom: 10px;
      font-size: 26px;
    }

    .login-box p,
    .no-result-box p {
      color: #64748b;
      margin-bottom: 22px;
    }

    .no-result-icon {
      width: 58px;
      height: 58px;
      border-radius: 50%;
      background: #fff1f2;
      color: #ef4444;
      display: grid;
      place-items: center;
      margin: 0 auto 14px;
      font-size: 30px;
      font-weight: 900;
    }

    .login-actions,
    .no-result-actions {
      display: flex;
      gap: 12px;
      justify-content: center;
      flex-wrap: wrap;
    }

    footer {
      background: #0f172a;
      color: #cbd5e1;
      margin-top: 40px;
    }

    @media (max-width: 992px) {
      .hero-grid,
      .features-grid,
      .career-grid,
      .why-grid,
      .steps {
        grid-template-columns: 1fr 1fr;
      }

      .hero h1 {
        font-size: 44px;
      }
    }

    @media (max-width: 700px) {
      .hero-grid,
      .features-grid,
      .career-grid,
      .why-grid,
      .steps {
        grid-template-columns: 1fr;
      }

      .hero {
        padding-top: 50px;
      }

      .hero h1 {
        font-size: 36px;
      }

      .section-title {
        font-size: 30px;
      }

      .cta-box h2 {
        font-size: 30px;
      }

      .btn {
        padding: 12px 16px;
        font-size: 14px;
      }
    }
  </style>
</head>

<body>

<header>
  <?php include 'navbar.php'; ?>

</header>

<main>
    <?php include 'career_guidance_bot/chat_widget.php'; ?> 

  <section class="hero">
    <div class="container hero-grid">
      <div>
        <h1>Discover the career path that fits your future.</h1>

        <p>
          GuideMe helps users explore career paths, understand required skills, and receive personalized recommendations based on interests, personality, and assessment results.
        </p>

        <div class="hero-buttons">
          <button class="btn btn-primary protected-action" onclick="openInstructions()">
            Discover Your Path 
          </button>
        </div>

        <div class="hero-stats">
          <a href="career_path.php" class="stat btn btn-outline protected-link">
            <h3>Career Paths</h3>
            <span>Explore multiple options</span>
          </a>

          <a href="dashboard/dashboard.php" class="stat btn btn-outline protected-link">
            <h3>Learning Plan</h3>
            <span>Know what to improve</span>
          </a>
        </div>
      </div>

      <div class="hero-card">
        <div class="mini-card">
          <h3>Take quiz</h3>
          <p>Take the quiz, review your matched careers, and follow a learning plan built for your goals.</p>
        </div>

        <div class="mini-card">
          <h3>Recommended Career</h3>
          <p><strong>Software Development</strong><br>Match your interests, problem-solving skills, and digital strengths.</p>
        </div>

        <div class="mini-card">
          <h3>Next Step</h3>
          <p>Complete your assessment to unlock recommended careers and skill plans.</p>
          <div class="progress"><span></span></div>
        </div>
      </div>
    </div>
  </section>

  <section id="features">
    <div class="container">
      <h2 class="section-title">What GuideMe Offers</h2>
      <p class="section-subtitle">
        Everything you need to better understand your future path and make more informed career decisions.
      </p>

      <div class="features-grid">
        <div class="feature-card">
          <div class="icon">01</div>
          <h3>Career Discovery</h3>
          <p>Explore careers that match your interests, strengths, and assessment results in a simple and clear way.</p>
        </div>

        <div class="feature-card">
          <div class="icon">02</div>
          <h3>Skills Guidance</h3>
          <p>Learn the important skills needed for each career and understand what you should focus on improving.</p>
        </div>

        <div class="feature-card">
          <div class="icon">03</div>
          <h3>Personalized Results</h3>
          <p>Get recommendations designed around your profile, personality, and career interests.</p>
        </div>
      </div>
    </div>
  </section>

  <section id="how-it-works">
    <div class="container">
      <h2 class="section-title">How It Works</h2>
      <p class="section-subtitle">
        GuideMe makes the journey simple, organized, and easy to follow from the first step.
      </p>

      <div class="steps">
        <div class="step">
          <div class="step-number">1</div>
          <h3>Create Account</h3>
          <p>Sign up and access your personal dashboard and career guidance tools.</p>
        </div>

        <div class="step">
          <div class="step-number">2</div>
          <h3>Take the Quiz</h3>
          <p>Answer a set of questions to identify your interests and career preferences.</p>
        </div>

        <div class="step">
          <div class="step-number">3</div>
          <h3>View Results</h3>
          <p>See career recommendations and learn which paths fit you best.</p>
        </div>

        <div class="step">
          <div class="step-number">4</div>
          <h3>Build Your Plan</h3>
          <p>Follow a learning plan and improve the skills needed for your chosen direction.</p>
        </div>
      </div>
    </div>
  </section>

  <section id="careers">
    <div class="container">
      <h2 class="section-title">Explore Career Paths</h2>
      <p class="section-subtitle">
        Preview some of the career areas you can explore through GuideMe.
      </p>

      <div class="career-grid">
        <div class="career-card">
          <h3>Software Development</h3>
          <p>Build applications, solve technical problems, and create digital solutions.</p>
        </div>

        <div class="career-card">
          <h3>Cybersecurity</h3>
          <p>Protect systems, networks, and data from risks and cyber threats.</p>
        </div>

        <div class="career-card">
          <h3>Data Analysis</h3>
          <p>Work with data to discover insights, trends, and support decision-making.</p>
        </div>

        <div class="career-card">
          <h3>UI/UX Design</h3>
          <p>Create digital experiences that are useful, simple, and user-friendly.</p>
        </div>

        <div class="career-card">
          <h3>Network Engineering</h3>
          <p>Design and manage communication networks and technical infrastructure.</p>
        </div>

        <div class="career-card">
          <h3>Cloud Computing</h3>
          <p>Support modern platforms and services using cloud-based technologies.</p>
        </div>
      </div>
    </div>
  </section>

  <section id="about">
    <div class="container">
      <h2 class="section-title">Why Choose GuideMe?</h2>
      <p class="section-subtitle">
        GuideMe is designed to help users move from confusion to clarity with a more guided experience.
      </p>

      <div class="why-grid">
        <div class="why-card">
          <h3>Simple Experience</h3>
          <p>The platform is easy to use, clear to navigate, and suitable for students and beginners.</p>
        </div>

        <div class="why-card">
          <h3>Focused Recommendations</h3>
          <p>Users receive career suggestions that are more relevant to their own interests and results.</p>
        </div>

        <div class="why-card">
          <h3>Better Direction</h3>
          <p>Instead of guessing what to learn next, users can follow a clearer path with practical guidance.</p>
        </div>
      </div>
    </div>
  </section>

  <section class="cta">
    <div class="container">
      <div class="cta-box">
        <h2>Start your career journey today</h2>
        <p>
          Take the first step with GuideMe and discover careers, skills, and opportunities that match your future goals.
        </p>

        <button class="btn btn-primary protected-action" onclick="openInstructions()">
          Start Your Journey
        </button>
      </div>
    </div>
  </section>
</main>

<div id="testModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeInstructions()">&times;</span>

    <h2>Test Instructions</h2>

    <p>
      <strong>Your journey in GuideMe starts with the personality test.</strong>
      This test is based on the RIASEC model and helps you discover your career interests,
      suitable paths, and the skills you need to develop.
    </p>

    <ul>
      <li><strong>Read Carefully:</strong> Read each question carefully before answering.</li>
      <li><strong>Choose Honestly:</strong> Choose the option that best matches your personality and interests.</li>
      <li><strong>No Right or Wrong:</strong> There are no right or wrong answers.</li>
      <li><strong>Be Honest:</strong> Answer honestly to get the most accurate result.</li>
      <li><strong>Latest Result:</strong> If you take the test more than once, only your latest result will be saved.</li>
    </ul>

    <div class="modal-buttons">
      <button class="btn btn-outline" onclick="closeInstructions()">Cancel</button>

      <a href="test.php" class="btn btn-primary protected-link">Start Test</a>

      <div class="result-wrapper">
        <?php if (!$hasResult): ?>
          <span class="warning-icon" onclick="openNoResultModal()">!</span>
        <?php endif; ?>

        <a
          href="<?php echo $hasResult ? 'result.php' : '#'; ?>"
          class="btn btn-primary <?php echo $hasResult ? 'protected-link' : 'disabled-btn'; ?>"
          onclick="<?php echo $hasResult ? '' : 'openNoResultModal(); return false;'; ?>"
        >
          Show my result
        </a>
      </div>
    </div>
  </div>
</div>

<div id="loginModal" class="login-modal">
  <div class="login-box">
    <h2>Login Required</h2>
    <p>Please login first to continue using GuideMe.</p>

    <div class="login-actions">
      <a href="auth/login.php" class="btn btn-primary">Login</a>
      <button class="btn btn-outline" onclick="closeLoginModal()">Cancel</button>
    </div>
  </div>
</div>

<div id="noResultModal" class="no-result-modal">
  <div class="no-result-box">
    <div class="no-result-icon">!</div>

    <h2>No Result Found</h2>
    <p>You do not have a result yet. Please take the test first to view your result.</p>

    <div class="no-result-actions">
      <button class="btn btn-outline" onclick="closeNoResultModal()">Cancel</button>
      <a href="test.php" class="btn btn-primary">Take Test</a>
    </div>
  </div>
</div>

<script>
const isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;

function openInstructions() {
  if (!isLoggedIn) {
    showLoginModal();
    return;
  }

  document.getElementById("testModal").style.display = "flex";
}


function showLoginModal() {
  document.getElementById("loginModal").style.display = "flex";
}

function closeLoginModal() {
  document.getElementById("loginModal").style.display = "none";
}
function closeInstructions() {
  document.getElementById("testModal").style.display = "none";
}

function openNoResultModal() {
  document.getElementById("noResultModal").style.display = "flex";
}

function closeNoResultModal() {
  document.getElementById("noResultModal").style.display = "none";
}

document.querySelectorAll(".protected-link").forEach(link => {
  link.addEventListener("click", function(event) {
    if (!isLoggedIn) {
      event.preventDefault();
      showLoginModal();
    }
  });
});

window.onclick = function(event) {
  const testModal = document.getElementById("testModal");
  const loginModal = document.getElementById("loginModal");
  const noResultModal = document.getElementById("noResultModal");

  if (event.target === testModal) {
    testModal.style.display = "none";
  }

  if (event.target === loginModal) {
    loginModal.style.display = "none";
  }

  if (event.target === noResultModal) {
    noResultModal.style.display = "none";
  }
};
// عرض نافذة الترحيب
const showWelcome = <?php echo $showWelcome ? 'true' : 'false'; ?>;

document.addEventListener("DOMContentLoaded", function () {

  if (isLoggedIn && showWelcome) {
    document.getElementById("welcomeModal").style.display = "flex";
  }

});

// إغلاق النافذة
function closeWelcomeModal() {
  document.getElementById("welcomeModal").style.display = "none";
}
</script>
<!-- Welcome Modal -->

<div id="welcomeModal" class="login-modal">
  <div class="login-box">

    <h2>Welcome to GuideMe </h2>
    <p>You are logged in successfully. Continue your journey with GuideMe.</p>

    <div class="login-actions">
      <button class="btn btn-primary" onclick="closeWelcomeModal()">Continue</button>
    </div>

  </div>
</div>
</body>
</html>