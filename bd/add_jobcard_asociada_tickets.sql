-- Agrega campo opcional para Jobcard Asociada en tickets
-- Compatible con MySQL sin "ADD COLUMN IF NOT EXISTS"

SET @db_name := DATABASE();
SET @col_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'tickets'
      AND COLUMN_NAME = 'jobcard_asociada'
);

SET @sql_stmt := IF(
    @col_exists = 0,
    'ALTER TABLE tickets ADD COLUMN jobcard_asociada VARCHAR(255) NULL AFTER solicitante_telefono',
    'SELECT ''Columna jobcard_asociada ya existe'' AS info'
);

PREPARE stmt FROM @sql_stmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
