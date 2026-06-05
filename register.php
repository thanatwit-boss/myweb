<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CDV</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <?php
        require "config.php";

        $message = "";

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $name = trim($_POST["name"]);
            $email = trim($_POST["email"]);
            $password = $_POST["password"];

            if ($name == "" || $email == "" || $password == "") {
                $message = "กรุณากรอกข้อมูลให้ครบ";
            } else {
                $hashPassword = password_hash($password, PASSWORD_DEFAULT);

                $sql = "INSERT INTO tbl_user (name, email, password) VALUES (?, ?, ?)";
                $stmt = mysqli_prepare($conn, $sql);

                mysqli_stmt_bind_param($stmt, "sss", $name, $email, $hashPassword);

                if (mysqli_stmt_execute($stmt)) {
                    $message = "สมัครสมาชิกสำเร็จ";
                } else {
                    $message = "อีเมลนี้ถูกใช้งานแล้ว";
                }

                mysqli_stmt_close($stmt);
            }
        }
    ?>

  
    <div class="form-container">
        <h1>สมัครสมาชิก</h1>
        <form action="register.php" method="post">
            <label for="name" class="label">ชื่อผู้ใช้:</label>
            <input type="text" id="name" name="name" required class="input-field">

            <label for="email" class="label">อีเมล:</label>
            <input type="email" id="email" name="email" required class="input-field">

            <label for="password" class="label">รหัสผ่าน:</label>
            <input type="password" id="password" name="password" required class="input-field">

             <input type="submit" value="สมัครสมาชิก" class="btn">
           
        </form>
         
        <p><?= $message ?></p>
        <a href="login.php">เข้าสู่ระบบ</a>
    </div>

</body>

</html>
