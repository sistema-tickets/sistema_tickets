ALTER TABLE tickets ADD COLUMN celular varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Teléfono del solicitante al momento de crear el ticket' AFTER linea_negocio_id;
