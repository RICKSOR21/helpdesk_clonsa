/**
 * Dashboard Export - Share, Print, Export funcionalidades
 * v1.0
 */

console.log('📤 Dashboard Export v1.0 cargando...');

$(document).ready(function() {

    // ============================================
    // VARIABLES GLOBALES
    // ============================================
    let currentPeriod = 'week'; // Por defecto última semana
    let dashboardScreenshot = null;

    // Detectar período activo
    function detectarPeriodo() {
        if ($('#overview-tab').hasClass('active') || $('#overview').hasClass('active')) {
            return 'week';
        } else if ($('#profile-tab').hasClass('active') || $('#audiences').hasClass('active')) {
            return 'month';
        } else if ($('#contact-tab').hasClass('active') || $('#demographics').hasClass('active')) {
            return 'year';
        } else if ($('#more-tab').hasClass('active') || $('#more').hasClass('active')) {
            return 'custom';
        }
        return 'week';
    }

    // ============================================
    // SHARE - Captura y compartir
    // ============================================
    $(document).on('click', '.btn-share', async function(e) {
        e.preventDefault();

        const btn = $(this);
        const originalHtml = btn.html();
        btn.html('<i class="mdi mdi-loading mdi-spin"></i> Capturando...');
        btn.prop('disabled', true);

        try {
            // Capturar solo el contenido principal del dashboard
            const mainPanel = document.querySelector('.main-panel');

            if (!mainPanel) {
                throw new Error('No se encontró el panel principal');
            }

            // Usar html2canvas para capturar
            const canvas = await html2canvas(mainPanel, {
                scale: 2,
                useCORS: true,
                allowTaint: true,
                backgroundColor: '#f4f5f7',
                logging: false,
                scrollX: 0,
                scrollY: -window.scrollY,
                windowWidth: document.documentElement.offsetWidth,
                windowHeight: document.documentElement.offsetHeight
            });

            dashboardScreenshot = canvas.toDataURL('image/png');

            // Mostrar modal de opciones para compartir
            mostrarModalCompartir(dashboardScreenshot);

        } catch (error) {
            console.error('Error al capturar:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo capturar el dashboard',
                timer: 3000
            });
        } finally {
            btn.html(originalHtml);
            btn.prop('disabled', false);
        }
    });

    function mostrarModalCompartir(imageData) {
        // Crear modal de compartir
        const modalHtml = `
        <div class="modal fade" id="shareModal" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title"><i class="mdi mdi-share-variant text-primary"></i> Compartir Dashboard</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="text-center mb-4">
                            <img src="${imageData}" class="img-fluid rounded shadow-sm" style="max-height: 300px; border: 1px solid #eee;" alt="Preview">
                        </div>
                        <div class="d-flex justify-content-center gap-3 flex-wrap">
                            <button class="btn btn-success btn-lg px-4" id="shareWhatsApp">
                                <i class="mdi mdi-whatsapp me-2"></i>WhatsApp
                            </button>
                            <button class="btn btn-primary btn-lg px-4" id="shareEmail">
                                <i class="mdi mdi-email me-2"></i>Email
                            </button>
                            <button class="btn btn-secondary btn-lg px-4" id="downloadImage">
                                <i class="mdi mdi-download me-2"></i>Descargar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>`;

        // Remover modal existente si hay
        $('#shareModal').remove();
        $('body').append(modalHtml);

        const modal = new bootstrap.Modal(document.getElementById('shareModal'));
        modal.show();

        // Eventos de botones
        $('#shareWhatsApp').on('click', function() {
            // WhatsApp Web no soporta imágenes directas, descargar primero
            descargarImagen(imageData, 'dashboard-helpdesk.png');

            // Abrir WhatsApp Web
            const texto = encodeURIComponent('📊 Reporte del Dashboard - HelpDesk CLONSA\n\n(Imagen adjunta descargada)');
            window.open(`https://web.whatsapp.com/send?text=${texto}`, '_blank');

            modal.hide();
        });

        $('#shareEmail').on('click', function() {
            // Descargar imagen primero
            descargarImagen(imageData, 'dashboard-helpdesk.png');

            // Abrir cliente de correo
            const asunto = encodeURIComponent('Reporte Dashboard - HelpDesk CLONSA');
            const cuerpo = encodeURIComponent('Adjunto el reporte del dashboard.\n\n(La imagen ha sido descargada a tu computadora para adjuntarla)');
            window.location.href = `mailto:?subject=${asunto}&body=${cuerpo}`;

            modal.hide();
        });

        $('#downloadImage').on('click', function() {
            descargarImagen(imageData, 'dashboard-helpdesk.png');
            modal.hide();
        });
    }

    function descargarImagen(dataUrl, filename) {
        const link = document.createElement('a');
        link.href = dataUrl;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // ============================================
    // PRINT - Imprimir dashboard
    // ============================================
    $(document).on('click', '.btn-print', function(e) {
        e.preventDefault();

        // Agregar clase para ocultar sidebar al imprimir
        $('body').addClass('printing-dashboard');

        // Configurar título de impresión
        const originalTitle = document.title;
        document.title = 'Dashboard HelpDesk - ' + new Date().toLocaleDateString('es-PE');

        // Ejecutar impresión
        window.print();

        // Restaurar
        document.title = originalTitle;
        $('body').removeClass('printing-dashboard');
    });

    // ============================================
    // EXPORT - Exportar a CSV
    // ============================================
    $(document).on('click', '.btn-export', async function(e) {
        e.preventDefault();

        const btn = $(this);
        const originalHtml = btn.html();
        btn.html('<i class="mdi mdi-loading mdi-spin"></i> Exportando...');
        btn.prop('disabled', true);

        try {
            // Detectar período actual
            currentPeriod = detectarPeriodo();

            // Obtener datos del dashboard
            const response = await $.ajax({
                url: 'api/export_dashboard.php',
                method: 'GET',
                data: { periodo: currentPeriod },
                dataType: 'json'
            });

            if (response.error) {
                throw new Error(response.error);
            }

            // Generar CSV
            generarCSV(response);

            Swal.fire({
                icon: 'success',
                title: '¡Exportado!',
                text: 'El archivo CSV ha sido descargado',
                timer: 2000,
                showConfirmButton: false
            });

        } catch (error) {
            console.error('Error al exportar:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo exportar los datos',
                timer: 3000
            });
        } finally {
            btn.html(originalHtml);
            btn.prop('disabled', false);
        }
    });

    function generarCSV(data) {
        let csv = [];

        // BOM para UTF-8
        csv.push('\uFEFF');

        // =====================
        // ENCABEZADO
        // =====================
        csv.push('REPORTE DASHBOARD HELPDESK CLONSA');
        csv.push('Fecha de exportación: ' + new Date().toLocaleString('es-PE'));
        csv.push('Período: ' + data.periodo_texto);
        csv.push('');

        // =====================
        // RESUMEN DE MÉTRICAS
        // =====================
        csv.push('═══════════════════════════════════════');
        csv.push('RESUMEN DE MÉTRICAS');
        csv.push('═══════════════════════════════════════');
        csv.push('');
        csv.push('Métrica,Valor,Comparación vs período anterior');

        if (data.metricas) {
            csv.push(`Tickets Abiertos,${data.metricas.abiertos || 0},${data.metricas.abiertos_comp || '0%'}`);
            csv.push(`Tickets en Proceso,${data.metricas.proceso || 0},${data.metricas.proceso_comp || '0%'}`);
            csv.push(`Tickets Resueltos,${data.metricas.resueltos || 0},${data.metricas.resueltos_comp || '0%'}`);
            csv.push(`Tiempo Promedio Resolución,${data.metricas.tiempo_promedio || 'N/A'},${data.metricas.tiempo_comp || '0%'}`);
            csv.push(`Nivel de Satisfacción,${data.metricas.satisfaccion || 'N/A'},${data.metricas.satisfaccion_comp || '0%'}`);
        }
        csv.push('');

        // =====================
        // TICKETS DEL PERÍODO
        // =====================
        csv.push('═══════════════════════════════════════');
        csv.push('TICKETS DEL PERÍODO');
        csv.push('═══════════════════════════════════════');
        csv.push('');
        csv.push('Código,Título,Estado,Prioridad,Departamento,Creado por,Asignado a,Fecha Creación,Fecha Actualización');

        if (data.tickets && data.tickets.length > 0) {
            data.tickets.forEach(ticket => {
                const titulo = (ticket.titulo || '').replace(/,/g, ' ').replace(/"/g, '""');
                csv.push(`"${ticket.codigo}","${titulo}","${ticket.estado}","${ticket.prioridad}","${ticket.departamento}","${ticket.creador}","${ticket.asignado || 'Sin asignar'}","${ticket.fecha_creacion}","${ticket.fecha_actualizacion}"`);
            });
        } else {
            csv.push('No hay tickets en este período');
        }
        csv.push('');

        // =====================
        // TOP EMPLEADOS
        // =====================
        csv.push('═══════════════════════════════════════');
        csv.push('TOP EMPLEADOS POR TICKETS RESUELTOS');
        csv.push('═══════════════════════════════════════');
        csv.push('');
        csv.push('Posición,Empleado,Departamento,Tickets Resueltos');

        if (data.top_empleados && data.top_empleados.length > 0) {
            data.top_empleados.forEach((emp, idx) => {
                csv.push(`${idx + 1},"${emp.nombre}","${emp.departamento}",${emp.resueltos}`);
            });
        } else {
            csv.push('No hay datos de empleados');
        }
        csv.push('');

        // =====================
        // ACTIVIDADES/STATUS
        // =====================
        csv.push('═══════════════════════════════════════');
        csv.push('RESUMEN POR ACTIVIDAD');
        csv.push('═══════════════════════════════════════');
        csv.push('');
        csv.push('Actividad,Total Tickets,Porcentaje');

        if (data.actividades && data.actividades.length > 0) {
            data.actividades.forEach(act => {
                csv.push(`"${act.nombre}",${act.total},${act.porcentaje}%`);
            });
        } else {
            csv.push('No hay datos de actividades');
        }
        csv.push('');

        // =====================
        // ESTADÍSTICAS POR DÍA
        // =====================
        if (data.estadisticas_diarias && data.estadisticas_diarias.length > 0) {
            csv.push('═══════════════════════════════════════');
            csv.push('ESTADÍSTICAS POR DÍA');
            csv.push('═══════════════════════════════════════');
            csv.push('');
            csv.push('Fecha,Tickets Creados,Tickets Resueltos');

            data.estadisticas_diarias.forEach(stat => {
                csv.push(`${stat.fecha},${stat.creados},${stat.resueltos}`);
            });
            csv.push('');
        }

        // Crear y descargar archivo
        const csvContent = csv.join('\n');
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const fecha = new Date().toISOString().split('T')[0];

        link.href = URL.createObjectURL(blob);
        link.download = `dashboard_helpdesk_${fecha}.csv`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    console.log('✅ Dashboard Export v1.0 inicializado');
});
