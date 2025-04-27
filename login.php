<?php
include 'config.php';
include 'menu.php';

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT FELHASZNALONEV, FELHASZNALO_ID, JELSZO, SZEREPKOR FROM Felhasznalo WHERE felhasznalonev = :username";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ":username", $username);

    oci_execute($stmt);
    $row = oci_fetch_assoc($stmt);

    if ($row) {
        $hashed_password_from_db = $row['JELSZO'];

        if (password_verify($password, $hashed_password_from_db)) {
            $_SESSION['username'] = $row['FELHASZNALONEV'];
            $_SESSION['user_id'] = $row['FELHASZNALO_ID'];
            $_SESSION['role'] = $row['SZEREPKOR'];
            $_SESSION['success_message'] = "Sikeres bejelentkezés!";
            header('Location: index.php');
            exit;
        } else {
            echo "<p class='error-message'>Hibás jelszó!</p>";
        }
    } else {
        echo "<p class='error-message'>Nincs ilyen felhasználó!</p>";
    }

    oci_free_statement($stmt);
}

oci_close($conn);
?>


<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bejelentkezés</title>
    <link rel="stylesheet" href="styles/style.css">
</head>
<body>

<div class="login-container">
    <h2>Bejelentkezés</h2>
    <form method="POST" action="">
        <div class="form-group">
            <input type="text" name="username" placeholder="Felhasználónév" required>
        </div>
        <div class="form-group">
            <input type="password" name="password" placeholder="Jelszó" required>
        </div>
        <button type="submit" name="login" class="btn-submit">Bejelentkezés</button>
    </form>
    <p>Ha még nincs fiókod, <a href="signup.php">regisztrálj itt!</a></p>
</div>

</body>
</html>
