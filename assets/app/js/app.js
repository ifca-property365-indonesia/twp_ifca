/* ------------------------------------------------------------------
 * TWP portal - script bersama (dimuat di <head> layout setelah jQuery,
 * CoreUI, DataTables, Select2, SweetAlert2, bootstrap-datepicker).
 * ------------------------------------------------------------------ */
(function ($) {
    'use strict';

    // CSRF untuk semua request AJAX jQuery
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    // Select2: tema bootstrap-5 & lebar penuh untuk semua pemanggilan .select2()
    if ($.fn.select2) {
        $.fn.select2.defaults.set('theme', 'bootstrap-5');
        $.fn.select2.defaults.set('width', '100%');
    }

    // DataTables: teks bahasa & jarak
    if ($.fn.dataTable) {
        $.extend(true, $.fn.dataTable.defaults, {
            language: {
                search: '',
                searchPlaceholder: 'Search...',
                lengthMenu: 'Show _MENU_',
                info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                infoEmpty: 'No entries',
                infoFiltered: '(filtered from _MAX_ total)',
                emptyTable: 'No data available',
                zeroRecords: 'No matching records found',
                processing: 'Loading...'
            }
        });
    }

    // Select2 di dalam modal: dropdown harus menempel ke modal (focus trap CoreUI)
    if ($.fn.select2) {
        var origSelect2 = $.fn.select2;
        $.fn.select2 = function (opts) {
            if (arguments.length === 0 || (opts && typeof opts === 'object')) {
                return this.each(function () {
                    var o = $.extend({}, opts || {});
                    var $modal = $(this).closest('.modal');
                    if ($modal.length && !o.dropdownParent) {
                        o.dropdownParent = $modal;
                    }
                    origSelect2.call($(this), o);
                });
            }
            return origSelect2.apply(this, arguments);
        };
        $.extend($.fn.select2, origSelect2);
    }

    // Datepicker: <input class="date-picker" data-date-format="dd/mm/yyyy">
    // Dipanggil saat halaman siap dan setiap ada elemen baru (isi modal via AJAX).
    function initDatepickers() {
        if (!$.fn.datepicker) {
            return;
        }
        $('.date-picker').each(function () {
            if ($(this).data('datepicker')) {
                return;
            }
            $(this).datepicker({
                format: $(this).data('date-format') || 'dd/mm/yyyy',
                autoclose: true,
                todayHighlight: true,
                orientation: 'bottom auto'
            });
        });
    }
    window.initDatepickers = initDatepickers;

    // Dropdown filter bergaya Select2 (sama dengan Ticket / Overtime): <select class="js-select">.
    // Tanpa kotak cari, kecuali diberi data-search="true" (daftar panjang);
    // data-dropdown-class="..." menambah class pada panel dropdown.
    // Opsi yang diisi lewat JS setelah halaman siap: panggil .trigger('change.select2') sesudahnya.
    function initSelects() {
        if (!$.fn.select2) {
            return;
        }
        $('select.js-select').each(function () {
            if ($(this).hasClass('select2-hidden-accessible')) {
                return;
            }
            $(this).select2({
                minimumResultsForSearch: $(this).data('search') ? 0 : Infinity,
                dropdownAutoWidth: true,
                dropdownCssClass: $(this).data('dropdown-class') || ''
            });
        });
    }
    window.initSelects = initSelects;

    $(function () {
        initSelects();
        initDatepickers();

        if (window.MutationObserver) {
            var pending = null;
            new MutationObserver(function () {
                clearTimeout(pending);
                pending = setTimeout(initDatepickers, 50);
            }).observe(document.body, { childList: true, subtree: true });
        }

        // Tooltip CoreUI
        $('[data-coreui-toggle="tooltip"]').each(function () {
            new coreui.Tooltip(this);
        });
    });

    // ------------------------------------------------------------------
    // Helper global yang dipakai view lama (dulu ada di header admin)
    // ------------------------------------------------------------------

    /** block(true, '#el') / block(false, '#el'): overlay "Loading..." di dalam elemen. */
    window.block = function (on, div) {
        var $el = $(div);
        if (!$el.length) {
            return;
        }
        if (on) {
            if ($el.css('position') === 'static') {
                $el.css('position', 'relative');
            }
            if (!$el.children('.block-overlay').length) {
                $el.append(
                    '<div class="block-overlay"><div class="block-msg">' +
                    '<span class="spinner-border spinner-border-sm" role="status"></span> Loading ...' +
                    '</div></div>'
                );
            }
        } else {
            $el.children('.block-overlay').remove();
        }
    };

    function pad2(n) {
        return n < 10 ? '0' + n : String(n);
    }

    /** 'yyyy-mm-dd hh:mm:ss' -> 'dd-mm-yyyy' */
    window.FormatDateNew = function (data) {
        if (data == null || data === '') {
            return 'Not Set';
        }
        var d = new Date(String(data).replace(/\s/, 'T'));
        if (isNaN(d.getTime())) {
            return String(data);
        }
        return pad2(d.getDate()) + '-' + pad2(d.getMonth() + 1) + '-' + d.getFullYear();
    };

    /** 'yyyy-mm-dd hh:mm:ss' -> 'dd-mm-yyyy hh:mm' */
    window.FormatDateTimeNew = function (data) {
        if (data == null || data === '') {
            return 'Not Set';
        }
        var d = new Date(String(data).replace(/\s/, 'T'));
        if (isNaN(d.getTime())) {
            return String(data);
        }
        return pad2(d.getDate()) + '-' + pad2(d.getMonth() + 1) + '-' + d.getFullYear() +
            ' ' + pad2(d.getHours()) + ':' + pad2(d.getMinutes());
    };

    /** Escape HTML untuk render DataTables / template string. */
    window.escapeHtml = function (s) {
        return $('<div>').text(s == null ? '' : String(s)).html();
    };
})(jQuery);
