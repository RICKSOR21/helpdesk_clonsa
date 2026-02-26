console.log('📋 ULTIMOS TICKETS v6 - CON FILTRO DEPARTAMENTO');

$(document).ready(function() {

    function cargarUltimosTickets() {
        console.log('📋 Cargando últimos tickets...');

        // Obtener departamento seleccionado
        var departamento = window.departamentoActual || 'all';

        $.ajax({
            url: 'api/ultimos_tickets.php',
            method: 'GET',
            data: {
                limit: 10,
                departamento: departamento
            },
            dataType: 'json',
            success: function(response) {
                console.log('✅ Últimos tickets recibidos:', response);
                renderizarUltimosTickets(response.tickets || []);
            },
            error: function(xhr, status, error) {
                console.error('❌ Error al cargar últimos tickets:', error);
                $('#ultimosTicketsContainer').html(
                    '<div class="text-center py-3 text-muted">' +
                    '<i class="mdi mdi-alert-circle"></i> Error al cargar' +
                    '</div>'
                );
            }
        });
    }

    function acortarActividad(actividad) {
        if (!actividad) return 'Sin actividad';
        return actividad.replace(/Mantenimiento/gi, 'Mantto');
    }

    function renderizarUltimosTickets(tickets) {
        const container = $('#ultimosTicketsContainer');

        if (tickets.length === 0) {
            container.html(
                '<div class="text-center py-3 text-muted">' +
                '<i class="mdi mdi-ticket-outline"></i> No hay tickets recientes' +
                '</div>'
            );
            return;
        }

        let html = '';

        tickets.forEach(function(ticket, index) {
            const borderClass = index < tickets.length - 1 ? 'border-bottom' : '';
            const actividadCorta = acortarActividad(ticket.actividad);

            html += '<div class="d-flex align-items-start ' + borderClass + ' py-2">' +
                '<div class="me-2 mt-1">' +
                    '<i class="mdi mdi-ticket-confirmation-outline text-primary" style="font-size: 16px;"></i>' +
                '</div>' +
                '<div class="flex-grow-1">' +
                    '<p class="mb-1 font-weight-medium" style="font-size: 12px; line-height: 1.3;">' +
                        '<span class="text-dark fw-bold">' + ticket.usuario + '</span>' +
                        '<i class="mdi mdi-arrow-right text-muted mx-1" style="font-size: 11px;"></i>' +
                        '<span class="text-primary">' + actividadCorta + '</span>' +
                        '<i class="mdi mdi-arrow-right text-muted mx-1" style="font-size: 11px;"></i>' +
                        '<span class="text-info">' + ticket.departamento + '</span>' +
                    '</p>' +
                    '<div class="d-flex align-items-center">' +
                        '<i class="mdi mdi-clock-outline text-muted me-1" style="font-size: 11px;"></i>' +
                        '<small class="text-muted" style="font-size: 10px;">' + ticket.fecha + ' - ' + ticket.hora + '</small>' +
                    '</div>' +
                '</div>' +
            '</div>';
        });

        container.html(html);
        console.log('✅ Últimos tickets renderizados:', tickets.length);
    }

    // Cargar inicialmente
    setTimeout(function() {
        cargarUltimosTickets();
    }, 500);

    // Recargar cuando cambie el filtro de departamento
    $(document).on('dashboardFiltersChanged', function() {
        cargarUltimosTickets();
    });

    // Exponer función para llamarla desde dashboard-filters.js
    window.cargarUltimosTickets = cargarUltimosTickets;

});
