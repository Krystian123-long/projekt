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
    <title>Konto bankowe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex justify-content-center align-items-center vh-100">
<div class="card shadow-lg" style="width: 22rem;">
    <div class="card-body text-center">
        <h3 class="card-title mb-3">💰 Konto bankowe</h3>
        <p class="card-text fw-bold">Saldo: <span class="text-primary"><?php echo $_SESSION['saldo']; ?> PLN</span></p>
        <?php if ($komunikat): ?>
            <div class="alert alert-info" role="alert">
                <?php echo $komunikat; ?>
            </div>
        <?php endif; ?>
        <form method="post">
            <div class="mb-3 text-start">
                <label for="kwota" class="form-label">Kwota:</label>
                <input type="text" name="kwota" id="kwota" class="form-control" required>
            </div>
            <div class="d-grid gap-2">
                <button type="submit" name="wplata" class="btn btn-success">Wpłata</button>
                <button type="submit" name="wyplata" class="btn btn-danger">Wypłata</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>