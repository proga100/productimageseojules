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

    // Scan Images (per-image scoring)
    $('#prodimg-seo-scan-images').on('click', function() {
        var $btn      = $(this);
        var $spinner  = $('#prodimg-scan-images-spinner');
        var $progress = $('#prodimg-scan-images-progress');

        $btn.prop('disabled', true);
        $spinner.addClass('is-active');
        $progress.text('');

        var totalProcessed = 0;

        function scanPage(page) {
            $.post(prodimg_seo_1972adm_admin.ajax_url, {
                action:    'prodimg_seo_1972adm_scan_all_images',
                nonce:     prodimg_seo_1972adm_admin.nonce,
                scan_page: page
            }, function(response) {
                if (response.success) {
                    totalProcessed += response.data.processed;

                    if (response.data.done) {
                        $btn.prop('disabled', false);
                        $spinner.removeClass('is-active');
                        $progress.text('Done! Scanned ' + totalProcessed + ' images.');
                        prodimgToast('Scan complete! ' + totalProcessed + ' images scanned.', 'success');
                    } else {
                        var pct = response.data.total_pages > 0
                            ? Math.round((page / response.data.total_pages) * 100)
                            : 0;
                        $progress.text('Scanning... ' + pct + '%');
                        scanPage(page + 1);
                    }
                } else {
                    $btn.prop('disabled', false);
                    $spinner.removeClass('is-active');
                    $progress.text('Error: ' + response.data);
                    prodimgToast('Scan error: ' + response.data, 'error');
                }
            }).fail(function() {
                $btn.prop('disabled', false);
                $spinner.removeClass('is-active');
                $progress.text('Scan failed. Please try again.');
                prodimgToast('Scan failed. Please try again.', 'error');
            });
        }

        scanPage(1);
    });

    // Single-image / single-product generation modal.
    var prodimgI18n = (prodimg_seo_1972adm_admin && prodimg_seo_1972adm_admin.i18n) || {};

    // Modal context — tracks whether we are generating for a whole product or a
    // single attachment row (so we can update that row in place after saving).
    var prodimgModalCtx = { mode: 'product', productId: 0, attachmentId: 0, $row: null, $btn: null };

    function prodimgEscHtml(str) {
        return $('<div>').text(str === null || typeof str === 'undefined' ? '' : String(str)).html();
    }

    function prodimgEscAttr(str) {
        return prodimgEscHtml(str).replace(/"/g, '&quot;');
    }

    function prodimgBandLabel(band) {
        var bands = prodimgI18n.bands || {};
        return bands[band] || band;
    }

    function prodimgRenderStatusBadge(band) {
        return '<span class="prodimg-status-badge prodimg-status-badge--' + prodimgEscAttr(band) + '">'
            + prodimgEscHtml(prodimgBandLabel(band)) + '</span>';
    }

    function prodimgRenderScoreBar(score, band) {
        if (score === '' || score === null || typeof score === 'undefined') {
            return '<span class="prodimg-text-secondary">—</span>';
        }
        score = parseInt(score, 10) || 0;
        var fillBand = (band === 'missing' || band === 'weak' || band === 'good' || band === 'excellent')
            ? band
            : (score >= 86 ? 'excellent' : (score >= 61 ? 'good' : (score > 0 ? 'weak' : 'missing')));
        return '<div class="prodimg-score-bar"><div class="prodimg-score-bar__track">'
            + '<div class="prodimg-score-bar__fill prodimg-score-bar__fill--' + prodimgEscAttr(fillBand) + '" style="width:' + score + '%;"></div>'
            + '</div><span>' + score + '</span></div>';
    }

    // Update a catalog list-table row (status badge, score bar, alt text) in place.
    function prodimgUpdateRow($row, data) {
        if (!$row || !$row.length) { return; }
        if (typeof data.band !== 'undefined') {
            $row.find('td.column-status').html(prodimgRenderStatusBadge(data.band));
        }
        if (typeof data.score !== 'undefined') {
            var $scoreCell = $row.find('td.column-quality_score');
            $scoreCell.html(prodimgRenderScoreBar(data.score, data.band));
            if (data.explanation) {
                $scoreCell.attr('title', data.explanation);
            }
        }
        if (typeof data.alt_text !== 'undefined') {
            var alt = $.trim(data.alt_text);
            var $altCell = $row.find('td.column-alt_text');
            if (alt === '') {
                $altCell.html('<em class="prodimg-text-secondary">' + prodimgEscHtml(prodimgI18n.noAltText) + '</em>');
            } else {
                var display = alt.length > 100 ? alt.substring(0, 97) + '…' : alt;
                $altCell.html('<span title="' + prodimgEscAttr(alt) + '">' + prodimgEscHtml(display) + '</span>');
            }
        }
    }

    function prodimgRenderSuggestions(suggestions) {
        var html = '<table class="wp-list-table widefat fixed striped">';
        html += '<thead><tr><th style="width:100px;">' + prodimgEscHtml(prodimgI18n.image)
            + '</th><th>' + prodimgEscHtml(prodimgI18n.roleScore)
            + '</th><th>' + prodimgEscHtml(prodimgI18n.suggestedAlt) + '</th></tr></thead><tbody>';

        $.each(suggestions, function(i, item) {
            html += '<tr>';
            html += '<td><img src="' + prodimgEscAttr(item.url) + '" alt="" style="max-width:100px; height:auto;" /></td>';
            html += '<td><strong>' + prodimgEscHtml(item.role) + '</strong><br>'
                + prodimgEscHtml(prodimgI18n.scoreLabel) + ': ' + prodimgEscHtml(item.score) + '</td>';
            html += '<td><textarea style="width:100%; height:60px;" class="prodimg-seo-alt-input" data-image-id="'
                + prodimgEscAttr(item.image_id) + '">' + prodimgEscHtml(item.alt_text) + '</textarea></td>';
            html += '</tr>';
        });

        html += '</tbody></table>';
        return html;
    }

    function prodimgCloseModal() {
        $('#prodimg-seo-modal-overlay').fadeOut();
        if (prodimgModalCtx.$btn && prodimgModalCtx.$btn.length) {
            prodimgModalCtx.$btn.prop('disabled', false).text(prodimgModalCtx.btnLabel);
        }
    }

    // Open the modal and request suggestions for the given payload/context.
    function prodimgOpenGenerate(payload, ctx) {
        prodimgModalCtx = ctx;

        $('#prodimg-seo-modal-overlay').fadeIn();
        $('#prodimg-seo-modal-content').html('<p>' + prodimgEscHtml(prodimgI18n.loading) + '</p>');
        $('#prodimg-seo-modal-save').hide();

        $.post(prodimg_seo_1972adm_admin.ajax_url, payload, function(response) {
            if (ctx.$btn && ctx.$btn.length) {
                ctx.$btn.prop('disabled', false).text(ctx.btnLabel);
            }
            if (response.success) {
                if (response.data && typeof response.data.product_id !== 'undefined') {
                    prodimgModalCtx.productId = response.data.product_id;
                }
                $('#prodimg-seo-modal-content').html(prodimgRenderSuggestions(response.data.suggestions));
                $('#prodimg-seo-modal-save').show();
            } else {
                $('#prodimg-seo-modal-content').html('<p class="prodimg-error-msg">'
                    + prodimgEscHtml(prodimgI18n.error) + ' ' + prodimgEscHtml(response.data) + '</p>');
            }
        }).fail(function() {
            if (ctx.$btn && ctx.$btn.length) {
                ctx.$btn.prop('disabled', false).text(ctx.btnLabel);
            }
            $('#prodimg-seo-modal-content').html('<p class="prodimg-error-msg">' + prodimgEscHtml(prodimgI18n.error) + '</p>');
        });
    }

    // Product-level generate (kept for back-compat with any product-scoped button).
    $(document).on('click', '.prodimg-seo-generate-single', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var pid  = $btn.data('product-id');
        prodimgOpenGenerate(
            {
                action: 'prodimg_seo_1972adm_generate_single',
                nonce: prodimg_seo_1972adm_admin.nonce,
                product_id: pid
            },
            { mode: 'product', productId: pid, attachmentId: 0, $row: $btn.closest('tr'), $btn: null, btnLabel: '' }
        );
    });

    // Per-image generate (catalog list-table row action + actions column).
    $(document).on('click', '.prodimg-seo-generate-attachment', function(e) {
        e.preventDefault();
        var $btn  = $(this);
        var aid   = $btn.data('attachment-id');
        var label = $btn.text();
        $btn.prop('disabled', true).text(prodimgI18n.generating);
        prodimgOpenGenerate(
            {
                action: 'prodimg_seo_1972adm_generate_single',
                nonce: prodimg_seo_1972adm_admin.nonce,
                attachment_id: aid
            },
            { mode: 'attachment', productId: 0, attachmentId: aid, $row: $btn.closest('tr'), $btn: $btn, btnLabel: label }
        );
    });

    // Per-image recalculate score.
    $(document).on('click', '.prodimg-seo-recalc-attachment', function(e) {
        e.preventDefault();
        var $btn  = $(this);
        var aid   = $btn.data('attachment-id');
        var $row  = $btn.closest('tr');
        var label = $btn.text();

        $btn.prop('disabled', true).text(prodimgI18n.recalculating);

        $.post(prodimg_seo_1972adm_admin.ajax_url, {
            action: 'prodimg_seo_1972adm_recalc_score',
            nonce: prodimg_seo_1972adm_admin.nonce,
            attachment_id: aid
        }, function(response) {
            $btn.prop('disabled', false).text(label);
            if (response.success) {
                prodimgUpdateRow($row, {
                    score: response.data.score,
                    band: response.data.band,
                    explanation: response.data.explanation
                });
                prodimgToast(prodimgI18n.scoreUpdated, 'success');
            } else {
                prodimgToast(prodimgI18n.error + ' ' + response.data, 'error');
            }
        }).fail(function() {
            $btn.prop('disabled', false).text(label);
            prodimgToast(prodimgI18n.error, 'error');
        });
    });

    $('#prodimg-seo-modal-close').on('click', function() {
        prodimgCloseModal();
    });

    $('#prodimg-seo-modal-save').on('click', function() {
        var $btn = $(this);
        var altTexts = {};

        $('.prodimg-seo-alt-input').each(function() {
            altTexts[$(this).data('image-id')] = $(this).val();
        });

        $btn.prop('disabled', true).text(prodimgI18n.saving);

        $.post(prodimg_seo_1972adm_admin.ajax_url, {
            action: 'prodimg_seo_1972adm_save_single',
            nonce: prodimg_seo_1972adm_admin.nonce,
            product_id: prodimgModalCtx.productId || 0,
            alt_texts: altTexts
        }, function(response) {
            $btn.prop('disabled', false).text(prodimgI18n.save);
            if (response.success) {
                prodimgToast(prodimgI18n.saved, 'success');
                $('#prodimg-seo-modal-overlay').fadeOut();

                if (prodimgModalCtx.mode === 'attachment' && prodimgModalCtx.$row) {
                    // Update the single row in place — no full reload.
                    var saved = (response.data && response.data.saved) ? response.data.saved : {};
                    var rowData = saved[prodimgModalCtx.attachmentId] || {};
                    prodimgUpdateRow(prodimgModalCtx.$row, {
                        score: rowData.score,
                        band: rowData.band,
                        explanation: rowData.explanation,
                        alt_text: altTexts[prodimgModalCtx.attachmentId]
                    });
                } else {
                    // Product-level save affects many rows — reload for accuracy.
                    location.reload();
                }
            } else {
                prodimgToast(prodimgI18n.saveError + ' ' + response.data, 'error');
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
