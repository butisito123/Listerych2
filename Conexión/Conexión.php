<?php
$servername = "b6jqx2rp2hjugebmrvqg-mysql.services.clever-cloud.com";
$port = "3306";
$username = "u0yph51ziwmajjsv";
$password = "Tjrvzz1cjq4qeSjmnENK";
$database = "b6jqx2rp2hjugebmrvqg";

// Crear conexión
$conn = new mysqli($servername . ":" . $port, $username, $password, $database);


if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

echo "Conexión exitosa a MySQL";
