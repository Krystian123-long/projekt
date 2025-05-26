<?php
session_start();
interface AccountInterface
{
    public function deposit(int $amount): void;
    public function withdraw(int $amount): void;
    public function transferTo(Account $target, int $amount): void;
    public function getBalance(): int;
}
class Account implements AccountInterface
{
    private string $sessionKey;
    private string $cookieKey;
    public function __construct(string $sessionKey, string $cookieKey, int $initial = 0)
    {
        $this->sessionKey = $sessionKey;
        $this->cookieKey = $cookieKey;
        if (!isset($_SESSION[$this->sessionKey])) 
        {
            $_SESSION[$this->sessionKey] = $initial;
            $this->updateCookie();
        } 
        elseif (!isset($_COOKIE[$this->cookieKey])) 
        {
            $this->updateCookie();
        }
    }
    private function updateCookie(): void
    {
        setcookie($this->cookieKey, $_SESSION[$this->sessionKey], time() + 86400, "/");
    }
    public function deposit(int $amount): void
    {
        $_SESSION[$this->sessionKey] += $amount;
        setcookie($this->cookieKey, $_SESSION[$this->sessionKey], time() + 86400, "/");
        $_SESSION['komunikat'] = "Wpłacono $amount PLN. Nowe saldo: " . $this->getBalance() . " PLN.";
        $_SESSION['last_action'] = 'wplata';
    }
    public function withdraw(int $amount): void
    {
        if ($amount > $this->getBalance()) 
        {
            $_SESSION['komunikat'] = "Brak wystarczających środków na koncie!";
            $_SESSION['last_action'] = 'error';
        } 
        else 
        {
            $_SESSION[$this->sessionKey] -= $amount;
            setcookie($this->cookieKey, $_SESSION[$this->sessionKey], time() + 86400, "/");
            $_SESSION['komunikat'] = "Wypłacono $amount PLN. Nowe saldo: " . $this->getBalance() . " PLN.";
            $_SESSION['last_action'] = 'wyplata';
        }
    }
    public function transferTo(Account $target, int $amount): void
    {
        if ($amount > $this->getBalance()) 
        {
            $_SESSION['komunikat'] = "Nie masz wystarczających środków na transfer.";
            $_SESSION['last_action'] = 'error';
        } 
        else 
        {
            $_SESSION[$this->sessionKey] -= $amount;            
            $_SESSION[$target->sessionKey] += $amount;
            setcookie($this->cookieKey, $_SESSION[$this->sessionKey], time() + 86400, "/");
            setcookie($this->cookieKey, $_SESSION[$this->sessionKey], time() + 86400, "/");
            $_SESSION['komunikat'] = "Przelano $amount PLN z {$this->sessionKey} na {$target->sessionKey}.";
            $_SESSION['last_action'] = $this->sessionKey === 'saldo' ? 'na_oszczednosci' : 'na_glowne';
        }
    }
    public function getBalance(): int
    {
        return $_SESSION[$this->sessionKey];
    }
}
$kontoGlowne = new Account('saldo', 'cookie_saldo', 1000);
$kontoOszczednosci = new Account('oszczednosci', 'cookie_oszczednosci', 500);
if ($_SERVER["REQUEST_METHOD"] == "POST") 
{
    if (isset($_POST['kwota']) && is_numeric($_POST['kwota']) && $_POST['kwota'] > 0) 
    {
        $kwota = (int)$_POST['kwota'];
        if (isset($_POST['wplata'])) 
        {
            $kontoGlowne->deposit($kwota);
        } 
        elseif (isset($_POST['wyplata'])) 
        {
            $kontoGlowne->withdraw($kwota);
        } 
        elseif (isset($_POST['przelej_na_oszczednosci'])) 
        {
            $kontoGlowne->transferTo($kontoOszczednosci, $kwota);
        } 
        elseif (isset($_POST['przelej_na_glowne'])) 
        {
            $kontoOszczednosci->transferTo($kontoGlowne, $kwota);
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
    <link rel="stylesheet" href="style.css">    
</head>
<body>
<div class="konto-container">
    <div class="card">
    <h3 class="<?php echo ($last_action === 'wplata' || $last_action === 'wyplata') ? 'pulse-yellow' : ''; ?>">💰 Konto główne</h3>
    <p class="saldo"><?php echo $kontoGlowne->getBalance(); ?> PLN</p> 
    <div class="komunikat <?php 
    if ($last_action === 'error' || $last_action === 'na_oszczednosci') 
    {
        echo 'error'; 
    } 
    elseif ($last_action === 'wplata') 
    {
        echo 'success'; 
    } 
    elseif ($last_action === 'wyplata') 
    {
        echo 'error';  
    } 
    elseif ($last_action === 'na_glowne') 
    {
        echo 'success'; 
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
<div class="card">
<h3 class="<?php echo ($last_action === 'na_oszczednosci' || $last_action === 'na_glowne') ? 'pulse-purple' : ''; ?>">🏦 Konto oszczędnościowe</h3>
<p class="saldo"><?php echo $kontoOszczednosci->getBalance(); ?> PLN</p>
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
    const saldoGlownyEl = document.querySelector('.konto-container .card:first-child .saldo');
    const saldoOszczednosciEl = document.querySelector('.konto-container .card:last-child .saldo');
    const lastAction = '<?php echo $last_action; ?>';
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
    if (lastAction === 'wplata') 
    {
      podswietl(saldoGlownyEl, '#d4edda', '#155724'); 
    } 
    else if (lastAction === 'wyplata') 
    {
      podswietl(saldoGlownyEl, '#f8d7da', '#721c24'); 
    }    
    if (lastAction === 'na_oszczednosci') 
    {
      podswietl(saldoOszczednosciEl, '#d4edda', '#155724'); 
    } 
    else if (lastAction === 'na_glowne') 
    {
      podswietl(saldoOszczednosciEl, '#f8d7da', '#721c24'); 
    }
  });
</script>
</body> 
</html>                                                                                                                                                                                                                                      



            
        








