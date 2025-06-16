<?php
session_start();
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['kwota'])) 
{
    setcookie('ostatnia_kwota', $_POST['kwota'], time() + 3600, "/");
}
require_once 'db.php';
require_once 'AccountInterface.php';
class Account implements AccountInterface
{
    private PDO $pdo;
    private string $name;
    public function __construct(PDO $pdo, string $name)
    {
        $this->pdo = $pdo;
        $this->name = $name;
        //Sprawdza, czy konto istnieje – jeśli nie, tworzy je z saldem 0
        $this->initialize();
    }
    private function initialize(): void
    {
        $stmt = $this->pdo->prepare("SELECT saldo FROM konta WHERE nazwa = ?");
        $stmt->execute([$this->name]);
        if (!$stmt->fetch()) 
        {
            $insert = $this->pdo->prepare("INSERT INTO konta (nazwa, saldo) VALUES (?, ?)");
            $insert->execute([$this->name, 0]);
        }
    }
    public function deposit(int $amount): void
    {
        $stmt = $this->pdo->prepare("UPDATE konta SET saldo = saldo + ? WHERE nazwa = ?");
        $stmt->execute([$amount, $this->name]);
        //Komunikat wyświetlany po operacji
        $_SESSION['komunikat'] = "Wpłacono $amount PLN. Nowe saldo: " . $this->getBalance() . " PLN.";
        $_SESSION['last_action'] = 'wplata';
    }
    public function withdraw(int $amount): void
    {
        $saldo = $this->getBalance();
        if ($amount > $saldo) 
        {
            $_SESSION['komunikat'] = "Brak wystarczających środków na koncie!";
            $_SESSION['last_action'] = 'error';
        } 
        else 
        {
            $stmt = $this->pdo->prepare("UPDATE konta SET saldo = saldo - ? WHERE nazwa = ?");
            $stmt->execute([$amount, $this->name]);
            $_SESSION['komunikat'] = "Wypłacono $amount PLN. Nowe saldo: " . $this->getBalance() . " PLN.";
            $_SESSION['last_action'] = 'wyplata';
        }
    }
    public function transferTo(Account $target, int $amount): void
    {
        $saldo = $this->getBalance();
        if ($amount > $saldo) {
            $_SESSION['komunikat'] = "Nie masz wystarczających środków na transfer.";
            $_SESSION['last_action'] = 'error';
        } 
        else 
        {
            //Początek transakcji – jeśli coś się nie uda, cofnie wszystko
            $this->pdo->beginTransaction();
            try 
            {
                $this->withdraw($amount);
                $target->deposit($amount);
                $this->pdo->commit();
                $_SESSION['komunikat'] = "Przelano $amount PLN z {$this->name} na {$target->name}.";
                $_SESSION['last_action'] = $this->name === 'saldo' ? 'na_oszczednosci' : 'na_glowne';
            } 
            catch (Exception $e) 
            {
                $this->pdo->rollBack();
                $_SESSION['komunikat'] = "Błąd podczas transferu.";
                $_SESSION['last_action'] = 'error';
            }
        }
    }
    // Zwraca bieżące saldo konta 
    public function getBalance(): int
    {
        $stmt = $this->pdo->prepare("SELECT saldo FROM konta WHERE nazwa = ?");
        $stmt->execute([$this->name]);
        return (int)$stmt->fetchColumn();
    }
}
$kontoGlowne = new Account($pdo, 'saldo');
$kontoOszczednosci = new Account($pdo, 'oszczednosci');
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
// Czyszczenie sesji z komunikatów po odczytaniu
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
    <link rel="stylesheet" href="my-app/src/index.css">  
</head>
<body>
<div class="konto-container">
    <div class="card">
    <h3 class="<?php echo ($last_action === 'wplata' || $last_action === 'wyplata') ? 'pulse-yellow' : ''; ?>">💰 Konto główne</h3>
    <p class="saldo"><?php echo $kontoGlowne->getBalance(); ?> PLN</p> 
    <div class="komunikat
<?php 
    // Komunikat zależny od rodzaju akcji użytkownika, np. błąd, sukces
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
<!-- Formularz dla konta głównego (wpłata / wypłata) -->
    <form method="post" id="formGlowny">
        <div class="input-row">
            <label for="kwota_glowna">Kwota:</label>
            <input type="text" id="kwota_glowna" name="kwota" class="form-control" required>
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
<!-- Komunikat w zależności od operacji dotyczącej konta oszczędnościowego -->
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
<!-- Formularz do przelewów między kontami -->
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
// Obsługa pola kwoty w koncie głównym – nasłuchiwanie zmian w polu tekstowym
    $('#kwota_glowna').on('input', function() 
    { 
        let val = $(this).val(); 
        if (val === '') 
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
    { 
        let val = $(this).val(); 
        // Jeśli pole puste, czyści podgląd
        if (val === '')     
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
    // Pobranie elementów z aktualnym saldem konta głównego i oszczędnościowego
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
    // Jeśli przelew na oszczędności – podświetl saldo konta oszczędnościowego na zielono
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



            
        








