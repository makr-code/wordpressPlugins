/**
 * ThemisDB Theme – contact-form.js
 * Formular-Validierung + AJAX-Submit (server-seitig via functions.php)
 */
(function () {
    'use strict';

    const form    = document.getElementById('themisdb-contact-form');
    if (!form)  return;

    const behoerde  = document.getElementById('themisdb-cf-behoerde');
    const email     = document.getElementById('themisdb-cf-email');
    const errEl     = document.getElementById('themisdb-cf-error');
    const sucEl     = document.getElementById('themisdb-cf-success');
    const submitBtn = form.querySelector('[type="submit"]');

    function showError(msg) {
        if (!errEl) return;
        errEl.textContent = msg;
        errEl.style.display = 'block';
        if (sucEl) sucEl.style.display = 'none';
    }

    function showSuccess(msg) {
        if (!sucEl) return;
        sucEl.textContent = msg;
        sucEl.style.display = 'block';
        if (errEl) errEl.style.display = 'none';
    }

    function clearMessages() {
        if (errEl) errEl.style.display = 'none';
        if (sucEl) sucEl.style.display = 'none';
    }

    function getContactEmail() {
        return window.themisdbThemeData?.contactEmail || 'admin@example.invalid';
    }

    function isValidEmail(val) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val);
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        clearMessages();

        const behVal   = behoerde?.value.trim() ?? '';
        const emailVal = email?.value.trim()    ?? '';

        /* Validierung */
        if (!behVal) {
            showError('Bitte geben Sie Ihre Behörde / Institution an.');
            behoerde?.focus();
            return;
        }
        if (!emailVal || !isValidEmail(emailVal)) {
            showError('Bitte geben Sie eine gültige dienstliche E-Mail-Adresse an.');
            email?.focus();
            return;
        }

        /* Kein AJAX-Endpunkt: Mailto-Fallback öffnen (clientseitig) */
        if (!window.themisdbThemeData || !window.themisdbThemeData.ajaxUrl) {
            const contactEmail = getContactEmail();
            const subject = encodeURIComponent('ThemisDB Zugangangsanfrage von ' + behVal);
            const body    = encodeURIComponent(
                'Behörde: ' + behVal + '\r\nE-Mail: ' + emailVal + '\r\n\r\nBitte nehmen Sie Kontakt auf.'
            );
            window.location.href = 'mailto:' + encodeURIComponent(contactEmail) + '?subject=' + subject + '&body=' + body;
            showSuccess('Ihr E-Mail-Client wurde geöffnet. Bitte senden Sie die Nachricht ab.');
            return;
        }

        /* AJAX-Request */
        if (submitBtn) submitBtn.disabled = true;

        const data = new URLSearchParams({
            action:   'themisdb_contact',
            nonce:    window.themisdbThemeData.contactNonce || '',
            behoerde: behVal,
            email:    emailVal,
        });

        fetch(window.themisdbThemeData.ajaxUrl, {
            method:      'POST',
            credentials: 'same-origin',
            headers:     { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:        data.toString(),
        })
        .then(function (res) { return res.json(); })
        .then(function (json) {
            if (json.success) {
                showSuccess(json.data?.message || 'Ihre Anfrage wurde erfolgreich gesendet. Wir melden uns in Kürze.');
                form.reset();
            } else {
                showError(json.data?.message || 'Fehler beim Senden. Bitte versuchen Sie es erneut oder schreiben Sie direkt an ' + getContactEmail() + '.');
            }
        })
        .catch(function () {
            showError('Netzwerkfehler. Bitte schreiben Sie direkt an ' + getContactEmail() + '.');
        })
        .finally(function () {
            if (submitBtn) submitBtn.disabled = false;
        });
    });
})();
