<?php
session_start();
require "config.php";
$message = "";
$msg_type = "danger";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = trim($_POST["email"]);
    $password = $_POST["password"];

    $stmt = mysqli_prepare($conn, "SELECT * FROM tbl_user WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user   = mysqli_fetch_assoc($result);

    if ($user && password_verify($password, $user["password"])) {
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["name"]    = $user["name"];
        $_SESSION["email"]   = $user["email"];

        if (isset($_POST["remember"])) {
            setcookie("user_email", $email, time() + (86400 * 7), "/");
        }
        header("Location: relay.php");
        exit;
    } else {
        $message = "Email or password is incorrect.";
    }
    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign in — MyWeb</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="page-sm">
  <div class="auth-logo">
    <svg viewBox="0 0 24 24" fill="none" stroke="#2563EB" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
    <h2>Welcome back</h2>
    <p>Sign in to your account</p>
  </div>

  <?php if ($message): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <div class="card">
    <div class="card-body">
      <form action="login.php" method="POST">
        <div class="form-group">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" placeholder="you@example.com"
                 value="<?= htmlspecialchars($_COOKIE['user_email'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" placeholder="••••••••" required>
        </div>
        <div class="form-group">
          <label class="check-label">
            <input type="checkbox" name="remember"> Remember my email
          </label>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Sign in</button>
      </form>
    </div>
  </div>

  <div class="auth-links mt-16">
    Don't have an account? <a href="register.php">Register</a>
  </div>
</div>
</body>
</html>
