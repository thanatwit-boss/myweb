<?php
session_start();
include "config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$message    = "";
$msg_type   = "";
$user_id    = $_SESSION["user_id"];
$upload_dir = __DIR__ . "/uploads/";

if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true)) {
        die("ไม่สามารถสร้างโฟลเดอร์ uploads/ ได้");
    }
}
if (!is_writable($upload_dir)) {
    die("โฟลเดอร์ uploads/ ไม่มีสิทธิ์เขียน — รัน: chmod 755 uploads/");
}

// ---- ลบรูปภาพเดี่ยว ----
if (isset($_POST["delete_id"])) {
    $delete_id = (int) $_POST["delete_id"];

    $stmt_get = mysqli_prepare($conn, "SELECT image_name FROM tbl_upload WHERE id = ? AND user_id = ?");
    if ($stmt_get) {
        mysqli_stmt_bind_param($stmt_get, "ii", $delete_id, $user_id);
        mysqli_stmt_execute($stmt_get);
        $res_get = mysqli_stmt_get_result($stmt_get);
        $row_get = mysqli_fetch_assoc($res_get);

        if ($row_get) {
            $file_to_delete = $upload_dir . $row_get["image_name"];
            $stmt_del = mysqli_prepare($conn, "DELETE FROM tbl_upload WHERE id = ? AND user_id = ?");
            mysqli_stmt_bind_param($stmt_del, "ii", $delete_id, $user_id);
            if (mysqli_stmt_execute($stmt_del)) {
                if (file_exists($file_to_delete)) unlink($file_to_delete);
                $message  = "ลบรูปภาพสำเร็จ";
                $msg_type = "success";
            } else {
                $message  = "ลบข้อมูลในฐานข้อมูลไม่สำเร็จ";
                $msg_type = "error";
            }
        } else {
            $message  = "ไม่พบรูปภาพ หรือไม่มีสิทธิ์ลบ";
            $msg_type = "error";
        }
    }
}

// ---- ลบหลายรูปพร้อมกัน ----
if (isset($_POST["bulk_delete"]) && !empty($_POST["selected_ids"])) {
    $raw_ids     = $_POST["selected_ids"];
    $safe_ids    = array_map("intval", $raw_ids);
    $deleted_ok  = 0;
    $deleted_err = 0;

    foreach ($safe_ids as $del_id) {
        $stmt_get = mysqli_prepare($conn, "SELECT image_name FROM tbl_upload WHERE id = ? AND user_id = ?");
        if (!$stmt_get) continue;
        mysqli_stmt_bind_param($stmt_get, "ii", $del_id, $user_id);
        mysqli_stmt_execute($stmt_get);
        $res_get = mysqli_stmt_get_result($stmt_get);
        $row_get = mysqli_fetch_assoc($res_get);

        if (!$row_get) { $deleted_err++; continue; }

        $file_to_delete = $upload_dir . $row_get["image_name"];
        $stmt_del = mysqli_prepare($conn, "DELETE FROM tbl_upload WHERE id = ? AND user_id = ?");
        mysqli_stmt_bind_param($stmt_del, "ii", $del_id, $user_id);
        if (mysqli_stmt_execute($stmt_del)) {
            if (file_exists($file_to_delete)) unlink($file_to_delete);
            $deleted_ok++;
        } else {
            $deleted_err++;
        }
    }

    $message  = "ลบสำเร็จ {$deleted_ok} รูป" . ($deleted_err ? " (ล้มเหลว {$deleted_err} รูป)" : "");
    $msg_type = $deleted_err === 0 ? "success" : "error";
}

// ---- อัปโหลดรูปภาพ ----
if (isset($_POST["upload"])) {
    $upload_error = $_FILES["image"]["error"] ?? UPLOAD_ERR_NO_FILE;

    if ($upload_error !== UPLOAD_ERR_OK) {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE   => "ไฟล์ใหญ่เกินค่า upload_max_filesize ใน php.ini",
            UPLOAD_ERR_FORM_SIZE  => "ไฟล์ใหญ่เกินค่า MAX_FILE_SIZE ใน form",
            UPLOAD_ERR_PARTIAL    => "ไฟล์ถูกอัปโหลดมาไม่ครบ",
            UPLOAD_ERR_NO_FILE    => "ไม่ได้เลือกไฟล์",
            UPLOAD_ERR_NO_TMP_DIR => "ไม่มี temp folder",
            UPLOAD_ERR_CANT_WRITE => "เขียนไฟล์ไปยัง disk ไม่ได้",
            UPLOAD_ERR_EXTENSION  => "PHP extension หยุด upload",
        ];
        $message  = $error_messages[$upload_error] ?? "เกิดข้อผิดพลาด (code: $upload_error)";
        $msg_type = "error";
    } else {
        $file_name = $_FILES["image"]["name"];
        $file_tmp  = $_FILES["image"]["tmp_name"];
        $file_size = $_FILES["image"]["size"];
        $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed   = ["jpg", "jpeg", "png", "gif"];

        if (!in_array($file_ext, $allowed)) {
            $message  = "อนุญาตเฉพาะไฟล์ JPG, JPEG, PNG, GIF เท่านั้น";
            $msg_type = "error";
        } elseif ($file_size > 10 * 1024 * 1024) {
            $message  = "ไฟล์ต้องไม่เกิน 10 MB";
            $msg_type = "error";
        } elseif (!getimagesize($file_tmp)) {
            $message  = "ไฟล์ที่เลือกไม่ใช่รูปภาพที่ถูกต้อง";
            $msg_type = "error";
        } else {
            $new_name    = uniqid("IMG_", true) . "." . $file_ext;
            $upload_path = $upload_dir . $new_name;

            if (!move_uploaded_file($file_tmp, $upload_path)) {
                $message  = "move_uploaded_file() ล้มเหลว — ตรวจสอบสิทธิ์โฟลเดอร์ uploads/";
                $msg_type = "error";
            } else {
                $stmt = mysqli_prepare($conn, "INSERT INTO tbl_upload (user_id, image_name) VALUES (?, ?)");
                if (!$stmt) {
                    $message  = "Prepare statement ล้มเหลว: " . mysqli_error($conn);
                    $msg_type = "error";
                } else {
                    mysqli_stmt_bind_param($stmt, "is", $user_id, $new_name);
                    if (mysqli_stmt_execute($stmt)) {
                        $message  = "อัปโหลดสำเร็จ!";
                        $msg_type = "success";
                    } else {
                        unlink($upload_path);
                        $message  = "บันทึกฐานข้อมูลไม่สำเร็จ: " . mysqli_stmt_error($stmt);
                        $msg_type = "error";
                    }
                }
            }
        }
    }
}

// ---- ดึงข้อมูลรูปทั้งหมด ----
$images = [];
$stmt2  = mysqli_prepare($conn, "SELECT * FROM tbl_upload WHERE user_id = ? ORDER BY id DESC");
if ($stmt2) {
    mysqli_stmt_bind_param($stmt2, "i", $user_id);
    mysqli_stmt_execute($stmt2);
    $result = mysqli_stmt_get_result($stmt2);
    while ($row = mysqli_fetch_assoc($result)) {
        $images[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>อัปโหลดรูปภาพ</title>
    <link rel="stylesheet" href="style.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f0f2f5;
            color: #333;
        }

        .page-wrap {
            max-width: 1200px;
            margin: 0 auto;
            padding: 24px 16px;
        }

        /* ---- Upload Card ---- */
        .upload-card {
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,.08);
            margin-bottom: 28px;
        }
        .upload-card h2 { font-size: 1.2rem; margin-bottom: 6px; }
        .user-info { font-size: .85rem; color: #777; margin-bottom: 16px; }

        /* ---- Drag & Drop Zone ---- */
        .drop-zone {
            border: 2px dashed #aaa;
            border-radius: 10px;
            padding: 28px 20px;
            text-align: center;
            background: #fafafa;
            cursor: pointer;
            transition: border-color .2s, background .2s;
            margin-bottom: 12px;
            position: relative;
        }
        .drop-zone.dragover {
            border-color: #4f6ef7;
            background: #f0f3ff;
        }
        .drop-zone input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }
        .drop-zone-icon { font-size: 2rem; margin-bottom: 6px; }
        .drop-zone-text { font-size: .9rem; color: #666; }
        .drop-zone-text span { color: #4f6ef7; font-weight: 600; }
        .drop-zone-hint { font-size: .78rem; color: #aaa; margin-top: 4px; }

        /* Preview strip */
        #previewStrip {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 10px;
        }
        .preview-thumb {
            position: relative;
            width: 72px;
            height: 72px;
            border-radius: 8px;
            overflow: hidden;
            border: 1.5px solid #e0e0e0;
        }
        .preview-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .preview-thumb .rm-btn {
            position: absolute;
            top: 2px;
            right: 2px;
            background: rgba(0,0,0,.55);
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: .7rem;
            line-height: 18px;
            text-align: center;
            cursor: pointer;
            padding: 0;
        }

        .upload-form {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        .btn-upload {
            padding: 9px 22px;
            background: #4f6ef7;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: .95rem;
            cursor: pointer;
            transition: background .2s;
            white-space: nowrap;
        }
        .btn-upload:hover { background: #3a57d4; }
        .btn-upload:disabled { background: #aaa; cursor: not-allowed; }

        .msg-success {
            margin-top: 12px;
            padding: 10px 14px;
            background: #e6f4ea;
            color: #2d7a3a;
            border-radius: 8px;
            font-size: .9rem;
        }
        .msg-error {
            margin-top: 12px;
            padding: 10px 14px;
            background: #fde8e8;
            color: #c0392b;
            border-radius: 8px;
            font-size: .9rem;
        }

        /* ---- Gallery Header ---- */
        .gallery-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 14px;
        }
        .gallery-header h2 { font-size: 1.2rem; }

        /* ---- Search Bar ---- */
        .search-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .search-input {
            padding: 7px 12px;
            border: 1.5px solid #ddd;
            border-radius: 8px;
            font-size: .88rem;
            width: 220px;
            outline: none;
            transition: border-color .2s;
        }
        .search-input:focus { border-color: #4f6ef7; }

        .size-control {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: .85rem;
            color: #555;
        }
        .size-control input[type="range"] {
            width: 110px;
            accent-color: #4f6ef7;
            cursor: pointer;
        }

        /* ---- Bulk-action toolbar ---- */
        .bulk-toolbar {
            display: none;
            align-items: center;
            gap: 10px;
            background: #fff3cd;
            border: 1px solid #ffe08a;
            border-radius: 8px;
            padding: 8px 14px;
            margin-bottom: 14px;
            flex-wrap: wrap;
        }
        .bulk-toolbar.visible { display: flex; }
        .bulk-toolbar span { font-size: .88rem; color: #7a5c00; flex: 1; }
        .btn-select-all {
            padding: 6px 14px;
            background: #fff;
            border: 1.5px solid #ffe08a;
            border-radius: 7px;
            font-size: .84rem;
            cursor: pointer;
            color: #7a5c00;
            transition: background .15s;
        }
        .btn-select-all:hover { background: #ffe08a; }
        .btn-bulk-del {
            padding: 6px 14px;
            background: #e74c3c;
            color: #fff;
            border: none;
            border-radius: 7px;
            font-size: .84rem;
            cursor: pointer;
            transition: background .15s;
        }
        .btn-bulk-del:hover { background: #c0392b; }
        .btn-cancel-sel {
            padding: 6px 14px;
            background: #f0f2f5;
            border: none;
            border-radius: 7px;
            font-size: .84rem;
            cursor: pointer;
            color: #555;
            transition: background .15s;
        }
        .btn-cancel-sel:hover { background: #e0e2e5; }

        /* ---- Gallery Grid ---- */
        .gallery-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(auto-fill, minmax(var(--col-size, 200px), 1fr));
        }

        /* ---- Gallery Item ---- */
        .gallery-item {
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 1px 6px rgba(0,0,0,.1);
            transition: transform .15s, box-shadow .15s;
            position: relative;
            cursor: pointer;
        }
        .gallery-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(0,0,0,.15);
        }
        /* Selected state */
        .gallery-item.selected {
            outline: 3px solid #4f6ef7;
            outline-offset: -3px;
        }
        .gallery-item.selected .select-overlay {
            display: flex;
        }

        /* Checkbox overlay top-left */
        .select-overlay {
            display: none;
            position: absolute;
            top: 6px;
            left: 6px;
            width: 24px;
            height: 24px;
            background: #4f6ef7;
            border-radius: 50%;
            color: #fff;
            font-size: .85rem;
            align-items: center;
            justify-content: center;
            z-index: 5;
        }
        /* Always show a faint circle when in select mode */
        .select-mode .gallery-item .select-overlay {
            display: flex;
            background: rgba(255,255,255,.7);
            color: transparent;
        }
        .select-mode .gallery-item.selected .select-overlay {
            background: #4f6ef7;
            color: #fff;
        }

        .gallery-thumb {
            width: 100%;
            aspect-ratio: 1 / 1;
            object-fit: cover;
            display: block;
        }

        .gallery-item-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 6px 8px;
            gap: 4px;
        }
        .gallery-item-name {
            font-size: .75rem;
            color: #888;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            flex: 1;
        }
        .btn-delete {
            flex-shrink: 0;
            background: none;
            border: none;
            cursor: pointer;
            padding: 3px 5px;
            border-radius: 6px;
            color: #bbb;
            font-size: 1rem;
            line-height: 1;
            transition: background .15s, color .15s;
        }
        .btn-delete:hover { background: #fde8e8; color: #e74c3c; }

        /* Toggle select mode button */
        .btn-toggle-select {
            padding: 7px 16px;
            background: #fff;
            border: 1.5px solid #ddd;
            border-radius: 8px;
            font-size: .85rem;
            cursor: pointer;
            color: #555;
            transition: background .15s, border-color .15s;
        }
        .btn-toggle-select:hover { background: #f0f2f5; border-color: #bbb; }
        .btn-toggle-select.active { border-color: #4f6ef7; color: #4f6ef7; background: #f0f3ff; }

        /* No results */
        .no-results {
            display: none;
            text-align: center;
            padding: 40px 0;
            color: #aaa;
            font-size: .95rem;
        }
        .no-results span { font-size: 2.5rem; display: block; margin-bottom: 8px; }

        /* ---- Lightbox ---- */
        .lightbox {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.88);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }
        .lightbox.active { display: flex; }
        .lightbox-img {
            max-width: 90vw;
            max-height: 80vh;
            border-radius: 8px;
            object-fit: contain;
            box-shadow: 0 8px 40px rgba(0,0,0,.6);
        }
        .lightbox-caption { margin-top: 10px; color: #ccc; font-size: .85rem; }
        .lightbox-close {
            position: absolute;
            top: 16px; right: 20px;
            font-size: 2rem;
            color: #fff;
            cursor: pointer;
            user-select: none;
        }
        .lightbox-close:hover { color: #f66; }
        .lightbox-prev, .lightbox-next {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            font-size: 2.5rem;
            color: #fff;
            cursor: pointer;
            user-select: none;
            padding: 0 18px;
            opacity: .7;
            transition: opacity .15s;
        }
        .lightbox-prev:hover, .lightbox-next:hover { opacity: 1; }
        .lightbox-prev { left: 0; }
        .lightbox-next { right: 0; }
        .lightbox-delete {
            position: absolute;
            bottom: 24px; right: 24px;
            background: rgba(231,76,60,.85);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 8px 18px;
            font-size: .9rem;
            cursor: pointer;
            transition: background .2s;
        }
        .lightbox-delete:hover { background: #c0392b; }

        /* ---- Confirm Modal ---- */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.active { display: flex; }
        .modal-box {
            background: #fff;
            border-radius: 12px;
            padding: 28px 32px;
            max-width: 360px;
            width: 90%;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0,0,0,.2);
        }
        .modal-box h3 { font-size: 1.1rem; margin-bottom: 10px; }
        .modal-box p  { font-size: .9rem; color: #666; margin-bottom: 22px; }
        .modal-actions { display: flex; gap: 10px; justify-content: center; }
        .btn-cancel {
            padding: 9px 22px;
            background: #f0f2f5;
            color: #555;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: .9rem;
            transition: background .15s;
        }
        .btn-cancel:hover { background: #e0e2e5; }
        .btn-confirm-del {
            padding: 9px 22px;
            background: #e74c3c;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: .9rem;
            transition: background .15s;
        }
        .btn-confirm-del:hover { background: #c0392b; }

        /* ---- Empty state ---- */
        .empty-state {
            text-align: center;
            padding: 48px 0;
            color: #aaa;
            font-size: 1rem;
        }
        .empty-state span { font-size: 3rem; display: block; margin-bottom: 10px; }

        /* Hidden items from search */
        .gallery-item.hidden { display: none; }
    </style>
</head>
<body>

<?php include "navbar.php"; ?>

<div class="page-wrap">

    <!-- Upload Card -->
    <div class="upload-card">
        <h2>📤 Upload รูปภาพ</h2>
        <p class="user-info">
            ผู้ใช้: <?php echo htmlspecialchars($_SESSION["name"]); ?>
        </p>

        <!-- Drag & Drop Zone -->
        <div class="drop-zone" id="dropZone">
            <input type="file" name="image" id="fileInput" accept="image/jpeg,image/png,image/gif" required>
            <div class="drop-zone-icon">🖼️</div>
            <div class="drop-zone-text">ลากรูปมาวางที่นี่ หรือ <span>คลิกเพื่อเลือกไฟล์</span></div>
            <div class="drop-zone-hint">JPG, PNG, GIF — ไม่เกิน 10 MB</div>
        </div>

        <!-- Preview -->
        <div id="previewStrip"></div>

        <!-- Upload button (outside drop-zone, inside its own form) -->
        <form class="upload-form" method="post" enctype="multipart/form-data" id="uploadForm">
            <input type="hidden" name="MAX_FILE_SIZE" value="10485760">
            <input type="file" name="image" id="formFileInput" accept="image/jpeg,image/png,image/gif" required style="display:none">
            <button class="btn-upload" type="submit" name="upload" id="uploadBtn" disabled>⬆️ Upload</button>
        </form>

        <?php if ($message !== ""): ?>
            <div class="msg-<?php echo $msg_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Gallery Header -->
    <div class="gallery-header">
        <h2>🖼 รูปภาพของฉัน <span style="font-size:.85rem;color:#aaa;font-weight:400" id="totalCount">(<?php echo count($images); ?> รูป)</span></h2>

        <div class="search-wrap">
            <!-- Search -->
            <input
                type="text"
                class="search-input"
                id="searchInput"
                placeholder="🔍 ค้นหาชื่อไฟล์..."
                oninput="filterGallery()"
            >
            <!-- Size slider -->
            <div class="size-control">
                <span>ขนาด</span>
                <input type="range" id="sizeSlider" min="120" max="320" value="200" oninput="applySize(this.value)">
                <span id="sizeLabel">200px</span>
            </div>
            <!-- Toggle select mode -->
            <button class="btn-toggle-select" id="toggleSelectBtn" onclick="toggleSelectMode()">☑️ เลือกหลายรูป</button>
        </div>
    </div>

    <!-- Bulk action toolbar (shows when ≥1 item selected) -->
    <div class="bulk-toolbar" id="bulkToolbar">
        <span id="bulkCount">เลือก 0 รูป</span>
        <button class="btn-select-all" onclick="selectAll()">เลือกทั้งหมด</button>
        <button class="btn-cancel-sel" onclick="toggleSelectMode()">ยกเลิก</button>
        <button class="btn-bulk-del" onclick="confirmBulkDelete()">🗑 ลบที่เลือก</button>
    </div>

    <?php if (empty($images)): ?>
        <div class="empty-state" id="emptyState">
            <span>📷</span>
            ยังไม่มีรูปภาพ — อัปโหลดรูปแรกเลย!
        </div>
    <?php else: ?>
        <script>
            let allImages = <?php
                echo json_encode(array_map(fn($r) => [
                    "id"   => (int)$r["id"],
                    "name" => $r["image_name"],
                    "src"  => "uploads/" . $r["image_name"],
                ], $images));
            ?>;
        </script>

        <div class="no-results" id="noResults">
            <span>🔍</span>
            ไม่พบรูปที่ตรงกัน
        </div>

        <div class="gallery-grid" id="galleryGrid">
            <?php foreach ($images as $i => $row):
                $safe = htmlspecialchars($row["image_name"]);
                $id   = (int)$row["id"];
            ?>
                <div class="gallery-item" id="item-<?php echo $id; ?>" data-id="<?php echo $id; ?>" data-name="<?php echo $safe; ?>" data-index="<?php echo $i; ?>">
                    <!-- Checkbox overlay -->
                    <div class="select-overlay">✓</div>
                    <!-- Thumbnail — click handled by JS (see toggleItem / openLightbox) -->
                    <img
                        class="gallery-thumb"
                        src="uploads/<?php echo $safe; ?>"
                        alt="<?php echo $safe; ?>"
                        loading="lazy"
                    >
                    <div class="gallery-item-footer">
                        <span class="gallery-item-name"><?php echo $safe; ?></span>
                        <button
                            class="btn-delete"
                            title="ลบรูปนี้"
                            onclick="confirmDelete(<?php echo $id; ?>, '<?php echo $safe; ?>', event)"
                        >🗑</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<!-- Lightbox -->
<div class="lightbox" id="lightbox" onclick="closeLightboxBg(event)">
    <span class="lightbox-close" onclick="closeLightbox()">✕</span>
    <span class="lightbox-prev" onclick="navigate(-1, event)">&#8249;</span>
    <img class="lightbox-img" id="lightboxImg" src="" alt="">
    <p class="lightbox-caption" id="lightboxCaption"></p>
    <span class="lightbox-next" onclick="navigate(1, event)">&#8250;</span>
    <button class="lightbox-delete" onclick="confirmDeleteFromLightbox()">🗑 ลบรูปนี้</button>
</div>

<!-- Confirm Delete Modal -->
<div class="modal-overlay" id="confirmModal">
    <div class="modal-box">
        <h3 id="modalTitle">⚠️ ยืนยันการลบ</h3>
        <p id="modalDesc">คุณต้องการลบรูปนี้ใช่ไหม? ไม่สามารถกู้คืนได้</p>
        <div class="modal-actions">
            <button class="btn-cancel" onclick="closeModal()">ยกเลิก</button>
            <button class="btn-confirm-del" onclick="executeDelete()">ลบเลย</button>
        </div>
    </div>
</div>

<!-- Hidden single-delete form -->
<form id="deleteForm" method="post" style="display:none;">
    <input type="hidden" name="delete_id" id="deleteIdInput">
</form>

<!-- Hidden bulk-delete form -->
<form id="bulkDeleteForm" method="post" style="display:none;">
    <div id="bulkInputs"></div>
    <input type="hidden" name="bulk_delete" value="1">
</form>

<script>
// ========================
// Size slider
// ========================
const grid      = document.getElementById("galleryGrid");
const sizeLabel = document.getElementById("sizeLabel");

function applySize(val) {
    if (grid) grid.style.setProperty("--col-size", val + "px");
    if (sizeLabel) sizeLabel.textContent = val + "px";
}
applySize(200); // default

// ========================
// Drag & Drop Upload
// ========================
const dropZone      = document.getElementById("dropZone");
const fileInput     = document.getElementById("fileInput");      // inside drop zone (display overlay)
const formFileInput = document.getElementById("formFileInput");  // real input in upload form
const uploadBtn     = document.getElementById("uploadBtn");
const previewStrip  = document.getElementById("previewStrip");
let   selectedFile  = null;

dropZone.addEventListener("dragover", (e) => {
    e.preventDefault();
    dropZone.classList.add("dragover");
});
dropZone.addEventListener("dragleave", () => dropZone.classList.remove("dragover"));
dropZone.addEventListener("drop", (e) => {
    e.preventDefault();
    dropZone.classList.remove("dragover");
    const file = e.dataTransfer.files[0];
    if (file) handleFileSelect(file);
});
fileInput.addEventListener("change", () => {
    if (fileInput.files[0]) handleFileSelect(fileInput.files[0]);
});

function handleFileSelect(file) {
    selectedFile = file;
    // Copy file to the real form input via DataTransfer
    const dt = new DataTransfer();
    dt.items.add(file);
    formFileInput.files = dt.files;
    uploadBtn.disabled  = false;

    // Show preview
    previewStrip.innerHTML = "";
    const reader = new FileReader();
    reader.onload = (e) => {
        const wrap = document.createElement("div");
        wrap.className = "preview-thumb";
        wrap.innerHTML = `
            <img src="${e.target.result}" alt="">
            <button class="rm-btn" onclick="clearPreview()" title="ลบออก">✕</button>
        `;
        previewStrip.appendChild(wrap);
    };
    reader.readAsDataURL(file);
}

function clearPreview() {
    previewStrip.innerHTML = "";
    formFileInput.value    = "";
    fileInput.value        = "";
    uploadBtn.disabled     = true;
    selectedFile           = null;
}

// ========================
// Search / Filter
// ========================
function filterGallery() {
    const q     = document.getElementById("searchInput").value.toLowerCase().trim();
    const items = document.querySelectorAll(".gallery-item");
    let   shown = 0;

    items.forEach(item => {
        const name  = item.dataset.name.toLowerCase();
        const match = !q || name.includes(q);
        item.classList.toggle("hidden", !match);
        if (match) shown++;
    });

    const noRes = document.getElementById("noResults");
    if (noRes) noRes.style.display = (shown === 0 && q) ? "block" : "none";
}

// ========================
// Multi-select
// ========================
let selectMode   = false;
let selectedIds  = new Set();

function toggleSelectMode() {
    selectMode = !selectMode;
    selectedIds.clear();

    const btn  = document.getElementById("toggleSelectBtn");
    const g    = document.getElementById("galleryGrid");
    const tb   = document.getElementById("bulkToolbar");

    btn.classList.toggle("active", selectMode);
    btn.textContent = selectMode ? "✕ ยกเลิก" : "☑️ เลือกหลายรูป";
    if (g) g.classList.toggle("select-mode", selectMode);
    tb.classList.toggle("visible", false);

    // Reset all selected visuals
    document.querySelectorAll(".gallery-item").forEach(el => el.classList.remove("selected"));
    updateBulkToolbar();
}

function toggleItem(el) {
    const id = parseInt(el.dataset.id);
    if (el.classList.contains("selected")) {
        el.classList.remove("selected");
        selectedIds.delete(id);
    } else {
        el.classList.add("selected");
        selectedIds.add(id);
    }
    updateBulkToolbar();
}

function selectAll() {
    document.querySelectorAll(".gallery-item:not(.hidden)").forEach(el => {
        el.classList.add("selected");
        selectedIds.add(parseInt(el.dataset.id));
    });
    updateBulkToolbar();
}

function updateBulkToolbar() {
    const tb = document.getElementById("bulkToolbar");
    document.getElementById("bulkCount").textContent = `เลือก ${selectedIds.size} รูป`;
    tb.classList.toggle("visible", selectMode && selectedIds.size > 0);
}

// Click handler on gallery items
document.querySelectorAll(".gallery-item").forEach(el => {
    el.addEventListener("click", (e) => {
        // Don't intercept delete button clicks
        if (e.target.closest(".btn-delete")) return;
        if (selectMode) {
            toggleItem(el);
        } else {
            openLightbox(parseInt(el.dataset.index));
        }
    });
});

// ========================
// Bulk Delete
// ========================
let pendingBulkDelete = false;

function confirmBulkDelete() {
    if (selectedIds.size === 0) return;
    pendingBulkDelete = true;
    document.getElementById("modalTitle").textContent = "⚠️ ยืนยันการลบ";
    document.getElementById("modalDesc").textContent =
        `ลบ ${selectedIds.size} รูปที่เลือกใช่ไหม? ไม่สามารถกู้คืนได้`;
    document.getElementById("confirmModal").classList.add("active");
}

// ========================
// Lightbox
// ========================
let currentIndex = 0;
if (typeof allImages === "undefined") var allImages = [];

function openLightbox(index) {
    currentIndex = index;
    showLightboxImage(index);
    document.getElementById("lightbox").classList.add("active");
    document.body.style.overflow = "hidden";
}

function closeLightbox() {
    document.getElementById("lightbox").classList.remove("active");
    document.body.style.overflow = "";
}

function closeLightboxBg(e) {
    if (e.target === document.getElementById("lightbox")) closeLightbox();
}

function showLightboxImage(index) {
    const img = allImages[index];
    document.getElementById("lightboxImg").src = img.src;
    document.getElementById("lightboxCaption").textContent = img.name;
}

function navigate(dir, e) {
    e.stopPropagation();
    currentIndex = (currentIndex + dir + allImages.length) % allImages.length;
    showLightboxImage(currentIndex);
}

document.addEventListener("keydown", (e) => {
    const lb = document.getElementById("lightbox");
    if (!lb.classList.contains("active")) return;
    if (e.key === "ArrowRight") navigate(1,  { stopPropagation: () => {} });
    if (e.key === "ArrowLeft")  navigate(-1, { stopPropagation: () => {} });
    if (e.key === "Escape")     closeLightbox();
});

// ========================
// Single Delete
// ========================
let pendingDeleteId   = null;
let pendingDeleteName = null;

function confirmDelete(id, name, e) {
    if (e) e.stopPropagation();
    pendingDeleteId   = id;
    pendingDeleteName = name;
    pendingBulkDelete = false;
    document.getElementById("modalTitle").textContent = "⚠️ ยืนยันการลบ";
    document.getElementById("modalDesc").textContent =
        `ลบ "${name}" ใช่ไหม? ไม่สามารถกู้คืนได้`;
    document.getElementById("confirmModal").classList.add("active");
}

function confirmDeleteFromLightbox() {
    const img = allImages[currentIndex];
    closeLightbox();
    confirmDelete(img.id, img.name, null);
}

function closeModal() {
    document.getElementById("confirmModal").classList.remove("active");
    pendingDeleteId   = null;
    pendingDeleteName = null;
    pendingBulkDelete = false;
}

function executeDelete() {
    if (pendingBulkDelete) {
        // Build bulk form inputs
        const container = document.getElementById("bulkInputs");
        container.innerHTML = "";
        selectedIds.forEach(id => {
            const inp = document.createElement("input");
            inp.type  = "hidden";
            inp.name  = "selected_ids[]";
            inp.value = id;
            container.appendChild(inp);
        });
        document.getElementById("bulkDeleteForm").submit();
    } else {
        if (!pendingDeleteId) return;
        document.getElementById("deleteIdInput").value = pendingDeleteId;
        document.getElementById("deleteForm").submit();
    }
}
</script>

</body>
</html>