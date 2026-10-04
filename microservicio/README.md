# 🤖 Casalai AI - Microservicio de Recepción

**Fase 1: Verificación Asistida (El Guardián)**

Microservicio de Inteligencia Artificial para auditoría de recepción de mercancía, basado en arquitectura MVC con FastAPI.

## 📁 Estructura

```
microservicio/
├── modelo/
│   └── auditor_recepcion.py      # Lógica IA (OCR + PLN)
├── controlador/
│   └── api_recepcion.py          # API REST FastAPI
├── javascript/
│   └── asistente_recepcion.js    # Cliente JS para PHP
├── config/                       # Configuración
├── temp_uploads/                 # Uploads temporales
├── factura_cache/                # Cache de facturas
├── logs_metricas/                # Logs y métricas
├── requirements.txt
└── README.md
```

## 🚀 Instalación

### 1. Instalar Tesseract OCR

**Windows:**
```powershell
# Descargar de https://github.com/UB-Mannheim/tesseract/wiki
# Agregar al PATH: C:\Program Files\Tesseract-OCR
```

**Ubuntu/Debian:**
```bash
sudo apt install tesseract-ocr tesseract-ocr-spa
```

Para procesar PDFs también se requiere Poppler, disponible en `PATH` para que `pdf2image` pueda convertir cada página.

### 2. Instalar dependencias Python

```bash
cd microservicio
pip install -r requirements.txt
```

### 3. Iniciar servidor

```bash
python controlador/api_recepcion.py
# o
uvicorn controlador.api_recepcion:app --host 0.0.0.0 --port 8000 --reload
```

## 🔌 API Endpoints

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/` | Estado del servicio |
| GET | `/health` | Health check |
| POST | `/fase1/extraer` | Extraer datos de factura (PDF o imagen) |
| POST | `/fase1/verificar` | Verificar coherencia con formulario |
| POST | `/fase1/comparar-directo` | Extraer + verificar en un paso |
| GET | `/fase1/cache/{id}` | Ver factura en cache |
| GET | `/fase1/estadisticas` | Estadísticas de uso |

El módulo PHP invoca estos endpoints a través de `Modelo/Servicio/RecepcionIAProxy.php`; PHP debe tener habilitada la extensión cURL. La URL base se configura con `RECEPCION_IA_URL` y por defecto es `http://127.0.0.1:8000`. El proxy aplica timeout total de 7,5 segundos, un reintento limitado para fallos de conexión/HTTP transitorios y un circuit breaker compartido en el host PHP: tres fallos consecutivos abren el circuito por dos minutos. El frontend aplica además un timeout de 8 segundos y activa el registro manual cuando detecta indisponibilidad.

La factura adjunta se conserva en `comprobantes/recepcion/`, con acceso HTTP denegado. Los manifiestos `.json` indican el correlativo, la recepción y si quedó `pendiente_ocr`; todavía no existe un trabajador batch que procese automáticamente esos pendientes.

Para actualizar una base existente con los campos fiscales, ejecutar una vez `agregar_iva_recepcion.sql` sobre la base principal. Los registros anteriores quedan con IVA no registrado; solo se completan al revisar su factura original.

## 📖 Flujo de Uso (Fase 1)

La carga acepta PDF e imágenes de hasta 5 MB. En los PDFs se analiza cada página; Tesseract y Poppler deben estar instalados en el equipo que ejecuta la API.

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│ PHP / Cliente JS │────▶│ Proxy PHP +      │────▶│ FastAPI / OCR  │
│                  │     │ circuit breaker  │     │ y verificación │
└──────────────────┘     └──────────────────┘     └────────────────┘
       │                         │                         │
       ▼                         ▼                         ▼
   1. Adjuntar PDF/imagen  2. Timeout/reintento    3. Autocompletar o
    conservar archivo      o modo manual           verificar
```

## Documentación de metodología

- [Texto para memoria técnica: arquitectura, seguridad e IA/OCR](ARQUITECTURA_Y_SEGURIDAD.md): degradación grácil, parámetros del breaker, política de adjuntos y pipeline implementado.
- [Ficha de caracterización del dataset de facturas](DATASET_FACTURAS.md): variabilidad, etiquetado, particiones, evaluación y rol actual/futuro de CNN.
- [Contrato propuesto para Analítica Predictiva](ANALITICA_PREDICTIVA.md): fuente de datos, endpoint, métricas de negocio y especificación del dashboard. No hay aún API predictiva implementada.

## 📝 Ejemplo de Uso en PHP/JavaScript

```javascript
// Inicializar asistente
const asistente = new AsistenteRecepcionIA({
    apiUrl: 'http://localhost:8000',
    debug: true
});

// Paso 1: Extraer factura
const archivo = document.getElementById('foto-factura').files[0];
const resultado = await asistente.extraerDesdeImagen(archivo);

// Paso 2: Verificar cuando se envía formulario
const datosForm = {
    numero_factura: document.getElementById('num-factura').value,
    nombre_proveedor: document.getElementById('proveedor').value,
    productos: [
        { nombre: 'Producto A', cantidad: 5, costo: 100.00, ... }
    ]
};

const verificacion = await asistente.verificarCoherencia(datosForm);

if (verificacion.exito) {
    // Permitir registro
    enviarFormularioPHP();
} else {
    // Mostrar discrepancias
    alert('Corrija: ' + verificacion.discrepancias.map(d => d.mensaje).join('\n'));
}
```

## ⚙️ Configuración

Crear `config/config.json`:

```json
{
    "tesseract_cmd": "tesseract",
    "idioma_ocr": "spa+eng",
    "preprocesamiento": true,
    "guardar_cache": true,
    "umbral_exactitud": 0.90,
    "tolerancia_costo": 0.05
}
```

## 📊 Fases del Proyecto

- **Fase 1** ✅: Verificación Asistida (El Guardián) - Actual
- **Fase 2**: Sugerencia Inteligente (El Asistente) - Próximo
- **Fase 3**: Automatización Completa (El Piloto) - Futuro

## 🔧 Tecnologías

- **Python 3.10+**
- **FastAPI**: API REST de alto rendimiento
- **OpenCV**: Preprocesamiento de imágenes
- **Tesseract OCR**: Reconocimiento óptico de caracteres
- **Pydantic**: Validación de datos

## 📄 Licencia

Proyecto privado - Casalai Systems
