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