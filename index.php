<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MyWeb — Smart Relay Control</title>
<link rel="stylesheet" href="style.css">
<style>
.hero { text-align:center; padding: 80px 20px 60px; }
.hero h1 { font-size: 40px; font-weight:800; line-height:1.2; margin-bottom:16px; }
.hero h1 span { color: var(--accent); }
.hero p { font-size: 18px; color: var(--text-sec); max-width: 520px; margin: 0 auto 32px; }
.feature-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin: 40px 0; }
.feature-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 24px 20px; text-align:center; }
.feature-card .icon { font-size: 32px; margin-bottom: 12px; }
.feature-card h3 { font-size: 15px; font-weight:600; margin-bottom: 6px; }
.feature-card p { font-size: 13px; color: var(--text-sec); }
</style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="page" style="max-width:860px;">
  <div class="hero">
    <h1>Control your devices<br>with <span>MyWeb</span></h1>
    <p>A clean, modern web interface for managing relay channels, files, and images — all in one place.</p>
    <?php if (isset($_SESSION["user_id"])): ?>
      <a href="relay.php" class="btn btn-primary" style="font-size:16px; padding:14px 32px;">🔌 Open Relay Control</a>
    <?php else: ?>
      <a href="login.php"    class="btn btn-primary" style="font-size:16px; padding:14px 32px; margin-right:10px;">Sign in</a>
      <a href="register.php" class="btn btn-ghost"   style="font-size:16px; padding:14px 32px;">Register</a>
    <?php endif; ?>
  </div>

  <div class="feature-grid">
    <div class="feature-card">
      <div class="icon">🔌</div>
      <h3>Relay control</h3>
      <p>Toggle 4 relay channels independently with live status indicators.</p>
    </div>
    <div class="feature-card">
      <div class="icon">🖼️</div>
      <h3>Image library</h3>
      <p>Upload, view and manage your images from anywhere.</p>
    </div>
    <div class="feature-card">
      <div class="icon">📄</div>
      <h3>File storage</h3>
      <p>Keep your PDF documents safe and accessible at all times.</p>
    </div>
    <div class="feature-card">
      <div class="icon">👤</div>
      <h3>User accounts</h3>
      <p>Secure login with per-user isolation — your data stays yours.</p>
    </div>
  </div>
</div>
</body>
</html>
