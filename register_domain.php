<?php
session_start();
include "config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: domain.php?error=Bejelentkezés szükséges a domain regisztrációhoz");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $domain_name = trim($_POST['domain_nev']);
    $domain_type = $_POST['domain_tipus'];
    $webtarhely_id = $_POST['webtarhely_id'];

    $full_domain = strtolower($domain_name . $domain_type);

    $check_sql = "SELECT domain_nev FROM Domain WHERE LOWER(domain_nev) = :domain";
    $stmt_check = oci_parse($conn, $check_sql);
    oci_bind_by_name($stmt_check, ":domain", $full_domain);
    oci_execute($stmt_check);

    if (oci_fetch($stmt_check)) {
        header("Location: domain.php?error=A domain név már foglalt");
        exit;
    }

    oci_free_statement($stmt_check);

    $insert_sql = "INSERT INTO Domain (domain_nev, domain_tipus, lejarati_datum, webtarhely_id)
                   VALUES (:domain_nev, :domain_tipus, ADD_MONTHS(SYSDATE, 1), :webtarhely_id)";
    $stmt_insert = oci_parse($conn, $insert_sql);
    oci_bind_by_name($stmt_insert, ":domain_nev", $full_domain);
    oci_bind_by_name($stmt_insert, ":domain_tipus", $domain_type);
    oci_bind_by_name($stmt_insert, ":webtarhely_id", $webtarhely_id);

    if (oci_execute($stmt_insert)) {
        header("Location: domain.php?success=Sikeresen regisztráltad a domaint: $full_domain");
    } else {
        $e = oci_error($stmt_insert);
        header("Location: domain.php?error=" . urlencode("Hiba: " . $e['message']));
    }

    oci_free_statement($stmt_insert);
    oci_close($conn);
} else {
    header("Location: domain.php?error=Érvénytelen kérés");
}
?>
