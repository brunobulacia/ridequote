<?php

require_once __DIR__ . '/../DB.php';
require_once __DIR__ . '/../Strategy/TarifaStrategy.php';

abstract class CotizacionTemplate
{
    private TarifaStrategy $strategy;
    private bool $preview = false;

    protected array $viaje = [];
    protected array $tarifa = [];
    protected float $precioBase = 0.0;
    protected float $recargo = 0.0;
    protected float $precioFinal = 0.0;

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
        $stmt = DB::getInstance()->prepare('SELECT * FROM rutas WHERE id = ?');
        $stmt->execute([$rutaId]);
        $this->viaje = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    protected function obtenerTarifa(int $tarifaId): void
    {
        $stmt = DB::getInstance()->prepare('SELECT * FROM tarifas WHERE id = ?');
        $stmt->execute([$tarifaId]);
        $this->tarifa = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    protected function validarDisponibilidad(): void
    {
        $stmt = DB::getInstance()->prepare('SELECT COUNT(*) FROM conductores WHERE disponible = 1');
        $stmt->execute();
        if ($stmt->fetchColumn() === 0) {
            throw new Exception('No hay conductores disponibles');
        }
    }

    protected function aplicarTarifa(): void
    {
        $this->precioBase = $this->strategy->calcular($this->viaje['km'], $this->tarifa);
    }

    protected function guardarEnHistorial(): void
    {
        $stmt = DB::getInstance()->prepare(
            'INSERT INTO cotizaciones
                (ruta_id, tarifa_id, km, precio_base, recargo, precio_final)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $this->viaje['id'],
            $this->tarifa['id'],
            $this->viaje['km'],
            $this->precioBase,
            $this->recargo,
            $this->precioFinal,
        ]);
    }

    abstract protected function calcularRecargos(): float;
    abstract protected function formatearRecibo(): array;


}