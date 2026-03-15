/**
 * Term Cleaner – Cleanup Tool JavaScript
 *
 * Handles all AJAX interactions on the "Cleanup Tool" admin page:
 *   – Preview nonsensical / similar terms
 *   – Bulk-delete selected terms
 *   – Merge a pair of terms
 *   – Auto-consolidate all similar terms in a taxonomy
 */

/* global themisdbCleaner, jQuery */
(function ( $ ) {
	'use strict';

	var i18n    = themisdbCleaner.i18n   || {};
	var ajaxurl = themisdbCleaner.ajaxurl;
	var nonce   = themisdbCleaner.nonce;

	// ------------------------------------------------------------------
	// Helpers
	// ------------------------------------------------------------------

	function showNotice( message, type ) {
		var $notice = $( '#cleaner-notice' );
		$notice
			.removeClass( 'notice-success notice-error notice-warning' )
			.addClass( 'notice-' + ( type || 'success' ) + ' is-dismissible' )
			.html( '<p>' + message + '</p>' )
			.show();

		if ( type !== 'error' ) {
			setTimeout( function () {
				$notice.fadeOut();
			}, 6000 );
		}

		$( 'html, body' ).animate( { scrollTop: ( $notice.offset() ? $notice.offset().top - 60 : 0 ) }, 300 );
	}

	function setLoading( $btn, loading ) {
		if ( loading ) {
			$btn.data( 'original-text', $btn.text() )
				.prop( 'disabled', true )
				.text( i18n.processing || 'Processing…' );
		} else {
			$btn.prop( 'disabled', false )
				.text( $btn.data( 'original-text' ) || $btn.text() );
		}
	}

	/** Collect checked term IDs from a table with class .cleaner-table */
	function getCheckedIds( $table ) {
		var ids = [];
		$table.find( 'input.cleaner-cb:checked' ).each( function () {
			ids.push( $( this ).val() );
		} );
		return ids;
	}

	// ------------------------------------------------------------------
	// Preview panel
	// ------------------------------------------------------------------

	function loadPreview() {
		var $btn  = $( '#btn-preview' );
		var $area = $( '#preview-area' );

		setLoading( $btn, true );
		$area.html( '<p>' + ( i18n.loading || 'Loading…' ) + '</p>' );

		$.post( ajaxurl, {
			action : 'themisdb_get_cleanup_preview',
			nonce  : nonce
		}, function ( response ) {
			setLoading( $btn, false );

			if ( ! response.success ) {
				$area.html( '<p class="error">' + ( response.data && response.data.message ? response.data.message : i18n.errorGeneric ) + '</p>' );
				return;
			}

			renderPreview( response.data, $area );
		} ).fail( function () {
			setLoading( $btn, false );
			$area.html( '<p class="error">' + i18n.errorAjax + '</p>' );
		} );
	}

	function renderPreview( data, $area ) {
		var html = '';

		html += renderNonsensicalSection(
			data.nonsensical_categories,
			'category',
			i18n.nonsensicalCategories || 'Nonsensical Categories'
		);

		html += renderNonsensicalSection(
			data.nonsensical_tags,
			'post_tag',
			i18n.nonsensicalTags || 'Nonsensical Tags'
		);

		html += renderSimilarSection(
			data.similar_categories,
			'category',
			i18n.similarCategories || 'Similar Categories (merge candidates)'
		);

		html += renderSimilarSection(
			data.similar_tags,
			'post_tag',
			i18n.similarTags || 'Similar Tags (merge candidates)'
		);

		if ( html === '' ) {
			html = '<p class="cleaner-ok">✅ ' + ( i18n.allClean || 'Everything looks clean! No nonsensical or duplicate terms found.' ) + '</p>';
		}

		$area.html( html );

		// Attach dynamic handlers AFTER inserting HTML.
		attachDynamicHandlers( $area );
	}

	function renderNonsensicalSection( items, taxonomy, heading ) {
		if ( ! items || items.length === 0 ) {
			return '';
		}

		var html = '<div class="cleaner-section">';
		html    += '<h3>' + heading + ' <span class="cleaner-count">(' + items.length + ')</span></h3>';
		html    += '<table class="wp-list-table widefat fixed striped cleaner-table" data-taxonomy="' + taxonomy + '">';
		html    += '<thead><tr>'
			   + '<th class="check-column"><input type="checkbox" class="cleaner-cb-all"></th>'
			   + '<th>' + ( i18n.termName    || 'Term'       ) + '</th>'
			   + '<th>' + ( i18n.reason      || 'Reason'     ) + '</th>'
			   + '<th>' + ( i18n.postsCount  || 'Posts'      ) + '</th>'
			   + '<th>' + ( i18n.action      || 'Action'     ) + '</th>'
			   + '</tr></thead><tbody>';

		items.forEach( function ( item ) {
			html += '<tr data-term-id="' + item.term_id + '">'
				  + '<td class="check-column"><input type="checkbox" class="cleaner-cb" value="' + item.term_id + '"></td>'
				  + '<td><strong>' + $( '<span>' ).text( item.name ).html() + '</strong></td>'
				  + '<td>' + $( '<span>' ).text( item.reason ).html() + '</td>'
				  + '<td>' + item.post_count + '</td>'
				  + '<td><button class="button button-small btn-delete-single" data-term-id="' + item.term_id + '" data-taxonomy="' + taxonomy + '">'
				  + ( i18n.delete || 'Delete' ) + '</button></td>'
				  + '</tr>';
		} );

		html += '</tbody></table>';
		html += '<div class="cleaner-bulk-actions">'
			  + '<button class="button button-primary btn-delete-selected" data-taxonomy="' + taxonomy + '">'
			  + ( i18n.deleteSelected || 'Delete Selected' ) + '</button>'
			  + '</div>';
		html += '</div>';

		return html;
	}

	function renderSimilarSection( items, taxonomy, heading ) {
		if ( ! items || items.length === 0 ) {
			return '';
		}

		var html = '<div class="cleaner-section">';
		html    += '<h3>' + heading + ' <span class="cleaner-count">(' + items.length + ')</span></h3>';

		html += '<div class="cleaner-bulk-actions cleaner-bulk-top">'
			  + '<button class="button button-primary btn-auto-consolidate" data-taxonomy="' + taxonomy + '">'
			  + ( i18n.autoConsolidate || 'Auto-Consolidate All' ) + '</button>'
			  + '</div>';

		html += '<table class="wp-list-table widefat fixed striped cleaner-similar-table">';
		html += '<thead><tr>'
			  + '<th>' + ( i18n.term1       || 'Term 1'     ) + '</th>'
			  + '<th>' + ( i18n.term2       || 'Term 2'     ) + '</th>'
			  + '<th>' + ( i18n.similarity  || 'Similarity' ) + '</th>'
			  + '<th>' + ( i18n.postsCount  || 'Posts'      ) + '</th>'
			  + '<th>' + ( i18n.action      || 'Action'     ) + '</th>'
			  + '</tr></thead><tbody>';

		items.forEach( function ( item ) {
			html += '<tr>'
				  + '<td>' + $( '<span>' ).text( item.term1 ).html() + '</td>'
				  + '<td>' + $( '<span>' ).text( item.term2 ).html() + '</td>'
				  + '<td>' + Math.round( item.similarity * 100 ) + '%</td>'
				  + '<td>' + item.post_count + '</td>'
				  + '<td>'
				  + '<button class="button button-small btn-merge-terms"'
				  + ' data-keep="' + item.id1 + '" data-remove="' + item.id2 + '" data-taxonomy="' + taxonomy + '">'
				  + ( i18n.merge || 'Merge' )
				  + '</button>'
				  + '</td>'
				  + '</tr>';
		} );

		html += '</tbody></table></div>';

		return html;
	}

	// ------------------------------------------------------------------
	// Dynamic event handlers (attached after HTML is injected)
	// ------------------------------------------------------------------

	function attachDynamicHandlers( $area ) {

		// Select-all checkbox.
		$area.on( 'change', '.cleaner-cb-all', function () {
			var $table = $( this ).closest( 'table' );
			$table.find( '.cleaner-cb' ).prop( 'checked', this.checked );
		} );

		// Single delete button.
		$area.on( 'click', '.btn-delete-single', function () {
			var $btn      = $( this );
			var termId    = $btn.data( 'term-id' );
			var taxonomy  = $btn.data( 'taxonomy' );

			if ( ! confirm( i18n.confirmDelete || 'Delete this term? This cannot be undone.' ) ) {
				return;
			}

			setLoading( $btn, true );

			$.post( ajaxurl, {
				action   : 'themisdb_delete_terms_batch',
				nonce    : nonce,
				term_ids : [ termId ],
				taxonomy : taxonomy
			}, function ( response ) {
				setLoading( $btn, false );
				if ( response.success ) {
					$btn.closest( 'tr' ).fadeOut( 300, function () {
						$( this ).remove();
						updateSectionCount( $btn );
					} );
					showNotice( response.data.message, 'success' );
				} else {
					showNotice( response.data && response.data.message ? response.data.message : i18n.errorGeneric, 'error' );
				}
			} ).fail( function () {
				setLoading( $btn, false );
				showNotice( i18n.errorAjax, 'error' );
			} );
		} );

		// Bulk delete selected button.
		$area.on( 'click', '.btn-delete-selected', function () {
			var $btn     = $( this );
			var taxonomy = $btn.data( 'taxonomy' );
			var $table   = $btn.closest( '.cleaner-section' ).find( '.cleaner-table' );
			var ids      = getCheckedIds( $table );

			if ( ids.length === 0 ) {
				showNotice( i18n.noneSelected || 'Please select at least one term.', 'warning' );
				return;
			}

			if ( ! confirm( ( i18n.confirmDeleteBulk || 'Delete {n} terms? This cannot be undone.' ).replace( '{n}', ids.length ) ) ) {
				return;
			}

			setLoading( $btn, true );

			$.post( ajaxurl, {
				action   : 'themisdb_delete_terms_batch',
				nonce    : nonce,
				term_ids : ids,
				taxonomy : taxonomy
			}, function ( response ) {
				setLoading( $btn, false );
				if ( response.success ) {
					// Remove rows.
					ids.forEach( function ( id ) {
						$table.find( 'input[value="' + id + '"]' ).closest( 'tr' ).remove();
					} );
					updateSectionCount( $btn );
					showNotice( response.data.message, 'success' );
				} else {
					showNotice( response.data && response.data.message ? response.data.message : i18n.errorGeneric, 'error' );
				}
			} ).fail( function () {
				setLoading( $btn, false );
				showNotice( i18n.errorAjax, 'error' );
			} );
		} );

		// Merge button (similar terms table).
		$area.on( 'click', '.btn-merge-terms', function () {
			var $btn     = $( this );
			var keepId   = $btn.data( 'keep' );
			var removeId = $btn.data( 'remove' );
			var taxonomy = $btn.data( 'taxonomy' );

			if ( ! confirm( i18n.confirmMerge || 'Merge these terms? The second term will be deleted. This cannot be undone.' ) ) {
				return;
			}

			setLoading( $btn, true );

			$.post( ajaxurl, {
				action   : 'themisdb_merge_terms_taxonomy',
				nonce    : nonce,
				keep_id  : keepId,
				remove_id: removeId,
				taxonomy : taxonomy
			}, function ( response ) {
				setLoading( $btn, false );
				if ( response.success ) {
					$btn.closest( 'tr' ).fadeOut( 300, function () {
						$( this ).remove();
						updateSectionCount( $btn );
					} );
					showNotice( response.data.message, 'success' );
				} else {
					showNotice( response.data && response.data.message ? response.data.message : i18n.errorGeneric, 'error' );
				}
			} ).fail( function () {
				setLoading( $btn, false );
				showNotice( i18n.errorAjax, 'error' );
			} );
		} );

		// Auto-consolidate all similar terms in a taxonomy.
		$area.on( 'click', '.btn-auto-consolidate', function () {
			var $btn     = $( this );
			var taxonomy = $btn.data( 'taxonomy' );

			if ( ! confirm( i18n.confirmConsolidate || 'Auto-consolidate all similar terms in this taxonomy? This cannot be undone.' ) ) {
				return;
			}

			setLoading( $btn, true );

			$.post( ajaxurl, {
				action  : 'themisdb_consolidate_taxonomy',
				nonce   : nonce,
				taxonomy: taxonomy
			}, function ( response ) {
				setLoading( $btn, false );
				if ( response.success ) {
					var msg = ( i18n.consolidatedCount || 'Consolidated {n} terms.' ).replace( '{n}', response.data.total_merged );
					showNotice( msg, 'success' );
					// Reload the preview section.
					loadPreview();
				} else {
					showNotice( response.data && response.data.message ? response.data.message : i18n.errorGeneric, 'error' );
				}
			} ).fail( function () {
				setLoading( $btn, false );
				showNotice( i18n.errorAjax, 'error' );
			} );
		} );
	}

	/** Update the count badge in a section heading after row removal. */
	function updateSectionCount( $el ) {
		var $section = $el.closest( '.cleaner-section' );
		if ( ! $section.length ) {
			return;
		}
		var remaining = $section.find( 'tbody tr:visible' ).length;
		$section.find( '.cleaner-count' ).text( '(' + remaining + ')' );
	}

	// ------------------------------------------------------------------
	// Boot
	// ------------------------------------------------------------------

	$( document ).ready( function () {
		// Load preview immediately when the page opens.
		loadPreview();

		// Re-load preview on button click.
		$( '#btn-preview' ).on( 'click', loadPreview );
	} );

}( jQuery ) );
