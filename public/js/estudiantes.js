$(document).ready(function () {
    if ($('#tablaEstudiantes').length > 0) {

        // Inicializar DataTables
        var table = $('#tablaEstudiantes').DataTable({
            "dom": 'rtip',
            "pageLength": 10,
            "ordering": true,
            "responsive": true,
            "language": {
                "url": "https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json"
            }
        });

        // 1. Buscador texto libre (Nombre, CI, Código, etc.)
        $('#dtSearch').on('keyup change clear input', function () {
            table.search(this.value).draw();
        });

        // 2. Filtro por Curso (Columna 3)
        $('#dtCurso').on('change', function () {
            var val = $(this).val();
            // Busca de forma flexible el nombre del curso en la columna 3
            table.column(3).search(val ? val : '', false, false).draw();
        });

        // 3. Filtro por Paralelo (Columna 4)
        $('#dtParalelo').on('change', function () {
            var val = $(this).val();
            // Busca de forma flexible la letra del paralelo en la columna 4
            table.column(4).search(val ? val : '', false, false).draw();
        });

        // 4. Filtro por Estado (Columna 7)
        $('#dtEstado').on('change', function () {
            var val = $(this).val();
            table.column(7).search(val ? val : '', false, false).draw();
        });
    }
});