<?php
session_start();
if (!isset($_SESSION['saldo'])) 
{
    $_SESSION['saldo'] = 1000;
}
if (!isset($_SESSION['oszczednosci'])) 
{
    $_SESSION['oszczednosci'] = 500;
}
function wplata($kwota)
{
    $_SESSION['saldo'] += $kwota;
    $_SESSION['komunikat'] = "Wpłacono $kwota PLN. Nowe saldo: {$_SESSION['saldo']} PLN.";
    $_SESSION['last_action'] = 'wplata';
}
function wyplata($kwota)
{
    if ($kwota > $_SESSION['saldo']) 
    {
        $_SESSION['komunikat'] = "Brak wystarczających środków na koncie!";
        $_SESSION['last_action'] = 'error';
    } 
    else 
    {
        $_SESSION['saldo'] -= $kwota;
        $_SESSION['komunikat'] = "Wypłacono $kwota PLN. Nowe saldo: {$_SESSION['saldo']} PLN.";
        $_SESSION['last_action'] = 'wyplata';
    }
}
function przeslijNaOszczednosci($kwota)
{
    if ($kwota > $_SESSION['saldo']) 
    {
        $_SESSION['komunikat'] = "Nie masz tylu środków, by przelać na konto oszczędnościowe.";
        $_SESSION['last_action'] = 'error';
    } 
    else 
    {
        $_SESSION['saldo'] -= $kwota;
        $_SESSION['oszczednosci'] += $kwota;
        $_SESSION['komunikat'] = "Przelano $kwota PLN na konto oszczędnościowe.";
        $_SESSION['last_action'] = 'na_oszczednosci';
    }
}
function przeslijNaGlowne($kwota)
{
    if ($kwota > $_SESSION['oszczednosci']) 
    {
        $_SESSION['komunikat'] = "Nie masz tylu środków, by przelać na konto główne.";
        $_SESSION['last_action'] = 'error';
    } 
    else 
    {
        $_SESSION['oszczednosci'] -= $kwota;
        $_SESSION['saldo'] += $kwota;
        $_SESSION['komunikat'] = "Przelano $kwota PLN na konto główne.";
        $_SESSION['last_action'] = 'na_glowne';
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
        elseif (isset($_POST['przelej_na_oszczednosci'])) 
        {
            przeslijNaOszczednosci($kwota);
        } 
        elseif (isset($_POST['przelej_na_glowne'])) 
        {
            przeslijNaGlowne($kwota);
        }
    } 
    else 
    {
        $_SESSION['komunikat'] = "Podaj poprawną kwotę!";
        $_SESSION['last_action'] = 'error';
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
$komunikat = $_SESSION['komunikat'] ?? "";
$last_action = $_SESSION['last_action'] ?? "";
unset($_SESSION['komunikat']);
unset($_SESSION['last_action']);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8" />
    <title>Konto bankowe</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body 
        {
            background: linear-gradient(135deg, #e0eafc, #cfdef3);
            font-family: Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .konto-container 
        {
            display: flex;
            gap: 20px;
            max-width: 1000px;
            width: 100%;
        }
        .card 
        {
            flex: 1;
            border-radius: 20px;
            padding: 20px;
            background: white;
            box-shadow: 0 0 15px rgb(0 0 0 / 0.15);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 400px; 
        }
        h3 
        {
            margin-bottom: 20px;
            text-align: center;
        }
        .saldo 
        {
            font-weight: 700;
            font-size: 1.5rem;
            margin-bottom: 20px;
            text-align: center;
            color: black; 
        }
        /* Formularz i komunikat w jednej linii */
        form 
        {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .input-row 
        {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .input-row label 
        {
            flex-shrink: 0;
            width: 60px;
        }
        .input-row input[type="text"] 
        {
            flex-grow: 1;
            border-radius: 10px;
            padding: 8px 12px;
            border: 1px solid #ced4da;
            font-size: 1rem;
        }
        .komunikat 
        {
            min-height: 1.6rem; /* rezerwujemy miejsce na tekst komunikatu */
            font-size: 0.9rem;
            color: #555;
            text-align: center;
            margin-bottom: 10px;
            height: 24px;
        }
        .btn-group 
        {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: center;
        }
        button.btn 
        {
            flex: 1 1 45%;
            border-radius: 12px;
            font-weight: 600;
            padding: 10px 0;
            font-size: 1rem;
            transition: background-color 0.3s ease;
        }
        button.btn:hover 
        {
            filter: brightness(1.1);
        }
        /* Kolory przycisków */
        .btn-success { background-color: #198754; color: white; border: none; }
        .btn-danger { background-color: #dc3545; color: white; border: none; }
        .btn-primary { background-color: #0d6efd; color: white; border: none; }
        .btn-info { background-color: #0dcaf0; color: white; border: none; }
        /* Animacje dla tytułów */
        .pulse-yellow 
        {
            animation: pulse-yellow 2s infinite;
            color: #ffc107;
        }
        @keyframes pulse-yellow 
        {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .pulse-purple 
        {
            animation: pulse-purple 2s infinite;
            color: #6f42c1;
        }
        @keyframes pulse-purple 
        {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        /* Kolory komunikatów */
        .komunikat.error 
        {
            color: #dc3545;
        }
        .komunikat.success 
        {
            color: #198754;
        }
        /* Podgląd kwoty */
        .podglad {
            color: #888888;
            font-size: 0.9rem;
            margin-top: 4px;
            min-height: 18px;
            font-style: italic;
        }
        @media (max-width: 768px) 
        {
            .konto-container 
            {
                flex-direction: column;
            }
            button.btn 
            {
                flex: 1 1 100%;
            }
        }
    </style>
</head>
<body>
<div class="konto-container">
    <!-- Konto główne -->
    <div class="card">
    <h3 class="<?php echo ($last_action === 'wplata' || $last_action === 'wyplata') ? 'pulse-yellow' : ''; ?>">💰 Konto główne</h3>
    <p class="saldo"><?php echo $_SESSION['saldo']; ?> PLN</p> <!-- bez text-primary, kolor czarny -->
    <div class="komunikat <?php 
    if ($last_action === 'error' || $last_action === 'na_oszczednosci') 
    {
        echo 'error'; // czerwony dla błędów i przelewów na oszczędności (z konta głównego)
    } 
    elseif ($last_action === 'wplata') 
    {
        echo 'success'; // zielony dla wpłaty
    } 
    elseif ($last_action === 'wyplata') 
    {
        echo 'error';   // czerwony dla wypłaty
    } 
    elseif ($last_action === 'na_glowne') 
    {
        echo 'success'; // zielony dla przelewu z oszczędności na konto główne
    } 
    else 
    {
        echo '';
    }
?>">
    <?php
        if ($komunikat && in_array($last_action, ['wplata', 'wyplata', 'error', 'na_oszczednosci', 'na_glowne'])) 
        {
            echo htmlspecialchars($komunikat);
        } 
        else 
        {
            echo "&nbsp;";
        }
    ?>
</div>
    <form method="post" id="formGlowny">
        <div class="input-row">
            <label for="kwota_glowna">Kwota:</label>
            <input type="text" id="kwota_glowna" name="kwota" autocomplete="off" required pattern="\d+" title="Podaj poprawną kwotę (liczba całkowita).">
        </div>
        <div id="podglad_kwota_glowna" class="podglad"></div>
        <div class="btn-group">
        <button type="submit" name="wplata" class="btn btn-success">Wpłata</button>
        <button type="submit" name="wyplata" class="btn btn-danger">Wypłata</button>
    </div>
</form>
</div>
<!-- Konto oszczędnościowe -->
<div class="card">
<h3 class="<?php echo ($last_action === 'na_oszczednosci' || $last_action === 'na_glowne') ? 'pulse-purple' : ''; ?>">🏦 Konto oszczędnościowe</h3>
<p class="saldo"><?php echo $_SESSION['oszczednosci']; ?> PLN</p>
<div class="komunikat <?php
    if ($last_action === 'error') 
    {
        echo 'error';
    } 
    elseif ($last_action === 'na_oszczednosci') 
    {
        echo 'success';
    } 
    elseif ($last_action === 'na_glowne') 
    {
        echo 'error';
    } 
    else 
    {
        echo '';
    }
?>">
    <?php
        if ($komunikat && in_array($last_action, ['na_oszczednosci', 'na_glowne', 'error'])) 
        {
            echo htmlspecialchars($komunikat);
        } 
        else 
        {
            echo "&nbsp;";
        }
    ?>
</div>
<form method="post" id="formOszczednosciowy">
    <div class="input-row">
        <label for="kwota_oszczednosci">Kwota:</label>
        <input type="text" id="kwota_oszczednosci" name="kwota" autocomplete="off" required pattern="\d+" title="Podaj poprawną kwotę (liczba całkowita).">
    </div>
    <div id="podglad_kwota_oszczednosci" class="podglad"></div>
    <div class="btn-group">
        <button type="submit" name="przelej_na_oszczednosci" class="btn btn-primary">Wpłać na oszczędności</button>
        <button type="submit" name="przelej_na_glowne" class="btn btn-info">Przelej na konto główne</button>
    </div>
</form>
</div>
</div> 
<script> $(document).ready(function() 
{ 
    $('#kwota_glowna').on('input', function() 
    { 
        let val = $(this).val(); if (val === '') 
        { 
            $('#podglad_kwota_glowna').text(''); 
        } 
        else 
        { 
            $('#podglad_kwota_glowna').text('Podgląd kwoty: ' + val + ' PLN');
        } 
    }
); 
    $('#kwota_oszczednosci').on('input', function() 
    { let val = $(this).val(); if (val === '') 
        { 
            $('#podglad_kwota_oszczednosci').text(''); 
        } 
        else 
        { 
            $('#podglad_kwota_oszczednosci').text('Podgląd kwoty: ' + val + ' PLN'); 
        } 
    }
);
}); 
</script>
<script>
  document.addEventListener('DOMContentLoaded', function() 
  {
    // Konto główne - saldo
    const saldoGlownyEl = document.querySelector('.konto-container .card:first-child .saldo');
    // Konto oszczędnościowe - saldo
    const saldoOszczednosciEl = document.querySelector('.konto-container .card:last-child .saldo');
    const lastAction = '<?php echo $last_action; ?>';
    // Funkcja do podświetlenia elementu
    function podswietl(el, bgColor, textColor) 
    {
      el.style.backgroundColor = bgColor;
      el.style.color = textColor;
      el.style.padding = '10px';
      el.style.borderRadius = '10px';
      el.style.transition = 'background-color 0.5s ease';
      setTimeout(() => 
      {
        el.style.backgroundColor = '';
        el.style.color = '';
        el.style.padding = '';
        el.style.borderRadius = '';
      }, 3000);
    }
    // Konto główne
    if (lastAction === 'wplata') 
    {
      podswietl(saldoGlownyEl, '#d4edda', '#155724'); // zieleń
    } 
    else if (lastAction === 'wyplata') 
    {
      podswietl(saldoGlownyEl, '#f8d7da', '#721c24'); // czerwień
    }
    // Konto oszczędnościowe
    if (lastAction === 'na_oszczednosci') 
    {
      podswietl(saldoOszczednosciEl, '#d4edda', '#155724'); // zieleń przy przelewie na oszczędności
    } 
    else if (lastAction === 'na_glowne') 
    {
      podswietl(saldoOszczednosciEl, '#f8d7da', '#721c24'); // czerwień przy przelewie z oszczędności
    }
  });
</script>
</body> 
</html>     



            
        








