<?php
include "koneksi.php";
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php"); exit;
}

// Stats
$total_barang    = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM barang"))[0];
$total_transaksi = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM transaksi"))[0];
$total_omzet     = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(total),0) FROM transaksi"))[0];
$low_stok        = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM barang WHERE stok < 5"))[0];

// Transaksi terakhir
$transaksi_akhir = mysqli_query($conn, "SELECT t.*, u.username FROM transaksi t JOIN users u ON t.user_id=u.id ORDER BY t.tanggal DESC LIMIT 5");

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin — Mini Kasir</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="navbar">
    <a href="admin.php" class="navbar-brand">
        <div class="logo-icon">🛒</div>
        Mini Kasir
    </a>
    <div class="navbar-nav">
        <a href="admin.php" class="nav-link active">Dashboard</a>
        <a href="barang.php" class="nav-link">
            Barang
            <?php if ($low_stok > 0): ?>
                <span class="nav-badge"><?= $low_stok ?></span>
            <?php endif; ?>
        </a>
        <a href="tampil_transaksi.php" class="nav-link">Transaksi</a>
        <a href="users.php" class="nav-link">Users</a>
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>
</nav>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">Selamat datang, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong> 👋</p>
    </div>

    <div class="grid-3" style="margin-bottom:28px;">
        <div class="stat-card blue">
            <div class="stat-icon blue">📦</div>
            <div class="stat-value"><?= $total_barang ?></div>
            <div class="stat-label">Total Barang</div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon green">💳</div>
            <div class="stat-value"><?= $total_transaksi ?></div>
            <div class="stat-label">Total Transaksi</div>
        </div>
        <div class="stat-card orange">
            <div class="stat-icon orange">💰</div>
            <div class="stat-value">Rp <?= number_format($total_omzet, 0, ',', '.') ?></div>
            <div class="stat-label">Total Omzet</div>
        </div>
    </div>

    <?php if ($low_stok > 0): ?>
    <div class="alert alert-warning" style="margin-bottom:24px;">
        ⚠️ Ada <strong><?= $low_stok ?> barang</strong> dengan stok kurang dari 5. <a href="barang.php" style="color:inherit;text-decoration:underline;">Lihat →</a>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <span class="card-title">Transaksi Terbaru</span>
            <a href="tampil_transaksi.php" class="btn btn-ghost btn-sm">Lihat Semua</a>
        </div>
        <?php if (mysqli_num_rows($transaksi_akhir) > 0): ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Kasir</th>
                        <th>Total</th>
                        <th>Bayar</th>
                        <th>Kembalian</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($transaksi_akhir)): ?>
                    <tr>
                        <td class="td-mono"><?= date('d/m/Y H:i', strtotime($row['tanggal'])) ?></td>
                        <td><?= htmlspecialchars($row['username']) ?></td>
                        <td class="td-mono">Rp <?= number_format($row['total'], 0, ',', '.') ?></td>
                        <td class="td-mono">Rp <?= number_format($row['bayar'], 0, ',', '.') ?></td>
                        <td class="td-mono" style="color:var(--success);">Rp <?= number_format($row['kembalian'], 0, ',', '.') ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">📋</div>
            <div class="empty-text">Belum ada transaksi.</div>
        </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>