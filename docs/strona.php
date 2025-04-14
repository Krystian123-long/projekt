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
    <style>
        body 
        {
            background: linear-gradient(135deg, #e0eafc, #cfdef3);
        }
        .card 
        {
            border-radius: 20px;
        }
        .form-control 
        {
            border-radius: 10px;
        }
        .btn 
        {
            transition: transform 0.2s ease;
        }
        .btn:hover 
        {
            transform: scale(1.05);
        }
        .alert 
        {
            animation: fadeIn 0.5s ease-in-out;
        }
        @keyframes fadeIn 
        {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="d-flex justify-content-center align-items-center vh-100">
<div class="card shadow-lg p-3" style="width: 22rem;">
    <div class="card-body text-center">
        <h3 class="card-title mb-3">💰 Konto bankowe</h3>
        <p class="card-text fw-bold">Saldo: <span class="text-primary"><?php echo $_SESSION['saldo']; ?> PLN</span></p>
        <?php if ($komunikat): ?>
            <div class="alert alert-info" role="alert">
                <?php echo $komunikat; ?>
            </div>
        <?php endif; ?>
        <form method="post" id="bankForm">
            <div class="mb-3 text-start">
                <label for="kwota" class="form-label">Kwota:</label>
                <input type="text" name="kwota" id="kwota" class="form-control" required>
                <div id="preview" class="form-text text-muted mt-1"></div>
            </div>
            <div class="d-grid gap-2">
                <button type="submit" name="wplata" class="btn btn-success">Wpłata</button>
                <button type="submit" name="wyplata" class="btn btn-danger">Wypłata</button>
            </div>
        </form>
    </div>
</div>
<script>
    const kwotaInput = document.getElementById('kwota');
    const form = document.getElementById('bankForm');
    const preview = document.getElementById('preview');
    kwotaInput.addEventListener('input', () => 
    {
        let value = kwotaInput.value.trim();
        if (value && !isNaN(value) && Number(value) > 0) 
        {
            preview.textContent = `Kwota do operacji: ${value} PLN`;
            preview.style.color = 'green';
        } 
        else 
        {
            preview.textContent = '';
        }
    });
    form.addEventListener('submit', (e) => 
    {
        let value = kwotaInput.value.trim();
        if (isNaN(value) || Number(value) <= 0) 
        {
            e.preventDefault();
            alert('Podaj poprawną kwotę większą od zera!');
            kwotaInput.focus();
        }
    });
</script>
</body>
</html>