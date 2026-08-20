/**
 * ThemisDB Theme – mermaid-init.js
 * Initialisiert Mermaid.js für alle <pre class="mermaid"> Blöcke
 */
(function () {
    'use strict';

    if (typeof mermaid === 'undefined') {
        /* Mermaid nicht geladen – Test-Fallback einblenden */
        document.querySelectorAll('.themisdb-mermaid-wrap').forEach(function (wrap) {
            const pre = wrap.querySelector('pre.mermaid');
            if (!pre) return;
            const fallback = document.createElement('div');
            fallback.className = 'themisdb-test-placeholder';
            fallback.innerHTML =
                '<strong>TEST-PLACEHOLDER – Mermaid.js:</strong> ' +
                'Das CDN-Skript (mermaid.min.js) konnte nicht geladen werden. ' +
                'Bitte Netzwerkverbindung prüfen oder das CDN in functions.php auf lokal umstellen.';
            wrap.insertBefore(fallback, pre);
        });
        return;
    }

    mermaid.initialize({
        startOnLoad: true,
        theme:       'base',
        themeVariables: {
            primaryColor:       '#0c4a6e',
            primaryTextColor:   '#ffffff',
            primaryBorderColor: '#0c4a6e',
            lineColor:          '#94a3b8',
            secondaryColor:     '#f0f9ff',
            tertiaryColor:      '#ecfdf5',
            fontFamily:         '"Plus Jakarta Sans", system-ui, sans-serif',
            fontSize:           '14px',
        },
        flowchart: {
            htmlLabels:  true,
            curve:       'basis',
            rankSpacing: 50,
            nodeSpacing: 30,
        },
        securityLevel: 'strict',
    });
})();
