<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../src/DB.php';

class RutasController
{
    public function handle(): void
    {
        header('Content-Type: application/json');

        $stmt = DB::getInstance()->query('SELECT * FROM rutas ORDER BY id');
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}

(new RutasController())->handle();
