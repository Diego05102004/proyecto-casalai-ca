import unittest

from microservicio.modelo.auditor_recepcion import AuditorRecepcion


class ExtraccionProductosFacturaTest(unittest.TestCase):
    def test_extrae_items_multilinea_y_reconcilia_precio_ocr(self):
        texto_ocr = """DESCRIPCION / MODELO MARCA SERIAL UNITARIO CANT. TOTAL
HP. DeskJet.2775 HP 0002 $4520.00 2 $3040.00
DeskJet'2775
Tinta HP Original GT HP. 0013 $250.00 6 $1500.00
662
Tarjeta SD Kingston -Azon 0008 $80.00 10 $800.00
Sublime
Subtotal: $5340.00,
IVA (16%): $854.40
Total: $6194.40"""
        auditor = AuditorRecepcion.__new__(AuditorRecepcion)

        productos = auditor._extraer_productos(texto_ocr)

        self.assertEqual(
            [
                (producto.nombre, producto.modelo, producto.marca, producto.serial,
                 producto.cantidad, producto.costo_unitario)
                for producto in productos
            ],
            [
                ("HP DeskJet 2775", "DeskJet 2775", "HP", "0002", 2, 1520.0),
                ("Tinta HP Original GT 662", "662", "HP", "0013", 6, 250.0),
                ("Tarjeta SD Kingston Sublime", "Sublime", "Azon", "0008", 10, 80.0),
            ],
        )


if __name__ == "__main__":
    unittest.main()