<?php
session_start();
require "config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$stmt    = $conn->prepare("SELECT * FROM tbl_user WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$message  = "";
$msg_type = "success";
if (isset($_GET["success"])) { $message = "Profile saved."; $msg_type = "success"; }
if (isset($_GET["error"]))   { $message = "Could not save profile."; $msg_type = "danger"; }

$initials = strtoupper(substr($user["name"], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profile — MyWeb</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="page" style="max-width:720px;">
  <div class="page-header">
    <h1>Profile</h1>
    <p>Manage your account information</p>
  </div>

  <?php if ($message): ?>
    <div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <!-- Info card -->
  <div class="card" style="margin-bottom:20px;">
    <div class="card-header">
      <?php if (!empty($user["img"])): ?>
        <img src="uploads/<?= htmlspecialchars($user["img"]) ?>" class="profile-avatar" alt="avatar">
      <?php else: ?>
        <div class="profile-avatar-placeholder"><?= $initials ?></div>
      <?php endif; ?>
      <div>
        <div class="card-title"><?= htmlspecialchars($user["name"]) ?></div>
        <div class="text-sec text-sm"><?= htmlspecialchars($user["email"]) ?></div>
      </div>
      <button onclick="document.getElementById('editForm').style.display='block'; this.style.display='none';"
              class="btn btn-ghost btn-sm ml-auto">✏️ Edit</button>
    </div>
    <div class="card-body">
      <div class="profile-info-row">
        <span class="profile-info-label">User ID</span>
        <span class="profile-info-value">#<?= htmlspecialchars($user["id"]) ?></span>
      </div>
      <div class="profile-info-row">
        <span class="profile-info-label">Name</span>
        <span class="profile-info-value"><?= htmlspecialchars($user["name"]) ?></span>
      </div>
      <div class="profile-info-row">
        <span class="profile-info-label">Email</span>
        <span class="profile-info-value"><?= htmlspecialchars($user["email"]) ?></span>
      </div>
      <div class="profile-info-row">
        <span class="profile-info-label">Phone</span>
        <span class="profile-info-value"><?= htmlspecialchars($user["phone"] ?: "—") ?></span>
      </div>
      <div class="profile-info-row">
        <span class="profile-info-label">Address</span>
        <span class="profile-info-value"><?= htmlspecialchars($user["address"] ?? "—") ?></span>
      </div>
    </div>
  </div>

  <!-- Edit form -->
  <div id="editForm" class="card" style="display:none;">
    <div class="card-header">
      <span class="card-title">Edit profile</span>
    </div>
    <div class="card-body">
      <form action="profile_update.php" method="POST" enctype="multipart/form-data">
        <div class="form-group">
          <label class="form-label">Full name</label>
          <input type="text" name="name" class="form-control"
                 value="<?= htmlspecialchars($user["name"]) ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Phone</label>
          <input type="text" name="phone" class="form-control"
                 value="<?= htmlspecialchars($user["phone"] ?? "") ?>" placeholder="e.g. 0812345678">
        </div>
        <div class="form-group">
          <label class="form-label">Address</label>
          <textarea name="address" class="form-control" rows="3"
                    placeholder="Your address..."><?= htmlspecialchars($user["address"] ?? "") ?></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Profile photo</label>
          <input type="file" name="image" accept="image/*" class="form-control">
        </div>
        <div class="flex gap-8">
          <button type="submit" class="btn btn-primary">Save changes</button>
          <button type="button"
                  onclick="document.getElementById('editForm').style.display='none'; document.querySelector('.card-header button').style.display='';"
                  class="btn btn-ghost">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>
</body>
</html>
