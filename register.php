<?php
session_start();
require "config.php";
$message  = "";
$msg_type = "danger";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name     = trim($_POST["name"]);
    $email    = trim($_POST["email"]);
    $password = $_POST["password"];

    if ($name == "" || $email == "" || $password == "") {
        $message = "Please fill in all fields.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn, "INSERT INTO tbl_user (name, email, password) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sss", $name, $email, $hash);
        if (mysqli_stmt_execute($stmt)) {
            $message  = "Account created! You can now sign in.";
            $msg_type = "success";
        } else {
            $message = "That email is already in use.";
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register — MyWeb</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="page-sm">
  <div class="auth-logo">
    <svg viewBox="0 0 24 24" fill="none" stroke="#2563EB" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
    <h2>Create account</h2>
    <p>Join MyWeb today</p>
  </div>

  <?php if ($message): ?>
    <div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <div class="card">
    <div class="card-body">
      <form action="register.php" method="POST">
        <div class="form-group">
          <label class="form-label">Full name</label>
          <input type="text" name="name" class="form-control" placeholder="Your full name" required>
        </div>
        <div class="form-group">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" placeholder="you@example.com" required>
        </div>
        <div class="form-group">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" placeholder="Min. 8 characters" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Create account</button>
      </form>
    </div>
  </div>

  <div class="auth-links mt-16">
    Already have an account? <a href="login.php">Sign in</a>
  </div>
</div>
</body>
</html>