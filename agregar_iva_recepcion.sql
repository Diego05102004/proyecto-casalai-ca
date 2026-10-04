ALTER TABLE tbl_recepcion_productos
    ADD COLUMN subtotal_factura DECIMAL(12, 2) NULL,
    ADD COLUMN porcentaje_iva DECIMAL(7, 4) NULL,
    ADD COLUMN monto_iva DECIMAL(12, 2) NULL,
    ADD COLUMN total_factura DECIMAL(12, 2) NULL;