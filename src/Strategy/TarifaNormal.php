<?php

require_once __DIR__ . '/TarifaStrategy.php';

class TarifaNormal implements TarifaStrategy
{
    public function calcular(float $km, array $tarifa): float
    {
        return $km * $tarifa['precio_km'];
    }
}