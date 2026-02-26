CREATE TABLE IF NOT EXISTS ticket_transferencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    usuario_origen INT NULL,
    usuario_destino INT NOT NULL,
    solicitado_por INT NOT NULL,
    motivo TEXT NULL,
    estado ENUM('pendiente','aprobada','rechazada') NOT NULL DEFAULT 'pendiente',
    aprobado_por INT NULL,
    comentario_aprobacion TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ticket_estado (ticket_id, estado),
    INDEX idx_solicitado_por (solicitado_por),
    INDEX idx_usuario_destino (usuario_destino),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;