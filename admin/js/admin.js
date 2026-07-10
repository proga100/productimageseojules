/**
 * prodimgToast — HUD notification helper.
 *
 * @param {string} msg  Message text.
 * @param {string} type 'success' | 'error' | 'info'  (default 'success')
 */
function prodimgToast( msg, type ) {
    type = type || 'success';
    var $t = jQuery('<div class="prodimg-toast prodimg-app prodimg-toast--' + type + '"></div>').text( msg );
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
                        // Refresh so cards/charts reflect the new scan data.
                        setTimeout(function() { location.reload(); }, 1200);
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

    // Skeleton shown while the AI request is in flight.
    function prodimgRenderSkeleton() {
        var html = '<div class="prodimg-modal-status" role="status">'
            + '<span class="prodimg-spinner" aria-hidden="true"></span>'
            + '<span><strong>' + prodimgEscHtml(prodimgI18n.genStatus) + '</strong><br>'
            + '<span class="prodimg-text-secondary">' + prodimgEscHtml(prodimgI18n.genHint) + '</span></span>'
            + '</div>';
        html += '<div class="prodimg-skeleton-card" aria-hidden="true">'
            + '<div class="prodimg-skeleton prodimg-skeleton--thumb"></div>'
            + '<div class="prodimg-skeleton-lines">'
            + '<div class="prodimg-skeleton prodimg-skeleton--chip"></div>'
            + '<div class="prodimg-skeleton prodimg-skeleton--line"></div>'
            + '<div class="prodimg-skeleton prodimg-skeleton--line" style="width:70%;"></div>'
            + '<div class="prodimg-skeleton prodimg-skeleton--block"></div>'
            + '</div></div>';
        return html;
    }

    function prodimgRenderError(message) {
        return '<div class="prodimg-modal-error">'
            + '<div class="prodimg-modal-error__icon" aria-hidden="true">!</div>'
            + '<p class="prodimg-modal-error__msg">' + prodimgEscHtml(message) + '</p>'
            + '<button type="button" class="button" id="prodimg-seo-modal-retry">' + prodimgEscHtml(prodimgI18n.retry) + '</button>'
            + '</div>';
    }

    function prodimgRoleLabel(role) {
        var roles = prodimgI18n.roles || {};
        return roles[role] || role;
    }

    function prodimgRenderSuggestions(suggestions) {
        var maxLen = parseInt(prodimg_seo_1972adm_admin.max_length, 10) || 125;
        var html = '';
        $.each(suggestions, function(i, item) {
            var current   = $.trim(item.current_alt || '');
            var suggested = String(item.alt_text || '');
            var fieldId   = 'prodimg-alt-input-' + prodimgEscAttr(item.image_id);
            html += '<div class="prodimg-suggestion-card">';
            html += '<div class="prodimg-suggestion-card__media"><img src="' + prodimgEscAttr(item.url) + '" alt="" /></div>';
            html += '<div class="prodimg-suggestion-card__body">';
            html += '<div class="prodimg-suggestion-card__meta">'
                + '<span class="prodimg-status-badge prodimg-status-badge--role-' + prodimgEscAttr(item.role) + '">'
                + prodimgEscHtml(prodimgRoleLabel(item.role)) + '</span>'
                + '<span class="prodimg-suggestion-card__score">' + prodimgEscHtml(prodimgI18n.aiScore)
                + ': <strong>' + prodimgEscHtml(item.score) + '</strong></span>'
                + '</div>';
            html += '<div class="prodimg-suggestion-card__current">'
                + '<span class="prodimg-suggestion-card__label">' + prodimgEscHtml(prodimgI18n.currentAlt) + '</span>'
                + (current === ''
                    ? '<em class="prodimg-text-secondary">' + prodimgEscHtml(prodimgI18n.noneLabel) + '</em>'
                    : '<span class="prodimg-suggestion-card__current-text">' + prodimgEscHtml(current) + '</span>')
                + '</div>';
            html += '<label class="prodimg-suggestion-card__label" for="' + fieldId + '">'
                + prodimgEscHtml(prodimgI18n.suggestedAlt) + '</label>';
            html += '<textarea id="' + fieldId + '" class="prodimg-seo-alt-input" rows="3" data-image-id="'
                + prodimgEscAttr(item.image_id) + '">' + prodimgEscHtml(suggested) + '</textarea>';
            html += '<div class="prodimg-char-counter" data-max="' + maxLen + '"><span>'
                + suggested.length + '</span>&thinsp;/&thinsp;' + maxLen + '</div>';
            html += '</div></div>';
        });
        return html;
    }

    // Brief green flash so the eye lands on the row that just changed.
    function prodimgFlashRow($row) {
        if (!$row || !$row.length) { return; }
        $row.addClass('prodimg-row-flash');
        setTimeout(function() { $row.removeClass('prodimg-row-flash'); }, 1600);
    }

    function prodimgCloseModal() {
        $('#prodimg-seo-modal-overlay').fadeOut(150);
        if (prodimgModalCtx.$btn && prodimgModalCtx.$btn.length) {
            prodimgModalCtx.$btn.prop('disabled', false).text(prodimgModalCtx.btnLabel);
            prodimgModalCtx.$btn.trigger('focus');
        }
    }

    // Open the modal and request suggestions for the given payload/context.
    function prodimgOpenGenerate(payload, ctx) {
        prodimgModalCtx = ctx;
        prodimgModalCtx.payload = payload;

        $('#prodimg-seo-modal-overlay').fadeIn(150);
        $('#prodimg-seo-modal-content').html(prodimgRenderSkeleton());
        $('#prodimg-seo-modal-save').hide();
        $('#prodimg-seo-modal-regenerate').hide();

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
                $('#prodimg-seo-modal-regenerate').show().prop('disabled', false).text(prodimgI18n.regenerate);
                var $first = $('#prodimg-seo-modal-content .prodimg-seo-alt-input').first();
                if ($first.length) {
                    var el = $first.get(0);
                    $first.trigger('focus');
                    el.setSelectionRange(el.value.length, el.value.length);
                }
            } else {
                var msg = (typeof response.data === 'string' && response.data !== '')
                    ? response.data
                    : prodimgI18n.genFailed;
                $('#prodimg-seo-modal-content').html(prodimgRenderError(msg));
                prodimgToast(msg, 'error');
            }
        }).fail(function() {
            if (ctx.$btn && ctx.$btn.length) {
                ctx.$btn.prop('disabled', false).text(ctx.btnLabel);
            }
            $('#prodimg-seo-modal-content').html(prodimgRenderError(prodimgI18n.genFailed));
            prodimgToast(prodimgI18n.genFailed, 'error');
        });
    }

    // Live character counter under each suggestion textarea.
    $(document).on('input', '.prodimg-seo-alt-input', function() {
        var $ta = $(this);
        var $counter = $ta.closest('.prodimg-suggestion-card__body').find('.prodimg-char-counter');
        if (!$counter.length) { return; }
        var max = parseInt($counter.data('max'), 10) || 125;
        var len = $ta.val().length;
        $counter.find('span').text(len);
        $counter.toggleClass('is-warn', len > max * 0.9 && len <= max);
        $counter.toggleClass('is-over', len > max);
    });

    // Retry after a failed generation (button lives inside the error state).
    $(document).on('click', '#prodimg-seo-modal-retry', function() {
        if (prodimgModalCtx.payload) {
            prodimgOpenGenerate(prodimgModalCtx.payload, prodimgModalCtx);
        }
    });

    // Regenerate suggestions for the same target.
    $('#prodimg-seo-modal-regenerate').on('click', function() {
        if (prodimgModalCtx.payload) {
            $(this).prop('disabled', true).text(prodimgI18n.regenerating);
            prodimgOpenGenerate(prodimgModalCtx.payload, prodimgModalCtx);
        }
    });

    // Close on overlay click (but not on clicks inside the dialog).
    $('#prodimg-seo-modal-overlay').on('click', function(e) {
        if (e.target === this) {
            prodimgCloseModal();
        }
    });

    // Close on Escape.
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $('#prodimg-seo-modal-overlay').is(':visible')) {
            prodimgCloseModal();
        }
    });

    // Keep Tab focus inside the dialog while it is open.
    $('#prodimg-seo-modal').on('keydown', function(e) {
        if (e.key !== 'Tab') { return; }
        var $focusable = $(this).find('button:visible, textarea:visible, a[href]:visible').filter(':not(:disabled)');
        if (!$focusable.length) { return; }
        var first = $focusable.get(0);
        var last  = $focusable.get($focusable.length - 1);
        if (e.shiftKey && document.activeElement === first) {
            e.preventDefault();
            last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
            e.preventDefault();
            first.focus();
        }
    });

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
        if (prodimgBulkActive) { return; }
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
        if (prodimgBulkActive) { return; }
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
                prodimgFlashRow($row);
                prodimgToast(prodimgI18n.scoreUpdated, 'success');
            } else {
                prodimgToast(prodimgI18n.error + ' ' + response.data, 'error');
            }
        }).fail(function() {
            $btn.prop('disabled', false).text(label);
            prodimgToast(prodimgI18n.error, 'error');
        });
    });

    // --- Catalog bulk actions (Generate / Recalculate) -----------------------
    // Run client-side, one image at a time, so every row updates in place and
    // failures don't abort the batch. A sticky banner above the table shows
    // live progress and lets the user cancel between items.

    var prodimgBulkActive = false;

    function prodimgBulkBanner(title, total) {
        var html = '<div class="prodimg-bulkrun" role="status" aria-live="polite">'
            + '<span class="prodimg-spinner" aria-hidden="true"></span>'
            + '<div class="prodimg-bulkrun__body">'
            + '<strong class="prodimg-bulkrun__title">' + prodimgEscHtml(title) + '</strong>'
            + '<div class="prodimg-progress"><div class="prodimg-progress__bar" style="width:0%;"></div></div>'
            + '<span class="prodimg-bulkrun__meta">'
            + prodimgEscHtml(prodimgI18n.bulkMeta.replace('%1$s', 0).replace('%2$s', total).replace('%3$s', 0).replace('%4$s', 0))
            + '</span>'
            + '</div>'
            + '<button type="button" class="button prodimg-bulkrun__cancel">' + prodimgEscHtml(prodimgI18n.cancel) + '</button>'
            + '</div>';
        var $banner = $(html);
        $('form').has('input[name="attachment_ids[]"]').first().before($banner);
        if ($banner.get(0).scrollIntoView) {
            $banner.get(0).scrollIntoView({ block: 'nearest' });
        }
        return $banner;
    }

    function prodimgRunBulk(action, ids) {
        var total     = ids.length;
        var done      = 0;
        var ok        = 0;
        var failed    = 0;
        var cancelled = false;
        var $apply    = $('#doaction, #doaction2');
        var origVal   = $apply.first().val();

        prodimgBulkActive = true;
        var title   = ('generate' === action) ? prodimgI18n.bulkGenTitle : prodimgI18n.bulkRecalcTitle;
        var $banner = prodimgBulkBanner(title, total);
        $banner.find('.prodimg-bulkrun__cancel').on('click', function() {
            cancelled = true;
            $(this).prop('disabled', true);
        });

        function progress() {
            $apply.prop('disabled', true).val(
                prodimgI18n.bulkProgress.replace('%1$s', done).replace('%2$s', total)
            );
            $banner.find('.prodimg-progress__bar').css('width', Math.round((done / total) * 100) + '%');
            $banner.find('.prodimg-bulkrun__meta').text(
                prodimgI18n.bulkMeta.replace('%1$s', done).replace('%2$s', total).replace('%3$s', ok).replace('%4$s', failed)
            );
        }

        function finish() {
            prodimgBulkActive = false;
            $apply.prop('disabled', false).val(origVal);
            var tpl     = ('generate' === action) ? prodimgI18n.bulkGenerateDone : prodimgI18n.bulkRecalcDone;
            var summary = cancelled
                ? prodimgI18n.bulkCancelled + ' ' + tpl.replace('%1$s', ok).replace('%2$s', failed)
                : tpl.replace('%1$s', ok).replace('%2$s', failed);
            $banner.find('.prodimg-spinner').remove();
            $banner.find('.prodimg-bulkrun__title').text(summary);
            $banner.find('.prodimg-bulkrun__meta').text('');
            $banner.find('.prodimg-bulkrun__cancel')
                .prop('disabled', false)
                .text(prodimgI18n.close)
                .off('click')
                .on('click', function() { $banner.remove(); });
            prodimgToast(summary, (failed || cancelled) ? 'info' : 'success');
        }

        function step() {
            if (!ids.length || cancelled) { progress(); finish(); return; }
            var id = ids.shift();
            done++;
            progress();
            var $row = $('input[name="attachment_ids[]"][value="' + id + '"]').closest('tr');
            $row.addClass('prodimg-row-busy');

            function next() {
                $row.removeClass('prodimg-row-busy');
                step();
            }

            if ('recalc' === action) {
                $.post(prodimg_seo_1972adm_admin.ajax_url, {
                    action: 'prodimg_seo_1972adm_recalc_score',
                    nonce: prodimg_seo_1972adm_admin.nonce,
                    attachment_id: id
                }, function(response) {
                    if (response.success) {
                        ok++;
                        prodimgUpdateRow($row, {
                            score: response.data.score,
                            band: response.data.band,
                            explanation: response.data.explanation
                        });
                        prodimgFlashRow($row);
                        $row.find('input[name="attachment_ids[]"]').prop('checked', false);
                    } else {
                        failed++;
                    }
                    next();
                }).fail(function() { failed++; next(); });
                return;
            }

            // generate: fetch the AI suggestion, then apply it right away.
            $.post(prodimg_seo_1972adm_admin.ajax_url, {
                action: 'prodimg_seo_1972adm_generate_single',
                nonce: prodimg_seo_1972adm_admin.nonce,
                attachment_id: id
            }, function(response) {
                var suggestion = (response.success && response.data && response.data.suggestions && response.data.suggestions.length)
                    ? response.data.suggestions[0]
                    : null;
                if (!suggestion || !suggestion.alt_text) {
                    failed++;
                    next();
                    return;
                }
                var altTexts = {};
                altTexts[id] = suggestion.alt_text;
                $.post(prodimg_seo_1972adm_admin.ajax_url, {
                    action: 'prodimg_seo_1972adm_save_single',
                    nonce: prodimg_seo_1972adm_admin.nonce,
                    product_id: response.data.product_id || 0,
                    alt_texts: altTexts
                }, function(saveResponse) {
                    if (saveResponse.success) {
                        ok++;
                        var saved   = (saveResponse.data && saveResponse.data.saved) ? saveResponse.data.saved : {};
                        var rowData = saved[id] || {};
                        prodimgUpdateRow($row, {
                            score: rowData.score,
                            band: rowData.band,
                            explanation: rowData.explanation,
                            alt_text: suggestion.alt_text
                        });
                        prodimgFlashRow($row);
                        $row.find('input[name="attachment_ids[]"]').prop('checked', false);
                    } else {
                        failed++;
                    }
                    next();
                }).fail(function() { failed++; next(); });
            }).fail(function() { failed++; next(); });
        }

        step();
    }

    $(document).on('submit', 'form', function(e) {
        var $form = $(this);
        if (!$form.find('input[name="attachment_ids[]"]').length) {
            return;
        }
        var action = $form.find('select[name="action"]').val();
        if (!action || '-1' === action) {
            action = $form.find('select[name="action2"]').val();
        }
        if ('generate' !== action && 'recalc' !== action) {
            return;
        }
        e.preventDefault();

        var ids = $form.find('input[name="attachment_ids[]"]:checked').map(function() {
            return parseInt($(this).val(), 10);
        }).get();

        if (!ids.length) {
            prodimgToast(prodimgI18n.bulkNoSelection, 'info');
            return;
        }
        prodimgRunBulk(action, ids);
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

        $btn.prop('disabled', true).addClass('prodimg-is-busy').text(prodimgI18n.saving);

        $.post(prodimg_seo_1972adm_admin.ajax_url, {
            action: 'prodimg_seo_1972adm_save_single',
            nonce: prodimg_seo_1972adm_admin.nonce,
            product_id: prodimgModalCtx.productId || 0,
            alt_texts: altTexts
        }, function(response) {
            $btn.prop('disabled', false).removeClass('prodimg-is-busy').text(prodimgI18n.save);
            if (response.success) {
                prodimgToast(prodimgI18n.saved, 'success');
                prodimgCloseModal();

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
                    prodimgFlashRow(prodimgModalCtx.$row);
                } else {
                    // Product-level save affects many rows — reload for accuracy.
                    location.reload();
                }
            } else {
                prodimgToast(prodimgI18n.saveError + ' ' + response.data, 'error');
            }
        }).fail(function() {
            $btn.prop('disabled', false).removeClass('prodimg-is-busy').text(prodimgI18n.save);
            prodimgToast(prodimgI18n.saveError + ' ' + prodimgI18n.genFailed, 'error');
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

    // Bulk-fix completion summary card — reports images actually processed
    // (generated / skipped / failed), not products iterated.
    $(document).on('prodimg-seo:bulk-completed', function(e, data) {
        var i18n = (prodimg_seo_1972adm_admin && prodimg_seo_1972adm_admin.i18n) || {};
        data = data || {};
        var generated = parseInt(data.images_generated, 10) || 0;
        var skipped   = parseInt(data.images_skipped, 10) || 0;
        var failed    = parseInt(data.images_failed, 10) || 0;

        var parts = [i18n.bulkFixDone || 'Bulk fix finished.'];
        if (generated) {
            parts.push((i18n.bulkFixGenerated || 'Generated alt text for %s images.').replace('%s', generated));
        }
        if (skipped) {
            parts.push((i18n.bulkFixSkipped || 'Skipped %s images that already had alt text.').replace('%s', skipped));
        }
        if (failed) {
            parts.push((i18n.bulkFixFailed || '%s images could not be generated.').replace('%s', failed));
        }
        if (!generated && !failed) {
            parts.push(i18n.bulkFixNothing || 'No images needed alt text.');
        }
        var msg = parts.join(' ');

        $('#prodimg-seo-bulk-results-body').text(msg);
        $('#prodimg-seo-bulk-results').removeAttr('hidden');
        prodimgToast(msg, failed ? 'info' : 'success');
    });
});
