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
        $precioNormal  = round($this->viaje['km'] * $this->tarifa['precio_km'], 2);
        $recargoTarifa = round($this->precioBase - $precioNormal, 2);

        return [
            'tipo'         => 'estandar',
            'ruta'         => $this->viaje['nombre'],
            'km'           => $this->viaje['km'],
            'tarifa'       => $this->tarifa['nombre'],
            'precio_base'  => $precioNormal,
            'recargo'      => $recargoTarifa,
            'recargo_nota' => $recargoTarifa > 0 ? $this->notaRecargo() : '',
            'precio_final' => round($this->precioFinal, 2),
        ];
    }
}
