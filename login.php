<?php
session_start();
include 'config.php';

if(isset($_POST['login'])){
    $username = $_POST['username'];
    $password = $_POST['password'];
    $sql = "SELECT jelszo FROM Felhasznalo WHERE felhasznalonev = :username";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ":username", $username);

    oci_execute($stmt);

    if (oci_fetch($stmt)) {
        $hashed_password_from_db = oci_result($stmt, 'JELSZO');

        if (password_verify($password, $hashed_password_from_db)) {
            $_SESSION['username'] = $username;
            $_SESSION['success_message'] = "Sikeres bejelentkezés!";
            header('Location: index.php');
            exit;
        } else {
            echo "<p style='color:red;'>Hibás jelszó!</p>";
        }
    } else {
        echo "<p style='color:red;'>Nincs ilyen felhasználó!</p>";
    }

    oci_free_statement($stmt);
}

oci_close($conn);
?>

<form method="POST" action="">
    <input type="text" name="username" placeholder="Felhasználónév" required>
    <input type="password" name="password" placeholder="Jelszó" required>
    <button type="submit" name="login">Bejelentkezés</button>
</form>