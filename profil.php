<?php
include 'config.php';
include 'menu.php';

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

$username = $_SESSION['username'];
$sql = "SELECT FELHASZNALONEV, EMAIL FROM Felhasznalo WHERE FELHASZNALONEV = :username";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ":username", $username);
oci_execute($stmt);
$row = oci_fetch_assoc($stmt);

if ($row) {
    $current_email = $row['EMAIL'];
} else {
    echo "Hiba történt a felhasználói adatok betöltésekor!";
    exit;
}

// Ha a felhasználó frissíteni akarja az adatokat
if (isset($_POST['update'])) {
    $new_email = trim($_POST['email']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Ellenőrizzük, hogy a jelszavak megegyeznek-e
    if ($new_password !== $confirm_password) {
        echo "<p styles='color:red;'>A jelszavak nem egyeznek!</p>";
    } else {
        // Ha van új jelszó, akkor hash-eljük azt
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $sql_update = "UPDATE Felhasznalo SET EMAIL = :email, JELSZO = :password WHERE FELHASZNALONEV = :username";
            $stmt_update = oci_parse($conn, $sql_update);
            oci_bind_by_name($stmt_update, ":email", $new_email);
            oci_bind_by_name($stmt_update, ":password", $hashed_password);
        } else {
            // Ha nincs új jelszó, akkor csak az emailt frissítjük
            $sql_update = "UPDATE Felhasznalo SET EMAIL = :email WHERE FELHASZNALONEV = :username";
            $stmt_update = oci_parse($conn, $sql_update);
            oci_bind_by_name($stmt_update, ":email", $new_email);
        }

        oci_bind_by_name($stmt_update, ":username", $username);
        if (oci_execute($stmt_update)) {
            echo "<p styles='color:green;'>Adatok frissítve!</p>";
        } else {
            $e = oci_error($stmt_update);
            echo "<p styles='color:red;'>Hiba történt a frissítés során: " . htmlentities($e['message']) . "</p>";
        }

        // Ügyelj arra, hogy a statementet mindig felszabadítsd
        oci_free_statement($stmt_update);
    }
}

// Bezárjuk a statement-et
oci_free_statement($stmt);
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
    <h2>Profilom</h2>
    <form method="POST" action="">
        <div class="form-group">
            <label for="email">Új email:</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($current_email); ?>" required>
        </div>

        <div class="form-group">
            <label for="new_password">Új jelszó:</label>
            <input type="password" name="new_password" placeholder="Új jelszó (nem kötelező)">
        </div>

        <div class="form-group">
            <label for="confirm_password">Jelszó újra:</label>
            <input type="password" name="confirm_password" placeholder="Új jelszó újra">
        </div>

        <button type="submit" name="update" class="btn-submit">Frissítés</button>
    </form>
</div>
</body>
</html>
