"""API REST FastAPI - Fase 1: Verificación Asistida."""
import sys, os
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

import json, shutil, hashlib
from datetime import datetime
from pathlib import Path
from typing import Dict, List, Optional, Any
from contextlib import asynccontextmanager

from fastapi import FastAPI, File, UploadFile, Form, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
import uvicorn

from modelo.auditor_recepcion import AuditorRecepcion

BASE_DIR = Path(__file__).parent.parent
UPLOAD_DIR = BASE_DIR / "temp_uploads"
for dir_path in [UPLOAD_DIR, BASE_DIR / "factura_cache", BASE_DIR / "logs_metricas"]:
    dir_path.mkdir(parents=True, exist_ok=True)

auditor: Optional[AuditorRecepcion] = None

@asynccontextmanager
async def lifespan(app: FastAPI):
    global auditor
    auditor = AuditorRecepcion()
    yield

app = FastAPI(title="Casalai AI - Microservicio Recepción", version="1.0.0", lifespan=lifespan)
app.add_middleware(CORSMiddleware, allow_origins=["*"], allow_credentials=True, allow_methods=["*"], allow_headers=["*"])

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
    factura_id = f"fact_{datetime.now().strftime('%Y%m%d%H%M%S')}_{hashlib.md5(imagen.filename.encode()).hexdigest()[:8]}"
    file_path = UPLOAD_DIR / f"{factura_id}_{imagen.filename}"
    try:
        with open(file_path, "wb") as buffer: shutil.copyfileobj(imagen.file, buffer)
        factura = auditor.extraer_desde_imagen(str(file_path), factura_id)
        productos_list = [{"nombre": p.nombre, "modelo": p.modelo, "marca": p.marca, "serial": p.serial, "cantidad": p.cantidad, "costo_unitario": p.costo_unitario, "confianza": p.confianza} for p in factura.productos]
        confianza = factura.metadatos_extraccion.get('confianza_promedio', 0.0)
        return ExtraccionResponse(exito=True, factura_id=factura_id, numero_factura=factura.numero_factura, nombre_proveedor=factura.nombre_proveedor, fecha_factura=factura.fecha_factura, total_factura=factura.total_factura, productos=productos_list, confianza_promedio=confianza, mensaje=f"Extracción completada. ID: {factura_id}")
    except Exception as e: raise HTTPException(status_code=500, detail=f"Error: {str(e)}")
    finally: imagen.file.close()

@app.post("/fase1/verificar", response_model=VerificacionResponse)
async def verificar_datos(request: VerificacionRequest):
    if not auditor: raise HTTPException(status_code=503, detail="Servicio no disponible")
    datos_dict = request.datos_formulario.dict()
    datos_dict['productos'] = [p.dict() for p in request.datos_formulario.productos]
    resultado = auditor.verificar_coherencia(request.factura_id, datos_dict)
    discrepancias_resp = [DiscrepanciaResponse(campo=d.campo, valor_factura=d.valor_factura, valor_formulario=d.valor_formulario, severidad=d.severidad, mensaje=d.mensaje) for d in resultado.discrepancias]
    mensaje = "✅ Verificación exitosa" if resultado.exito else f"⚠️ {len(discrepancias_resp)} discrepancias encontradas"
    return VerificacionResponse(exito=resultado.exito, discrepancias=discrepancias_resp, reporte_detallado=resultado.reporte_detallado, hash_verificacion=resultado.hash_verificacion, timestamp=resultado.timestamp, mensaje=mensaje)

@app.post("/fase1/comparar-directo")
async def comparar_directo(imagen: UploadFile = File(...), datos_json: str = Form(...)):
    if not auditor: raise HTTPException(status_code=503, detail="Servicio no disponible")
    try: datos_formulario = json.loads(datos_json)
    except: raise HTTPException(status_code=400, detail="JSON inválido")
    temp_id = f"direct_{datetime.now().strftime('%Y%m%d%H%M%S')}_{hashlib.md5(imagen.filename.encode()).hexdigest()[:8]}"
    file_path = UPLOAD_DIR / f"{temp_id}_{imagen.filename}"
    try:
        with open(file_path, "wb") as buffer: shutil.copyfileobj(imagen.file, buffer)
        resultado = auditor.comparar_directo(str(file_path), datos_formulario)
        discrepancias_resp = [DiscrepanciaResponse(campo=d.campo, valor_factura=d.valor_factura, valor_formulario=d.valor_formulario, severidad=d.severidad, mensaje=d.mensaje) for d in resultado.discrepancias]
        return {"exito": resultado.exito, "discrepancias": [d.dict() for d in discrepancias_resp], "reporte": resultado.reporte_detallado, "mensaje": "OK" if resultado.exito else "Discrepancias encontradas"}
    finally:
        imagen.file.close()
        if file_path.exists(): file_path.unlink()

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
    uvicorn.run(app, host="0.0.0.0", port=8000)
