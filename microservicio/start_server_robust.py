#!/usr/bin/env python3
"""
Script de inicio robusto para el microservicio IA Recepción
Maneja errores de forma más elegante y proporciona mejor retroalimentación
"""

import os
import sys
import logging
import time
from pathlib import Path

# Configuración de logging
def setup_logging():
    """Configurar logging para mejor depuración"""
    logs_dir = Path("logs")
    logs_dir.mkdir(exist_ok=True)
    
    logging.basicConfig(
        level=logging.INFO,
        format='%(asctime)s - %(levelname)s - %(message)s',
        handlers=[
            logging.FileHandler(logs_dir / 'server.log', encoding='utf-8'),
            logging.StreamHandler(sys.stdout)
        ]
    )
    return logging.getLogger(__name__)

def check_dependencies():
    """Verificar dependencias necesarias"""
    logger = logging.getLogger(__name__)
    
    try:
        import fastapi
        logger.info("FastAPI disponible")
    except ImportError as e:
        logger.error(f"FastAPI no disponible: {e}")
        logger.error("Ejecute: pip install fastapi")
        return False
    
    try:
        import uvicorn
        logger.info("Uvicorn disponible")
    except ImportError as e:
        logger.error(f"Uvicorn no disponible: {e}")
        logger.error("Ejecute: pip install uvicorn")
        return False
    
    return True

def check_port_available(port=8000):
    """Verificar si el puerto está disponible"""
    import socket
    with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
        try:
            s.bind(('localhost', port))
            s.close()
            return True
        except OSError:
            return False

def start_server():
    """Iniciar el servidor de forma robusta"""
    logger = logging.getLogger(__name__)
    
    logger.info("Iniciando Microservicio IA Recepción")
    logger.info("=" * 50)
    
    # Verificar dependencias
    if not check_dependencies():
        logger.error("Faltan dependencias. No se puede iniciar el servidor.")
        return False
    
    # Verificar puerto
    if not check_port_available():
        logger.warning("El puerto 8000 ya está en uso")
        logger.info("Intentando usar puerto 8001...")
        port = 8001
        if not check_port_available(port):
            logger.error("No hay puertos disponibles (8000, 8001)")
            return False
    else:
        port = 8000
    
    # Importar y crear la aplicación
    try:
        # Importar la aplicación demo
        from demo_server import app
        
        logger.info(f"Servidor iniciando en http://localhost:{port}")
        logger.info("Modo: Demostración (sin Tesseract)")
        logger.info("Recarga automática de archivos activada")
        logger.info("Presione Ctrl+C para detener")
        logger.info("=" * 50)
        
        # Iniciar con uvicorn
        import uvicorn
        
        uvicorn.run(
            app,
            host="0.0.0.0",
            port=port,
            reload=True,
            reload_dirs=["."],
            log_level="info"
        )
        
    except KeyboardInterrupt:
        logger.info("\nServidor detenido por el usuario")
        return True
    except Exception as e:
        logger.error(f"Error al iniciar servidor: {e}")
        logger.error(f"Tipo de error: {type(e).__name__}")
        return False

def main():
    """Función principal"""
    logger = setup_logging()
    
    try:
        success = start_server()
        if success:
            logger.info("Servidor finalizado correctamente")
            sys.exit(0)
        else:
            logger.error("El servidor encontró errores")
            sys.exit(1)
    except Exception as e:
        logger.error(f"Error crítico: {e}")
        sys.exit(1)

if __name__ == "__main__":
    main()
