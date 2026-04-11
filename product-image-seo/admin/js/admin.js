jQuery(document).ready(function($) {
    // Test Connection
    $('#prodimg-seo-test-connection').on('click', function() {
        var $btn = $(this);
        var $spinner = $('#prodimg-seo-test-spinner');
        var $result = $('#prodimg-seo-test-result');

        $spinner.addClass('is-active');
        $result.text('').removeClass('error success');

        $.post(prodimg_seo_1972adm_admin.ajax_url, {
            action: 'prodimg_seo_1972adm_test_connection',
            nonce: prodimg_seo_1972adm_admin.nonce
        }, function(response) {
            $spinner.removeClass('is-active');
            if (response.success) {
                $result.text(response.data).addClass('success').css('color', 'green');
            } else {
                $result.text(response.data).addClass('error').css('color', 'red');
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
        $result.text('Scanning catalog...').css('color', '');

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
                        $result.text('Scan complete! Scanned ' + totalScanned + ' products.').css('color', 'green');
                    } else {
                        var pct = Math.round((page / response.data.total_pages) * 100);
                        $result.text('Scanning... ' + pct + '% (' + page + '/' + response.data.total_pages + ')');
                        scanPage(page + 1);
                    }
                } else {
                    $btn.prop('disabled', false);
                    $spinner.removeClass('is-active');
                    $result.text('Error: ' + response.data).css('color', 'red');
                }
            }).fail(function() {
                $btn.prop('disabled', false);
                $spinner.removeClass('is-active');
                $result.text('An error occurred during the scan. Please try again.').css('color', 'red');
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
                $('#prodimg-seo-modal-content').html('<p style="color:red;">Error: ' + response.data + '</p>');
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
                $('#prodimg-seo-modal-overlay').fadeOut();
                // Optionally reload the page to update list table status
                location.reload();
            } else {
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
                }
            } else {
                clearInterval(bulkPollInterval);
            }
        });
    }
});
