/* ============================================================
   ePalmHaul — shared DataTables initializer
   Used by the admin, farmer, driver and weighbridge AJAX shells.
   Page partials are injected into #page_result and re-fetched on
   every navigation, so tables are (re)bound after each swap and
   destroyed before the next one to avoid leaking instances.
   Not a framework file — safe to edit.
   ============================================================ */
(function (window, $) {
    'use strict';

    // DataTables core must be present (loaded via CDN in each shell).
    function ready() {
        return !!(window.jQuery && $.fn && $.fn.dataTable);
    }

    var BASE_OPTS = {
        pageLength: 10,
        lengthChange: false,
        autoWidth: false,
        order: [],                 // keep server-rendered order
        language: {
            search: '',
            searchPlaceholder: 'Filter rows…',
            infoFiltered: '(filtered from _MAX_ total)',
            zeroRecords: 'No rows match your filter.',
            infoEmpty: ''
        }
    };

    // A table that is only a placeholder ("No records yet" spanning row) or
    // has no header cells must not be initialised — DataTables would warn
    // about the colspan mismatch and there is nothing to sort.
    function skipTable($t) {
        if ($t.hasClass('no-dt')) return true;
        if ($t.closest('.modal').length) return true;          // micro-tables inside dialogs
        var $th = $t.find('thead th');
        if (!$th.length) return true;                            // no header to derive columns from
        var $rows = $t.children('tbody').children('tr');
        if ($rows.length === 1) {
            var $td = $rows.first().children('td');
            if ($td.length === 1 && $td.attr('colspan')) return true;  // empty-state row
        }
        return false;
    }

    window.dtDestroy = function (scope) {
        if (!ready()) return;
        $(scope).find('table').each(function () {
            if ($.fn.dataTable.isDataTable(this)) {
                $(this).DataTable().destroy(true);
            }
        });
    };

    window.dtApply = function (scope) {
        if (!ready()) return;
        $(scope).find('table').each(function () {
            var t = this, $t = $(t);
            if (skipTable($t)) return;
            if ($.fn.dataTable.isDataTable(t)) return;          // already bound

            var opts = $.extend({}, BASE_OPTS);
            var external = $t.attr('data-search');             // optional custom filter input
            if (external) opts.dom = 'rtip';                   // hide built-in filter, use the page's own box

            var api = $t.DataTable(opts);

            if (external) {
                $(external)
                    .off('input.dtsearch')
                    .on('input.dtsearch', function () {
                        api.search($(this).val()).draw();
                    });
            }
        });
    };
})(window, window.jQuery);
