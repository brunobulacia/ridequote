<?php

require_once __DIR__ . '/CotizacionTemplate.php';

class CotizacionEstandar extends CotizacionTemplate
{
    protected function calcularRecargos(): float
    {
        return 0.0;
    }

    protected function formatearRecibo(): array
    {
        return [
            'tipo' => 'estandar',
            'ruta' => $this->viaje['nombre'],
            'km' => $this->viaje['km'],
            'tarifa' => $this->tarifa['nombre'],
            'precio_base' => round($this->precioBase, 2),
            'recargo' => 0.0,
            'precio_final' => round($this->precioFinal, 2),
        ];
    }
}