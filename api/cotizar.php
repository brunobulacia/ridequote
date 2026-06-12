<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../src/DB.php';
require_once __DIR__ . '/../src/Strategy/TarifaStrategy.php';
require_once __DIR__ . '/../src/Strategy/TarifaNormal.php';
require_once __DIR__ . '/../src/Strategy/TarifaNocturna.php';
require_once __DIR__ . '/../src/Strategy/TarifaLluvia.php';
require_once __DIR__ . '/../src/Strategy/TarifaFeriado.php';
require_once __DIR__ . '/../src/Strategy/TarifaVIP.php';
require_once __DIR__ . '/../src/Template/CotizacionTemplate.php';
require_once __DIR__ . '/../src/Template/CotizacionEstandar.php';
require_once __DIR__ . '/../src/Template/CotizacionAeropuerto.php';

class CotizadorController
{
    private array $strategies = [];

    public function __construct()
    {
        $this->strategies = [
            'normal'   => new TarifaNormal(),
            'nocturna' => new TarifaNocturna(),
            'lluvia'   => new TarifaLluvia(),
            'feriado'  => new TarifaFeriado(),
            'vip'      => new TarifaVIP(),
        ];
    }

    public function handle(): void
    {
        header('Content-Type: application/json');

        $input        = json_decode(file_get_contents('php://input'), true);
        $rutaId       = (int)   ($input['ruta_id'] ?? 0);
        $tarifaNombre = (string)($input['tarifa']  ?? '');
        $preview      = (bool)  ($input['preview'] ?? false);

        $db = DB::getInstance();

        $stmt = $db->prepare('SELECT * FROM tarifas WHERE nombre = ?');
        $stmt->execute([$tarifaNombre]);
        $tarifaData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tarifaData) {
            http_response_code(400);
            echo json_encode(['error' => 'Tarifa no válida']);
            return;
        }

        $stmt = $db->prepare('SELECT tipo FROM rutas WHERE id = ?');
        $stmt->execute([$rutaId]);
        $rutaData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$rutaData) {
            http_response_code(400);
            echo json_encode(['error' => 'Ruta no válida']);
            return;
        }

        $strategy   = $this->strategies[$tarifaNombre];
        $cotizacion = ($rutaData['tipo'] === 'aeropuerto')
            ? new CotizacionAeropuerto()
            : new CotizacionEstandar();

        $cotizacion->setStrategy($strategy);
        $cotizacion->setPreview($preview);

        try {
            $recibo = $cotizacion->procesarCotizacion($rutaId, $tarifaData['id']);
            echo json_encode($recibo);
        } catch (Exception $e) {
            http_response_code(409);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}

(new CotizadorController())->handle();
