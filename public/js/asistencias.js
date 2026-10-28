document.addEventListener('DOMContentLoaded', function () {
    // Cámara de la Raspberry: si se cae, mostrar aviso y reintentar cada 5 s
    const camara = document.getElementById('camara-raspberry');
    const avisoCamara = document.getElementById('camara-desconectada');
    if (camara && avisoCamara) {
        const urlEstado = new URL(camara.dataset.src).origin + '/estado';

        const esperarCamara = () => {
            fetch(urlEstado, { cache: 'no-store' })
                .then((r) => r.json())
                .then((estado) => {
                    if (!estado.camara_ok) throw new Error('cámara sin imagen');
                    camara.src = camara.dataset.src + '?t=' + Date.now();
                    camara.classList.remove('d-none');
                    avisoCamara.classList.add('d-none');
                })
                .catch(() => setTimeout(esperarCamara, 5000));
        };

        camara.addEventListener('error', () => {
            camara.classList.add('d-none');
            avisoCamara.classList.remove('d-none');
            setTimeout(esperarCamara, 5000);
        });
    }

    // Verificar si Laravel Echo está inicializado
    if (typeof Echo === 'undefined') {
        console.warn('Laravel Echo no está definido en este entorno.');
        return;
    }

    // Escuchar el canal en tiempo real
    Echo.channel('asistencias-channel')
        .listen('.AsistenciaRegistrada', (e) => {
            const asistencia = e.asistencia;
            const estudiante = e.asistencia.estudiante || {};

            // Remover la fila de "No hay registros" si está visible
            const filaVacia = document.getElementById('fila-vacia');
            if (filaVacia) {
                filaVacia.remove();
            }

            // Construir el badge de estado según el valor
            let badgeEstado = '';
            if (asistencia.estado === 'presente') {
                badgeEstado = '<span class="badge badge-success px-2 py-1"><i class="bi bi-check-circle mr-1"></i>Presente</span>';
            } else if (asistencia.estado === 'tardanza' || asistencia.estado === 'atraso') {
                badgeEstado = '<span class="badge badge-warning px-2 py-1"><i class="bi bi-clock-history mr-1"></i>Tardanza</span>';
            } else {
                badgeEstado = '<span class="badge badge-danger px-2 py-1"><i class="bi bi-x-circle mr-1"></i>Ausente</span>';
            }

            // Formatear datos de la fila
            const hora = asistencia.hora_ingreso ? asistencia.hora_ingreso : '—';
            const cursoTexto = estudiante.curso ? `${estudiante.curso}º de Secundaria` : '';
            const paraleloTexto = estudiante.paralelo ? `- ${estudiante.paralelo}` : '';

            // Ícono según el género, copiado de las plantillas de la vista (cada copia con su propio id)
            const plantilla = document.getElementById('avatar-estudiante-' + (['M', 'F'].includes(estudiante.genero) ? estudiante.genero : ''));
            const avatar = plantilla
                ? plantilla.innerHTML.replace(/av[A-Za-z0-9]{10}/g, 'av' + Math.random().toString(36).slice(2, 12))
                : '';

            // Generar la estructura de la fila HTML
            const nuevaFila = `
                <tr class="table-success" style="transition: background-color 2s ease;">
                    <td class="pl-4 font-weight-bold text-primary">${estudiante.codigo_estudiante || 'N/A'}</td>
                    <td>${avatar}${estudiante.nombres || ''} ${estudiante.apellidos || ''}</td>
                    <td>${cursoTexto} ${paraleloTexto}</td>
                    <td>${new Date().toLocaleDateString('es-ES')}</td>
                    <td class="font-weight-bold">${hora}</td>
                    <td>${badgeEstado}</td>
                    <td class="text-center">
                        <a href="/asistencias/${asistencia.id}" class="btn btn-sm btn-outline-primary" title="Ver detalle">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
            `;

            // Insertar la fila al principio del tbody
            const tbody = document.getElementById('tabla-asistencias-body');
            if (tbody) {
                tbody.insertAdjacentHTML('afterbegin', nuevaFila);
            }
        });
});