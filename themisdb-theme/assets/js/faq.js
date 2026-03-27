/**
 * ThemisDB Theme – faq.js
 * Accessible accordion behavior for multiple FAQ block instances.
 */
(function () {
    'use strict';

    function setAnswerHeight(answer, expanded) {
        if (!answer) {
            return;
        }

        if (expanded) {
            answer.style.maxHeight = answer.scrollHeight + 'px';
        } else {
            answer.style.maxHeight = '0px';
        }
    }

    function collapseItem(item) {
        var toggle = item ? item.querySelector('[data-faq-toggle]') : null;
        var answer = item ? item.querySelector('[data-faq-answer]') : null;

        if (!toggle || !answer) {
            return;
        }

        item.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
        answer.setAttribute('aria-hidden', 'true');
        answer.classList.remove('is-open');
        setAnswerHeight(answer, false);
    }

    function expandItem(item) {
        var toggle = item ? item.querySelector('[data-faq-toggle]') : null;
        var answer = item ? item.querySelector('[data-faq-answer]') : null;

        if (!toggle || !answer) {
            return;
        }

        item.classList.add('is-open');
        toggle.setAttribute('aria-expanded', 'true');
        answer.setAttribute('aria-hidden', 'false');
        answer.classList.add('is-open');
        setAnswerHeight(answer, true);
    }

    function toggleItem(container, item) {
        var isOpen = item && item.classList.contains('is-open');

        container.querySelectorAll('.themisdb-faq-item.is-open').forEach(function (openItem) {
            collapseItem(openItem);
        });

        if (!isOpen && item) {
            expandItem(item);
        }
    }

    function focusSibling(toggles, currentToggle, direction) {
        var index = toggles.indexOf(currentToggle);
        if (-1 === index) {
            return;
        }

        var nextIndex = (index + direction + toggles.length) % toggles.length;
        toggles[nextIndex].focus();
    }

    function initContainer(container) {
        var toggles = Array.prototype.slice.call(container.querySelectorAll('[data-faq-toggle]'));

        container.querySelectorAll('[data-faq-answer]').forEach(function (answer) {
            answer.style.maxHeight = '0px';
        });

        container.addEventListener('click', function (event) {
            var toggle = event.target.closest('[data-faq-toggle]');
            var item = toggle ? toggle.closest('.themisdb-faq-item') : null;

            if (!toggle || !item) {
                return;
            }

            toggleItem(container, item);
        });

        container.addEventListener('keydown', function (event) {
            var toggle = event.target.closest('[data-faq-toggle]');
            var item = toggle ? toggle.closest('.themisdb-faq-item') : null;

            if (!toggle) {
                return;
            }

            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                toggleItem(container, item);
                return;
            }

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                focusSibling(toggles, toggle, 1);
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                focusSibling(toggles, toggle, -1);
            } else if (event.key === 'Home') {
                event.preventDefault();
                toggles[0].focus();
            } else if (event.key === 'End') {
                event.preventDefault();
                toggles[toggles.length - 1].focus();
            }
        });

        window.addEventListener('resize', function () {
            container.querySelectorAll('.themisdb-faq-item.is-open [data-faq-answer]').forEach(function (answer) {
                setAnswerHeight(answer, true);
            });
        });
    }

    document.querySelectorAll('[data-faq-container]').forEach(initContainer);
}());
