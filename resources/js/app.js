import Alpine from 'alpinejs';

/**
 * Selector de vehículo: marca → modelo → cilindraje → año.
 *
 * El árbol completo (224 vehículos) se descarga una sola vez y la cascada se
 * resuelve en memoria. En el sitio anterior cada paso era una petición al
 * servidor de más de un segundo; aquí no hay ninguna.
 */
Alpine.data('selectorVehiculo', (urlArbol, elegido = null) => ({
    vehiculos: [],
    cargando: true,
    error: false,

    marca: '',
    modelo: '',
    cilindraje: '',
    anio: '',

    async init() {
        await this.cargar();
        this.recordar(elegido);
    },

    /**
     * Deja el tablero mostrando el carro que ya está elegido en la sesión.
     * Sin esto, quien acaba de buscar ve los cuatro campos en blanco y tiene
     * que rehacer el recorrido entero sólo para cambiar de año.
     */
    recordar(id) {
        const v = id ? this.vehiculos.find((x) => x.i === id) : null;
        if (!v) return;

        this.marca = v.ma;
        this.modelo = v.mo;
        this.cilindraje = v.c;
        this.anio = String(v.d);
    },

    /**
     * Con timeout: en la señal intermitente de un taller una petición se puede
     * quedar colgada, y sin corte el tablero se queda en "Encendiendo…" para
     * siempre, con los cuatro campos muertos y sin explicación.
     */
    async cargar() {
        this.cargando = true;
        this.error = false;

        const corte = new AbortController();
        const temporizador = setTimeout(() => corte.abort(), 8000);

        try {
            const respuesta = await fetch(urlArbol, {
                headers: { Accept: 'application/json' },
                signal: corte.signal,
            });
            if (!respuesta.ok) throw new Error(respuesta.status);
            this.vehiculos = await respuesta.json();
        } catch {
            this.error = true;
        } finally {
            clearTimeout(temporizador);
            this.cargando = false;
        }
    },

    unicos(lista) {
        return [...new Set(lista)];
    },

    get marcas() {
        return this.unicos(this.vehiculos.map((v) => v.ma));
    },

    get modelos() {
        if (!this.marca) return [];
        return this.unicos(this.vehiculos.filter((v) => v.ma === this.marca).map((v) => v.mo));
    },

    get cilindrajes() {
        if (!this.modelo) return [];
        return this.unicos(
            this.vehiculos
                .filter((v) => v.ma === this.marca && v.mo === this.modelo)
                .map((v) => v.c),
        );
    },

    /** Un modelo puede tener dos generaciones: se unen sus rangos de años. */
    get anios() {
        if (!this.cilindraje) return [];
        const años = new Set();
        this.candidatos().forEach((v) => {
            for (let a = v.d; a <= v.h; a++) años.add(a);
        });
        return [...años].sort((a, b) => b - a);
    },

    candidatos() {
        return this.vehiculos.filter(
            (v) => v.ma === this.marca && v.mo === this.modelo && v.c === this.cilindraje,
        );
    },

    get vehiculoId() {
        if (!this.anio) return null;
        const año = Number(this.anio);
        return this.candidatos().find((v) => año >= v.d && año <= v.h)?.i ?? null;
    },

    get completo() {
        return this.vehiculoId !== null;
    },

    /** El siguiente paso, dicho en palabras y no sólo con campos que se activan. */
    get pendiente() {
        if (this.cargando || this.error) return '';
        if (!this.marca) return 'Elige la marca para empezar.';
        if (!this.modelo) return `Modelo: ${this.modelos.length} opciones para ${this.marca}.`;
        if (!this.cilindraje) return `Cilindraje: ${this.cilindrajes.length} opciones para ${this.modelo}.`;
        if (!this.anio) return `Año: ${this.anios.length} opciones disponibles.`;
        return 'Listo, ya puedes buscar.';
    },

    /** Lleva el foco al primer campo sin llenar cuando alguien pulsa Buscar. */
    enfocarPendiente() {
        const campo = !this.marca ? 'marca'
            : !this.modelo ? 'modelo'
            : !this.cilindraje ? 'cilindraje'
            : 'anio';

        document.getElementById(`hero-${campo}`)?.focus();
    },

    // Al cambiar un nivel se limpian los de abajo: si no, queda un modelo de
    // Chevrolet colgando después de escoger Renault.
    cambiarMarca() {
        this.modelo = this.cilindraje = this.anio = '';
    },
    cambiarModelo() {
        this.cilindraje = this.anio = '';
    },
    cambiarCilindraje() {
        this.anio = '';
    },
}));

/**
 * Añadir a la cotización sin recargar la página.
 *
 * El formulario sigue siendo un POST normal: si el JavaScript no carga, el
 * botón funciona igual y el servidor responde con la redirección de siempre.
 * Esto sólo evita el viaje de ida y vuelta cuando sí hay navegador moderno.
 */
Alpine.data('agregarACotizacion', () => ({
    enviando: false,
    listo: false,

    async enviar(evento) {
        if (this.enviando) return;
        this.enviando = true;

        const formulario = evento.target;

        try {
            const respuesta = await fetch(formulario.action, {
                method: 'POST',
                headers: { Accept: 'application/json' },
                body: new FormData(formulario),
            });

            if (!respuesta.ok) throw new Error(respuesta.status);

            const datos = await respuesta.json();

            // El contador de la cabecera vive en otro componente: se entera por
            // el evento, no por una referencia directa.
            window.dispatchEvent(new CustomEvent('cotizacion-actualizada', {
                detail: { total: datos.total, mensaje: datos.mensaje },
            }));

            this.listo = true;
            setTimeout(() => (this.listo = false), 2500);
        } catch {
            // Si algo falla, se manda el formulario a la antigua antes que
            // dejar al visitante creyendo que agregó algo que no agregó.
            formulario.submit();
            return;
        } finally {
            this.enviando = false;
        }
    },
}));

/** Sugerencias del buscador mientras se escribe. */
Alpine.data('buscadorSugerencias', (urlSugerencias) => ({
    termino: '',
    sugerencias: [],
    abierto: false,
    temporizador: null,

    escribir() {
        clearTimeout(this.temporizador);

        if (this.termino.trim().length < 3) {
            this.sugerencias = [];
            this.abierto = false;
            return;
        }

        // Espera a que el usuario deje de escribir: sin esto se dispara una
        // consulta por cada tecla.
        this.temporizador = setTimeout(() => this.consultar(), 220);
    },

    async consultar() {
        try {
            const url = `${urlSugerencias}?q=${encodeURIComponent(this.termino)}`;
            const respuesta = await fetch(url, { headers: { Accept: 'application/json' } });
            this.sugerencias = respuesta.ok ? await respuesta.json() : [];
            this.abierto = this.sugerencias.length > 0;
        } catch {
            this.sugerencias = [];
            this.abierto = false;
        }
    },

    cerrar() {
        this.abierto = false;
    },
}));

window.Alpine = Alpine;
Alpine.start();
