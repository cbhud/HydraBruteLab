<?php

session_start();

require __DIR__ . '/utils/connection.php';
require __DIR__ . '/vendor/autoload.php';

use RobThree\Auth\TwoFactorAuth;
use RobThree\Auth\Providers\Qr\QRServerProvider;


$email = $_SESSION['preauth_email'] ?? '';
$username = $_SESSION['preauth_username'] ?? '';

if ($email === '' || $username === '') {
    header('Location: login.php');
    exit;
}

$stmt = $konekcija->prepare("SELECT mfa_enabled, mfa_secret FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    header('Location: login.php');
    exit;
}

$row = $res->fetch_assoc();
if ((int)$row['mfa_enabled'] !== 1 || empty($row['mfa_secret'])) {
    header('Location: mfa_setup.php');
    exit;
}

$secret = $row['mfa_secret'];
$qrProvider = new QRServerProvider();
$tfa = new TwoFactorAuth($qrProvider, 'LAB');

$err = "";

if (isset($_POST['verify'])) {
    $code = trim($_POST['code'] ?? '');

    if (!preg_match('/^\d{6}$/', $code)) {
        $err = "Unesi 6-cifreni kod.";
    } else {
        if ($tfa->verifyCode($secret, $code, 1)) {
            session_regenerate_id(true);
            $_SESSION['email'] = $email;
            $_SESSION['username'] = $username;

            unset($_SESSION['preauth_email'], $_SESSION['preauth_username']);

            header('Location: index.php');
            exit;
        } else {
            $err = "Pogrešan kod. Probaj ponovo.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2FA Provjera</title>
    <style>
        body { font-family: Arial, sans-serif; margin:0; background:#f5f5f5; }
        header { background:#111; color:#fff; padding:14px 0; }
        .container { width: min(1000px, 92%); margin: 0 auto; }
        .card { background:#fff; margin:40px auto; padding:22px; border-radius:10px; width:min(420px, 92%); box-shadow:0 6px 20px rgba(0,0,0,.08); }
        label { display:block; margin-top:12px; margin-bottom:6px; }
        input { width:100%; padding:10px; border:1px solid #ccc; border-radius:8px; }
        button { width:100%; margin-top:16px; padding:10px; border:0; border-radius:8px; background:#111; color:#fff; cursor:pointer; }
        button:hover { background:#222; }
        .err { color:#c20000; margin-top:12px; display:block; }
    </style>
</head>
<body>
<header><div class="container"><strong>LAB</strong></div></header>

<main class="container">
    <div class="card">
        <h2 style="margin-top:0;">Unesi 2FA kod</h2>

        <form method="post" action="mfa_verify.php" autocomplete="off">
            <label for="code">6-cifreni kod</label>
            <input type="text" id="code" name="code" inputmode="numeric" placeholder="123456">
            <button type="submit" name="verify">Potvrdi</button>

            <?php if ($err !== ""): ?>
                <span class="err"><?php echo htmlspecialchars($err, ENT_QUOTES); ?></span>
            <?php endif; ?>
        </form>
    </div>
</main>
</body>
</html>
