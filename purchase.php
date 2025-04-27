<?php
session_start();
include 'config.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

if (isset($_GET['id']) && isset($_GET['price'])) {
    $package_id = $_GET['id'];
    $package_price = $_GET['price'];
    $user_id = $_SESSION['user_id'];

    $sql_check = "SELECT dijcsomag_id, ar FROM Dijcsomag WHERE dijcsomag_id = :package_id";
    $stmt_check = oci_parse($conn, $sql_check);
    oci_bind_by_name($stmt_check, ":package_id", $package_id);
    oci_execute($stmt_check);

    $package = oci_fetch_assoc($stmt_check);

    if ($package) {
        $sql_insert_rendelkezik = "INSERT INTO Rendelkezik (felhasznalo_id, dijcsomag_id) 
                                   VALUES (:user_id, :package_id) 
                                   RETURNING rendelkezes_id INTO :rendelkezes_id";
        $stmt_insert_rendelkezik = oci_parse($conn, $sql_insert_rendelkezik);
        oci_bind_by_name($stmt_insert_rendelkezik, ":user_id", $user_id);
        oci_bind_by_name($stmt_insert_rendelkezik, ":package_id", $package_id);
        oci_bind_by_name($stmt_insert_rendelkezik, ":rendelkezes_id", $rendelkezes_id, 32);

        if (oci_execute($stmt_insert_rendelkezik)) {
            $sql_insert_webtarhely = "INSERT INTO Webtarhely (meret, statusz, letrehozas, felhasznalo_id)
                                      VALUES (100, 'Aktív', SYSDATE, :user_id)";
            $stmt_insert_webtarhely = oci_parse($conn, $sql_insert_webtarhely);
            oci_bind_by_name($stmt_insert_webtarhely, ":user_id", $user_id);

            if (oci_execute($stmt_insert_webtarhely)) {
                // Miután létrejött a webtárhely, beszúrjuk a számlát
                $sql_insert_szamla = "INSERT INTO Szamla (osszeg, datum, allapot, rendelkezes_id) 
                                      VALUES (:osszeg, SYSDATE, 'Fizetve', :rendelkezes_id)";
                $stmt_insert_szamla = oci_parse($conn, $sql_insert_szamla);
                oci_bind_by_name($stmt_insert_szamla, ":osszeg", $package_price);
                oci_bind_by_name($stmt_insert_szamla, ":rendelkezes_id", $rendelkezes_id);

                if (oci_execute($stmt_insert_szamla)) {
                    header("Location: profil.php");
                    exit;
                } else {
                    echo "Hiba történt a számla rögzítésekor!";
                }

                oci_free_statement($stmt_insert_szamla);
            } else {
                echo "Hiba történt a webtárhely rögzítésekor!";
            }

            oci_free_statement($stmt_insert_webtarhely);
        } else {
            echo "Hiba történt a vásárlás során!";
        }
        oci_free_statement($stmt_insert_rendelkezik);
    } else {
        echo "Ez a csomag nem létezik!";
    }

    oci_free_statement($stmt_check);
} else {
    echo "Nincs csomag kiválasztva!";
}

oci_close($conn);

