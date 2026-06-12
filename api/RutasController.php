<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../src/Model/RutaModel.php';

class RutasController
{
    private RutaModel $rutaModel;

    public function __construct()
    {
        $this->rutaModel = new RutaModel();
    }

    public function handle(): void
    {
        header('Content-Type: application/json');

        echo json_encode($this->rutaModel->get());
    }
}

(new RutasController())->handle();
