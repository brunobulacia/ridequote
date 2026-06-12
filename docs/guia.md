# Guía de implementación paso a paso

> Paso 1 (DB.php + rides.sql) ya está hecho.
> Seguí en orden — cada paso depende del anterior.

---

## Paso 2 — Strategy

### `src/Strategy/TarifaStrategy.php`

```php
<?php

interface TarifaStrategy
{
    public function calcular(float $km, array $tarifa): float;
}
```

---

### `src/Strategy/TarifaNormal.php`

```php
<?php

require_once __DIR__ . '/TarifaStrategy.php';

class TarifaNormal implements TarifaStrategy
{
    public function calcular(float $km, array $tarifa): float
    {
        return $km * $tarifa['precio_km'];
    }
}
```

---

### `src/Strategy/TarifaNocturna.php`

```php
<?php

require_once __DIR__ . '/TarifaStrategy.php';

class TarifaNocturna implements TarifaStrategy
{
    public function calcular(float $km, array $tarifa): float
    {
        return $km * $tarifa['precio_km'] * $tarifa['multiplicador'];
    }
}
```

---

### `src/Strategy/TarifaLluvia.php`

```php
<?php

require_once __DIR__ . '/TarifaStrategy.php';

class TarifaLluvia implements TarifaStrategy
{
    public function calcular(float $km, array $tarifa): float
    {
        return $km * $tarifa['precio_km'] * $tarifa['multiplicador'];
    }
}
```

---

### `src/Strategy/TarifaFeriado.php`

```php
<?php

require_once __DIR__ . '/TarifaStrategy.php';

class TarifaFeriado implements TarifaStrategy
{
    public function calcular(float $km, array $tarifa): float
    {
        return $km * $tarifa['precio_km'] * $tarifa['multiplicador'];
    }
}
```

---

### `src/Strategy/TarifaVIP.php`

```php
<?php

require_once __DIR__ . '/TarifaStrategy.php';

class TarifaVIP implements TarifaStrategy
{
    public function calcular(float $km, array $tarifa): float
    {
        return ($km * $tarifa['precio_km']) + $tarifa['cargo_fijo'];
    }
}
```

> TarifaVIP usa una fórmula distinta (suma cargo fijo) — eso justifica que sea una Strategy separada.

---

## Paso 3 — Template Method

### `src/Template/CotizacionTemplate.php`

```php
<?php

require_once __DIR__ . '/../DB.php';
require_once __DIR__ . '/../Strategy/TarifaStrategy.php';

abstract class CotizacionTemplate
{
    private TarifaStrategy $strategy;

    protected array $viaje   = [];
    protected array $tarifa  = [];
    protected float $precioBase  = 0.0;
    protected float $recargo     = 0.0;
    protected float $precioFinal = 0.0;

    public function setStrategy(TarifaStrategy $strategy): void
    {
        $this->strategy = $strategy;
    }

    // Template Method — no se puede pisar en subclases
    final public function procesarCotizacion(int $rutaId, int $tarifaId): array
    {
        $this->obtenerViaje($rutaId);
        $this->obtenerTarifa($tarifaId);
        $this->validarDisponibilidad();
        $this->aplicarTarifa();
        $this->recargo     = $this->calcularRecargos();
        $this->precioFinal = $this->precioBase + $this->recargo;
        $resultado         = $this->formatearRecibo();
        $this->guardarEnHistorial();
        return $resultado;
    }

    // -- Pasos concretos (iguales para todas las subclases) --

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
        $stmt = DB::getInstance()->prepare(
            'SELECT COUNT(*) FROM conductores WHERE disponible = 1'
        );
        $stmt->execute();
        if ($stmt->fetchColumn() === 0) {
            throw new Exception('No hay conductores disponibles.');
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

    // -- Pasos abstractos (cada subclase los define distinto) --

    abstract protected function calcularRecargos(): float;
    abstract protected function formatearRecibo(): array;
}
```

---

### `src/Template/CotizacionEstandar.php`

```php
<?php

require_once __DIR__ . '/CotizacionTemplate.php';

class CotizacionEstandar extends CotizacionTemplate
{
    protected function calcularRecargos(): float
    {
        return 0.0;
    }

    protected function formatearRecibo(): array
    {
        return [
            'tipo'         => 'estandar',
            'ruta'         => $this->viaje['nombre'],
            'km'           => $this->viaje['km'],
            'tarifa'       => $this->tarifa['nombre'],
            'precio_base'  => round($this->precioBase, 2),
            'recargo'      => 0.0,
            'precio_final' => round($this->precioFinal, 2),
        ];
    }
}
```

---

### `src/Template/CotizacionAeropuerto.php`

```php
<?php

require_once __DIR__ . '/CotizacionTemplate.php';

class CotizacionAeropuerto extends CotizacionTemplate
{
    protected function calcularRecargos(): float
    {
        return 5.00;
    }

    protected function formatearRecibo(): array
    {
        return [
            'tipo'         => 'aeropuerto',
            'ruta'         => $this->viaje['nombre'],
            'km'           => $this->viaje['km'],
            'tarifa'       => $this->tarifa['nombre'],
            'precio_base'  => round($this->precioBase, 2),
            'recargo'      => $this->recargo,
            'recargo_nota' => 'Tasa aeroportuaria',
            'precio_final' => round($this->precioFinal, 2),
        ];
    }
}
```

---

## Paso 4 — API

### `api/rutas.php`

```php
<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../src/DB.php';

$stmt = DB::getInstance()->query('SELECT * FROM rutas ORDER BY id');
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
```

---

### `api/cotizar.php`

```php
<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

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

$input        = json_decode(file_get_contents('php://input'), true);
$rutaId       = (int)   ($input['ruta_id'] ?? 0);
$tarifaNombre = (string)($input['tarifa']  ?? '');

$db = DB::getInstance();

// Buscar tarifa en DB
$stmt = $db->prepare('SELECT * FROM tarifas WHERE nombre = ?');
$stmt->execute([$tarifaNombre]);
$tarifaData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tarifaData) {
    http_response_code(400);
    echo json_encode(['error' => 'Tarifa no válida']);
    exit;
}

// Buscar ruta en DB
$stmt = $db->prepare('SELECT tipo FROM rutas WHERE id = ?');
$stmt->execute([$rutaId]);
$rutaData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$rutaData) {
    http_response_code(400);
    echo json_encode(['error' => 'Ruta no válida']);
    exit;
}

// Instanciar Strategy según nombre de tarifa
$strategies = [
    'normal'   => new TarifaNormal(),
    'nocturna' => new TarifaNocturna(),
    'lluvia'   => new TarifaLluvia(),
    'feriado'  => new TarifaFeriado(),
    'vip'      => new TarifaVIP(),
];
$strategy = $strategies[$tarifaNombre];

// Instanciar subclase de Template según tipo de ruta
$cotizacion = ($rutaData['tipo'] === 'aeropuerto')
    ? new CotizacionAeropuerto()
    : new CotizacionEstandar();

$cotizacion->setStrategy($strategy);

try {
    $recibo = $cotizacion->procesarCotizacion($rutaId, $tarifaData['id']);
    echo json_encode($recibo);
} catch (Exception $e) {
    http_response_code(409);
    echo json_encode(['error' => $e->getMessage()]);
}
```

---

## Paso 5 — Frontend

### `index.html`

```html
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RideQuote</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="app">
    <header>
        <h1>RideQuote</h1>
        <p>Cotizá tu viaje al instante</p>
    </header>

    <main>
        <section class="form-card">

            <div class="field">
                <label for="ruta">Ruta</label>
                <select id="ruta">
                    <option value="">Cargando rutas...</option>
                </select>
            </div>

            <div class="field">
                <label>Tipo de tarifa</label>
                <div class="tarifa-grid" id="tarifaGrid">
                    <button class="tarifa-btn active" data-tarifa="normal">
                        <span class="tarifa-icon">🚗</span>
                        <span class="tarifa-nombre">Normal</span>
                        <span class="tarifa-desc">Tarifa estándar</span>
                    </button>
                    <button class="tarifa-btn" data-tarifa="nocturna">
                        <span class="tarifa-icon">🌙</span>
                        <span class="tarifa-nombre">Nocturna</span>
                        <span class="tarifa-desc">+30% después de las 22hs</span>
                    </button>
                    <button class="tarifa-btn" data-tarifa="lluvia">
                        <span class="tarifa-icon">🌧️</span>
                        <span class="tarifa-nombre">Lluvia</span>
                        <span class="tarifa-desc">+20% en mal clima</span>
                    </button>
                    <button class="tarifa-btn" data-tarifa="feriado">
                        <span class="tarifa-icon">📅</span>
                        <span class="tarifa-nombre">Feriado</span>
                        <span class="tarifa-desc">+50% días feriados</span>
                    </button>
                    <button class="tarifa-btn tarifa-vip" data-tarifa="vip">
                        <span class="tarifa-icon">⭐</span>
                        <span class="tarifa-nombre">VIP</span>
                        <span class="tarifa-desc">Vehículo premium</span>
                    </button>
                </div>
            </div>

            <button id="btnCotizar">Cotizar viaje</button>

        </section>

        <section class="recibo" id="recibo" hidden>
            <div class="recibo-header">
                <span id="reciboRuta"></span>
                <span class="badge" id="reciboBadge"></span>
            </div>
            <div class="recibo-detalle">
                <div class="linea">
                    <span>Distancia</span>
                    <span id="reciboKm"></span>
                </div>
                <div class="linea">
                    <span>Precio base</span>
                    <span id="reciboPrecioBase"></span>
                </div>
                <div class="linea recargo" id="lineaRecargo" hidden>
                    <span id="reciboRecargoNota">Recargo</span>
                    <span id="reciboRecargo"></span>
                </div>
                <div class="linea total">
                    <span>Total</span>
                    <span id="reciboTotal"></span>
                </div>
            </div>
        </section>

        <section class="historial">
            <h2>Últimas cotizaciones</h2>
            <table id="tablaHistorial">
                <thead>
                    <tr>
                        <th>Ruta</th>
                        <th>Tarifa</th>
                        <th>Km</th>
                        <th>Total</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody id="historialBody"></tbody>
            </table>
        </section>
    </main>
</div>

<script src="app.js"></script>
</body>
</html>
```

---

### `style.css`

```css
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'Segoe UI', sans-serif;
    background: #f0f2f5;
    color: #1a1a2e;
}

.app {
    max-width: 720px;
    margin: 0 auto;
    padding: 2rem 1rem;
}

header {
    text-align: center;
    margin-bottom: 2rem;
}

header h1 {
    font-size: 2rem;
    color: #2d6a4f;
}

header p {
    color: #666;
    margin-top: 0.25rem;
}

/* Form card */
.form-card {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 2px 12px rgba(0,0,0,.08);
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.field label {
    display: block;
    font-weight: 600;
    margin-bottom: 0.5rem;
    font-size: .9rem;
}

select {
    width: 100%;
    padding: .6rem .8rem;
    border: 1.5px solid #ddd;
    border-radius: 8px;
    font-size: 1rem;
    outline: none;
    transition: border-color .2s;
}

select:focus { border-color: #2d6a4f; }

/* Tarifa grid */
.tarifa-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: .75rem;
}

.tarifa-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .2rem;
    padding: .75rem .5rem;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    background: white;
    cursor: pointer;
    transition: border-color .2s, background .2s;
    text-align: center;
}

.tarifa-btn:hover { border-color: #2d6a4f; }

.tarifa-btn.active {
    border-color: #2d6a4f;
    background: #e9f5ee;
}

.tarifa-icon  { font-size: 1.4rem; }
.tarifa-nombre { font-weight: 700; font-size: .85rem; }
.tarifa-desc  { font-size: .7rem; color: #888; }

.tarifa-vip.active {
    border-color: #b5831a;
    background: #fff8e7;
}

#btnCotizar {
    padding: .85rem;
    background: #2d6a4f;
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    transition: background .2s;
}

#btnCotizar:hover { background: #1b4332; }

/* Recibo */
.recibo {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    margin-top: 1.25rem;
    box-shadow: 0 2px 12px rgba(0,0,0,.08);
    animation: slideIn .3s ease;
}

@keyframes slideIn {
    from { opacity: 0; transform: translateY(12px); }
    to   { opacity: 1; transform: translateY(0); }
}

.recibo-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    font-weight: 700;
    font-size: 1.05rem;
}

.badge {
    padding: .25rem .75rem;
    border-radius: 999px;
    font-size: .75rem;
    font-weight: 700;
    text-transform: uppercase;
    background: #e9f5ee;
    color: #2d6a4f;
}

.badge.vip {
    background: #fff8e7;
    color: #b5831a;
}

.badge.nocturna { background: #e8eaf6; color: #3949ab; }
.badge.lluvia   { background: #e3f2fd; color: #1565c0; }
.badge.feriado  { background: #fce4ec; color: #c62828; }

.recibo-detalle { display: flex; flex-direction: column; gap: .6rem; }

.linea {
    display: flex;
    justify-content: space-between;
    font-size: .95rem;
    color: #444;
}

.linea.recargo { color: #e07b00; }

.linea.total {
    border-top: 2px solid #eee;
    padding-top: .6rem;
    font-size: 1.2rem;
    font-weight: 800;
    color: #1a1a2e;
}

/* Historial */
.historial {
    margin-top: 2rem;
}

.historial h2 {
    font-size: 1rem;
    margin-bottom: .75rem;
    color: #555;
}

table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,.06);
    font-size: .875rem;
}

th {
    background: #2d6a4f;
    color: white;
    padding: .6rem .8rem;
    text-align: left;
    font-weight: 600;
}

td {
    padding: .55rem .8rem;
    border-bottom: 1px solid #f0f0f0;
}

tr:last-child td { border-bottom: none; }
tr:hover td { background: #f9fafb; }
```

---

### `app.js`

```javascript
const API = 'http://localhost:8000';

let tarifaActiva = 'normal';

// Cargar rutas al iniciar
async function cargarRutas() {
    const res  = await fetch(`${API}/api/rutas.php`);
    const data = await res.json();
    const sel  = document.getElementById('ruta');
    sel.innerHTML = data.map(r =>
        `<option value="${r.id}">${r.nombre} (${r.km} km)</option>`
    ).join('');
}

// Selección de tarifa
document.getElementById('tarifaGrid').addEventListener('click', e => {
    const btn = e.target.closest('.tarifa-btn');
    if (!btn) return;
    document.querySelectorAll('.tarifa-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    tarifaActiva = btn.dataset.tarifa;
});

// Cotizar
document.getElementById('btnCotizar').addEventListener('click', async () => {
    const rutaId = document.getElementById('ruta').value;
    if (!rutaId) return;

    const res = await fetch(`${API}/api/cotizar.php`, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ ruta_id: parseInt(rutaId), tarifa: tarifaActiva }),
    });

    const data = await res.json();
    if (data.error) { alert(data.error); return; }

    renderRecibo(data);
    cargarHistorial();
});

function renderRecibo(d) {
    const recibo = document.getElementById('recibo');
    recibo.hidden = false;

    document.getElementById('reciboRuta').textContent = d.ruta;
    document.getElementById('reciboKm').textContent   = `${d.km} km`;
    document.getElementById('reciboPrecioBase').textContent = `$${d.precio_base.toFixed(2)}`;
    document.getElementById('reciboTotal').textContent      = `$${d.precio_final.toFixed(2)}`;

    const badge = document.getElementById('reciboBadge');
    badge.textContent  = d.tarifa;
    badge.className    = `badge ${d.tarifa}`;

    const lineaRecargo = document.getElementById('lineaRecargo');
    if (d.recargo > 0) {
        lineaRecargo.hidden = false;
        document.getElementById('reciboRecargoNota').textContent = d.recargo_nota ?? 'Recargo';
        document.getElementById('reciboRecargo').textContent = `$${d.recargo.toFixed(2)}`;
    } else {
        lineaRecargo.hidden = true;
    }
}

async function cargarHistorial() {
    const res  = await fetch(`${API}/api/cotizar.php?historial=1`);
    // Para no crear otro endpoint, el historial lo manejamos directo
    // Ver nota abajo
}

cargarRutas();
```

> **Nota sobre el historial:** para no crear un tercer endpoint, podés agregar
> un bloque `if (isset($_GET['historial']))` al inicio de `cotizar.php` que
> retorne un SELECT de las últimas 10 cotizaciones con JOIN a `rutas` y `tarifas`.
> O simplemente crear `api/historial.php` — es el mismo patrón que `rutas.php`.

---

## Cómo levantar el proyecto

```bash
# Terminal — desde la raíz del proyecto
php -S localhost:8000
```

Abrí `index.html` con Live Server (puerto 5500 por defecto).
El `app.js` apunta a `http://localhost:8000` para los endpoints PHP.
