document.addEventListener('DOMContentLoaded', function () {
    function soloNumeros() {
        this.value = this.value.replace(/[^0-9]/g, '');
    }

    function soloLetras() {
        this.value = this.value.replace(/[^A-Za-zÁÉÍÓÚáéíóúÑñÜü\s]/g, '');
    }

    function soloLetrasSinEspacio() {
        this.value = this.value.replace(/[^A-Za-zÁÉÍÓÚáéíóúÑñÜü]/g, '').toUpperCase();
    }

    function cursoSecundaria() {
        // Solo dígitos
        this.value = this.value.replace(/[^0-9]/g, '');

        // Si tiene valor, forzar rango 1-6
        if (this.value !== '') {
            let n = parseInt(this.value, 10);
            if (isNaN(n)) {
                this.value = '';
                return;
            }
            if (n < 1) n = 1;
            if (n > 6) n = 6;
            this.value = String(n);
        }
    }

    // Solo números (CI, teléfono)
    ['tutor_ci', 'tutor_telefono', 'ci'].forEach(function (id) {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', soloNumeros);
    });

    // Curso 1-6
    const curso = document.getElementById('curso');
    if (curso) curso.addEventListener('input', cursoSecundaria);

    // Solo letras
    ['tutor_nombres', 'tutor_apellidos', 'nombres', 'apellidos'].forEach(function (id) {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', soloLetras);
    });
    
});