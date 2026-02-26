console.log('🔔 NOTIFICACIONES v4 - CARGANDO...');

$(document).ready(function() {

    // ============================================
    // MARCAR COMO LEÍDO
    // ============================================
    function marcarComoLeido(tipo, referenciaId, elemento) {
        $.ajax({
            url: 'api/marcar_leido.php',
            method: 'POST',
            data: {
                tipo: tipo,
                referencia_id: referenciaId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Cambiar el punto rojo por el icono de visto
                    $(elemento).removeClass('unread');
                    $(elemento).find('.status-indicator').html('<i class="mdi mdi-eye-check-outline text-success" style="font-size: 12px;"></i>');

                    // Actualizar contador del badge
                    if (tipo === 'comunicado') {
                        actualizarBadge('.count-comunicados');
                    } else {
                        actualizarBadge('.count-tickets');
                    }
                }
            }
        });
    }

    function actualizarBadge(selector) {
        const badge = $(selector);
        let count = parseInt(badge.text()) || 0;
        count = Math.max(0, count - 1);

        if (count > 0) {
            badge.text(count);
        } else {
            badge.removeClass('show').text('0');
        }

        if (selector === '.count-tickets') {
            const sideBadge = $('.count-notificaciones-sidebar');
            if (sideBadge.length) {
                if (count > 0) {
                    sideBadge.text(count).addClass('show');
                } else {
                    sideBadge.text('0').removeClass('show');
                }
            }
        }
    }

    // ============================================
    // CARGAR COMUNICADOS DEL SISTEMA
    // ============================================
    function cargarComunicados() {
        $.ajax({
            url: 'api/comunicados.php',
            method: 'GET',
            data: { limit: 5 },
            dataType: 'json',
            success: function(response) {
                console.log('✅ Comunicados recibidos:', response);
                renderizarComunicados(response.comunicados || []);

                if (response.total > 0) {
                    $('.count-comunicados').text(response.total).addClass('show');
                    $('.count-comunicados-sidebar').text(response.total).addClass('show');
                } else {
                    $('.count-comunicados').removeClass('show').text('0');
                    $('.count-comunicados-sidebar').removeClass('show').text('0');
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Error al cargar comunicados:', error);
                $('#comunicadosContainer').html(
                    '<div class="text-center py-4 text-muted">' +
                    '<i class="mdi mdi-alert-circle" style="font-size: 28px;"></i>' +
                    '<p class="mb-0 mt-2" style="font-size: 12px;">Error al cargar</p>' +
                    '</div>'
                );
            }
        });
    }

    function renderizarComunicados(comunicados) {
        const container = $('#comunicadosContainer');

        if (comunicados.length === 0) {
            container.html(
                '<div class="text-center py-4 text-muted">' +
                '<i class="mdi mdi-email-check-outline" style="font-size: 36px; color: #ccc;"></i>' +
                '<p class="mb-0 mt-2" style="font-size: 13px;">No hay comunicados</p>' +
                '</div>'
            );
            return;
        }

        let html = '';

        comunicados.forEach(function(com) {
            const isUnread = !com.leido;
            const unreadClass = isUnread ? 'unread' : '';
            const statusIcon = isUnread
                ? '<span class="unread-dot"></span>'
                : '<i class="mdi mdi-eye-check-outline text-success" style="font-size: 12px;"></i>';

            html += '<a class="dropdown-item notif-item ' + unreadClass + '" href="comunicados.php?id=' + com.id + '" data-tipo="comunicado" data-id="' + com.id + '">' +
                '<div class="notif-row">' +
                    '<div class="status-indicator">' + statusIcon + '</div>' +
                    '<div class="notif-icon" style="background: ' + com.color + '18;">' +
                        '<i class="mdi ' + com.icono + '" style="color: ' + com.color + ';"></i>' +
                    '</div>' +
                    '<div class="notif-content">' +
                        '<p class="notif-title">' + com.titulo + '</p>' +
                        '<span class="notif-time"><i class="mdi mdi-clock-outline"></i> ' + com.tiempo_relativo + '</span>' +
                    '</div>' +
                '</div>' +
            '</a>';
        });

        container.html(html);

        // Evento click para marcar como leído
        container.find('.notif-item').on('click', function(e) {
            const tipo = $(this).data('tipo');
            const id = $(this).data('id');
            marcarComoLeido(tipo, id, this);
        });
    }

    // ============================================
    // CARGAR NOTIFICACIONES DE TICKETS
    // ============================================
    function cargarNotificacionesTickets() {
        $.ajax({
            url: 'api/notificaciones_tickets.php',
            method: 'GET',
            data: { limit: 5 },
            dataType: 'json',
            success: function(response) {
                console.log('✅ Notificaciones tickets recibidas:', response);
                renderizarNotificacionesTickets(response.notificaciones || []);

                if (response.total > 0) {
                    $('.count-tickets').text(response.total).addClass('show');
                    $('.count-notificaciones-sidebar').text(response.total).addClass('show');
                } else {
                    $('.count-tickets').removeClass('show').text('0');
                    $('.count-notificaciones-sidebar').removeClass('show').text('0');
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Error al cargar notificaciones:', error);
                $('#ticketsNotificacionesContainer').html(
                    '<div class="text-center py-4 text-muted">' +
                    '<i class="mdi mdi-alert-circle" style="font-size: 28px;"></i>' +
                    '<p class="mb-0 mt-2" style="font-size: 12px;">Error al cargar</p>' +
                    '</div>'
                );
            }
        });
    }

    function cargarContadoresSidebar() {
        const badgeAsignados = $('.count-asignados-sidebar');
        const badgeTodos = $('.count-todos-sidebar');
        const badgeMis = $('.count-mis-sidebar');
        if (!badgeAsignados.length && !badgeTodos.length && !badgeMis.length) return;

        $.ajax({
            url: 'api/tickets.php?action=listar',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (!response || !response.success) return;

                const currentUserId = parseInt(window.CURRENT_USER_ID || 0);
                const tickets = response.data || [];
                const totalAsignados = tickets.filter(function(t) {
                    const transferidoAMi = parseInt(t.transferido_a_mi || 0) === 1;
                    const asignadoActual = currentUserId > 0 ? parseInt(t.asignado_a || 0) === currentUserId : true;
                    const cerradoFinal = parseInt(t.estado_id || 0) === 4 && parseInt(t.pendiente_aprobacion || 0) === 0;
                    return transferidoAMi && asignadoActual && !cerradoFinal;
                }).length;

                const totalAbiertos = tickets.filter(function(t) {
                    const cerradoFinal = parseInt(t.estado_id || 0) === 4 && parseInt(t.pendiente_aprobacion || 0) === 0;
                    return !cerradoFinal;
                }).length;
                const totalMis = tickets.filter(function(t) {
                    const cerradoFinal = parseInt(t.estado_id || 0) === 4 && parseInt(t.pendiente_aprobacion || 0) === 0;
                    var esCreador = currentUserId > 0 ? parseInt(t.usuario_id || 0) === currentUserId : false;
                    var asignadoA = parseInt(t.asignado_a || 0);
                    var asignadoAMi = currentUserId > 0 && asignadoA === currentUserId;
                    // Mis tickets = asignados a mí O creados por mí (sin asignar a otro)
                    var esMio = asignadoAMi || (esCreador && (asignadoA === 0 || asignadoA === currentUserId));
                    return esMio && !cerradoFinal;
                }).length;

                if (badgeAsignados.length) {
                    if (totalAsignados > 0) {
                        badgeAsignados.text(totalAsignados).addClass('show');
                    } else {
                        badgeAsignados.text('0').removeClass('show');
                    }
                }

                if (badgeTodos.length) {
                    if (totalAbiertos > 0) {
                        badgeTodos.text(totalAbiertos).addClass('show');
                    } else {
                        badgeTodos.text('0').removeClass('show');
                    }
                }

                if (badgeMis.length) {
                    if (totalMis > 0) {
                        badgeMis.text(totalMis).addClass('show');
                    } else {
                        badgeMis.text('0').removeClass('show');
                    }
                }
            }
        });
    }

    function renderizarNotificacionesTickets(notificaciones) {
        const container = $('#ticketsNotificacionesContainer');

        if (notificaciones.length === 0) {
            container.html(
                '<div class="text-center py-4 text-muted">' +
                '<i class="mdi mdi-bell-check-outline" style="font-size: 36px; color: #ccc;"></i>' +
                '<p class="mb-0 mt-2" style="font-size: 13px;">No hay notificaciones</p>' +
                '</div>'
            );
            return;
        }

        let html = '';

        notificaciones.forEach(function(notif) {
            const isUnread = !notif.leido;
            const unreadClass = isUnread ? 'unread' : '';
            let iconColor = '#2196F3';
            let bgColor = 'rgba(33, 150, 243, 0.12)';
            let iconName = 'mdi-account-arrow-right';

            if (notif.tipo === 'nuevo') {
                iconColor = '#4CAF50';
                bgColor = 'rgba(76, 175, 80, 0.12)';
                iconName = 'mdi-ticket-confirmation';
            } else if (notif.tipo === 'aprobado') {
                iconColor = '#28a745';
                bgColor = 'rgba(40, 167, 69, 0.15)';
                iconName = 'mdi-check-decagram';
            } else if (notif.tipo === 'rechazado') {
                iconColor = '#dc3545';
                bgColor = 'rgba(220, 53, 69, 0.15)';
                iconName = 'mdi-close-octagon';
            }
            const statusIcon = isUnread
                ? '<span class="unread-dot"></span>'
                : '<i class="mdi mdi-eye-check-outline text-success" style="font-size: 12px;"></i>';

            const tipoNotificacion = notif.tipo_notificacion || 'ticket';
            const referenciaEvento = notif.referencia_evento || notif.id;
            html += '<a class="dropdown-item notif-item ' + unreadClass + '" href="ticket-view.php?id=' + notif.id + '" data-tipo="' + tipoNotificacion + '" data-id="' + referenciaEvento + '">' +
                '<div class="notif-row">' +
                    '<div class="status-indicator">' + statusIcon + '</div>' +
                    '<div class="notif-icon" style="background: ' + bgColor + ';">' +
                        '<i class="mdi ' + iconName + '" style="color: ' + iconColor + ';"></i>' +
                    '</div>' +
                    '<div class="notif-content">' +
                        '<p class="notif-title">' + notif.titulo + '</p>' +
                        '<span class="notif-subtitle">' + notif.mensaje + '</span>' +
                        '<span class="notif-time"><i class="mdi mdi-clock-outline"></i> ' + notif.tiempo + '</span>' +
                    '</div>' +
                '</div>' +
            '</a>';
        });

        container.html(html);

        // Evento click para marcar como leído
        container.find('.notif-item').on('click', function(e) {
            const tipo = $(this).data('tipo');
            const id = $(this).data('id');
            marcarComoLeido(tipo, id, this);
        });
    }

    // ============================================
    // INICIALIZACIÓN
    // ============================================
    $('#comunicadosDropdown').on('click', function() {
        cargarComunicados();
    });

    $('#ticketsDropdown').on('click', function() {
        cargarNotificacionesTickets();
    });

    // Cargar todo inmediatamente (sin delay para evitar flash en badges)
    cargarContadoresSidebar();
    cargarComunicados();
    cargarNotificacionesTickets();

    // Refrescar cada 60 segundos
    setInterval(function() {
        cargarComunicados();
        cargarNotificacionesTickets();
        cargarContadoresSidebar();
    }, 60000);

    console.log('✅ Notificaciones v4 inicializado');

});
