<?php
session_start();

if (!empty($_SESSION['email'])) {
    header('Location: index.php');
    exit;
}

require __DIR__ . '/utils/connection.php';

$loginErr = "";

if (isset($_POST['submit'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $loginErr = "Email i lozinka su obavezni!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $loginErr = "Unesite ispravan email!";
    } else {
        $stmt = $konekcija->prepare("SELECT username, email, password FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $loginErr = "Nalog sa ovom email adresom ne postoji!";
        } else {
            $user = $result->fetch_assoc();

            if (!password_verify($password, $user['password'])) {
                $loginErr = "Wrong password";
            } else {
                session_regenerate_id(true);

                $_SESSION['email'] = $user['email'];
                $_SESSION['username'] = $user['username'];

                header('Location: index.php');
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prijava</title>
    <style>
        body { font-family: Arial, sans-serif; margin:0; background:#f5f5f5; }
        header { background:#111; color:#fff; padding:14px 0; }
        .container { width: min(1000px, 92%); margin: 0 auto; }
        nav a { color:#fff; text-decoration:none; margin-right:16px; opacity:.9; }
        nav a:hover { opacity:1; }
        .card { background:#fff; margin:40px auto; padding:22px; border-radius:10px; width:min(420px, 92%); box-shadow:0 6px 20px rgba(0,0,0,.08); }
        label { display:block; margin-top:12px; margin-bottom:6px; }
        input { width:100%; padding:10px; border:1px solid #ccc; border-radius:8px; }
        button { width:100%; margin-top:16px; padding:10px; border:0; border-radius:8px; background:#111; color:#fff; cursor:pointer; }
        button:hover { background:#222; }
        .err { color:#c20000; margin-top:12px; display:block; }
        footer { padding:18px 0; color:#666; text-align:center; }
    </style>
</head>
<body>
<header>
    <div class="container">
        <strong>LAB</strong>
        <nav style="margin-top:8px;">
            <a href="index.php">Početna</a>
            <a href="login.php">Prijava</a>
        </nav>
    </div>
</header>

<main class="container">
    <div class="card">
        <h2 style="margin-top:0;">Prijava</h2>

        <form method="post" action="login.php" autocomplete="off">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES); ?>">

            <label for="password">Lozinka</label>
            <input type="password" id="password" name="password">

            <button type="submit" name="submit">Prijavi se</button>

            <?php if ($loginErr !== ""): ?>
                <span class="err"><?php echo htmlspecialchars($loginErr, ENT_QUOTES); ?></span>
            <?php endif; ?>
        </form>
    </div>
</main>

<footer>
    <div class="container">© 2026 LAB</div>
</footer>
</body>
</html>
