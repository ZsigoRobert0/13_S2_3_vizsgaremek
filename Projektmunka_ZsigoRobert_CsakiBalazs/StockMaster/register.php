<?php
session_start();
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>StockMaster – Regisztráció</title>
    <link rel="stylesheet" href="register_style.css">
</head>

<body class="login-page">

    <div class="login-background"></div>

    <div class="login-wrapper">

            <div class="register-card">
                <div class="logo">StockMaster</div>
                <h2 class="register-title">Regisztráció</h2>

            <?php if (isset($_SESSION["error"])): ?>
                <div class="alert error">
                    <?php 
                        echo htmlspecialchars($_SESSION["error"]); 
                        unset($_SESSION["error"]); 
                    ?>
                </div>
            <?php endif; ?>

            <form action="process_register.php" method="POST" class="login-form">

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
             © <?php echo date("Y"); ?> StockMaster • Portfóliókezelő
        </div>

    </div>

</body>
</html>
