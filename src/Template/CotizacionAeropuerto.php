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
        $precioNormal  = round($this->viaje['km'] * $this->tarifa['precio_km'], 2);
        $recargoTarifa = round($this->precioBase - $precioNormal, 2);
        $recargoTotal  = round($recargoTarifa + $this->recargo, 2);

        $nota = 'Tasa aeroportuaria ($5.00)';
        if ($recargoTarifa > 0) {
            $nota .= ' + ' . $this->notaRecargo();
        }

        return [
            'tipo'         => 'aeropuerto',
            'ruta'         => $this->viaje['nombre'],
            'km'           => $this->viaje['km'],
            'tarifa'       => $this->tarifa['nombre'],
            'precio_base'  => $precioNormal,
            'recargo'      => $recargoTotal,
            'recargo_nota' => $nota,
            'precio_final' => round($this->precioFinal, 2),
        ];
    }
}
