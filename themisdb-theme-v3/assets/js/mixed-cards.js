(function () {
    function initMixedCards(root) {
        if (!root || root.dataset.tv3Bound === "1") {
            return;
        }

        root.dataset.tv3Bound = "1";

        var sectionKey = root.id || "tv3-mixed";
        var searchTimer = null;
        var isBusy = false;
        var loadMoreObserver = null;

        function getForm() { return root.querySelector("[data-tv3-search-form]"); }
        function getFilterInput() { return root.querySelector("[data-tv3-filter-input]"); }
        function getFeedback() { return root.querySelector("[data-tv3-feedback]"); }
        function getLoadMoreWrap() { return root.querySelector("[data-tv3-loadmore-wrap]"); }

        function setBusy(nextBusy) {
            isBusy = !!nextBusy;
            root.classList.toggle("is-loading", isBusy);
        }

        function showFeedback(message) {
            var feedback = getFeedback();
            if (!feedback) {
                return;
            }
            if (!message) {
                feedback.hidden = true;
                feedback.textContent = "";
                return;
            }
            feedback.hidden = false;
            feedback.textContent = message;
        }

        function setAutoLoadMode(isAuto) {
            var wrap = getLoadMoreWrap();
            if (!wrap) {
                return;
            }
            wrap.classList.toggle("is-auto", !!isAuto);
        }

        function disconnectLoadMoreObserver() {
            if (loadMoreObserver) {
                loadMoreObserver.disconnect();
                loadMoreObserver = null;
            }
        }

        function parseIntSafe(value, fallback) {
            var parsed = parseInt(value, 10);
            return Number.isFinite(parsed) && parsed > 0 ? parsed : fallback;
        }

        function readStateFromLocation() {
            var form = getForm();
            var params = new URLSearchParams(window.location.search || "");
            return {
                limit: parseIntSafe(params.get("tv3_limit"), parseIntSafe(form && form.elements.tv3_limit ? form.elements.tv3_limit.value : "12", 12)),
                search: params.get("tv3_search") || "",
                sort: params.get("tv3_sort") || "newest",
                filter: params.get("tv3_object_type") || "",
                page: parseIntSafe(params.get("tv3_page"), 1)
            };
        }

        function applyStateToForm(state) {
            var form = getForm();
            if (!form || !state) {
                return;
            }

            if (form.elements.tv3_limit && state.limit) {
                form.elements.tv3_limit.value = String(state.limit);
            }
            if (form.elements.tv3_search) {
                form.elements.tv3_search.value = state.search || "";
            }
            if (form.elements.tv3_sort) {
                form.elements.tv3_sort.value = state.sort || "newest";
            }
            if (form.elements.tv3_object_type) {
                form.elements.tv3_object_type.value = state.filter || "";
            }
            if (form.elements.tv3_page) {
                form.elements.tv3_page.value = "1";
            }
        }

        function buildBrowserState(page) {
            var form = getForm();
            var params = new URLSearchParams(new FormData(form));
            return {
                limit: parseIntSafe(params.get("tv3_limit"), 12),
                search: params.get("tv3_search") || "",
                sort: params.get("tv3_sort") || "newest",
                filter: params.get("tv3_object_type") || "",
                page: parseIntSafe(String(page || 1), 1),
                section: sectionKey
            };
        }

        function buildQueryFromState(state) {
            var query = new URLSearchParams();
            if (parseIntSafe(state.limit, 12) !== 12) {
                query.set("tv3_limit", String(parseIntSafe(state.limit, 12)));
            }
            if (state.search) {
                query.set("tv3_search", state.search);
            }
            if (state.sort && state.sort !== "newest") {
                query.set("tv3_sort", state.sort);
            }
            if (state.filter) {
                query.set("tv3_object_type", state.filter);
            }
            if (parseIntSafe(state.page, 1) > 1) {
                query.set("tv3_page", String(parseIntSafe(state.page, 1)));
            }
            return query.toString();
        }

        function syncBrowserHistory(page, mode) {
            if (!window.history || typeof window.history.replaceState !== "function") {
                return;
            }
            if (mode === "none") {
                return;
            }

            var state = buildBrowserState(page);
            var query = buildQueryFromState(state);
            var url = window.location.pathname + (query ? ("?" + query) : "");
            var payload = { tv3MixedCards: state };

            if (mode === "push" && typeof window.history.pushState === "function") {
                window.history.pushState(payload, "", url);
                return;
            }

            window.history.replaceState(payload, "", url);
        }

        function observeLoadMore() {
            disconnectLoadMoreObserver();

            if (!("IntersectionObserver" in window)) {
                setAutoLoadMode(false);
                return;
            }

            var loadMore = root.querySelector("[data-tv3-load-more]");
            if (!loadMore) {
                setAutoLoadMode(false);
                return;
            }

            setAutoLoadMode(true);

            loadMoreObserver = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting || isBusy) {
                        return;
                    }

                    var nextPage = parseIntSafe(entry.target.getAttribute("data-tv3-load-more"), 1);
                    loadMoreObserver.unobserve(entry.target);
                    request(nextPage, true, "replace");
                });
            }, {
                rootMargin: "280px 0px 280px 0px",
                threshold: 0.01
            });

            loadMoreObserver.observe(loadMore);
        }

        function buildParams(page, append) {
            var form = getForm();
            var params = new URLSearchParams(new FormData(form));
            params.set("action", "themisdb_v3_mixed_cards");
            params.set("nonce", root.dataset.ajaxNonce || "");
            params.set("tv3_page", String(page || 1));
            params.set("tv3_append", append ? "1" : "0");
            return params;
        }

        function applyPayload(payload, append) {
            if (!payload || !payload.success || !payload.data) {
                showFeedback("Aktualisierung fehlgeschlagen.");
                return;
            }

            root.querySelector("[data-tv3-controls-region]").innerHTML = payload.data.controlsHtml || "";
            if (payload.data.contextView) {
                root.setAttribute("data-tv3-context", payload.data.contextView);
            }
            if (append) {
                var grid = root.querySelector("[data-tv3-grid-region] .tv3-post-grid");
                var tmp = document.createElement("div");
                tmp.innerHTML = payload.data.gridHtml || "";
                var nextGrid = tmp.querySelector(".tv3-post-grid");
                if (grid && nextGrid) {
                    while (nextGrid.firstChild) {
                        grid.appendChild(nextGrid.firstChild);
                    }
                } else {
                    root.querySelector("[data-tv3-grid-region]").innerHTML = payload.data.gridHtml || "";
                }
            } else {
                root.querySelector("[data-tv3-grid-region]").innerHTML = payload.data.gridHtml || "";
            }

            root.querySelector("[data-tv3-pager-region]").innerHTML = payload.data.pagerHtml || "";
            showFeedback(payload.data.message || "");
        }

        function request(page, append, historyMode) {
            if (isBusy) {
                return;
            }

            disconnectLoadMoreObserver();
            setBusy(true);
            showFeedback("");

            var params = buildParams(page, append);
            fetch(root.dataset.ajaxUrl, {
                method: "POST",
                credentials: "same-origin",
                headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
                body: params.toString()
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error("http_" + response.status);
                    }
                    return response.json();
                })
                .then(function (payload) {
                    applyPayload(payload, append);
                    syncBrowserHistory(page, historyMode || "push");
                })
                .catch(function () { showFeedback("Aktualisierung fehlgeschlagen."); })
                .finally(function () {
                    setBusy(false);
                    observeLoadMore();
                });
        }

        if (window.history && typeof window.history.replaceState === "function") {
            // Only restore URL state if tv3_ params are already present (e.g. back navigation).
            // Avoids writing default state (tv3_limit=12) to a clean URL on initial load.
            if (window.location.search.indexOf("tv3_") !== -1) {
                syncBrowserHistory(readStateFromLocation().page || 1, "replace");
            }
        }

        window.addEventListener("popstate", function (event) {
            var state = event.state && event.state.tv3MixedCards ? event.state.tv3MixedCards : null;
            if (!state || state.section !== sectionKey) {
                return;
            }
            applyStateToForm(state);
            request(parseIntSafe(state.page, 1), false, "none");
        });

        observeLoadMore();

        root.addEventListener("submit", function (event) {
            if (!event.target.matches("[data-tv3-search-form]")) {
                return;
            }
            event.preventDefault();
            request(1, false, "push");
        });

        root.addEventListener("change", function (event) {
            if (event.target.matches("[data-tv3-sort-select]")) {
                request(1, false, "push");
            }
        });

        root.addEventListener("input", function (event) {
            if (!event.target.matches("[data-tv3-search-input]")) {
                return;
            }

            if (searchTimer) {
                clearTimeout(searchTimer);
            }

            searchTimer = setTimeout(function () {
                request(1, false, "replace");
            }, 220);
        });

        root.addEventListener("click", function (event) {
            var filter = event.target.closest("[data-tv3-filter]");
            if (filter) {
                event.preventDefault();
                var input = getFilterInput();
                if (input) {
                    input.value = filter.getAttribute("data-tv3-filter") === "all" ? "" : filter.getAttribute("data-tv3-filter");
                }
                request(1, false, "push");
                return;
            }

            var loadMore = event.target.closest("[data-tv3-load-more]");
            if (loadMore) {
                event.preventDefault();
                request(parseInt(loadMore.getAttribute("data-tv3-load-more") || "1", 10), true, "push");
            }
        });
    }

    function initAll() {
        var roots = document.querySelectorAll("[data-tv3-mixed-cards]");
        if (!roots.length) {
            return;
        }
        roots.forEach(initMixedCards);
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initAll);
    } else {
        initAll();
    }
})();
