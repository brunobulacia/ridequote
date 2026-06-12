class Vista {
  #API = "http://localhost:8000";
  #tarifaActiva = "normal";
  #mapa = null;
  #marcadores = []; // [{marker, lat, lng, nombre}] — [0]=origen, [1]=destino
  #rutaPersonalizada = null;
  #cotizarController = null; // AbortController para cancelar previews en vuelo
  #cotizarNonce = 0;         // cada llamada recibe un ID único; descarta respuestas obsoletas

  constructor() {
    this.#initMapa();
    this.#bindTarifas();
    this.#bindCotizar();
    this.#cargarHistorial();
  }

  // ── Mapa ─────────────────────────────────────────────────────────────────

  #initMapa() {
    this.#mapa = L.map("mapa").setView([-17.7833, -63.1822], 13); // Santa Cruz de la Sierra
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      attribution: "© OpenStreetMap contributors",
    }).addTo(this.#mapa);
    this.#mapa.on("click", (e) => this.#manejarClickMapa(e));
    setTimeout(() => this.#mapa.invalidateSize(), 100);
  }

  async #geocodificarPunto(lat, lng) {
    try {
      const res = await fetch(
        `https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json&accept-language=es`,
      );
      const data = await res.json();
      const partes = (
        data.display_name || `${lat.toFixed(4)}, ${lng.toFixed(4)}`
      ).split(",");
      return partes[0].trim() + (partes[1] ? ", " + partes[1].trim() : "");
    } catch {
      return `${lat.toFixed(4)}, ${lng.toFixed(4)}`;
    }
  }

  #crearMarcadorLeaflet(lat, lng, esOrigen) {
    return L.marker([lat, lng], {
      icon: L.divIcon({
        html: esOrigen ? "🟢" : "🔴",
        className: "mapa-icono",
        iconSize: [24, 24],
        iconAnchor: [12, 12],
      }),
    }).addTo(this.#mapa);
  }

  // Clic en mapa: si ya hay 2 marcadores, resetea ambos y el clic actual
  // se convierte en el nuevo origen. Si hay 0 → origen, si hay 1 → destino.
  async #manejarClickMapa(e) {
    const { lat, lng } = e.latlng;

    if (this.#marcadores.length >= 2) {
      this.#resetMarcadores();
    }

    const esOrigen = this.#marcadores.length === 0;
    const labelEl = document.getElementById(
      esOrigen ? "labelOrigen" : "labelDestino",
    );

    labelEl.textContent = "Buscando nombre...";
    labelEl.classList.remove("wp-vacio");

    const marker = this.#crearMarcadorLeaflet(lat, lng, esOrigen);
    const nombre = await this.#geocodificarPunto(lat, lng);

    labelEl.textContent = nombre;
    this.#marcadores.push({ marker, lat, lng, nombre });

    if (this.#marcadores.length === 2) {
      document.getElementById("btnCalcular").hidden = false;
    }
  }

  // Botón GPS: reemplaza solo el origen, respeta el destino si ya estaba puesto.
  async #usarUbicacionActual() {
    if (!navigator.geolocation) {
      alert("Tu navegador no soporta geolocalización.");
      return;
    }

    const btn = document.getElementById("btnUbicacion");
    btn.textContent = "Buscando...";
    btn.disabled = true;

    navigator.geolocation.getCurrentPosition(
      async (pos) => {
        const { latitude: lat, longitude: lng } = pos.coords;

        // Quita el marcador de origen anterior si existe
        if (this.#marcadores.length > 0) {
          this.#mapa.removeLayer(this.#marcadores[0].marker);
          this.#marcadores.shift();
          this.#rutaPersonalizada = null;
          document.getElementById("recibo").hidden = true;
          document.getElementById("btnCalcular").hidden = true;
        }

        this.#mapa.setView([lat, lng], 15);

        const labelEl = document.getElementById("labelOrigen");
        labelEl.textContent = "Buscando nombre...";
        labelEl.classList.remove("wp-vacio");

        const marker = this.#crearMarcadorLeaflet(lat, lng, true);
        const nombre = await this.#geocodificarPunto(lat, lng);

        labelEl.textContent = nombre;

        // Inserta origen al frente del array (el destino queda en [1] si ya estaba)
        this.#marcadores.unshift({ marker, lat, lng, nombre });

        btn.textContent = "📍 Mi ubicación";
        btn.disabled = false;

        // Si el destino ya estaba marcado, habilita recalcular
        if (this.#marcadores.length === 2) {
          document.getElementById("btnCalcular").hidden = false;
        }
      },
      (err) => {
        btn.textContent = "📍 Mi ubicación";
        btn.disabled = false;
        const msgs = {
          1: "Permiso de ubicación denegado. Permitilo en tu navegador.",
          2: "Ubicación no disponible.",
          3: "Tiempo de espera agotado.",
        };
        alert(msgs[err.code] || "No se pudo obtener tu ubicación.");
      },
      { timeout: 10000, enableHighAccuracy: true },
    );
  }

  #resetMarcadores() {
    this.#marcadores.forEach((m) => this.#mapa.removeLayer(m.marker));
    this.#marcadores = [];
    this.#rutaPersonalizada = null;
    document.getElementById("btnCalcular").hidden = true;
    document.getElementById("recibo").hidden = true;
    const lo = document.getElementById("labelOrigen");
    const ld = document.getElementById("labelDestino");
    lo.textContent = "Clic en el mapa para marcar el origen";
    lo.classList.add("wp-vacio");
    ld.textContent = "Destino no seleccionado";
    ld.classList.add("wp-vacio");
  }

  // ── Cotización ────────────────────────────────────────────────────────────

  async #calcularRutaPersonalizada() {
    if (this.#marcadores.length < 2) return;

    const btn = document.getElementById("btnCalcular");
    btn.textContent = "Calculando Cotizacion...";
    btn.disabled = true;

    const [o, d] = this.#marcadores;

    const res = await fetch(`${this.#API}/api/RutaPersonalizadaController.php`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        origin_lat: o.lat,
        origin_lon: o.lng,
        origin_name: o.nombre,
        dest_lat: d.lat,
        dest_lon: d.lng,
        dest_name: d.nombre,
      }),
    });

    btn.textContent = "Calcular ruta";
    btn.disabled = false;

    const data = await res.json();

    if (data.error) {
      alert("Error: " + data.error);
      return;
    }

    this.#rutaPersonalizada = data;
    this.#cotizar(true);
  }

  #bindTarifas() {
    document.getElementById("tarifaGrid").addEventListener("click", (e) => {
      const btn = e.target.closest(".tarifa-btn");
      if (!btn) return;
      document
        .querySelectorAll(".tarifa-btn")
        .forEach((b) => b.classList.remove("active"));
      btn.classList.add("active");
      this.#tarifaActiva = btn.dataset.tarifa;
      if (this.#rutaPersonalizada) {
        document.getElementById("lineaRecargo").hidden = true; // limpia recargo mientras llega la preview
        this.#cotizar(true);
      }
    });
  }

  #bindCotizar() {
    document
      .getElementById("btnCotizar")
      .addEventListener("click", () => this.#cotizar(false));
    document
      .getElementById("btnCalcular")
      .addEventListener("click", () => this.#calcularRutaPersonalizada());
    document
      .getElementById("btnUbicacion")
      .addEventListener("click", () => this.#usarUbicacionActual());
  }

  async #cargarHistorial() {
    const res = await fetch(`${this.#API}/api/HistorialController.php`);
    const data = await res.json();
    this.#renderHistorial(data);
  }

  async #cotizar(preview = false) {
    if (!this.#rutaPersonalizada) return;

    if (this.#cotizarController) this.#cotizarController.abort();
    this.#cotizarController = new AbortController();
    const nonce = ++this.#cotizarNonce;

    try {
      const res = await fetch(`${this.#API}/api/CotizadorController.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          ruta_id: this.#rutaPersonalizada.ruta_id,
          tarifa: this.#tarifaActiva,
          preview,
        }),
        signal: this.#cotizarController.signal,
      });

      const data = await res.json();
      if (nonce !== this.#cotizarNonce) return; // respuesta obsoleta, descartada
      this.#cotizarController = null;

      if (data.error) { alert(data.error); return; }

      this.#renderRecibo(data);
      if (!preview) this.#cargarHistorial();
    } catch (e) {
      if (e.name !== "AbortError") throw e;
    }
  }

  // ── Render ────────────────────────────────────────────────────────────────

  #renderRecibo(d) {
    const recibo = document.getElementById("recibo");
    if (recibo.hidden) recibo.hidden = false;

    document.getElementById("reciboRuta").textContent = d.ruta;
    document.getElementById("reciboKm").textContent = `${d.km} km`;
    document.getElementById("reciboPrecioBase").textContent =
      `$${d.precio_base.toFixed(2)}`;
    document.getElementById("reciboTotal").textContent =
      `$${d.precio_final.toFixed(2)}`;

    const badge = document.getElementById("reciboBadge");
    badge.textContent = d.tarifa;
    badge.className = `badge ${d.tarifa}`;

    const lineaRecargo = document.getElementById("lineaRecargo");
    if (d.recargo > 0) {
      lineaRecargo.hidden = false;
      document.getElementById("reciboRecargoNota").textContent =
        d.recargo_nota ?? "Recargo";
      document.getElementById("reciboRecargo").textContent =
        `$${d.recargo.toFixed(2)}`;
    } else {
      lineaRecargo.hidden = true;
      document.getElementById("reciboRecargoNota").textContent = "";
      document.getElementById("reciboRecargo").textContent = "";
    }
  }

  #renderHistorial(filas) {
    const lista = document.getElementById("historialLista");

    if (!filas.length) {
      lista.innerHTML = '<p class="vacio">Sin cotizaciones aún</p>';
      return;
    }

    lista.innerHTML = filas
      .map(
        (f) => `
        <div class="cotizacion-card">
          <div class="cc-ruta">${f.ruta}</div>
          <div class="cc-detalle">
            <span class="badge ${f.tarifa}">${f.tarifa}</span>
            <span class="cc-km">${f.km} km</span>
            <strong class="cc-total">$${parseFloat(f.precio_final).toFixed(2)}</strong>
          </div>
        </div>
      `,
      )
      .join("");
  }
}

new Vista();
