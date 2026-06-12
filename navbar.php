<div class="navbar">
    <a href="index.php">หน้าแรก</a>
    <a href="about.php">เกี่ยวกับเรา</a>

    <?php if (isset($_SESSION["user_id"])){ ?>
        <a href="profile.php">โปรไฟล์</a>
        <a href="logout.php">ออกจากระบบ</a>
        <a href="upload.php">อัปโหลด</a>
    <?php }else { ?>
     <a href="login.php">เข้าสู่ระบบ</a> 
     <a href="register.php">สมัครสมาชิก</a>
    <?php  } ?>

</div>