(function () {
    'use strict';

    var POSITION_CLASSES = ['left', 'center', 'right'];

    /**
     * Returns true if the blockquote text starts with a Kernthese marker
     * (e.g. "Kernthese:", "Kernthese –", "Kernthese.").
     */
    function isKerntheseQuote(el) {
        var text = (el.textContent || '').trim().toLowerCase();
        return text.indexOf('kernthese') === 0;
    }

    /**
     * Picks a float position (left / center / right) based on a simple
     * alternating counter so successive Kernthesen don't all land on one side.
     */
    function pickPosition(counter) {
        var sequence = ['left', 'right', 'center'];
        return sequence[counter % sequence.length];
    }

    /**
     * Mark the first strong inside the blockquote as the label element so the
     * CSS can style it independently ("Kernthese:" in small-caps / accent colour).
     */
    function tagLabel(blockquote) {
        if (blockquote.querySelector('.tv3-kernthese-label')) {
            return;
        }

        var firstStrong = blockquote.querySelector('p strong:first-child, strong:first-child');
        if (firstStrong) {
            firstStrong.classList.add('tv3-kernthese-label');
            return;
        }

        // Fallback: wrap the leading "Kernthese…:" text node in a <span>.
        var firstP = blockquote.querySelector('p') || blockquote;
        var walker = document.createTreeWalker(firstP, NodeFilter.SHOW_TEXT, null, false);
        var textNode = walker.nextNode();
        if (!textNode) {
            return;
        }

        var full = textNode.textContent;
        var colonPos = full.indexOf(':');
        if (colonPos < 0) {
            return;
        }

        var labelText = full.slice(0, colonPos + 1);
        var rest      = full.slice(colonPos + 1);

        var labelSpan = document.createElement('span');
        labelSpan.className   = 'tv3-kernthese-label';
        labelSpan.textContent = labelText;

        var parent = textNode.parentNode;
        parent.insertBefore(labelSpan, textNode);
        parent.insertBefore(document.createTextNode(rest), textNode);
        parent.removeChild(textNode);
    }

    function enhanceRoot(root, counter) {
        if (!root) {
            return counter;
        }

        var blockquotes = root.querySelectorAll('blockquote');
        for (var i = 0; i < blockquotes.length; i += 1) {
            var bq = blockquotes[i];

            // Skip already processed ones.
            if (bq.classList.contains('tv3-kernthese-inline')) {
                continue;
            }

            if (!isKerntheseQuote(bq)) {
                continue;
            }

            var pos = pickPosition(counter);
            counter += 1;

            // Remove any position classes that might exist, then apply the
            // current one so re-runs stay idempotent.
            for (var p = 0; p < POSITION_CLASSES.length; p += 1) {
                bq.classList.remove(POSITION_CLASSES[p]);
            }

            bq.classList.add('tv3-kernthese-inline', pos);
            tagLabel(bq);
        }

        return counter;
    }

    function boot() {
        var counter = 0;

        // Formula-content wrapper (generated posts).
        var formulaRoots = document.querySelectorAll('.wp-block-post-content .themisdb-formula-content');
        for (var i = 0; i < formulaRoots.length; i += 1) {
            counter = enhanceRoot(formulaRoots[i], counter);
        }

        // Direct rich-content wrapper (page.html / single.html without formula).
        var richRoots = document.querySelectorAll('.tv3-rich-post-content');
        for (var j = 0; j < richRoots.length; j += 1) {
            counter = enhanceRoot(richRoots[j], counter);
        }

        // Fallback: the bare post-content block when neither wrapper is present.
        var postContentRoots = document.querySelectorAll('.wp-block-post-content');
        for (var k = 0; k < postContentRoots.length; k += 1) {
            if (
                postContentRoots[k].querySelector('.themisdb-formula-content') ||
                postContentRoots[k].querySelector('.tv3-rich-post-content')
            ) {
                continue;
            }
            counter = enhanceRoot(postContentRoots[k], counter);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
