// Buscador al instante para tablas: <input data-filtro-tabla="id-de-la-tabla">
// Oculta las filas que no contienen todas las palabras escritas (sin importar mayúsculas ni tildes).
// Las filas ocultas siguen dentro del formulario: solo se dejan de ver.
document.addEventListener('DOMContentLoaded', function () {
    function normalizar(texto) {
        return (texto || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
    }

    document.querySelectorAll('[data-filtro-tabla]').forEach(function (input) {
        var tabla = document.getElementById(input.getAttribute('data-filtro-tabla'));
        if (!tabla || !tabla.tBodies.length) return;

        // Filas de datos (no la fila de "no hay registros")
        var filas = Array.prototype.filter.call(tabla.tBodies[0].rows, function (fila) {
            return !fila.hasAttribute('data-sin-filtro');
        });
        if (!filas.length) return;

        var textos = filas.map(function (fila) { return normalizar(fila.textContent); });

        var aviso = document.createElement('div');
        aviso.className = 'text-center text-muted py-3 d-none';
        tabla.parentNode.insertBefore(aviso, tabla.nextSibling);

        input.addEventListener('input', function () {
            var palabras = normalizar(input.value).split(/\s+/).filter(Boolean);
            var visibles = 0;

            filas.forEach(function (fila, i) {
                var coincide = palabras.every(function (p) { return textos[i].indexOf(p) !== -1; });
                fila.style.display = coincide ? '' : 'none';
                if (coincide) visibles++;
            });

            aviso.textContent = 'Ningún resultado para "' + input.value.trim() + '".';
            aviso.classList.toggle('d-none', visibles > 0);
        });

        // Enter en el buscador no debe enviar el formulario de asistencia
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') e.preventDefault();
        });
    });
});
