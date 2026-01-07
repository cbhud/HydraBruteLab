<?php
require __DIR__ . '/vendor/autoload.php';
session_start();

if (!empty($_SESSION['email'])) {
    header('Location: index.php');
    exit;
}

require __DIR__ . '/utils/connection.php';

$loginErr = "";

// Podesavanja lockout-a
$MAX_FAILS = 5;          // koliko fail-ova
$WINDOW_SECONDS = 600;   // u kom prozoru (10 min)
$LOCK_SECONDS = 900;     // koliko traje lock (15 min)

function throttle_get(mysqli $db, string $email): ?array {
    $stmt = $db->prepare("SELECT email, fails, first_fail_at, locked_until FROM login_throttle WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();
    return ($res->num_rows === 1) ? $res->fetch_assoc() : null;
}

function throttle_fail(mysqli $db, string $email, int $now, int $MAX_FAILS, int $WINDOW_SECONDS, int $LOCK_SECONDS): void {
    $row = throttle_get($db, $email);

    if ($row === null) {
        $fails = 1;
        $first = $now;
        $locked_until = 0;

        if ($fails >= $MAX_FAILS) {
            $locked_until = $now + $LOCK_SECONDS;
        }

        $stmt = $db->prepare("INSERT INTO login_throttle (email, fails, first_fail_at, locked_until) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("siii", $email, $fails, $first, $locked_until);
        $stmt->execute();
        return;
    }

    $fails = (int)$row['fails'];
    $first = (int)$row['first_fail_at'];
    $locked_until = (int)$row['locked_until'];

    // Ako je prozor istekao, resetuj brojanje
    if ($first === 0 || ($now - $first) > $WINDOW_SECONDS) {
        $fails = 1;
        $first = $now;
        $locked_until = 0;
    } else {
        $fails += 1;
    }

    if ($fails >= $MAX_FAILS) {
        $locked_until = $now + $LOCK_SECONDS;
    }

    $stmt = $db->prepare("UPDATE login_throttle SET fails = ?, first_fail_at = ?, locked_until = ? WHERE email = ?");
    $stmt->bind_param("iiis", $fails, $first, $locked_until, $email);
    $stmt->execute();
}

function throttle_clear(mysqli $db, string $email): void {
    $stmt = $db->prepare("DELETE FROM login_throttle WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
}

if (isset($_POST['submit'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $loginErr = "Email i lozinka su obavezni!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $loginErr = "Unesite ispravan email!";
    } else {
        $now = time();

        // 1) Provjera lockout-a
        $t = throttle_get($konekcija, $email);
        if ($t !== null && (int)$t['locked_until'] > $now) {
            $remaining = (int)$t['locked_until'] - $now;
            $loginErr = "Account temporary locked";
        } else {
            // 2) Normalna login provjera
            $stmt = $konekcija->prepare("SELECT username, email, password, mfa_enabled FROM users WHERE email = ? LIMIT 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                // Fail se broji i za nepostojeći email
                throttle_fail($konekcija, $email, $now, $MAX_FAILS, $WINDOW_SECONDS, $LOCK_SECONDS);
                $loginErr = "Wrong password";
            } else {
                $user = $result->fetch_assoc();

                if (!password_verify($password, $user['password'])) {
                    throttle_fail($konekcija, $email, $now, $MAX_FAILS, $WINDOW_SECONDS, $LOCK_SECONDS);
                    $loginErr = "Wrong password";
                } else {
                    // Success -> očisti throttle za taj email
                    throttle_clear($konekcija, $email);

                    session_regenerate_id(true);
                    $_SESSION['preauth_email'] = $user['email'];
                    $_SESSION['preauth_username'] = $user['username'];

                    if ((int)$user['mfa_enabled'] === 1) {
                    header('Location: mfa_verify.php');
                    } else {
                    header('Location: mfa_setup.php');
                }
                    exit;
                }
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
