# Plan: Cotizador de Viajes con Strategy + Template Method

## Descripción general

App de cotización de viajes estilo ride-share donde el usuario ingresa
origen, destino y km estimados, elige el tipo de tarifa y obtiene el
precio final en tiempo real. Las cotizaciones se guardan en SQLite.

**Stack:** PHP 8, HTML5, CSS3, JavaScript (Vanilla), SQLite 3

---

## Patrones aplicados

### Strategy — Algoritmo de tarifa intercambiable

El contexto (cotizador) no sabe cómo se calcula la tarifa.
Delega en la estrategia activa.

| Estrategia       | Lógica                                          |
|------------------|-------------------------------------------------|
| `TarifaNormal`   | precio = km × tarifa_base                       |
| `TarifaNocturna` | precio = km × tarifa_base × 1.30               |
| `TarifaLluvia`   | precio = km × tarifa_base × 1.20               |
| `TarifaFeriado`  | precio = km × tarifa_base × 1.50               |
| `TarifaVIP`      | precio = km × tarifa_premium + cargo_fijo_VIP  |

### Template Method — Flujo de cotización fijo con pasos variables

La clase base `CotizacionTemplate` define el esqueleto del proceso.
Las subclases sobreescriben solo los pasos que cambian.

```
procesarCotizacion():
    1. obtenerViaje()           ← mismo para todos
    2. validarDisponibilidad()  ← mismo para todos
    3. aplicarTarifa()          ← DELEGA en Strategy
    4. calcularRecargos()       ← varía: Estándar vs Aeropuerto
    5. formatearRecibo()        ← varía: desglose distinto
    6. guardarEnHistorial()     ← mismo para todos
```

Subclases concretas:
- `CotizacionEstandar` — sin recargo extra, recibo con desglose km
- `CotizacionAeropuerto` — recargo fijo $5 por tasa aeroportuaria, recibo con nota de terminal

---

## Estructura de archivos

```
/
├── index.html              # UI: formulario de viaje, selector de tarifa, resultado
├── style.css               # Estilo tipo app ride-share
├── app.js                  # Lógica frontend: fetch, renderizar recibo
│
├── api/
│   ├── cotizar.php         # Endpoint principal (POST)
│   └── rutas.php           # Endpoint (GET) lista de rutas predefinidas
│
├── src/
│   ├── Strategy/
│   │   ├── TarifaStrategy.php          # Interface
│   │   ├── TarifaNormal.php
│   │   ├── TarifaNocturna.php
│   │   ├── TarifaLluvia.php
│   │   ├── TarifaFeriado.php
│   │   └── TarifaVIP.php
│   │
│   ├── Template/
│   │   ├── CotizacionTemplate.php      # Clase abstracta
│   │   ├── CotizacionEstandar.php
│   │   └── CotizacionAeropuerto.php
│   │
│   └── DB.php                          # Singleton SQLite
│
└── database/
    └── rides.sqlite                    # Generado al primer request
```

---

## Base de datos (SQLite)

### Tabla `rutas`

| Campo       | Tipo    | Notas                              |
|-------------|---------|------------------------------------|
| id          | INTEGER | PK autoincrement                   |
| nombre      | TEXT    | Ej: "Centro → Aeropuerto"          |
| origen      | TEXT    |                                    |
| destino     | TEXT    |                                    |
| km          | REAL    | Distancia estimada                 |
| tipo        | TEXT    | `estandar` o `aeropuerto`          |

### Tabla `conductores`

| Campo    | Tipo    | Notas                      |
|----------|---------|----------------------------|
| id       | INTEGER | PK autoincrement           |
| nombre   | TEXT    |                            |
| vehiculo | TEXT    | `estandar` o `vip`         |
| disponible | INTEGER | 0 o 1                    |

### Tabla `tarifas`

| Campo        | Tipo  | Notas                              |
|--------------|-------|------------------------------------|
| id           | INTEGER | PK autoincrement                 |
| nombre       | TEXT  | `normal`, `nocturna`, `vip`, etc.  |
| precio_km    | REAL  | Precio base por km                 |
| multiplicador| REAL  | 1.0, 1.3, 1.5…                     |
| cargo_fijo   | REAL  | Para VIP y aeropuerto              |

### Tabla `cotizaciones`

| Campo           | Tipo     | Notas                          |
|-----------------|----------|--------------------------------|
| id              | INTEGER  | PK autoincrement               |
| ruta_id         | INTEGER  | FK → rutas                     |
| tarifa_id       | INTEGER  | FK → tarifas                   |
| km              | REAL     |                                |
| precio_base     | REAL     | km × tarifa sin recargos       |
| recargo         | REAL     | Recargo extra (aeropuerto etc) |
| precio_final    | REAL     |                                |
| created_at      | DATETIME |                                |

---

## Flujo completo de una cotización

```
[Usuario]
   │  selecciona ruta + tipo de tarifa
   ▼
[app.js]
   │  POST /api/cotizar.php  { ruta_id, estrategia }
   ▼
[cotizar.php]
   │  1. Instancia la Strategy según parámetro
   │  2. Decide subclase de Template (según tipo de ruta)
   │  3. Llama a procesarCotizacion()
   ▼
[CotizacionTemplate::procesarCotizacion()]
   │  → obtenerViaje()           leer SQLite (ruta + km)
   │  → validarDisponibilidad()  check conductores disponibles (fake OK)
   │  → aplicarTarifa()          Strategy::calcular(km)
   │  → calcularRecargos()       abstracto: $0 o $5 aeropuerto
   │  → formatearRecibo()        abstracto: armar array resultado
   │  → guardarEnHistorial()     INSERT cotizaciones
   ▼
[cotizar.php]
   │  json_encode($recibo)
   ▼
[app.js]
   │  renderiza recibo con desglose:
   │    km / tarifa por km / subtotal / recargo / TOTAL
```

---

## UI

### Principal
- Dropdown: seleccionar ruta (cargada desde `/api/rutas.php`)
- Cards: elegir tipo de tarifa (con icono y descripción de cada una)
- Panel resultado: recibo animado con desglose y precio final destacado
- Tabla historial: últimas 10 cotizaciones

### Estados visuales
- Badge de tarifa activa con color distinto por tipo
- Badge "VIP" con estilo premium cuando aplica TarifaVIP
- Precio final con animación de conteo al aparecer
- Indicador de disponibilidad de conductores (fake, siempre verde excepto madrugada)

---

## Relación entre patrones

```
CotizacionTemplate  ──usa──▶  TarifaStrategy (interface)
        │                           ▲
        │               ┌───────────┼───────────┐
        ▼           TarifaNormal  TarifaVIP  TarifaFeriado
CotizacionEstandar  TarifaNocturna  TarifaLluvia
CotizacionAeropuerto
```

Template Method controla **cuándo** se aplica la tarifa.
Strategy controla **cómo** se calcula.

---

## Rutas de prueba

| Ruta                        | km    | Tipo        |
|-----------------------------|-------|-------------|
| Centro → Shopping           | 5.0   | estandar    |
| Centro → Aeropuerto         | 18.0  | aeropuerto  |
| Barrio Norte → Hospital     | 3.5   | estandar    |
| Terminal → Hotel            | 12.0  | aeropuerto  |
| Universidad → Centro        | 7.2   | estandar    |

---

## Orden de implementación

### Paso 1 — Base de datos ✅
- [x] `database/rides.sql` — schema + seed de las 4 tablas
- [x] `src/DB.php` — Singleton PDO, crea el `.sqlite` si no existe

---

### Paso 2 — Strategy: interface + clases concretas
- [ ] `src/Strategy/TarifaStrategy.php` — interface con un único método `calcular(float $km, array $tarifa): float`
- [ ] `src/Strategy/TarifaNormal.php` — implementa `calcular()`
- [ ] `src/Strategy/TarifaNocturna.php` — implementa `calcular()`
- [ ] `src/Strategy/TarifaLluvia.php` — implementa `calcular()`
- [ ] `src/Strategy/TarifaFeriado.php` — implementa `calcular()`
- [ ] `src/Strategy/TarifaVIP.php` — implementa `calcular()`

> Cada clase recibe el array `$tarifa` leído de la DB, no hardcodea números.

**Estructura UML del Context (CotizacionTemplate actúa como Context):**
```
- $strategy: TarifaStrategy
+ setStrategy(TarifaStrategy $s): void
```
El Context solo llama `$this->strategy->calcular()` — nunca sabe qué clase concreta es.

---

### Paso 3 — Template Method: clase abstracta + subclases
- [ ] `src/Template/CotizacionTemplate.php` — clase `abstract` con:
  - `final public function procesarCotizacion()` — el template method, no se puede pisar
  - `protected function obtenerViaje()` — concreto, igual para todos
  - `protected function validarDisponibilidad()` — concreto, igual para todos
  - `protected function aplicarTarifa()` — concreto, delega en Strategy
  - `protected function guardarEnHistorial()` — concreto, igual para todos
  - `abstract protected function calcularRecargos(): float` — **abstracto**, cada subclase lo define
  - `abstract protected function formatearRecibo(): array` — **abstracto**, cada subclase lo define
- [ ] `src/Template/CotizacionEstandar.php` — sobreescribe solo `calcularRecargos()` y `formatearRecibo()`
- [ ] `src/Template/CotizacionAeropuerto.php` — sobreescribe solo `calcularRecargos()` y `formatearRecibo()`

> Las subclases NO tocan `procesarCotizacion()` — eso garantiza el patrón.

---

### Paso 4 — API endpoints
- [ ] `api/rutas.php` — GET, retorna lista de rutas desde DB (JSON)
- [ ] `api/cotizar.php` — POST `{ ruta_id, tarifa_nombre }`, instancia Strategy + Template, retorna recibo (JSON)

---

### Paso 5 — Frontend
- [ ] `index.html` — dropdown rutas, cards de tarifa, panel resultado, tabla historial
- [ ] `style.css` — estilo ride-share: colores por tarifa, badge VIP, animaciones
- [ ] `app.js` — fetch rutas al cargar, POST al cotizar, renderizar recibo con desglose
