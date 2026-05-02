/* ThemisDB Taxonomy Manager - Tree View JavaScript */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Initialize tree view
        initTreeView();
        
        // Taxonomy selector change
        $('#taxonomy-selector').on('change', function() {
            var taxonomy = $(this).val();
            window.location.href = 'admin.php?page=themisdb-taxonomy-tree&taxonomy=' + taxonomy;
        });
        
        // Search/Filter
        $('#taxonomy-search').on('input', function() {
            var query = $(this).val().toLowerCase();
            
            if (query === '') {
                $('.tree-item').show();
                return;
            }
            
            $('.tree-item').each(function() {
                var label = $(this).find('.tree-label').first().text().toLowerCase();
                if (label.includes(query)) {
                    $(this).show();
                    // Show all parents
                    $(this).parents('.tree-item').show();
                    // Show all children
                    $(this).find('.tree-item').show();
                } else {
                    // Only hide if no children match
                    var hasMatchingChildren = $(this).find('.tree-label').filter(function() {
                        return $(this).text().toLowerCase().includes(query);
                    }).length > 0;
                    
                    if (!hasMatchingChildren) {
                        $(this).hide();
                    }
                }
            });
        });
        
        // Expand all
        $('#expand-all').on('click', function() {
            $('.tree-children').slideDown(200);
            $('.tree-toggle').removeClass('collapsed');
        });
        
        // Collapse all
        $('#collapse-all').on('click', function() {
            $('.tree-children').slideUp(200);
            $('.tree-toggle').addClass('collapsed');
        });
        
        // Export JSON
        $('#export-tree').on('click', function() {
            var $btn = $(this);
            var originalText = $btn.text();
            
            $btn.prop('disabled', true).text('Exporting...');
            
            $.post(themisdbTaxonomy.ajaxurl, {
                action: 'themisdb_export_taxonomies',
                nonce: themisdbTaxonomy.nonce
            }, function(response) {
                if (response.success) {
                    // Create JSON blob and download
                    var dataStr = JSON.stringify(response.data, null, 2);
                    var dataBlob = new Blob([dataStr], {type: 'application/json'});
                    var url = URL.createObjectURL(dataBlob);
                    
                    var a = document.createElement('a');
                    a.href = url;
                    a.download = 'themisdb-taxonomies-' + response.data.export_date + '.json';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                    
                    alert('Export completed successfully!');
                } else {
                    alert('Export failed: ' + response.data.message);
                }
                
                $btn.prop('disabled', false).text(originalText);
            }).fail(function() {
                alert('Export failed. Please try again.');
                $btn.prop('disabled', false).text(originalText);
            });
        });
        
        /**
         * Initialize tree view functionality
         */
        function initTreeView() {
            // Toggle expand/collapse - support both click and keyboard
            $(document).on('click keydown', '.tree-toggle', function(e) {
                // For keyboard: only respond to Enter or Space
                if (e.type === 'keydown' && (e.key !== 'Enter' && e.key !== ' ')) {
                    return;
                }
                
                // Prevent default for keyboard events to avoid page scroll
                if (e.type === 'keydown') {
                    e.preventDefault();
                }
                
                e.stopPropagation();
                var $toggle = $(this);
                var $children = $toggle.closest('.tree-item').find('> .tree-children');
                
                if ($children.length > 0) {
                    var wasExpanded = !$toggle.hasClass('collapsed');
                    $children.slideToggle(200);
                    $toggle.toggleClass('collapsed');
                    
                    // Update aria-expanded for accessibility
                    $toggle.attr('aria-expanded', wasExpanded ? 'false' : 'true');
                }
            });
            
            // Initialize jQuery UI Sortable for drag & drop
            if ($.fn.sortable) {
                $('.taxonomy-tree, .tree-children').sortable({
                    connectWith: '.taxonomy-tree, .tree-children',
                    placeholder: 'tree-placeholder',
                    handle: '.tree-node',
                    cursor: 'move',
                    opacity: 0.8,
                    tolerance: 'pointer',
                    update: function(event, ui) {
                        // Only trigger on the receiving list
                        if (this === ui.item.parent()[0]) {
                            saveTermOrder($(this));
                        }
                    },
                    start: function(event, ui) {
                        // Collapse children during drag
                        ui.item.find('.tree-children').hide();
                    },
                    stop: function(event, ui) {
                        // Restore children after drag
                        ui.item.find('.tree-children').show();
                    }
                });
            }
        }
        
        /**
         * Save term order via AJAX
         */
        function saveTermOrder($list) {
            var order = $list.sortable('toArray', {attribute: 'data-term-id'});
            
            // Filter out empty values
            order = order.filter(function(id) {
                return id !== '';
            });
            
            if (order.length === 0) {
                return;
            }
            
            $.post(themisdbTaxonomy.ajaxurl, {
                action: 'themisdb_save_term_order',
                nonce: themisdbTaxonomy.nonce,
                order: order
            }, function(response) {
                if (response.success) {
                    // Show success indicator
                    showNotification('Order saved', 'success');
                } else {
                    showNotification('Failed to save order', 'error');
                }
            }).fail(function() {
                showNotification('Failed to save order', 'error');
            });
        }
        
        /**
         * Show notification message
         */
        function showNotification(message, type) {
            var $notification = $('<div>')
                .addClass('themisdb-notification')
                .addClass('notification-' + type)
                .text(message)
                .css({
                    position: 'fixed',
                    top: '32px',
                    right: '20px',
                    padding: '12px 20px',
                    background: type === 'success' ? '#27ae60' : '#e74c3c',
                    color: 'white',
                    borderRadius: '4px',
                    zIndex: 100000,
                    boxShadow: '0 2px 8px rgba(0,0,0,0.2)',
                    fontWeight: '500'
                });
            
            $('body').append($notification);
            
            setTimeout(function() {
                $notification.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
        }
        
        // Inline editing (future enhancement)
        $(document).on('dblclick', '.tree-label', function() {
            var $label = $(this);
            var currentText = $label.text();
            var $treeItem = $label.closest('.tree-item');
            var termId = $label.closest('.tree-item').data('term-id');
            var taxonomy = $('.taxonomy-tree').first().data('taxonomy');

            // Prevent creating nested editors when double-clicking repeatedly.
            if ($label.find('input[type="text"]').length) {
                return;
            }
            
            // Create inline input
            var $input = $('<input type="text">')
                .val(currentText)
                .addClass('tree-label-inline-input')
                .css({
                    'width': '100%',
                    'font-weight': '500',
                    'border': '1px solid #3498db',
                    'padding': '4px 8px',
                    'border-radius': '4px'
                });

            var $status = $('<span>')
                .addClass('tree-label-inline-status')
                .attr('aria-live', 'polite');
            
            $label.html($input).append($status);
            $input.focus().select();

            var hasSaved = false;

            function restoreOriginal() {
                $label.text(currentText);
            }

            function hasDuplicateSiblingName(candidateName) {
                var normalizedCandidate = candidateName.toLowerCase();
                var isDuplicate = false;

                $treeItem.siblings('.tree-item').each(function() {
                    var siblingName = $(this).find('> .tree-node .tree-label').first().text().trim().toLowerCase();
                    if (siblingName === normalizedCandidate) {
                        isDuplicate = true;
                        return false;
                    }
                });

                return isDuplicate;
            }

            function saveIfChanged() {
                if (hasSaved) {
                    return;
                }

                var newText = $input.val().trim();
                if (newText === '' || newText === currentText) {
                    restoreOriginal();
                    return;
                }

                if (hasDuplicateSiblingName(newText)) {
                    $input.addClass('is-invalid').focus().select();
                    $status.text('Name already exists on this level');
                    showNotification('Duplicate name on this level', 'error');
                    return;
                }

                hasSaved = true;
                $input.prop('disabled', true);
                $input.addClass('is-saving').attr('aria-busy', 'true');
                $status.text('Saving...');

                $.post(themisdbTaxonomy.ajaxurl, {
                    action: 'themisdb_rename_term',
                    nonce: themisdbTaxonomy.nonce,
                    term_id: termId,
                    taxonomy: taxonomy,
                    name: newText
                }, function(response) {
                    if (response.success) {
                        $label.text(newText);
                        $label.addClass('tree-label-renamed');
                        setTimeout(function() {
                            $label.removeClass('tree-label-renamed');
                        }, 1500);
                        showNotification('Name updated', 'success');
                    } else {
                        restoreOriginal();
                        var message = response && response.data && response.data.message
                            ? response.data.message
                            : 'Failed to update name';
                        showNotification(message, 'error');
                    }
                }).fail(function() {
                    restoreOriginal();
                    showNotification('Failed to update name', 'error');
                });
            }
            
            // Save on blur or enter
            $input.on('blur keydown', function(e) {
                if (e.type === 'keydown' && e.key === 'Escape') {
                    e.preventDefault();
                    hasSaved = true;
                    restoreOriginal();
                    return;
                }

                if (e.type === 'keydown' && e.key === 'Enter') {
                    e.preventDefault();
                    saveIfChanged();
                    return;
                }

                if (e.type === 'blur') {
                    saveIfChanged();
                }
            });

            $input.on('input', function() {
                $input.removeClass('is-invalid');
                $status.text('');
            });
        });
        
    });
    
})(jQuery);
