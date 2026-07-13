<?php
session_start();
include "../config/db.php";
require_once '../mail.php';
date_default_timezone_set('Asia/Riyadh');

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = "Please enter your email.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email.";
    } else {

        $stmt = $conn->prepare("
            SELECT user_id, username, verified
            FROM userlogininformation
            WHERE email=?
            LIMIT 1
        ");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->bind_result($user_id, $username, $verified);
        $found = $stmt->fetch();
        $stmt->close();

        if (!$found) {
            $error = "No account found.";
        } elseif ((int)$verified !== 1) {
            $error = "Account not verified.";
        } else {

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
                $mail->Subject = 'Reset Password';
                $mail->Body = "Hello $username,\n\nYour OTP: $otp\nExpires in 5 minutes.";

                if ($mail->send()) {
                    $_SESSION['reset_email'] = $email;
                    header("Location: reset_password.php");
                    exit();
                } else {
                    $error = "Email failed.";
                }
            } else {
                $error = "Something went wrong.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Forgot Password</title>

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
    margin-bottom:10px;
}

.subtitle{
    text-align:center;
    font-size:14px;
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

button{
    width:100%;
    padding:12px;
    background:#65435c;
    color:white;
    border:none;
    border-radius:10px;
    cursor:pointer;
}

.error{
    color:red;
    text-align:center;
    margin-bottom:10px;
}

.bottom{
    text-align:center;
    margin-top:15px;
}

.bottom a{
    color:#65435c;
    text-decoration:none;
    font-weight:bold;
}
</style>
</head>

<body>

<div class="card">
<h2>Forgot Password</h2>
<div class="subtitle">Enter your email to get OTP</div>

<?php if($error!=""): ?>
<div class="error"><?php echo $error; ?></div>
<?php endif; ?>

<form method="POST">
<input type="email" name="email" placeholder="Enter email" required>

<button type="submit">Send Code</button>
</form>

<div class="bottom">
<a href="login.php">Back to login</a>
</div>

</div>

</body>
</html>