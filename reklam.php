<?php
include "config.php";
include 'menu.php';

$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

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
    $_SESSION['felhasznalo_id'] = $felhasznalo_id; // <- ADD THIS LINE
} else {
    echo "Hiba történt a felhasználói adatok betöltésekor!";
    exit;
}

$message = '';

if (isset($_POST['submit'])) {
    $szoveg = trim($_POST['szoveg']);
    $hivatkozas = trim($_POST['hivatkozas']);
    $felhasznalo_id = $_SESSION['felhasznalo_id'];

    if (empty($szoveg) || empty($hivatkozas)) {
        $message = "<p class='error'>Minden mezőt ki kell tölteni!</p>";
    } else {
        $sql = "INSERT INTO Reklam (szoveg, hivatkozas, felhasznalo_id) VALUES (:szoveg, :hivatkozas, :felhasznalo_id)";
        $stmt = oci_parse($conn, $sql);

        oci_bind_by_name($stmt, ":szoveg", $szoveg);
        oci_bind_by_name($stmt, ":hivatkozas", $hivatkozas);
        oci_bind_by_name($stmt, ":felhasznalo_id", $felhasznalo_id);

        if (oci_execute($stmt)) {
            $message = "<p class='success'>Reklám sikeresen hozzáadva!</p>";
        } else {
            $e = oci_error($stmt);
            $message = "<p class='error'>Hiba: " . htmlentities($e['message']) . "</p>";
        }

        oci_free_statement($stmt);
    }
}

$sql = "
    SELECT r.reklam_id, r.szoveg, r.hivatkozas, f.szerepkor 
    FROM Reklam r
    JOIN Felhasznalo f ON r.felhasznalo_id = f.felhasznalo_id
    WHERE r.felhasznalo_id = :felhasznalo_id
    OR f.szerepkor = 'admin'
";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ":felhasznalo_id", $_SESSION['felhasznalo_id']);
oci_execute($stmt);

$top_link_sql = "
    SELECT hivatkozas, COUNT(*) AS db
    FROM Reklam
    GROUP BY hivatkozas
    ORDER BY db DESC
    FETCH FIRST 1 ROWS ONLY
";
$top_link_stmt = oci_parse($conn, $top_link_sql);
oci_execute($top_link_stmt);
$top_link_row = oci_fetch_assoc($top_link_stmt);
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reklám hozzáadása</title>
    <link rel="stylesheet" href="styles/style.css">
    <link rel="stylesheet" href="styles/admin.css">
</head>
<body>
<div class="container">
    <h2>Reklám hozzáadása</h2>

    <div class="message-container">
        <?php if (!empty($message)) { echo $message; } ?>
    </div>

    <div class="admin-container">
        <h3>Új reklám hozzáadása</h3>
        <form method="POST" action="">
            <label for="szoveg">Reklám szövege</label>
            <textarea name="szoveg" id="szoveg" rows="4" required></textarea>
            <label for="hivatkozas">Hivatkozás</label>
            <input type="text" name="hivatkozas" id="hivatkozas" required>

            <button type="submit" name="submit" class="submit-btn">Hozzáadás</button>
        </form>
        <?php if ($top_link_row): ?>
            <div class="top-reklam">
                <h3>Leggyakrabban hirdetett oldal:</h3>
                <p>
                    <a href="<?php echo htmlspecialchars($top_link_row['HIVATKOZAS']); ?>" target="_blank">
                        <?php echo htmlspecialchars($top_link_row['HIVATKOZAS']); ?>
                    </a>
                    (<?php echo $top_link_row['DB']; ?> reklámmal)
                </p>
            </div>
        <?php endif; ?>

        <div class="message-container">
            <?php if (!empty($message)) { echo $message; } ?>
        </div>
    </div>

    <div class="packages-table">
        <h3>Reklámok</h3>
        <table class="reklam-table">
            <thead>
            <tr>
                <th>Szöveg</th>
                <th>Hivatkozás</th>
                <th>Hozzáadó</th>
            </tr>
            </thead>
            <tbody>
            <?php while ($row = oci_fetch_assoc($stmt)) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['SZOVEG']); ?></td>
                    <td><a href="<?php echo htmlspecialchars($row['HIVATKOZAS']); ?>" target="_blank">Link</a></td>
                    <td><?php echo htmlspecialchars($row['SZEREPKOR']); ?></td>
                </tr>
            <?php } ?>
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
