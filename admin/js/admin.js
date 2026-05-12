/**
 * prodimgToast — HUD notification helper.
 *
 * @param {string} msg  Message text.
 * @param {string} type 'success' | 'error' | 'info'  (default 'success')
 */
function prodimgToast( msg, type ) {
    type = type || 'success';
    var $t = jQuery('<div class="prodimg-toast prodimg-toast--' + type + '">' + msg + '</div>');
    jQuery('body').append( $t );
    setTimeout(function() { $t.addClass('is-visible'); }, 10);
    setTimeout(function() {
        $t.removeClass('is-visible');
        setTimeout(function() { $t.remove(); }, 400);
    }, 2400);
}

jQuery(document).ready(function($) {
    // Test Connection
    $('#prodimg-seo-test-connection').on('click', function() {
        var $btn = $(this);
        var $spinner = $('#prodimg-seo-test-spinner');
        var $result = $('#prodimg-seo-test-result');

        $spinner.addClass('is-active');
        $result.text('').removeClass('is-success is-error');

        $.post(prodimg_seo_1972adm_admin.ajax_url, {
            action: 'prodimg_seo_1972adm_test_connection',
            nonce: prodimg_seo_1972adm_admin.nonce
        }, function(response) {
            $spinner.removeClass('is-active');
            if (response.success) {
                $result.text(response.data).removeClass('is-success is-error').addClass('is-success');
                prodimgToast(response.data, 'success');
            } else {
                $result.text(response.data).removeClass('is-success is-error').addClass('is-error');
                prodimgToast(response.data, 'error');
            }
        });
    });

    // Scan Catalog
    $('#prodimg-seo-scan-catalog').on('click', function() {
        var $btn = $(this);
        var $spinner = $('#prodimg-seo-scan-spinner');
        var $result = $('#prodimg-seo-scan-result');

        $btn.prop('disabled', true);
        $spinner.addClass('is-active');
        $result.text('Scanning catalog...').removeClass('is-success is-error');

        var totalScanned = 0;

        function scanPage(page) {
            $.post(prodimg_seo_1972adm_admin.ajax_url, {
                action: 'prodimg_seo_1972adm_scan_catalog',
                nonce: prodimg_seo_1972adm_admin.nonce,
                scan_page: page
            }, function(response) {
                if (response.success) {
                    totalScanned += response.data.scanned;

                    if (response.data.done) {
                        $btn.prop('disabled', false);
                        $spinner.removeClass('is-active');
                        var doneMsg = 'Scan complete! Scanned ' + totalScanned + ' products.';
                        $result.text(doneMsg).removeClass('is-success is-error').addClass('is-success');
                        prodimgToast(doneMsg, 'success');
                    } else {
                        var pct = Math.round((page / response.data.total_pages) * 100);
                        $result.text('Scanning... ' + pct + '% (' + page + '/' + response.data.total_pages + ')');
                        scanPage(page + 1);
                    }
                } else {
                    $btn.prop('disabled', false);
                    $spinner.removeClass('is-active');
                    $result.text('Error: ' + response.data).removeClass('is-success is-error').addClass('is-error');
                    prodimgToast('Error: ' + response.data, 'error');
                }
            }).fail(function() {
                $btn.prop('disabled', false);
                $spinner.removeClass('is-active');
                var errMsg = 'An error occurred during the scan. Please try again.';
                $result.text(errMsg).removeClass('is-success is-error').addClass('is-error');
                prodimgToast(errMsg, 'error');
            });
        }

        scanPage(1);
    });

    // Single Generation Modal
    var currentProductId = 0;

    $('.prodimg-seo-generate-single').on('click', function(e) {
        e.preventDefault();
        var $btn = $(this);
        currentProductId = $btn.data('product-id');

        $('#prodimg-seo-modal-overlay').fadeIn();
        $('#prodimg-seo-modal-content').html('<p>Loading suggestions...</p>');
        $('#prodimg-seo-modal-save').hide();

        $.post(prodimg_seo_1972adm_admin.ajax_url, {
            action: 'prodimg_seo_1972adm_generate_single',
            nonce: prodimg_seo_1972adm_admin.nonce,
            product_id: currentProductId
        }, function(response) {
            if (response.success) {
                var html = '<table class="wp-list-table widefat fixed striped">';
                html += '<thead><tr><th style="width:100px;">Image</th><th>Role / Score</th><th>Suggested Alt Text (Edit below)</th></tr></thead><tbody>';

                $.each(response.data.suggestions, function(i, item) {
                    html += '<tr>';
                    html += '<td><img src="' + item.url + '" style="max-width:100px; height:auto;" /></td>';
                    html += '<td><strong>' + item.role + '</strong><br>Score: ' + item.score + '</td>';
                    html += '<td><textarea style="width:100%; height:60px;" class="prodimg-seo-alt-input" data-image-id="' + item.image_id + '">' + item.alt_text + '</textarea></td>';
                    html += '</tr>';
                });

                html += '</tbody></table>';
                $('#prodimg-seo-modal-content').html(html);
                $('#prodimg-seo-modal-save').show();
            } else {
                $('#prodimg-seo-modal-content').html('<p class="prodimg-error-msg">Error: ' + response.data + '</p>');
            }
        });
    });

    $('#prodimg-seo-modal-close').on('click', function() {
        $('#prodimg-seo-modal-overlay').fadeOut();
    });

    $('#prodimg-seo-modal-save').on('click', function() {
        var $btn = $(this);
        var altTexts = {};

        $('.prodimg-seo-alt-input').each(function() {
            altTexts[$(this).data('image-id')] = $(this).val();
        });

        $btn.prop('disabled', true).text('Saving...');

        $.post(prodimg_seo_1972adm_admin.ajax_url, {
            action: 'prodimg_seo_1972adm_save_single',
            nonce: prodimg_seo_1972adm_admin.nonce,
            product_id: currentProductId,
            alt_texts: altTexts
        }, function(response) {
            $btn.prop('disabled', false).text('Save Approved Alt Text');
            if (response.success) {
                prodimgToast('Saved successfully.', 'success');
                $('#prodimg-seo-modal-overlay').fadeOut();
                // Reload to update list table status (defer elimination to v3).
                location.reload();
            } else {
                prodimgToast('Error saving: ' + response.data, 'error');
                alert('Error saving: ' + response.data);
            }
        });
    });

    // Bulk Processing
    var bulkPollInterval;
    $('#prodimg-seo-bulk-start').on('click', function() {
        var $btn = $(this);
        var $container = $('#prodimg-seo-bulk-progress-container');

        $btn.prop('disabled', true).text('Starting...');

        $.post(prodimg_seo_1972adm_admin.ajax_url, {
            action: 'prodimg_seo_1972adm_bulk_start',
            nonce: prodimg_seo_1972adm_admin.nonce
        }, function(response) {
            if (response.success) {
                $btn.hide();
                $container.show();
                bulkPollInterval = setInterval(pollBulkStatus, 2000);
            } else {
                $btn.prop('disabled', false).text('Start Bulk Fix');
                alert(response.data);
            }
        });
    });

    function pollBulkStatus() {
        $.post(prodimg_seo_1972adm_admin.ajax_url, {
            action: 'prodimg_seo_1972adm_bulk_status',
            nonce: prodimg_seo_1972adm_admin.nonce
        }, function(response) {
            if (response.success && response.data.status !== 'idle') {
                var completed = response.data.completed_batches;
                var total = response.data.total_batches;
                var pct = total > 0 ? Math.round((completed / total) * 100) : 0;

                $('#prodimg-seo-bulk-progress-bar').css('width', pct + '%');
                $('#prodimg-seo-bulk-progress-text').text(pct + '%');

                if (response.data.status === 'completed') {
                    clearInterval(bulkPollInterval);
                    $('#prodimg-seo-bulk-progress-text').text('Completed!');
                    jQuery(document).trigger('prodimg-seo:bulk-completed', [response.data]);
                }
            } else {
                clearInterval(bulkPollInterval);
            }
        });
    }
});

jQuery(function($) {
    // Settings tabs
    $(document).on('click', '.prodimg-tabs [role="tab"]', function(e) {
        e.preventDefault();
        var $tab = $(this);
        var $tabs = $tab.closest('.prodimg-tabs');
        $tabs.find('[role="tab"]').attr('aria-selected', 'false');
        $tab.attr('aria-selected', 'true');
        var panelId = $tab.attr('aria-controls');
        $tabs.parent().find('[role="tabpanel"]').attr('hidden', 'hidden');
        $('#' + panelId).removeAttr('hidden');
        if (history.replaceState) {
            history.replaceState(null, '', '#' + $tab.attr('id'));
        }
    });

    if (location.hash) {
        var $target = $('.prodimg-tabs [role="tab"]' + location.hash);
        if ($target.length) {
            $target.trigger('click');
        }
    }

    // Score gauge ring-sweep + count-up animation
    $('.prodimg-score-gauge[data-score]').each(function() {
        var $g     = $(this);
        var target = parseInt( $g.attr('data-score'), 10 ) || 0;
        var $prog  = $g.find('.prodimg-score-gauge__progress');
        var $val   = $g.find('.prodimg-score-gauge__value');
        var C      = 326.7; // circumference = 2 * Math.PI * 52
        var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        if ( reduce ) {
            $prog.css('stroke-dashoffset', C - ( C * target / 100 ) );
            $val.text( target );
            return;
        }
        // Trigger ring sweep
        requestAnimationFrame(function() {
            $prog.css('stroke-dashoffset', C - ( C * target / 100 ) );
        });
        // Synchronized number count-up (~1.2s, ~30 ticks)
        var start = 0;
        var step  = Math.max( 1, Math.floor( target / 30 ) );
        var timer = setInterval(function() {
            start += step;
            if ( start >= target ) { start = target; clearInterval(timer); }
            $val.text( start );
        }, 40);
    });

    // Bulk-fix completion summary card
    $(document).on('prodimg-seo:bulk-completed', function(e, data) {
        var $body = $('#prodimg-seo-bulk-results-body');
        var msg = 'Bulk fix finished. Processed ' + (data && data.total_products ? data.total_products : 'all') + ' products.';
        $body.text(msg);
        $('#prodimg-seo-bulk-results').removeAttr('hidden');
        prodimgToast(msg, 'success');
    });
});
