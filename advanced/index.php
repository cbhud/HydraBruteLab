<?php
session_start();

if (empty($_SESSION['email'])) {
    header('Location: login.php');
    exit;
}

$username = $_SESSION['username'] ?? 'Korisnik';
$email = $_SESSION['email'];
?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Početna</title>
    <style>
        body { font-family: Arial, sans-serif; margin:0; background:#f5f5f5; }
        header { background:#111; color:#fff; padding:14px 0; }
        .container { width: min(1000px, 92%); margin: 0 auto; }
        nav a { color:#fff; text-decoration:none; margin-right:16px; opacity:.9; }
        nav a:hover { opacity:1; }
        .card { background:#fff; margin:40px auto; padding:22px; border-radius:10px; width:min(700px, 92%); box-shadow:0 6px 20px rgba(0,0,0,.08); }
        .btn { display:inline-block; padding:10px 12px; border-radius:8px; background:#111; color:#fff; text-decoration:none; }
        .btn:hover { background:#222; }
        footer { padding:18px 0; color:#666; text-align:center; }
    </style>
</head>
<body>
<header>
    <div class="container">
        <strong>LAB</strong>
        <nav style="margin-top:8px;">
            <a href="index.php">Početna</a>
            <a href="logout.php">Odjava</a>
        </nav>
    </div>
</header>

<main class="container">
    <div class="card">
        <h2 style="margin-top:0;">Dobrodošao, <?php echo htmlspecialchars($username, ENT_QUOTES); ?>!</h2>
        <p>Ulogovan si kao: <strong><?php echo htmlspecialchars($email, ENT_QUOTES); ?></strong></p>

        <p>Ovdje možeš dodati sadržaj lab stranice (materijale, zadatke, linkove, itd.).</p>

        <a class="btn" href="logout.php">Odjavi se</a>
    </div>
</main>

<footer>
    <div class="container">© 2026 LAB</div>
</footer>
</body>
</html>
