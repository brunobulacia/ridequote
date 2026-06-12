<?php
interface TarifaStrategy
{
    public function calcular(float $km, array $tarifa): float;
}