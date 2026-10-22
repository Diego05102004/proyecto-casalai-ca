import pandas as pd
import os
from datetime import datetime

DATA_PATH = "microservicio/modelo/historical_data.csv"

def log_prediction_data(input_features: dict, prediction_result: dict):
    """Almacena los datos de entrada y la predicción generada para auditoría y reentrenamiento."""
    record = {**input_features, **prediction_result, "timestamp": datetime.now()}
    df_new = pd.DataFrame([record])
    
    if os.path.exists(DATA_PATH):
        df_new.to_csv(DATA_PATH, mode='a', header=False, index=False)
    else:
        df_new.to_csv(DATA_PATH, mode='w', header=True, index=False)