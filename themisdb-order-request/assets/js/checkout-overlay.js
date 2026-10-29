(function () {
    'use strict';
    document.addEventListener('DOMContentLoaded', function () {
        var dialog = document.getElementById('themisdb-checkout-dialog');
        if (!dialog) return;
        var frame = dialog.querySelector('iframe');
        var trigger;
        document.addEventListener('click', function (event) {
            var button = event.target.closest('[data-themisdb-open-cart], .tpd-order-btn');
            if (!button) return;
            event.preventDefault();
            trigger = button;
            var url = new URL(window.themisdbCheckout.url);
            if (button.matches('.tpd-order-btn')) {
                var selection = new URL(button.href, window.location.href);
                ['edition', 'modules'].forEach(function (key) {
                    if (selection.searchParams.has(key)) url.searchParams.set(key, selection.searchParams.get(key));
                });
            }
            frame.src = url.href;
            dialog.showModal();
            document.body.classList.add('themisdb-cart-is-open');
            dialog.querySelector('[data-themisdb-close-cart]').focus();
        });
        function close() { dialog.close(); }
        dialog.querySelector('[data-themisdb-close-cart]').addEventListener('click', close);
        dialog.addEventListener('click', function (event) {
            if (event.target !== dialog) return;
            var bounds = dialog.getBoundingClientRect();
            if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) close();
        });
        dialog.addEventListener('close', function () {
            document.body.classList.remove('themisdb-cart-is-open');
            if (trigger && trigger.isConnected) trigger.focus();
        });
        window.addEventListener('message', function (event) {
            if (event.origin === window.location.origin && event.source === frame.contentWindow &&
                event.data && event.data.type === 'themisdb-close-cart') close();
        });
    });
})();
