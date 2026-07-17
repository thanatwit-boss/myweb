<?php
/*
 * relay_update.php
 * AJAX endpoint — saves relay status to DB after ESP32 responds OK
 */
session_start();
include "config.php";

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    exit("Unauthorized");
}

$user_id  = $_SESSION["user_id"];
$relay_id = (int)$_POST["relay_id"];
$status   = (int)$_POST["status"];

$stmt = $conn->prepare("UPDATE tbl_relay SET status=? WHERE id=? AND user_id=?");
$stmt->bind_param("iii", $status, $relay_id, $user_id);
$stmt->execute();

echo "ok";