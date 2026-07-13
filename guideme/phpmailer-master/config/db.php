<?php
$servername = "localhost:4306"; // رجعها localhost حالياً
$username = "root";
$password = "";
$dbname = "dd";

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    echo "ERROR CONNECTING: ";
    echo mysqli_connect_errno() . " - ";
    echo mysqli_connect_error();
    exit();
}

?>
