<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../src/DB.php';

class HistorialController
{
    public function handle(): void
    {
        header('Content-Type: application/json');

        $stmt = DB::getInstance()->query(
            'SELECT c.id, r.nombre AS ruta, t.nombre AS tarifa,
                    c.km, c.precio_base, c.recargo, c.precio_final, c.created_at
             FROM cotizaciones c
             JOIN rutas   r ON r.id = c.ruta_id
             JOIN tarifas t ON t.id = c.tarifa_id
             ORDER BY c.id DESC
             LIMIT 10'
        );

        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}

(new HistorialController())->handle();
