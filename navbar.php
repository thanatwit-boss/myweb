<?php
$current = basename($_SERVER['PHP_SELF']);
function nav_active($page) {
    global $current;
    return $current === $page ? 'active' : '';
}
?>
<nav class="navbar">
  <a href="index.php" class="nav-brand">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
    MyWeb
  </a>

  <?php if (isset($_SESSION["user_id"])): ?>
    <a href="relay.php"   class="<?= nav_active('relay.php') ?>">🔌 Relay</a>
    <a href="upload.php"  class="<?= nav_active('upload.php') ?>">Images</a>
    <a href="files.php"   class="<?= nav_active('files.php') ?>">Files</a>
    <a href="profile.php" class="<?= nav_active('profile.php') ?>">Profile</a>
    <a href="logout.php"  class="nav-logout">Sign out</a>
  <?php else: ?>
    <a href="login.php"    class="<?= nav_active('login.php') ?>">Sign in</a>
    <a href="register.php" class="<?= nav_active('register.php') ?>">Register</a>
  <?php endif; ?>
</nav>
