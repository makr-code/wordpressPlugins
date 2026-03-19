/* ThemisDB Order Request Plugin - Frontend JavaScript */

jQuery(document).ready(function($) {
    'use strict';
    
    var currentStep = 1;
    var orderData = {};
    
    // Product selection
    $(document).on('click', '.product-card', function() {
        $('.product-card').removeClass('selected');
        $(this).addClass('selected');
        $(this).find('input[type="radio"]').prop('checked', true);
    });
    
    // Module/Training card selection
    $(document).on('change', '.module-card input[type="checkbox"], .training-card input[type="checkbox"]', function() {
        if ($(this).is(':checked')) {
            $(this).closest('.module-card, .training-card').addClass('selected');
        } else {
            $(this).closest('.module-card, .training-card').removeClass('selected');
        }
        calculateTotal();
    });
    
    // Next button
    $(document).on('click', '.button-next', function() {
        var step = $(this).data('step');
        var data = collectStepData(step);
        
        if (!validateStep(step, data)) {
            return;
        }
        
        saveOrderStep(step, data, function(response) {
            if (response.success) {
                loadStep(step + 1);
            }
        });
    });
    
    // Previous button
    $(document).on('click', '.button-prev', function() {
        var step = $(this).data('step');
        loadStep(step - 1);
    });
    
    // Submit button
    $(document).on('click', '.button-submit', function() {
        var $button = $(this);
        $button.prop('disabled', true).text(themisdbOrder.strings.loading);
        
        $.ajax({
            url: themisdbOrder.ajaxUrl,
            type: 'POST',
            data: {
                action: 'themisdb_submit_order',
                nonce: themisdbOrder.nonce
            },
            success: function(response) {
                if (response.success) {
                    showSuccessMessage(response.data.message);
                    // Redirect or show confirmation
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                } else {
                    showErrorMessage(response.data.message);
                    $button.prop('disabled', false).text('Bestellung absenden');
                }
            },
            error: function() {
                showErrorMessage(themisdbOrder.strings.error);
                $button.prop('disabled', false).text('Bestellung absenden');
            }
        });
    });
    
    /**
     * Collect step data
     */
    function collectStepData(step) {
        var data = {};
        
        switch(step) {
            case 1:
                data.product_edition = $('input[name="product_edition"]:checked').val();
                break;
                
            case 2:
                data.modules = [];
                $('input[name="modules[]"]:checked').each(function() {
                    data.modules.push($(this).val());
                });
                break;
                
            case 3:
                data.training_modules = [];
                $('input[name="training_modules[]"]:checked').each(function() {
                    data.training_modules.push($(this).val());
                });
                break;
                
            case 4:
                data.customer_name = $('#customer_name').val();
                data.customer_email = $('#customer_email').val();
                data.customer_company = $('#customer_company').val();
                data.customer_type = $('input[name="customer_type"]:checked').val() || 'consumer';
                data.vat_id = $('#vat_id').val();
                data.billing_name = $('#billing_name').val();
                data.billing_address_line1 = $('#billing_address_line1').val();
                data.billing_address_line2 = $('#billing_address_line2').val();
                data.billing_postal_code = $('#billing_postal_code').val();
                data.billing_city = $('#billing_city').val();
                data.billing_country = ($('#billing_country').val() || '').toUpperCase();
                data.shipping_name = $('#shipping_name').val();
                data.shipping_address_line1 = $('#shipping_address_line1').val();
                data.shipping_address_line2 = $('#shipping_address_line2').val();
                data.shipping_postal_code = $('#shipping_postal_code').val();
                data.shipping_city = $('#shipping_city').val();
                data.shipping_country = ($('#shipping_country').val() || '').toUpperCase();
                data.shipping_method = $('#shipping_method').val();
                data.legal_terms_accepted = $('input[name="legal_terms_accepted"]').is(':checked') ? '1' : '';
                data.legal_privacy_accepted = $('input[name="legal_privacy_accepted"]').is(':checked') ? '1' : '';
                data.legal_withdrawal_acknowledged = $('input[name="legal_withdrawal_acknowledged"]').is(':checked') ? '1' : '';
                data.legal_withdrawal_waiver = $('input[name="legal_withdrawal_waiver"]').is(':checked') ? '1' : '';
                break;
        }
        
        return data;
    }
    
    /**
     * Validate step data
     */
    function validateStep(step, data) {
        switch(step) {
            case 1:
                if (!data.product_edition) {
                    showErrorMessage('Bitte wählen Sie ein Produkt aus.');
                    return false;
                }
                break;
                
            case 4:
                if (!data.customer_name || !data.customer_email || !data.billing_name || !data.billing_address_line1 || !data.billing_postal_code || !data.billing_city || !data.billing_country) {
                    showErrorMessage('Bitte füllen Sie alle Pflichtfelder aus.');
                    return false;
                }
                
                if (!isValidEmail(data.customer_email)) {
                    showErrorMessage('Bitte geben Sie eine gültige E-Mail-Adresse ein.');
                    return false;
                }
                
                if (!$('input[name="legal_terms_accepted"]').is(':checked')) {
                    showErrorMessage('Bitte akzeptieren Sie die AGB.');
                    return false;
                }

                if (!$('input[name="legal_privacy_accepted"]').is(':checked')) {
                    showErrorMessage('Bitte akzeptieren Sie die Datenschutzerklärung.');
                    return false;
                }

                if ((data.customer_type || 'consumer') === 'consumer' && !$('input[name="legal_withdrawal_acknowledged"]').is(':checked')) {
                    showErrorMessage('Bitte bestätigen Sie die Widerrufsbelehrung.');
                    return false;
                }
                break;
        }
        
        return true;
    }
    
    /**
     * Save order step
     */
    function saveOrderStep(step, data, callback) {
        $.ajax({
            url: themisdbOrder.ajaxUrl,
            type: 'POST',
            data: {
                action: 'themisdb_save_order_step',
                nonce: themisdbOrder.nonce,
                step: step,
                data: data
            },
            success: callback,
            error: function() {
                showErrorMessage(themisdbOrder.strings.error);
            }
        });
    }
    
    /**
     * Load step
     */
    function loadStep(step) {
        // Hide all steps
        $('.order-step-content').hide();
        
        // Show target step
        $('.order-step-content[data-step="' + step + '"]').show();
        
        // Update step indicator
        $('.order-steps .step').each(function(index) {
            var stepNum = index + 1;
            $(this).removeClass('active completed');
            
            if (stepNum < step) {
                $(this).addClass('completed');
            } else if (stepNum === step) {
                $(this).addClass('active');
            }
        });
        
        currentStep = step;
        
        // Scroll to top
        $('html, body').animate({
            scrollTop: $('.themisdb-order-flow').offset().top - 100
        }, 500);
    }
    
    /**
     * Calculate total
     */
    function calculateTotal() {
        var product_edition = $('input[name="product_edition"]:checked').val();
        var modules = [];
        var training_modules = [];
        
        $('input[name="modules[]"]:checked').each(function() {
            modules.push($(this).val());
        });
        
        $('input[name="training_modules[]"]:checked').each(function() {
            training_modules.push($(this).val());
        });
        
        if (!product_edition) {
            return;
        }
        
        $.ajax({
            url: themisdbOrder.ajaxUrl,
            type: 'POST',
            data: {
                action: 'themisdb_calculate_total',
                nonce: themisdbOrder.nonce,
                product_edition: product_edition,
                modules: modules,
                training_modules: training_modules
            },
            success: function(response) {
                if (response.success && response.data.total) {
                    var total = parseFloat(response.data.total);
                    var formatted = formatCurrency(total);
                    
                    // Update total display if exists
                    $('.total-amount strong').text(formatted + ' EUR');
                }
            }
        });
    }
    
    /**
     * Format currency
     */
    function formatCurrency(amount) {
        return amount.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }
    
    /**
     * Validate email
     */
    function isValidEmail(email) {
        var regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }
    
    /**
     * Show success message
     */
    function showSuccessMessage(message) {
        var $notice = $('<div class="notice notice-success"><p>' + message + '</p></div>');
        $('.themisdb-order-flow').prepend($notice);
        
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    /**
     * Show error message
     */
    function showErrorMessage(message) {
        var $notice = $('<div class="notice notice-error"><p>' + message + '</p></div>');
        $('.themisdb-order-flow').prepend($notice);
        
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
        
        // Scroll to top to show error
        $('html, body').animate({
            scrollTop: $('.themisdb-order-flow').offset().top - 100
        }, 500);
    }
});
