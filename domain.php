<?php
include "config.php";
include 'menu.php';
$is_logged_in = isset($_SESSION['user_id']);
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;

$search_result = "";
if (isset($_GET['kereses'])) {
    $search_name = trim($_GET['kereses']);
    $search_name = strtolower($search_name);

    $check_sql = "SELECT domain_nev FROM Domain WHERE LOWER(domain_nev) LIKE :keresett";
    $stmt = oci_parse($conn, $check_sql);
    $like_pattern = $search_name . "%";
    oci_bind_by_name($stmt, ":keresett", $like_pattern);
    oci_execute($stmt);

    $found_domains = [];
    while ($row = oci_fetch_assoc($stmt)) {
        $found_domains[] = strtolower($row['DOMAIN_NEV']);
    }
    oci_free_statement($stmt);

    $search_result .= "<div class='domain-results'><h3>Eredmények:</h3>";
    $types = [".hu", ".com", ".net"];
    foreach ($types as $type) {
        $full = $search_name . $type;
        if (in_array($full, $found_domains)) {
            $search_result .= "<p><strong>$full</strong> - <span class='foglalt'>Foglalt</span></p>";
        } else {
            $search_result .= "<p><strong>$full</strong> - <span class='szabad'>Szabad</span></p>";
        }
    }
    $search_result .= "</div>";
}

$success = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : null;
$error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : null;
?>

    <!DOCTYPE html>
    <html lang="hu">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Domain</title>
        <link rel="stylesheet" href="styles/style.css">
        <link rel="stylesheet" href="styles/webtarhely.css">
    </head>
    <body>
    <div class="container">
        <h2>Domain regisztráció</h2>

        <form method="GET" action="domain.php" class="domain-search-form">
            <label for="kereses">Domain név keresése:</label>
            <input type="text" name="kereses" id="kereses" placeholder="pl. sajatoldal" required>
            <input type="submit" value="Keresés">
        </form>

        <?= $search_result ?>

        <?php if (!$is_logged_in): ?>
            <div class="warning">
                <p><strong>Csak bejelentkezett felhasználók regisztrálhatnak domain nevet!</strong></p>
            </div>
        <?php else: ?>
            <form method="POST" action="register_domain.php" class="domain-reg-form">
                <label for="domain_nev">Domain név:</label>
                <input type="text" name="domain_nev" id="domain_nev" required>

                <label for="domain_tipus">Domain típus:</label>
                <select name="domain_tipus" id="domain_tipus" required>
                    <option value=".hu">.hu</option>
                    <option value=".com">.com</option>
                    <option value=".net">.net</option>
                </select>

                <label for="webtarhely_id">Webtárhely kiválasztása:</label>
                <select name="webtarhely_id" id="webtarhely_id" required>
                    <?php
                    $sql = "SELECT w.webtarhely_id 
                        FROM Webtarhely w 
                        LEFT JOIN Domain d ON w.webtarhely_id = d.webtarhely_id 
                        WHERE w.felhasznalo_id = :user_id AND d.domain_nev IS NULL";
                    $stmt = oci_parse($conn, $sql);
                    oci_bind_by_name($stmt, ":user_id", $user_id);
                    oci_execute($stmt);
                    while ($row = oci_fetch_assoc($stmt)) {
                        echo "<option value='" . $row['WEBTARHELY_ID'] . "'>Webtárhely #" . $row['WEBTARHELY_ID'] . "</option>";
                    }
                    oci_free_statement($stmt);
                    ?>
                </select>

                <input type="submit" value="Regisztrálás">
            </form>
        <?php endif; ?>
    </div>

    <?php if ($success): ?>
        <script>
            alert("<?php echo $success; ?>");
        </script>
    <?php endif; ?>

    <?php if ($error): ?>
        <script>
            alert("<?php echo $error; ?>");
        </script>
    <?php endif; ?>
    </body>
    </html>

<?php oci_close($conn); ?>