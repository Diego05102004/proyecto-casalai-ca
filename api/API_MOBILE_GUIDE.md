# Guía de Integración API Móvil

## Estructura de la API

Los archivos PHP en la carpeta `api` actúan como endpoints para recibir solicitudes HTTPS de la app móvil Expo.

## Función RecibirPeticion()

La función `RecibirPeticion()` es el núcleo del sistema. Detecta la función solicitada e invoca el método correspondiente de la clase del modelo.

### Uso en archivos PHP

**IMPORTANTE:** Los métodos deben ser métodos públicos reales de la clase, no closures asignados como propiedades.

```php
<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api_helper.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Usuario\ProyectoCasalaiCa\Modelo\Clases\Productos;

// Crear instancia de la clase
$producto = new Productos();

// Definir operaciones disponibles
$operations = [
    'default' => [
        'method' => 'GET',
        'handler' => 'obtenerTodosProductos'
    ],
    'por_id' => [
        'method' => 'GET',
        'handler' => 'obtenerProductoPorId'
    ],
    'por_categoria' => [
        'method' => 'GET',
        'handler' => 'obtenerProductosPorCategoria'
    ]
];

// Los métodos (obtenerTodosProductos, obtenerProductoPorId, etc.) deben estar
// definidos como métodos públicos en la clase Productos

// Procesar la petición
RecibirPeticion($producto, $operations);
```

### Implementación de Métodos en la Clase

Los métodos deben implementarse dentro de la clase del modelo:

```php
class Productos extends BD {
    // ... otros métodos existentes ...

    public function obtenerTodosProductos($data) {
        return $this->api_obtenerTodosProductos($data);
    }
    
    private function api_obtenerTodosProductos($data) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($data) {
            // Lógica de consulta SQL
            return ['productos' => [...], 'total' => 10];
        });
    }
}
```

## Uso desde la App Móvil (JavaScript/Expo)

### Comunicador API Actualizado

El comunicador ahora envía el parámetro `funcion` solo para endpoints que usan `RecibirPeticion()`:

```javascript
import { secureGet, securePost, securePut, secureDelete } from './api';

const OPERATIONS = {
  login: { method: 'POST', useFunctionParam: false },
  registro: { method: 'POST', useFunctionParam: false },
  productos: { method: 'GET', useFunctionParam: true },
  productos_por_id: { method: 'GET', useFunctionParam: true },
  productos_por_categoria: { method: 'GET', useFunctionParam: true },
  productos_detallado: { method: 'GET', useFunctionParam: true },
};

const normalizeApiFile = (apiFile) => {
  if (typeof apiFile !== 'string' || !apiFile.trim()) {
    throw new Error('Debe indicar el nombre del archivo de la API');
  }
  const fileName = apiFile.trim().split('/').pop();
  return fileName.endsWith('.php') ? fileName : `${fileName}.php`;
};

const normalizeFunctionName = (functionName) => {
  if (typeof functionName !== 'string' || !functionName.trim()) {
    throw new Error('Debe indicar el nombre de la funcion de la API');
  }
  return functionName.trim().toLowerCase();
};

const saveResult = (resultHolder, response) => {
  if (typeof resultHolder === 'function') {
    resultHolder(response);
  } else if (resultHolder && typeof resultHolder === 'object') {
    if (Object.prototype.hasOwnProperty.call(resultHolder, 'current')) {
      resultHolder.current = response;
    } else {
      resultHolder.value = response;
    }
  }
};

class ComunicadorAPI {
  async ConsultarMetodo(nombreArchivoApi, nombreFuncion) {
    const apiFile = normalizeApiFile(nombreArchivoApi);
    const functionName = normalizeFunctionName(nombreFuncion);
    const operation = OPERATIONS[functionName] || { method: 'GET' };
    const endpoint = `/${apiFile}`;

    return async (datos = {}) => {
      // Agregar el parámetro 'funcion' a los datos
      const datosConFuncion = {
        ...datos,
        funcion: functionName
      };

      switch (operation.method) {
        case 'POST':
          return securePost(endpoint, datosConFuncion);
        case 'PUT':
          return securePut(endpoint, datosConFuncion);
        case 'DELETE':
          return secureDelete(endpoint, datosConFuncion);
        case 'GET':
        default:
          return secureGet(endpoint, datosConFuncion);
      }
    };
  }

  async InvocarMetodo(nombreArchivoApi, nombreFuncion, datos = {}, resultado = null) {
    const metodo = await this.ConsultarMetodo(nombreArchivoApi, nombreFuncion);
    const response = await metodo(datos);
    saveResult(resultado, response);
    return response;
  }
}

export const comunicadorApi = new ComunicadorAPI();
export const ConsultarMetodo = comunicadorApi.ConsultarMetodo.bind(comunicadorApi);
export const InvocarMetodo = comunicadorApi.InvocarMetodo.bind(comunicadorApi);
export default comunicadorApi;
```

## Ejemplos de Uso

### Obtener todos los productos
```javascript
// En tu componente React/expo
import { InvocarMetodo } from './comunicador-api';

const obtenerProductos = async () => {
  try {
    const response = await InvocarMetodo('productos', 'default');
    console.log(response.data.productos);
  } catch (error) {
    console.error('Error:', error);
  }
};
```

### Obtener producto por ID
```javascript
const obtenerProductoPorId = async (id) => {
  try {
    const response = await InvocarMetodo('productos', 'por_id', { id });
    console.log(response.data);
  } catch (error) {
    console.error('Error:', error);
  }
};
```

### Obtener productos por categoría
```javascript
const obtenerProductosPorCategoria = async (categoriaId) => {
  try {
    const response = await InvocarMetodo('productos', 'por_categoria', { 
      categoria: categoriaId 
    });
    console.log(response.data.productos);
  } catch (error) {
    console.error('Error:', error);
  }
};
```

### Login
```javascript
const login = async (email, password) => {
  try {
    const response = await InvocarMetodo('login', 'login', {
      email,
      password
    });
    console.log('Token:', response.data.token);
  } catch (error) {
    console.error('Error:', error);
  }
};
```

## Formato de Respuesta

Todas las respuestas siguen este formato:

### Éxito
```json
{
  "status": "success",
  "message": "Operación exitosa",
  "data": {
    // Datos específicos de la operación
  }
}
```

### Error
```json
{
  "status": "error",
  "message": "Descripción del error",
  "errors": {
    // Detalles de errores de validación (opcional)
  }
}
```

## Crear Nuevos Endpoints

Para crear un nuevo endpoint:

1. Crea el archivo PHP en `api/`
2. Incluye `config.php` y `api_helper.php`
3. Crea la instancia de la clase del modelo
4. Define las operaciones en el array `$operations`
5. Agrega los métodos a la instancia (como closures)
6. Llama a `RecibirPeticion($instance, $operations)`

Ejemplo:
```php
<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api_helper.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Usuario\ProyectoCasalaiCa\Modelo\Clases\Categoria;

$categoria = new Categoria();

$operations = [
    'default' => ['method' => 'GET', 'handler' => 'obtenerTodas'],
    'por_id' => ['method' => 'GET', 'handler' => 'obtenerPorId']
];

$categoria->obtenerTodas = function($data) {
    // Lógica
    return ['categorias' => [...]];
};

RecibirPeticion($categoria, $operations);
```

## Endpoints Disponibles

- **login.php**: Autenticación de usuarios
- **registro.php**: Registro de nuevos usuarios
- **productos.php**: Catálogo de productos
  - `default`: Todos los productos
  - `por_id`: Producto por ID
  - `por_categoria`: Productos por categoría
  - `detallado`: Producto con características dinámicas

## Configuración de URLs

### Backend (PHP)

El archivo `api_config.php` centraliza las URLs para el backend:

```php
<?php
require_once __DIR__ . '/api_config.php';

// Usar en la clase Productos
private function getBaseUrl() {
    $configFile = __DIR__ . '/../../../../api/api_config.php';
    if (file_exists($configFile)) {
        require_once $configFile;
        if (function_exists('getBaseUrl')) {
            return getBaseUrl();
        }
    }
    return 'http://localhost/Repositorio%20de%20GITHUB/proyecto-casalai-main/proyecto-casalai-ca';
}
```

### Frontend (JavaScript/Expo)

El archivo `apiConfig.js` centraliza las URLs para la app móvil:

```javascript
import { getBaseUrl, setEnvironment } from './apiConfig';

// Para desarrollo (navegador en el mismo PC)
// ENV = 'development' -> usa localhost

// Para emulador Android
setEnvironment('emulator'); // usa 10.0.2.2

// Para dispositivo físico
setEnvironment('device'); // usa IP local (configurar en apiConfig.js)

// Para producción
setEnvironment('production'); // usa HTTPS
```

**Configuración de IP para dispositivo físico:**

1. Obtén la IP de tu PC (ejecutar `ipconfig` en Windows)
2. Actualiza la sección `device` en `apiConfig.js` con tu IP
3. Asegúrate de que XAMPP esté configurado para aceptar conexiones externas
