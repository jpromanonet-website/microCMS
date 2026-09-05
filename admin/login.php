<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';

use MicroCMS\Auth;

if (Auth::check()) {
    header('Location: index.php');
    exit;
}

$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $user = trim((string) ($_POST['username'] ?? ''));
    $pass = (string) ($_POST['password'] ?? '');
    if (Auth::attempt($user, $pass)) {
        header('Location: index.php');
        exit;
    }
    $error = 'Invalid username or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="color-scheme" content="light dark" />
    <title>Login — microCMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="assets/admin.css" />
    <script>
      (function () {
        try {
          var t = localStorage.getItem('jpr-theme') || 'light';
          document.documentElement.setAttribute('data-theme', t);
        } catch (e) {
          document.documentElement.setAttribute('data-theme', 'light');
        }
      })();
    </script>
</head>
<body>
<div class="login-page">
    <button type="button" class="icon-btn theme-toggle" id="theme-toggle" aria-label="Switch theme">
        <svg class="theme-toggle__moon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M21 14.3A8.5 8.5 0 0 1 9.7 3 7 7 0 1 0 21 14.3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
        </svg>
        <svg class="theme-toggle__sun" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="1.8"/>
            <path d="M12 2v2.2M12 19.8V22M4.2 4.2l1.6 1.6M18.2 18.2l1.6 1.6M2 12h2.2M19.8 12H22M4.2 19.8l1.6-1.6M18.2 5.8l1.6-1.6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
        </svg>
    </button>
    <form class="login-card" method="post" action="">
        <h1>microCMS</h1>
        <p>Sign in to manage site content.</p>
        <?php if ($error !== ''): ?>
            <div class="flash flash--error"><?= cms_e($error) ?></div>
        <?php endif; ?>
        <div class="form-grid" style="grid-template-columns:1fr">
            <label>Username
                <input type="text" name="username" autocomplete="username" required autofocus />
            </label>
            <label>Password
                <input type="password" name="password" autocomplete="current-password" required />
            </label>
        </div>
        <div class="actions">
            <button class="btn btn--primary" type="submit">Sign in</button>
        </div>
    </form>
</div>
<script src="assets/admin.js"></script>
</body>
</html>
