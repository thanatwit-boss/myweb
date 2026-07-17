<?php
session_start();
include "config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id  = $_SESSION["user_id"];
$message  = "";
$msg_type = "success";

if (isset($_POST["upload"])) {
    $file_name = $_FILES["image"]["name"];
    $file_tmp  = $_FILES["image"]["tmp_name"];
    $file_size = $_FILES["image"]["size"];
    $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $allowed   = ["jpg","jpeg","png","gif","webp"];

    if (!in_array($file_ext, $allowed)) {
        $message = "Only JPG, PNG, GIF and WEBP are allowed.";
        $msg_type = "danger";
    } elseif ($file_size > 2 * 1024 * 1024) {
        $message = "File must be under 2 MB.";
        $msg_type = "danger";
    } else {
        $new_name    = uniqid("IMG_", true) . "." . $file_ext;
        $upload_path = "uploads/" . $new_name;
        if (move_uploaded_file($file_tmp, $upload_path)) {
            $stmt = $conn->prepare("INSERT INTO tbl_upload (user_id, image_name) VALUES (?,?)");
            $stmt->bind_param("is", $user_id, $new_name);
            $message  = $stmt->execute() ? "Image uploaded." : "Database error.";
            $msg_type = $stmt->execute() ? "success" : "danger";
        } else {
            $message = "Upload failed.";
            $msg_type = "danger";
        }
    }
}

if (isset($_GET["msg"]) && $_GET["msg"] === "deleted") {
    $message = "Image deleted."; $msg_type = "info";
}

/* fetch images */
$stmt = $conn->prepare("SELECT * FROM tbl_upload WHERE user_id=? ORDER BY id DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$images = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Images — MyWeb</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="page">
  <div class="page-header">
    <h1>Image library</h1>
    <p>Upload and manage your images</p>
  </div>

  <?php if ($message): ?>
    <div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <!-- Upload area -->
  <div class="card" style="margin-bottom:28px;">
    <div class="card-header">
      <span style="font-size:20px;">📤</span>
      <span class="card-title">Upload image</span>
    </div>
    <div class="card-body">
      <form method="POST" enctype="multipart/form-data">
        <div class="upload-zone" onclick="this.querySelector('input').click()">
          <div style="font-size:36px;">🖼️</div>
          <p>Click to select an image</p>
          <p style="font-size:12px; margin-top:4px;">JPG, PNG, GIF, WEBP — max 2 MB</p>
          <input type="file" name="image" accept="image/*" required style="display:none"
                 onchange="this.closest('form').querySelector('button').textContent='Upload: '+this.files[0].name">
        </div>
        <button type="submit" name="upload" class="btn btn-primary mt-16">Upload image</button>
      </form>
    </div>
  </div>

  <!-- Gallery -->
  <div class="section-title"><span>🖼️</span> My images (<?= count($images) ?>)</div>

  <?php if (empty($images)): ?>
    <div style="text-align:center; padding:48px 0; color:var(--text-mut);">
      <div style="font-size:48px;">📭</div>
      <p style="margin-top:12px;">No images yet. Upload your first one above.</p>
    </div>
  <?php else: ?>
    <div class="img-grid">
      <?php foreach ($images as $row): ?>
      <div class="img-card">
        <img src="uploads/<?= htmlspecialchars($row["image_name"]) ?>"
             alt="<?= htmlspecialchars($row["image_name"]) ?>"
             onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%22150%22><rect fill=%22%23f3f4f6%22 width=%22200%22 height=%22150%22/><text x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 fill=%22%239ca3af%22 dy=%22.3em%22>No preview</text></svg>'">
        <div class="img-card-footer">
          <span class="img-card-name"><?= htmlspecialchars($row["image_name"]) ?></span>
          <a href="delete_image.php?id=<?= $row["id"] ?>"
             class="btn btn-danger btn-sm"
             onclick="return confirm('Delete this image?')">🗑️</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
</body>
</html>
