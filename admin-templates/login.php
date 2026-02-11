<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — CDEM Admin</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/png" href="/img/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/admin.css">
</head>
<body class="admin-login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-box__header">
                <img src="/img/logo.png" alt="CDEM Solutions" width="48" height="48">
                <h1>CDEM<strong>Admin</strong></h1>
            </div>

            <?php foreach (get_flashes() as $flash): ?>
            <div class="flash flash--<?= htmlspecialchars($flash['type']) ?>">
                <?= htmlspecialchars($flash['message']) ?>
            </div>
            <?php endforeach; ?>

            <form method="POST" action="/admin/login/">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required autofocus autocomplete="username">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn btn--primary btn--full">Sign In</button>
            </form>
        </div>
    </div>
</body>
</html>
