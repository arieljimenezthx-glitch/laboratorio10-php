<?php
/**
 * Ejercicio 2: Formulario POST Seguro con CSRF y Sanitizacion
 */
session_start();

// TODO: Incluir archivos de biblioteca mediante require_once
require_once 'includes/seguridad.php';
require_once 'includes/CalculadoraFinanciera.php';

// TODO: Gestionar Token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$mensajeError = "";
$resultadoJSON = null;
$valores = ['cedula_semilla' => '', 'monto' => '', 'tasa' => '', 'plazo' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // TODO: Validar Token CSRF enviado
    $tokenEnviado = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $tokenEnviado)) {
        die("Error de validacion CSRF: Token invalido o expirado.");
    }
    // Token de un solo uso: se renueva despues de cada envio valido
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    // TODO: Sanitizar entradas POST
    $semilla = htmlspecialchars(trim($_POST['cedula_semilla'] ?? '000'));
    $monto   = filter_var($_POST['monto'] ?? 0, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $tasa    = filter_var($_POST['tasa'] ?? 0, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $plazo   = filter_var($_POST['plazo'] ?? 0, FILTER_SANITIZE_NUMBER_INT);
    $valores = ['cedula_semilla' => $semilla, 'monto' => $monto, 'tasa' => $tasa, 'plazo' => $plazo];

    if (!preg_match('/^\d{3}$/', $semilla)) {
        $mensajeError = "La semilla debe tener exactamente 3 digitos.";
    } elseif ((float)$monto < 100 || (float)$tasa < 1 || (int)$plazo < 1) {
        $mensajeError = "El monto minimo es 100, la tasa minima 1% y el plazo minimo 1 mes.";
    } elseif ($monto > 0 && $tasa > 0 && $plazo > 0) {
        try {
            $calc = new CalculadoraFinanciera((float)$monto, (float)$tasa, (int)$plazo);
            $cuota = $calc->calcularCuotaMensual();
            $interesTotal = $calc->calcularInteresTotal();

            $resultadoData = [
                'semilla'       => $semilla,
                'cuota'         => $cuota,
                'interes_total' => $interesTotal
            ];

            $resultadoJSON = json_encode($resultadoData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        } catch (Exception $e) {
            $mensajeError = $e->getMessage();
        }
    } else {
        $mensajeError = "Por favor, ingrese valores validos en todos los campos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Laboratorio #10 - Ejercicio 2</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; background-color: #f4f4f9; }
        .container { max-width: 600px; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="number"] { width: 100%; padding: 8px; box-sizing: border-box; }
        button { background: #0056b3; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; }
        .error { color: red; margin-bottom: 15px; }
        .resultado { background: #e9ecef; padding: 15px; border-radius: 4px; margin-top: 20px; }
    </style>
</head>
<body>
<div class="container">
    <h2>Calculadora Financiera - Formulario POST Seguro</h2>
    <?php if ($mensajeError): ?>
        <div class="error"><?php echo htmlspecialchars($mensajeError, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form action="ejercicio2.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
        
        <div class="form-group">
            <label for="cedula_semilla">Ultimos 3 digitos de Cedula (Semilla):</label>
            <input type="text" id="cedula_semilla" name="cedula_semilla" required maxlength="3" pattern="\d{3}" value="<?php echo htmlspecialchars((string)$valores['cedula_semilla'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="form-group">
            <label for="monto">Monto del Prestamo ($):</label>
            <input type="number" id="monto" name="monto" step="0.01" required min="100" value="<?php echo htmlspecialchars((string)$valores['monto'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="form-group">
            <label for="tasa">Tasa de Interes Anual (%):</label>
            <input type="number" id="tasa" name="tasa" step="0.1" required min="1" value="<?php echo htmlspecialchars((string)$valores['tasa'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="form-group">
            <label for="plazo">Plazo en Meses:</label>
            <input type="number" id="plazo" name="plazo" required min="1" value="<?php echo htmlspecialchars((string)$valores['plazo'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <button type="submit">Calcular Cuota</button>
    </form>

    <?php if ($resultadoJSON): ?>
        <div class="resultado">
            <h3>Resultado del Calculo</h3>
            <?php $res = json_decode($resultadoJSON, true); ?>
            <p><strong>Cuota Mensual:</strong> $<?php echo number_format($res['cuota'], 2); ?></p>
            <p><strong>Interes Total:</strong> $<?php echo number_format($res['interes_total'], 2); ?></p>
            <?php $enlaceEj3 = 'ejercicio3.php?' . http_build_query(['cedula_semilla' => $valores['cedula_semilla'], 'monto' => $valores['monto'], 'tasa' => $valores['tasa'], 'plazo' => $valores['plazo']]); ?>
            <p><a href="<?php echo htmlspecialchars($enlaceEj3, ENT_QUOTES, 'UTF-8'); ?>">Ver tabla de amortizacion (Ejercicio 3)</a></p>
        </div>
        <!-- Bloque estructurado exigido para validacion del Autograde -->
        <script type="application/json" id="resultado-ej2">
            <?php echo $resultadoJSON; ?>
        </script>
    <?php endif; ?>
</div>
</body>
</html>