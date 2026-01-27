<?php
session_start();
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>StockMaster – Bejelentkezés</title>
    <link rel="stylesheet" href="login_style.css">
</head>

<body class="login-page">

    <div class="login-background"></div>

    <div class="login-wrapper">
        <div class="login-card">

            <div class="login-logo">StockMaster</div>
            <h1 class="login-title">Bejelentkezés</h1>

            <?php if (isset($_SESSION["error"])): ?>
                <div class="alert error">
                    <?php 
                        echo htmlspecialchars($_SESSION["error"]); 
                        unset($_SESSION["error"]); 
                    ?>
                </div>
            <?php endif; ?>

            <form action="login_action.php" method="POST" class="login-form">

                <div class="form-group">
                    <label for="username">Felhasználónév</label>
                    <input type="text" id="username" name="username" placeholder="Felhasználónév" required>
                </div>

                <div class="form-group">
                    <label for="password">Jelszó</label>
                    <input type="password" id="password" name="password" placeholder="Jelszó" required>
                </div>

                <div class="form-footer">
                    <button type="submit" class="btn btn-primary">Belépés</button>
                    <a href="register.php" class="link-secondary">Nincs még fiókod?</a>
                </div>

            </form>
        </div>

        <div class="login-footer-text">
            © <?php echo date("Y"); ?> StockMaster • Portfóliókezelő
        </div>
    </div>

</body>
</html>
