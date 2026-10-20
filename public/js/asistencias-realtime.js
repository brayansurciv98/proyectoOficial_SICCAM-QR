document.addEventListener('DOMContentLoaded', function () {
    if (typeof Echo === 'undefined') return;

    // Registro nuevo de asistencia
    Echo.channel('asistencias-channel')
        .listen('.AsistenciaRegistrada', function (e) {
            var data = e.asistencia;
            var estudiante = data.estudiante;

            if (typeof Toastify !== 'undefined') {
                Toastify({
                    text: 'Asistencia registrada: ' + estudiante.nombres + ' ' + estudiante.apellidos,
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

            // La fila de la tabla la agrega public/js/asistencias.js (solo en la vista de asistencias)
        });

    // QR ya registrado hoy
    Echo.channel('asistencias-channel')
        .listen('.AsistenciaYaRegistrada', function (e) {
            var data = e.data || e;
            var est = data.estudiante || {};
            var nombre = est.nombre || ((est.nombres || '') + ' ' + (est.apellidos || '')).trim();
            var hora = data.hora_ingreso || '—';

            if (typeof Toastify !== 'undefined') {
                Toastify({
                    text: 'Estudiante: ' + nombre + ', ya registrado (ingreso: ' + hora + ')',
                    duration: 5000,
                    gravity: 'top',
                    position: 'right',
                    style: {
                        background: 'linear-gradient(to right, #e65100, #ef6c00)',
                        borderRadius: '8px',
                        boxShadow: '0 4px 12px rgba(0,0,0,0.15)'
                    }
                }).showToast();
            }
        });
});