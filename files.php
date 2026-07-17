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
    $file_name = $_FILES["files"]["name"];
    $file_tmp  = $_FILES["files"]["tmp_name"];
    $file_size = $_FILES["files"]["size"];
    $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    if ($file_ext !== "pdf") {
        $message = "Only PDF files are allowed."; $msg_type = "danger";
    } elseif ($file_size > 2 * 1024 * 1024) {
        $message = "File must be under 2 MB."; $msg_type = "danger";
    } else {
        $new_name    = uniqid("PDF_", true) . ".pdf";
        $upload_path = "files/" . $new_name;
        if (move_uploaded_file($file_tmp, $upload_path)) {
            $stmt = $conn->prepare("INSERT INTO tbl_files (user_id, files_name) VALUES (?,?)");
            $stmt->bind_param("is", $user_id, $new_name);
            $message  = $stmt->execute() ? "PDF uploaded." : "Database error.";
            $msg_type = $stmt->execute() ? "success" : "danger";
        } else {
            $message = "Upload failed."; $msg_type = "danger";
        }
    }
}

if (isset($_GET["msg"]) && $_GET["msg"] === "deleted") {
    $message = "File deleted."; $msg_type = "info";
}

$stmt = $conn->prepare("SELECT * FROM tbl_files WHERE user_id=? ORDER BY id DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$files = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Files — MyWeb</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="page">
  <div class="page-header">
    <h1>PDF files</h1>
    <p>Upload and manage your PDF documents</p>
  </div>

  <?php if ($message): ?>
    <div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <!-- Upload -->
  <div class="card" style="margin-bottom:28px;">
    <div class="card-header">
      <span style="font-size:20px;">📤</span>
      <span class="card-title">Upload PDF</span>
    </div>
    <div class="card-body">
      <form method="POST" enctype="multipart/form-data">
        <div class="upload-zone" onclick="this.querySelector('input').click()">
          <div style="font-size:36px;">📄</div>
          <p>Click to select a PDF file</p>
          <p style="font-size:12px; margin-top:4px;">PDF only — max 2 MB</p>
          <input type="file" name="files" accept=".pdf" required style="display:none"
                 onchange="this.closest('form').querySelector('button').textContent='Upload: '+this.files[0].name">
        </div>
        <button type="submit" name="upload" class="btn btn-primary mt-16">Upload PDF</button>
      </form>
    </div>
  </div>

  <!-- List -->
  <div class="section-title"><span>📁</span> My files (<?= count($files) ?>)</div>

  <?php if (empty($files)): ?>
    <div style="text-align:center; padding:48px 0; color:var(--text-mut);">
      <div style="font-size:48px;">📭</div>
      <p style="margin-top:12px;">No files yet. Upload your first PDF above.</p>
    </div>
  <?php else: ?>
    <div class="file-list">
      <?php foreach ($files as $row): ?>
      <div class="file-item">
        <div class="file-icon">📄</div>
        <div class="file-info">
          <a href="files/<?= htmlspecialchars($row["files_name"]) ?>"
             class="file-name" target="_blank">
            <?= htmlspecialchars($row["files_name"]) ?>
          </a>
          <?php if (!empty($row["uploaded_at"])): ?>
          <div class="file-meta">Uploaded <?= date("d M Y, H:i", strtotime($row["uploaded_at"])) ?></div>
          <?php endif; ?>
        </div>
        <a href="delete_file.php?id=<?= $row["id"] ?>"
           class="btn btn-danger btn-sm"
           onclick="return confirm('Delete this file?')">🗑️ Delete</a>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
</body>
</html>
