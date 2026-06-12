<?php

require_once __DIR__ . '/../DB.php';

class TarifaModel
{
    private ?int $id = null;
    private string $nombre = '';
    private float $precioKm = 0.0;
    private float $multiplicador = 1.0;
    private float $cargoFijo = 0.0;

    public function get(): array
    {
        $stmt = DB::getInstance()->query('SELECT * FROM tarifas ORDER BY id');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $stmt = DB::getInstance()->prepare('SELECT * FROM tarifas WHERE id = ?');
        $stmt->execute([$id]);
        $tarifa = $stmt->fetch(PDO::FETCH_ASSOC);
        return $tarifa ?: null;
    }

    public function create(string $nombre, float $precioKm, float $multiplicador, float $cargoFijo): int
    {
        $db   = DB::getInstance();
        $stmt = $db->prepare(
            'INSERT INTO tarifas (nombre, precio_km, multiplicador, cargo_fijo) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$nombre, $precioKm, $multiplicador, $cargoFijo]);
        return (int) $db->lastInsertId();
    }

    public function update(int $id, string $nombre, float $precioKm, float $multiplicador, float $cargoFijo): bool
    {
        $stmt = DB::getInstance()->prepare(
            'UPDATE tarifas SET nombre = ?, precio_km = ?, multiplicador = ?, cargo_fijo = ? WHERE id = ?'
        );
        return $stmt->execute([$nombre, $precioKm, $multiplicador, $cargoFijo, $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = DB::getInstance()->prepare('DELETE FROM tarifas WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function obtenerPorNombre(string $nombre): ?array
    {
        $stmt = DB::getInstance()->prepare('SELECT * FROM tarifas WHERE nombre = ?');
        $stmt->execute([$nombre]);
        $tarifa = $stmt->fetch(PDO::FETCH_ASSOC);
        return $tarifa ?: null;
    }
}
