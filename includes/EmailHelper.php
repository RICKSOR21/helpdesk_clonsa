<?php
/**
 * EmailHelper - Sistema centralizado de notificaciones por correo
 * Usa PHPMailer con SMTP para enviar correos del helpdesk
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailHelper
{
    private static $logFile = null;

    /**
     * Inicializa el archivo de log
     */
    private static function getLogFile()
    {
        if (self::$logFile === null) {
            self::$logFile = dirname(__DIR__) . '/logs/email_notifications.log';
        }
        return self::$logFile;
    }

    /**
     * Escribe en el log de notificaciones
     */
    private static function log($message)
    {
        $timestamp = date('Y-m-d H:i:s');
        $line = "[{$timestamp}] {$message}" . PHP_EOL;
        @file_put_contents(self::getLogFile(), $line, FILE_APPEND | LOCK_EX);
    }

    /**
     * Crea y configura una instancia de PHPMailer
     */
    private static function createMailer()
    {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';
        $mail->isHTML(true);
        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);

        return $mail;
    }

    /**
     * Envia un correo individual
     */
    private static function sendEmail($toEmail, $toName, $subject, $htmlBody)
    {
        if (!defined('SMTP_ENABLED') || !SMTP_ENABLED) {
            self::log("SMTP deshabilitado - No enviado a: {$toEmail} | Asunto: {$subject}");
            return false;
        }

        try {
            $mail = self::createMailer();
            $mail->addAddress($toEmail, $toName);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));
            $mail->send();
            self::log("OK - Enviado a: {$toEmail} | Asunto: {$subject}");
            return true;
        } catch (Exception $e) {
            self::log("ERROR - No enviado a: {$toEmail} | Asunto: {$subject} | Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Genera el template HTML profesional del correo
     */
    private static function buildTemplate($title, $iconEmoji, $color, $bodyHtml, $actionUrl = '', $actionText = 'Ver Ticket')
    {
        $buttonHtml = '';
        if ($actionUrl) {
            $buttonHtml = '
            <div style="text-align:center; margin:25px 0;">
                <a href="' . htmlspecialchars($actionUrl) . '"
                   style="background:linear-gradient(135deg, #4B49AC, #7B7BF7); color:#ffffff;
                          padding:12px 30px; text-decoration:none; border-radius:6px;
                          font-weight:bold; font-size:14px; display:inline-block;">
                    ' . htmlspecialchars($actionText) . '
                </a>
            </div>';
        }

        return '
        <!DOCTYPE html>
        <html lang="es">
        <head><meta charset="UTF-8"></head>
        <body style="margin:0; padding:0; background-color:#f4f5f7; font-family:Arial,Helvetica,sans-serif;">
            <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f5f7; padding:20px 0;">
                <tr>
                    <td align="center">
                        <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.1);">
                            <!-- Header -->
                            <tr>
                                <td style="background:linear-gradient(135deg, #4B49AC 0%, #7B7BF7 100%); padding:25px 30px; text-align:center;">
                                    <h1 style="color:#ffffff; margin:0; font-size:20px; font-weight:bold;">
                                        SIRA Portal - Helpdesk
                                    </h1>
                                    <p style="color:rgba(255,255,255,0.85); margin:5px 0 0; font-size:12px;">
                                        Sistema de Tickets de Operaci&oacute;n | Clonsa Ingenier&iacute;a
                                    </p>
                                </td>
                            </tr>
                            <!-- Titulo del evento -->
                            <tr>
                                <td style="padding:25px 30px 10px; text-align:center;">
                                    <span style="font-size:32px;">' . $iconEmoji . '</span>
                                    <h2 style="color:' . htmlspecialchars($color) . '; margin:10px 0 0; font-size:18px;">
                                        ' . htmlspecialchars($title) . '
                                    </h2>
                                </td>
                            </tr>
                            <!-- Cuerpo -->
                            <tr>
                                <td style="padding:15px 30px 20px;">
                                    ' . $bodyHtml . '
                                    ' . $buttonHtml . '
                                </td>
                            </tr>
                            <!-- Footer -->
                            <tr>
                                <td style="background:#f8f9fa; padding:20px 30px; text-align:center; border-top:1px solid #e9ecef;">
                                    <p style="color:#6c757d; font-size:11px; margin:0;">
                                        Este correo fue enviado autom&aacute;ticamente por el sistema Helpdesk Clonsa Ingenier&iacute;a.<br>
                                        Por favor no responda a este mensaje.
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>';
    }

    /**
     * Construye una tabla de detalles para el cuerpo del correo
     */
    private static function buildDetailsTable($details)
    {
        $rows = '';
        foreach ($details as $label => $value) {
            if ($value === null || $value === '') continue;
            $rows .= '
            <tr>
                <td style="padding:8px 12px; font-weight:bold; color:#495057; width:40%; border-bottom:1px solid #f0f0f0; font-size:13px;">
                    ' . htmlspecialchars($label) . '
                </td>
                <td style="padding:8px 12px; color:#212529; border-bottom:1px solid #f0f0f0; font-size:13px;">
                    ' . htmlspecialchars($value) . '
                </td>
            </tr>';
        }

        return '<table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e9ecef; border-radius:6px; overflow:hidden; margin:10px 0;">' . $rows . '</table>';
    }

    /**
     * Obtiene los destinatarios para un evento de ticket
     * Retorna array de ['id'=>, 'email'=>, 'nombre'=>, 'rol'=>]
     */
    private static function getTicketRecipients($ticketId, $excludeUserId, $db, $includeActor = false)
    {
        $recipients = [];

        // Obtener datos del ticket
        $stmt = $db->prepare("SELECT t.usuario_id, t.asignado_a, t.departamento_id
                              FROM tickets t WHERE t.id = :id");
        $stmt->execute([':id' => $ticketId]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ticket) return [];

        // Verificar si el actor (usuario que ejecuto la accion) tiene habilitado notificar al admin
        // recibir_notificaciones_email del ACTOR determina si los admins reciben correo sobre sus acciones
        $notificarAdmin = true;
        if ($excludeUserId) {
            $stmtActor = $db->prepare("SELECT recibir_notificaciones_email FROM usuarios WHERE id = :id");
            $stmtActor->execute([':id' => $excludeUserId]);
            $actorData = $stmtActor->fetch(PDO::FETCH_ASSOC);
            if ($actorData) {
                $notificarAdmin = (bool)$actorData['recibir_notificaciones_email'];
            }
        }

        $userIds = [];

        // Creador del ticket
        if ($ticket['usuario_id']) {
            $userIds[] = (int)$ticket['usuario_id'];
        }

        // Asignado al ticket
        if ($ticket['asignado_a']) {
            $userIds[] = (int)$ticket['asignado_a'];
        }

        // Jefe del departamento
        if ($ticket['departamento_id']) {
            $stmtJefe = $db->prepare("SELECT jefe_id FROM departamentos WHERE id = :id AND activo = 1 AND jefe_id IS NOT NULL");
            $stmtJefe->execute([':id' => $ticket['departamento_id']]);
            $dept = $stmtJefe->fetch(PDO::FETCH_ASSOC);
            if ($dept && $dept['jefe_id']) {
                $userIds[] = (int)$dept['jefe_id'];
            }
        }

        // Admins activos (solo si el actor tiene habilitado notificar al admin)
        $adminIds = [];
        if ($notificarAdmin) {
            $stmtAdmins = $db->prepare("SELECT u.id FROM usuarios u
                                         INNER JOIN roles r ON r.id = u.rol_id
                                         WHERE (r.nombre = 'Administrador' OR r.nombre = 'Admin')
                                         AND u.activo = 1");
            $stmtAdmins->execute();
            $admins = $stmtAdmins->fetchAll(PDO::FETCH_COLUMN);
            $adminIds = array_map('intval', $admins);
            $userIds = array_merge($userIds, $adminIds);
        }

        // Eliminar duplicados
        $userIds = array_unique($userIds);

        // Excluir al actor (quien ejecuto la accion), excepto si includeActor = true
        if (!$includeActor) {
            $userIds = array_filter($userIds, function ($id) use ($excludeUserId) {
                return $id !== (int)$excludeUserId;
            });
        }

        if (empty($userIds)) return [];

        // Obtener datos de los usuarios
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $stmtUsers = $db->prepare("SELECT u.id, u.email, u.nombre_completo,
                                          r.nombre as rol_nombre
                                   FROM usuarios u
                                   INNER JOIN roles r ON r.id = u.rol_id
                                   WHERE u.id IN ({$placeholders}) AND u.activo = 1");
        $stmtUsers->execute(array_values($userIds));
        $users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

        foreach ($users as $user) {
            // Verificar que tenga email
            if (empty($user['email'])) continue;

            $recipients[] = [
                'id'     => (int)$user['id'],
                'email'  => $user['email'],
                'nombre' => $user['nombre_completo'],
                'rol'    => $user['rol_nombre']
            ];
        }

        return $recipients;
    }

    /**
     * Configuracion de cada tipo de evento
     */
    private static function getEventConfig($eventType)
    {
        $events = [
            'ticket_creado' => [
                'title'   => 'Nuevo Ticket Creado',
                'icon'    => '🎫',
                'color'   => '#4B49AC',
                'subject' => 'Nuevo Ticket: {codigo} - {titulo}'
            ],
            'ticket_actualizado' => [
                'title'   => 'Ticket Actualizado',
                'icon'    => '📝',
                'color'   => '#2196F3',
                'subject' => 'Ticket Actualizado: {codigo} - {titulo}'
            ],
            'progreso_actualizado' => [
                'title'   => 'Progreso Actualizado',
                'icon'    => '📊',
                'color'   => '#17a2b8',
                'subject' => 'Progreso Actualizado: {codigo} ({progreso}%)'
            ],
            'pendiente_verificacion' => [
                'title'   => 'Pendiente de Verificación',
                'icon'    => '✅',
                'color'   => '#F59E0B',
                'subject' => 'Verificación Requerida: {codigo} - {titulo}'
            ],
            'estado_actualizado' => [
                'title'   => 'Estado del Ticket Actualizado',
                'icon'    => '🔄',
                'color'   => '#6f42c1',
                'subject' => 'Estado Actualizado: {codigo}'
            ],
            'ticket_aprobado' => [
                'title'   => 'Ticket Aprobado / Verificado',
                'icon'    => '✅',
                'color'   => '#28a745',
                'subject' => 'Ticket Aprobado: {codigo} - {titulo}'
            ],
            'ticket_rechazado' => [
                'title'   => 'Ticket Rechazado',
                'icon'    => '❌',
                'color'   => '#dc3545',
                'subject' => 'Ticket Rechazado: {codigo} - {titulo}'
            ],
            'comentario_agregado' => [
                'title'   => 'Nuevo Comentario en Ticket',
                'icon'    => '💬',
                'color'   => '#20c997',
                'subject' => 'Nuevo Comentario: {codigo} - {titulo}'
            ],
            'transferencia_solicitada' => [
                'title'   => 'Solicitud de Transferencia',
                'icon'    => '🔀',
                'color'   => '#F59E0B',
                'subject' => 'Solicitud de Transferencia: {codigo}'
            ],
            'transferencia_directa' => [
                'title'   => 'Ticket Transferido',
                'icon'    => '➡️',
                'color'   => '#6f42c1',
                'subject' => 'Ticket Transferido: {codigo} - {titulo}'
            ],
            'transferencia_aprobada' => [
                'title'   => 'Transferencia Aprobada',
                'icon'    => '✅',
                'color'   => '#28a745',
                'subject' => 'Transferencia Aprobada: {codigo}'
            ],
            'transferencia_rechazada' => [
                'title'   => 'Transferencia Rechazada',
                'icon'    => '❌',
                'color'   => '#dc3545',
                'subject' => 'Transferencia Rechazada: {codigo}'
            ],
        ];

        return $events[$eventType] ?? [
            'title'   => 'Notificación del Sistema',
            'icon'    => '📢',
            'color'   => '#4B49AC',
            'subject' => 'Notificación: {codigo}'
        ];
    }

    /**
     * Reemplaza placeholders en el subject
     */
    private static function parseSubject($template, $data)
    {
        $replacements = [
            '{codigo}'   => $data['codigo'] ?? '',
            '{titulo}'   => $data['titulo'] ?? '',
            '{progreso}' => $data['progreso'] ?? '',
        ];
        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    /**
     * Construye el cuerpo HTML segun el tipo de evento
     */
    private static function buildEventBody($eventType, $data)
    {
        $actorName = $data['actor_nombre'] ?? 'Sistema';

        switch ($eventType) {
            case 'ticket_creado':
                $details = [
                    'Código'       => $data['codigo'] ?? '',
                    'Título'       => $data['titulo'] ?? '',
                    'Descripción'  => mb_substr($data['descripcion'] ?? '', 0, 200),
                    'Departamento' => $data['departamento'] ?? '',
                    'Creado por'   => $actorName,
                    'Asignado a'   => $data['asignado_nombre'] ?? '',
                    'Prioridad'    => $data['prioridad'] ?? '',
                ];
                return '<p style="color:#495057; font-size:14px;">Se ha creado un nuevo ticket en el sistema.</p>'
                    . self::buildDetailsTable($details);

            case 'ticket_actualizado':
                $details = [
                    'Código'        => $data['codigo'] ?? '',
                    'Título'        => $data['titulo'] ?? '',
                    'Actualizado por' => $actorName,
                    'Cambios'       => $data['cambios'] ?? 'Campos actualizados',
                ];
                return '<p style="color:#495057; font-size:14px;">Se han realizado cambios en el ticket.</p>'
                    . self::buildDetailsTable($details);

            case 'progreso_actualizado':
                $progreso = $data['progreso'] ?? 0;
                $barColor = $progreso >= 75 ? '#28a745' : ($progreso >= 50 ? '#17a2b8' : ($progreso >= 25 ? '#ffc107' : '#dc3545'));
                $details = [
                    'Código'        => $data['codigo'] ?? '',
                    'Título'        => $data['titulo'] ?? '',
                    'Progreso'      => $progreso . '%',
                    'Actualizado por' => $actorName,
                ];
                $progressBar = '
                <div style="background:#e9ecef; border-radius:10px; height:20px; margin:10px 0; overflow:hidden;">
                    <div style="background:' . $barColor . '; height:100%; width:' . $progreso . '%; border-radius:10px; text-align:center; color:white; font-size:11px; line-height:20px; font-weight:bold;">
                        ' . $progreso . '%
                    </div>
                </div>';
                return '<p style="color:#495057; font-size:14px;">El progreso del ticket ha sido actualizado.</p>'
                    . self::buildDetailsTable($details) . $progressBar;

            case 'pendiente_verificacion':
                $details = [
                    'Código'        => $data['codigo'] ?? '',
                    'Título'        => $data['titulo'] ?? '',
                    'Completado por' => $actorName,
                ];
                return '<p style="color:#495057; font-size:14px;"><strong>El ticket ha alcanzado el 100% y requiere verificaci&oacute;n.</strong></p>'
                    . self::buildDetailsTable($details)
                    . '<p style="color:#F59E0B; font-size:13px; font-weight:bold;">⚠️ Este ticket necesita ser aprobado o rechazado por un Jefe o Administrador.</p>';

            case 'estado_actualizado':
                $details = [
                    'Código'      => $data['codigo'] ?? '',
                    'Título'      => $data['titulo'] ?? '',
                    'Nuevo Estado' => $data['estado'] ?? '',
                    'Cambiado por' => $actorName,
                ];
                return '<p style="color:#495057; font-size:14px;">El estado del ticket ha sido actualizado.</p>'
                    . self::buildDetailsTable($details);

            case 'ticket_aprobado':
                $details = [
                    'Código'      => $data['codigo'] ?? '',
                    'Título'      => $data['titulo'] ?? '',
                    'Aprobado por' => $actorName,
                    'Comentario'  => $data['comentario'] ?? '',
                ];
                return '<p style="color:#28a745; font-size:14px; font-weight:bold;">El ticket ha sido aprobado y verificado exitosamente.</p>'
                    . self::buildDetailsTable($details);

            case 'ticket_rechazado':
                $details = [
                    'Código'      => $data['codigo'] ?? '',
                    'Título'      => $data['titulo'] ?? '',
                    'Rechazado por' => $actorName,
                    'Motivo'      => $data['motivo'] ?? '',
                ];
                return '<p style="color:#dc3545; font-size:14px; font-weight:bold;">El ticket ha sido rechazado y vuelve a estado "En Atenci&oacute;n" con 90%.</p>'
                    . self::buildDetailsTable($details);

            case 'comentario_agregado':
                $details = [
                    'Código'       => $data['codigo'] ?? '',
                    'Título'       => $data['titulo'] ?? '',
                    'Comentado por' => $actorName,
                    'Comentario'   => mb_substr($data['comentario'] ?? '', 0, 300),
                ];
                return '<p style="color:#495057; font-size:14px;">Se ha agregado un nuevo comentario al ticket.</p>'
                    . self::buildDetailsTable($details);

            case 'transferencia_solicitada':
                $details = [
                    'Código'        => $data['codigo'] ?? '',
                    'Título'        => $data['titulo'] ?? '',
                    'Solicitado por' => $actorName,
                    'Transferir a'  => $data['destino_nombre'] ?? '',
                    'Motivo'        => $data['motivo'] ?? '',
                ];
                return '<p style="color:#F59E0B; font-size:14px; font-weight:bold;">Se ha solicitado una transferencia de ticket que requiere aprobaci&oacute;n.</p>'
                    . self::buildDetailsTable($details);

            case 'transferencia_directa':
                $details = [
                    'Código'          => $data['codigo'] ?? '',
                    'Título'          => $data['titulo'] ?? '',
                    'Transferido por' => $actorName,
                    'De'              => $data['origen_nombre'] ?? '',
                    'A'               => $data['destino_nombre'] ?? '',
                    'Motivo'          => $data['motivo'] ?? '',
                ];
                return '<p style="color:#495057; font-size:14px;">Un ticket ha sido transferido directamente.</p>'
                    . self::buildDetailsTable($details);

            case 'transferencia_aprobada':
                $details = [
                    'Código'       => $data['codigo'] ?? '',
                    'Título'       => $data['titulo'] ?? '',
                    'Aprobado por' => $actorName,
                    'Nuevo asignado' => $data['destino_nombre'] ?? '',
                ];
                return '<p style="color:#28a745; font-size:14px; font-weight:bold;">La solicitud de transferencia ha sido aprobada.</p>'
                    . self::buildDetailsTable($details);

            case 'transferencia_rechazada':
                $details = [
                    'Código'        => $data['codigo'] ?? '',
                    'Título'        => $data['titulo'] ?? '',
                    'Rechazado por' => $actorName,
                    'Comentario'    => $data['comentario'] ?? '',
                ];
                return '<p style="color:#dc3545; font-size:14px; font-weight:bold;">La solicitud de transferencia ha sido rechazada.</p>'
                    . self::buildDetailsTable($details);

            default:
                return '<p style="color:#495057; font-size:14px;">Notificaci&oacute;n del sistema de tickets.</p>';
        }
    }

    /**
     * METODO PRINCIPAL: Notifica un evento de ticket a todos los destinatarios
     *
     * @param string $eventType Tipo de evento
     * @param array  $data      Datos del ticket/evento
     * @param int    $actorId   ID del usuario que ejecuto la accion
     * @param PDO    $db        Conexion a BD
     */
    public static function notifyTicketEvent($eventType, $data, $actorId, $db)
    {
        if (!defined('SMTP_ENABLED') || !SMTP_ENABLED) {
            self::log("SMTP deshabilitado - Evento: {$eventType}");
            return;
        }

        try {
            $ticketId = $data['ticket_id'] ?? null;
            if (!$ticketId) {
                self::log("ERROR - notifyTicketEvent sin ticket_id para evento: {$eventType}");
                return;
            }

            // Obtener info completa del ticket si no viene en data
            if (empty($data['codigo']) || empty($data['titulo'])) {
                $stmtTk = $db->prepare("SELECT t.codigo, t.titulo, t.descripcion, t.progreso,
                                               t.usuario_id, t.asignado_a, t.departamento_id,
                                               d.nombre as departamento_nombre,
                                               uc.nombre_completo as creador_nombre,
                                               ua.nombre_completo as asignado_nombre_completo
                                        FROM tickets t
                                        LEFT JOIN departamentos d ON d.id = t.departamento_id
                                        LEFT JOIN usuarios uc ON uc.id = t.usuario_id
                                        LEFT JOIN usuarios ua ON ua.id = t.asignado_a
                                        WHERE t.id = :id");
                $stmtTk->execute([':id' => $ticketId]);
                $tkInfo = $stmtTk->fetch(PDO::FETCH_ASSOC);
                if ($tkInfo) {
                    $data['codigo']          = $data['codigo'] ?? $tkInfo['codigo'];
                    $data['titulo']          = $data['titulo'] ?? $tkInfo['titulo'];
                    $data['descripcion']     = $data['descripcion'] ?? $tkInfo['descripcion'];
                    $data['departamento']    = $data['departamento'] ?? $tkInfo['departamento_nombre'];
                    $data['asignado_nombre'] = $data['asignado_nombre'] ?? $tkInfo['asignado_nombre_completo'];
                    $data['progreso']        = $data['progreso'] ?? $tkInfo['progreso'];
                }
            }

            // Obtener nombre del actor
            if (empty($data['actor_nombre'])) {
                $stmtActor = $db->prepare("SELECT nombre_completo FROM usuarios WHERE id = :id");
                $stmtActor->execute([':id' => $actorId]);
                $actor = $stmtActor->fetch(PDO::FETCH_ASSOC);
                $data['actor_nombre'] = $actor ? $actor['nombre_completo'] : 'Sistema';
            }

            // Obtener destinatarios
            // En ticket_creado, incluir al creador para que reciba confirmacion por correo
            $includeActor = ($eventType === 'ticket_creado');
            $recipients = self::getTicketRecipients($ticketId, $actorId, $db, $includeActor);

            if (empty($recipients)) {
                self::log("Sin destinatarios para evento: {$eventType} | Ticket: {$data['codigo']}");
                return;
            }

            // Configuracion del evento
            $config = self::getEventConfig($eventType);
            $subject = self::parseSubject($config['subject'], $data);
            $bodyHtml = self::buildEventBody($eventType, $data);

            // URL del ticket
            $ticketUrl = (defined('APP_URL') ? APP_URL : '') . '/ticket-view.php?id=' . $ticketId;
            $html = self::buildTemplate($config['title'], $config['icon'], $config['color'], $bodyHtml, $ticketUrl);

            // Enviar a cada destinatario
            $sent = 0;
            $failed = 0;
            foreach ($recipients as $r) {
                $ok = self::sendEmail($r['email'], $r['nombre'], $subject, $html);
                if ($ok) $sent++;
                else $failed++;
            }

            self::log("Evento: {$eventType} | Ticket: {$data['codigo']} | Enviados: {$sent} | Fallidos: {$failed}");

        } catch (\Exception $e) {
            self::log("EXCEPCION en notifyTicketEvent({$eventType}): " . $e->getMessage());
        }
    }

    /**
     * Notifica un nuevo comunicado a TODOS los usuarios activos
     */
    public static function notifyComunicado($data, $db)
    {
        if (!defined('SMTP_ENABLED') || !SMTP_ENABLED) {
            self::log("SMTP deshabilitado - Comunicado: " . ($data['titulo'] ?? ''));
            return;
        }

        try {
            $titulo    = $data['titulo'] ?? 'Comunicado';
            $contenido = $data['contenido'] ?? '';
            $tipo      = $data['tipo'] ?? 'informativo';
            $creadorId = $data['creado_por'] ?? 0;

            // Obtener nombre del creador
            $creadorNombre = 'Administración';
            if ($creadorId) {
                $stmtC = $db->prepare("SELECT nombre_completo FROM usuarios WHERE id = :id");
                $stmtC->execute([':id' => $creadorId]);
                $cr = $stmtC->fetch(PDO::FETCH_ASSOC);
                if ($cr) $creadorNombre = $cr['nombre_completo'];
            }

            // Colores por tipo
            $tipoColors = [
                'actualizacion' => '#4CAF50',
                'mantenimiento' => '#F59E0B',
                'alerta'        => '#E91E63',
                'informativo'   => '#2196F3',
            ];
            $tipoNames = [
                'actualizacion' => 'Actualización',
                'mantenimiento' => 'Mantenimiento',
                'alerta'        => 'Alerta',
                'informativo'   => 'Informativo',
            ];

            $color = $tipoColors[$tipo] ?? '#2196F3';
            $tipoNombre = $tipoNames[$tipo] ?? 'Informativo';

            $details = [
                'Tipo'        => $tipoNombre,
                'Publicado por' => $creadorNombre,
            ];

            $bodyHtml = '<p style="color:#495057; font-size:14px; font-weight:bold;">' . htmlspecialchars($titulo) . '</p>'
                . '<p style="color:#495057; font-size:13px;">' . nl2br(htmlspecialchars(mb_substr($contenido, 0, 500))) . '</p>'
                . self::buildDetailsTable($details);

            $comunicadosUrl = (defined('APP_URL') ? APP_URL : '') . '/comunicados.php';
            $html = self::buildTemplate('Nuevo Comunicado', '📢', $color, $bodyHtml, $comunicadosUrl, 'Ver Comunicados');

            $subject = APP_NAME . ' - Comunicado: ' . $titulo;

            // Obtener TODOS los usuarios activos (excepto el creador)
            $stmt = $db->prepare("SELECT u.id, u.email, u.nombre_completo
                                  FROM usuarios u
                                  WHERE u.activo = 1 AND u.id != :creador_id AND u.email IS NOT NULL AND u.email != ''");
            $stmt->execute([':creador_id' => $creadorId]);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $sent = 0;
            $failed = 0;
            foreach ($users as $user) {
                $ok = self::sendEmail($user['email'], $user['nombre_completo'], $subject, $html);
                if ($ok) $sent++;
                else $failed++;
            }

            self::log("Comunicado: '{$titulo}' | Enviados: {$sent} | Fallidos: {$failed}");

        } catch (\Exception $e) {
            self::log("EXCEPCION en notifyComunicado: " . $e->getMessage());
        }
    }
}
