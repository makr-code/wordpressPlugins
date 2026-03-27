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
    const website   = document.getElementById('themisdb-cf-website');
    const behoerdeCount = document.getElementById('themisdb-cf-behoerde-count');
    const errEl     = document.getElementById('themisdb-cf-error');
    const sucEl     = document.getElementById('themisdb-cf-success');
    const submitBtn = form.querySelector('[type="submit"]');
    const submitLabelDefault = submitBtn?.textContent || t('contactSubmitDefault', 'Anfrage senden');
    const BEHOERDE_MAX = 120;
    const LIVE_VALIDATE_DELAY_MS = 180;
    let rateLimitTimer = null;

    function t(key, fallback) {
        return window.themisdbThemeData?.i18n?.[key] || fallback;
    }

    function fmt(template, value) {
        return String(template).replace('%d', value).replace('%s', value);
    }

    function fmt2(template, first, second) {
        return String(template)
            .replace('%1$s', first)
            .replace('%2$s', second)
            .replace('%s', first);
    }

    function fieldByKey(field) {
        if (field === 'behoerde') return behoerde;
        if (field === 'email') return email;
        return null;
    }

    function setFieldInvalid(field, isInvalid) {
        const el = fieldByKey(field);
        if (!el) return;
        if (isInvalid) {
            el.classList.add('themisdb-input-error');
            el.setAttribute('aria-invalid', 'true');
            return;
        }
        el.classList.remove('themisdb-input-error');
        el.removeAttribute('aria-invalid');
    }

    function markFieldError(field) {
        setFieldInvalid(field, true);
    }

    function clearFieldErrors() {
        setFieldInvalid('behoerde', false);
        setFieldInvalid('email', false);
    }

    function debounce(fn, delay) {
        let timeoutId;
        return function () {
            const args = arguments;
            const context = this;
            clearTimeout(timeoutId);
            timeoutId = setTimeout(function () {
                fn.apply(context, args);
            }, delay);
        };
    }

    function clearRateLimitTimer() {
        if (!rateLimitTimer) return;
        clearInterval(rateLimitTimer);
        rateLimitTimer = null;
    }

    function startRateLimitCountdown(seconds) {
        clearRateLimitTimer();

        let remaining = Math.max(1, Math.ceil(seconds));
        showError(fmt(t('contactWaitSeconds', 'Bitte warten Sie %d Sekunden, bevor Sie eine weitere Anfrage senden.'), remaining));

        rateLimitTimer = setInterval(function () {
            remaining -= 1;
            if (remaining <= 0) {
                clearRateLimitTimer();
                hideErrorIfFieldsAreValid();
                return;
            }
            showError(fmt(t('contactWaitSeconds', 'Bitte warten Sie %d Sekunden, bevor Sie eine weitere Anfrage senden.'), remaining));
        }, 1000);
    }

    function showError(msg) {
        if (!errEl) return;
        errEl.textContent = msg;
        errEl.classList.add('is-visible');
        if (sucEl) sucEl.classList.remove('is-visible');
    }

    function showSuccess(msg) {
        if (!sucEl) return;
        clearRateLimitTimer();
        sucEl.textContent = msg;
        sucEl.classList.add('is-visible');
        if (errEl) errEl.classList.remove('is-visible');
    }

    function clearMessages() {
        clearRateLimitTimer();
        if (errEl) errEl.classList.remove('is-visible');
        if (sucEl) sucEl.classList.remove('is-visible');
        clearFieldErrors();
    }

    function hideErrorIfFieldsAreValid() {
        if (!errEl?.classList.contains('is-visible')) return;
        if (behoerde?.classList.contains('themisdb-input-error')) return;
        if (email?.classList.contains('themisdb-input-error')) return;
        errEl.classList.remove('is-visible');
    }

    function updateBehoerdeCounter() {
        if (!behoerdeCount || !behoerde) return;
        const length = (behoerde.value || '').trim().length;
        behoerdeCount.textContent = length + ' / ' + BEHOERDE_MAX;
        if (length >= BEHOERDE_MAX - 10) {
            behoerdeCount.classList.add('is-near-limit');
            return;
        }
        behoerdeCount.classList.remove('is-near-limit');
    }

    function validateBehoerde(showMessage) {
        const value = behoerde?.value.trim() ?? '';
        const isValid = value.length > 0;
        setFieldInvalid('behoerde', !isValid);
        if (!isValid && showMessage) {
            showError(t('contactOrgRequired', 'Bitte geben Sie Ihre Behoerde / Institution an.'));
        }
        hideErrorIfFieldsAreValid();
        return isValid;
    }

    function validateEmail(showMessage) {
        const value = email?.value.trim() ?? '';
        const isValid = !!value && isValidEmail(value);
        setFieldInvalid('email', !isValid);
        if (!isValid && showMessage) {
            showError(t('contactEmailInvalid', 'Bitte geben Sie eine gueltige dienstliche E-Mail-Adresse an.'));
        }
        hideErrorIfFieldsAreValid();
        return isValid;
    }

    function setSubmitting(isSubmitting) {
        if (!submitBtn) return;
        submitBtn.disabled = isSubmitting;
        if (isSubmitting) {
            submitBtn.setAttribute('aria-busy', 'true');
            submitBtn.textContent = t('contactSubmitBusy', 'Wird gesendet...');
            return;
        }
        submitBtn.removeAttribute('aria-busy');
        submitBtn.textContent = submitLabelDefault;
    }

    function showServerError(data, status) {
        const code = data?.code || '';

        if (code === 'invalid_input' && Array.isArray(data?.fields)) {
            data.fields.forEach(markFieldError);
            const firstInvalid = fieldByKey(data.fields[0]);
            firstInvalid?.focus();
        }

        if (code === 'rate_limited' && Number.isFinite(data?.retry_after) && data.retry_after > 0) {
            startRateLimitCountdown(data.retry_after);
            return;
        }

        if (status === 429) {
            showError(t('contactWaitGeneric', 'Bitte warten Sie kurz, bevor Sie eine weitere Anfrage senden.'));
            return;
        }

        showError(data?.message || fmt(t('contactSendError', 'Fehler beim Senden. Bitte versuchen Sie es erneut oder schreiben Sie direkt an %s.'), getContactEmail()));
    }

    function getContactEmail() {
        return window.themisdbThemeData?.contactEmail || 'admin@example.invalid';
    }

    function isValidEmail(val) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val);
    }

    updateBehoerdeCounter();

    const debouncedBehoerdeValidation = debounce(function () {
        validateBehoerde(false);
    }, LIVE_VALIDATE_DELAY_MS);

    const debouncedEmailValidation = debounce(function () {
        validateEmail(false);
    }, LIVE_VALIDATE_DELAY_MS);

    behoerde?.addEventListener('input', function () {
        updateBehoerdeCounter();
        debouncedBehoerdeValidation();
    });
    behoerde?.addEventListener('blur', function () {
        validateBehoerde(true);
    });

    email?.addEventListener('input', function () {
        debouncedEmailValidation();
    });
    email?.addEventListener('blur', function () {
        validateEmail(true);
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        clearMessages();

        const behVal   = behoerde?.value.trim() ?? '';
        const emailVal = email?.value.trim()    ?? '';

        /* Validierung */
        if (!validateBehoerde(true)) {
            behoerde?.focus();
            return;
        }
        if (!validateEmail(true)) {
            email?.focus();
            return;
        }

        /* Kein AJAX-Endpunkt: Mailto-Fallback öffnen (clientseitig) */
        if (!window.themisdbThemeData || !window.themisdbThemeData.ajaxUrl) {
            const contactEmail = getContactEmail();
            const subject = encodeURIComponent(fmt(t('contactMailtoSubject', 'ThemisDB Zugangsanfrage von %s'), behVal));
            const body    = encodeURIComponent(fmt2(t('contactMailtoBody', 'Behoerde: %1$s\r\nE-Mail: %2$s\r\n\r\nBitte nehmen Sie Kontakt auf.'), behVal, emailVal));
            window.location.href = 'mailto:' + encodeURIComponent(contactEmail) + '?subject=' + subject + '&body=' + body;
            showSuccess(t('contactEmailClientOpen', 'Ihr E-Mail-Client wurde geoeffnet. Bitte senden Sie die Nachricht ab.'));
            return;
        }

        /* AJAX-Request */
        setSubmitting(true);

        const data = new URLSearchParams({
            action:   'themisdb_contact',
            nonce:    window.themisdbThemeData.contactNonce || '',
            behoerde: behVal,
            email:    emailVal,
            website:  website?.value.trim() ?? '',
        });

        let responseStatus = 0;
        fetch(window.themisdbThemeData.ajaxUrl, {
            method:      'POST',
            credentials: 'same-origin',
            headers:     { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:        data.toString(),
        })
        .then(function (res) {
            responseStatus = res.status;
            return res.json();
        })
        .then(function (json) {
            if (json.success) {
                showSuccess(json.data?.message || t('contactSendSuccess', 'Ihre Anfrage wurde erfolgreich gesendet. Wir melden uns in Kuerze.'));
                form.reset();
            } else {
                showServerError(json.data || {}, responseStatus);
            }
        })
        .catch(function () {
            showError(fmt(t('contactNetworkError', 'Netzwerkfehler. Bitte schreiben Sie direkt an %s.'), getContactEmail()));
        })
        .finally(function () {
            setSubmitting(false);
        });
    });
})();
