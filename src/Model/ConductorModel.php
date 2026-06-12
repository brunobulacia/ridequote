<?php

require_once __DIR__ . '/../DB.php';

class ConductorModel
{
    private ?int $id = null;
    private string $nombre = '';
    private string $vehiculo = 'estandar';
    private int $disponible = 1;

    public function get(): array
    {
        $stmt = DB::getInstance()->query('SELECT * FROM conductores ORDER BY id');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $stmt = DB::getInstance()->prepare('SELECT * FROM conductores WHERE id = ?');
        $stmt->execute([$id]);
        $conductor = $stmt->fetch(PDO::FETCH_ASSOC);
        return $conductor ?: null;
    }

    public function create(string $nombre, string $vehiculo, int $disponible): int
    {
        $db   = DB::getInstance();
        $stmt = $db->prepare(
            'INSERT INTO conductores (nombre, vehiculo, disponible) VALUES (?, ?, ?)'
        );
        $stmt->execute([$nombre, $vehiculo, $disponible]);
        return (int) $db->lastInsertId();
    }

    public function update(int $id, string $nombre, string $vehiculo, int $disponible): bool
    {
        $stmt = DB::getInstance()->prepare(
            'UPDATE conductores SET nombre = ?, vehiculo = ?, disponible = ? WHERE id = ?'
        );
        return $stmt->execute([$nombre, $vehiculo, $disponible, $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = DB::getInstance()->prepare('DELETE FROM conductores WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function hayDisponibles(): bool
    {
        $stmt = DB::getInstance()->prepare('SELECT COUNT(*) FROM conductores WHERE disponible = 1');
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }
}
