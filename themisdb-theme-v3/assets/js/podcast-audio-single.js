(function() {
    'use strict';

    const cardSelector = '.tv3-card-audio';
    const managedAudioSelector = cardSelector + ' audio';

    function getCard(audioElement) {
        return audioElement.closest(cardSelector);
    }

    function syncCardState(card) {
        if (!card) {
            return;
        }

        const label = card.querySelector('.tv3-card-audio-label');
        if (label && !label.dataset.defaultText) {
            label.dataset.defaultText = label.textContent ? label.textContent.trim() : 'Episode Audio';
        }

        const audios = card.querySelectorAll('audio');
        let isPlaying = false;

        audios.forEach(function(audio) {
            if (!audio.paused && !audio.ended) {
                isPlaying = true;
            }
        });

        card.classList.toggle('tv3-card-audio-playing', isPlaying);

        if (label) {
            label.textContent = isPlaying ? 'Jetzt laeuft' : (label.dataset.defaultText || 'Episode Audio');
        }
    }

    function ensureCardVisible(card) {
        if (!card) {
            return;
        }

        const rect = card.getBoundingClientRect();
        const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
        const isVisible = rect.top >= 80 && rect.bottom <= (viewportHeight - 80);

        if (!isVisible) {
            card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    function syncAllCardStates() {
        document.querySelectorAll(cardSelector).forEach(function(card) {
            syncCardState(card);
        });
    }

    document.addEventListener('play', function(event) {
        const target = event.target;

        if (!(target instanceof HTMLAudioElement)) {
            return;
        }

        document.querySelectorAll('audio').forEach(function(audio) {
            if (audio !== target && !audio.paused) {
                audio.pause();
            }
        });

        ensureCardVisible(getCard(target));
        syncAllCardStates();
    }, true);

    document.addEventListener('pause', function(event) {
        if (event.target instanceof HTMLAudioElement) {
            syncAllCardStates();
        }
    }, true);

    document.addEventListener('ended', function(event) {
        if (event.target instanceof HTMLAudioElement) {
            syncAllCardStates();
        }
    }, true);

    document.addEventListener('DOMContentLoaded', function() {
        if (!document.querySelector(managedAudioSelector)) {
            return;
        }

        syncAllCardStates();
    });
})();
