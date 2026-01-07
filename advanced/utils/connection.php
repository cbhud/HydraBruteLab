<?php
// utils/connection.php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = "localhost";
$dbname = "korisnici";
$dbuser = "root";
$dbpass = "";

try {
    $konekcija = new mysqli($host, $dbuser, $dbpass, $dbname);
    $konekcija->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    // Za produkciju se ovo ne prikazuje, ali za lab može.
    exit("Konekcija NIJE USPJESNA! Provjeri connection.php: " . $e->getMessage());
}
