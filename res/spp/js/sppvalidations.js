/**
 * sppvalidations.js
 * Client-side validation library for SPP forms.
 */

function _showSppError(id, msg) {
    const el = document.getElementById(id);
    if (el) {
        el.innerHTML = msg;
        el.style.display = 'block';
        el.classList.add('errormsg');
    }
}

function _clearSppError(id) {
    const el = document.getElementById(id);
    if (el) {
        el.innerHTML = '';
        el.style.display = 'none';
        el.classList.remove('errormsg');
    }
}

function validateRequired(errId, msg, fieldId) {
    const field = document.getElementById(fieldId) || document.getElementsByName(fieldId)[0];
    if (!field) return true;

    if (field.value.trim() === '') {
        _showSppError(errId, msg);
        field.classList.add('errorclass');
        return false;
    } else {
        _clearSppError(errId);
        field.classList.remove('errorclass');
        return true;
    }
}

function validateNumeric(errId, msg, fieldId) {
    const field = document.getElementById(fieldId) || document.getElementsByName(fieldId)[0];
    if (!field) return true;

    if (isNaN(field.value) || field.value.trim() === '') {
        _showSppError(errId, msg);
        field.classList.add('errorclass');
        return false;
    } else {
        _clearSppError(errId);
        field.classList.remove('errorclass');
        return true;
    }
}
