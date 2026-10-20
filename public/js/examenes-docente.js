document.addEventListener('DOMContentLoaded', function () {
    const select = document.getElementById('curso_paralelo');
    const curso = document.getElementById('curso');
    const paralelo = document.getElementById('paralelo');

    if (!select || !curso || !paralelo) return;

    function sync() {
        const value = select.value || '';
        if (!value.includes('|')) {
            curso.value = '';
            paralelo.value = '';
            return;
        }
        const parts = value.split('|');
        curso.value = parts[0] || '';
        paralelo.value = parts[1] || '';
    }

    select.addEventListener('change', sync);
    sync();
});