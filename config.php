<?php
//TODO a nálad létrehozott felhasználóval csatlakozz az adatbázisra, a connectiont is lehet szükséges átírni
$conn = oci_connect('', '', 'localhost:1521/XE');
if (!$conn) {
    $e = oci_error();
    trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
}