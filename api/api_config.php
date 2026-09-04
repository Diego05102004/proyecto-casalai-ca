<?php
/**
 * Configuración de URLs para la API
 * Centraliza las URLs base para desarrollo y producción
 */

// Detectar entorno
$env = getenv('APP_ENV') ?: 'development';

// Configuración de URLs según entorno
$config = [
    'development' => [
        'base_url' => 'http://localhost/proyecto-casalai-ca',
        'api_base_url' => 'http://localhost/proyecto-casalai-ca/api',
        'assets_url' => 'http://localhost/proyecto-casalai-ca/assets',
        'images_url' => 'http://localhost/proyecto-casalai-ca/assets/img',
    ],
    'production' => [
        'base_url' => 'https://tu-dominio.com',
        'api_base_url' => 'https://tu-dominio.com/api',
        'assets_url' => 'https://tu-dominio.com/assets',
        'images_url' => 'https://tu-dominio.com/assets/img',
    ]
];

// Configuración actual basada en el entorno
$currentConfig = $config[$env] ?? $config['development'];

/**
 * Obtiene la URL base configurada
 * @return string URL base
 */
function getBaseUrl() {
    return getCurrentConfig()['base_url'];
}

/**
 * Obtiene la URL base de la API
 * @return string URL base de la API
 */
function getApiBaseUrl() {
    return getCurrentConfig()['api_base_url'];
}

/**
 * Obtiene la URL de assets
 * @return string URL de assets
 */
function getAssetsUrl() {
    return getCurrentConfig()['assets_url'];
}

/**
 * Obtiene la URL de imágenes
 * @return string URL de imágenes
 */
function getImagesUrl() {
    return getCurrentConfig()['images_url'];
}

function getCurrentConfig() {
    $env = getenv('APP_ENV') ?: 'development';
    $configs = [
        'development' => [
            'base_url' => 'http://localhost/Repositorio%20de%20GITHUB/proyecto-casalai-main/proyecto-casalai-ca',
            'api_base_url' => 'http://localhost/Repositorio%20de%20GITHUB/proyecto-casalai-main/proyecto-casalai-ca/api',
            'assets_url' => 'http://localhost/Repositorio%20de%20GITHUB/proyecto-casalai-main/proyecto-casalai-ca/assets',
            'images_url' => 'http://localhost/Repositorio%20de%20GITHUB/proyecto-casalai-main/proyecto-casalai-ca/assets/img',
        ],
        'production' => [
            'base_url' => 'https://tu-dominio.com',
            'api_base_url' => 'https://tu-dominio.com/api',
            'assets_url' => 'https://tu-dominio.com/assets',
            'images_url' => 'https://tu-dominio.com/assets/img',
        ],
    ];

    return $configs[$env] ?? $configs['development'];
}

/**
 * Construye URL completa para una imagen
 * @param string $imagePath Ruta relativa de la imagen
 * @return string URL completa de la imagen
 */
function getImageUrl($imagePath) {
    if (empty($imagePath)) {
        return null;
    }
    
    // Normalizar ruta de imagen
    $imagePath = str_replace('\\', '/', $imagePath);
    $imagePath = str_replace('assets/img/productos/', '', $imagePath);
    
    return getImagesUrl() . '/productos/' . $imagePath;
}

/**
 * Obtiene el entorno actual
 * @return string Entorno (development|production)
 */
function getEnvironment() {
    global $env;
    return $env;
}
