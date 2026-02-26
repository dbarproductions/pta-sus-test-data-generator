/* PTA SUS Test Data Generator — Admin JS */
(function ($) {
    'use strict';

    // -----------------------------------------------------------------------
    // Tab switching
    // -----------------------------------------------------------------------
    function activateTab(tabId) {
        $('.ptg-tab-nav a').removeClass('ptg-tab-active');
        $('.ptg-tab-panel').removeClass('ptg-panel-active');

        $('.ptg-tab-nav a[data-tab="' + tabId + '"]').addClass('ptg-tab-active');
        $('#ptg-panel-' + tabId).addClass('ptg-panel-active');

        // Update URL hash without scroll jump.
        if (history.replaceState) {
            history.replaceState(null, null, '#tab-' + tabId);
        }
    }

    // Activate tab from hash on page load.
    var hash = window.location.hash;
    if (hash && hash.indexOf('#tab-') === 0) {
        var tabFromHash = hash.replace('#tab-', '');
        if ($('.ptg-tab-nav a[data-tab="' + tabFromHash + '"]').length) {
            activateTab(tabFromHash);
        }
    }

    $(document).on('click', '.ptg-tab-nav a', function (e) {
        e.preventDefault();
        activateTab($(this).data('tab'));
    });

    // -----------------------------------------------------------------------
    // Fill rate slider ↔ number sync
    // -----------------------------------------------------------------------
    $(document).on('input change', '#ptg-fill-range', function () {
        var val = $(this).val();
        $('#ptg-fill-number').val(val);
        $('#ptg-fill-display').text(val + '%');
    });

    $(document).on('input change', '#ptg-fill-number', function () {
        var val = Math.min(100, Math.max(0, parseInt($(this).val(), 10) || 0));
        $('#ptg-fill-range').val(val);
        $('#ptg-fill-display').text(val + '%');
    });

    // -----------------------------------------------------------------------
    // User mix: keep guest % = 100 - user %
    // -----------------------------------------------------------------------
    $(document).on('input change', '#ptg-user-pct', function () {
        var val = Math.min(100, Math.max(0, parseInt($(this).val(), 10) || 0));
        $('#ptg-guest-pct').text(100 - val);
    });

    // -----------------------------------------------------------------------
    // Sheet type select: grey out when preset != random
    // -----------------------------------------------------------------------
    $(document).on('change', '#ptg-preset', function () {
        var isRandom = $(this).val() === 'random';
        $('#ptg-sheet-type').prop('disabled', !isRandom);
        if (!isRandom) {
            $('#ptg-sheet-type').val('');
        }
    });
    // Run on load.
    if ($('#ptg-preset').length) {
        var isRandom = $('#ptg-preset').val() === 'random';
        $('#ptg-sheet-type').prop('disabled', !isRandom);
    }

    // -----------------------------------------------------------------------
    // Delete ALL confirmation dialog
    // -----------------------------------------------------------------------
    $(document).on('click', '#ptg-delete-all-btn', function (e) {
        if (!confirm('Are you sure you want to permanently delete ALL generated test users, sheets, tasks, and signups? This cannot be undone.')) {
            e.preventDefault();
        }
    });

    // -----------------------------------------------------------------------
    // Individual delete confirmations
    // -----------------------------------------------------------------------
    $(document).on('click', '.ptg-delete-confirm', function (e) {
        var msg = $(this).data('confirm') || 'Are you sure you want to delete this data?';
        if (!confirm(msg)) {
            e.preventDefault();
        }
    });

}(jQuery));
