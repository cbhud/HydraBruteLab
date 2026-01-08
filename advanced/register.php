<?php
session_start();

if (!empty($_SESSION['email'])) {
    header('Location: index.php');
    exit;
}

require __DIR__ . '/utils/connection.php';

$err = "";
$ok  = "";

if (isset($_POST['register'])) {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $pass1    = $_POST['password'] ?? '';

    // Backend Validation Logic
    $hasUpperCase = preg_match('@[A-Z]@', $pass1);
    $hasSpecialChar = preg_match('@[^\w]@', $pass1); // Matches any non-word character (symbol)

    if ($username === '' || $email === '' || $pass1 === '') {
        $err = "Sva polja su obavezna!";
    } elseif (strlen($username) < 3) {
        $err = "Username mora imati bar 3 karaktera.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = "Unesi ispravan email.";
    } elseif (strlen($pass1) < 8 || !$hasUpperCase || !$hasSpecialChar) {
        $err = "Lozinka mora imati 8 karaktera, 1 veliko slovo i 1 simbol.";
    } else {
        // Provjeri da li email već postoji
        $stmt = $konekcija->prepare("SELECT 1 FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows > 0) {
            $err = "Email je već registrovan.";
        } else {
            $hash = password_hash($pass1, PASSWORD_DEFAULT);

            $stmt2 = $konekcija->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt2->bind_param("sss", $username, $email, $hash);
            $stmt2->execute();

            session_regenerate_id(true);
            $_SESSION['preauth_email'] = $email;
            $_SESSION['preauth_username'] = $username;
            header('Location: mfa_setup.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registracija</title>
    <style>
        body { font-family: Arial, sans-serif; margin:0; background:#f5f5f5; }
        header { background:#111; color:#fff; padding:14px 0; }
        .container { width: min(1000px, 92%); margin: 0 auto; }
        nav a { color:#fff; text-decoration:none; margin-right:16px; opacity:.9; }
        nav a:hover { opacity:1; }
        .card { background:#fff; margin:40px auto; padding:22px; border-radius:10px; width:min(420px, 92%); box-shadow:0 6px 20px rgba(0,0,0,.08); }
        label { display:block; margin-top:12px; margin-bottom:6px; }
        input { width:100%; padding:10px; border:1px solid #ccc; border-radius:8px; box-sizing: border-box; }
        button { width:100%; margin-top:16px; padding:10px; border:0; border-radius:8px; background:#111; color:#fff; cursor:pointer; }
        button:hover { background:#222; }
        .err { color:#c20000; margin-top:12px; display:block; }
        .ok { color: #0a7a2f; margin-top:12px; display:block; }
        .small { margin-top:12px; color:#555; font-size:14px; }

        /* Checklist Styles */
        .checklist { margin-top: 10px; font-size: 13px; color: #777; }
        .check-item { margin-bottom: 4px; display: flex; align-items: center; }
        .check-item.valid { color: #0a7a2f; font-weight: bold; }
        .indicator { margin-right: 8px; }
    </style>
</head>
<body>
<header>
    <div class="container">
        <strong>LAB</strong>
        <nav style="margin-top:8px;">
            <a href="index.php">Početna</a>
            <a href="login.php">Prijava</a>
            <a href="register.php">Registracija</a>
        </nav>
    </div>
</header>

<main class="container">
    <div class="card">
        <h2 style="margin-top:0;">Registracija</h2>

        <form method="post" action="register.php" autocomplete="off">
            <label for="username">Username</label>
            <input type="text" id="username" name="username"
                   value="<?php echo htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES); ?>">

            <label for="email">Email</label>
            <input type="email" id="email" name="email"
                   value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES); ?>">

            <label for="password">Lozinka</label>
            <input type="password" id="password" name="password">

            <div class="checklist">
                <div id="check-len" class="check-item"><span class="indicator">○</span> Min. 8 karaktera</div>
                <div id="check-upper" class="check-item"><span class="indicator">○</span> 1 Veliko slovo</div>
                <div id="check-sym" class="check-item"><span class="indicator">○</span> 1 Simbol</div>
            </div>

            <button type="submit" name="register">Kreiraj nalog</button>

            <?php if ($err !== ""): ?>
                <span class="err"><?php echo htmlspecialchars($err, ENT_QUOTES); ?></span>
            <?php endif; ?>
            <?php if ($ok !== ""): ?>
                <span class="ok"><?php echo htmlspecialchars($ok, ENT_QUOTES); ?></span>
            <?php endif; ?>

            <div class="small">Već imaš nalog? <a href="login.php">Prijavi se</a></div>
        </form>
    </div>
</main>

<script>
    const passwordInput = document.getElementById('password');
    const checkLen = document.getElementById('check-len');
    const checkUpper = document.getElementById('check-upper');
    const checkSym = document.getElementById('check-sym');

    passwordInput.addEventListener('input', () => {
        const val = passwordInput.value;

        // Condition checks
        const isLenValid = val.length >= 8;
        const isUpperValid = /[A-Z]/.test(val);
        const isSymValid = /[^A-Za-z0-9]/.test(val);

        updateUI(checkLen, isLenValid);
        updateUI(checkUpper, isUpperValid);
        updateUI(checkSym, isSymValid);
    });

    function updateUI(element, isValid) {
        if (isValid) {
            element.classList.add('valid');
            element.querySelector('.indicator').innerText = '✓';
        } else {
            element.classList.remove('valid');
            element.querySelector('.indicator').innerText = '○';
        }
    }
</script>
</body>
</html>