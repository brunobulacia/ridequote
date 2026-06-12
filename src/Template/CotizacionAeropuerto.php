<?php

require_once __DIR__ . '/CotizacionTemplate.php';

class CotizacionAeropuerto extends CotizacionTemplate
{
    protected function calcularRecargos(): float
    {
        return 5.00;
    }

    protected function formatearRecibo(): array
    {
        return [
            'tipo' => 'aeropuerto',
            'ruta' => $this->viaje['nombre'],
            'km' => $this->viaje['km'],
            'tarifa' => $this->tarifa['nombre'],
            'precio_base' => round($this->precioBase, 2),
            'recargo' => $this->recargo,
            'recargo_nota' => 'Tasa aeroportuaria',
            'precio_final' => round($this->precioFinal, 2),
        ];
    }
}