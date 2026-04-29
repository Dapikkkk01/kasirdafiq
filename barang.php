<?php
include "koneksi.php";
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php"); exit;
}

$pesan = $tipe = '';

// Simpan barang
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nama'])) {
    $nama   = trim($_POST['nama']);
    $harga  = (float) $_POST['harga'];
    $stok   = (int)   $_POST['stok'];
    $diskon = (float) $_POST['diskon_otomatis'];

    if ($nama === '' || $harga <= 0 || $stok < 0 || $diskon < 0 || $diskon > 100) {
        $pesan = "Data tidak valid. Pastikan semua field diisi dengan benar.";
        $tipe  = "danger";
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO barang (nama_barang, harga, stok, diskon_otomatis) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sdid", $nama, $harga, $stok, $diskon);
        if (mysqli_stmt_execute($stmt)) {
            $pesan = "Barang \"$nama\" berhasil ditambahkan!";
            $tipe  = "success";
        } else {
            $pesan = "Gagal menyimpan: " . mysqli_error($conn);
            $tipe  = "danger";
        }
        mysqli_stmt_close($stmt);
    }
}

if (isset($_GET['pesan'])) {
    $pesan = $_GET['pesan'] === 'hapus_sukses' ? 'Barang berhasil dihapus.' : '';
    $tipe  = 'success';
}

// Ambil semua barang
$data_barang = mysqli_query($conn, "SELECT * FROM barang ORDER BY id DESC");
$low_stok    = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM barang WHERE stok < 5"))[0];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Barang — Mini Kasir</title>
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
        <a href="barang.php" class="nav-link active">
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
        <h1 class="page-title">Kelola Barang</h1>
        <p class="page-subtitle">Tambah, lihat, dan hapus data barang</p>
    </div>

    <?php if ($pesan): ?>
        <div class="alert alert-<?= $tipe ?>"><?= $tipe === 'success' ? '✅' : '⚠️' ?> <?= htmlspecialchars($pesan) ?></div>
    <?php endif; ?>

    <div class="grid-2" style="align-items:start; gap:28px;">

        <!-- FORM TAMBAH BARANG -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">➕ Tambah Barang Baru</span>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Nama Barang</label>
                    <input type="text" name="nama" class="form-control" placeholder="cth. Kopi Arabika" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Harga (Rp)</label>
                        <div class="input-prefix">
                            <span>Rp</span>
                            <input type="number" name="harga" class="form-control" placeholder="0" min="0" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Stok</label>
                        <input type="number" name="stok" class="form-control" placeholder="0" min="0" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Diskon Otomatis (%)</label>
                    <div class="input-prefix">
                        <input type="number" name="diskon_otomatis" class="form-control" placeholder="0" min="0" max="100" value="0" style="border-radius:var(--radius-sm) 0 0 var(--radius-sm); border-right:none;">
                        <span style="border-left:none; border-radius:0 var(--radius-sm) var(--radius-sm) 0;">%</span>
                    </div>
                    <div class="form-hint">💡 Diskon ini otomatis diterapkan saat barang dipilih di kasir.</div>
                </div>
                <button type="submit" class="btn btn-primary btn-full">
                    Simpan Barang
                </button>
            </form>
        </div>

        <!-- DAFTAR BARANG -->
        <div class="card" style="padding:0; overflow:hidden;">
            <div class="card-header" style="padding:20px 24px 16px;">
                <span class="card-title">📦 Daftar Barang</span>
                <span class="badge badge-blue"><?= mysqli_num_rows($data_barang) ?> item</span>
            </div>
            <div class="table-wrapper" style="border:none; border-radius:0;">
                <table>
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Harga</th>
                            <th>Stok</th>
                            <th>Diskon</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($data_barang) > 0):
                            mysqli_data_seek($data_barang, 0);
                            while ($d = mysqli_fetch_assoc($data_barang)): ?>
                        <tr>
                            <td><?= htmlspecialchars($d['nama_barang']) ?></td>
                            <td class="td-mono">Rp <?= number_format($d['harga'], 0, ',', '.') ?></td>
                            <td>
                                <?php if ($d['stok'] < 5): ?>
                                    <span class="badge badge-red"><?= $d['stok'] ?></span>
                                <?php elseif ($d['stok'] < 20): ?>
                                    <span class="badge badge-orange"><?= $d['stok'] ?></span>
                                <?php else: ?>
                                    <span class="badge badge-green"><?= $d['stok'] ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($d['diskon_otomatis'] > 0): ?>
                                    <span class="diskon-tag">🏷️ <?= $d['diskon_otomatis'] ?>%</span>
                                <?php else: ?>
                                    <span style="color:var(--text3);">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="hapus_barang.php?id=<?= $d['id'] ?>"
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Yakin ingin menghapus barang ini?')">
                                    Hapus
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <div class="empty-icon">📭</div>
                                    <div class="empty-text">Belum ada barang.</div>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
</body>
</html>