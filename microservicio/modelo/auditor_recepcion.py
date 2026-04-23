"""Módulo: auditor_recepcion.py - Modelo de IA para auditoría de recepción. Fase 1: Verificación Asistida."""

import re
import json
import logging
import hashlib
from typing import Dict, List, Optional, Tuple, Any
from dataclasses import dataclass, field, asdict
from datetime import datetime
from pathlib import Path
import pytesseract
from PIL import Image
import cv2
import numpy as np

logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[logging.FileHandler('logs_metricas/auditor_recepcion.log'), logging.StreamHandler()])
logger = logging.getLogger(__name__)

@dataclass
class ProductoExtraido:
    nombre: str = ""
    modelo: str = ""
    marca: str = ""
    serial: str = ""
    cantidad: int = 0
    costo_unitario: float = 0.0
    confianza: float = 0.0

@dataclass
class FacturaExtraida:
    numero_factura: str = ""
    nombre_proveedor: str = ""
    fecha_factura: str = ""
    productos: List[ProductoExtraido] = field(default_factory=list)
    total_factura: float = 0.0
    metadatos_extraccion: Dict = field(default_factory=dict)

@dataclass
class Discrepancia:
    campo: str
    valor_factura: str
    valor_formulario: str
    severidad: str
    mensaje: str

@dataclass
class ResultadoVerificacion:
    exito: bool
    discrepancias: List[Discrepancia]
    factura_extraida: Optional[FacturaExtraida]
    datos_formulario: Optional[Dict]
    reporte_detallado: str
    timestamp: str = field(default_factory=lambda: datetime.now().isoformat())
    hash_verificacion: str = ""
    def __post_init__(self):
        if not self.hash_verificacion:
            data = f"{self.timestamp}{json.dumps(self.datos_formulario, sort_keys=True, default=str)}"
            self.hash_verificacion = hashlib.sha256(data.encode()).hexdigest()[:16]

class AuditorRecepcion:
    UMBRAL_CONFIANZA_ALTA = 0.85
    UMBRAL_CONFIANZA_MEDIA = 0.60
    PATRONES = {
        'numero_factura': [r'(?:factura|n[°º]|nro|numero|#)\s*[.:]?\s*([A-Z0-9\-]{5,20})', r'(?:factura)\s*(?:n[°º])?\s*[.:]?\s*([A-Z0-9\-]+)', r'(?:invoice|inv)\s*(?:#|n[°º])?\s*[.:]?\s*([A-Z0-9\-]+)'],
        'proveedor': [r'(?:proveedor|vendedor|supplier|vendor|de|from)[:\s]+([A-Z][A-Za-z0-9\s&.,]+(?:S\.A|S\.R\.L|C\.A|LLC|INC|LTDA)?)', r'(?:razon social|nombre)[:\s]+([A-Z][A-Za-z0-9\s&.,]+)'],
        'fecha': [r'(\d{1,2}[/-]\d{1,2}[/-]\d{2,4})', r'(\d{4}[/-]\d{1,2}[/-]\d{1,2})'],
        'producto': [r'(?:descripcion|producto|articulo|item)[:\s]+([^\n]+)'],
        'modelo': [r'(?:modelo|model|mod)[:\s]+([A-Z0-9\-]+)'],
        'marca': [r'(?:marca|brand|fabricante)[:\s]+([A-Za-z0-9\s]+)'],
        'serial': [r'(?:serial|s/n|serie|sn)[:\s]+([A-Z0-9\-]+)'],
        'cantidad': [r'(?:cantidad|qty|quantity|cant)[:\s]+(\d+)', r'(?:und|unidades|units?)[:\s]+(\d+)'],
        'costo': [r'(?:precio|costo|price|valor|unitario|p/u)[:\s]+[\$]?\s*(\d+[.,]?\d*)', r'[\$]\s*(\d+[.,]?\d{2})'],
        'total': [r'(?:total|monto total|importe)[:\s]+[\$]?\s*(\d+[.,]?\d{2})', r'total\s*[\$]?\s*(\d+[.,]?\d{2})']
    }

    def __init__(self, config_path: Optional[str] = None):
        self.config = self._cargar_configuracion(config_path)
        self.cache_facturas: Dict[str, FacturaExtraida] = {}
        self.historial_verificaciones: List[ResultadoVerificacion] = []
        logger.info("AuditorRecepcion inicializado - Fase 1: Verificación Asistida")

    def _cargar_configuracion(self, config_path: Optional[str]) -> Dict:
        config_default = {'tesseract_cmd': 'tesseract', 'idioma_ocr': 'spa+eng', 'preprocesamiento': True,
            'guardar_cache': True, 'umbral_exactitud': 0.90, 'tolerancia_costo': 0.05, 'tolerancia_cantidad': 0}
        if config_path and Path(config_path).exists():
            try:
                with open(config_path, 'r', encoding='utf-8') as f:
                    config_default.update(json.load(f))
            except Exception as e:
                logger.warning(f"Error cargando config: {e}")
        return config_default

    def extraer_desde_imagen(self, ruta_imagen: str, id_cache: Optional[str] = None) -> FacturaExtraida:
        logger.info(f"Iniciando extracción OCR: {ruta_imagen}")
        if not Path(ruta_imagen).exists():
            raise FileNotFoundError(f"Imagen no encontrada: {ruta_imagen}")
        imagen_procesada = self._preprocesar_imagen(ruta_imagen)
        texto_extraido = self._ejecutar_ocr(imagen_procesada)
        factura = self._extraer_campos_factura(texto_extraido)
        factura.metadatos_extraccion = {'timestamp': datetime.now().isoformat(), 'longitud_texto': len(texto_extraido),
            'confianza_promedio': self._calcular_confianza_promedio(factura), 'lineas_detectadas': len(texto_extraido.split('\n')),
            'metodo': 'OCR_Tesseract + Regex_Contextual'}
        if id_cache and self.config['guardar_cache']:
            self.cache_facturas[id_cache] = factura
        logger.info(f"Extracción completada. Confianza: {factura.metadatos_extraccion['confianza_promedio']:.2%}")
        return factura

    def verificar_coherencia(self, id_factura_cache: str, datos_formulario: Dict) -> ResultadoVerificacion:
        logger.info(f"Verificación de coherencia: {id_factura_cache}")
        if id_factura_cache not in self.cache_facturas:
            error_msg = f"Factura no encontrada en cache: {id_factura_cache}"
            logger.error(error_msg)
            return ResultadoVerificacion(exito=False, discrepancias=[Discrepancia(campo="SISTEMA", valor_factura="N/A", valor_formulario="N/A", severidad="CRITICA", mensaje=error_msg)],
                factura_extraida=None, datos_formulario=datos_formulario, reporte_detallado=error_msg)
        factura = self.cache_facturas[id_factura_cache]
        discrepancias: List[Discrepancia] = []
        if 'numero_factura' in datos_formulario:
            disc = self._comparar_campo('Número de Factura', factura.numero_factura, datos_formulario['numero_factura'], 'CRITICA')
            if disc: discrepancias.append(disc)
        if 'nombre_proveedor' in datos_formulario:
            disc = self._comparar_campo_proveedor(factura.nombre_proveedor, datos_formulario['nombre_proveedor'])
            if disc: discrepancias.append(disc)
        productos_form = datos_formulario.get('productos', [])
        for idx, prod_form in enumerate(productos_form):
            disc_producto = self._verificar_producto(factura.productos, prod_form, idx)
            discrepancias.extend(disc_producto)
        exito = len(discrepancias) == 0
        reporte = self._generar_reporte(discrepancias, factura, datos_formulario)
        resultado = ResultadoVerificacion(exito=exito, discrepancias=discrepancias, factura_extraida=factura, datos_formulario=datos_formulario, reporte_detallado=reporte)
        self.historial_verificaciones.append(resultado)
        logger.info(f"Verificación: {'exitosa' if exito else f'{len(discrepancias)} discrepancias'}")
        return resultado

    def comparar_directo(self, ruta_imagen: str, datos_formulario: Dict) -> ResultadoVerificacion:
        id_temp = f"temp_{datetime.now().strftime('%Y%m%d%H%M%S')}_{hashlib.md5(ruta_imagen.encode()).hexdigest()[:8]}"
        factura = self.extraer_desde_imagen(ruta_imagen, id_temp)
        return self.verificar_coherencia(id_temp, datos_formulario)

    def _preprocesar_imagen(self, ruta_imagen: str) -> np.ndarray:
        imagen = cv2.imread(ruta_imagen)
        if imagen is None:
            raise ValueError(f"No se pudo cargar: {ruta_imagen}")
        if not self.config['preprocesamiento']: return imagen
        gris = cv2.cvtColor(imagen, cv2.COLOR_BGR2GRAY)
        denoised = cv2.fastNlMeansDenoising(gris, None, 10, 7, 21)
        clahe = cv2.createCLAHE(clipLimit=2.0, tileGridSize=(8, 8))
        contraste = clahe.apply(denoised)
        binaria = cv2.adaptiveThreshold(contraste, 255, cv2.ADAPTIVE_THRESH_GAUSSIAN_C, cv2.THRESH_BINARY, 11, 2)
        return binaria

    def _ejecutar_ocr(self, imagen: np.ndarray) -> str:
        config_tesseract = '--psm 6 --oem 3'
        return pytesseract.image_to_string(imagen, lang=self.config['idioma_ocr'], config=config_tesseract)

    def _extraer_campos_factura(self, texto: str) -> FacturaExtraida:
        factura = FacturaExtraida()
        texto_limpio = self._limpiar_texto(texto)
        factura.numero_factura = self._extraer_con_patrones(texto_limpio, self.PATRONES['numero_factura'])
        factura.nombre_proveedor = self._extraer_con_patrones(texto_limpio, self.PATRONES['proveedor'])
        factura.fecha_factura = self._extraer_con_patrones(texto_limpio, self.PATRONES['fecha'])
        total_str = self._extraer_con_patrones(texto_limpio, self.PATRONES['total'])
        factura.total_factura = self._parsear_monto(total_str)
        factura.productos = self._extraer_productos(texto_limpio)
        return factura

    def _extraer_con_patrones(self, texto: str, patrones: List[str]) -> str:
        for patron in patrones:
            coincidencias = re.findall(patron, texto, re.IGNORECASE)
            if coincidencias:
                return coincidencias[0].strip() if isinstance(coincidencias[0], str) else str(coincidencias[0])
        return ""

    def _extraer_productos(self, texto: str) -> List[ProductoExtraido]:
        productos = []
        lineas = texto.split('\n')
        for i, linea in enumerate(lineas):
            if any(indicador in linea.lower() for indicador in ['producto', 'descripcion', 'articulo', 'item', 'codigo', 'sku']):
                prod = self._parsear_linea_producto(linea, lineas[i:i+3])
                if prod and prod.nombre: productos.append(prod)
        if not productos:
            prod = self._extraer_producto_general(texto)
            if prod: productos.append(prod)
        return productos

    def _parsear_linea_producto(self, linea: str, contexto: List[str]) -> Optional[ProductoExtraido]:
        prod = ProductoExtraido()
        texto_completo = ' '.join(contexto)
        prod.nombre = self._extraer_con_patrones(texto_completo, self.PATRONES['producto'])
        prod.modelo = self._extraer_con_patrones(texto_completo, self.PATRONES['modelo'])
        prod.marca = self._extraer_con_patrones(texto_completo, self.PATRONES['marca'])
        prod.serial = self._extraer_con_patrones(texto_completo, self.PATRONES['serial'])
        cantidad_str = self._extraer_con_patrones(texto_completo, self.PATRONES['cantidad'])
        prod.cantidad = int(cantidad_str) if cantidad_str.isdigit() else 1
        costo_str = self._extraer_con_patrones(texto_completo, self.PATRONES['costo'])
        prod.costo_unitario = self._parsear_monto(costo_str)
        campos_llenos = sum([bool(prod.nombre), bool(prod.modelo), bool(prod.marca), bool(prod.serial), prod.cantidad > 0, prod.costo_unitario > 0])
        prod.confianza = campos_llenos / 6.0
        return prod if prod.nombre else None

    def _extraer_producto_general(self, texto: str) -> Optional[ProductoExtraido]:
        prod = ProductoExtraido()
        prod.nombre = self._extraer_con_patrones(texto, self.PATRONES['producto'])
        prod.modelo = self._extraer_con_patrones(texto, self.PATRONES['modelo'])
        prod.marca = self._extraer_con_patrones(texto, self.PATRONES['marca'])
        prod.serial = self._extraer_con_patrones(texto, self.PATRONES['serial'])
        cantidad_str = self._extraer_con_patrones(texto, self.PATRONES['cantidad'])
        prod.cantidad = int(cantidad_str) if cantidad_str.isdigit() else 1
        costo_str = self._extraer_con_patrones(texto, self.PATRONES['costo'])
        prod.costo_unitario = self._parsear_monto(costo_str)
        return prod if prod.nombre else None

    def _comparar_campo(self, nombre_campo: str, valor_factura: str, valor_formulario: str, severidad: str) -> Optional[Discrepancia]:
        v_factura = self._normalizar_texto(valor_factura)
        v_formulario = self._normalizar_texto(valor_formulario)
        if v_factura != v_formulario:
            return Discrepancia(campo=nombre_campo, valor_factura=valor_factura, valor_formulario=valor_formulario, severidad=severidad, mensaje=f"Diferencia en {nombre_campo}: Factura='{valor_factura}' vs Formulario='{valor_formulario}'")
        return None

    def _comparar_campo_proveedor(self, valor_factura: str, valor_formulario: str) -> Optional[Discrepancia]:
        v_factura = self._normalizar_texto(valor_factura)
        v_formulario = self._normalizar_texto(valor_formulario)
        if v_factura == v_formulario: return None
        similitud = self._calcular_similitud(v_factura, v_formulario)
        if similitud < 0.80:
            return Discrepancia(campo="Nombre del Proveedor", valor_factura=valor_factura, valor_formulario=valor_formulario, severidad="ALTA", mensaje=f"Proveedor diferente (similitud: {similitud:.0%})")
        return None

    def _verificar_producto(self, productos_factura: List[ProductoExtraido], producto_form: Dict, idx: int) -> List[Discrepancia]:
        discrepancias = []
        prod_factura = self._encontrar_producto_correspondiente(productos_factura, producto_form)
        if not prod_factura:
            discrepancias.append(Discrepancia(campo=f"Producto {idx + 1}", valor_factura="No encontrado", valor_formulario=producto_form.get('nombre', 'N/A'), severidad="ALTA", mensaje="Producto no encontrado en factura"))
            return discrepancias
        if 'nombre' in producto_form:
            disc = self._comparar_campo_proveedor(prod_factura.nombre, producto_form['nombre'])
            if disc: disc.campo = f"Producto {idx + 1} - Nombre"; discrepancias.append(disc)
        if 'modelo' in producto_form and prod_factura.modelo:
            disc = self._comparar_campo(f"Producto {idx + 1} - Modelo", prod_factura.modelo, producto_form['modelo'], "MEDIA")
            if disc: discrepancias.append(disc)
        if 'marca' in producto_form and prod_factura.marca:
            disc = self._comparar_campo(f"Producto {idx + 1} - Marca", prod_factura.marca, producto_form['marca'], "MEDIA")
            if disc: discrepancias.append(disc)
        if 'serial' in producto_form and prod_factura.serial:
            disc = self._comparar_campo(f"Producto {idx + 1} - Serial", prod_factura.serial, producto_form['serial'], "ALTA")
            if disc: discrepancias.append(disc)
        if 'cantidad' in producto_form:
            cantidad_form = int(producto_form['cantidad'])
            if prod_factura.cantidad != cantidad_form:
                discrepancias.append(Discrepancia(campo=f"Producto {idx + 1} - Cantidad", valor_factura=str(prod_factura.cantidad), valor_formulario=str(cantidad_form), severidad="CRITICA", mensaje=f"Cantidad diferente: {prod_factura.cantidad} vs {cantidad_form}"))
        if 'costo' in producto_form:
            costo_form = float(producto_form['costo'])
            tolerancia = self.config['tolerancia_costo']
            diferencia = abs(prod_factura.costo_unitario - costo_form) / max(costo_form, 0.01)
            if diferencia > tolerancia:
                discrepancias.append(Discrepancia(campo=f"Producto {idx + 1} - Costo", valor_factura=f"{prod_factura.costo_unitario:.2f}", valor_formulario=f"{costo_form:.2f}", severidad="ALTA", mensaje=f"Costo diferente en {diferencia:.1%}"))
        return discrepancias

    def _encontrar_producto_correspondiente(self, productos: List[ProductoExtraido], prod_form: Dict) -> Optional[ProductoExtraido]:
        nombre_form = self._normalizar_texto(prod_form.get('nombre', ''))
        for prod in productos:
            similitud = self._calcular_similitud(self._normalizar_texto(prod.nombre), nombre_form)
            if similitud >= 0.70: return prod
        return None

    def _generar_reporte(self, discrepancias: List[Discrepancia], factura: FacturaExtraida, datos_form: Dict) -> str:
        reporte = ["=" * 60, "REPORTE DE VERIFICACIÓN - FASE 1: EL GUARDIÁN", "=" * 60, f"Fecha: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}", ""]
        if discrepancias:
            reporte.extend([f"⚠️  RESULTADO: BLOQUEADO - {len(discrepancias)} DISCREPANCIAS ENCONTRADAS", "", "DETALLE DE DISCREPANCIAS:", "-" * 40])
            for i, disc in enumerate(discrepancias, 1):
                icono = {"CRITICA": "🔴", "ALTA": "🟠", "MEDIA": "🟡", "BAJA": "🟢"}.get(disc.severidad, "⚪")
                reporte.extend([f"{i}. {icono} [{disc.severidad}] {disc.campo}", f"   Factura:     {disc.valor_factura}", f"   Formulario:  {disc.valor_formulario}", f"   → {disc.mensaje}", ""])
            reporte.extend(["-" * 40, "ACCIÓN REQUERIDA:", "• Corrija los datos del formulario", "• Vuelva a intentar el registro", "=" * 60])
        else:
            reporte.extend(["✅ RESULTADO: APROBADO - Sin discrepancias", "• El registro puede proceder", "=" * 60])
        return "\n".join(reporte)

    def _calcular_confianza_promedio(self, factura: FacturaExtraida) -> float:
        if not factura.productos: return 0.5
        confianzas = [p.confianza for p in factura.productos]
        confianza_productos = sum(confianzas) / len(confianzas)
        campos_principales = sum([bool(factura.numero_factura), bool(factura.nombre_proveedor), bool(factura.fecha_factura)]) / 3.0
        return (confianza_productos * 0.6) + (campos_principales * 0.4)

    def _limpiar_texto(self, texto: str) -> str:
        lineas = [linea.strip() for linea in texto.split('\n') if linea.strip()]
        return '\n'.join(lineas)

    def _normalizar_texto(self, texto: str) -> str:
        if not texto: return ""
        texto = texto.upper().strip()
        for char in '.,;:!?': texto = texto.replace(char, '')
        return ' '.join(texto.split())

    def _calcular_similitud(self, s1: str, s2: str) -> float:
        if not s1 or not s2: return 0.0
        if s1 == s2: return 1.0
        len1, len2 = len(s1), len(s2)
        max_len = max(len1, len2)
        if max_len == 0: return 1.0
        distancia = self._levenshtein_distance(s1, s2)
        return 1.0 - (distancia / max_len)

    def _levenshtein_distance(self, s1: str, s2: str) -> int:
        if len(s1) < len(s2): return self._levenshtein_distance(s2, s1)
        if len(s2) == 0: return len(s1)
        previous_row = range(len(s2) + 1)
        for i, c1 in enumerate(s1):
            current_row = [i + 1]
            for j, c2 in enumerate(s2):
                insertions = previous_row[j + 1] + 1
                deletions = current_row[j] + 1
                substitutions = previous_row[j] + (c1 != c2)
                current_row.append(min(insertions, deletions, substitutions))
            previous_row = current_row
        return previous_row[-1]

    def _parsear_monto(self, texto: str) -> float:
        if not texto: return 0.0
        texto = texto.replace(',', '.')
        numeros = re.findall(r'\d+\.?\d*', texto)
        return float(numeros[0]) if numeros else 0.0

    def obtener_estadisticas(self) -> Dict:
        if not self.historial_verificaciones: return {"total_verificaciones": 0, "tasa_exito": 0.0}
        total = len(self.historial_verificaciones)
        exitosos = sum(1 for r in self.historial_verificaciones if r.exito)
        return {"total_verificaciones": total, "tasa_exito": exitosos / total, "verificaciones_exitosas": exitosos, "verificaciones_fallidas": total - exitosos}

    def limpiar_cache(self, max_edad_horas: int = 24) -> int:
        ahora = datetime.now()
        eliminados = 0
        for id_cache in list(self.cache_facturas.keys()):
            factura = self.cache_facturas[id_cache]
            timestamp_str = factura.metadatos_extraccion.get('timestamp', '')
            if timestamp_str:
                try:
                    timestamp = datetime.fromisoformat(timestamp_str)
                    edad_horas = (ahora - timestamp).total_seconds() / 3600
                    if edad_horas > max_edad_horas:
                        del self.cache_facturas[id_cache]
                        eliminados += 1
                except: pass
        return eliminados

    def exportar_historial(self, ruta: str) -> bool:
        try:
            datos = [{"exito": r.exito, "timestamp": r.timestamp, "hash": r.hash_verificacion, "num_discrepancias": len(r.discrepancias), "datos_formulario": r.datos_formulario} for r in self.historial_verificaciones]
            with open(ruta, 'w', encoding='utf-8') as f:
                json.dump(datos, f, indent=2, default=str)
            return True
        except Exception as e:
            logger.error(f"Error exportando historial: {e}")
            return False
