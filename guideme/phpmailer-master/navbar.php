<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


$base_url = "/guideme1/guideme/phpmailer-master/";
?>

<style>
.gm-header{
  position: sticky;
  top: 0;
  left: 0;
  width: 100%;
  z-index: 9999;
  background: rgba(255,255,255,0.92);
  backdrop-filter: blur(10px);
  border-bottom: 1px solid #e5edff;
}

.gm-container{
  width: min(1180px, 92%);
  margin: auto;
}

.gm-navbar{
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 0;
}

.gm-logo{
  font-size: 26px;
  font-weight: 800;
  color: #65435c;
  text-decoration: none;
}

.gm-nav-links{
  display: flex;
  gap: 28px;
}

.gm-nav-links a{
  color: #475569;
  font-weight: 500;
  text-decoration: none;
}

.gm-nav-links a:hover{
  color: #65435c;
}

.gm-nav-actions{
  display: flex;
  gap: 12px;
}

.gm-btn{
  padding: 13px 22px;
  border-radius: 14px;
  font-weight: 700;
  text-decoration: none;
}

.gm-btn-outline{
  border: 1px solid #d7e2ff;
  color: #65435c;
}

.gm-btn-primary{
  background: #65435c;
  color: white;
}
</style>

<header class="gm-header">
  <div class="gm-container gm-navbar">
    
    <!-- اللوقو -->
    <a href="<?= $base_url ?>home.php" class="gm-logo">GuideMe</a>

    <!-- الروابط -->
    <nav class="gm-nav-links">
      <a href="<?= $base_url ?>home.php">Home</a>

      <?php if (isset($_SESSION['user_id'])): ?>
        <!-- إذا مسجل -->
        <a href="<?= $base_url ?>dashboard/dashboard.php">My Learning Plan</a>
        <a href="<?= $base_url ?>Career_path.php">My Careers</a>
      <?php else: ?>
        <!-- إذا مو مسجل -->
        <a href="#" onclick="loginRequired()">My Learning Plan</a>
        <a href="#" onclick="loginRequired()">My Careers</a>
      <?php endif; ?>
    </nav>

    <!-- الأزرار -->
    <div class="gm-nav-actions">
      <?php if (isset($_SESSION['user_id'])): ?>
        <a href="<?= $base_url ?>profile.php" class="gm-btn gm-btn-primary">Profile</a>
      <?php else: ?>
        <a href="<?= $base_url ?>auth/login.php" class="gm-btn gm-btn-primary">Login</a>
      <?php endif; ?>
    </div>

  </div>
</header>

<script>
function loginRequired() {
  alert("Please login first to access this page.");
}
</script>
