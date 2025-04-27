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

$sql = "SELECT dijcsomag_id, dijcsomag_nev, ar FROM Dijcsomag";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);
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
    <h2>Admin Panel - Webtárhely Csomagok</h2>

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
</body>
</html>

<?php
oci_free_statement($stmt);
oci_close($conn);
?>

