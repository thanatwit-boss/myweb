<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <form action="register.php" method="post" class="form-container">
        <h1>สมัครสมาชิก</h1>
        <label for="username">ชื่อผู้ใช้:</label>
        <input type="text" id="username" name="username" required><br><br>

        <label for="email">อีเมล:</label>
        <input type="email" id="email" name="email" required><br><br>

        <label for="password">รหัสผ่าน:</label>
        <input type="password" id="password" name="password" required><br><br>

        <button type="submit">สมัครสมาชิก</button>
    </form>
</body>
</html>