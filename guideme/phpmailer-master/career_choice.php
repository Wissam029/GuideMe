<?php
session_start();

if (empty($_SESSION['user_logged_in']) || empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Career Choice</title>

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
  padding:42px 36px;
  box-shadow:0 18px 45px rgba(101,67,92,0.10);
  text-align:center;
}

.badge{
  display:inline-block;
  padding:8px 18px;
  border-radius:999px;
  background:#f3eaf1;
  color:#65435c;
  font-size:13px;
  font-weight:800;
  margin-bottom:16px;
}

h1{
  margin:0;
  font-size:34px;
  color:#65435c;
  font-weight:800;
  line-height:1.3;
}

.subtitle{
  margin:14px auto 34px;
  color:#64748b;
  font-size:16px;
  line-height:1.7;
  max-width:620px;
}

.choice-card{
  width:100%;
  max-width:760px;
  margin:0 auto;
  background:#f8fbff;
  border:1px solid #e5edff;
  border-radius:24px;
  padding:28px;
}

.btn{
  width:100%;
  display:block;
  padding:17px 20px;
  border-radius:16px;
  border:1px solid transparent;
  font-size:16px;
  font-weight:800;
  cursor:pointer;
  transition:.2s;
}

.btn + .btn{
  margin-top:16px;
}

.btn-primary{
  background:#65435c;
  color:#ffffff;
  box-shadow:0 10px 24px rgba(101,67,92,0.18);
}

.btn-primary:hover{
  transform:translateY(-2px);
  box-shadow:0 14px 28px rgba(101,67,92,0.22);
}

.btn-secondary{
  background:#ffffff;
  color:#65435c;
  border-color:#dac9d5;
}

.btn-secondary:hover{
  background:#f7eef5;
  transform:translateY(-2px);
}

@media (max-width:640px){
  .hero-card{
    padding:28px 18px;
  }

  h1{
    font-size:28px;
  }

  .choice-card{
    padding:20px;
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

    <div class="badge">Career Path</div>

    <h1>How would you like to choose your career path?</h1>

    <p class="subtitle">
      Choose one option to continue and start building your career direction.
    </p>

    <div class="choice-card">
      <button id="btnAI" class="btn btn-primary" type="button">
        Access AI-generated Career Paths and Gaps
      </button>

      <button id="btnManual" class="btn btn-secondary" type="button">
        Choose Career Manually
      </button>
    </div>

  </section>
</main>

<script>
document.getElementById("btnAI").onclick = () => {
  window.location.href = "career_suggestions.php";
};

document.getElementById("btnManual").onclick = () => {
  window.location.href = "career_manual.php";
};
</script>

</body>
</html>