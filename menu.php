<?php
session_start();
?>


<head>
    <link rel="stylesheet" href="styles/menu.css">
    <title></title>
</head>
<nav class="menu">
    <h1>WWWebhosting</h1>
    <ul>
        <li><a href="index.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'class="active"' : ''; ?>>Főoldal</a></li>
        <li><a href="webtarhely.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'webtarhely.php') ? 'class="active"' : ''; ?>>Webtárhely</a></li>
        <li><a href="domain.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'domain.php') ? 'class="active"' : ''; ?>>Domain</a></li>
        <?php if (isset($_SESSION['username'])): ?>
            <li><a href="profil.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'profil.php') ? 'class="active"' : ''; ?>>Profil</a></li>
            <li><a href="reklam.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'reklam.php') ? 'class="active"' : ''; ?>>Reklám</a></li>
            <li><a href="logout.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'logout.php') ? 'class="active"' : ''; ?>>Kijelentkezés</a></li>
        <?php else: ?>
            <li><a href="login.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'login.php') ? 'class="active"' : ''; ?>>Bejelentkezés</a></li>
            <li><a href="signup.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'signup.php') ? 'class="active"' : ''; ?>>Regisztráció</a></li>
        <?php endif; ?>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') { ?>
            <li><a href="admin.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'admin.php') ? 'class="active"' : ''; ?>>Admin</a></li>
        <?php } ?>
    </ul>
</nav>
