# Instalación Manual de Tesseract OCR

## Windows - Método 1: Descarga Directa (Recomendado)

1. **Descargar el instalador**:
   - Visita: https://github.com/UB-Mannheim/tesseract/wiki
   - Descarga: `tesseract-ocr-w64-setup-5.3.3.20231005.exe` (64-bit)

2. **Instalar**:
   - Ejecuta el instalador como Administrador
   - Seleccionar instalación para "Todos los usuarios"
   - Marcar la opción "Add Tesseract to your PATH" durante la instalación

3. **Verificar instalación**:
   ```cmd
   tesseract --version
   ```

## Windows - Método 2: Usando Winget

```cmd
winget install --id=UB-Mannheim.TesseractOCR
```

## Windows - Método 3: Usando Chocolatey

```cmd
choco install tesseract
```

## Configuración Adicional

### Agregar al PATH (si no se hizo automáticamente)
Agrega estas rutas a tus variables de entorno PATH:
- `C:\Program Files\Tesseract-OCR`
- `C:\Program Files\Tesseract-OCR\tessdata`

### Descargar datos de idioma español (opcional)
Si necesitas reconocimiento en español:
1. Descarga `spa.traineddata` desde: https://github.com/tesseract-ocr/tessdata
2. Coloca el archivo en: `C:\Program Files\Tesseract-OCR\tessdata\`

## Verificación Final

```cmd
tesseract --version
tesseract --list-langs
```

Deberías ver algo como:
```
tesseract 5.3.3
List of available languages (1):
spa
```

## Solución de Problemas

### Error: "tesseract no se reconoce como comando"
- Reinicia tu terminal después de la instalación
- Verifica que las rutas estén en el PATH
- Reinicia Windows si es necesario

### Error: "Error opening data file"
- Asegúrate que los archivos `.traineddata` estén en la carpeta `tessdata`
- Verifica permisos de las carpetas

### Error: "Failed to load language"
- Descarga los archivos de idioma adicionales
- Verifica que el idioma esté disponible con `tesseract --list-langs`

## Para Desarrolladores

### Verificar desde Python
```python
import pytesseract
print(pytesseract.get_tesseract_version())
```

### Configurar ruta personalizada (si necesario)
```python
import pytesseract
pytesseract.pytesseract.tesseract_cmd = r'C:\Program Files\Tesseract-OCR\tesseract.exe'
```

---

Una vez instalado Tesseract, ejecuta:
```cmd
cd microservicio
py start_server.py
```

El microservicio debería iniciar correctamente en http://localhost:8000
