<?php
require "../config/secure_session.php";
date_default_timezone_set('Asia/Riyadh'); 
include "../config/db.php";
require_once "../mail.php";

$MAX_ATTEMPTS = 5;
$LOCK_MINUTES = 15;
$error_message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $stmt = $conn->prepare("
        SELECT user_id, password, verified, log_failed_attempt, last_attempt
        FROM userlogininformation 
        WHERE email=?
    ");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($user_id, $hashed_password, $verified, $failed_attempts, $last_attempt);
    $stmt->fetch();
    $stmt->close();
    if (!$user_id) {
        $error_message = "Email not registered ❌";
    }
    elseif ($verified == 0) {
        $error_message = "Please verify your account first via OTP ❌";
    }
    else {
        if ($failed_attempts >= $MAX_ATTEMPTS) {
            $unlock_time = strtotime($last_attempt) + ($LOCK_MINUTES * 60);
            if (time() < $unlock_time) {
                $remaining = ceil(($unlock_time - time()) / 60);
                $error_message = "Your account is temporarily locked. Try again in $remaining minutes ⛔";
            } else {
                $reset_stmt = $conn->prepare("
                    UPDATE userlogininformation 
                    SET log_failed_attempt = 0, last_attempt = NULL
                    WHERE email = ?
                ");
                $reset_stmt->bind_param("s", $email);
                $reset_stmt->execute();
                $reset_stmt->close();
                $failed_attempts = 0;
            }
        }

        if (empty($error_message)) {
            if (password_verify($password, $hashed_password)) {

                $reset_stmt = $conn->prepare("
                    UPDATE userlogininformation 
                    SET log_failed_attempt = 0, last_attempt = NULL
                    WHERE email = ?
                ");
                $reset_stmt->bind_param("s", $email);
                $reset_stmt->execute();
                $reset_stmt->close();

                $login_otp = mt_rand(100000, 999999);
                $expires = date("Y-m-d H:i:s", strtotime("+5 minutes"));

                $update = $conn->prepare("
                    UPDATE userlogininformation 
                    SET OTP=?, otp_expires=? 
                    WHERE email=?
                ");
                $update->bind_param("sss", $login_otp, $expires, $email);
                $update->execute();
                $update->close();

                $mail->setFrom('guideme.otp@gmail.com', 'GuideMe');
                $mail->addAddress($email);
                $mail->Subject = "Login OTP Code";
                $mail->Body = "Your login OTP is: $login_otp (valid for 5 minutes)";
                $mail->send();

                session_regenerate_id(true);

$_SESSION['email'] = $email;
$_SESSION['otp_type'] = "login";
$_SESSION['pending_user_id'] = $user_id;

$_SESSION['last_activity'] = time();
$_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
$_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';

header("Location: verify_otp.php");
exit;

                

            } else {
                $update_attempt = $conn->prepare("
                    UPDATE userlogininformation
                    SET log_failed_attempt = log_failed_attempt + 1, last_attempt = NOW()
                    WHERE email = ?
                ");
                $update_attempt->bind_param("s", $email);
                $update_attempt->execute();
                $update_attempt->close();

                $stmt_check = $conn->prepare("
                    SELECT log_failed_attempt 
                    FROM userlogininformation 
                    WHERE email = ?
                ");
                $stmt_check->bind_param("s", $email);
                $stmt_check->execute();
                $stmt_check->bind_result($failed_attempts);
                $stmt_check->fetch();
                $stmt_check->close();

                if ($failed_attempts >= $MAX_ATTEMPTS) {
                    $mail->setFrom('guideme.otp@gmail.com', 'GuideMe');
                    $mail->addAddress($email);
                    $mail->Subject = "Account Locked Due to Failed Login Attempts";
                    $mail->Body = "Your account has been temporarily locked due to 5 failed login attempts.\n\n" .
                                  "Lock duration: 15 minutes.\n" .
                                  "If you forgot your password, you can recover your account using the password reset link.\n\n" .
                                  "To protect your account, avoid multiple incorrect login attempts.";
                    $mail->send();

                    $error_message = "Your account has been temporarily locked after 5 failed login attempts ⛔. A notification email has been sent.";
                } else {
                    $error_message = "Incorrect password ❌";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول</title>

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
            font-size: 14px;
        }
        .password-wrapper {
    position: relative;
    width: 100%;
}

.password-wrapper input {
    width: 100%;
    padding-right: 42px;
}

.eye {
    position: absolute;
    right: 14px;
    top: 12px;
    cursor: pointer;
    font-size: 16px;
    user-select: none;
}
    </style>
</head>
<script>
function togglePassword() {
    const password = document.getElementById("password");
    password.type = password.type === "password" ? "text" : "password";
}
</script>
<body>

<form method="POST">
    <h2>Login</h2>

    <?php if (!empty($error_message)) : ?>
        <div class="error"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <input type="email" name="email" placeholder="Emai" required>
    <div class="password-wrapper">
    <input type="password" id="password" name="password" placeholder="Password" required>
    <span class="eye" onclick="togglePassword()">👁</span>
</div>
    

    <p style="text-align:center; margin-top:10px;">
  <a href="forgot_password.php" style="color:#65435c; font-size:14px;">
    Forgot Password?
  </a>
</p>

    <button type="submit">Login</button>
    <p class="register-link">
  Don't have an account?
  <a href="register.php">Sign Up</a>
  
</p>
</form>

</body>
</html>