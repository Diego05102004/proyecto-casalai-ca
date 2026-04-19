# Microservicio IA Recepción

Microservicio inteligente para el módulo de recepción que implementa Visión Artificial y Procesamiento de Lenguaje Natural para automatizar y auditar el proceso de recepción de mercancía.

## Arquitectura

El microservicio sigue una arquitectura MVC (Modelo-Vista-Controlador) orientada a objetos:

- **Modelo** (`modelo/`): Lógica de IA y procesamiento de imágenes
- **Controlador** (`controlador/`): API REST con FastAPI
- **Vista** (`javascript/`): Cliente JavaScript para integración con PHP

## Funcionalidades - Fase 1: Verificación Asistida (El Guardián)

### 1. Captura y Procesamiento de Facturas
- Captura de fotografía de factura física
- Preprocesamiento de imagen con OpenCV
- Extracción de texto con OCR contextualizado (Tesseract)

### 2. Extracción de Información
- N° de factura del proveedor
- Nombre del proveedor
- Detalles de productos (nombre, modelo, marca, serial, costo, cantidad)

### 3. Verificación de Coherencia
- Comparación entre datos extraídos y datos del formulario
- Detección de discrepancias
- Bloqueo de registros inconsistentes

## Instalación

### Prerrequisitos
- Python 3.8 o superior
- Tesseract OCR instalado en el sistema
- pip (gestor de paquetes Python)

### 1. Instalar dependencias
```bash
pip install -r requirements.txt
```

### 2. Instalar Tesseract OCR
**Windows:**
```bash
# Descargar desde: https://github.com/UB-Mannheim/tesseract/wiki
# Agregar al PATH durante la instalación
```

**Linux (Ubuntu/Debian):**
```bash
sudo apt update
sudo apt install tesseract-ocr tesseract-ocr-spa
```

### 3. Configurar variables de entorno (opcional)
```bash
# Copiar y editar archivo de configuración
cp .env.example .env
```

## Uso

### Iniciar el servidor
```bash
# Desde la carpeta controlador/
python api.py
```

O usando uvicorn directamente:
```bash
uvicorn controlador.api:app --host 0.0.0.0 --port 8000 --reload
```

### Endpoints disponibles

#### GET `/`
Estado del servicio y endpoints disponibles

#### GET `/health`
Verificación de salud del microservicio

#### POST `/procesar-factura`
Procesa una imagen de factura y extrae información
- **Body**: `multipart/form-data` con campo `archivo`
- **Response**: Datos extraídos con nivel de confianza

#### POST `/verificar-coherencia`
Verifica coherencia entre datos de factura y formulario
- **Body**: JSON con datos de comparación
- **Response**: Reporte de discrepancias

#### POST `/procesar-y-verificar`
Procesamiento integral en una sola llamada
- **Body**: `multipart/form-data` con `archivo` y `datos_formulario`
- **Response**: Resultado completo con acción recomendada

## Integración con PHP

### 1. Incluir el cliente JavaScript
```html
<script src="microservicio/javascript/ia-recepcion.js"></script>
```

### 2. Inicializar el cliente
```javascript
// Configurar cliente
const iaClient = new IARecepcionClient({
    apiUrl: 'http://localhost:8000',
    timeout: 30000
});

// Integrar con formulario existente
const formHelper = new IARecepcionFormHelper(iaClient, '#formulario-recepcion');
```

### 3. Ejemplo de uso básico
```javascript
// Procesar factura
async function procesarFactura(archivoImagen) {
    try {
        const resultado = await iaClient.procesarFactura(archivoImagen);
        console.log('Factura procesada:', resultado);
        return resultado;
    } catch (error) {
        console.error('Error:', error);
    }
}

// Verificar coherencia
async function verificarCoherencia(datosFormulario, datosFactura) {
    try {
        const resultado = await iaClient.verificarCoherencia(datosFormulario, datosFactura);
        console.log('Verificación:', resultado);
        return resultado;
    } catch (error) {
        console.error('Error:', error);
    }
}
```

## Flujo de Trabajo - Fase 1

1. **Captura Previa**: El usuario carga una fotografía de la factura
2. **Extracción**: El microservicio procesa la imagen y extrae datos clave
3. **Verificación**: Al enviar el formulario, se comparan datos manuales con extraídos
4. **Acción**: 
   - Si es coherente: Permite el registro
   - Si hay discrepancias: Bloquea y muestra reporte de diferencias

## Estructura de Datos

### Datos de Factura Extraídos
```json
{
    "numero_factura": "FAC-001",
    "nombre_proveedor": "Proveedor S.A.",
    "productos": [
        {
            "nombre": "Producto X",
            "modelo": "MDL-001",
            "marca": "MARCA-A",
            "serial": "SN123456",
            "costo": 150.50,
            "cantidad": 2,
            "confianza": 0.85
        }
    ],
    "confianza_general": 0.82
}
```

### Reporte de Verificación
```json
{
    "es_coherente": false,
    "discrepancias": [
        {
            "campo": "producto_0_costo",
            "valor_factura": 150.50,
            "valor_formulario": 160.00,
            "severidad": "media"
        }
    ],
    "confianza_verificacion": 0.72,
    "accion_recomendada": "requiere_revision"
}
```

## Configuración

### Variables de entorno
```bash
# Configuración del servidor
API_HOST=0.0.0.0
API_PORT=8000
API_DEBUG=true

# Configuración de Tesseract
TESSERACT_CMD=/usr/bin/tesseract  # Linux
# TESSERACT_CMD=C:\\Program Files\\Tesseract-OCR\\tesseract.exe  # Windows

# Configuración de procesamiento
MAX_FILE_SIZE=10485760  # 10MB
ALLOWED_MIME_TYPES=image/jpeg,image/png,image/bmp
```

## Monitoreo y Logging

El microservicio incluye logging estructurado con diferentes niveles:

- **INFO**: Operaciones normales y estado
- **WARNING**: Discrepancias y advertencias
- **ERROR**: Errores de procesamiento
- **DEBUG**: Información detallada para desarrollo

## Testing

### Ejecutar pruebas unitarias
```bash
python -m pytest tests/ -v
```

### Pruebas de integración
```bash
python -m pytest tests/integration/ -v
```

## Rendimiento

### Especificaciones recomendadas
- **CPU**: 2+ cores para procesamiento paralelo
- **RAM**: 4GB+ para procesamiento de imágenes
- **Almacenamiento**: SSD para mejor rendimiento I/O

### Optimizaciones implementadas
- Procesamiento asíncrono con FastAPI
- Cache de patrones OCR
- Compresión de imágenes
- Reintentos automáticos con exponential backoff

## Seguridad

- Validación de tipos de archivo
- Límites de tamaño de archivo
- Sanitización de entrada
- CORS configurable
- Logging de auditoría

## Desarrollo

### Extender el modelo de IA
Para agregar nuevos campos de extracción:

1. Actualizar patrones regex en `modelo/vision_ai.py`
2. Modificar la clase `DatosFactura`
3. Actualizar validaciones correspondientes

### Agregar nuevos endpoints
1. Definir modelos Pydantic en `controlador/api.py`
2. Implementar lógica del endpoint
3. Agregar documentación y tests

## Troubleshooting

### Problemas comunes

**Tesseract no encontrado**
```bash
# Verificar instalación
tesseract --version

# Configurar PATH si es necesario
export PATH=$PATH:/usr/bin/tesseract
```

**Error de memoria**
- Reducir tamaño máximo de archivo
- Optimizar resolución de imágenes
- Aumentar RAM del servidor

**Conexión rechazada**
- Verificar que el servidor esté corriendo
- Configurar firewall para permitir puerto 8000
- Verificar configuración CORS

## Roadmap - Próximas Fases

### Fase 2: Asistencia Activa
- Autocompletado inteligente de formularios
- Sugerencias de corrección en tiempo real
- Validación contextual avanzada

### Fase 3: Automatización Completa
- Procesamiento completamente automático
- Integración con sistemas de proveedores
- Análisis predictivo de anomalías

## Licencia

Este microservicio es parte del proyecto CasaLai CA y está sujeto a las mismas condiciones de licencia del proyecto principal.

## Soporte

Para soporte técnico o reporte de issues:
- Crear issue en el repositorio del proyecto
- Contactar al equipo de desarrollo
- Revisar logs del microservicio para diagnóstico
