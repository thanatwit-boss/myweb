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
        $_SESSION["email"] = $user["email"];

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
<?php
include 'navbar.php';
?>

<div class="form-container">
<h1>เข้าสู่ระบบ</h1>

<form action="login.php" method="POST">
    <input type="email" name="email" placeholder="อีเมล"
           value="<?= $_COOKIE['user_email'] ?? '' ?>" class="input-field"><br> <br/>

    <input type="password" name="password" placeholder="รหัสผ่าน" class="input-field"><br>

    <label>
        <input type="checkbox" name="remember">
        จำอีเมลไว้
    </label><br>

    <button type="submit" class="btn">Login</button>
</form>

<p><?= $message ?></p>
<a href="register.php">สมัครสมาชิก</a>

</div>
