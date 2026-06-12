<?php

class DistanciaService
{
    private string $osrmBase = 'http://router.project-osrm.org/route/v1/driving';

    public function calcularKm(float $latOrigen, float $lonOrigen, float $latDestino, float $lonDestino): float
    {
        $url = "{$this->osrmBase}/{$lonOrigen},{$latOrigen};{$lonDestino},{$latDestino}?overview=false";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_USERAGENT      => 'RideQuote/1.0',
        ]);
        $response = curl_exec($ch);

        if (!$response) return 0.0;

        $data   = json_decode($response, true);
        $meters = $data['routes'][0]['distance'] ?? 0;

        return round($meters / 1000, 2);
    }
}
