$(document).ready(function() {
    if (!$.fn.DataTable.isDataTable('#tablaBitacora')) {
        $('#tablaBitacora').DataTable({
            language: {
                "url": "assets/public/js/es-ES.json"
            },
            responsive: true,
            order: [[0, 'desc']],
            pageLength: 25
        });
    }
});
