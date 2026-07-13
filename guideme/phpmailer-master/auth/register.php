<?php
require "../config/secure_session.php";
include "../config/db.php";
require_once '../mail.php';

date_default_timezone_set('Asia/Riyadh');

$error = "";
$success = "";
$showOtpForm = false;
$remaining = 0;

if (isset($_SESSION['user_id'])) {
    header("Location: ../home.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    unset($_SESSION['pending_email']);
    unset($_SESSION['pending_otp_type']);
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_SESSION['pending_email']) &&
    isset($_SESSION['pending_otp_type']) &&
    $_SESSION['pending_otp_type'] === 'register'
) {
    $showOtpForm = true;

    $email_for_otp = $_SESSION['pending_email'];

    $stmt = $conn->prepare("
        SELECT otp_expires
        FROM userlogininformation
        WHERE email = ?
        LIMIT 1
    ");
    $stmt->bind_param("s", $email_for_otp);
    $stmt->execute();
    $stmt->bind_result($expires);
    $stmt->fetch();
    $stmt->close();

    $expire_time = $expires ? strtotime($expires) : 0;
    $remaining = max(0, $expire_time - time());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    if ($action === 'register') {

        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $raw_password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $academic_status = $_POST['academic_status'] ?? '';
        $terms = isset($_POST['terms']) ? 1 : 0;

        if (empty($username) || empty($email) || empty($raw_password) || empty($confirm_password) || empty($academic_status)) {
            $error = "Please fill in all required fields.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address.";
        } elseif ($raw_password !== $confirm_password) {
            $error = "Password and Confirm Password do not match.";
        } elseif (!$terms) {
            $error = "You must agree to the Terms & Privacy Policy.";
        } elseif (
            strlen($raw_password) < 8 ||
            !preg_match('/[A-Z]/', $raw_password) ||
            !preg_match('/[a-z]/', $raw_password) ||
            !preg_match('/[0-9]/', $raw_password) ||
            !preg_match('/[\W_]/', $raw_password)
        ) {
            $error = "Password must be at least 8 characters and include uppercase, lowercase, number, and special character.";
        } else {

            $stmt = $conn->prepare("
                SELECT user_id, verified
                FROM userlogininformation
                WHERE email = ?
                LIMIT 1
            ");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->bind_result($existing_user_id, $existing_verified);
            $email_exists = $stmt->fetch();
            $stmt->close();

            if ($email_exists) {
                if ((int)$existing_verified === 1) {
                    $error = "Email already registered.";
                } else {
                    $deleteOld = $conn->prepare("
                        DELETE FROM userlogininformation
                        WHERE email = ? AND verified = 0
                    ");
                    $deleteOld->bind_param("s", $email);
                    $deleteOld->execute();
                    $deleteOld->close();
                }
            }

            if ($error === "") {
                $password = password_hash($raw_password, PASSWORD_DEFAULT);

                $otp = (string) mt_rand(100000, 999999);
                $otp_expires = date("Y-m-d H:i:s", strtotime("+5 minutes"));

                $stmt = $conn->prepare("
                    INSERT INTO userlogininformation 
                    (username, email, password, academic_status, OTP, otp_expires, verified)
                    VALUES (?, ?, ?, ?, ?, ?, 0)
                ");
                $stmt->bind_param("ssssss", $username, $email, $password, $academic_status, $otp, $otp_expires);

                if ($stmt->execute()) {
                    $stmt->close();

                    $mail->clearAddresses();
                    $mail->setFrom('guideme.otp@gmail.com', 'GuideMe');
                    $mail->addAddress($email);
                    $mail->Subject = 'Your OTP Code';
                    $mail->Body = "Hello $username,\n\nYour OTP code is: $otp\nIt expires in 5 minutes.";

                    if ($mail->send()) {
                        $_SESSION['pending_email'] = $email;
                        $_SESSION['pending_otp_type'] = 'register';

                        $_SESSION['last_activity'] = time();
                        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
                        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';

                        $showOtpForm = true;
                        $success = "A verification code has been sent successfully.";

                        $expire_time = strtotime($otp_expires);
                        $remaining = max(0, $expire_time - time());
                    } else {
                        $error = "Email could not be sent. Mailer Error: " . $mail->ErrorInfo;
                    }
                } else {
                    $error = "Something went wrong while creating your account.";
                    $stmt->close();
                }
            }
        }
    }

    if ($action === 'verify_otp') {

        $showOtpForm = true;

        if (!isset($_SESSION['pending_email']) || !isset($_SESSION['pending_otp_type'])) {
            $error = "Your OTP session has expired. Please register again.";
        } else {

            $email = $_SESSION['pending_email'];
            $otp = trim($_POST['otp'] ?? '');

            $stmt = $conn->prepare("
                SELECT user_id, username, OTP, otp_expires
                FROM userlogininformation
                WHERE email = ?
                LIMIT 1
            ");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->bind_result($uid, $db_username, $db_otp, $expires);
            $stmt->fetch();
            $stmt->close();

            $expire_time = $expires ? strtotime($expires) : 0;
            $remaining = max(0, $expire_time - time());

            $otp_ok = (
                $db_otp !== null &&
                $db_otp !== "" &&
                hash_equals((string)$db_otp, (string)$otp)
            );

            $not_expired = ($expire_time > time());

            if ($otp_ok && $not_expired) {

                $update = $conn->prepare("
                    UPDATE userlogininformation
                    SET verified = 1, OTP = NULL, otp_expires = NULL
                    WHERE email = ?
                ");
                $update->bind_param("s", $email);

                if ($update->execute()) {
                    $update->close();

                    session_regenerate_id(true);

                    $_SESSION['user_logged_in'] = true;
                    $_SESSION['user_id'] = (int)$uid;
                    $_SESSION['email'] = $email;
                    $_SESSION['full_name'] = $db_username;
                    $_SESSION['show_welcome'] = true;

                    unset($_SESSION['pending_email']);
                    unset($_SESSION['pending_otp_type']);

                    $_SESSION['last_activity'] = time();
                    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
                    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';

                    header("Location: ../home.php");
                    exit();
                } else {
                    $error = "Failed to verify your account.";
                    $update->close();
                }

            } else {
                $error = "OTP is incorrect or expired.";
            }
        }
    }

    if ($action === 'resend_otp') {

        $showOtpForm = true;

        if (!isset($_SESSION['pending_email']) || !isset($_SESSION['pending_otp_type'])) {
            $error = "Your OTP session has expired. Please register again.";
        } else {

            $email = $_SESSION['pending_email'];

            $stmt = $conn->prepare("
                SELECT username
                FROM userlogininformation
                WHERE email = ?
                LIMIT 1
            ");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->bind_result($username);
            $stmt->fetch();
            $stmt->close();

            $otp = (string) mt_rand(100000, 999999);
            $otp_expires = date("Y-m-d H:i:s", strtotime("+5 minutes"));

            $stmt = $conn->prepare("
                UPDATE userlogininformation
                SET OTP = ?, otp_expires = ?
                WHERE email = ?
            ");
            $stmt->bind_param("sss", $otp, $otp_expires, $email);

            if ($stmt->execute()) {
                $stmt->close();

                $mail->clearAddresses();
                $mail->setFrom('guideme.otp@gmail.com', 'GuideMe');
                $mail->addAddress($email);
                $mail->Subject = 'Your New OTP Code';
                $mail->Body = "Hello $username,\n\nYour new OTP code is: $otp\nIt expires in 5 minutes.";

                if ($mail->send()) {
                    $_SESSION['last_activity'] = time();

                    $success = "A new OTP has been sent to your email.";
                    $expire_time = strtotime($otp_expires);
                    $remaining = max(0, $expire_time - time());
                } else {
                    $error = "Could not resend OTP. Mailer Error: " . $mail->ErrorInfo;
                }

            } else {
                $error = "Failed to generate a new OTP.";
                $stmt->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Account</title>

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

.register-card {
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

.field {
    margin-bottom: 16px;
}

.field label {
    display: none;
}

input,
select {
    width: 100%;
    padding: 12px 14px;
    border-radius: 12px;
    border: 1px solid #cbd5f5;
    font-size: 14px;
    outline: none;
}

input:focus,
select:focus {
    border-color: #65435c;
    box-shadow: 0 0 0 3px rgba(109, 40, 217, 0.10);
}

.password-box {
    position: relative;
}

.password-box input {
    padding-right: 42px;
}

.eye {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: #666;
}

.checkbox-row {
    display: flex;
    gap: 8px;
    font-size: 13px;
    color: #475569;
    margin-bottom: 16px;
}

.checkbox-row input {
    width: auto;
}

.register-btn {
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

.register-btn:hover {
    opacity: 0.9;
}

.error {
    color: red;
    text-align: center;
    margin-bottom: 12px;
    font-size: 14px;
}

.success {
    color: green;
    text-align: center;
    margin-bottom: 12px;
    font-size: 14px;
}

.bottom-text {
    text-align: center;
    margin-top: 18px;
    font-size: 14px;
    color: #475569;
}

.bottom-text a {
    color: #65435c;
    font-weight: 700;
    text-decoration: none;
}

.bottom-text a:hover {
    text-decoration: underline;
}

#timer {
    text-align: center;
    margin-top: 12px;
    font-size: 14px;
    font-weight: bold;
    color: #475569;
}
</style>
</head>

<body>

<form class="register-card" action="" method="POST">

    <?php if (!$showOtpForm): ?>
        <h2>Sign Up</h2>
    <?php else: ?>
        <h2>Verify Email</h2>
    <?php endif; ?>

    <?php if ($error != ""): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success != ""): ?>
        <div class="success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if (!$showOtpForm): ?>

        <div class="field">
            <input 
                type="text" 
                name="username" 
                placeholder="Username" 
                required
                value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
            >
        </div>

        <div class="field">
            <input 
                type="email" 
                name="email" 
                placeholder="Email" 
                required
                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
            >
        </div>

        <div class="field">
            <div class="password-box">
                <input type="password" id="password" name="password" placeholder="Password" required>
                <span class="eye" onclick="togglePassword('password', this)">👁</span>
            </div>
        </div>

        <div class="field">
            <div class="password-box">
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
                <span class="eye" onclick="togglePassword('confirm_password', this)">👁</span>
            </div>
        </div>

        <div class="field">
            <select name="academic_status" required>
                <option value="">Academic Status</option>
                <option value="Student">Student</option>
                <option value="Graduate">Graduate</option>
            </select>
        </div>

        <div class="checkbox-row">
            <input type="checkbox" id="terms" name="terms">
            <label for="terms">I agree to the Terms and Privacy Policy.</label>
        </div>

        <button type="submit" name="action" value="register" class="register-btn">
            Create Account
        </button>

        <div class="bottom-text">
            Already have an account? <a href="login.php">Login</a>
        </div>

    <?php else: ?>

        <div class="field">
            <input 
                type="text" 
                name="otp" 
                placeholder="Enter OTP Code" 
                required 
                maxlength="6" 
                pattern="[0-9]{6}"
            >
        </div>

        <button type="submit" name="action" value="verify_otp" class="register-btn">
            Verify
        </button>

        <div id="timer"></div>

        <div class="bottom-text">
            <button 
                type="submit" 
                name="action" 
                value="resend_otp" 
                style="background:none;border:none;color:#65435c;font-weight:bold;cursor:pointer;"
            >
                Resend Code
            </button>

            <br><br>

            <a href="register.php?reset=1">Back to Register</a>
        </div>

    <?php endif; ?>

</form>

<script>
function togglePassword(id, icon) {
    const input = document.getElementById(id);

    if (input.type === "password") {
        input.type = "text";
        icon.textContent = "🙈";
    } else {
        input.type = "password";
        icon.textContent = "👁";
    }
}

let timeLeft = <?php echo (int)$remaining; ?>;

if (timeLeft > 0) {
    const timerElement = document.getElementById("timer");

    if (timerElement) {
        const countdown = setInterval(function () {
            let minutes = Math.floor(timeLeft / 60);
            let seconds = timeLeft % 60;

            seconds = seconds < 10 ? "0" + seconds : seconds;

            timerElement.innerHTML = "Time remaining: " + minutes + ":" + seconds;

            if (timeLeft <= 0) {
                clearInterval(countdown);
                timerElement.innerHTML = "The code has expired.";
            }

            timeLeft--;
        }, 1000);
    }
}
</script>

</body>
</html>