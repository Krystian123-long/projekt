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
if ($_SERVER["REQUEST_METHOD"] == "POST") 
{
    if (isset($_POST['kwota']) && is_numeric($_POST['kwota']) && $_POST['kwota'] > 0) 
    {
        $kwota = (int)$_POST['kwota'];
        if (isset($_POST['wplata'])) 
        {
            wplata($kwota);
        } elseif (isset($_POST['wyplata'])) 
        {
            wyplata($kwota);
        }
    } 
    else 
    {
        $_SESSION['komunikat'] = "Podaj poprawną kwotę!";
        $_SESSION['last_action'] = 'error';
    }
    header("Location: ". $_SERVER['PHP_SELF']);
    exit();
}
$komunikat = isset($_SESSION['komunikat']) ? $_SESSION['komunikat'] : "";
$last_action = isset($_SESSION['last_action']) ? $_SESSION['last_action'] : "";
unset($_SESSION['komunikat']);
unset($_SESSION['last_action']);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Zapewnia responsywność -->
    <title>Konto bankowe</title>    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>   
    <style>
        body 
        {
            background: linear-gradient(135deg, #e0eafc, #cfdef3);
            font-family: Arial, sans-serif;
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
        .highlight-success 
        {
            background-color: #d4edda !important;
            transition: background-color 1s ease;
            border-radius: 5px;
            padding: 5px;
        }
        .highlight-danger 
        {
            background-color: #f8d7da !important;
            transition: background-color 1s ease;
            border-radius: 5px;
            padding: 5px;
        }
        @media (max-width: 576px) 
        {
            .card 
            {
                width: 100%;
                margin: 10px;
            }
            .card-body 
            {
                padding: 1.5rem;
            }
            h3.card-title 
            {
                font-size: 1.5rem;
            }
            .form-label 
            {
                font-size: 0.9rem;
            }
            .btn 
            {
                font-size: 0.9rem;
            }
        }
        @media (min-width: 577px) and (max-width: 768px) 
        {
            .card 
            {
                width: 80%;
            }
        }
        @media (min-width: 769px) 
        {
            .card 
            {
                width: 30%;
            }
        }
    </style>
</head>
<body class="d-flex justify-content-center align-items-center vh-100">
    <div class="card shadow-lg p-3">
        <div class="card-body text-center">
            <h3 class="card-title mb-3">💰 Konto bankowe</h3>
            <p class="card-text fw-bold">
                Saldo: <span id="saldo" class="text-primary"><?php echo $_SESSION['saldo']; ?> PLN</span>
            </p>
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
        $(document).ready(function() 
        {
            $('#kwota').on('input', function() 
            {
                let value = $(this).val().trim();
                if (value && !isNaN(value) && Number(value) > 0) 
                {
                    $('#preview').text(`Kwota do operacji: ${value} PLN`).css('color', 'green');
                } 
                else 
                {
                    $('#preview').text('');
                }
            });
            $('#bankForm').on('submit', function(e) 
            {
                let value = $('#kwota').val().trim();
                if (isNaN(value) || Number(value) <= 0) 
                {
                    e.preventDefault();
                    alert('Podaj poprawną kwotę większą od zera!');
                    $('#kwota').focus();
                }
            });
            <?php if ($last_action === 'wplata'): ?>
                $('#saldo').addClass('highlight-success');
                setTimeout(() => {
                    $('#saldo').removeClass('highlight-success');
                }, 1500);
            <?php elseif($last_action === 'wyplata'): ?>
                $('#saldo').addClass('highlight-danger');
                setTimeout(() => {
                    $('#saldo').removeClass('highlight-danger');
                }, 1500);
            <?php endif; ?>
        });
    </script>
</body>
</html>