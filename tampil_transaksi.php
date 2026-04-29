<?php
include "koneksi.php";
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php"); exit;
}

$total_omzet = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(total),0) FROM transaksi"))[0];
$data = mysqli_query($conn, "SELECT t.*, u.username FROM transaksi t JOIN users u ON t.user_id=u.id ORDER BY t.tanggal DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Transaksi — Mini Kasir</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="navbar">
    <a href="admin.php" class="navbar-brand">
        <div class="logo-icon">🛒</div>
        Mini Kasir
    </a>
    <div class="navbar-nav">
        <a href="admin.php" class="nav-link">Dashboard</a>
        <a href="barang.php" class="nav-link">Barang</a>
        <a href="tampil_transaksi.php" class="nav-link active">Transaksi</a>
        <a href="users.php" class="nav-link">Users</a>
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>
</nav>

<div class="container">
    <div class="page-header flex-between">
        <div>
            <h1 class="page-title">Data Transaksi</h1>
            <p class="page-subtitle">Riwayat seluruh transaksi</p>
        </div>
        <div>
            <div class="stat-card green" style="padding:14px 20px; min-width:200px;">
                <div class="stat-label" style="margin-bottom:4px;">Total Omzet</div>
                <div class="stat-value" style="font-size:1.2rem;">Rp <?= number_format($total_omzet, 0, ',', '.') ?></div>
            </div>
        </div>
    </div>

    <div class="card" style="padding:0; overflow:hidden;">
        <div class="table-wrapper" style="border:none; border-radius:0;">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tanggal</th>
                        <th>Kasir</th>
                        <th>Total</th>
                        <th>Bayar</th>
                        <th>Kembalian</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($data) > 0):
                        $no = 1;
                        while ($d = mysqli_fetch_assoc($data)): ?>
                    <tr>
                        <td style="color:var(--text3);"><?= $no++ ?></td>
                        <td class="td-mono"><?= date('d/m/Y H:i', strtotime($d['tanggal'])) ?></td>
                        <td>
                            <span class="badge badge-blue"><?= htmlspecialchars($d['username']) ?></span>
                        </td>
                        <td class="td-mono" style="font-weight:700;">Rp <?= number_format($d['total'], 0, ',', '.') ?></td>
                        <td class="td-mono">Rp <?= number_format($d['bayar'], 0, ',', '.') ?></td>
                        <td class="td-mono" style="color:var(--success);">Rp <?= number_format($d['kembalian'], 0, ',', '.') ?></td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <div class="empty-icon">📋</div>
                                <div class="empty-text">Belum ada transaksi.</div>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>