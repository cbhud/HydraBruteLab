<?php


session_start();

require __DIR__ . '/utils/connection.php';
require __DIR__ . '/vendor/autoload.php';

use RobThree\Auth\TwoFactorAuth;
use RobThree\Auth\Providers\Qr\QRServerProvider;

$email = $_SESSION['preauth_email'] ?? '';
$username = $_SESSION['preauth_username'] ?? '';

if ($email === '' || $username === '') {
    header('Location: register.php');
    exit;
}

$qrProvider = new QRServerProvider();
$tfa = new TwoFactorAuth($qrProvider, 'LAB');

// Secret čuvamo u sesiji dok korisnik ne potvrdi kod
if (empty($_SESSION['mfa_setup_secret'])) {
    $_SESSION['mfa_setup_secret'] = $tfa->createSecret();
}
$secret = $_SESSION['mfa_setup_secret'];

$err = "";

// QR (ako failuje, i dalje prikazujemo secret)
$qrDataUri = null;
try {
    $label = "LAB:" . $email;
    $qrDataUri = $tfa->getQRCodeImageAsDataUri($label, $secret);
} catch (Throwable $e) {
    $qrDataUri = null;
}

if (isset($_POST['confirm'])) {
    $code = trim($_POST['code'] ?? '');

    if (!preg_match('/^\d{6}$/', $code)) {
        $err = "Unesi 6-cifreni kod iz aplikacije.";
    } else {
        if ($tfa->verifyCode($secret, $code, 1)) {
            // Upis u bazu + enable
            $stmt = $konekcija->prepare("UPDATE users SET mfa_secret = ?, mfa_enabled = 1 WHERE email = ? LIMIT 1");
            $stmt->bind_param("ss", $secret, $email);
            $stmt->execute();

            // Sada tek postaje ulogovan
            session_regenerate_id(true);
            $_SESSION['email'] = $email;
            $_SESSION['username'] = $username;

            unset($_SESSION['preauth_email'], $_SESSION['preauth_username'], $_SESSION['mfa_setup_secret']);

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
    <title>2FA Setup</title>
    <style>
        body { font-family: Arial, sans-serif; margin:0; background:#f5f5f5; }
        header { background:#111; color:#fff; padding:14px 0; }
        .container { width: min(1000px, 92%); margin: 0 auto; }
        .card { background:#fff; margin:40px auto; padding:22px; border-radius:10px; width:min(520px, 92%); box-shadow:0 6px 20px rgba(0,0,0,.08); }
        label { display:block; margin-top:12px; margin-bottom:6px; }
        input { width:100%; padding:10px; border:1px solid #ccc; border-radius:8px; }
        button { width:100%; margin-top:16px; padding:10px; border:0; border-radius:8px; background:#111; color:#fff; cursor:pointer; }
        button:hover { background:#222; }
        .err { color:#c20000; margin-top:12px; display:block; }
        code { background:#f0f0f0; padding:2px 6px; border-radius:6px; }
        img { max-width: 220px; display:block; margin: 12px 0; }
    </style>
</head>
<body>
<header>
    <div class="container"><strong>LAB</strong></div>
</header>

<main class="container">
    <div class="card">
        <h2 style="margin-top:0;">Podesi 2FA</h2>
        <p>Skeniraj QR kod u Authenticator aplikaciji, ili unesi secret ručno.</p>

        <?php if ($qrDataUri): ?>
            <img src="<?php echo htmlspecialchars($qrDataUri, ENT_QUOTES); ?>" alt="2FA QR">
        <?php endif; ?>

        <p>Secret: <code><?php echo htmlspecialchars($secret, ENT_QUOTES); ?></code></p>

        <form method="post" action="mfa_setup.php" autocomplete="off">
            <label for="code">6-cifreni kod</label>
            <input type="text" id="code" name="code" inputmode="numeric" placeholder="123456">

            <button type="submit" name="confirm">Aktiviraj 2FA</button>

            <?php if ($err !== ""): ?>
                <span class="err"><?php echo htmlspecialchars($err, ENT_QUOTES); ?></span>
            <?php endif; ?>
        </form>
    </div>
</main>
</body>
</html>
