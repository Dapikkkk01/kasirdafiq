<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Mini Kasir</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="login-wrap">
    <div class="login-card">
        <div class="login-logo">🛒</div>
        <h1 class="login-title">Mini Kasir</h1>
        <p class="login-sub">Masuk untuk melanjutkan</p>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">⚠️ Username atau password salah.</div>
        <?php endif; ?>
        <?php if (isset($_GET['logout'])): ?>
            <div class="alert alert-success">✅ Berhasil logout.</div>
        <?php endif; ?>

        <form method="POST" action="proses_login.php">
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" placeholder="Masukkan username" required autofocus>
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-primary btn-full" style="margin-top:8px;">
                Masuk →
            </button>
        </form>
    </div>
</div>
</body>
</html>
