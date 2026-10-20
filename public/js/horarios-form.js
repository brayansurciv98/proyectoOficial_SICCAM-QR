document.addEventListener('DOMContentLoaded', function () {
    var docente = document.getElementById('docente_id');
    var materiaId = document.getElementById('materia_id');
    var materiaNombre = document.getElementById('materia_nombre');

    if (!docente || !materiaId || !materiaNombre) return;

    function syncMateria() {
        var option = docente.options[docente.selectedIndex];
        if (!option || !option.value) {
            materiaId.value = '';
            materiaNombre.value = '';
            return;
        }
        materiaId.value = option.getAttribute('data-materia') || '';
        materiaNombre.value = option.getAttribute('data-materia-nombre') || '';
    }

    docente.addEventListener('change', syncMateria);
    syncMateria();
});