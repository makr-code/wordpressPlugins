(function () {
    "use strict";

    function getCodeBlocks() {
        return Array.prototype.slice.call(
            document.querySelectorAll(
                ".tv3-single-main pre code, .tv3-page-main pre code, .tv3-single-main .wp-block-code code, .tv3-page-main .wp-block-code code"
            )
        );
    }

    function applyHighlight(blocks) {
        if (!window.hljs || typeof window.hljs.highlightElement !== "function") {
            return;
        }

        blocks.forEach(function (block) {
            if (!block || block.dataset.tv3HlDone === "1") {
                return;
            }
            window.hljs.highlightElement(block);
            block.dataset.tv3HlDone = "1";
        });
    }

    function loadHighlightJs(onReady) {
        if (window.hljs && typeof window.hljs.highlightElement === "function") {
            onReady();
            return;
        }

        if (document.querySelector('script[data-tv3-highlightjs="1"]')) {
            var waitTimer = window.setInterval(function () {
                if (window.hljs && typeof window.hljs.highlightElement === "function") {
                    window.clearInterval(waitTimer);
                    onReady();
                }
            }, 120);
            window.setTimeout(function () {
                window.clearInterval(waitTimer);
            }, 6000);
            return;
        }

        var currentScript = document.querySelector('script[src*="/assets/js/code-highlight.js"]');
        var localSrc = "";
        if (currentScript && currentScript.src) {
            localSrc = currentScript.src.replace("/assets/js/code-highlight.js", "/assets/vendor/highlightjs/highlight.min.js");
        }

        var script = document.createElement("script");
        script.src = localSrc || "https://cdn.jsdelivr.net/npm/highlight.js@11.9.0/lib/common.min.js";
        script.async = true;
        script.defer = true;
        script.dataset.tv3Highlightjs = "1";
        script.onload = onReady;
        script.onerror = function () {
            // Local load failed: try CDN once, then gracefully fallback.
            if (script.dataset.tv3CdnTried === "1") {
                return;
            }

            script.dataset.tv3CdnTried = "1";
            var cdnScript = document.createElement("script");
            cdnScript.src = "https://cdn.jsdelivr.net/npm/highlight.js@11.9.0/lib/common.min.js";
            cdnScript.async = true;
            cdnScript.defer = true;
            cdnScript.dataset.tv3Highlightjs = "1";
            cdnScript.onload = onReady;
            cdnScript.onerror = function () {
                // Graceful fallback: unhighlighted code is still readable.
            };
            document.head.appendChild(cdnScript);
        };
        document.head.appendChild(script);
    }

    function init() {
        var blocks = getCodeBlocks();
        if (!blocks.length) {
            return;
        }

        loadHighlightJs(function () {
            applyHighlight(blocks);
        });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init, { once: true });
    } else {
        init();
    }
})();
