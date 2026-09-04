document.addEventListener('DOMContentLoaded', function () {
    var docenteSelect = document.getElementById('docente_id');
    var materiaSelect = document.getElementById('materia_id');

    if (!docenteSelect || !materiaSelect) return;

    docenteSelect.addEventListener('change', function () {
        var option = docenteSelect.options[docenteSelect.selectedIndex];
        var materiaId = option.getAttribute('data-materia');
        if (materiaId) {
            materiaSelect.value = materiaId;
        }
    });
});