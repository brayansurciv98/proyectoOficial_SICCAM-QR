document.addEventListener('DOMContentLoaded', function () {
    const cursoSelect = document.getElementById('cursoSelect');
    const paraleloSelect = document.getElementById('paraleloSelect');
    const btnAdd = document.getElementById('btnAddCurso');
    const list = document.getElementById('cursosAsignadosList');
    const hidden = document.getElementById('cursosHiddenInputs');

    // Si no existen estos elementos, no estamos en create/edit de docentes
    if (!cursoSelect || !paraleloSelect || !btnAdd || !list || !hidden) {
        return;
    }

    // En edit vienen cursos previos; en create empieza vacío
    let cursos = [];

    const dataBox = document.getElementById('cursos-iniciales-data');
    if (dataBox) {
        try {
            const raw = dataBox.getAttribute('data-cursos') || '[]';
            const parsed = JSON.parse(raw);
            if (Array.isArray(parsed)) {
                cursos = parsed.map(c => ({
                    curso: String(c.curso),
                    paralelo: String(c.paralelo)
                }));
            }
        } catch (e) {
            cursos = [];
        }
    }

    function render() {
        list.innerHTML = '';
        hidden.innerHTML = '';

        if (!cursos.length) {
            list.innerHTML = '<p class="text-muted mb-0">Aún no hay cursos asignados.</p>';
            return;
        }

        cursos.forEach((item, index) => {
            const row = document.createElement('div');
            row.className = 'd-flex justify-content-between align-items-center border rounded px-3 py-2 mb-2';
            row.innerHTML = `
                <span><strong>${item.curso}°</strong> - Paralelo <strong>${item.paralelo}</strong></span>
                <button type="button" class="btn btn-sm btn-outline-danger" data-index="${index}">×</button>
            `;
            list.appendChild(row);

            hidden.innerHTML += `
                <input type="hidden" name="cursos[${index}][curso]" value="${item.curso}">
                <input type="hidden" name="cursos[${index}][paralelo]" value="${item.paralelo}">
            `;
        });

        list.querySelectorAll('button[data-index]').forEach(btn => {
            btn.addEventListener('click', function () {
                const i = parseInt(this.dataset.index, 10);
                cursos.splice(i, 1);
                render();
            });
        });
    }

    btnAdd.addEventListener('click', function () {
        const curso = cursoSelect.value;
        const paralelo = paraleloSelect.value;

        if (!curso || !paralelo) {
            alert('Selecciona curso y paralelo');
            return;
        }

        const existe = cursos.some(
            c => String(c.curso) === String(curso) && String(c.paralelo) === String(paralelo)
        );

        if (existe) {
            alert('Ese curso y paralelo ya fue asignado');
            return;
        }

        cursos.push({
            curso: String(curso),
            paralelo: String(paralelo)
        });

        render();

        cursoSelect.value = '';
        paraleloSelect.value = '';
    });

    render();
});