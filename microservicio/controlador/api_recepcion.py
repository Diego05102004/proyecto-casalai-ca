"""API REST FastAPI - Fase 1: Verificación Asistida."""
from fastapi import FastAPI, UploadFile, File, HTTPException
from pydantic import BaseModel
from pathlib import Path
import json
import shutil

# Importación de tus módulos existentes y nuevos
from ..modelo.auditor_recepcion import AuditorRecepcion
from ..modelo.model_registry import ModelRegistry
from ..modelo.logger import logger

app = FastAPI(
    title="Microservicio de Auditoría y Recepción - Casa Lai, C.A.",
    description="API unificada para procesamiento OCR, reglas de auditoría, feedback y control de modelos.",
    version="1.2.0"
)

# Inicialización de componentes principales
registry = ModelRegistry()
auditor = AuditorRecepcion()

# Directorios de soporte
FEEDBACK_DIR = Path("data/feedback")
FEEDBACK_DIR.mkdir(parents=True, exist_ok=True)
FEEDBACK_FILE = FEEDBACK_DIR / "feedback_dataset.jsonl"

class FeedbackPayload(BaseModel):
    documento_id: str
    prediccion_ocr: dict
    ground_truth: dict
    ruta_archivo: str

@app.on_event("startup")
def startup_event():
    logger.info("Iniciando microservicio de recepción y auditoría Casa Lai, C.A...")

@app.get("/")
def read_root():
    active_v, info = registry.get_active_model_info()
    logger.info(f"Consulta de estado general. Versión activa del modelo: {active_v}")
    return {
        "status": "online",
        "service": "Auditoría Casa Lai, C.A.",
        "active_version": active_v,
        "model_metrics": info["metrics"]
    }

@app.post("/api/v1/auditar")
async def auditar_documento(file: UploadFile = File(...)):
    """
    Endpoint principal para recibir facturas/documentos, 
    procesarlos mediante OCR y aplicar las reglas de auditoría.
    """
    temp_dir = Path("temp")
    temp_dir.mkdir(exist_ok=True)
    file_path = temp_dir / file.filename
    
    logger.info(f"Recibiendo archivo para auditoría: {file.filename}")
    
    with open(file_path, "wb") as buffer:
        shutil.copyfileobj(file.file, buffer)
    
    try:
        active_v, _ = registry.get_active_model_info()
        
        # Ejecución del motor de auditoría existente en auditor_recepcion.py
        resultado = auditor.procesar_documento(str(file_path))
        
        logger.info(f"Documento {file.filename} procesado exitosamente usando el modelo {active_v}")
        return {
            "status": "success",
            "version_modelo": active_v,
            "resultado_auditoria": resultado
        }
    except Exception as e:
        logger.error(f"Error crítico procesando el archivo {file.filename}: {str(e)}")
        raise HTTPException(status_code=500, detail=f"Error procesando documento: {str(e)}")
    finally:
        # Limpieza del archivo temporal
        if file_path.exists():
            file_path.unlink()

@app.post("/api/v1/feedback")
def registrar_feedback(payload: FeedbackPayload):
    """
    Registra las correcciones manuales del operador (Ground Truth) 
    para alimentar el pipeline de reentrenamiento periódico.
    """
    try:
        registro = payload.dict()
        with open(FEEDBACK_FILE, "a", encoding="utf-8") as f:
            f.write(json.dumps(registro, ensure_ascii=False) + "\n")
        
        logger.info(f"Feedback registrado correctamente para el documento ID: {payload.documento_id}")
        return {
            "status": "success",
            "message": "Feedback almacenado correctamente para el pipeline de reentrenamiento."
        }
    except Exception as e:
        logger.error(f"Error al almacenar el feedback: {str(e)}")
        raise HTTPException(status_code=500, detail=f"No se pudo guardar el feedback: {str(e)}")

@app.post("/api/v1/modelo/recargar")
def recargar_modelo():
    """
    Permite sincronizar la API con la versión más reciente del modelo 
    registrada en disco sin necesidad de reiniciar el contenedor o servidor.
    """
    try:
        active_v, info = registry.get_active_model_info()
        logger.info(f"Modelo recargado en caliente de forma exitosa. Versión activa: {active_v}")
        return {
            "status": "success",
            "active_version": active_v,
            "detalles": info,
            "message": "Modelo recargado exitosamente en caliente."
        }
    except Exception as e:
        logger.error(f"Error al intentar recargar el modelo: {str(e)}")
        raise HTTPException(status_code=500, detail=f"Error al recargar modelo: {str(e)}")

"""import sys, os
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

import json
import uuid
from datetime import datetime
from pathlib import Path
from typing import Dict, List, Optional, Any
from contextlib import asynccontextmanager

from fastapi import FastAPI, File, UploadFile, Form, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
import uvicorn

from modelo.auditor_recepcion import AuditorRecepcion
from controlador.api_analitica import router as analitica_router

BASE_DIR = Path(__file__).parent.parent
UPLOAD_DIR = BASE_DIR / "temp_uploads"
for dir_path in [UPLOAD_DIR, BASE_DIR / "factura_cache", BASE_DIR / "logs_metricas"]:
    dir_path.mkdir(parents=True, exist_ok=True)
MAX_UPLOAD_SIZE = 5 * 1024 * 1024
ALLOWED_EXTENSIONS = {".pdf", ".png", ".jpg", ".jpeg", ".webp", ".bmp", ".tif", ".tiff"}

async def guardar_archivo_temporal(imagen: UploadFile, factura_id: str) -> Path:
    extension = Path(imagen.filename or "").suffix.lower()
    if extension not in ALLOWED_EXTENSIONS:
        raise HTTPException(status_code=400, detail="Formato no permitido. Use PDF o una imagen.")

    contenido = await imagen.read(MAX_UPLOAD_SIZE + 1)
    if len(contenido) > MAX_UPLOAD_SIZE:
        raise HTTPException(status_code=413, detail="El archivo debe ser menor a 5 MB.")

    file_path = UPLOAD_DIR / f"{factura_id}{extension}"
    file_path.write_bytes(contenido)
    return file_path

auditor: Optional[AuditorRecepcion] = None

@asynccontextmanager
async def lifespan(app: FastAPI):
    global auditor
    auditor = AuditorRecepcion()
    yield

app = FastAPI(title="Casalai AI - Microservicio Recepción", version="1.0.0", lifespan=lifespan)
app.add_middleware(CORSMiddleware, allow_origins=["*"], allow_credentials=True, allow_methods=["*"], allow_headers=["*"])
app.include_router(analitica_router)

class ProductoFormulario(BaseModel):
    nombre: str
    modelo: Optional[str] = ""
    marca: Optional[str] = ""
    serial: Optional[str] = ""
    cantidad: int = 1
    costo: float = 0.0

class DatosRecepcion(BaseModel):
    numero_factura: str
    nombre_proveedor: str
    productos: List[ProductoFormulario]
    fecha_recepcion: Optional[str] = ""

class VerificacionRequest(BaseModel):
    factura_id: str
    datos_formulario: DatosRecepcion

class DiscrepanciaResponse(BaseModel):
    campo: str
    valor_factura: str
    valor_formulario: str
    severidad: str
    mensaje: str

class VerificacionResponse(BaseModel):
    exito: bool
    discrepancias: List[DiscrepanciaResponse]
    reporte_detallado: str
    hash_verificacion: str
    timestamp: str
    mensaje: str

class ExtraccionResponse(BaseModel):
    exito: bool
    factura_id: str
    numero_factura: str
    nombre_proveedor: str
    fecha_factura: str
    subtotal_factura: float
    porcentaje_iva: float
    monto_iva: float
    total_factura: float
    productos: List[Dict]
    confianza_promedio: float
    mensaje: str

@app.get("/")
async def root():
    return {"status": "operativo", "fase": "Fase 1: Verificación Asistida", "version": "1.0.0"}

@app.get("/health")
async def health_check():
    return {"status": "ok", "auditor_listo": auditor is not None}

@app.post("/fase1/extraer", response_model=ExtraccionResponse)
async def extraer_factura(imagen: UploadFile = File(...)):
    if not auditor: raise HTTPException(status_code=503, detail="Servicio no disponible")
    factura_id = f"fact_{datetime.now().strftime('%Y%m%d%H%M%S')}_{uuid.uuid4().hex[:8]}"
    file_path = await guardar_archivo_temporal(imagen, factura_id)
    try:
        factura = auditor.extraer_desde_imagen(str(file_path), factura_id)
        productos_list = [{"nombre": p.nombre, "modelo": p.modelo, "marca": p.marca, "serial": p.serial, "cantidad": p.cantidad, "costo_unitario": p.costo_unitario, "confianza": p.confianza} for p in factura.productos]
        confianza = factura.metadatos_extraccion.get('confianza_promedio', 0.0)
        return ExtraccionResponse(exito=True, factura_id=factura_id, numero_factura=factura.numero_factura, nombre_proveedor=factura.nombre_proveedor,
         fecha_factura=factura.fecha_factura, subtotal_factura=factura.subtotal_factura, porcentaje_iva=factura.porcentaje_iva, monto_iva=factura.monto_iva, total_factura=factura.total_factura, 
         productos=productos_list, confianza_promedio=confianza, mensaje=f"Extracción completada. ID: {factura_id}")
    except Exception as e: raise HTTPException(status_code=500, detail=f"Error: {str(e)}")
    finally:
        await imagen.close()
        file_path.unlink(missing_ok=True)

@app.post("/fase1/verificar", response_model=VerificacionResponse)
async def verificar_datos(request: VerificacionRequest):
    if not auditor: raise HTTPException(status_code=503, detail="Servicio no disponible")
    datos_dict = request.datos_formulario.dict()
    datos_dict['productos'] = [p.dict() for p in request.datos_formulario.productos]
    resultado = auditor.verificar_coherencia(request.factura_id, datos_dict)
    discrepancias_resp = [DiscrepanciaResponse(campo=d.campo, valor_factura=d.valor_factura, valor_formulario=d.valor_formulario, severidad=d.severidad, mensaje=d.mensaje) for d in resultado.discrepancias]
    mensaje = "✅ Verificación exitosa" if resultado.exito else f"⚠️ {len(discrepancias_resp)} discrepancias encontradas"
    return VerificacionResponse(exito=resultado.exito, discrepancias=discrepancias_resp, reporte_detallado=resultado.reporte_detallado, hash_verificacion=resultado.hash_verificacion,
     timestamp=resultado.timestamp, mensaje=mensaje)

@app.post("/fase1/comparar-directo")
async def comparar_directo(imagen: UploadFile = File(...), datos_json: str = Form(...)):
    if not auditor: raise HTTPException(status_code=503, detail="Servicio no disponible")
    try: datos_formulario = json.loads(datos_json)
    except: raise HTTPException(status_code=400, detail="JSON inválido")
    temp_id = f"direct_{datetime.now().strftime('%Y%m%d%H%M%S')}_{uuid.uuid4().hex[:8]}"
    file_path = await guardar_archivo_temporal(imagen, temp_id)
    try:
        resultado = auditor.comparar_directo(str(file_path), datos_formulario)
        discrepancias_resp = [DiscrepanciaResponse(campo=d.campo, valor_factura=d.valor_factura, valor_formulario=d.valor_formulario, severidad=d.severidad, mensaje=d.mensaje) for d in resultado.discrepancias]
        return {"exito": resultado.exito, "discrepancias": [d.dict() for d in discrepancias_resp], "reporte": resultado.reporte_detallado, "mensaje": "OK" if resultado.exito else "Discrepancias encontradas"}
    finally:
        await imagen.close()
        file_path.unlink(missing_ok=True)

@app.get("/fase1/cache/{factura_id}")
async def obtener_cache(factura_id: str):
    if not auditor or factura_id not in auditor.cache_facturas: raise HTTPException(status_code=404, detail="No encontrado")
    f = auditor.cache_facturas[factura_id]
    return {"factura_id": factura_id, "numero": f.numero_factura, "proveedor": f.nombre_proveedor, "productos": len(f.productos)}

@app.get("/fase1/estadisticas")
async def obtener_estadisticas():
    if not auditor: raise HTTPException(status_code=503, detail="No disponible")
    stats = auditor.obtener_estadisticas()
    return {"total_verificaciones": stats.get("total_verificaciones", 0), "tasa_exito": stats.get("tasa_exito", 0.0)}

if __name__ == "__main__":
    uvicorn.run(app, host="127.0.0.1", port=8000)"""
