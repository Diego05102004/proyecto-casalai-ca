"""Training and inference for the local, weekly-demand model."""
import json
import logging
import math
import os
import threading
from collections import Counter, defaultdict
from datetime import date, datetime, timedelta
from pathlib import Path
from typing import Any

import numpy as np

BASE_DIR = Path(__file__).resolve().parents[1]
MODEL_PATH = BASE_DIR / "analitica_predictiva" / "modelos" / "demanda.json"
FEEDBACK_PATH = BASE_DIR / "logs_metricas" / "feedback_analitica.jsonl"
_feedback_lock = threading.Lock()
_LAG_WEEKS = 4
_MIN_TRAINING_WEEKS = 24


def _date_range(start: date, days: int) -> list[date]:
    return [start + timedelta(days=offset) for offset in range(days)]


def _build_daily_series(sales: list[dict[str, Any]], product_ids: set[int]) -> dict[int, Counter]:
    daily: dict[int, Counter] = {product_id: Counter() for product_id in product_ids}
    for sale in sales:
        product_id = int(sale["id_producto"])
        if product_id in daily:
            daily[product_id][date.fromisoformat(str(sale["fecha"]))] += int(sale["cantidad"])
    return daily


def _weekly_series(daily: Counter, end_date: date) -> tuple[list[date], np.ndarray]:
    end_monday = end_date - timedelta(days=end_date.weekday() + 7)
    first_monday = end_monday - timedelta(weeks=_MIN_TRAINING_WEEKS - 1)
    weeks = [first_monday + timedelta(weeks=offset) for offset in range(_MIN_TRAINING_WEEKS)]
    totals = np.zeros(len(weeks), dtype=float)
    week_indices = {week: index for index, week in enumerate(weeks)}
    for sale_date, quantity in daily.items():
        monday = sale_date - timedelta(days=sale_date.weekday())
        if monday in week_indices:
            totals[week_indices[monday]] += quantity
    return weeks, totals


def _fit_weekly_model(weekly: np.ndarray) -> dict[str, Any] | None:
    if len(weekly) < _MIN_TRAINING_WEEKS or np.count_nonzero(weekly) < 6:
        return None

    features = []
    targets = []
    for index in range(_LAG_WEEKS, len(weekly)):
        features.append([1.0, *weekly[index - _LAG_WEEKS:index][::-1]])
        targets.append(float(weekly[index]))

    matrix = np.asarray(features, dtype=float)
    target = np.asarray(targets, dtype=float)
    penalty = np.diag([0.0, 0.25, 0.25, 0.25, 0.25])
    coefficients = np.linalg.solve(matrix.T @ matrix + penalty, matrix.T @ target)
    residuals = target - matrix @ coefficients
    error = float(np.sqrt(np.mean(residuals ** 2)))

    return {
        "coefficients": coefficients.tolist(),
        "error": error,
        "training_rows": int(len(target)),
        "history_weeks": int(len(weekly)),
    }


def train_models(products: list[dict[str, Any]], sales: list[dict[str, Any]]) -> dict[str, Any]:
    today = date.today()
    oldest = today - timedelta(days=365)
    relevant_sales = [
        sale for sale in sales
        if oldest <= date.fromisoformat(str(sale["fecha"])) <= today
    ]
    active_products = {
        int(product["id_producto"]): product for product in products
        if str(product.get("estado", "habilitado")) == "habilitado"
    }
    daily_by_product = _build_daily_series(relevant_sales, set(active_products))
    groups: dict[str, dict[str, Any]] = {}
    non_trainable = 0

    for product_id, daily in daily_by_product.items():
        weeks, weekly = _weekly_series(daily, today)
        model = _fit_weekly_model(weekly)
        if model is None:
            non_trainable += 1
            continue
        groups[f"producto:{product_id}"] = {
            "coefficients": model["coefficients"],
            "error": model["error"],
            "training_rows": model["training_rows"],
            "history_weeks": model["history_weeks"],
        }

    category_daily: dict[int, Counter] = defaultdict(Counter)
    for product_id, product in active_products.items():
        category_id = product.get("id_categoria")
        if category_id is not None:
            category_daily[int(category_id)].update(daily_by_product[product_id])

    for category_id, daily in category_daily.items():
        _, weekly = _weekly_series(daily, today)
        model = _fit_weekly_model(weekly)
        if model is not None:
            groups[f"categoria:{category_id}"] = {
                "coefficients": model["coefficients"],
                "error": model["error"],
                "training_rows": model["training_rows"],
                "history_weeks": model["history_weeks"],
            }

    if not groups:
        return {
            "estado": "insuficiente_historial",
            "version": "ridge-lag4-v1",
            "trained_at": None,
            "modelos_entrenados": 0,
            "productos_sin_historial_suficiente": non_trainable,
            "minimo_semanas": _MIN_TRAINING_WEEKS,
        }

    artifact = {
        "version": "ridge-lag4-v1",
        "trained_at": datetime.now().astimezone().isoformat(timespec="seconds"),
        "source": "facturas_pagadas",
        "models": groups,
    }
    MODEL_PATH.parent.mkdir(parents=True, exist_ok=True)
    temporary_path = MODEL_PATH.with_suffix(f".{os.getpid()}.tmp")
    temporary_path.write_text(json.dumps(artifact, ensure_ascii=False), encoding="utf-8")
    os.replace(temporary_path, MODEL_PATH)
    return {
        "estado": "entrenamiento_completado",
        "version": artifact["version"],
        "trained_at": artifact["trained_at"],
        "modelos_entrenados": len(groups),
        "productos_sin_historial_suficiente": non_trainable,
        "minimo_semanas": _MIN_TRAINING_WEEKS,
    }


def _load_artifact() -> dict[str, Any]:
    if not MODEL_PATH.is_file():
        return {"version": None, "trained_at": None, "models": {}}
    try:
        artifact = json.loads(MODEL_PATH.read_text(encoding="utf-8"))
    except json.JSONDecodeError as error:
        logging.exception("El artefacto de analítica predictiva no es JSON válido")
        raise RuntimeError("El modelo guardado está dañado; vuelva a entrenarlo.") from error
    if not isinstance(artifact, dict) or not isinstance(artifact.get("models"), dict):
        raise RuntimeError("El formato del modelo guardado no es válido.")
    return artifact


def _aggregate_sales(
    sales: list[dict[str, Any]],
    product_ids: set[int],
    start_date: date,
    end_date: date,
) -> tuple[Counter, dict[int, Counter], dict[int, set[int]]]:
    daily_total: Counter = Counter()
    daily_products: dict[int, Counter] = {product_id: Counter() for product_id in product_ids}
    invoice_products: dict[int, set[int]] = defaultdict(set)
    for sale in sales:
        sale_date = date.fromisoformat(str(sale["fecha"]))
        product_id = int(sale["id_producto"])
        if product_id not in product_ids:
            continue
        quantity = int(sale["cantidad"])
        if start_date <= sale_date <= end_date:
            daily_products[product_id][sale_date] += quantity
            daily_total[sale_date] += quantity
        invoice_products[int(sale["id_factura"])].add(product_id)
    return daily_total, daily_products, invoice_products


def _bucket_history(daily: Counter, frequency: str, today: date) -> list[dict[str, Any]]:
    if frequency == "diaria":
        start = today - timedelta(days=89)
        return [
            {"fecha": day.isoformat(), "valor": int(daily[day])}
            for day in _date_range(start, 90)
        ]
    if frequency == "semanal":
        end_week = today - timedelta(days=today.weekday())
        start_week = end_week - timedelta(weeks=25)
        totals: Counter = Counter()
        for sale_date, quantity in daily.items():
            monday = sale_date - timedelta(days=sale_date.weekday())
            totals[monday] += quantity
        return [
            {"fecha": (start_week + timedelta(weeks=offset)).isoformat(),
             "valor": int(totals[start_week + timedelta(weeks=offset)])}
            for offset in range(26)
        ]

    start_month = date(today.year, today.month, 1)
    months = []
    for offset in range(11, -1, -1):
        year = start_month.year
        month = start_month.month - offset
        while month <= 0:
            month += 12
            year -= 1
        months.append(date(year, month, 1))
    totals: Counter = Counter()
    for sale_date, quantity in daily.items():
        totals[date(sale_date.year, sale_date.month, 1)] += quantity
    return [{"fecha": month.isoformat(), "valor": int(totals[month])} for month in months]


def _forecast_daily(model: dict[str, Any], weekly: np.ndarray, horizon: int) -> list[dict[str, Any]]:
    history = [float(value) for value in weekly[-_LAG_WEEKS:]]
    coefficients = np.asarray(model["coefficients"], dtype=float)
    error = max(0.0, float(model["error"]))
    today = date.today()
    start = today + timedelta(days=1)
    daily_predictions = []
    for week_index in range(math.ceil(horizon / 7)):
        features = np.asarray([1.0, *history[-_LAG_WEEKS:][::-1]], dtype=float)
        predicted_week = max(0.0, float(features @ coefficients))
        lower_week = max(0.0, predicted_week - 1.96 * error)
        upper_week = predicted_week + 1.96 * error
        history.append(predicted_week)
        days_this_week = min(7, horizon - week_index * 7)
        for day_offset in range(days_this_week):
            prediction_date = start + timedelta(days=week_index * 7 + day_offset)
            daily_predictions.append({
                "fecha": prediction_date,
                "valor": predicted_week / 7.0,
                "limite_inferior": lower_week / 7.0,
                "limite_superior": upper_week / 7.0,
            })
    return daily_predictions


def _bucket_forecast(predictions: list[dict[str, Any]], frequency: str) -> list[dict[str, Any]]:
    if frequency == "diaria":
        return [
            {
                "fecha": item["fecha"].isoformat(),
                "valor": round(item["valor"], 2),
                "limite_inferior": round(item["limite_inferior"], 2),
                "limite_superior": round(item["limite_superior"], 2),
            }
            for item in predictions
        ]

    totals: dict[date, dict[str, float]] = {}
    for item in predictions:
        key = item["fecha"] - timedelta(days=item["fecha"].weekday()) if frequency == "semanal" else date(
            item["fecha"].year, item["fecha"].month, 1
        )
        bucket = totals.setdefault(key, {"valor": 0.0, "limite_inferior": 0.0, "limite_superior": 0.0})
        for metric in bucket:
            bucket[metric] += item[metric]
    return [
        {
            "fecha": bucket_date.isoformat(),
            "valor": round(values["valor"], 2),
            "limite_inferior": round(values["limite_inferior"], 2),
            "limite_superior": round(values["limite_superior"], 2),
        }
        for bucket_date, values in sorted(totals.items())
    ]


def _feedback_averages() -> dict[str, tuple[float, int]]:
    if not FEEDBACK_PATH.is_file():
        return {}
    latest_votes: dict[tuple[str, str], tuple[str, int]] = {}
    with _feedback_lock, FEEDBACK_PATH.open("r", encoding="utf-8") as feedback_file:
        for line_number, line in enumerate(feedback_file, start=1):
            try:
                entry = json.loads(line)
                recommendation_type = str(entry["tipo"])
                recommendation_id = str(entry["recomendacion_id"])
                stars = int(entry["estrellas"])
            except (json.JSONDecodeError, KeyError, TypeError, ValueError):
                logging.warning("Se omite feedback inválido en línea %s", line_number)
                continue
            if 1 <= stars <= 5:
                user_id = str(entry.get("usuario_id") or "anonimo")
                latest_votes[(user_id, recommendation_id)] = (recommendation_type, stars)

    totals: Counter = Counter()
    counts: Counter = Counter()
    for recommendation_type, stars in latest_votes.values():
        totals[recommendation_type] += stars
        counts[recommendation_type] += 1
    return {
        kind: (totals[kind] / counts[kind], counts[kind])
        for kind in counts
    }


def _recommendation_id(kind: str, item_ids: list[int]) -> str:
    normalized = ",".join(str(item_id) for item_id in sorted(item_ids))
    return f"{kind}:{normalized}"


def _confidence_level(confidence: float) -> str:
    if confidence >= 0.7:
        return "alta"
    if confidence >= 0.4:
        return "media"
    return "baja"


def build_dashboard(
    payload: dict[str, Any],
    feedback: dict[str, tuple[float, int]] | None = None,
) -> dict[str, Any]:
    products = payload["productos"]
    sales = payload["ventas"]
    selected_product = payload.get("producto_id")
    selected_category = payload.get("categoria_id")
    frequency = payload["frecuencia"]
    horizon = int(payload["horizonte"])
    today = date.today()
    feedback = feedback or {}
    product_by_id = {int(product["id_producto"]): product for product in products}

    if selected_product is not None:
        selected_ids = {int(selected_product)} if int(selected_product) in product_by_id else set()
        model_key = f"producto:{int(selected_product)}"
    else:
        selected_ids = {
            product_id for product_id, product in product_by_id.items()
            if int(product.get("id_categoria") or 0) == int(selected_category)
        }
        model_key = f"categoria:{int(selected_category)}"
    if not selected_ids:
        return {"calidad": {"estado": "sin_datos"}, "historico": [], "prediccion": [], "metricas": {}}

    start_date = today - timedelta(days=365)
    daily_total, daily_by_product, invoice_products = _aggregate_sales(sales, selected_ids, start_date, today)
    _, _, all_invoice_products = _aggregate_sales(sales, set(product_by_id), start_date, today)
    if not daily_total:
        return {
            "unidad": "unidades",
            "frecuencia": frequency,
            "horizonte_dias": horizon,
            "historico": [],
            "prediccion": [],
            "metricas": {
                "venta_proyectada": None,
                "dias_estimados_agotamiento": None,
                "punto_reorden": None,
                "cantidad_sugerida_compra": None,
                "rotacion_proyectada": None,
            },
            "calidad": {"estado": "sin_ventas"},
            "recomendaciones": [],
        }
    historical = _bucket_history(daily_total, frequency, today)
    artifact = _load_artifact()
    saved_model = artifact["models"].get(model_key)
    prediction = []
    metrics: dict[str, Any] = {
        "venta_proyectada": None,
        "dias_estimados_agotamiento": None,
        "punto_reorden": None,
        "cantidad_sugerida_compra": None,
        "rotacion_proyectada": None,
    }
    quality: dict[str, Any] = {
        "modelo": artifact.get("version") or "ridge-lag4-v1",
        "estado": "modelo_pendiente",
        "fecha_entrenamiento": artifact.get("trained_at"),
    }

    if selected_product is not None:
        stock_products = [product_by_id[int(selected_product)]]
    else:
        stock_products = [product_by_id[product_id] for product_id in selected_ids]
    stocks_known = all(product.get("stock") is not None for product in stock_products)
    minimums_known = all(product.get("stock_minimo") is not None for product in stock_products)
    current_stock = sum(int(product["stock"]) for product in stock_products) if stocks_known else None
    minimum_stock = sum(int(product["stock_minimo"]) for product in stock_products) if minimums_known else None
    daily_prediction: list[dict[str, Any]] = []

    if saved_model is not None:
        _, weekly = _weekly_series(daily_total, today)
        daily_prediction = _forecast_daily(saved_model, weekly, horizon)
        prediction = _bucket_forecast(daily_prediction, frequency)
        projected = sum(item["valor"] for item in daily_prediction)
        quality.update({
            "estado": "disponible",
            "semanas_entrenamiento": saved_model["history_weeks"],
            "muestras_entrenamiento": saved_model["training_rows"],
        })
        metrics["venta_proyectada"] = round(projected, 2)
        cumulative = 0.0
        if selected_product is not None and current_stock is not None:
            if current_stock == 0 and projected > 0:
                metrics["dias_estimados_agotamiento"] = 0
            else:
                for offset, item in enumerate(daily_prediction, start=1):
                    cumulative += item["valor"]
                    if cumulative >= current_stock and current_stock > 0:
                        metrics["dias_estimados_agotamiento"] = offset
                        break
            ending_stock = max(0.0, current_stock - projected)
            average_stock = (current_stock + ending_stock) / 2.0
            metrics["rotacion_proyectada"] = round(projected / average_stock, 2) if average_stock > 0 else None
        if selected_product is not None and minimum_stock is not None:
            metrics["punto_reorden"] = minimum_stock
        if selected_product is not None and current_stock is not None and minimum_stock is not None:
            metrics["cantidad_sugerida_compra"] = max(
                0, math.ceil(projected + minimum_stock - current_stock)
            )
        quality["error_semanal_estimado"] = round(float(saved_model["error"]), 2)
    elif _fit_weekly_model(_weekly_series(daily_total, today)[1]) is None:
        quality["estado"] = "insuficiente_historial"

    inventory_actions = []
    for product_id, product in product_by_id.items():
        if product_id not in selected_ids and selected_product is not None:
            continue
        if product.get("stock") is None or product.get("stock_minimo") is None:
            continue
        stock = int(product["stock"])
        min_stock = int(product["stock_minimo"])
        product_model = artifact["models"].get(f"producto:{product_id}")
        if product_model is None:
            continue
        # Si el producto no tiene registros de ventas diarias, salta al siguiente o asígnale vacío
        if product_id not in daily_by_product:
             continue   # Si estás dentro de un ciclo 'for', usa continue para saltarlo de forma segura.
        _, product_weekly = _weekly_series(daily_by_product[product_id], today)
        forecast = _forecast_daily(product_model, product_weekly, min(horizon, 90))
        projected_demand = sum(item["valor"] for item in forecast)
        suggested = max(0, math.ceil(projected_demand + min_stock - stock))
        if stock > min_stock and projected_demand < stock:
            continue
        confidence = max(
            0.0,
            min(0.99, 1.0 - float(product_model["error"]) / (float(np.mean(product_weekly)) + 1.0)),
        )
        average_rating, rating_count = feedback.get("reposicion", (0.0, 0))
        feedback_factor = 1.0 if rating_count == 0 else 0.7 + 0.3 * average_rating / 5.0
        inventory_actions.append({
            "recomendacion_id": _recommendation_id("reposicion", [product_id]),
            "tipo": "reposicion",
            "producto": product["nombre_producto"],
            "producto_id": product_id,
            "senal": "Stock bajo el mínimo configurado" if stock <= min_stock else "Demanda prevista supera el stock",
            "cantidad_sugerida": suggested,
            "confianza": round(confidence, 2),
            "nivel_confianza": _confidence_level(confidence),
            "calculado_en": datetime.now().astimezone().isoformat(timespec="seconds"),
            "retroalimentacion": {"promedio": round(average_rating, 2) if rating_count else None, "respuestas": rating_count},
            "prioridad_ajustada": round(confidence * feedback_factor, 3),
        })

    invoice_frequency: Counter = Counter()
    pair_frequency: Counter = Counter()
    for product_ids in all_invoice_products.values():
        invoice_frequency.update(product_ids)
        ordered_ids = sorted(product_ids)
        for first_index, first_id in enumerate(ordered_ids):
            for second_id in ordered_ids[first_index + 1:]:
                if selected_product is not None and int(selected_product) not in (first_id, second_id):
                    continue
                if selected_category is not None and not ({first_id, second_id} & selected_ids):
                    continue
                pair_frequency[(first_id, second_id)] += 1
    combo_actions = []
    invoice_count = len(all_invoice_products)
    for pair, pair_count in pair_frequency.items():
        first_id, second_id = pair
        if pair_count < 2 or first_id not in product_by_id or second_id not in product_by_id:
            continue
        first_confidence = pair_count / max(1, invoice_frequency[first_id])
        second_confidence = pair_count / max(1, invoice_frequency[second_id])
        confidence = max(first_confidence, second_confidence)
        support = pair_count / max(1, invoice_count)
        average_rating, rating_count = feedback.get("combo", (0.0, 0))
        feedback_factor = 1.0 if rating_count == 0 else 0.7 + 0.3 * average_rating / 5.0
        combo_actions.append({
            "recomendacion_id": _recommendation_id("combo", [first_id, second_id]),
            "tipo": "combo",
            "producto": f"{product_by_id[first_id]['nombre_producto']} + {product_by_id[second_id]['nombre_producto']}",
            "producto_id": first_id,
            "productos": [first_id, second_id],
            "senal": f"Comprados juntos en {pair_count} facturas",
            "cantidad_sugerida": None,
            "confianza": round(confidence, 2),
            "nivel_confianza": _confidence_level(confidence),
            "soporte": round(support, 3),
            "calculado_en": datetime.now().astimezone().isoformat(timespec="seconds"),
            "retroalimentacion": {"promedio": round(average_rating, 2) if rating_count else None, "respuestas": rating_count},
            "prioridad_ajustada": round(confidence * support * feedback_factor, 3),
        })
    recommendations = sorted(
        inventory_actions + combo_actions,
        key=lambda item: item["prioridad_ajustada"],
        reverse=True,
    )[:20]

    return {
        "unidad": "unidades",
        "frecuencia": frequency,
        "horizonte_dias": horizon,
        "historico": historical,
        "prediccion": prediction,
        "metricas": metrics,
        "calidad": quality,
        "recomendaciones": recommendations,
        "explicaciones": {
            "cantidad_sugerida_compra": "Demanda prevista del horizonte + stock mínimo configurado - stock actual. No descuenta órdenes de compra abiertas.",
            "rotacion_proyectada": "Unidades previstas / inventario promedio estimado durante el horizonte.",
            "fuente": "Facturas con estado Pagada o Pagada Presencialmente.",
        },
    }


def append_feedback(feedback: dict[str, Any]) -> dict[str, Any]:
    FEEDBACK_PATH.parent.mkdir(parents=True, exist_ok=True)
    record = {
        **feedback,
        "fecha": datetime.now().astimezone().isoformat(timespec="seconds"),
    }
    with _feedback_lock, FEEDBACK_PATH.open("a", encoding="utf-8") as feedback_file:
        feedback_file.write(json.dumps(record, ensure_ascii=False) + "\n")
        feedback_file.flush()
        os.fsync(feedback_file.fileno())
    return {"guardado": True, "fecha": record["fecha"]}


def get_feedback_averages() -> dict[str, tuple[float, int]]:
    return _feedback_averages()
