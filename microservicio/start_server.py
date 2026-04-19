#!/usr/bin/env python3
"""
Script de inicio para el Microservicio IA Recepción
Facilita la ejecución del servidor con configuración adecuada
"""

import sys
import os
import argparse
import uvicorn
from pathlib import Path

# Agregar rutas al Python path
current_dir = Path(__file__).parent
sys.path.insert(0, str(current_dir / "controlador"))
sys.path.insert(0, str(current_dir / "modelo"))

def verificar_dependencias():
    """Verifica que las dependencias necesarias estén instaladas"""
    
    dependencias_requeridas = [
        'fastapi',
        'uvicorn', 
        'opencv-python',
        'pytesseract',
        'Pillow',
        'numpy',
        'pydantic'
    ]
    
    faltantes = []
    
    for dependencia in dependencias_requeridas:
        try:
            if dependencia == 'opencv-python':
                import cv2
            elif dependencia == 'pytesseract':
                import pytesseract
            elif dependencia == 'Pillow':
                from PIL import Image
            elif dependencia == 'numpy':
                import numpy
            elif dependencia == 'pydantic':
                import pydantic
            elif dependencia == 'fastapi':
                import fastapi
            elif dependencia == 'uvicorn':
                import uvicorn
        except ImportError:
            faltantes.append(dependencia)
    
    if faltantes:
        print("ERROR: Faltan las siguientes dependencias:")
        for dep in faltantes:
            print(f"  - {dep}")
        print("\nInstale las dependencias con:")
        print("  pip install -r requirements.txt")
        return False
    
    return True

def verificar_tesseract():
    """Verifica que Tesseract OCR esté instalado"""
    
    try:
        import pytesseract
        pytesseract.pytesseract.tesseract_cmd
        
        # Intentar ejecutar tesseract
        version = pytesseract.get_tesseract_version()
        print(f"Tesseract OCR encontrado: {version}")
        return True
        
    except Exception as e:
        print("ERROR: Tesseract OCR no encontrado o no configurado")
        print("Por favor, instale Tesseract OCR:")
        print("  Windows: https://github.com/UB-Mannheim/tesseract/wiki")
        print("  Linux: sudo apt install tesseract-ocr tesseract-ocr-spa")
        print(f"  Detalles del error: {e}")
        return False

def crear_directorios_necesarios():
    """Crea directorios necesarios para el funcionamiento"""
    
    directorios = [
        'logs',
        'temp', 
        'uploads'
    ]
    
    for directorio in directorios:
        ruta = current_dir / directorio
        ruta.mkdir(exist_ok=True)
        print(f"Directorio verificado: {ruta}")

def main():
    """Función principal de inicio"""
    
    parser = argparse.ArgumentParser(description='Iniciar Microservicio IA Recepción')
    
    parser.add_argument(
        '--host',
        default='0.0.0.0',
        help='Host para el servidor (default: 0.0.0.0)'
    )
    
    parser.add_argument(
        '--port',
        type=int,
        default=8000,
        help='Puerto para el servidor (default: 8000)'
    )
    
    parser.add_argument(
        '--reload',
        action='store_true',
        help='Activar recarga automática en desarrollo'
    )
    
    parser.add_argument(
        '--workers',
        type=int,
        default=1,
        help='Número de workers (default: 1)'
    )
    
    parser.add_argument(
        '--log-level',
        choices=['critical', 'error', 'warning', 'info', 'debug'],
        default='info',
        help='Nivel de logging (default: info)'
    )
    
    args = parser.parse_args()
    
    print("=" * 60)
    print("Microservicio IA Recepción - Iniciando")
    print("=" * 60)
    
    # 1. Verificar dependencias
    print("\n1. Verificando dependencias...")
    if not verificar_dependencias():
        sys.exit(1)
    
    # 2. Verificar Tesseract
    print("\n2. Verificando Tesseract OCR...")
    if not verificar_tesseract():
        sys.exit(1)
    
    # 3. Crear directorios
    print("\n3. Creando directorios necesarios...")
    crear_directorios_necesarios()
    
    # 4. Configurar variables de entorno
    print("\n4. Configurando entorno...")
    os.environ['PYTHONPATH'] = str(current_dir)
    
    # 5. Iniciar servidor
    print("\n5. Iniciando servidor...")
    print(f"   Host: {args.host}")
    print(f"   Puerto: {args.port}")
    print(f"   Workers: {args.workers}")
    print(f"   Reload: {args.reload}")
    print(f"   Log Level: {args.log_level}")
    
    print("\n" + "=" * 60)
    print("Servidor iniciado. Presione Ctrl+C para detener.")
    print(f"API Documentation: http://{args.host}:{args.port}/docs")
    print(f"Health Check: http://{args.host}:{args.port}/health")
    print("=" * 60 + "\n")
    
    try:
        uvicorn.run(
            "controlador.api:app",
            host=args.host,
            port=args.port,
            reload=args.reload,
            workers=args.workers if not args.reload else 1,
            log_level=args.log_level,
            access_log=True,
            use_colors=True
        )
    except KeyboardInterrupt:
        print("\nServidor detenido por el usuario.")
    except Exception as e:
        print(f"\nError iniciando servidor: {e}")
        sys.exit(1)

if __name__ == "__main__":
    main()
