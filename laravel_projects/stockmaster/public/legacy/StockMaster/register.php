<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>StockMaster – Regisztráció</title>
    <link rel="stylesheet" href="register_style.css">
</head>

<body class="login-page">

    <!-- Háttér effektek -->
    <div class="login-background"></div>

    <div class="login-wrapper">

        <div class="register-card">
            <div class="logo">StockMaster</div>
            <h2 class="register-title">Regisztráció</h2>

            <!-- PHP hiba kiírás -->
            <?php if (!empty($_SESSION['error'])): ?>
                <div class="alert error">
                    <?php
                        echo htmlspecialchars((string)$_SESSION['error'], ENT_QUOTES, 'UTF-8');
                        unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>

            <!-- Regisztrációs űrlap -->
            <form action="register_action.php" method="POST" class="login-form" id="registerForm">

                <div class="form-group">
                    <label for="username">Felhasználónév</label>
                    <input type="text" id="username" name="username" placeholder="Felhasználónév" required>
                </div>

                <div class="form-group">
                    <label for="email">Email cím</label>
                    <input type="email" id="email" name="email" placeholder="Email" required>
                </div>

                <div class="form-group">
                    <label for="password">Jelszó</label>
                    <input type="password" id="password" name="password" placeholder="Jelszó" required>
                </div>

                <div class="form-group">
                    <label for="password2">Jelszó megerősítése</label>
                    <input type="password" id="password2" name="password2" placeholder="Jelszó újra" required>
                </div>

                <div class="form-footer">
                    <button type="submit" class="btn-register">Regisztráció</button>
                    <a href="login.php" class="register-link">Van már fiókod?</a>
                </div>

            </form>
        </div>

        <div class="register-footer">
            © <?php echo date('Y'); ?> StockMaster • Portfóliókezelő
        </div>

    </div>

<script>

document.getElementById('registerForm').addEventListener('submit', function(e) {
    const p1 = document.getElementById('password').value;
    const p2 = document.getElementById('password2').value;

    if (p1 !== p2) {
        e.preventDefault();
        alert("A két jelszó nem egyezik!");
    }

    if (p1.length < 6) {
        e.preventDefault();
        alert("A jelszónak legalább 6 karakter hosszúnak kell lennie!");
    }
});
</script>

</body>
</html>
