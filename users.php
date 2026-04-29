<?php
include "koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php"); exit;
}

$pesan = '';
$tipe  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'tambah') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $konfirmasi = $_POST['konfirmasi'];
    $role     = $_POST['role'];

    // Validasi
    if ($username === '' || $password === '' || $konfirmasi === '') {
        $pesan = "Semua field wajib diisi.";
        $tipe  = "danger";
    } elseif (strlen($username) < 3) {
        $pesan = "Username minimal 3 karakter.";
        $tipe  = "danger";
    } elseif (strlen($password) < 6) {
        $pesan = "Password minimal 6 karakter.";
        $tipe  = "danger";
    } elseif ($password !== $konfirmasi) {
        $pesan = "Password dan konfirmasi tidak cocok.";
        $tipe  = "danger";
    } elseif (!in_array($role, ['admin', 'kasir'])) {
        $pesan = "Role tidak valid.";
        $tipe  = "danger";
    } else {
        // Cek username sudah ada
        $cek_stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
        mysqli_stmt_bind_param($cek_stmt, "s", $username);
        mysqli_stmt_execute($cek_stmt);
        mysqli_stmt_store_result($cek_stmt);

        if (mysqli_stmt_num_rows($cek_stmt) > 0) {
            $pesan = "Username \"$username\" sudah digunakan. Pilih username lain.";
            $tipe  = "danger";
        } else {
            // Hash password sebelum disimpan
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            $stmt = mysqli_prepare($conn, "INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sss", $username, $hashed, $role);

            if (mysqli_stmt_execute($stmt)) {
                $pesan = "User \"$username\" berhasil ditambahkan!";
                $tipe  = "success";
            } else {
                $pesan = "Gagal menyimpan: " . mysqli_error($conn);
                $tipe  = "danger";
            }
            mysqli_stmt_close($stmt);
        }
        mysqli_stmt_close($cek_stmt);
    }
}

if (isset($_GET['hapus'])) {
    $hapus_id = (int) $_GET['hapus'];

    // Tidak boleh hapus diri sendiri
    if ($hapus_id === (int) $_SESSION['user_id']) {
        $pesan = "Tidak bisa menghapus akun yang sedang digunakan.";
        $tipe  = "danger";
    } else {
        $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $hapus_id);
        if (mysqli_stmt_execute($stmt)) {
            $pesan = "User berhasil dihapus.";
            $tipe  = "success";
        }
        mysqli_stmt_close($stmt);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'ganti_password') {
    $edit_id        = (int) $_POST['edit_id'];
    $pass_baru      = $_POST['pass_baru'];
    $pass_konfirmasi = $_POST['pass_konfirmasi'];

    if (strlen($pass_baru) < 6) {
        $pesan = "Password baru minimal 6 karakter.";
        $tipe  = "danger";
    } elseif ($pass_baru !== $pass_konfirmasi) {
        $pesan = "Password baru dan konfirmasi tidak cocok.";
        $tipe  = "danger";
    } else {
        $hashed = password_hash($pass_baru, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $hashed, $edit_id);
        if (mysqli_stmt_execute($stmt)) {
            $pesan = "Password berhasil diperbarui.";
            $tipe  = "success";
        }
        mysqli_stmt_close($stmt);
    }
}

// Ambil semua user
$data_users = mysqli_query($conn, "SELECT id, username, role, created_at FROM users ORDER BY id ASC");
$total_admin = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM users WHERE role='admin'"))[0];
$total_kasir = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM users WHERE role='kasir'"))[0];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen User — Mini Kasir</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Modal */
        .modal-overlay {
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.65);
            backdrop-filter: blur(4px);
            z-index: 200;
            display: flex; align-items: center; justify-content: center;
            padding: 24px;
            opacity: 0; pointer-events: none;
            transition: opacity 0.2s ease;
        }
        .modal-overlay.open { opacity: 1; pointer-events: all; }
        .modal {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 32px;
            width: 100%; max-width: 440px;
            box-shadow: 0 24px 64px rgba(0,0,0,0.5);
            transform: translateY(16px);
            transition: transform 0.25s ease;
        }
        .modal-overlay.open .modal { transform: translateY(0); }
        .modal-title {
            font-size: 1.1rem; font-weight: 700;
            margin-bottom: 22px; display: flex;
            align-items: center; gap: 10px;
        }
        .modal-close {
            margin-left: auto; background: none; border: none;
            color: var(--text2); cursor: pointer; font-size: 1.2rem;
            line-height: 1; padding: 4px;
        }
        .modal-close:hover { color: var(--text); }

        /* Password strength */
        .strength-bar {
            height: 4px; border-radius: 4px;
            background: var(--border); margin-top: 8px;
            overflow: hidden;
        }
        .strength-fill {
            height: 100%; width: 0%;
            border-radius: 4px;
            transition: width 0.3s ease, background 0.3s ease;
        }
        .strength-label {
            font-size: 0.72rem; margin-top: 4px; height: 14px;
        }

        /* User card list */
        .user-row {
            display: flex; align-items: center;
            padding: 14px 20px; gap: 14px;
            border-bottom: 1px solid var(--border);
            transition: background 0.15s;
        }
        .user-row:last-child { border-bottom: none; }
        .user-row:hover { background: var(--bg3); }
        .user-avatar {
            width: 38px; height: 38px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.85rem; font-weight: 700; flex-shrink: 0;
        }
        .avatar-admin { background: rgba(79,110,247,0.2); color: var(--accent); }
        .avatar-kasir { background: rgba(34,197,94,0.2); color: var(--success); }
        .user-info { flex: 1; min-width: 0; }
        .user-name { font-weight: 600; font-size: 0.9rem; color: var(--text); }
        .user-meta { font-size: 0.75rem; color: var(--text3); margin-top: 2px; }
        .user-actions { display: flex; gap: 8px; }

        /* Self indicator */
        .self-tag {
            font-size: 0.68rem; background: rgba(79,110,247,0.15);
            color: var(--accent); padding: 2px 7px; border-radius: 20px;
            font-weight: 600; margin-left: 6px;
        }

        .eye-btn {
            background: none; border: none; color: var(--text3);
            cursor: pointer; padding: 0 8px; font-size: 1rem;
            transition: color 0.15s;
        }
        .eye-btn:hover { color: var(--text); }
        .pass-wrap { position: relative; display: flex; align-items: center; }
        .pass-wrap .form-control { padding-right: 40px; }
        .pass-wrap .eye-btn { position: absolute; right: 2px; }
    </style>
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
        <a href="tampil_transaksi.php" class="nav-link">Transaksi</a>
        <a href="users.php" class="nav-link active">Users</a>
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>
</nav>

<div class="container">
    <div class="page-header flex-between">
        <div>
            <h1 class="page-title">Manajemen User</h1>
            <p class="page-subtitle">Kelola akun admin dan kasir</p>
        </div>
        <button class="btn btn-primary" onclick="openModal('modalTambah')">
            + Tambah User
        </button>
    </div>

    <!-- ALERT -->
    <?php if ($pesan): ?>
        <div class="alert alert-<?= $tipe ?>" id="alertBox">
            <?= $tipe === 'success' ? '✅' : '⚠️' ?> <?= htmlspecialchars($pesan) ?>
        </div>
    <?php endif; ?>

    <!-- STAT CARDS -->
    <div class="grid-3" style="margin-bottom:28px;">
        <div class="stat-card blue">
            <div class="stat-icon blue">👥</div>
            <div class="stat-value"><?= $total_admin + $total_kasir ?></div>
            <div class="stat-label">Total User</div>
        </div>
        <div class="stat-card blue" style="--accent:var(--accent);">
            <div class="stat-icon blue">🔑</div>
            <div class="stat-value"><?= $total_admin ?></div>
            <div class="stat-label">Admin</div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon green">💳</div>
            <div class="stat-value"><?= $total_kasir ?></div>
            <div class="stat-label">Kasir</div>
        </div>
    </div>

    <!-- DAFTAR USER -->
    <div class="card" style="padding:0; overflow:hidden;">
        <div class="card-header" style="padding:18px 22px 14px;">
            <span class="card-title">👤 Daftar User</span>
            <span class="badge badge-blue"><?= $total_admin + $total_kasir ?> akun</span>
        </div>

        <?php if (mysqli_num_rows($data_users) > 0): ?>
            <?php while ($u = mysqli_fetch_assoc($data_users)):
                $isSelf = ((int)$u['id'] === (int)$_SESSION['user_id']);
                $initial = strtoupper(substr($u['username'], 0, 2));
            ?>
            <div class="user-row">
                <div class="user-avatar <?= $u['role'] === 'admin' ? 'avatar-admin' : 'avatar-kasir' ?>">
                    <?= $initial ?>
                </div>
                <div class="user-info">
                    <div class="user-name">
                        <?= htmlspecialchars($u['username']) ?>
                        <?php if ($isSelf): ?>
                            <span class="self-tag">Anda</span>
                        <?php endif; ?>
                    </div>
                    <div class="user-meta">
                        <?= $u['role'] === 'admin' ? '🔑 Administrator' : '💳 Kasir' ?>
                        &nbsp;·&nbsp; ID: <?= $u['id'] ?>
                    </div>
                </div>
                <div class="user-actions">
                    <button class="btn btn-ghost btn-sm"
                        onclick="openGantiPassword(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username']) ?>')">
                        🔒 Ganti Password
                    </button>
                    <?php if (!$isSelf): ?>
                    <a href="users.php?hapus=<?= $u['id'] ?>"
                       class="btn btn-danger btn-sm"
                       onclick="return confirm('Hapus user \'<?= htmlspecialchars($u['username']) ?>\'?')">
                        Hapus
                    </a>
                    <?php else: ?>
                    <button class="btn btn-sm" style="opacity:0.3; cursor:not-allowed;" disabled>Hapus</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">👤</div>
                <div class="empty-text">Belum ada user terdaftar.</div>
            </div>
        <?php endif; ?>
    </div>

    <!-- INFO KEAMANAN -->
    <div class="alert alert-info" style="margin-top:24px;">
        🔐 Password disimpan menggunakan <strong>bcrypt hash</strong> — tidak ada yang bisa membaca password asli, termasuk admin.
    </div>
</div>

<!-- MODAL: TAMBAH USER -->
<div class="modal-overlay" id="modalTambah">
    <div class="modal">
        <div class="modal-title">
            ➕ Tambah User Baru
            <button class="modal-close" onclick="closeModal('modalTambah')">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="aksi" value="tambah">

            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="username" id="newUsername" class="form-control"
                       placeholder="min. 3 karakter" minlength="3" required autocomplete="off">
                <div class="form-hint" id="usernameHint"></div>
            </div>

            <div class="form-group">
                <label class="form-label">Role</label>
                <select name="role" class="form-control" required>
                    <option value="kasir">💳 Kasir</option>
                    <option value="admin">🔑 Admin</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <div class="pass-wrap">
                    <input type="password" name="password" id="newPassword" class="form-control"
                           placeholder="min. 6 karakter" required autocomplete="new-password">
                    <button type="button" class="eye-btn" onclick="togglePass('newPassword', this)">👁️</button>
                </div>
                <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                <div class="strength-label" id="strengthLabel" style="color:var(--text3);"></div>
            </div>

            <div class="form-group">
                <label class="form-label">Konfirmasi Password</label>
                <div class="pass-wrap">
                    <input type="password" name="konfirmasi" id="konfPassword" class="form-control"
                           placeholder="Ulangi password" required autocomplete="new-password">
                    <button type="button" class="eye-btn" onclick="togglePass('konfPassword', this)">👁️</button>
                </div>
                <div class="form-hint" id="matchHint"></div>
            </div>

            <div style="display:flex; gap:12px; margin-top:8px;">
                <button type="button" class="btn btn-ghost" style="flex:1;" onclick="closeModal('modalTambah')">Batal</button>
                <button type="submit" class="btn btn-primary" style="flex:1;">Simpan User</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: GANTI PASSWORD -->
<div class="modal-overlay" id="modalPassword">
    <div class="modal">
        <div class="modal-title">
            🔒 Ganti Password
            <button class="modal-close" onclick="closeModal('modalPassword')">✕</button>
        </div>
        <p style="color:var(--text2); font-size:0.85rem; margin-bottom:20px;">
            User: <strong id="editNama" style="color:var(--text);"></strong>
        </p>
        <form method="POST">
            <input type="hidden" name="aksi" value="ganti_password">
            <input type="hidden" name="edit_id" id="editId">

            <div class="form-group">
                <label class="form-label">Password Baru</label>
                <div class="pass-wrap">
                    <input type="password" name="pass_baru" id="passBaru" class="form-control"
                           placeholder="min. 6 karakter" required autocomplete="new-password">
                    <button type="button" class="eye-btn" onclick="togglePass('passBaru', this)">👁️</button>
                </div>
                <div class="strength-bar"><div class="strength-fill" id="strengthFill2"></div></div>
                <div class="strength-label" id="strengthLabel2" style="color:var(--text3);"></div>
            </div>

            <div class="form-group">
                <label class="form-label">Konfirmasi Password Baru</label>
                <div class="pass-wrap">
                    <input type="password" name="pass_konfirmasi" id="konfBaru" class="form-control"
                           placeholder="Ulangi password baru" required autocomplete="new-password">
                    <button type="button" class="eye-btn" onclick="togglePass('konfBaru', this)">👁️</button>
                </div>
                <div class="form-hint" id="matchHint2"></div>
            </div>

            <div style="display:flex; gap:12px; margin-top:8px;">
                <button type="button" class="btn btn-ghost" style="flex:1;" onclick="closeModal('modalPassword')">Batal</button>
                <button type="submit" class="btn btn-primary" style="flex:1;">Simpan Password</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) {
    document.getElementById(id).classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeModal(id) {
    document.getElementById(id).classList.remove('open');
    document.body.style.overflow = '';
}
// Tutup saat klik overlaydocument.querySelectorAll('.modal-overlay').forEach(el => {
    el.addEventListener('click', function(e) {
        if (e.target === this) closeModal(this.id);
    });
});

// Buka modal ganti password & isi data
function openGantiPassword(id, nama) {
    document.getElementById('editId').value   = id;
    document.getElementById('editNama').textContent = nama;
    document.getElementById('passBaru').value  = '';
    document.getElementById('konfBaru').value  = '';
    openModal('modalPassword');
}

function togglePass(fieldId, btn) {
    const el = document.getElementById(fieldId);
    if (el.type === 'password') { el.type = 'text'; btn.textContent = '🙈'; }
    else                        { el.type = 'password'; btn.textContent = '👁️'; }
}

function checkStrength(val, fillId, labelId) {
    const fill  = document.getElementById(fillId);
    const label = document.getElementById(labelId);
    let score = 0;
    if (val.length >= 6)  score++;
    if (val.length >= 10) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    const levels = [
        { pct:'0%',   color:'transparent', text:'' },
        { pct:'25%',  color:'var(--danger)',  text:'Lemah' },
        { pct:'50%',  color:'var(--warning)', text:'Cukup' },
        { pct:'75%',  color:'var(--accent)',  text:'Kuat' },
        { pct:'100%', color:'var(--success)', text:'Sangat Kuat' },
    ];
    const lv = val.length === 0 ? levels[0] : (score <= 1 ? levels[1] : score <= 2 ? levels[2] : score <= 3 ? levels[3] : levels[4]);
    fill.style.width = lv.pct;
    fill.style.background = lv.color;
    label.textContent = lv.text;
    label.style.color = lv.color;
}

document.getElementById('newPassword').addEventListener('input', function() {
    checkStrength(this.value, 'strengthFill', 'strengthLabel');
    checkMatch('newPassword','konfPassword','matchHint');
});
document.getElementById('konfPassword').addEventListener('input', function() {
    checkMatch('newPassword','konfPassword','matchHint');
});
document.getElementById('passBaru').addEventListener('input', function() {
    checkStrength(this.value, 'strengthFill2', 'strengthLabel2');
    checkMatch('passBaru','konfBaru','matchHint2');
});
document.getElementById('konfBaru').addEventListener('input', function() {
    checkMatch('passBaru','konfBaru','matchHint2');
});

function checkMatch(p1Id, p2Id, hintId) {
    const p1 = document.getElementById(p1Id).value;
    const p2 = document.getElementById(p2Id).value;
    const hint = document.getElementById(hintId);
    if (p2.length === 0) { hint.textContent = ''; return; }
    if (p1 === p2) {
        hint.textContent = '✅ Password cocok';
        hint.style.color = 'var(--success)';
    } else {
        hint.textContent = '❌ Password tidak cocok';
        hint.style.color = 'var(--danger)';
    }
}

// Auto-buka modal jika ada error validasi dari server (POST)
<?php if ($tipe === 'danger' && $_SERVER['REQUEST_METHOD'] === 'POST'): ?>
    <?php if (isset($_POST['aksi']) && $_POST['aksi'] === 'tambah'): ?>
        window.addEventListener('DOMContentLoaded', () => openModal('modalTambah'));
    <?php elseif (isset($_POST['aksi']) && $_POST['aksi'] === 'ganti_password'): ?>
        window.addEventListener('DOMContentLoaded', () => {
            openGantiPassword(<?= (int)$_POST['edit_id'] ?>, '<?= htmlspecialchars(mysqli_fetch_assoc(mysqli_query($conn, "SELECT username FROM users WHERE id=".(int)$_POST['edit_id']))['username'] ?? '') ?>');
        });
    <?php endif; ?>
<?php endif; ?>

// Auto-dismiss alert setelah 4 detik
const alertBox = document.getElementById('alertBox');
if (alertBox) setTimeout(() => alertBox.style.display = 'none', 4000);
</script>
</body>
</html>