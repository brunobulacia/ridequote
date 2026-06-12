<?php

require_once __DIR__ . '/../DB.php';

class RutaModel
{
    private ?int $id = null;
    private string $nombre = '';
    private string $origen = '';
    private string $destino = '';
    private float $km = 0.0;
    private string $tipo = 'estandar';

    public function get(): array
    {
        $stmt = DB::getInstance()->query('SELECT * FROM rutas ORDER BY id');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $stmt = DB::getInstance()->prepare('SELECT * FROM rutas WHERE id = ?');
        $stmt->execute([$id]);
        $ruta = $stmt->fetch(PDO::FETCH_ASSOC);
        return $ruta ?: null;
    }

    public function create(string $nombre, string $origen, string $destino, float $km, string $tipo): int
    {
        $db   = DB::getInstance();
        $stmt = $db->prepare(
            'INSERT INTO rutas (nombre, origen, destino, km, tipo) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$nombre, $origen, $destino, $km, $tipo]);
        return (int) $db->lastInsertId();
    }

    public function update(int $id, string $nombre, string $origen, string $destino, float $km, string $tipo): bool
    {
        $stmt = DB::getInstance()->prepare(
            'UPDATE rutas SET nombre = ?, origen = ?, destino = ?, km = ?, tipo = ? WHERE id = ?'
        );
        return $stmt->execute([$nombre, $origen, $destino, $km, $tipo, $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = DB::getInstance()->prepare('DELETE FROM rutas WHERE id = ?');
        return $stmt->execute([$id]);
    }
}
