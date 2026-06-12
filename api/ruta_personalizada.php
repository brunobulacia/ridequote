<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../src/DB.php';
require_once __DIR__ . '/../src/DistanciaService.php';
require_once __DIR__ . '/../src/Classifier.php';

class RutaPersonalizadaController
{
    private DistanciaService $distanciaService;
    private Classifier $classifier;

    public function __construct()
    {
        $this->distanciaService = new DistanciaService();
        $this->classifier       = new Classifier();
    }

    public function handle(): void
    {
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true);

        $latOrigen  = (float)($input['origin_lat'] ?? 0);
        $lonOrigen  = (float)($input['origin_lon'] ?? 0);
        $latDestino = (float)($input['dest_lat']   ?? 0);
        $lonDestino = (float)($input['dest_lon']   ?? 0);

        if (!$latOrigen || !$latDestino) {
            http_response_code(400);
            echo json_encode(['error' => 'Coordenadas inválidas']);
            return;
        }

        $nombreOrigen  = !empty($input['origin_name']) ? $input['origin_name'] : $this->reverseGeocode($latOrigen,  $lonOrigen);
        $nombreDestino = !empty($input['dest_name'])   ? $input['dest_name']   : $this->reverseGeocode($latDestino, $lonDestino);

        $km   = $this->distanciaService->calcularKm($latOrigen, $lonOrigen, $latDestino, $lonDestino);
        $tipo = $this->classifier->clasificar($nombreOrigen, $nombreDestino);

        $nombre = "{$nombreOrigen} → {$nombreDestino}";

        $db   = DB::getInstance();
        $stmt = $db->prepare(
            'INSERT INTO rutas (nombre, origen, destino, km, tipo) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$nombre, $nombreOrigen, $nombreDestino, $km, $tipo]);
        $rutaId = (int) $db->lastInsertId();

        echo json_encode([
            'ruta_id' => $rutaId,
            'nombre'  => $nombre,
            'km'      => $km,
            'tipo'    => $tipo,
        ]);
    }

    private function reverseGeocode(float $lat, float $lon): string
    {
        $url = "https://nominatim.openstreetmap.org/reverse?lat={$lat}&lon={$lon}&format=json";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_USERAGENT      => 'RideQuote/1.0',
        ]);
        $response = curl_exec($ch);

        if (!$response) return "Punto ({$lat}, {$lon})";

        $data  = json_decode($response, true);
        $parts = explode(',', $data['display_name'] ?? "Punto ({$lat}, {$lon})");

        return trim($parts[0]) . (isset($parts[1]) ? ', ' . trim($parts[1]) : '');
    }
}

new RutaPersonalizadaController()->handle();
