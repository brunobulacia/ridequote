<?php

require_once __DIR__ . '/../DB.php';

class CotizacionModel
{
    private ?int $id = null;
    private int $rutaId = 0;
    private int $tarifaId = 0;
    private float $km = 0.0;
    private float $precioBase = 0.0;
    private float $recargo = 0.0;
    private float $precioFinal = 0.0;
    private ?string $createdAt = null;

    public function get(): array
    {
        $stmt = DB::getInstance()->query('SELECT * FROM cotizaciones ORDER BY id');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $stmt = DB::getInstance()->prepare('SELECT * FROM cotizaciones WHERE id = ?');
        $stmt->execute([$id]);
        $cotizacion = $stmt->fetch(PDO::FETCH_ASSOC);
        return $cotizacion ?: null;
    }

    public function create(int $rutaId, int $tarifaId, float $km, float $precioBase, float $recargo, float $precioFinal): int
    {
        $db   = DB::getInstance();
        $stmt = $db->prepare(
            'INSERT INTO cotizaciones
                (ruta_id, tarifa_id, km, precio_base, recargo, precio_final)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$rutaId, $tarifaId, $km, $precioBase, $recargo, $precioFinal]);
        return (int) $db->lastInsertId();
    }

    public function update(int $id, int $rutaId, int $tarifaId, float $km, float $precioBase, float $recargo, float $precioFinal): bool
    {
        $stmt = DB::getInstance()->prepare(
            'UPDATE cotizaciones
             SET ruta_id = ?, tarifa_id = ?, km = ?, precio_base = ?, recargo = ?, precio_final = ?
             WHERE id = ?'
        );
        return $stmt->execute([$rutaId, $tarifaId, $km, $precioBase, $recargo, $precioFinal, $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = DB::getInstance()->prepare('DELETE FROM cotizaciones WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function obtenerHistorial(int $limite = 10): array
    {
        $stmt = DB::getInstance()->prepare(
            'SELECT c.id, r.nombre AS ruta, t.nombre AS tarifa,
                    c.km, c.precio_base, c.recargo, c.precio_final, c.created_at
             FROM cotizaciones c
             JOIN rutas   r ON r.id = c.ruta_id
             JOIN tarifas t ON t.id = c.tarifa_id
             ORDER BY c.id DESC
             LIMIT ?'
        );
        $stmt->execute([$limite]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
