#!/usr/bin/env python3
"""
Versión de demostración del microservicio IA Recepción
Funciona sin Tesseract OCR para pruebas de integración
"""

import os
import sys
import json
import base64
import logging
from datetime import datetime
from pathlib import Path

# Agregar rutas del proyecto
sys.path.append(os.path.dirname(os.path.abspath(__file__)))
sys.path.append(os.path.join(os.path.dirname(os.path.abspath(__file__)), 'modelo'))

try:
    from fastapi import FastAPI, HTTPException, UploadFile, File, Form
    from fastapi.middleware.cors import CORSMiddleware
    from fastapi.responses import JSONResponse
    from pydantic import BaseModel, Field
    import uvicorn
    DEPENDENCIAS_OK = True
except ImportError as e:
    print(f"Error importando dependencias: {e}")
    print("Ejecuta: pip install fastapi uvicorn python-multipart pydantic")
    DEPENDENCIAS_OK = False

# Configuración de logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('logs/demo_server.log', encoding='utf-8'),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger(__name__)

# Crear directorio de logs
Path('logs').mkdir(exist_ok=True)

# Modelos de datos
class FacturaInfo(BaseModel):
    numero_factura: str = Field(default="", description="Número de factura")
    nombre_proveedor: str = Field(default="", description="Nombre del proveedor")
    productos: list = Field(default_factory=list, description="Lista de productos")
    confianza_general: float = Field(default=0.0, description="Confianza general del OCR")

class VerificacionRequest(BaseModel):
    datos_formulario: dict = Field(..., description="Datos del formulario PHP")
    datos_factura: FacturaInfo = Field(..., description="Datos extraídos de la factura")

class VerificacionResponse(BaseModel):
    es_coherente: bool = Field(..., description="Si los datos son coherentes")
    discrepancias: list = Field(default_factory=list, description="Lista de discrepancias")
    accion_recomendada: str = Field(..., description="Acción recomendada")
    mensaje: str = Field(..., description="Mensaje explicativo")

# Crear aplicación FastAPI
if DEPENDENCIAS_OK:
    app = FastAPI(
        title="Microservicio IA Recepción - Demo",
        description="Versión de demostración sin Tesseract OCR",
        version="1.0.0-demo"
    )

    # Configurar CORS
    app.add_middleware(
        CORSMiddleware,
        allow_origins=["*"],
        allow_credentials=True,
        allow_methods=["*"],
        allow_headers=["*"],
    )

def generar_datos_simulados() -> FacturaInfo:
    """Genera datos simulados de factura para demostración"""
    return FacturaInfo(
        numero_factura=f"FAC-{datetime.now().strftime('%Y%m%d')}-001",
        nombre_proveedor="DEMO SUPPLIERS S.A.",
        productos=[
            {
                "nombre": "Producto Demo 1",
                "modelo": "MD-001",
                "marca": "DEMOBRAND",
                "serial": f"SN-{datetime.now().strftime('%Y%m%H%M%S')}",
                "costo": 150.50,
                "cantidad": 5
            },
            {
                "nombre": "Producto Demo 2", 
                "modelo": "MD-002",
                "marca": "DEMOBRAND",
                "serial": f"SN-{datetime.now().strftime('%Y%m%H%M%S')}-2",
                "costo": 89.99,
                "cantidad": 3
            }
        ],
        confianza_general=0.85
    )

def verificar_coherencia_demo(datos_formulario: dict, datos_factura: FacturaInfo) -> VerificacionResponse:
    """Función de demostración para verificación de coherencia"""
    
    discrepancias = []
    
    # Simular algunas verificaciones
    if 'correlativo' in datos_formulario:
        correlativo_form = datos_formulario['correlativo']
        if correlativo_form != datos_factura.numero_factura[-6:]:  # Comparar últimos 6 dígitos
            discrepancias.append({
                "campo": "Número de Factura",
                "valor_factura": datos_factura.numero_factura,
                "valor_formulario": correlativo_form,
                "gravedad": "alta"
            })
    
    # Simular verificación de proveedor
    if 'proveedor' in datos_formulario:
        # En modo demo, asumimos que siempre hay coincidencia parcial
        if len(datos_factura.nombre_proveedor) > 10:
            discrepancias.append({
                "campo": "Nombre del Proveedor",
                "valor_factura": datos_factura.nombre_proveedor,
                "valor_formulario": "Proveedor seleccionado del formulario",
                "gravedad": "media"
            })
    
    # Determinar acción recomendada
    if len(discrepancias) == 0:
        accion = "aprobar"
        mensaje = "Todos los datos son coherentes. Puede proceder con el registro."
        es_coherente = True
    elif len(discrepancias) == 1:
        accion = "advertencia"
        mensaje = "Se detectó una discrepancia menor. Revise los datos antes de continuar."
        es_coherente = True
    else:
        accion = "bloquear"
        mensaje = "Se detectaron múltiples discrepancias. Corrija los datos antes de continuar."
        es_coherente = False
    
    return VerificacionResponse(
        es_coherente=es_coherente,
        discrepancias=discrepancias,
        accion_recomendada=accion,
        mensaje=mensaje
    )

if DEPENDENCIAS_OK:
    @app.get("/")
    async def root():
        """Endpoint raíz - estado del servicio"""
        return {
            "servicio": "Microservicio IA Recepción - Demo",
            "estado": "operativo (modo demo)",
            "version": "1.0.0-demo",
            "tesseract": "no disponible (modo demo)",
            "timestamp": datetime.now().isoformat()
        }

    @app.get("/health")
    async def health_check():
        """Endpoint de health check"""
        return {
            "status": "healthy",
            "mode": "demo",
            "dependencies": {
                "fastapi": True,
                "pydantic": True,
                "tesseract": False
            }
        }

    @app.post("/procesar_factura", response_model=FacturaInfo)
    async def procesar_factura_demo(
        imagen: UploadFile = File(..., description="Imagen de la factura"),
        idioma: str = Form(default="spa", description="Idioma del OCR")
    ):
        """
        Procesa una imagen de factura (modo demo)
        En modo real, esto usaría Tesseract OCR
        """
        try:
            # Validar archivo
            if not imagen.content_type.startswith('image/'):
                raise HTTPException(status_code=400, detail="El archivo debe ser una imagen")
            
            # Leer imagen (solo para validación)
            contenido = await imagen.read()
            if len(contenido) > 10 * 1024 * 1024:  # 10MB
                raise HTTPException(status_code=400, detail="El archivo es demasiado grande")
            
            logger.info(f"Procesando imagen demo: {imagen.filename} ({len(contenido)} bytes)")
            
            # Generar datos simulados
            resultado = generar_datos_simulados()
            
            logger.info(f"Datos simulados generados: {resultado.numero_factura}")
            
            return resultado
            
        except Exception as e:
            logger.error(f"Error procesando factura: {str(e)}")
            raise HTTPException(status_code=500, detail=f"Error procesando la factura: {str(e)}")

    @app.post("/verificar_coherencia", response_model=VerificacionResponse)
    async def verificar_coherencia_endpoint(request: VerificacionRequest):
        """
        Verifica la coherencia entre datos del formulario y de la factura
        """
        try:
            logger.info("Verificando coherencia (modo demo)")
            
            resultado = verificar_coherencia_demo(
                request.datos_formulario,
                request.datos_factura
            )
            
            logger.info(f"Resultado verificación: {resultado.accion_recomendada}")
            
            return resultado
            
        except Exception as e:
            logger.error(f"Error en verificación: {str(e)}")
            raise HTTPException(status_code=500, detail=f"Error en verificación: {str(e)}")

    @app.post("/procesar_y_verificar")
    async def procesar_y_verificar_demo(
        imagen: UploadFile = File(...),
        datos_formulario: str = Form(..., description="Datos del formulario en JSON"),
        idioma: str = Form(default="spa")
    ):
        """
        Procesa factura y verifica coherencia en un solo paso
        """
        try:
            # Procesar imagen
            datos_factura = await procesar_factura_demo(imagen, idioma)
            
            # Parsear datos del formulario
            formulario_dict = json.loads(datos_formulario)
            
            # Verificar coherencia
            verificacion = verificar_coherencia_demo(formulario_dict, datos_factura)
            
            return {
                "datos_factura": datos_factura.dict(),
                "verificacion": verificacion.dict(),
                "modo": "demo"
            }
            
        except Exception as e:
            logger.error(f"Error en proceso completo: {str(e)}")
            raise HTTPException(status_code=500, detail=f"Error: {str(e)}")

def main():
    """Función principal para iniciar el servidor"""
    if not DEPENDENCIAS_OK:
        print("Error: Dependencias no instaladas")
        print("Ejecuta: pip install fastapi uvicorn python-multipart pydantic")
        return
    
    print("=" * 60)
    print("Microservicio IA Recepción - MODO DEMOSTRACIÓN")
    print("=" * 60)
    print("Este es un modo de demostración que funciona sin Tesseract OCR")
    print("Para funcionalidad completa, instale Tesseract OCR")
    print("Ver: INSTALAR_TESSERACT.md")
    print("=" * 60)
    print()
    print("Iniciando servidor en http://localhost:8000")
    print("Presiona Ctrl+C para detener")
    print()
    
    try:
        uvicorn.run(
            "demo_server:app",
            host="0.0.0.0",
            port=8000,
            reload=True,
            log_level="info"
        )
    except KeyboardInterrupt:
        print("\nServidor detenido por el usuario")
    except Exception as e:
        print(f"\nError iniciando servidor: {e}")

if __name__ == "__main__":
    main()
