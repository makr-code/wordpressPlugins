( function () {
	'use strict';

	var initializeLiveSearch = function () {
		var config = window.themisdbLiveSearch || {};
		var restUrl = config.restUrl || '/wp-json/wp/v2/search';
		var searchWraps = document.querySelectorAll(
			'.tv3-header-search, .tv3-docs-sidebar-search, .tv3-search-form, .wp-block-search'
		);

		var styleEl = document.querySelector( '#tv3-live-search-overlay-styles' );
		if ( ! styleEl ) {
			styleEl = document.createElement( 'style' );
			styleEl.id = 'tv3-live-search-overlay-styles';
			styleEl.textContent = '.tv3-live-search-summary, .tv3-live-search-group-pill, .tv3-live-search-group-label, .tv3-live-search-group-label span, .tv3-live-search-meta { font-size: 0.58rem !important; line-height: 1.2 !important; white-space: nowrap !important; } .tv3-live-search-summary { display: block !important; font-size: 0.68rem !important; font-weight: 800 !important; letter-spacing: 0.08em !important; text-transform: uppercase !important; color: #1e6fba !important; } .tv3-live-search-group-pill { display: inline-flex !important; align-items: center !important; gap: 0.25rem !important; padding: 0.18rem 0.5rem !important; border-radius: 999px !important; background: rgba(15, 23, 42, 0.05) !important; color: #10233d !important; font-weight: 700 !important; } .tv3-live-search-group-label { display: flex !important; align-items: center !important; justify-content: space-between !important; gap: 0.5rem !important; padding: 0.45rem 0.55rem 0.2rem !important; font-weight: 800 !important; letter-spacing: 0.08em !important; text-transform: uppercase !important; color: #64748b !important; } .tv3-live-search-group-label span { display: inline-flex !important; min-width: 1.5rem !important; justify-content: center !important; padding: 0.12rem 0.3rem !important; border-radius: 999px !important; background: rgba(30, 111, 186, 0.08) !important; color: #1e6fba !important; }';
			document.head.appendChild( styleEl );
		}

		searchWraps.forEach( function ( wrap ) {
			if ( wrap.querySelector( '.tv3-live-search-overlay' ) ) {
				return;
			}

			var input = wrap.querySelector( 'input[type="search"], .wp-block-search__input, input[name="s"]' );
			if ( ! input ) {
				return;
			}

			var overlay = document.createElement( 'div' );
			overlay.className = 'tv3-live-search-overlay';
			overlay.style.position = 'fixed';
			overlay.style.zIndex = '20000';
			overlay.style.pointerEvents = 'auto';
			overlay.style.minWidth = '320px';
			overlay.style.maxWidth = 'min(480px, calc(100vw - 24px))';
			overlay.style.background = 'rgba(255,255,255,0.98)';
			overlay.style.border = '1px solid rgba(15, 23, 42, 0.10)';
			overlay.style.borderRadius = '16px';
			overlay.style.boxShadow = '0 24px 60px rgba(15, 23, 42, 0.18)';
			overlay.style.maxHeight = '420px';
			overlay.style.overflowY = 'auto';
			overlay.style.backdropFilter = 'blur(10px)';
			overlay.style.WebkitBackdropFilter = 'blur(10px)';
			overlay.hidden = true;
			document.body.appendChild( overlay );

			var positionOverlay = function () {
				var rect = input.getBoundingClientRect();
				var width = Math.max( 260, Math.min( rect.width + 32, window.innerWidth - 24 ) );
				var left = Math.min( Math.max( 12, rect.left ), window.innerWidth - width - 12 );
				overlay.style.position = 'fixed';
				overlay.style.zIndex = '20000';
				overlay.style.width = width + 'px';
				overlay.style.left = left + 'px';
				overlay.style.top = ( window.scrollY + rect.bottom + 10 ) + 'px';
			};

			var closeOverlay = function () {
				overlay.hidden = true;
				overlay.innerHTML = '';
				input.setAttribute( 'aria-expanded', 'false' );
			};

			var normalizeTypeLabel = function ( type ) {
				switch ( type ) {
					case 'page':
						return 'Seiten';
					case 'post':
						return 'Beiträge';
					case 'attachment':
						return 'Medien';
					case 'product':
						return 'Produkte';
					default:
						return 'Ergebnisse';
				}
			};

			var renderResults = function ( results ) {
				var resultItems = Array.isArray( results ) ? results : [];
				var grouped = {};
				var totalCount = resultItems.length;

				resultItems.forEach( function ( item ) {
					var rawType = item && ( item.subtype || item.type || 'page' );
					var label = normalizeTypeLabel( rawType );
					if ( ! grouped[ label ] ) {
						grouped[ label ] = [];
					}
					grouped[ label ].push( item );
				} );

				var groupLabels = Object.keys( grouped );
				var summary = '<div class="tv3-live-search-header" style="display:flex;flex-direction:column;gap:0.45rem;padding:0.7rem 0.8rem 0.55rem;border-bottom:1px solid rgba(15,23,42,0.06);background:linear-gradient(180deg, rgba(30,111,186,0.05) 0%, rgba(255,255,255,1) 100%);"><div class="tv3-live-search-summary" style="display:block;font-size:0.68rem;font-weight:800;letter-spacing:0.08em;text-transform:uppercase;line-height:1.2;white-space:nowrap;color:#1e6fba;">' + totalCount + ' Treffer</div><div class="tv3-live-search-groups" style="display:flex;flex-wrap:wrap;align-items:center;gap:0.35rem;min-width:0;">' + groupLabels.map( function ( groupLabel ) {
					return '<span class="tv3-live-search-group-pill" style="display:inline-flex;align-items:center;gap:0.25rem;padding:0.18rem 0.5rem;border-radius:999px;background:rgba(15,23,42,0.05);color:#10233d;font-size:0.56rem;line-height:1.2;font-weight:700;white-space:nowrap;">' + groupLabel + ' <strong style="color:#1e6fba;">' + grouped[ groupLabel ].length + '</strong></span>';
				} ).join( '' ) + '</div></div>';

				if ( totalCount === 0 ) {
					overlay.innerHTML = summary + '<div class="tv3-live-search-empty">Keine Treffer</div>';
					overlay.hidden = false;
					input.setAttribute( 'aria-expanded', 'true' );
					return;
				}

				var body = groupLabels.map( function ( groupLabel ) {
					var items = grouped[ groupLabel ].map( function ( item ) {
						var title = item.title && item.title.rendered ? item.title.rendered : ( item.title || 'Untitled' );
						var url = item.url || '#';
						var teaser = '';

						if ( item.excerpt ) {
							teaser = typeof item.excerpt === 'string' ? item.excerpt : item.excerpt.rendered || '';
						} else if ( item.description ) {
							teaser = typeof item.description === 'string' ? item.description : item.description.rendered || '';
						}

						teaser = teaser.replace(/<[^>]*>/g, '').replace(/\s+/g, ' ').trim();
						if ( teaser.length > 120 ) {
							teaser = teaser.slice( 0, 117 ) + '…';
						}

						if ( ! teaser ) {
							var queryTerm = ( document.querySelector( '.tv3-header-search input[type="search"], .tv3-header-search .wp-block-search__input' ) || input ).value.trim();
							teaser = 'Treffer in ' + groupLabel + ' für „' + ( queryTerm || 'Suche' ) + '“';
						}

						return '<a class="tv3-live-search-item" href="' + url + '" style="display:flex !important;flex-direction:row;align-items:flex-start;justify-content:space-between;gap:0.7rem;width:100%;padding:0.72rem 0.75rem;margin:0.2rem 0 0;border:1px solid rgba(15,23,42,0.06);border-radius:10px;background:rgba(255,255,255,0.94);box-shadow:0 6px 16px rgba(15,23,42,0.04);color:#10233d;text-decoration:none;font-size:0.72rem;line-height:1.35;box-sizing:border-box;white-space:normal;overflow:hidden;"><span class="tv3-live-search-item-main" style="display:flex;flex:1 1 auto;min-width:0;flex-direction:column;gap:0.18rem;"><span class="tv3-live-search-title" style="display:block;min-width:0;color:#10233d;font-size:0.82rem;font-weight:800;line-height:1.35;letter-spacing:-0.01em;">' + title + '</span><span class="tv3-live-search-teaser" style="display:block;color:#46566c;font-size:0.68rem;line-height:1.45;overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">' + teaser + '</span></span><span class="tv3-live-search-meta" style="flex:0 0 auto;align-self:flex-start;color:#64748b;font-size:0.58rem;line-height:1.2;letter-spacing:0.08em;text-transform:uppercase;white-space:nowrap;text-align:right;margin-left:auto;padding-top:0.12rem;">' + groupLabel + '</span></a>';
					} ).join( '' );

					return '<div class="tv3-live-search-group" style="display:block;padding:0.2rem 0.25rem 0.4rem;border-bottom:1px solid rgba(15,23,42,0.05);background:rgba(248,250,252,0.55);"><div class="tv3-live-search-group-label" style="display:flex;align-items:center;justify-content:space-between;gap:0.5rem;padding:0.45rem 0.55rem 0.2rem;font-size:0.58rem;line-height:1.2;font-weight:800;letter-spacing:0.08em;text-transform:uppercase;color:#64748b;white-space:nowrap;">' + groupLabel + ' <span style="display:inline-flex;min-width:1.5rem;justify-content:center;padding:0.12rem 0.3rem;border-radius:999px;background:rgba(30,111,186,0.08);color:#1e6fba;font-size:0.56rem;line-height:1.2;white-space:nowrap;">' + grouped[ groupLabel ].length + '</span></div>' + items + '</div>';
				} ).join( '' );

				overlay.innerHTML = summary + body;
				overlay.style.background = 'rgba(255,255,255,0.98)';
				overlay.style.border = '1px solid rgba(15, 23, 42, 0.10)';
				overlay.style.borderRadius = '16px';
				overlay.style.boxShadow = '0 24px 60px rgba(15, 23, 42, 0.18)';
				overlay.hidden = false;
				input.setAttribute( 'aria-expanded', 'true' );
			};

			var debounceTimer = null;
			input.addEventListener( 'input', function () {
				var term = input.value.trim();
				if ( debounceTimer ) {
					window.clearTimeout( debounceTimer );
				}

				if ( term.length < 2 ) {
					closeOverlay();
					return;
				}

				positionOverlay();

				debounceTimer = window.setTimeout( function () {
					var requestUrl = restUrl + '?search=' + encodeURIComponent( term ) + '&per_page=5';

					fetch( requestUrl )
						.then( function ( response ) {
							if ( ! response.ok ) {
								throw new Error( 'Search request failed' );
							}
							return response.json();
						} )
						.then( function ( results ) {
							positionOverlay();
							renderResults( results );
						} )
						.catch( function () {
							overlay.innerHTML = '<div class="tv3-live-search-empty">Suche fehlgeschlagen</div>';
							positionOverlay();
							overlay.hidden = false;
							input.setAttribute( 'aria-expanded', 'true' );
						} );
				}, 180 );
			} );

			input.addEventListener( 'focus', function () {
				if ( input.value.trim().length >= 2 ) {
					positionOverlay();
					overlay.hidden = false;
				}
			} );

			input.addEventListener( 'keydown', function ( event ) {
				if ( event.key === 'Escape' ) {
					closeOverlay();
				}
			} );

			document.addEventListener( 'click', function ( event ) {
				var target = event.target;
				if ( ! wrap.contains( target ) && ! overlay.contains( target ) ) {
					closeOverlay();
				}
			} );

			window.addEventListener( 'resize', function () {
				if ( ! overlay.hidden ) {
					positionOverlay();
				}
			} );
		} );
	};

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initializeLiveSearch );
	} else {
		initializeLiveSearch();
	}
} )();
