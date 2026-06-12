class Vista {
  #API = "http://localhost:8000";
  #tarifaActiva = "normal";

  constructor() {
    this.#bindTarifas();
    this.#bindCotizar();
    this.#cargarRutas();
    this.#cargarHistorial();
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
      this.#cotizar(true);
    });
  }

  #bindCotizar() {
    document
      .getElementById("btnCotizar")
      .addEventListener("click", () => this.#cotizar(false));

    document
      .getElementById("ruta")
      .addEventListener("change", () => this.#cotizar(true));
  }

  async #cargarRutas() {
    const res = await fetch(`${this.#API}/api/rutas.php`);
    const rutas = await res.json();
    const sel = document.getElementById("ruta");
    sel.innerHTML = rutas
      .map((r) => `<option value="${r.id}">${r.nombre} — ${r.km} km</option>`)
      .join("");
  }

  async #cargarHistorial() {
    const res = await fetch(`${this.#API}/api/historial.php`);
    const data = await res.json();
    this.#renderHistorial(data);
  }

  async #cotizar(preview = false) {
    const rutaId = document.getElementById("ruta").value;
    if (!rutaId) return;

    const res = await fetch(`${this.#API}/api/cotizar.php`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        ruta_id: parseInt(rutaId),
        tarifa: this.#tarifaActiva,
        preview,
      }),
    });

    const data = await res.json();

    if (data.error) {
      alert(data.error);
      return;
    }

    this.#renderRecibo(data);
    if (!preview) this.#cargarHistorial();
  }

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
    }
  }

  #renderHistorial(filas) {
    const tbody = document.getElementById("historialBody");

    if (!filas.length) {
      tbody.innerHTML =
        '<tr><td colspan="6" class="vacio">Sin cotizaciones aún</td></tr>';
      return;
    }

    tbody.innerHTML = filas
      .map(
        (f) => `
            <tr>
                <td>${f.ruta}</td>
                <td><span class="badge ${f.tarifa}">${f.tarifa}</span></td>
                <td>${f.km} km</td>
                <td>$${parseFloat(f.precio_base).toFixed(2)}</td>
                <td>${f.recargo > 0 ? "$" + parseFloat(f.recargo).toFixed(2) : "—"}</td>
                <td><strong>$${parseFloat(f.precio_final).toFixed(2)}</strong></td>
            </tr>
        `,
      )
      .join("");
  }
}

new Vista();
