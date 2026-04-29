<?php
include "koneksi.php";
if (!isset($_SESSION['role'])) {
    header("Location: login.php"); exit;
}

// Ambil daftar barang untuk dropdown
$daftar_barang = mysqli_query($conn, "SELECT * FROM barang WHERE stok > 0 ORDER BY nama_barang ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasir — Mini Kasir</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .preview-box {
            background: var(--bg2); border: 1px solid var(--border);
            border-radius: var(--radius-sm); padding: 16px;
            margin-top: 20px; display: none;
        }
        .preview-box.show { display: block; }
        .preview-row { display: flex; justify-content: space-between; padding: 5px 0; font-size:0.85rem; color:var(--text2); }
        .preview-row.highlight { color: var(--white); font-weight: 700; font-size: 1rem; border-top: 1px dashed var(--border); margin-top: 6px; padding-top: 10px; }
        .preview-row.kurang { color: var(--danger); font-weight:700; }
    </style>
</head>
<body>

<nav class="navbar">
    <a href="<?= $_SESSION['role'] == 'admin' ? 'admin.php' : 'kasir.php' ?>" class="navbar-brand">
        <div class="logo-icon">🛒</div>
        Mini Kasir
    </a>
    <div class="navbar-nav">
        <?php if ($_SESSION['role'] == 'admin'): ?>
            <a href="admin.php" class="nav-link">Dashboard</a>
            <a href="barang.php" class="nav-link">Barang</a>
            <a href="tampil_transaksi.php" class="nav-link">Transaksi</a>
        <?php endif; ?>
        <a href="kasir.php" class="nav-link active">Kasir</a>
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>
</nav>

<div class="container" style="max-width:680px;">
    <div class="page-header">
        <h1 class="page-title">Transaksi Baru</h1>
        <p class="page-subtitle">Kasir: <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></p>
    </div>

    <div class="card">
        <form method="POST" action="proses_transaksi.php" id="formTransaksi">

            <!-- Pilih Barang -->
            <div class="form-group">
                <label class="form-label">Pilih Barang</label>
                <select name="barang_id" id="barangSelect" class="form-control" required>
                    <option value="">— Pilih barang —</option>
                    <?php while ($b = mysqli_fetch_assoc($daftar_barang)): ?>
                    <option value="<?= $b['id'] ?>"
                        data-harga="<?= $b['harga'] ?>"
                        data-diskon="<?= $b['diskon_otomatis'] ?>"
                        data-stok="<?= $b['stok'] ?>"
                        data-nama="<?= htmlspecialchars($b['nama_barang']) ?>">
                        <?= htmlspecialchars($b['nama_barang']) ?> — Rp <?= number_format($b['harga'], 0, ',', '.') ?>
                        <?php if ($b['diskon_otomatis'] > 0): ?>(Diskon <?= $b['diskon_otomatis'] ?>%)<?php endif; ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Harga & Diskon -->
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Harga (Rp)</label>
                    <div class="input-prefix">
                        <span>Rp</span>
                        <input type="number" name="harga" id="inputHarga" class="form-control" placeholder="0" min="0" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Diskon</label>
                    <div class="input-prefix">
                        <input type="number" name="diskon" id="inputDiskon" class="form-control"
                               placeholder="0" min="0" max="100" value="0"
                               style="border-radius:var(--radius-sm) 0 0 var(--radius-sm);border-right:none;">
                        <span style="border-left:none;border-radius:0 var(--radius-sm) var(--radius-sm) 0;">%</span>
                    </div>
                    <div class="form-hint" id="diskonHint"></div>
                </div>
            </div>

            <!-- Bayar -->
            <div class="form-group">
                <label class="form-label">Uang Bayar (Rp)</label>
                <div class="input-prefix">
                    <span>Rp</span>
                    <input type="number" name="bayar" id="inputBayar" class="form-control" placeholder="0" min="0" required>
                </div>
            </div>

            <!-- Preview Kalkulasi -->
            <div class="preview-box" id="previewBox">
                <div class="preview-row">
                    <span>Harga</span>
                    <span id="prvHarga">—</span>
                </div>
                <div class="preview-row" id="rowDiskon" style="color:var(--warning)">
                    <span>Potongan Diskon</span>
                    <span id="prvPotongan">—</span>
                </div>
                <div class="preview-row highlight">
                    <span>Total</span>
                    <span id="prvTotal">—</span>
                </div>
                <div class="preview-row" id="rowKembalian">
                    <span id="labelKembalian">Kembalian</span>
                    <span id="prvKembalian">—</span>
                </div>
            </div>

            <!-- Error bayar kurang -->
            <div id="errorBayar" class="alert alert-danger" style="display:none; margin-top:16px;">
                ⚠️ Uang bayar kurang! Kekurangan: <strong id="errKurang"></strong>
            </div>

            <div style="margin-top:24px;">
                <button type="submit" class="btn btn-success btn-full" id="btnProses" disabled>
                    Proses Transaksi →
                </button>
            </div>

        </form>
    </div>
</div>

<script>
const barangSelect = document.getElementById('barangSelect');
const inputHarga   = document.getElementById('inputHarga');
const inputDiskon  = document.getElementById('inputDiskon');
const inputBayar   = document.getElementById('inputBayar');
const previewBox   = document.getElementById('previewBox');
const btnProses    = document.getElementById('btnProses');
const errorBayar   = document.getElementById('errorBayar');
const diskonHint   = document.getElementById('diskonHint');

function fmt(n) {
    return 'Rp ' + Math.round(n).toLocaleString('id-ID');
}

// Saat barang dipilih → isi harga & diskon otomatis
barangSelect.addEventListener('change', function () {
    const opt = this.options[this.selectedIndex];
    if (this.value) {
        inputHarga.value  = opt.dataset.harga;
        const dis = parseFloat(opt.dataset.diskon) || 0;
        inputDiskon.value = dis;
        diskonHint.textContent = dis > 0 ? `🏷️ Diskon otomatis ${dis}% dari data barang` : '';
    } else {
        inputHarga.value = '';
        inputDiskon.value = 0;
        diskonHint.textContent = '';
    }
    hitung();
});

// Live kalkulasi
[inputHarga, inputDiskon, inputBayar].forEach(el => el.addEventListener('input', hitung));

function hitung() {
    const harga  = parseFloat(inputHarga.value)  || 0;
    const diskon = parseFloat(inputDiskon.value) || 0;
    const bayar  = parseFloat(inputBayar.value)  || 0;

    if (harga <= 0) {
        previewBox.classList.remove('show');
        btnProses.disabled = true;
        errorBayar.style.display = 'none';
        return;
    }

    const potongan  = (diskon / 100) * harga;
    const total     = harga - potongan;
    const kembalian = bayar - total;

    previewBox.classList.add('show');
    document.getElementById('prvHarga').textContent    = fmt(harga);
    document.getElementById('prvPotongan').textContent = diskon > 0 ? `− ${fmt(potongan)}` : '—';
    document.getElementById('rowDiskon').style.display = diskon > 0 ? 'flex' : 'none';
    document.getElementById('prvTotal').textContent    = fmt(total);

    const rowKembalian    = document.getElementById('rowKembalian');
    const prvKembalian    = document.getElementById('prvKembalian');
    const labelKembalian  = document.getElementById('labelKembalian');

    if (bayar <= 0) {
        rowKembalian.style.display = 'none';
        errorBayar.style.display   = 'none';
        btnProses.disabled = true;
    } else if (kembalian < 0) {
        // Uang kurang
        rowKembalian.style.display    = 'flex';
        rowKembalian.className        = 'preview-row kurang';
        labelKembalian.textContent    = 'Kekurangan';
        prvKembalian.textContent      = fmt(Math.abs(kembalian));
        document.getElementById('errKurang').textContent = fmt(Math.abs(kembalian));
        errorBayar.style.display      = 'flex';
        btnProses.disabled = true;
    } else {
        rowKembalian.style.display    = 'flex';
        rowKembalian.className        = 'preview-row';
        rowKembalian.style.color      = 'var(--success)';
        labelKembalian.textContent    = 'Kembalian';
        prvKembalian.textContent      = fmt(kembalian);
        errorBayar.style.display      = 'none';
        btnProses.disabled = false;
    }
}
</script>
</body>
</html>
