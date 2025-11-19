$(document).ready(function() {
    if (!$.fn.DataTable.isDataTable('#tablaBitacora')) {
        $('#tablaBitacora').DataTable({
            language: {
                "url": "assets/public/js/es-ES.json"
            },
            responsive: true,
            scrollX: true,
            scrollCollapse: true,
            autoWidth: false,
            columnDefs: [
                { targets: 0, width: '6%'  }, // ID
                { targets: 1, width: '18%' }, // Fecha y Hora
                { targets: 2, width: '12%' }, // Usuario
                { targets: 3, width: '16%' }, // Acción
                { targets: 4, width: '18%' }, // Módulo
                { targets: 5, width: '30%' }  // Descripción
            ],
            order: [[0, 'desc']],
            pageLength: 25,
            initComplete: function () {
                var api = this.api();
                var $wrapper = $(api.table().container());

                api.columns.adjust();

                var $filter = $wrapper.find('.dataTables_filter');
                if (!$filter.length) return;
            }
        });
    }
});
