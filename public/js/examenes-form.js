// Programar examen (admin): docente y materia van juntos.
// - Al elegir un docente, la materia es la suya y no se puede cambiar.
// - Al elegir primero la materia, solo se muestran sus docentes (si hay uno, se elige solo).
// Para cambiar, se quita el docente (o se usa "Limpiar"). El servidor valida lo mismo al guardar.
document.addEventListener('DOMContentLoaded', function () {
    var docenteSelect = document.getElementById('docente_id');
    var materiaSelect = document.getElementById('materia_id');
    var ayuda = document.getElementById('ayuda-docente-materia');
    var limpiar = document.getElementById('limpiar-docente-materia');

    if (!docenteSelect || !materiaSelect) return;

    function opciones(select) {
        return Array.prototype.filter.call(select.options, function (o) { return o.value !== ''; });
    }

    function mostrar(opcion, visible) {
        opcion.hidden = !visible;
        opcion.disabled = !visible;
    }

    function materiaDelDocente() {
        var opcion = docenteSelect.options[docenteSelect.selectedIndex];
        return opcion ? opcion.getAttribute('data-materia') || '' : '';
    }

    function aplicar() {
        var docente = docenteSelect.value;

        if (docente) {
            materiaSelect.value = materiaDelDocente();
        }
        var materia = materiaSelect.value;

        // Docentes: solo los de la materia elegida
        var docentesDeLaMateria = opciones(docenteSelect).filter(function (o) {
            var coincide = !materia || o.getAttribute('data-materia') === materia;
            mostrar(o, coincide);
            return coincide;
        });

        // Materia: fija mientras haya un docente elegido
        opciones(materiaSelect).forEach(function (o) {
            mostrar(o, !docente || o.value === materia);
        });
        materiaSelect.options[0].disabled = !!docente;

        if (!ayuda) return;
        ayuda.classList.remove('text-danger');
        if (docente) {
            ayuda.textContent = 'La materia es la del docente elegido. Para cambiarla, quite el docente o use "Limpiar".';
        } else if (materia && !docentesDeLaMateria.length) {
            ayuda.textContent = 'Ningún docente activo dicta esta materia.';
            ayuda.classList.add('text-danger');
        } else if (materia) {
            ayuda.textContent = 'Se muestran solo los docentes de esta materia.';
        } else {
            ayuda.textContent = 'Elija el docente o la materia: el otro campo se completa según corresponda.';
        }
    }

    docenteSelect.addEventListener('change', aplicar);

    materiaSelect.addEventListener('change', function () {
        if (!docenteSelect.value) {
            var docentes = opciones(docenteSelect).filter(function (o) {
                return o.getAttribute('data-materia') === materiaSelect.value;
            });
            // Si la materia tiene un solo docente, se asigna directamente
            if (materiaSelect.value && docentes.length === 1) {
                docenteSelect.value = docentes[0].value;
            }
        }
        aplicar();
    });

    if (limpiar) {
        limpiar.addEventListener('click', function () {
            docenteSelect.value = '';
            materiaSelect.value = '';
            aplicar();
        });
    }

    // Al volver con errores de validación, respeta lo que ya estaba elegido
    aplicar();
});
