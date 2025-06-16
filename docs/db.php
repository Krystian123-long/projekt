<?php
$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "phptestdb";
try 
{
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} 
catch(PDOException $e) 
{
    die("Błąd połączenia z bazą danych: " . $e->getMessage());
}
?>