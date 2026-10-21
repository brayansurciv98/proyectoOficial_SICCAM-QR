// Programar examen (admin): guía con el horario semanal del docente elegido.
// - Muestra sus clases por día y bloque (como la hoja de Horarios).
// - Con curso y paralelo elegidos, resalta esas clases y propone las próximas fechas:
//   al hacer clic en una, se llenan la fecha y la hora del examen.
// Es solo una ayuda: al guardar, el servidor valida igual el horario.
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('form-examen');
    var guia = document.getElementById('guia-horario');
    if (!form || !guia) return;

    var horarios = JSON.parse(form.getAttribute('data-horarios') || '[]');
    var examenes = JSON.parse(form.getAttribute('data-examenes') || '[]');

    var campos = form.elements;
    var titulo = document.getElementById('guia-titulo');
    var mensaje = document.getElementById('guia-mensaje');
    var contenido = document.getElementById('guia-contenido');
    var aviso = document.getElementById('guia-aviso');
    var fechas = document.getElementById('guia-fechas');
    var celdas = guia.querySelectorAll('td[data-dia]');

    var DIAS = ['', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
    var DIAS_CORTOS = ['', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
    var SEMANAS = 3; // próximas fechas a proponer

    function elemento(etiqueta, clase, texto) {
        var e = document.createElement(etiqueta);
        if (clase) e.className = clase;
        if (texto !== undefined) e.textContent = texto;
        return e;
    }

    function ymd(fecha) {
        var m = String(fecha.getMonth() + 1).padStart(2, '0');
        var d = String(fecha.getDate()).padStart(2, '0');
        return fecha.getFullYear() + '-' + m + '-' + d;
    }

    function diaIso(fecha) {
        return (fecha.getDay() + 6) % 7 + 1; // 1 = lunes ... 7 = domingo
    }

    function ocupado(curso, paralelo, fecha, clase) {
        return examenes.some(function (e) {
            return e.curso === curso && e.paralelo === paralelo && e.fecha === fecha
                && (!e.hora || (e.hora >= clase.inicio && e.hora < clase.fin));
        });
    }

    function actualizar() {
        var docente = campos.docente_id.value;
        var curso = campos.curso.value;
        var paralelo = campos.paralelo.value;
        var conCurso = !!(curso && paralelo);
        var grupo = curso + '° ' + paralelo;

        aviso.innerHTML = '';
        fechas.innerHTML = '';

        if (!docente) {
            titulo.textContent = '';
            mensaje.textContent = 'Elija un docente (o una materia) para ver en qué días y bloques tiene clase con cada curso.';
            mensaje.classList.remove('d-none');
            contenido.classList.add('d-none');
            return;
        }

        var opcion = campos.docente_id.options[campos.docente_id.selectedIndex];
        titulo.textContent = '· ' + opcion.textContent.trim().replace(/\s+/g, ' ');

        var clases = horarios.filter(function (h) { return String(h.docente) === docente; });
        if (!clases.length) {
            mensaje.textContent = 'Este docente no tiene clases registradas en Horarios: primero regístrelas para poder programarle exámenes.';
            mensaje.classList.remove('d-none');
            contenido.classList.add('d-none');
            return;
        }
        mensaje.classList.add('d-none');
        contenido.classList.remove('d-none');

        // 1. Tabla semanal
        celdas.forEach(function (td) {
            var dia = Number(td.getAttribute('data-dia'));
            var bloque = Number(td.getAttribute('data-bloque'));
            var enCelda = clases.filter(function (h) { return h.dia === dia && h.bloque === bloque; });
            var resaltada = conCurso && enCelda.some(function (h) { return h.curso === curso && h.paralelo === paralelo; });

            td.innerHTML = '';
            td.classList.toggle('table-success', resaltada);
            td.style.opacity = conCurso && enCelda.length && !resaltada ? '0.45' : '';

            if (!enCelda.length) {
                td.appendChild(elemento('span', 'text-muted', '—'));
                return;
            }
            enCelda.forEach(function (h) {
                td.appendChild(elemento('small', 'd-block text-muted', 'CURSO ' + h.curso + '° ' + h.paralelo));
                td.appendChild(elemento('div', 'font-weight-bold small', (h.materia || '').toUpperCase()));
            });
        });

        // Fila del día de la fecha elegida
        var fechaElegida = campos.fecha.value ? new Date(campos.fecha.value + 'T00:00:00') : null;
        guia.querySelectorAll('tbody tr').forEach(function (tr) {
            var esDia = fechaElegida && Number(tr.getAttribute('data-dia')) === diaIso(fechaElegida);
            tr.style.outline = esDia ? '2px solid #f0ad4e' : '';
        });

        if (!conCurso) {
            aviso.appendChild(elemento('small', 'text-muted d-block', 'Elija curso y paralelo para ver las próximas fechas en que puede tomar el examen.'));
            return;
        }

        var conGrupo = clases.filter(function (h) { return h.curso === curso && h.paralelo === paralelo; });
        if (!conGrupo.length) {
            aviso.appendChild(elemento('div', 'alert alert-warning py-2 mb-2',
                'Este docente no tiene clases con ' + grupo + '. Elija otro curso o registre la clase en Horarios.'));
            return;
        }

        // 2. ¿La fecha elegida cae en una de sus clases con ese curso?
        if (fechaElegida) {
            var delDia = conGrupo.filter(function (h) { return h.dia === diaIso(fechaElegida); });
            var nombreDia = DIAS[diaIso(fechaElegida)].toLowerCase();
            if (delDia.length) {
                var horas = delDia.map(function (h) { return h.bloque + '° bloque, ' + h.inicio + ' a ' + h.fin; }).join(' · ');
                aviso.appendChild(elemento('div', 'alert alert-success py-2 mb-2',
                    'El ' + nombreDia + ' tiene clase con ' + grupo + ' (' + horas + ').'));
            } else {
                aviso.appendChild(elemento('div', 'alert alert-warning py-2 mb-2',
                    'El ' + nombreDia + ' no tiene clase con ' + grupo + ': el examen no se podrá guardar en esa fecha.'));
            }
        }

        // 3. Próximas fechas con clase (clic = llena fecha y hora)
        fechas.appendChild(elemento('small', 'font-weight-bold d-block mb-1',
            'Próximas fechas con clase de ' + grupo + ' (clic para elegir):'));

        var hoy = new Date();
        hoy.setHours(0, 0, 0, 0);
        var propuestas = 0;

        for (var i = 0; i < SEMANAS * 7; i++) {
            proponer(new Date(hoy.getFullYear(), hoy.getMonth(), hoy.getDate() + i));
        }

        // Una función por día para que cada botón recuerde su propia fecha
        function proponer(dia) {
            var fecha = ymd(dia);
            conGrupo
                .filter(function (h) { return h.dia === diaIso(dia); })
                .sort(function (a, b) { return a.inicio < b.inicio ? -1 : 1; })
                .forEach(function (h) {
                    var lleno = ocupado(curso, paralelo, fecha, h);
                    var texto = DIAS_CORTOS[h.dia] + ' ' + fecha.slice(8, 10) + '/' + fecha.slice(5, 7)
                        + ' · ' + h.bloque + '° bloque ' + h.inicio + (lleno ? ' (ya tiene examen)' : '');
                    var boton = elemento('button', 'btn btn-sm mr-1 mb-1 ' + (lleno ? 'btn-outline-secondary' : 'btn-outline-success'), texto);
                    boton.type = 'button';
                    boton.disabled = lleno;
                    boton.addEventListener('click', function () {
                        campos.fecha.value = fecha;
                        campos.hora.value = h.inicio;
                        actualizar();
                    });
                    fechas.appendChild(boton);
                    propuestas++;
                });
        }

        if (!propuestas) {
            fechas.appendChild(elemento('small', 'text-muted', 'No hay clases en las próximas ' + SEMANAS + ' semanas.'));
        }
    }

    // El cambio de docente/materia lo hace primero examenes-form.js; aquí solo se vuelve a dibujar
    form.addEventListener('change', actualizar);
    form.addEventListener('input', function (e) {
        if (e.target === campos.fecha) actualizar();
    });
    var limpiar = document.getElementById('limpiar-docente-materia');
    if (limpiar) limpiar.addEventListener('click', actualizar);

    actualizar();
});
