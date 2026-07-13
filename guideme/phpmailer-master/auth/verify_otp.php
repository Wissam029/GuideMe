<?php
require "../config/secure_session.php";
include "../config/db.php";

date_default_timezone_set('Asia/Riyadh');

if (!isset($_SESSION['email']) || !isset($_SESSION['otp_type'])) {
    die("Session expired. Please try again.");
}

$email = $_SESSION['email'];
$type  = $_SESSION['otp_type'];

$remaining = 0;
$error_message = "";
$success_message = "";

/* جلب OTP ووقت الانتهاء */
$stmt = $conn->prepare("
    SELECT OTP, otp_expires
    FROM userlogininformation
    WHERE email = ?
");

$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->bind_result($db_otp, $expires);
$stmt->fetch();
$stmt->close();

if (!$db_otp || !$expires) {
    $error_message = "OTP not found. Please try again.";
}

$expire_time = strtotime($expires);
$current_time = time();
$remaining = $expire_time - $current_time;

if ($remaining < 0) {
    $remaining = 0;
}

/* عند إرسال الكود */
if ($_SERVER['REQUEST_METHOD'] == "POST") {

    $otp = trim($_POST['otp'] ?? '');

    if ($otp === '') {
        $error_message = "Please enter OTP.";
    } elseif ($otp == $db_otp && strtotime($expires) > time()) {

        /* =========================
           حالة إنشاء حساب جديد
        ========================== */
        if ($type == "register") {

            $update = $conn->prepare("
                UPDATE userlogininformation
                SET verified = 1, OTP = NULL, otp_expires = NULL
                WHERE email = ?
            ");

            $update->bind_param("s", $email);
            $update->execute();
            $update->close();

            unset($_SESSION['otp_type']);

            $success_message = "Account verified successfully ✅";
        }

        /* =========================
           حالة تسجيل الدخول
        ========================== */
        if ($type == "login") {

            $user_id = $_SESSION['pending_user_id'] ?? null;

            if (!$user_id) {
                session_unset();
                session_destroy();
                die("Session expired. Please login again.");
            }

            $user_id = (int)$user_id;

            $update = $conn->prepare("
                UPDATE userlogininformation
                SET OTP = NULL, otp_expires = NULL
                WHERE email = ?
            ");

            $update->bind_param("s", $email);
            $update->execute();
            $update->close();

            session_regenerate_id(true);

            $_SESSION['user_logged_in'] = true;
            $_SESSION['user_id'] = $user_id;
            $_SESSION['email'] = $email;
            $_SESSION['show_welcome'] = true;

            unset($_SESSION['pending_user_id']);
            unset($_SESSION['otp_type']);

            $_SESSION['last_activity'] = time();
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';

            header("Location: ../home.php");
            exit;
        }

    } else {
        $error_message = "OTP is incorrect or expired ❌";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Verify OTP</title>

    <style>
        * {
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            margin: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background: #eef2ff;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        form {
            background: #ffffff;
            padding: 40px 32px;
            border-radius: 24px;
            width: 100%;
            max-width: 380px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.08);
            display: flex;
            flex-direction: column;
        }

        h2 {
            text-align: center;
            margin: 0 0 20px;
            font-size: 32px;
            font-weight: 700;
            color: #111827;
        }

        input {
            padding: 12px 14px;
            border-radius: 12px;
            border: 1px solid #cbd5f5;
            margin-bottom: 16px;
            font-size: 14px;
            outline: none;
        }

        input:focus {
            border-color: #65435c;
            box-shadow: 0 0 0 3px rgba(109, 40, 217, 0.10);
        }

        button {
            padding: 14px;
            border-radius: 14px;
            border: none;
            background: linear-gradient(135deg, #65435c, #432b3c);
            color: white;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 10px;
        }

        button:hover {
            opacity: 0.9;
        }

        .error {
            color: red;
            text-align: center;
            margin-bottom: 12px;
        }

        .success {
            color: green;
            text-align: center;
            margin-bottom: 12px;
        }

        #timer {
            text-align: center;
            margin-top: 16px;
            font-size: 14px;
            font-weight: bold;
            color: #475569;
        }

        .login-link {
            text-align: center;
            margin-top: 14px;
            font-size: 14px;
        }

        .login-link a {
            color: #65435c;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>

<body>

<form method="POST">

    <h2>Enter OTP</h2>

    <?php if (!empty($error_message)) : ?>
        <div class="error"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <?php if (!empty($success_message)) : ?>
        <div class="success"><?= htmlspecialchars($success_message) ?></div>

        <?php if ($type == "register") : ?>
            <div class="login-link">
                <a href="login.php">Go to Login</a>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (empty($success_message)) : ?>
        <input 
            name="otp" 
            placeholder="Enter OTP" 
            required
            maxlength="6"
            pattern="[0-9]{6}"
        >

        <button type="submit">Verify</button>

        <p id="timer"></p>
    <?php endif; ?>

</form>

<script>
let timeLeft = <?php echo (int)$remaining; ?>;

function startTimer() {
    let countdown = setInterval(function() {
        let minutes = Math.floor(timeLeft / 60);
        let seconds = timeLeft % 60;

        seconds = seconds < 10 ? "0" + seconds : seconds;

        document.getElementById("timer").innerHTML =
            "Time remaining: " + minutes + ":" + seconds;

        if (timeLeft <= 0) {
            clearInterval(countdown);
            document.getElementById("timer").innerHTML =
                "The OTP code has expired.";
        }

        timeLeft--;

    }, 1000);
}

if (document.getElementById("timer")) {
    startTimer();
}
</script>

</body>
</html>