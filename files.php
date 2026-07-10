<?php
session_start();
include "config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$message = "";
$user_id = $_SESSION["user_id"];

if (isset($_POST["upload"])) {

    $file_name = $_FILES["files"]["name"];
    $file_tmp  = $_FILES["files"]["tmp_name"];
    $file_size = $_FILES["files"]["size"];
    $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    $allowed = ["pdf"];

    if (!in_array($file_ext, $allowed)) {
        $message = "อนุญาตเฉพาะ PDF";
    } elseif ($file_size > 2 * 1024 * 1024) {
        $message = "ไฟล์ต้องไม่เกิน 2MB";
    } else {
        $new_name = uniqid("PDF_", true) . "." . $file_ext;
        $upload_path = "files/" . $new_name;

        if (move_uploaded_file($file_tmp, $upload_path)) {

            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO tbl_files (user_id, files_name) VALUES (?, ?)"
            );

            mysqli_stmt_bind_param($stmt, "is", $user_id, $new_name);

            if (mysqli_stmt_execute($stmt)) {
                $message = "Upload สำเร็จ";
            } else {
                $message = "บันทึกฐานข้อมูลไม่สำเร็จ";
            }

        } else {
            $message = "Upload ไม่สำเร็จ";
        }
    }
}

echo "<link rel='stylesheet' href='style.css'>";
include 'navbar.php';

?>

<h2>Upload PDF</h2>

<p>
    ผู้ใช้งาน: <?php echo $_SESSION["name"]; ?> |
    อีเมล: <?php echo $_SESSION["email"]; ?>
</p>

<form method="post" enctype="multipart/form-data">
    <input type="file" name="files" required>
    <button type="submit" name="upload" class="btn">Upload PDF</button>
</form>

<p><?php echo $message; ?></p>

<hr>

<h2>PDF ของฉัน</h2>

<?php
$stmt = mysqli_prepare(
    $conn,
    "SELECT * FROM tbl_files WHERE user_id = ? ORDER BY id DESC"
);

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($result)) {
?>
    <a href="files/<?php echo htmlspecialchars($row["files_name"]); ?>">
     ชื่อไฟล์: <?php echo htmlspecialchars($row['files_name']);  ?>
    </a> | 
   <?php
    echo "<a href='delete_file.php?id=" . $row["id"] . "' onclick='return confirm(\"คุณแน่ใจหรือไม่ว่าต้องการลบไฟล์นี้?\")'>ลบ</a>";
    ?>
    <br>
<?php
}
?>
