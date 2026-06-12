# Diagramas UML

---

## Archivos → clases UML

| Archivo | Clase UML | Tipo | Lenguaje |
|---|---|---|---|
| `app.js` | `Vista` | clase | JavaScript |
| `api/cotizar.php` | `CotizadorController` | clase | PHP |
| `api/rutas.php` | `RutasController` | clase | PHP |
| `api/historial.php` | `HistorialController` | clase | PHP |
| `src/DB.php` | `DB` | clase (Singleton) | PHP |
| `src/Strategy/TarifaStrategy.php` | `TarifaStrategy` | `<<interface>>` | PHP |
| `src/Strategy/TarifaNormal.php` | `TarifaNormal` | clase concreta | PHP |
| `src/Strategy/TarifaNocturna.php` | `TarifaNocturna` | clase concreta | PHP |
| `src/Strategy/TarifaLluvia.php` | `TarifaLluvia` | clase concreta | PHP |
| `src/Strategy/TarifaFeriado.php` | `TarifaFeriado` | clase concreta | PHP |
| `src/Strategy/TarifaVIP.php` | `TarifaVIP` | clase concreta | PHP |
| `src/Template/CotizacionTemplate.php` | `CotizacionTemplate` | `<<abstract>>` — Context + AbstractClass | PHP |
| `src/Template/CotizacionEstandar.php` | `CotizacionEstandar` | clase concreta | PHP |
| `src/Template/CotizacionAeropuerto.php` | `CotizacionAeropuerto` | clase concreta | PHP |

---

## Diagrama 1 — Strategy Pattern

```mermaid
classDiagram
    class Vista {
        <<JavaScript>>
        -API string
        -tarifaActiva string
        +constructor()
        -bindTarifas() void
        -bindCotizar() void
        -cotizar() void
    }

    class CotizadorController {
        -strategies array
        +constructor()
        +handle() void
    }

    class TarifaStrategy {
        <<interface>>
        +calcular(km float, tarifa array) float
    }

    class TarifaNormal {
        +calcular(km float, tarifa array) float
    }

    class TarifaNocturna {
        +calcular(km float, tarifa array) float
    }

    class TarifaLluvia {
        +calcular(km float, tarifa array) float
    }

    class TarifaFeriado {
        +calcular(km float, tarifa array) float
    }

    class TarifaVIP {
        +calcular(km float, tarifa array) float
    }

    class CotizacionTemplate {
        <<abstract>>
        -strategy TarifaStrategy
        -preview bool
        +setStrategy(strategy TarifaStrategy) void
        +setPreview(preview bool) void
        +procesarCotizacion(rutaId int, tarifaId int) array
    }

    TarifaNormal     ..|> TarifaStrategy
    TarifaNocturna   ..|> TarifaStrategy
    TarifaLluvia     ..|> TarifaStrategy
    TarifaFeriado    ..|> TarifaStrategy
    TarifaVIP        ..|> TarifaStrategy

    CotizacionTemplate o--> TarifaStrategy : strategy

    CotizadorController --> TarifaNormal     : crea
    CotizadorController --> TarifaNocturna   : crea
    CotizadorController --> TarifaLluvia     : crea
    CotizadorController --> TarifaFeriado    : crea
    CotizadorController --> TarifaVIP        : crea
    CotizadorController --> CotizacionTemplate : setStrategy() / setPreview()

    Vista ..> CotizadorController : POST /api/cotizar.php
```

---

## Diagrama 2 — Template Method Pattern

```mermaid
classDiagram
    class CotizacionTemplate {
        <<abstract>>
        -strategy TarifaStrategy
        -preview bool
        #viaje array
        #tarifa array
        #precioBase float
        #recargo float
        #precioFinal float
        +setStrategy(strategy TarifaStrategy) void
        +setPreview(preview bool) void
        +procesarCotizacion(rutaId int, tarifaId int) array
        #obtenerViaje(rutaId int) void
        #obtenerTarifa(tarifaId int) void
        #validarDisponibilidad() void
        #aplicarTarifa() void
        #guardarEnHistorial() void
        #calcularRecargos()* float
        #formatearRecibo()* array
    }

    class CotizacionEstandar {
        #calcularRecargos() float
        #formatearRecibo() array
    }

    class CotizacionAeropuerto {
        #calcularRecargos() float
        #formatearRecibo() array
    }

    class DB {
        -instance DB
        -__construct()
        +getInstance() PDO
    }

    CotizacionEstandar   --|> CotizacionTemplate
    CotizacionAeropuerto --|> CotizacionTemplate
    CotizacionTemplate   ..> DB : getInstance()
```

---

## Diagrama 3 — Sistema completo

```mermaid
classDiagram
    class Vista {
        <<JavaScript>>
        -API string
        -tarifaActiva string
        +constructor()
        -cargarRutas() void
        -cargarHistorial() void
        -cotizar() void
        -renderRecibo(d object) void
        -renderHistorial(filas array) void
    }

    class CotizadorController {
        -strategies array
        +constructor()
        +handle() void
    }

    class RutasController {
        +handle() void
    }

    class HistorialController {
        +handle() void
    }

    class DB {
        -instance DB
        -__construct()
        +getInstance() PDO
    }

    class TarifaStrategy {
        <<interface>>
        +calcular(km float, tarifa array) float
    }

    class TarifaNormal     { +calcular(km float, tarifa array) float }
    class TarifaNocturna   { +calcular(km float, tarifa array) float }
    class TarifaLluvia     { +calcular(km float, tarifa array) float }
    class TarifaFeriado    { +calcular(km float, tarifa array) float }
    class TarifaVIP        { +calcular(km float, tarifa array) float }

    class CotizacionTemplate {
        <<abstract>>
        -strategy TarifaStrategy
        -preview bool
        #viaje array
        #tarifa array
        #precioBase float
        #recargo float
        #precioFinal float
        +setStrategy(strategy TarifaStrategy) void
        +setPreview(preview bool) void
        +procesarCotizacion(rutaId int, tarifaId int) array
        #obtenerViaje(rutaId int) void
        #obtenerTarifa(tarifaId int) void
        #validarDisponibilidad() void
        #aplicarTarifa() void
        #guardarEnHistorial() void
        #calcularRecargos()* float
        #formatearRecibo()* array
    }

    class CotizacionEstandar {
        #calcularRecargos() float
        #formatearRecibo() array
    }

    class CotizacionAeropuerto {
        #calcularRecargos() float
        #formatearRecibo() array
    }

    %% Herencia / Realización
    TarifaNormal     ..|> TarifaStrategy
    TarifaNocturna   ..|> TarifaStrategy
    TarifaLluvia     ..|> TarifaStrategy
    TarifaFeriado    ..|> TarifaStrategy
    TarifaVIP        ..|> TarifaStrategy

    CotizacionEstandar   --|> CotizacionTemplate
    CotizacionAeropuerto --|> CotizacionTemplate

    %% Strategy dentro del Template (Context)
    CotizacionTemplate o--> TarifaStrategy : strategy

    %% Controllers usan DB
    CotizacionTemplate  ..> DB : getInstance()
    RutasController     ..> DB : getInstance()
    HistorialController ..> DB : getInstance()
    CotizadorController ..> DB : getInstance()

    %% Controllers crean objetos de dominio
    CotizadorController --> TarifaNormal     : crea
    CotizadorController --> TarifaNocturna   : crea
    CotizadorController --> TarifaLluvia     : crea
    CotizadorController --> TarifaFeriado    : crea
    CotizadorController --> TarifaVIP        : crea
    CotizadorController --> CotizacionEstandar   : crea
    CotizadorController --> CotizacionAeropuerto : crea

    %% Vista llama a controllers vía HTTP
    Vista ..> CotizadorController  : POST /api/cotizar.php
    Vista ..> RutasController      : GET /api/rutas.php
    Vista ..> HistorialController  : GET /api/historial.php
```
