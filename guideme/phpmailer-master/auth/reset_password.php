<?php
require_once __DIR__ . '/../config/secure_session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../mail.php';
date_default_timezone_set('Asia/Riyadh');

$error = "";
$success = "";
$remaining = 0;

if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit();
}

$email = $_SESSION['reset_email'];

$stmt = $conn->prepare("
    SELECT username, otp_expires
    FROM userlogininformation
    WHERE email=?
    LIMIT 1
");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->bind_result($username, $expires);
$stmt->fetch();
$stmt->close();

$expire_time = $expires ? strtotime($expires) : 0;
$remaining = max(0, $expire_time - time());

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? 'reset_password';

    if ($action === 'resend_otp') {

        $otp = mt_rand(100000, 999999);
        $otp_expires = date("Y-m-d H:i:s", strtotime("+5 minutes"));

        $stmt = $conn->prepare("
            UPDATE userlogininformation
            SET OTP=?, otp_expires=?
            WHERE email=?
        ");
        $stmt->bind_param("sss", $otp, $otp_expires, $email);

        if ($stmt->execute()) {
            $stmt->close();

            $mail->clearAddresses();
            $mail->setFrom('guideme.otp@gmail.com', 'GuideMe');
            $mail->addAddress($email);
            $mail->Subject = 'Reset OTP';
            $mail->Body = "Hello $username,\n\nOTP: $otp\nExpires in 5 minutes.";

            if ($mail->send()) {
                $success = "New OTP sent.";
                $expire_time = strtotime($otp_expires);
                $remaining = max(0, $expire_time - time());
            } else {
                $error = "Mailer Error";
            }
        }
    }

    if ($action === 'reset_password') {

        $otp = trim($_POST['otp'] ?? '');
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($otp) || empty($new_password) || empty($confirm_password)) {
            $error = "Fill all fields";
        }
        elseif ($new_password !== $confirm_password) {
            $error = "Passwords do not match";
        }
        elseif (
            strlen($new_password) < 8 ||
            !preg_match('/[A-Z]/', $new_password) ||
            !preg_match('/[a-z]/', $new_password) ||
            !preg_match('/[0-9]/', $new_password) ||
            !preg_match('/[\W_]/', $new_password)
        ) {
            $error = "Password must be at least 8 characters and include uppercase, lowercase, number, and special character.";
        }
        else {

            $stmt = $conn->prepare("
                SELECT OTP, otp_expires
                FROM userlogininformation
                WHERE email=?
                LIMIT 1
            ");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->bind_result($db_otp, $expires);
            $stmt->fetch();
            $stmt->close();

            if ($db_otp != $otp || strtotime($expires) < time()) {
                $error = "OTP incorrect or expired";
            } else {

                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                $stmt = $conn->prepare("
                    UPDATE userlogininformation
                    SET password=?, OTP=NULL, otp_expires=NULL
                    WHERE email=?
                ");
                $stmt->bind_param("ss", $hashed_password, $email);

                if ($stmt->execute()) {
                    unset($_SESSION['reset_email']);
                    header("Location: login.php?reset_success=1");
                    exit();
                } else {
                    $error = "Update failed";
                }
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
<title>Reset Password</title>

<style>
body{
    margin:0;
    font-family: Arial;
    background:#f4f8ff;
    display:flex;
    justify-content:center;
    align-items:center;
    height:100vh;
}

.card{
    background:#fff;
    padding:30px;
    border-radius:20px;
    width:360px;
    box-shadow:0 12px 30px rgba(0,0,0,0.1);
}

h2{
    text-align:center;
}

.subtitle{
    text-align:center;
    color:#666;
    margin-bottom:20px;
}

input{
    width:100%;
    padding:12px;
    margin:10px 0;
    border-radius:10px;
    border:1px solid #ddd;
}

.password-box{
    position:relative;
}

.eye{
    position:absolute;
    right:10px;
    top:50%;
    transform:translateY(-50%);
    cursor:pointer;
}

button{
    width:100%;
    padding:12px;
    margin-top:10px;
    background:#65435c;
    color:white;
    border:none;
    border-radius:10px;
}

.error{color:red;text-align:center;}
.success{color:green;text-align:center;}

#timer{
    text-align:center;
    margin-top:10px;
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

<div class="card">
<h2>Reset Password</h2>
<div class="subtitle">Enter OTP and new password</div>

<?php if($error!=""): ?>
<div class="error"><?php echo $error; ?></div>
<?php endif; ?>

<?php if($success!=""): ?>
<div class="success"><?php echo $success; ?></div>
<?php endif; ?>

<form method="POST">

<input type="text" name="otp" placeholder="OTP" required>

<div class="password-box">
<input type="password" id="new_password" name="new_password" placeholder="New Password" required>
<span class="eye" onclick="togglePassword('new_password')">👁</span>
</div>

<div class="password-box">
<input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
<span class="eye" onclick="togglePassword('confirm_password')">👁</span>
</div>

<button type="submit" name="action" value="reset_password">Reset</button>
<button type="submit" name="action" value="resend_otp">Resend OTP</button>
<div class="login-link">
                <a href="login.php">Go to Login</a>
            </div>

<div id="timer"></div>

</form>
</div>

<script>
function togglePassword(id){
    let input = document.getElementById(id);
    input.type = input.type === "password" ? "text" : "password";
}

let timeLeft = <?php echo (int)$remaining; ?>;

if(timeLeft > 0){
    let timer = document.getElementById("timer");

    let count = setInterval(()=>{
        let m = Math.floor(timeLeft/60);
        let s = timeLeft % 60;
        if(s<10) s="0"+s;

        timer.innerHTML = "Time: "+m+":"+s;

        if(timeLeft <= 0){
            clearInterval(count);
            timer.innerHTML = "OTP expired";
        }

        timeLeft--;
    },1000);
}
</script>

</body>
</html>