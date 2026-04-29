<?php
include "koneksi.php";

if (isset($_POST['username'])) {
    $user = mysqli_real_escape_string($conn, $_POST['username']);
    $pass = $_POST['password'];

    $data = mysqli_query($conn, "SELECT * FROM users WHERE username='$user'");

    if (mysqli_num_rows($data) > 0) {
        $d = mysqli_fetch_assoc($data);

        if (password_verify($pass, $d['password'])) {
            $_SESSION['role']     = $d['role'];
            $_SESSION['user_id']  = $d['id'];
            $_SESSION['username'] = $d['username'];

            header("Location: " . ($d['role'] == 'admin' ? "admin.php" : "kasir.php"));
            exit;
        }
    }

    header("Location: login.php?error=1");
    exit;
}
header("Location: login.php");
exit;
?>
