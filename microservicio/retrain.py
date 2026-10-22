import pandas as pd
from sklearn.ensemble import RandomForestClassifier
import joblib
import os

def run_retraining():
    data_path = "entrenamiento/historical_data.csv"
    if not os.path.exists(data_path):
        print("No hay suficientes datos para reentrenar.")
        return

    df = pd.read_csv(data_path)
    # Definir características (X) y objetivo (y) según tu caso de negocio
    X = df.drop(columns=["target", "timestamp"])
    y = df["target"]

    model = RandomForestClassifier()
    model.fit(X, y)

    # Guardar el nuevo modelo temporalmente o reemplazar el actual
    model_path = "modelo/modelo_actual.pkl"
    joblib.dump(model, model_path)
    print("Modelo reentrenado y guardado exitosamente.")

if __name__ == "__main__":
    run_retraining()