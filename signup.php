<?php
session_start();
include 'config.php';

if (isset($_POST['submit'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        echo "<p style='color:red;'>A jelszavak nem egyeznek!</p>";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $check_sql = "SELECT * FROM Felhasznalo WHERE felhasznalonev = :username OR email = :email";
        $stmt_check = oci_parse($conn, $check_sql);
        oci_bind_by_name($stmt_check, ":username", $username);
        oci_bind_by_name($stmt_check, ":email", $email);

        oci_execute($stmt_check);

        if (oci_fetch($stmt_check)) {
            echo "<p style='color:red;'>A felhasználónév vagy email már létezik!</p>";
        } else {
            $role = 'user';

            $sql = "INSERT INTO Felhasznalo (felhasznalonev, email, jelszo, szerepkor) 
                    VALUES (:username, :email, :password, :role)";
            $stmt = oci_parse($conn, $sql);

            oci_bind_by_name($stmt, ":username", $username);
            oci_bind_by_name($stmt, ":email", $email);
            oci_bind_by_name($stmt, ":password", $hashed_password);
            oci_bind_by_name($stmt, ":role", $role);

            if (oci_execute($stmt)) {
                $_SESSION['success_message'] = "Sikeres regisztráció!";
                header('Location: index.php');
                exit;
            } else {
                $e = oci_error($stmt);
                echo "<p style='color:red;'>Hiba: " . htmlentities($e['message']) . "</p>";
            }

            oci_free_statement($stmt);
        }

        oci_free_statement($stmt_check);
    }
}

oci_close($conn);
?>

<form method="POST" action="">
    <input type="text" name="username" placeholder="Felhasználónév" required><br>
    <input type="email" name="email" placeholder="Email" required><br>
    <input type="password" name="password" placeholder="Jelszó" required><br>
    <input type="password" name="confirm_password" placeholder="Jelszó újra" required><br>
    <button type="submit" name="submit">Regisztrálok</button>
</form>