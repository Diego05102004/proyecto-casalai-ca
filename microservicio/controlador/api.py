"""
Controlador API REST con FastAPI para el Microservicio de Recepción
Expone endpoints para procesamiento de facturas con IA
"""

from fastapi import FastAPI, File, UploadFile, HTTPException, BackgroundTasks
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import JSONResponse
from pydantic import BaseModel
from typing import Dict, List, Optional
import sys
import os
import logging
import asyncio
from datetime import datetime
import traceback

# Agregar ruta del modelo al path
sys.path.append(os.path.join(os.path.dirname(__file__), '..', 'modelo'))

from vision_ai import ProcesadorFacturas, DatosFactura

# Configuración de logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

# Inicializar aplicación FastAPI
app = FastAPI(
    title="Microservicio IA Recepción",
    description="API para procesamiento inteligente de facturas en módulo de recepción",
    version="1.0.0",
    docs_url="/docs",
    redoc_url="/redoc"
)

# Configurar CORS
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # En producción, especificar dominios permitidos
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Instancia global del procesador
procesador = None

# Modelos Pydantic para validación
class ProductoFormulario(BaseModel):
    nombre: str
    modelo: Optional[str] = ""
    marca: Optional[str] = ""
    serial: Optional[str] = ""
    costo: float
    cantidad: int

class DatosFormulario(BaseModel):
    numero_factura: str
    nombre_proveedor: str
    productos: List[ProductoFormulario]

class RespuestaProcesamiento(BaseModel):
    exito: bool
    mensaje: str
    datos_factura: Optional[Dict] = None
    confianza: Optional[float] = None
    timestamp: str

class RespuestaVerificacion(BaseModel):
    exito: bool
    mensaje: str
    es_coherente: bool
    discrepancias: List[Dict]
    confianza_verificacion: float
    timestamp: str

# Eventos de la aplicación
@app.on_event("startup")
async def startup_event():
    """Inicializar el procesador de facturas al iniciar la API"""
    global procesador
    try:
        procesador = ProcesadorFacturas()
        logger.info("Microservicio IA Recepción iniciado exitosamente")
    except Exception as e:
        logger.error(f"Error iniciando el procesador: {e}")
        raise

@app.on_event("shutdown")
async def shutdown_event():
    """Limpiar recursos al cerrar la API"""
    logger.info("Microservicio IA Recepción deteniéndose")

# Endpoints de la API
@app.get("/", tags=["General"])
async def root():
    """Endpoint de bienvenida y estado del servicio"""
    return {
        "servicio": "Microservicio IA Recepción",
        "estado": "activo",
        "version": "1.0.0",
        "timestamp": datetime.now().isoformat(),
        "endpoints": {
            "procesar_factura": "/procesar-factura",
            "verificar_coherencia": "/verificar-coherencia",
            "estado": "/health",
            "documentación": "/docs"
        }
    }

@app.get("/health", tags=["General"])
async def health_check():
    """Verificar estado de salud del servicio"""
    try:
        # Verificar que el procesador esté disponible
        estado_procesador = procesador is not None
        
        return {
            "estado": "saludable" if estado_procesador else "degradado",
            "procesador_activo": estado_procesador,
            "timestamp": datetime.now().isoformat()
        }
    except Exception as e:
        logger.error(f"Error en health check: {e}")
        raise HTTPException(status_code=500, detail="Error interno del servidor")

@app.post("/procesar-factura", response_model=RespuestaProcesamiento, tags=["Procesamiento"])
async def procesar_factura(
    background_tasks: BackgroundTasks,
    archivo: UploadFile = File(..., description="Imagen de la factura en formato JPG, PNG o PDF")
):
    """
    Procesa una imagen de factura y extrae información clave usando IA
    
    - **archivo**: Imagen de la factura (JPG, PNG, PDF)
    - **retorna**: Datos extraídos con nivel de confianza
    """
    try:
        # Validar tipo de archivo
        if not archivo.content_type.startswith('image/'):
            raise HTTPException(
                status_code=400, 
                detail="El archivo debe ser una imagen (JPG, PNG, etc.)"
            )
        
        # Leer contenido del archivo
        contenido = await archivo.read()
        
        if len(contenido) > 10 * 1024 * 1024:  # 10MB límite
            raise HTTPException(
                status_code=400,
                detail="El archivo es demasiado grande (máximo 10MB)"
            )
        
        # Procesar la factura
        logger.info(f"Procesando factura: {archivo.filename}")
        
        # Ejecutar procesamiento en segundo plano para no bloquear
        resultado = await asyncio.get_event_loop().run_in_executor(
            None, procesador.procesar_factura, contenido
        )
        
        # Construir respuesta
        respuesta = RespuestaProcesamiento(
            exito=True,
            mensaje="Factura procesada exitosamente",
            datos_factura={
                "numero_factura": resultado.numero_factura,
                "nombre_proveedor": resultado.nombre_proveedor,
                "productos": resultado.productos
            },
            confianza=resultado.confianza_general,
            timestamp=datetime.now().isoformat()
        )
        
        # Agregar log en background
        background_tasks.add_task(
            logger.info, 
            f"Factura procesada: {archivo.filename}, Confianza: {resultado.confianza_general}"
        )
        
        return respuesta
        
    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Error procesando factura: {e}")
        logger.error(traceback.format_exc())
        
        return RespuestaProcesamiento(
            exito=False,
            mensaje=f"Error procesando la factura: {str(e)}",
            timestamp=datetime.now().isoformat()
        )

@app.post("/verificar-coherencia", response_model=RespuestaVerificacion, tags=["Verificación"])
async def verificar_coherencia(
    datos_formulario: DatosFormulario,
    numero_factura: Optional[str] = None,
    nombre_proveedor: Optional[str] = None,
    productos_factura: Optional[List[Dict]] = None
):
    """
    Verifica la coherencia entre datos extraídos de la factura y datos del formulario
    
    - **datos_formulario**: Datos ingresados manualmente por el usuario
    - **numero_factura**: Número de factura extraído (opcional)
    - **nombre_proveedor**: Nombre de proveedor extraído (opcional)
    - **productos_factura**: Productos extraídos de la factura (opcional)
    - **retorna**: Reporte de coherencia y discrepancias encontradas
    """
    try:
        # Construir objeto de datos de factura
        from vision_ai import DatosFactura
        
        datos_factura = DatosFactura(
            numero_factura=numero_factura or "",
            nombre_proveedor=nombre_proveedor or "",
            productos=productos_factura or [],
            confianza_general=0.8  # Valor por defecto
        )
        
        # Convertir datos del formulario a diccionario
        formulario_dict = {
            "numero_factura": datos_formulario.numero_factura,
            "nombre_proveedor": datos_formulario.nombre_proveedor,
            "productos": [
                {
                    "nombre": p.nombre,
                    "modelo": p.modelo,
                    "marca": p.marca,
                    "serial": p.serial,
                    "costo": p.costo,
                    "cantidad": p.cantidad
                }
                for p in datos_formulario.productos
            ]
        }
        
        # Realizar verificación de coherencia
        resultado_verificacion = procesador.verificar_coherencia(datos_factura, formulario_dict)
        
        # Construir respuesta
        respuesta = RespuestaVerificacion(
            exito=True,
            mensaje="Verificación de coherencia completada",
            es_coherente=resultado_verificacion['es_coherente'],
            discrepancias=resultado_verificacion['discrepancias'],
            confianza_verificacion=resultado_verificacion['confianza_verificacion'],
            timestamp=datetime.now().isoformat()
        )
        
        logger.info(f"Verificación completada. Coherente: {respuesta.es_coherente}")
        
        return respuesta
        
    except Exception as e:
        logger.error(f"Error en verificación de coherencia: {e}")
        logger.error(traceback.format_exc())
        
        raise HTTPException(
            status_code=500,
            detail=f"Error en verificación: {str(e)}"
        )

@app.post("/procesar-y-verificar", response_model=Dict, tags=["Procesamiento Integral"])
async def procesar_y_verificar(
    background_tasks: BackgroundTasks,
    archivo: UploadFile = File(...),
    datos_formulario: DatosFormulario = None
):
    """
    Endpoint combinado que procesa la factura y verifica coherencia en una sola llamada
    
    - **archivo**: Imagen de la factura
    - **datos_formulario**: Datos ingresados por el usuario
    - **retorna**: Resultado completo del procesamiento y verificación
    """
    try:
        # 1. Procesar la factura
        resultado_procesamiento = await procesar_factura(background_tasks, archivo)
        
        if not resultado_procesamiento.exito:
            return resultado_procesamiento
        
        # 2. Verificar coherencia
        resultado_verificacion = await verificar_coherencia(
            datos_formulario=datos_formulario,
            numero_factura=resultado_procesamiento.datos_factura["numero_factura"],
            nombre_proveedor=resultado_procesamiento.datos_factura["nombre_proveedor"],
            productos_factura=resultado_procesamiento.datos_factura["productos"]
        )
        
        # 3. Construir respuesta combinada
        respuesta = {
            "exito": resultado_procesamiento.exito and resultado_verificacion.exito,
            "mensaje": "Procesamiento y verificación completados",
            "procesamiento": resultado_procesamiento.dict(),
            "verificacion": resultado_verificacion.dict(),
            "accion_recomendada": _determinar_accion(resultado_verificacion),
            "timestamp": datetime.now().isoformat()
        }
        
        return respuesta
        
    except Exception as e:
        logger.error(f"Error en procesamiento integral: {e}")
        raise HTTPException(
            status_code=500,
            detail=f"Error en procesamiento integral: {str(e)}"
        )

def _determinar_accion(verificacion: RespuestaVerificacion) -> str:
    """
    Determina la acción recomendada basada en los resultados de verificación
    """
    if verificacion.es_coherente:
        return "aprobar"
    
    # Contar discrepancias por severidad
    discrepancias_altas = len([d for d in verificacion.discrepancias if d['severidad'] == 'alta'])
    discrepancias_medias = len([d for d in verificacion.discrepancias if d['severidad'] == 'media'])
    
    if discrepancias_altas > 0:
        return "bloquear"
    elif discrepancias_medias > 2:
        return "requiere_revision"
    else:
        return "advertencia"

# Middleware para logging de solicitudes
@app.middleware("http")
async def log_requests(request, call_next):
    """Middleware para logging de todas las solicitudes"""
    start_time = datetime.now()
    
    response = await call_next(request)
    
    process_time = (datetime.now() - start_time).total_seconds()
    
    logger.info(
        f"{request.method} {request.url.path} - "
        f"Status: {response.status_code} - "
        f"Time: {process_time:.3f}s"
    )
    
    return response

# Manejadores de excepciones
@app.exception_handler(404)
async def not_found_handler(request, exc):
    """Manejador para rutas no encontradas"""
    return JSONResponse(
        status_code=404,
        content={
            "error": "Endpoint no encontrado",
            "mensaje": f"La ruta {request.url.path} no existe",
            "timestamp": datetime.now().isoformat()
        }
    )

@app.exception_handler(500)
async def internal_error_handler(request, exc):
    """Manejador para errores internos del servidor"""
    logger.error(f"Error interno: {exc}")
    return JSONResponse(
        status_code=500,
        content={
            "error": "Error interno del servidor",
            "mensaje": "Ocurrió un error inesperado",
            "timestamp": datetime.now().isoformat()
        }
    )

if __name__ == "__main__":
    import uvicorn
    
    # Configuración para desarrollo
    uvicorn.run(
        "api:app",
        host="0.0.0.0",
        port=8000,
        reload=True,
        log_level="info"
    )
