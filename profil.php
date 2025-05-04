<?php
include 'config.php';
include 'menu.php';

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

$username = $_SESSION['username'];
$sql = "SELECT FELHASZNALONEV, EMAIL, FELHASZNALO_ID FROM Felhasznalo WHERE FELHASZNALONEV = :username";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ":username", $username);
oci_execute($stmt);
$row = oci_fetch_assoc($stmt);

if ($row) {
    $current_email = $row['EMAIL'];
    $felhasznalo_id = $row['FELHASZNALO_ID'];
} else {
    echo "Hiba történt a felhasználói adatok betöltésekor!";
    exit;
}

// Webtárhelyek lekérdezése
$sql_webtarhely = "SELECT * FROM Webtarhely WHERE FELHASZNALO_ID = :felhasznalo_id";
$stmt_webtarhely = oci_parse($conn, $sql_webtarhely);
oci_bind_by_name($stmt_webtarhely, ":felhasznalo_id", $felhasznalo_id);
oci_execute($stmt_webtarhely);

$sql_szamla = "SELECT S.SZAMLA_ID, S.OSSZEG, S.DATUM, S.ALLAPOT 
               FROM Szamla S
               JOIN Rendelkezik R ON S.RENDELKEZES_ID = R.RENDELKEZES_ID
               WHERE R.FELHASZNALO_ID = :felhasznalo_id";
$stmt_szamla = oci_parse($conn, $sql_szamla);
oci_bind_by_name($stmt_szamla, ":felhasznalo_id", $felhasznalo_id);
oci_execute($stmt_szamla);

$sql_domain = "SELECT D.DOMAIN_NEV, D.DOMAIN_TIPUS, D.LEJARATI_DATUM, W.WEBTARHELY_ID
               FROM Domain D
               JOIN Webtarhely W ON D.WEBTARHELY_ID = W.WEBTARHELY_ID
               WHERE W.FELHASZNALO_ID = :felhasznalo_id";
$stmt_domain = oci_parse($conn, $sql_domain);
oci_bind_by_name($stmt_domain, ":felhasznalo_id", $felhasznalo_id);
oci_execute($stmt_domain);

$domainok = [];
while ($domain = oci_fetch_assoc($stmt_domain)) {
    $domainok[$domain['WEBTARHELY_ID']] = $domain;
}

if (isset($_POST['update'])) {
    $new_email = trim($_POST['email']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        echo "<p style='color:red;'>A jelszavak nem egyeznek!</p>";
    } else {
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $sql_update = "UPDATE Felhasznalo SET EMAIL = :email, JELSZO = :password WHERE FELHASZNALONEV = :username";
            $stmt_update = oci_parse($conn, $sql_update);
            oci_bind_by_name($stmt_update, ":email", $new_email);
            oci_bind_by_name($stmt_update, ":password", $hashed_password);
        } else {
            $sql_update = "UPDATE Felhasznalo SET EMAIL = :email WHERE FELHASZNALONEV = :username";
            $stmt_update = oci_parse($conn, $sql_update);
            oci_bind_by_name($stmt_update, ":email", $new_email);
        }

        oci_bind_by_name($stmt_update, ":username", $username);
        if (oci_execute($stmt_update)) {
            echo "<p style='color:green;'>Adatok frissítve!</p>";
        } else {
            $e = oci_error($stmt_update);
            echo "<p style='color:red;'>Hiba történt a frissítés során: " . htmlentities($e['message']) . "</p>";
        }

        oci_free_statement($stmt_update);
    }
}

if (isset($_POST['fizet'])) {
    $szamla_id = $_POST['fizet_szamla_id'];

    $sql_fizet = "UPDATE Szamla SET allapot = 'Fizetve' WHERE szamla_id = :szamla_id";
    $stmt_fizet = oci_parse($conn, $sql_fizet);
    oci_bind_by_name($stmt_fizet, ":szamla_id", $szamla_id);

    if (oci_execute($stmt_fizet)) {
        oci_free_statement($stmt_fizet);
        header("Location: profil.php?fizetve=$szamla_id");
        exit;
    } else {
        $e = oci_error($stmt_fizet);
        echo "<p style='color:red;'>Hiba történt a fizetés során: " . htmlentities($e['message']) . "</p>";
        oci_free_statement($stmt_fizet);
    }

    if (isset($_GET['fizetve'])) {
        $id = htmlspecialchars($_GET['fizetve']);
        echo "<p style='color:green;'>Számla #$id sikeresen kifizetve.</p>";
    }
}

if (isset($_POST['lemond'])) {
    $webtarhely_id = $_POST['lemond_webtarhely_id'];

    $sql_lemond = "UPDATE Webtarhely SET STATUSZ = 'Inaktív' WHERE WEBTARHELY_ID = :webtarhely_id";
    $stmt_lemond = oci_parse($conn, $sql_lemond);
    oci_bind_by_name($stmt_lemond, ":webtarhely_id", $webtarhely_id);

    if (oci_execute($stmt_lemond)) {
        oci_free_statement($stmt_lemond);
        header("Location: profil.php?lemondva=$webtarhely_id");
        exit;
    } else {
        $e = oci_error($stmt_lemond);
        echo "<p style='color:red;'>Hiba történt a lemondás során: " . htmlentities($e['message']) . "</p>";
        oci_free_statement($stmt_lemond);
    }

    if (isset($_GET['lemondva'])) {
        $id = htmlspecialchars($_GET['lemondva']);
        echo "<p style='color:green;'>Webtárhely #$id sikeresen lemondva.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilom</title>
    <link rel="stylesheet" href="styles/style.css">
    <link rel="stylesheet" href="styles/webtarhely.css">
</head>
<body>
<div class="container">
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

    <h3>Webtárhelyek</h3>
    <table>
        <thead>
        <tr>
            <th>Webtárhely Mérete</th>
            <th>Státusz</th>
            <th>Letrehozás Dátuma</th>
            <th>Domain Név</th>
            <th>Művelet</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($webtarhely = oci_fetch_assoc($stmt_webtarhely)) : ?>
            <tr>
                <td><?php echo $webtarhely['MERET']; ?> MB</td>
                <td><?php echo $webtarhely['STATUSZ']; ?></td>
                <td><?php echo $webtarhely['LETREHOZAS']; ?></td>
                <td>
                    <?php
                    $web_id = $webtarhely['WEBTARHELY_ID'];
                    if (isset($domainok[$web_id])) {
                        $domain = $domainok[$web_id];
                        echo $domain['DOMAIN_NEV'] . " (" . $domain['DOMAIN_TIPUS'] . ")";
                    } else {
                        echo "Nincs domain";
                    }
                    ?>
                </td>
                <td>
                    <?php if ($webtarhely['STATUSZ'] === 'Aktív'): ?>
                        <form method="POST" action="">
                            <input type="hidden" name="lemond_webtarhely_id" value="<?php echo $webtarhely['WEBTARHELY_ID']; ?>">
                            <button type="submit" name="lemond" class="btn-fizet">Lemondás</button>
                        </form>
                    <?php else: ?>
                        <span class="inactive">Nincs elvégezhető művelet.</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <h3>Számlák</h3>
    <table>
        <thead>
        <tr>
            <th>Számla ID</th>
            <th>Összeg</th>
            <th>Dátum</th>
            <th>Állapot</th>
            <th>Művelet</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($szamla = oci_fetch_assoc($stmt_szamla)) : ?>
            <tr>
                <td><?php echo $szamla['SZAMLA_ID']; ?></td>
                <td><?php echo $szamla['OSSZEG']; ?> Ft</td>
                <td><?php echo $szamla['DATUM']; ?></td>
                <td><?php echo $szamla['ALLAPOT']; ?></td>
                <td>
                    <?php if (in_array($szamla['ALLAPOT'], ['Függőben', 'Késedelmes'])): ?>
                        <form method="POST" action="">
                            <input type="hidden" name="fizet_szamla_id" value="<?php echo $szamla['SZAMLA_ID']; ?>">
                            <button type="submit" name="fizet" class="btn-fizet">Fizet</button>
                        </form>
                    <?php else: ?>
                        <span class="paid">Minden teendő elvégezve!</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>
