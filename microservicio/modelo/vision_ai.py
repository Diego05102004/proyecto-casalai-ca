"""
Modelo de Visión Artificial para el Microservicio de Recepción
Implementa OCR contextualizado y extracción de información de facturas
"""

import cv2
import numpy as np
import pytesseract
import re
from PIL import Image
import io
import base64
from typing import Dict, List, Optional, Tuple
from dataclasses import dataclass
import logging

# Configuración de logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

@dataclass
class DatosFactura:
    """Estructura de datos para información extraída de factura"""
    numero_factura: str
    nombre_proveedor: str
    productos: List[Dict[str, str]]
    confianza_general: float

@dataclass
class ProductoExtraido:
    """Estructura para información de producto extraído"""
    nombre: str
    modelo: str
    marca: str
    serial: str
    costo: float
    cantidad: int
    confianza: float

class ProcesadorFacturas:
    """Clase principal para procesamiento de facturas con IA"""
    
    def __init__(self):
        # Configuración de Tesseract para español
        self.tesseract_config = r'--oem 3 --psm 6 -l spa'
        
        # Patrones de regex para extracción
        self.patrones = {
            'numero_factura': [
                r'(?i)factura\s*[:#]?\s*(\w+[\w-]*)',
                r'(?i)n[°º]\s*factura\s*[:#]?\s*(\w+[\w-]*)',
                r'(?i)invoice\s*[:#]?\s*(\w+[\w-]*)',
                r'^(\w{3,}[-\d]{3,})'  # Patrones comunes de facturas
            ],
            'proveedor': [
                r'(?i)(?:proveedor|empresa|vendor)\s*[:#]?\s*([A-Za-zÑñÁáÉéÍíÓóÚú\s]{2,50})',
                r'^([A-Za-zÑñÁáÉéÍíÓóÚú\s]{2,50})\s*(?:RUC|NIT|CC)',
            ],
            'producto': [
                r'(?i)(?:producto|item|artículo)\s*[:#]?\s*([A-Za-z0-9ÑñÁáÉéÍíÓóÚú\s\-_]{2,100})',
            ],
            'modelo': [
                r'(?i)(?:modelo|model)\s*[:#]?\s*([A-Za-z0-9\-_\.]{2,30})',
            ],
            'marca': [
                r'(?i)(?:marca|brand)\s*[:#]?\s*([A-Za-z0-9\-_\.]{2,30})',
            ],
            'serial': [
                r'(?i)(?:serial|s/n|sn)\s*[:#]?\s*([A-Za-z0-9\-_]{5,30})',
            ],
            'costo': [
                r'(?i)(?:costo|precio|price|valor)\s*[:#]?\s*\$?\s*([\d,]+\.?\d*)',
                r'\$?\s*([\d,]+\.?\d*)\s*(?:USD|COP|EUR|MXN)?',
            ],
            'cantidad': [
                r'(?i)(?:cantidad|qty|quantity)\s*[:#]?\s*(\d+)',
            ]
        }
    
    def preprocesar_imagen(self, imagen_bytes: bytes) -> np.ndarray:
        """
        Preprocesa la imagen para mejorar la precisión del OCR
        """
        try:
            # Convertir bytes a array numpy
            nparr = np.frombuffer(imagen_bytes, np.uint8)
            img = cv2.imdecode(nparr, cv2.IMREAD_COLOR)
            
            if img is None:
                raise ValueError("No se pudo decodificar la imagen")
            
            # Convertir a escala de grises
            gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
            
            # Reducir ruido
            denoised = cv2.fastNlMeansDenoising(gray)
            
            # Binarización adaptativa
            binary = cv2.adaptiveThreshold(
                denoised, 255, cv2.ADAPTIVE_THRESH_GAUSSIAN_C, 
                cv2.THRESH_BINARY, 11, 2
            )
            
            # Mejorar contraste
            enhanced = cv2.equalizeHist(binary)
            
            return enhanced
            
        except Exception as e:
            logger.error(f"Error en preprocesamiento de imagen: {e}")
            raise
    
    def extraer_texto_ocr(self, imagen: np.ndarray) -> str:
        """
        Extrae texto usando OCR con Tesseract
        """
        try:
            # Configuración optimizada para facturas
            custom_config = r'--oem 3 --psm 6 --dpi 300 -l spa'
            
            texto = pytesseract.image_to_string(
                imagen, 
                config=custom_config
            )
            
            return texto.strip()
            
        except Exception as e:
            logger.error(f"Error en extracción OCR: {e}")
            raise
    
    def aplicar_patrones_regex(self, texto: str) -> Dict[str, List[str]]:
        """
        Aplica patrones regex para extraer información estructurada
        """
        resultados = {}
        
        for campo, patrones in self.patrones.items():
            coincidencias = []
            for patron in patrones:
                matches = re.findall(patron, texto, re.MULTILINE | re.IGNORECASE)
                coincidencias.extend(matches)
            
            # Limpiar y deduplicar resultados
            resultados[campo] = list(set([coincidencia.strip() for coincidencia in coincidencias if coincidencia.strip()]))
        
        return resultados
    
    def extraer_productos_tabla(self, texto: str) -> List[ProductoExtraido]:
        """
        Intenta extraer productos en formato de tabla
        """
        productos = []
        
        # Dividir texto en líneas
        lineas = texto.split('\n')
        
        # Buscar patrones de productos (líneas con múltiples campos)
        for linea in lineas:
            linea = linea.strip()
            if len(linea) > 20:  # Líneas con contenido significativo
                
                # Intentar extraer información de producto
                producto = self._procesar_linea_producto(linea)
                if producto:
                    productos.append(producto)
        
        return productos
    
    def _procesar_linea_producto(self, linea: str) -> Optional[ProductoExtraido]:
        """
        Procesa una línea de texto para extraer información de producto
        """
        try:
            # Patrones para detectar información de producto en una línea
            patron_producto = r'([A-Za-z0-9\s]{5,50})\s+([A-Za-z0-9\-_]{2,20})\s+([A-Za-z0-9\-_]{2,20})\s+([A-Za-z0-9\-_]{5,20})\s+\$?([\d,]+\.?\d*)\s+(\d+)'
            
            match = re.match(patron_producto, linea)
            if match:
                return ProductoExtraido(
                    nombre=match.group(1).strip(),
                    modelo=match.group(2).strip(),
                    marca=match.group(3).strip(),
                    serial=match.group(4).strip(),
                    costo=float(match.group(5).replace(',', '')),
                    cantidad=int(match.group(6)),
                    confianza=0.8  # Confianza base para patrones estructurados
                )
            
            return None
            
        except Exception as e:
            logger.warning(f"Error procesando línea de producto: {e}")
            return None
    
    def calcular_confianza(self, datos_extraidos: Dict) -> float:
        """
        Calcula una puntuación de confianza para los datos extraídos
        """
        factores = []
        
        # Confianza por número de factura encontrado
        if datos_extraidos.get('numero_factura'):
            factores.append(0.9)
        else:
            factores.append(0.3)
        
        # Confianza por proveedor identificado
        if datos_extraidos.get('nombre_proveedor'):
            factores.append(0.8)
        else:
            factores.append(0.4)
        
        # Confianza por productos extraídos
        productos = datos_extraidos.get('productos', [])
        if len(productos) > 0:
            factores.append(min(0.9, 0.5 + (len(productos) * 0.1)))
        else:
            factores.append(0.2)
        
        # Promedio de factores
        confianza_total = sum(factores) / len(factores)
        
        return round(confianza_total, 2)
    
    def procesar_factura(self, imagen_bytes: bytes) -> DatosFactura:
        """
        Método principal para procesar una factura y extraer información
        """
        try:
            # 1. Preprocesar imagen
            imagen_procesada = self.preprocesar_imagen(imagen_bytes)
            
            # 2. Extraer texto con OCR
            texto_extraido = self.extraer_texto_ocr(imagen_procesada)
            
            # 3. Aplicar patrones regex
            datos_estructurados = self.aplicar_patrones_regex(texto_extraido)
            
            # 4. Extraer productos
            productos = self.extraer_productos_tabla(texto_extraido)
            
            # 5. Construir resultado
            numero_factura = datos_estructurados.get('numero_factura', [''])[0]
            nombre_proveedor = datos_estructurados.get('proveedor', [''])[0]
            
            # Convertir productos a diccionarios
            productos_dict = []
            for producto in productos:
                productos_dict.append({
                    'nombre': producto.nombre,
                    'modelo': producto.modelo,
                    'marca': producto.marca,
                    'serial': producto.serial,
                    'costo': producto.costo,
                    'cantidad': producto.cantidad,
                    'confianza': producto.confianza
                })
            
            # 6. Calcular confianza general
            datos_resultado = {
                'numero_factura': numero_factura,
                'nombre_proveedor': nombre_proveedor,
                'productos': productos_dict
            }
            
            confianza_general = self.calcular_confianza(datos_resultado)
            
            resultado = DatosFactura(
                numero_factura=numero_factura,
                nombre_proveedor=nombre_proveedor,
                productos=productos_dict,
                confianza_general=confianza_general
            )
            
            logger.info(f"Factura procesada exitosamente. Confianza: {confianza_general}")
            return resultado
            
        except Exception as e:
            logger.error(f"Error procesando factura: {e}")
            raise
    
    def verificar_coherencia(self, datos_factura: DatosFactura, datos_formulario: Dict) -> Dict:
        """
        Compara datos extraídos de la factura con datos del formulario
        """
        discrepancias = []
        
        # Verificar número de factura
        if datos_factura.numero_factura and datos_formulario.get('numero_factura'):
            if not self._son_similares(datos_factura.numero_factura, datos_formulario['numero_factura']):
                discrepancias.append({
                    'campo': 'numero_factura',
                    'valor_factura': datos_factura.numero_factura,
                    'valor_formulario': datos_formulario['numero_factura'],
                    'severidad': 'alta'
                })
        
        # Verificar proveedor
        if datos_factura.nombre_proveedor and datos_formulario.get('nombre_proveedor'):
            if not self._son_similares(datos_factura.nombre_proveedor, datos_formulario['nombre_proveedor']):
                discrepancias.append({
                    'campo': 'nombre_proveedor',
                    'valor_factura': datos_factura.nombre_proveedor,
                    'valor_formulario': datos_formulario['nombre_proveedor'],
                    'severidad': 'alta'
                })
        
        # Verificar productos
        productos_formulario = datos_formulario.get('productos', [])
        for i, producto_factura in enumerate(datos_factura.productos):
            if i < len(productos_formulario):
                producto_form = productos_formulario[i]
                discrepancias_producto = self._verificar_producto(producto_factura, producto_form, i)
                discrepancias.extend(discrepancias_producto)
        
        return {
            'es_coherente': len(discrepancias) == 0,
            'discrepancias': discrepancias,
            'confianza_verificacion': max(0, datos_factura.confianza_general - (len(discrepancias) * 0.1))
        }
    
    def _son_similares(self, texto1: str, texto2: str, umbral: float = 0.8) -> bool:
        """
        Compara similitud entre dos textos
        """
        texto1_limpio = re.sub(r'[^\w]', '', texto1.lower())
        texto2_limpio = re.sub(r'[^\w]', '', texto2.lower())
        
        if texto1_limpio == texto2_limpio:
            return True
        
        # Similitud simple basada en caracteres comunes
        comunes = set(texto1_limpio) & set(texto2_limpio)
        total = set(texto1_limpio) | set(texto2_limpio)
        
        if len(total) == 0:
            return True
        
        similitud = len(comunes) / len(total)
        return similitud >= umbral
    
    def _verificar_producto(self, producto_factura: Dict, producto_form: Dict, indice: int) -> List[Dict]:
        """
        Verifica coherencia de un producto específico
        """
        discrepancias = []
        
        campos_verificar = ['nombre', 'modelo', 'marca', 'serial', 'costo', 'cantidad']
        
        for campo in campos_verificar:
            valor_factura = producto_factura.get(campo)
            valor_form = producto_form.get(campo)
            
            if valor_factura and valor_form:
                if campo in ['costo', 'cantidad']:
                    # Comparación numérica
                    try:
                        if float(valor_factura) != float(valor_form):
                            discrepancias.append({
                                'campo': f'producto_{indice}_{campo}',
                                'valor_factura': valor_factura,
                                'valor_formulario': valor_form,
                                'severidad': 'media'
                            })
                    except ValueError:
                        pass
                else:
                    # Comparación de texto
                    if not self._son_similares(str(valor_factura), str(valor_form)):
                        discrepancias.append({
                            'campo': f'producto_{indice}_{campo}',
                            'valor_factura': valor_factura,
                            'valor_formulario': valor_form,
                            'severidad': 'media'
                        })
        
        return discrepancias
