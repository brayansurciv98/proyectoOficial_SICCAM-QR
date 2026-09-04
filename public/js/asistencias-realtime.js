document.addEventListener('DOMContentLoaded', function () {
    if (typeof Echo === 'undefined') return;

    Echo.channel('asistencias-channel')
        .listen('.AsistenciaRegistrada', function (e) {
            var data = e.asistencia;
            var estudiante = data.estudiante;

            if (typeof Toastify !== 'undefined') {
                Toastify({
                    text: '⚡ Asistencia Registrada: ' + estudiante.nombres + ' ' + estudiante.apellidos,
                    duration: 4500,
                    gravity: 'top',
                    position: 'right',
                    style: {
                        background: 'linear-gradient(to right, #1a5f2a, #2e7d32)',
                        borderRadius: '8px',
                        boxShadow: '0 4px 12px rgba(0,0,0,0.15)'
                    }
                }).showToast();
            }

            var tablaBody = document.querySelector('#tabla-asistencias-body');
            if (tablaBody) {
                var noDataRow = document.querySelector('#sin-registros-row');
                if (noDataRow) noDataRow.remove();

                var badgeColor = data.estado === 'atraso' ? 'warning' : 'success';
                var fila = ''
                    + '<tr class="row-realtime">'
                    + '<td class="font-weight-bold text-success">' + estudiante.codigo_estudiante + '</td>'
                    + '<td>' + estudiante.nombres + ' ' + estudiante.apellidos + '</td>'
                    + '<td>' + estudiante.curso + 'º de Secundaria - ' + (estudiante.paralelo || '') + '</td>'
                    + '<td>' + data.fecha + '</td>'
                    + '<td class="font-weight-bold">' + data.hora_ingreso + '</td>'
                    + '<td><span class="badge badge-' + badgeColor + ' px-2 py-1">' + data.estado.toUpperCase() + '</span></td>'
                    + '<td class="text-center"><span class="badge badge-info">Nuevo</span></td>'
                    + '</tr>';

                tablaBody.insertAdjacentHTML('afterbegin', fila);
            }

            var contadorPresentes = document.querySelector('#contador-presentes');
            if (contadorPresentes) {
                contadorPresentes.innerText = parseInt(contadorPresentes.innerText || 0, 10) + 1;
            }

            var contadorAtrasos = document.querySelector('#contador-atrasos');
            if (contadorAtrasos && data.estado === 'atraso') {
                contadorAtrasos.innerText = parseInt(contadorAtrasos.innerText || 0, 10) + 1;
            }
        });
});