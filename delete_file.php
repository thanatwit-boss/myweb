<?php
session_start();
include "config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];

if (!isset($_GET["id"])) {
    header("Location: files.php");
    exit;
}

/*
 * ตรวจสอบว่าเป็นรูปของผู้ใช้คนนี้จริง
 */
$stmt = mysqli_prepare( $conn,"SELECT * FROM tbl_files WHERE id = ? AND user_id = ?");

mysqli_stmt_bind_param( $stmt, "ii", $_GET["id"], $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);

if (!$row) {
    die("ไม่พบรูปภาพ หรือไม่มีสิทธิ์ลบ");
}

/*
 * ลบไฟล์จริง
 */
$file = "files/" . $row["files_name"];

if (file_exists($file)) {
    unlink($file);
}

/*
 * ลบข้อมูลในฐานข้อมูล
 */
$stmt = mysqli_prepare( $conn, "DELETE FROM tbl_files WHERE id = ? AND user_id = ?");
mysqli_stmt_bind_param($stmt,"ii", $_GET["id"],$user_id);
mysqli_stmt_execute($stmt);

header("Location: files.php?msg=deleted");
exit;
?>