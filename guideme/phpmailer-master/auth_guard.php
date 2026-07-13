<?php

require_once __DIR__ . "/config/secure_session.php";

$isLoggedIn = isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;

if (!$isLoggedIn):
?>

<style>
.login-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.6);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.modal-box {
    background: #fff;
    padding: 30px;
    border-radius: 20px;
    text-align: center;
    width: 320px;
}

.modal-box h2 {
    margin-bottom: 10px;
}

.modal-box button {
    margin-top: 15px;
    padding: 10px 18px;
    border: none;
    border-radius: 10px;
    background: #65435c;
    color: white;
    cursor: pointer;
}
</style>

<div class="login-modal">
    <div class="modal-box">
        <h2>Login Required</h2>
        <p>Please login first</p>
        <button onclick="goLogin()">Login</button>
    </div>
</div>

<script>
function goLogin() {
    window.location.href = "auth/login.php";
}

// يمنع التفاعل مع الصفحة
document.body.style.overflow = "hidden";
</script>

<?php endif; ?>