# Instrucciones de Integración - Microservicio IA Recepción

## Overview

Se ha integrado exitosamente el microservicio de Inteligencia Artificial en el módulo de Recepción (Fase 1: El Guardián). Esta implementación permite la verificación automática de facturas mediante Visión Artificial y OCR.

## Componentes Integrados

### 1. Microservicio Python
- **Ubicación**: `microservicio/`
- **Arquitectura**: MVC (Modelo-Vista-Controlador)
- **Tecnología**: FastAPI + OpenCV + Tesseract OCR

### 2. Formulario PHP Modificado
- **Archivo**: `Vista/Recepcion.php`
- **Nuevos elementos**:
  - Campo de carga de imagen de factura
  - Botones de verificación IA
  - Contenedor de resultados
  - Estilos CSS personalizados

### 3. JavaScript Cliente
- **Archivo**: `assets/javascript/recepcion.js`
- **Funcionalidades**:
  - Inicialización automática del cliente IA
  - Validación antes de envío
  - Manejo de resultados de verificación

## Flujo de Trabajo Implementado

### Paso 1: Captura de Factura
1. El usuario carga una fotografía de la factura física
2. El sistema valida el archivo (tipo, tamaño)
3. Se muestra preview de la imagen

### Paso 2: Verificación con IA
1. El usuario hace clic en "Verificar con IA"
2. La imagen se envía al microservicio Python
3. Se extrae información mediante OCR
4. Se muestra resultado con nivel de confianza

### Paso 3: Llenado del Formulario
1. El usuario completa los datos manualmente
2. El sistema mantiene los resultados de IA en memoria

### Paso 4: Validación Final
1. Al enviar el formulario, se compara datos manuales vs IA
2. Si hay discrepancias críticas: **Bloquea el registro**
3. Si hay discrepancias leves: **Muestra advertencia**
4. Si todo es coherente: **Permite el registro**

## Configuración Requerida

### 1. Instalar Dependencias Python
```bash
cd microservicio
pip install -r requirements.txt
```

### 2. Instalar Tesseract OCR
**Windows:**
- Descargar desde: https://github.com/UB-Mannheim/tesseract/wiki
- Instalar y agregar al PATH

**Linux:**
```bash
sudo apt update
sudo apt install tesseract-ocr tesseract-ocr-spa
```

### 3. Iniciar el Microservicio
```bash
cd microservicio
python start_server.py
```

O manualmente:
```bash
cd controlador
python api.py
```

### 4. Verificar Conexión
- El microservicio se iniciará en `http://localhost:8000`
- El formulario mostrará estado "Conectado" cuando esté disponible

## Características de Seguridad

### Validaciones Implementadas
- **Tipo de archivo**: Solo imágenes (JPG, PNG, BMP)
- **Tamaño máximo**: 10MB por archivo
- **Validación de estructura**: Verificación de integridad de datos
- **Bloqueo automático**: Prevención de registros inconsistentes

### Niveles de Acción IA
1. **Aprobar**: Datos coherentes, permite registro
2. **Advertencia**: Discrepancias leves, requiere confirmación
3. **Bloquear**: Discrepancias críticas, impide registro

## Mensajes al Usuario

### Conexión Exitosa
```
Estado: Conectado
Color: Verde (badge-success)
```

### Verificación Exitosa
```
Resultado: Factura procesada con confianza del 85%
Productos detectados: 3
```

### Discrepancias Detectadas
```
Advertencia: Se encontraron diferencias
- N° Factura: "FAC-001" vs "FAC-002"
- Producto 0 - Costo: "150.50" vs "160.00"
```

### Bloqueo por Discrepancias Críticas
```
Error: Verificación IA - Bloqueado
La inteligencia artificial ha detectado discrepancias críticas
Por favor, corrija los datos antes de continuar
```

## Archivos Modificados

### Frontend
- `Vista/Recepcion.php` - Formulario con componentes IA
- `assets/javascript/recepcion.js` - Lógica de validación

### Backend
- `Modelo/Controlador/recepcion.php` - Manejo de archivos y datos IA

### Microservicio
- `microservicio/modelo/vision_ai.py` - Lógica de IA
- `microservicio/controlador/api.py` - Endpoints API
- `microservicio/javascript/ia-recepcion.js` - Cliente JavaScript

## Directorios Creados

### Almacenamiento de Facturas
```
assets/img/comprobantes/facturas/
```
Las facturas se guardan con nombres únicos:
`factura_2025-04-19_14-30-25_642abc123.jpg`

## Logs y Monitoreo

### Logs del Microservicio
```
logs/microservicio.log
```

### Errores Comunes
1. **Tesseract no encontrado**: Verificar instalación
2. **Conexión rechazada**: Asegurar que el servidor esté corriendo
3. **Archivo muy grande**: Reducir tamaño o aumentar límite
4. **Formato no soportado**: Usar JPG, PNG o BMP

## Pruebas de Funcionamiento

### 1. Prueba Básica
- Cargar una imagen de factura clara
- Verificar que se extraiga información
- Comprobar que el estado sea "Conectado"

### 2. Prueba de Validación
- Introducir datos diferentes a los extraídos
- Verificar que se muestren discrepancias
- Confirmar que el sistema bloquee si es necesario

### 3. Prueba de Flujo Completo
- Cargar imagen y procesar
- Llenar formulario con datos correctos
- Enviar y verificar registro exitoso

## Mantenimiento

### Limpieza de Archivos
Las facturas antiguas pueden ser archivadas o eliminadas periódicamente para liberar espacio.

### Actualización del Modelo
Para mejorar la precisión del OCR:
1. Actualizar patrones regex en `vision_ai.py`
2. Reentrenar modelos si es necesario
3. Ajustar umbrales de confianza

### Monitoreo de Rendimiento
- Revisar logs regularmente
- Monitorear tiempo de respuesta
- Verificar uso de memoria y CPU

## Soporte

### Problemas Comunes y Soluciones

**Problema**: "Desconectado" en el estado
**Solución**: Iniciar el microservicio Python

**Problema**: Error al procesar imagen
**Solución**: Verificar formato y tamaño del archivo

**Problema**: No se detecta texto
**Solución**: Usar imágenes más claras y mejor iluminadas

**Problema**: Discrepancias falsas
**Solución**: Ajustar patrones de reconocimiento

## Próximos Pasos (Fase 2)

Para la siguiente fase de desarrollo:
1. Autocompletado inteligente de formularios
2. Sugerencias de corrección en tiempo real
3. Validación contextual avanzada

---

**Nota**: Esta implementación corresponde a la Fase 1 del plan de desarrollo, enfocada en la verificación asistida ("El Guardián").
