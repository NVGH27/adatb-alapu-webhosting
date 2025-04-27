<?php
include 'config.php';
include 'menu.php';

$sql = "SELECT dijcsomag_id, dijcsomag_nev, ar FROM Dijcsomag";
$stmt = oci_parse($conn, $sql);

if (!oci_execute($stmt)) {
    $e = oci_error($stmt);
    echo "Hiba a lekérdezés végrehajtásakor: " . htmlentities($e['message']);
    exit;
}

?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Webtárhely</title>
    <link rel="stylesheet" href="styles/style.css">
    <link rel="stylesheet" href="styles/webtarhely.css">
</head>
<body>
<div class="container">
    <h2>Válassz egy webtárhely csomagot!</h2>
    <div class="packages">
        <?php
        while ($row = oci_fetch_assoc($stmt)) {
            ?>
            <div class="package">
                <h3><?php echo htmlspecialchars($row['DIJCSOMAG_NEV']); ?></h3>
                <p>Ár: <?php echo htmlspecialchars($row['AR']); ?> Ft</p>
                <?php if (isset($_SESSION['username'])) {
                    $in_cart = isset($_SESSION['cart']) && in_array($row['DIJCSOMAG_ID'], $_SESSION['cart']);
                    $button_text = "Megvásárlom";
                    ?>
                    <button
                            class="cart-button"
                            data-package-id="<?php echo htmlspecialchars($row['DIJCSOMAG_ID']); ?>"
                            onclick="confirmPurchase(<?php echo $row['DIJCSOMAG_ID']; ?>, '<?php echo $row['DIJCSOMAG_NEV']; ?>', <?php echo $row['AR']; ?>)">
                        <?php echo $button_text; ?>
                    </button>
                <?php } else { ?>
                    <p>Be kell jelentkezned a a vásárláshoz!</p>
                <?php } ?>
            </div>
            <?php
        }
        ?>
    </div>
</div>
<script>
    function confirmPurchase(packageId, packageName, packagePrice) {
        if (confirm("Biztosan meg szeretnéd vásárolni a csomagot: " + packagePrice + " Ft-ért?")) {
            window.location.href = "purchase.php?id=" + packageId + "&price=" + packagePrice;
        }
    }
</script>
</body>
</html>

<?php
oci_free_statement($stmt);
oci_close($conn);
?>
