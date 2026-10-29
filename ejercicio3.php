<?php
/**
 * Ejercicio 3: Procesamiento GET, Tabla de Amortizacion y Cifrado OpenSSL
 */
require_once 'includes/seguridad.php';
require_once 'includes/CalculadoraFinanciera.php';

// TODO: Sanitizar entradas recibidas por GET
$semilla = htmlspecialchars(trim($_GET['cedula_semilla'] ?? '000'));
$monto   = (float)filter_var($_GET['monto'] ?? 1000, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
$tasa    = (float)filter_var($_GET['tasa'] ?? 12, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
$plazo   = (int)filter_var($_GET['plazo'] ?? 12, FILTER_SANITIZE_NUMBER_INT);

$mensajeError = "";
$cuota = 0.0;
try {
    $calc = new CalculadoraFinanciera($monto, $tasa, $plazo);
    $cuota = $calc->calcularCuotaMensual();
} catch (InvalidArgumentException $e) {
    $mensajeError = $e->getMessage();
    $plazo = 0;
}

// TODO: Cifrado Simetrico con OpenSSL (AES-256-CBC)
$claveCifrado = hash('sha256', $semilla);
$iv = substr(hash('sha256', 'UTP_IV_' . $semilla), 0, 16);
$textoPlano = "Monto: {$monto} | Cuota: {$cuota}";
$textoCifrado = openssl_encrypt($textoPlano, 'AES-256-CBC', $claveCifrado, 0, $iv);

// TODO: Generacion de Tabla de Amortizacion con Bucle for
$saldo = $monto;
$r = ($tasa / 100) / 12;
$tablaAmortizacion = [];

for ($mes = 1; $mes <= $plazo; $mes++) {
    $interesMes = round($saldo * $r, 2);
    $principalMes = round($cuota - $interesMes, 2);
    $cuotaMes = $cuota;
    // En el ultimo mes se paga todo el saldo pendiente para que quede en 0
    if ($mes === $plazo) {
        $principalMes = $saldo;
        $cuotaMes = round($interesMes + $principalMes, 2);
    }
    $saldo = round($saldo - $principalMes, 2);
    if ($saldo < 0) $saldo = 0;

    $tablaAmortizacion[] = [
        'mes' => $mes,
        'cuota' => $cuotaMes,
        'interes' => $interesMes,
        'principal' => $principalMes,
        'saldo' => $saldo
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Laboratorio #10 - Ejercicio 3</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; background-color: #f8f9fa; }
        .container { max-width: 800px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #dee2e6; padding: 8px; text-align: center; }
        th { background-color: #0056b3; color: white; }
        .cifrado-box { background: #e2e3e5; padding: 10px; word-break: break-all; font-family: monospace; border-radius: 4px; margin-top: 15px; }
    </style>
</head>
<body>
<div class="container">
    <h2>Tabla de Amortizacion (Procesamiento GET)</h2>
    <?php if ($mensajeError): ?>
        <p style="color: red;"><?php echo htmlspecialchars($mensajeError, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>
    <p><strong>Semilla de Cedula:</strong> <?php echo $semilla; ?></p>
    <p><strong>Resumen Cifrado (AES-256-CBC):</strong></p>
    <div id="cifrado-resultado" class="cifrado-box"><?php echo htmlspecialchars((string)$textoCifrado, ENT_QUOTES, 'UTF-8'); ?></div>

    <table>
        <thead>
            <tr>
                <th>Mes</th>
                <th>Cuota ($)</th>
                <th>Interes ($)</th>
                <th>Principal ($)</th>
                <th>Saldo Restante ($)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tablaAmortizacion as $row): ?>
            <tr>
                <td><?php echo $row['mes']; ?></td>
                <td><?php echo number_format($row['cuota'], 2); ?></td>
                <td><?php echo number_format($row['interes'], 2); ?></td>
                <td><?php echo number_format($row['principal'], 2); ?></td>
                <td><?php echo number_format($row['saldo'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>