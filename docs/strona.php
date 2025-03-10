<?php
session_start();
if (!isset($_SESSION['saldo'])) 
{
    $_SESSION['saldo'] = 1000; 
}
function wplata($kwota) 
{
    $_SESSION['saldo'] += $kwota;
    $_SESSION['komunikat'] = "Wpłacono $kwota PLN. Nowe saldo: {$_SESSION['saldo']} PLN.";
}
function wyplata($kwota) 
{
    if ($kwota > $_SESSION['saldo']) 
    {
        $_SESSION['komunikat'] = "Brak wystarczających środków na koncie!";
    } 
    else 
    {
        $_SESSION['saldo'] -= $kwota;
        $_SESSION['komunikat'] = "Wypłacono $kwota PLN. Nowe saldo: {$_SESSION['saldo']} PLN.";
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST") 
{
    if (isset($_POST['kwota']) && is_numeric($_POST['kwota']) && $_POST['kwota'] > 0) 
    {
        $kwota = (int)$_POST['kwota'];

        if (isset($_POST['wplata'])) 
        {
            wplata($kwota);
        } 
        elseif (isset($_POST['wyplata'])) 
        {
            wyplata($kwota);
        }
    } else 
    {
        $_SESSION['komunikat'] = "Podaj poprawną kwotę!";
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
$komunikat = isset($_SESSION['komunikat']) ? $_SESSION['komunikat'] : "";
unset($_SESSION['komunikat']);
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Bank PHP</title>
</head>
<body>
    <h2>Prosty system bankowy</h2>
    <p>Aktualne saldo: <strong><?php echo $_SESSION['saldo']; ?> PLN</strong></p>
    <form method="post">
        <label>Kwota: <input type="number" name="kwota" required></label>
        <button type="submit" name="wplata">Wpłata</button>
        <button type="submit" name="wyplata">Wypłata</button>
    </form>
    <p><?php echo $komunikat; ?></p>
</body>