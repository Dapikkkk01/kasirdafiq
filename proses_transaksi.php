<?php
include "koneksi.php";
if (!isset($_SESSION['role'])) {
    header("Location: login.php"); exit;
}

function hitungTotal($harga, $diskon) {
    $potongan = ($diskon / 100) * $harga;
    return $harga - $potongan;
}

function hitungKembalian($bayar, $total) {
    return $bayar - $total;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['harga'])) {
    header("Location: kasir.php"); exit;
}

$harga       = (float) $_POST['harga'];
$diskon      = (float) $_POST['diskon'];
$bayar       = (float) $_POST['bayar'];
$barang_id   = (int)   ($_POST['barang_id'] ?? 0);
$user_id     = (int)   $_SESSION['user_id'];

// Validasi input
if ($harga <= 0 || $bayar <= 0) {
    header("Location: kasir.php?error=invalid"); exit;
}

$total     = hitungTotal($harga, $diskon);
$kembalian = hitungKembalian($bayar, $total);

if ($kembalian < 0) {
    $kurang = abs($kembalian);
    header("Location: kasir.php?error=kurang&kurang=" . urlencode($kurang)); exit;
}

// Simpan transaksi
$stmt = mysqli_prepare($conn, "INSERT INTO transaksi (tanggal, total, bayar, kembalian, user_id) VALUES (NOW(), ?, ?, ?, ?)");
mysqli_stmt_bind_param($stmt, "dddi", $total, $bayar, $kembalian, $user_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// Kurangi stok jika barang dipilih
if ($barang_id > 0) {
    $stmtStok = mysqli_prepare($conn, "UPDATE barang SET stok = stok - 1 WHERE id = ? AND stok > 0");
    mysqli_stmt_bind_param($stmtStok, "i", $barang_id);
    mysqli_stmt_execute($stmtStok);
    mysqli_stmt_close($stmtStok);

    // Ambil nama barang
    $res  = mysqli_query($conn, "SELECT nama_barang FROM barang WHERE id=$barang_id");
    $brow = mysqli_fetch_assoc($res);
    $nama_barang = $brow['nama_barang'] ?? '—';
} else {
    $nama_barang = '—';
}

$potongan = ($diskon / 100) * $harga;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Transaksi — Mini Kasir</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<nav class="navbar">
    <a href="kasir.php" class="navbar-brand">
        <div class="logo-icon">🛒</div>
        Mini Kasir
    </a>
    <div class="navbar-nav">
        <?php if ($_SESSION['role'] == 'admin'): ?>
            <a href="admin.php" class="nav-link">Dashboard</a>
        <?php endif; ?>
        <a href="kasir.php" class="nav-link">Kasir</a>
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>
</nav>

<div class="container" style="max-width:480px;">
    <div class="alert alert-success" style="margin-bottom:24px;">
        ✅ Transaksi berhasil disimpan!
    </div>

    <div class="receipt">
        <div class="receipt-header">
            <div class="receipt-title">🛒 MINI KASIR</div>
            <div class="receipt-sub"><?= date('d/m/Y H:i:s') ?></div>
            <div class="receipt-sub">Kasir: <?= htmlspecialchars($_SESSION['username']) ?></div>
        </div>

        <hr class="receipt-divider">

        <div class="receipt-row">
            <span>Barang</span>
            <span><?= htmlspecialchars($nama_barang) ?></span>
        </div>
        <div class="receipt-row">
            <span>Harga Asli</span>
            <span>Rp <?= number_format($harga, 0, ',', '.') ?></span>
        </div>

        <?php if ($diskon > 0): ?>
        <div class="receipt-row diskon">
            <span>Diskon (<?= $diskon ?>%)</span>
            <span>− Rp <?= number_format($potongan, 0, ',', '.') ?></span>
        </div>
        <?php endif; ?>

        <hr class="receipt-divider">

        <div class="receipt-row total">
            <span>TOTAL</span>
            <span>Rp <?= number_format($total, 0, ',', '.') ?></span>
        </div>
        <div class="receipt-row">
            <span>Bayar</span>
            <span>Rp <?= number_format($bayar, 0, ',', '.') ?></span>
        </div>
        <div class="receipt-row kembalian">
            <span>KEMBALIAN</span>
            <span>Rp <?= number_format($kembalian, 0, ',', '.') ?></span>
        </div>

        <hr class="receipt-divider">
        <div style="text-align:center;color:var(--text3);font-size:0.75rem;">Terima kasih!</div>
    </div>

<div style="margin-top:20px; display:flex; flex-direction:column; gap:12px;">
    <button onclick="window.print()" class="btn btn-ghost" style="width:100%;">
        🖨️ Print Struk
    </button>
    <div style="display:flex; gap:12px;">
        <a href="kasir.php" class="btn btn-primary" style="flex:1;">
            + Transaksi Baru
        </a>
        <?php if ($_SESSION['role'] == 'admin'): ?>
        <a href="tampil_transaksi.php" class="btn btn-ghost" style="flex:1;">Lihat Semua</a>
        <?php endif; ?>
    </div>
</div>
</div>
</body>
</html>
