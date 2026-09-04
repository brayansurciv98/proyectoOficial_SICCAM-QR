document.addEventListener('DOMContentLoaded', function () {
    function soloNumeros() {
        this.value = this.value.replace(/[^0-9]/g, '');
    }

    function soloLetras() {
        this.value = this.value.replace(/[^A-Za-zÁÉÍÓÚáéíóúÑñÜü\s]/g, '');
    }

    ['ci', 'telefono'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) el.addEventListener('input', soloNumeros);
    });

    ['nombres', 'apellidos'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) el.addEventListener('input', soloLetras);
    });

    var container = document.getElementById('cursosContainer');
    var btnAdd = document.getElementById('btnAddCurso');

    // Índice inicial: lo lee del data-attribute del contenedor
    var cursoIndex = 1;
    if (container && container.dataset.cursoIndex) {
        cursoIndex = parseInt(container.dataset.cursoIndex, 10) || 1;
    }

    function opcionesCurso() {
        var html = '<option value="">Curso</option>';
        for (var n = 1; n <= 6; n++) {
            html += '<option value="' + n + '">' + n + '°</option>';
        }
        return html;
    }

    function opcionesParalelo() {
        return ''
            + '<option value="">Paralelo</option>'
            + '<option value="A">A</option>'
            + '<option value="B">B</option>'
            + '<option value="C">C</option>';
    }

    if (btnAdd && container) {
        btnAdd.addEventListener('click', function () {
            var row = document.createElement('div');
            row.className = 'row curso-item mb-2';
            row.innerHTML = ''
                + '<div class="col-5">'
                +   '<select name="cursos[' + cursoIndex + '][curso]" class="form-control" required>'
                +     opcionesCurso()
                +   '</select>'
                + '</div>'
                + '<div class="col-5">'
                +   '<select name="cursos[' + cursoIndex + '][paralelo]" class="form-control" required>'
                +     opcionesParalelo()
                +   '</select>'
                + '</div>'
                + '<div class="col-2">'
                +   '<button type="button" class="btn btn-outline-danger btn-block btn-remove">×</button>'
                + '</div>';

            container.appendChild(row);
            cursoIndex++;
        });

        container.addEventListener('click', function (e) {
            if (e.target.classList.contains('btn-remove')) {
                var items = container.querySelectorAll('.curso-item');
                if (items.length > 1) {
                    e.target.closest('.curso-item').remove();
                }
            }
        });
    }
});