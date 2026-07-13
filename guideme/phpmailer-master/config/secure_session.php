<?php
// secure_session.php

if (session_status() === PHP_SESSION_NONE) {

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => false, // خليها true إذا موقعك HTTPS
        'httponly' => true,
        'samesite' => 'Strict'
    ]);

    session_start();
}

// مدة انتهاء الجلسة: 30 دقيقة
$timeout = 1800;

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
    session_unset();
    session_destroy();

    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(["error" => "Session expired. Please login again."]);
    exit;
}

$_SESSION['last_activity'] = time();

// التحقق من User Agent
if (!isset($_SESSION['user_agent'])) {
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
} else {
    if ($_SESSION['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
        session_unset();
        session_destroy();

        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(["error" => "Session security check failed. Please login again."]);
        exit;
    }
}

// التحقق من IP
if (!isset($_SESSION['ip_address'])) {
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
} else {
    if ($_SESSION['ip_address'] !== ($_SERVER['REMOTE_ADDR'] ?? '')) {
        session_unset();
        session_destroy();

        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(["error" => "Session security check failed. Please login again."]);
        exit;
    }
}