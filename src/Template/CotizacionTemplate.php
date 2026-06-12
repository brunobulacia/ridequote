<?php

require_once __DIR__ . '/../Model/RutaModel.php';
require_once __DIR__ . '/../Model/TarifaModel.php';
require_once __DIR__ . '/../Model/CotizacionModel.php';
require_once __DIR__ . '/../Model/ConductorModel.php';
require_once __DIR__ . '/../Strategy/TarifaStrategy.php';

abstract class CotizacionTemplate
{
    private TarifaStrategy $strategy;
    private bool $preview = false;

    protected RutaModel $rutaModel;
    protected TarifaModel $tarifaModel;
    protected CotizacionModel $cotizacionModel;
    protected ConductorModel $conductorModel;

    protected array $viaje = [];
    protected array $tarifa = [];
    protected float $precioBase = 0.0;
    protected float $recargo = 0.0;
    protected float $precioFinal = 0.0;

    public function __construct()
    {
        $this->rutaModel       = new RutaModel();
        $this->tarifaModel     = new TarifaModel();
        $this->cotizacionModel = new CotizacionModel();
        $this->conductorModel  = new ConductorModel();
    }

    public function setStrategy(TarifaStrategy $strategy): void
    {
        $this->strategy = $strategy;
    }

    public function setPreview(bool $preview): void
    {
        $this->preview = $preview;
    }

    final public function procesarCotizacion(int $rutaId, int $tarifaId): array
    {
        $this->obtenerViaje($rutaId);
        $this->obtenerTarifa($tarifaId);
        $this->validarDisponibilidad();
        $this->aplicarTarifa();
        $this->recargo = $this->calcularRecargos();
        $this->precioFinal = $this->precioBase + $this->recargo;
        $resultado = $this->formatearRecibo();
        if (!$this->preview) {
            $this->guardarEnHistorial();
        }
        return $resultado;
    }

    protected function obtenerViaje(int $rutaId): void
    {
        $this->viaje = $this->rutaModel->getById($rutaId);
    }

    protected function obtenerTarifa(int $tarifaId): void
    {
        $this->tarifa = $this->tarifaModel->getById($tarifaId);
    }

    protected function validarDisponibilidad(): void
    {
        if (!$this->conductorModel->hayDisponibles()) {
            throw new Exception('No hay conductores disponibles');
        }
    }

    protected function aplicarTarifa(): void
    {
        $this->precioBase = $this->strategy->calcular($this->viaje['km'], $this->tarifa);
    }

    protected function guardarEnHistorial(): void
    {
        $this->cotizacionModel->create(
            $this->viaje['id'],
            $this->tarifa['id'],
            $this->viaje['km'],
            $this->precioBase,
            $this->recargo,
            $this->precioFinal,
        );
    }

    protected function notaRecargo(): string
    {
        return match ($this->tarifa['nombre']) {
            'nocturna' => 'Recargo nocturno (+30%)',
            'lluvia'   => 'Recargo por lluvia (+20%)',
            'feriado'  => 'Recargo feriado (+50%)',
            'vip'      => 'Cargo fijo VIP',
            default    => 'Recargo',
        };
    }

    abstract protected function calcularRecargos(): float;
    abstract protected function formatearRecibo(): array;


}