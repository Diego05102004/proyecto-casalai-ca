# Contrato propuesto: Analítica predictiva

## Estado actual

El microservicio implementado cubre recepción de facturas (`/fase1/...`); no expone un endpoint de predicción de demanda. El reporte existente de rotación calcula días promedio en inventario, pero no es una serie temporal ni una proyección. Por eso, el dashboard no debe mostrar predicciones simuladas como datos reales.

## Datos requeridos

Antes de entrenar o publicar predicciones hay que confirmar la fuente de ventas/transacciones, la semántica de devoluciones y anulaciones, el historial de existencias y tiempos de reposición por proveedor. Para una serie de demanda hacen falta fechas y unidades vendidas, agregables por producto/categoría y frecuencia; el stock actual y mínimo por sí solos no permiten calcular agotamiento ni cantidad sugerida de compra.

## API propuesta

`GET /api/v1/analitica/predicciones?producto_id=...&frecuencia=diaria&horizonte=30`

Parámetros:

- `producto_id` o `categoria_id` (exactamente uno).
- `frecuencia`: `diaria`, `semanal` o `mensual`.
- `horizonte`: 15, 30, 60 o 90 días.
- `desde`/`hasta`: ventana histórica opcional y validada por el servicio.

Respuesta propuesta:

```json
{
  "unidad": "unidades",
  "frecuencia": "diaria",
  "historico": [{"fecha": "2026-09-01", "valor": 12}],
  "prediccion": [{"fecha": "2026-10-02", "valor": 14, "limite_inferior": 9, "limite_superior": 19}],
  "metricas": {
    "dias_estimados_agotamiento": null,
    "punto_reorden": null,
    "cantidad_sugerida_compra": null,
    "rotacion_proyectada": null
  },
  "calidad": {"modelo": "pendiente", "estado": "insuficiente_historial"}
}
```

Los KPI operativos requieren reglas aprobadas por negocio: el agotamiento se estima al cruzar demanda acumulada prevista con stock disponible; el punto de reorden depende de demanda durante el lead time más stock de seguridad; la compra sugerida resta el stock utilizable y órdenes abiertas a esa necesidad. La definición de rotación y los períodos deben documentarse para que gerencia interprete el valor de forma consistente.

## Especificación del dashboard

- Chart.js (ya utilizado en reportes PHP) con histórico real como línea continua y predicción como línea discontinua.
- Banda de incertidumbre sombreada entre límites inferior y superior; indicar unidades y horizonte en tooltip/leyenda.
- KPI de venta proyectada, días hasta agotamiento, punto de reorden, cantidad sugerida y rotación, con unidades y explicación breve.
- Filtros dependientes de producto/categoría, frecuencia y horizontes de 15/30/60/90 días.
- Estados de carga, sin datos, historial insuficiente y error de API; no presentar valores nulos como cero.
- Tabla de acciones recomendadas con producto, señal, cantidad sugerida, nivel de confianza y fecha de cálculo.

La vista PHP y su conexión con Chart.js deben implementarse cuando se definan la fuente histórica y el endpoint; hasta entonces, esta especificación es un contrato de trabajo y no una predicción disponible para usuarios.