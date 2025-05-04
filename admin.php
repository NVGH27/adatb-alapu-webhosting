<?php
include "config.php";
include 'menu.php';

$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

if (!$is_admin) {
    header('Location: index.php');
    exit;
}

$message = '';

if (isset($_POST['submit'])) {
    $dijcsomag_nev = trim($_POST['dijcsomag_nev']);
    $ar = $_POST['ar'];

    if (empty($dijcsomag_nev) || empty($ar)) {
        $message = "<p class='error'>Minden mezőt ki kell tölteni!</p>";
    } else {
        $sql_insert = "INSERT INTO Dijcsomag (dijcsomag_nev, ar) VALUES (:dijcsomag_nev, :ar)";
        $stmt_insert = oci_parse($conn, $sql_insert);

        oci_bind_by_name($stmt_insert, ":dijcsomag_nev", $dijcsomag_nev);
        oci_bind_by_name($stmt_insert, ":ar", $ar);

        if (oci_execute($stmt_insert)) {
            $message = "<p class='success'>Új díjcsomag sikeresen hozzáadva!</p>";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $e = oci_error($stmt_insert);
            $message = "<p class='error'>Hiba: " . htmlentities($e['message']) . "</p>";
        }

        oci_free_statement($stmt_insert);
    }
}

if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $sql_delete = "DELETE FROM Dijcsomag WHERE dijcsomag_id = :delete_id";
    $stmt_delete = oci_parse($conn, $sql_delete);

    oci_bind_by_name($stmt_delete, ":delete_id", $delete_id);

    if (oci_execute($stmt_delete)) {
        $message = "<p class='success'>Díjcsomag sikeresen törölve!</p>";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $e = oci_error($stmt_delete);
        $message = "<p class='error'>Hiba: " . htmlentities($e['message']) . "</p>";
    }

    oci_free_statement($stmt_delete);
}

if (isset($_POST['submit_reklam'])) {
    $szoveg = trim($_POST['szoveg']);
    $hivatkozas = trim($_POST['hivatkozas']);
    $felhasznalo_id = $_SESSION['user_id']; // Az admin felhasználó ID-ja

    if (empty($szoveg) || empty($hivatkozas)) {
        $message = "<p class='error'>Minden mezőt ki kell tölteni!</p>";
    } else {
        $sql_insert_reklam = "INSERT INTO Reklam (szoveg, hivatkozas, felhasznalo_id) VALUES (:szoveg, :hivatkozas, :felhasznalo_id)";
        $stmt_insert_reklam = oci_parse($conn, $sql_insert_reklam);

        oci_bind_by_name($stmt_insert_reklam, ":szoveg", $szoveg);
        oci_bind_by_name($stmt_insert_reklam, ":hivatkozas", $hivatkozas);
        oci_bind_by_name($stmt_insert_reklam, ":felhasznalo_id", $felhasznalo_id);

        if (oci_execute($stmt_insert_reklam)) {
            $message = "<p class='success'>Új reklám sikeresen hozzáadva!</p>";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $e = oci_error($stmt_insert_reklam);
            $message = "<p class='error'>Hiba: " . htmlentities($e['message']) . "</p>";
        }

        oci_free_statement($stmt_insert_reklam);
    }
}

if (isset($_GET['delete_reklam_id'])) {
    $delete_reklam_id = $_GET['delete_reklam_id'];
    $sql_delete_reklam = "DELETE FROM Reklam WHERE reklam_id = :delete_reklam_id";
    $stmt_delete_reklam = oci_parse($conn, $sql_delete_reklam);

    oci_bind_by_name($stmt_delete_reklam, ":delete_reklam_id", $delete_reklam_id);

    if (oci_execute($stmt_delete_reklam)) {
        $message = "<p class='success'>Reklám sikeresen törölve!</p>";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $e = oci_error($stmt_delete_reklam);
        $message = "<p class='error'>Hiba: " . htmlentities($e['message']) . "</p>";
    }

    oci_free_statement($stmt_delete_reklam);
}

if (isset($_POST['run_procedure'])) {
    $proc_sql = "BEGIN fizetesi_hatarido_ellenorzes; END;";
    $stmt_proc = oci_parse($conn, $proc_sql);

    if (oci_execute($stmt_proc)) {
        $sql_ellenorzes = "
            SELECT w.felhasznalo_id, f.felhasznalonev
            FROM Webtarhely w
            JOIN Felhasznalo f ON w.felhasznalo_id = f.felhasznalo_id
            WHERE w.statusz = 'Inaktív'
        ";
        $stmt_result = oci_parse($conn, $sql_ellenorzes);
        oci_execute($stmt_result);

        $inaktivak = [];
        while ($row = oci_fetch_assoc($stmt_result)) {
            $inaktivak[] = $row['FELHASZNALONEV'];
        }

        oci_free_statement($stmt_result);
    } else {
        $e = oci_error($stmt_proc);
        $message = "<p class='error'>Hiba az eljárás futtatásakor: " . htmlentities($e['message']) . "</p>";
    }

    oci_free_statement($stmt_proc);
}

$sql = "SELECT dijcsomag_id, dijcsomag_nev, ar FROM Dijcsomag";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);

$sql_reklam = "SELECT reklam_id, szoveg, hivatkozas FROM Reklam";
$stmt_reklam = oci_parse($conn, $sql_reklam);
oci_execute($stmt_reklam);

$sql_stats = "
    SELECT d.dijcsomag_nev, COUNT(m.dijcsomag_id) AS rendelesek_szama
    FROM Dijcsomag d
    LEFT JOIN Rendelkezik m ON d.dijcsomag_id = m.dijcsomag_id
    GROUP BY d.dijcsomag_nev
    ORDER BY rendelesek_szama DESC
";
$stmt_stats = oci_parse($conn, $sql_stats);
oci_execute($stmt_stats);
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Felület - Webtárhely Csomagok</title>
    <link rel="stylesheet" href="styles/style.css">
    <link rel="stylesheet" href="styles/admin.css">
</head>
<body>
<div class="container">
    <h2>Admin Panel</h2>

    <div class="message-container">
        <?php if (!empty($message)) { echo $message; } ?>
    </div>

    <div class="admin-container">
        <h3>Megrendelhető díjcsomagok</h3>
        <table class="packages-table">
            <thead>
            <tr>
                <th>Csomag neve</th>
                <th>Ár</th>
                <th>Akciók</th>
            </tr>
            </thead>
            <tbody>
            <?php while ($row = oci_fetch_assoc($stmt)) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['DIJCSOMAG_NEV']); ?></td>
                    <td><?php echo htmlspecialchars($row['AR']); ?> Ft</td>
                    <td>
                        <a href="?delete_id=<?php echo $row['DIJCSOMAG_ID']; ?>" class="action-btn" onclick="return confirm('Biztosan törölni szeretnéd ezt a csomagot?');">Törlés</a>
                    </td>
                </tr>
            <?php } ?>
            <tr class="new-package-row">
                <form method="POST" action="">
                    <td><input type="text" name="dijcsomag_nev" id="dijcsomag_nev" placeholder="Új csomag neve" required></td>
                    <td><input type="number" name="ar" id="ar" placeholder="Új csomag ára" required min="1000">
                    <td><button type="submit" name="submit" class="submit-btn">Hozzáadás</button></td>
                </form>
            </tr>
            </tbody>
        </table>
    </div>
</div>
<div class="container">
    <div class="admin-container">
    <h3>Feltöltött Reklámok</h3>
    <table class="packages-table">
        <thead>
        <tr>
            <th>Szöveg</th>
            <th>Hivatkozás</th>
            <th>Akciók</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($row = oci_fetch_assoc($stmt_reklam)) { ?>
            <tr>
                <td><?php echo htmlspecialchars($row['SZOVEG']); ?></td>
                <td><a href="<?php echo htmlspecialchars($row['HIVATKOZAS']); ?>" target="_blank"><?php echo htmlspecialchars($row['HIVATKOZAS']); ?></a></td>
                <td>
                    <a href="?delete_reklam_id=<?php echo $row['REKLAM_ID']; ?>" class="action-btn" onclick="return confirm('Biztosan törölni szeretnéd ezt a reklámot?');">Törlés</a>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
    </div>
    <h3>Új reklám hozzáadása</h3>
    <form method="POST" action="">
        <div>
            <label for="szoveg">Reklám szöveg:</label>
            <input type="text" name="szoveg" id="szoveg" required>
        </div>
        <div>
            <label for="hivatkozas">Hivatkozás:</label>
            <input type="url" name="hivatkozas" id="hivatkozas" required>
        </div>
        <button type="submit" name="submit_reklam" class="submit-btn">Hozzáadás</button>
    </form>
</div>
<div class="container">
    <h3>Statisztika</h3>
    <table class="packages-table">
        <thead>
        <tr>
            <th>Díjcsomag neve</th>
            <th>Megrendelések száma</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($row = oci_fetch_assoc($stmt_stats)) { ?>
            <tr>
                <td><?php echo htmlspecialchars($row['DIJCSOMAG_NEV']); ?></td>
                <td><?php echo htmlspecialchars($row['RENDELESEK_SZAMA']); ?></td>
            </tr>
        <?php } ?>
        </tbody>
    </table>

</div>
<div class="container">
    <form method="post" action="">
        <button type="submit" name="run_procedure" class="submit-btn">Fizetési határidők ellenőrzése</button>
    </form>
</div>
</body>
</html>

<?php
oci_free_statement($stmt);
oci_close($conn);
?>

