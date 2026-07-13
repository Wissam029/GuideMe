<?php
require "../config/secure_session.php";

$loggedIn = (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true);
$message = "";
$done = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'logout') {

        // حذف بيانات الجلسة
        session_unset();
        session_destroy();

        // حذف كوكي الجلسة (مهم للأمان)
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        $message = "You have been logged out successfully.";
        $done = true;
    }

    if ($_POST['action'] === 'cancel') {
        header("Location: ../profile.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Logout | GuideMe</title>

<style>
* {
    box-sizing: border-box;
}

:root {
    --primary: #65435c;
    --primary-dark: #4e3046;
    --bg: #f4f8ff;
    --text: #1e293b;
    --muted: #64748b;
    --border: #e5edff;
    --card: #ffffff;
    --danger: #b42318;
    --danger-bg: #fff1f1;
}

body {
    margin: 0;
    min-height: 100vh;
    font-family: Arial, sans-serif;
    background:
        radial-gradient(circle at top left, rgba(101,67,92,0.14), transparent 35%),
        #f4f8ff;
    color: var(--text);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
}

.card {
    width: 100%;
    max-width: 430px;
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 28px;
    padding: 38px 32px 32px;
    text-align: center;
    box-shadow: 0 14px 35px rgba(15, 23, 42, 0.08);
}

.icon {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    margin: 0 auto 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(101, 67, 92, 0.10);
    color: var(--primary);
    font-size: 34px;
}

.icon.danger {
    background: var(--danger-bg);
    color: var(--danger);
}

h2 {
    margin: 0 0 10px;
    font-size: 30px;
    font-weight: 800;
    color: #331a4d;
}

p {
    margin: 0 0 24px;
    color: var(--muted);
    font-size: 15px;
    line-height: 1.7;
}

.actions {
    display: flex;
    gap: 12px;
    margin-top: 8px;
}

form {
    flex: 1;
}

button,
.login-link {
    width: 100%;
    display: inline-block;
    padding: 14px 16px;
    border: none;
    border-radius: 14px;
    cursor: pointer;
    font-weight: 800;
    font-size: 14px;
    text-decoration: none;
    transition: 0.2s ease;
}

.logoutBtn {
    background: var(--primary);
    color: #ffffff;
    box-shadow: 0 10px 22px rgba(101, 67, 92, 0.22);
}

.logoutBtn:hover {
    background: var(--primary-dark);
    transform: translateY(-1px);
}

.cancelBtn {
    background: #f8fafc;
    color: var(--primary);
    border: 1px solid #dbe4f5;
}

.cancelBtn:hover {
    background: #f1f5f9;
    transform: translateY(-1px);
}

.login-link {
    background: var(--primary);
    color: white;
    box-shadow: 0 10px 22px rgba(101, 67, 92, 0.22);
}

.login-link:hover {
    background: var(--primary-dark);
    transform: translateY(-1px);
}

@media (max-width: 480px) {
    .card {
        padding: 32px 22px 28px;
        border-radius: 24px;
    }

    .actions {
        flex-direction: column;
    }

    h2 {
        font-size: 26px;
    }
}
</style>
</head>

<body>

<div class="card">

<?php if (!$done): ?>

    <div class="icon danger">!</div>
    <h2>Confirm Logout</h2>
    <p>Are you sure you want to logout from your GuideMe account?</p>

    <div class="actions">
        <form method="POST">
            <input type="hidden" name="action" value="logout">
            <button type="submit" class="logoutBtn">Yes, Logout</button>
        </form>

        <form method="POST">
            <input type="hidden" name="action" value="cancel">
            <button type="submit" class="cancelBtn">Cancel</button>
        </form>
    </div>

<?php else: ?>

    <div class="icon">✓</div>
    <h2>Logged Out</h2>
    <p><?php echo htmlspecialchars($message); ?></p>

    <a href="login.php" class="login-link">Return to Login</a>

<?php endif; ?>

</div>

</body>
</html>