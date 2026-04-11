/**
 * SPP Validations - Client side validation helpers
 */
function validateRequired(id, msg, errorHolder) {
    const elem = document.getElementById(id);
    const err = document.getElementById(errorHolder);
    if (!elem) return true;

    if (!elem.value || elem.value.trim() === '') {
        elem.classList.add('spp-invalid');
        if (err) {
            err.innerHTML = msg;
            err.style.display = 'block';
        }
        return false;
    } else {
        elem.classList.remove('spp-invalid');
        if (err) {
            err.innerHTML = '';
            err.style.display = 'none';
        }
        return true;
    }
}

function validateNumeric(id, msg, errorHolder) {
    const elem = document.getElementById(id);
    const err = document.getElementById(errorHolder);
    if (!elem) return true;

    if (isNaN(elem.value) || elem.value.trim() === '') {
        elem.classList.add('spp-invalid');
        if (err) {
            err.innerHTML = msg;
            err.style.display = 'block';
        }
        return false;
    } else {
        elem.classList.remove('spp-invalid');
        if (err) {
            err.innerHTML = '';
            err.style.display = 'none';
        }
        return true;
    }
}
