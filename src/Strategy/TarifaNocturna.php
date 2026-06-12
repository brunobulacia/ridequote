<?php

require_once __DIR__ . '/TarifaStrategy.php';

class TarifaNocturna implements TarifaStrategy
{
    public function calcular(float $km, array $tarifa): float
    {
        return $km * $tarifa['precio_km'] * $tarifa['multiplicador'];
    }
}
