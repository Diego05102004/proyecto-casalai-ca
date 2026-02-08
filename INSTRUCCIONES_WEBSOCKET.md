# Configuración de Conexión Automática WebSocket

## Pasos para la conexión automática al iniciar sesión:

### 1. Configurar el servidor WebSocket para inicio automático

Ejecuta como **Administrador** el siguiente archivo:
```
install_autostart.bat
```

Esto creará una tarea programada en Windows que:
- Inicia automáticamente el servidor WebSocket al iniciar sesión
- Se ejecuta en segundo plano sin mostrar ventana
- Se reinicia automáticamente si hay caídas

### 2. Verificar la instalación

Ve al **Programador de Tareas** de Windows y busca "Casalai WebSocket Server".

### 3. Probar la conexión automática

1. Reinicia tu sesión de Windows
2. Inicia sesión en el sistema Casalai
3. Abre la consola del navegador (F12)
4. Deberías ver los siguientes mensajes:
   - "Servidor WebSocket iniciado automáticamente"
   - "Conexión WebSocket establecida"
   - "WebSocket inicializado automáticamente"

## ¿Qué se ha configurado?

### En el servidor:
- **Verificación automática**: Al iniciar sesión, se verifica si el servidor WebSocket está corriendo
- **Inicio automático**: Si no está corriendo, se inicia automáticamente
- **Reconexión automática**: El cliente se reconecta automáticamente si la conexión se pierde

### En el cliente:
- **Inicialización automática**: El WebSocket se inicializa automáticamente después del login
- **Verificación de conexión**: Se verifica que la conexión esté activa
- **Reconexión forzada**: Si la conexión está caída, se repara automáticamente

## Solución de problemas

Si no funciona automáticamente:

1. **Verifica permisos**: Asegúrate de ejecutar `install_autostart.bat` como administrador
2. **Revisa PHP**: Confirma que PHP está en el PATH del sistema
3. **Verifica puerto**: Asegúrate que el puerto 8080 esté disponible
4. **Reinicia sesión**: Cierra y vuelve a iniciar tu sesión de Windows

## Para desinstalar

Ejecuta como administrador:
```
uninstall_autostart.bat
```

## Notas importantes

- El servidor WebSocket se iniciará **automáticamente cada vez** que inicies sesión en Windows
- No necesitarás intervención manual después de la instalación
- El sistema de reconexión automática seguirá funcionando si hay interrupciones
- La conexión se establecerá inmediatamente después de iniciar sesión en Casalai
