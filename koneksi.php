<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "mini_kasir");
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8mb4");
?>
