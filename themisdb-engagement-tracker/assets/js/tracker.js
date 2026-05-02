/* ThemisDB Engagement Tracker – frontend event tracking
 *
 * Events fired:
 *   view            – on page load (once per session via sessionStorage guard)
 *   read            – when scroll-depth reaches 60 %
 *   podcast_play    – when any <audio> element starts playing
 *   podcast_complete– when any <audio> element passes 80 % of duration
 */
(function () {
    'use strict';

    if (typeof tdetConfig === 'undefined') return;

    var cfg    = tdetConfig;
    var postId = parseInt(cfg.postId, 10);
    var ssKey  = 'tdet_sent_' + postId;

    // Restore already-sent events from sessionStorage to avoid double-counting
    // on browser back/forward navigation within the same tab session.
    var sent = {};
    try {
        sent = JSON.parse(sessionStorage.getItem(ssKey) || '{}');
    } catch (e) { /* ignore */ }

    function persist() {
        try { sessionStorage.setItem(ssKey, JSON.stringify(sent)); } catch (e) { /* ignore */ }
    }

    function track(event) {
        if (sent[event]) return;
        sent[event] = true;
        persist();

        fetch(cfg.restUrl, {
            method:    'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce':   cfg.nonce,
            },
            body:      JSON.stringify({ post_id: postId, event: event }),
            keepalive: true,
        }).catch(function () { /* silent – tracking is best-effort */ });
    }

    // ── View (fired once per session per post) ─────────────────────────────
    track('view');

    // ── Read completion at 60 % scroll depth ──────────────────────────────
    var readFired = false;
    function checkScroll() {
        if (readFired) return;
        var scrolled = window.scrollY + window.innerHeight;
        var total    = document.documentElement.scrollHeight;
        if (total > 0 && scrolled / total >= 0.60) {
            readFired = true;
            track('read');
            window.removeEventListener('scroll', checkScroll, { passive: true });
        }
    }
    window.addEventListener('scroll', checkScroll, { passive: true });

    // ── Podcast tracking ───────────────────────────────────────────────────
    if (cfg.hasPodcast) {
        function bindAudio(audio) {
            // Guard against re-binding the same element.
            if (audio.__tdetBound) return;
            audio.__tdetBound = true;

            var playFired     = false;
            var completeFired = false;

            audio.addEventListener('play', function () {
                if (playFired) return;
                playFired = true;
                track('podcast_play');
            });

            audio.addEventListener('timeupdate', function () {
                if (completeFired || audio.duration <= 0) return;
                if (audio.currentTime / audio.duration >= 0.80) {
                    completeFired = true;
                    track('podcast_complete');
                }
            });
        }

        // Bind existing audio elements.
        document.querySelectorAll('audio').forEach(bindAudio);

        // Bind audio injected later (e.g. persistent podcast player).
        var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (m) {
                m.addedNodes.forEach(function (node) {
                    if (node.nodeType !== 1) return;
                    if (node.tagName === 'AUDIO') {
                        bindAudio(node);
                    } else if (node.querySelectorAll) {
                        node.querySelectorAll('audio').forEach(bindAudio);
                    }
                });
            });
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }
})();
