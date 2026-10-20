document.addEventListener('DOMContentLoaded', function () {
    const botones = document.querySelectorAll('.btn-ver-examen');
    if (!botones.length) return;

    botones.forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('det-titulo').textContent = this.dataset.titulo || '';
            document.getElementById('det-materia').textContent = this.dataset.materia || '';
            document.getElementById('det-curso').textContent = this.dataset.curso || '';
            document.getElementById('det-fecha').textContent = this.dataset.fecha || '';
            document.getElementById('det-hora').textContent = this.dataset.hora || '';
            document.getElementById('det-observacion').textContent = this.dataset.observacion || 'Sin observación';

            $('#modalDetalleExamen').modal('show');
        });
    });
});