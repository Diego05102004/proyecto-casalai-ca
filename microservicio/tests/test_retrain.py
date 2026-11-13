import csv
import json
import os
import tempfile
import unittest
from pathlib import Path
from unittest.mock import patch

import joblib

import retrain


class RetrainingTest(unittest.TestCase):
    def write_feedback(self, path, labels):
        fields = [*retrain.REQUIRED_COLUMNS]
        with path.open("w", encoding="utf-8", newline="") as feedback_file:
            writer = csv.DictWriter(feedback_file, fieldnames=fields)
            writer.writeheader()
            for index, label in enumerate(labels):
                field_error = 0.05 if label else 0.5
                writer.writerow(
                    {
                        "error_proveedor": field_error,
                        "error_correlativo": field_error,
                        "error_productos": field_error,
                        "error_subtotal": field_error,
                        "error_total": field_error,
                        "confianza_ocr_promedio": 0.6 + index * 0.05,
                    }
                )

    def test_trains_to_script_relative_paths_outside_microservice_cwd(self):
        with tempfile.TemporaryDirectory() as temporary_directory:
            root = Path(temporary_directory) / "microservice"
            workdir = Path(temporary_directory) / "elsewhere"
            (root / "entrenamiento").mkdir(parents=True)
            (root / "modelo").mkdir()
            workdir.mkdir()
            self.write_feedback(root / "entrenamiento" / "feedback_dataset.csv", [0, 1, 0, 1, 1])

            with patch.object(retrain, "BASE_DIR", root):
                previous_directory = Path.cwd()
                try:
                    os.chdir(workdir)
                    metrics = retrain.run_retraining()
                finally:
                    os.chdir(previous_directory)

            model_path = root / "modelo" / "modelo_actual.pkl"
            metrics_path = root / "entrenamiento" / "metricas_reentrenamiento.json"
            self.assertTrue(model_path.is_file())
            self.assertTrue(metrics_path.is_file())
            model = joblib.load(model_path)
            self.assertEqual(set(model.classes_.tolist()), {0, 1})
            saved_metrics = json.loads(metrics_path.read_text(encoding="utf-8"))
            self.assertEqual(saved_metrics["num_registros"], 5)
            self.assertEqual(saved_metrics["distribucion_clases"], {
                "no_confiable": 2,
                "confiable": 3,
            })
            self.assertEqual(metrics["accuracy_entrenamiento"], saved_metrics["accuracy_entrenamiento"])

    def test_refuses_less_than_five_samples_without_replacing_artifacts(self):
        with tempfile.TemporaryDirectory() as temporary_directory:
            root = Path(temporary_directory)
            feedback_path = root / "feedback.csv"
            model_path = root / "modelo_actual.pkl"
            metrics_path = root / "metrics.json"
            self.write_feedback(feedback_path, [0, 1, 0, 1])
            model_path.write_bytes(b"existing-model")
            metrics_path.write_text("existing-metrics", encoding="utf-8")

            with self.assertRaisesRegex(ValueError, "al menos 5"):
                retrain.run_retraining(feedback_path, model_path, metrics_path)

            self.assertEqual(model_path.read_bytes(), b"existing-model")
            self.assertEqual(metrics_path.read_text(encoding="utf-8"), "existing-metrics")

    def test_refuses_dataset_missing_either_class_without_replacing_model(self):
        for labels in ([1] * 5, [0] * 5):
            with self.subTest(labels=labels), tempfile.TemporaryDirectory() as temporary_directory:
                root = Path(temporary_directory)
                feedback_path = root / "feedback.csv"
                model_path = root / "modelo_actual.pkl"
                metrics_path = root / "metrics.json"
                self.write_feedback(feedback_path, labels)
                model_path.write_bytes(b"existing-model")

                with self.assertRaisesRegex(ValueError, "ambas clases"):
                    retrain.run_retraining(feedback_path, model_path, metrics_path)

                self.assertEqual(model_path.read_bytes(), b"existing-model")
                self.assertFalse(metrics_path.exists())

    def test_reliability_target_uses_all_field_errors_not_only_invoice_total(self):
        with tempfile.TemporaryDirectory() as temporary_directory:
            root = Path(temporary_directory)
            feedback_path = root / "feedback.csv"
            self.write_feedback(feedback_path, [1] * 5)
            with feedback_path.open(encoding="utf-8", newline="") as feedback_file:
                rows = list(csv.DictReader(feedback_file))
            for row in rows:
                row["error_proveedor"] = 0.5
                row["error_correlativo"] = 0.5
                row["error_productos"] = 0.5
                row["error_subtotal"] = 0.5
                row["error_total"] = 0.0
            with feedback_path.open("w", encoding="utf-8", newline="") as feedback_file:
                writer = csv.DictWriter(feedback_file, fieldnames=retrain.REQUIRED_COLUMNS)
                writer.writeheader()
                writer.writerows(rows)

            with self.assertRaisesRegex(ValueError, "ambas clases"):
                retrain.run_retraining(
                    feedback_path,
                    root / "modelo_actual.pkl",
                    root / "metrics.json",
                )

    def test_rejects_non_numeric_and_out_of_range_feedback(self):
        with tempfile.TemporaryDirectory() as temporary_directory:
            root = Path(temporary_directory)
            feedback_path = root / "feedback.csv"
            self.write_feedback(feedback_path, [0, 1, 0, 1, 1])
            with feedback_path.open(encoding="utf-8", newline="") as feedback_file:
                rows = list(csv.DictReader(feedback_file))
            rows[0]["confianza_ocr_promedio"] = "1.5"
            with feedback_path.open("w", encoding="utf-8", newline="") as feedback_file:
                writer = csv.DictWriter(feedback_file, fieldnames=retrain.REQUIRED_COLUMNS)
                writer.writeheader()
                writer.writerows(rows)

            with self.assertRaisesRegex(ValueError, "entre 0 y 1"):
                retrain.run_retraining(
                    feedback_path,
                    root / "modelo_actual.pkl",
                    root / "metrics.json",
                )


if __name__ == "__main__":
    unittest.main()
