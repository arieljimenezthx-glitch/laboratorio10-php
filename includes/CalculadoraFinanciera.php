<?php
/**
 * Biblioteca de Clases Financieras
 */

// TODO: Ejercicio 1.1 - Implementar verificacion de constante ACCESO_PERMITIDO
if (!defined('ACCESO_PERMITIDO')) {
    http_response_code(403);
    die("Acceso directo no permitido.");
}

/**
 * Calcula cuotas e intereses de un prestamo con amortizacion francesa.
 * Integrantes: Ariel Jimenez y Alejandro Saenz
 */
class CalculadoraFinanciera {
    // TODO: Ejercicio 1.2 - Declarar miembros privados ($montoBase, $tasaAnual, $plazoMeses)
    /** @var float Monto del prestamo */
    private float $montoBase;
    /** @var float Tasa de interes anual en porcentaje */
    private float $tasaAnual;
    /** @var int Plazo del prestamo en meses */
    private int $plazoMeses;

    // TODO: Ejercicio 1.3 - Implementar Constructor con validacion
    public function __construct(float $monto, float $tasa, int $plazo) {
        if ($monto <= 0 || $tasa <= 0 || $plazo <= 0) {
            throw new InvalidArgumentException("Los parametros deben ser estrictamente mayores a cero.");
        }
        $this->montoBase = $monto;
        $this->tasaAnual = $tasa;
        $this->plazoMeses = $plazo;
    }

    // Getters
    public function getMontoBase(): float { return $this->montoBase; }
    public function getTasaAnual(): float { return $this->tasaAnual; }
    public function getPlazoMeses(): int { return $this->plazoMeses; }

    // TODO: Ejercicio 1.4 - Implementar calcularCuotaMensual()
    public function calcularCuotaMensual(): float {
        return $this->cuotaParaPlazo($this->plazoMeses);
    }

    // Formula de amortizacion francesa para un plazo de $n meses
    private function cuotaParaPlazo(int $n): float {
        $r = ($this->tasaAnual / 100) / 12;
        $cuota = $this->montoBase * ($r * pow(1 + $r, $n)) / (pow(1 + $r, $n) - 1);
        return round($cuota, 2);
    }

    // TODO: Ejercicio 1.5 - Implementar sobrecarga dinamica con __call
    /**
     * Sobrecarga dinamica: calcularInteresTotal([int $plazo]) devuelve el interes total.
     * Cualquier otro metodo inexistente lanza BadMethodCallException.
     */
    public function __call(string $name, array $arguments) {
        if ($name === 'calcularInteresTotal') {
            $plazoEfectivo = isset($arguments[0]) && is_int($arguments[0]) ? $arguments[0] : $this->plazoMeses;
            if ($plazoEfectivo <= 0) {
                throw new InvalidArgumentException("El plazo efectivo debe ser mayor a cero.");
            }
            $cuota = $this->cuotaParaPlazo($plazoEfectivo);
            $totalPagado = $cuota * $plazoEfectivo;
            return round($totalPagado - $this->montoBase, 2);
        }

        throw new BadMethodCallException("El metodo '$name' no existe en la clase CalculadoraFinanciera.");
    }
}