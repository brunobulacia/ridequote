<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../src/Model/CotizacionModel.php';

class HistorialController
{
    private CotizacionModel $cotizacionModel;

    public function __construct()
    {
        $this->cotizacionModel = new CotizacionModel();
    }

    public function handle(): void
    {
        header('Content-Type: application/json');

        echo json_encode($this->cotizacionModel->obtenerHistorial(10));
    }
}

(new HistorialController())->handle();
