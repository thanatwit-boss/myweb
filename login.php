<?php
session_start(); 
require "config.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    $sql = "SELECT * FROM tbl_user WHERE email = ?";
    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if ($user && password_verify($password, $user["password"])) {
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["name"] = $user["name"];

        if (isset($_POST["remember"])) {
            setcookie("user_email", $email, time() + (86400 * 7), "/");
        }

        header("Location: profile.php");
        exit;
    } else {
        $message = "อีเมลหรือรหัสผ่านไม่ถูกต้อง";
    }

    mysqli_stmt_close($stmt);
}
?>

<link rel="stylesheet" href="style.css">
<div class="form-container">
    <h2>เข้าสู่ระบบ</h2>

    <?php if ($message): ?>
        <p style="color:red;"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form method="POST">
        <input type="email" name="email" placeholder="อีเมล"
               value="<?= htmlspecialchars($_COOKIE['user_email'] ?? '') ?>"
               class="input-field"><br><br>

        <input type="password" name="password" placeholder="รหัสผ่าน" class="input-field"><br><br>

        <label>
            <input type="checkbox" name="remember"> จดจำฉัน
        </label><br><br>

        <button type="submit">Login</button>
    </form>
</div>