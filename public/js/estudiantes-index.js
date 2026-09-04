// public/js/estudiantes-index.js

$(document.ready || function() {
    // 1. Desplegar automático del Modal de Credenciales si existe en el DOM
    var modalCredenciales = $('#modalCredencialesTutor');
    if (modalCredenciales.length) {
        modalCredenciales.modal('show');
    }

    // 2. Filtros y Búsqueda en la Tabla (DataTables / jQuery)
    $('#dtSearch').on('keyup', function() {
        var valor = $(this).val().toLowerCase();
        $('#tablaEstudiantes tbody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(valor) > -1);
        });
    });

    $('#dtCurso, #dtParalelo, #dtEstado').on('change', function() {
        var curso = $('#dtCurso').val();
        var paralelo = $('#dtParalelo').val();
        var estado = $('#dtEstado').val().toLowerCase();

        $('#tablaEstudiantes tbody tr').each(function() {
            var row = $(this);
            var matchCurso = !curso || row.find('td:nth-child(4)').text().trim() === curso;
            var matchParalelo = !paralelo || row.find('td:nth-child(5)').text().trim() === paralelo;
            var matchEstado = !estado || row.find('td:nth-child(8)').text().toLowerCase().indexOf(estado) > -1;

            row.toggle(matchCurso && matchParalelo && matchEstado);
        });
    });
});

// 3. Función de descarga de credenciales en TXT
function descargarCredencialesTxt() {
    var elem = $('#datosCredencialesTxt');
    if (!elem.length) return;

    var contenido = "==================================================\n" +
                    "       SICCAM TUTOR - CREDENCIALES DE ACCESO      \n" +
                    "==================================================\n\n" +
                    "Estudiante : " + elem.data('estudiante') + "\n" +
                    "Código EST : " + elem.data('codigo') + "\n" +
                    "Tutor      : " + elem.data('tutor') + "\n" +
                    "--------------------------------------------------\n" +
                    "DATOS DE INICIO DE SESIÓN EN APP MÓVIL:\n" +
                    "Usuario (CI/Email): " + elem.data('usuario') + "\n" +
                    "Contraseña        : " + elem.data('password') + "\n" +
                    "--------------------------------------------------\n" +
                    "Guarde este archivo en un lugar seguro.\n";

    var blob = new Blob([contenido], { type: "text/plain;charset=utf-8" });
    var enlace = document.createElement("a");
    enlace.href = URL.createObjectURL(blob);
    enlace.download = "Credenciales_Tutor_" + elem.data('codigo') + ".txt";
    document.body.appendChild(enlace);
    enlace.click();
    document.body.removeChild(enlace);
}