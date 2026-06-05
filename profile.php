<?php
session_start(); 
require "config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];

$sql = "SELECT * FROM tbl_user WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);
?>

<h2>Profile</h2>
<p>รหัสผู้ใช้: <?= $user["id"] ?></p>
<p>ชื่อ: <?= htmlspecialchars($user["name"]) ?></p>
<p>อีเมล: <?= htmlspecialchars($user["email"]) ?></p>
<p>วันที่สมัคร: <?= $user["created_at"] ?></p>

<a href="logout.php">ออกจากระบบ</a>