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
    } 
    else 
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
    <style>
        body 
        {
            font-family: Arial, sans-serif;
            background-color: white;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container 
        {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px black;
            width: 300px;
            text-align: center;
            margin: 0 auto;
        }
        h2 
        {
            color: #333;
            margin-bottom: 20px;
        }
        form 
        {
            margin-bottom: 20px;
        }
        label 
        {
            display: block;
            margin-bottom: 10px;
            font-size: 14px;
            color: #555;
        }
        input[type="text"] 
        {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box; 
        }
        button 
        {
            background-color: green;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            margin-bottom: 10px;
        }
        button:hover 
        {
            background-color: green;
        }
        p 
        {
            font-size: 14px;
            color: red;
            margin-top: 10px;
        }
        .saldo 
        {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Konto bankowe</h2>
        <p>Aktualne saldo: <span class="saldo"><?php echo $_SESSION['saldo']; ?> PLN</span></p>
        <form method="post">
            <label>Kwota:</label>
            <input type="text" name="kwota" required>
            <button type="submit" name="wplata">Wpłata</button>
            <button type="submit" name="wyplata">Wypłata</button>
        </form>
        <p><?php echo $komunikat; ?></p>
    </div>
</body>
</html>