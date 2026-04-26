import os
import json
import random
import argparse
from math import sqrt

import joblib
import numpy as np
import pandas as pd
import tensorflow as tf
import matplotlib.pyplot as plt

from sklearn.preprocessing import MinMaxScaler
from sklearn.metrics import mean_squared_error
from sklearn.linear_model import LinearRegression

from tensorflow.keras.models import Sequential
from tensorflow.keras.layers import LSTM, Dense
from tensorflow.keras.callbacks import EarlyStopping


SEED = 42
os.environ["PYTHONHASHSEED"] = str(SEED)
random.seed(SEED)
np.random.seed(SEED)
tf.random.set_seed(SEED)


def load_and_clean_csv(csv_path: str) -> pd.DataFrame:
    expected_cols = ["Date", "Close", "High", "Low", "Open", "Volume"]

    # Try clean format first
    df = pd.read_csv(csv_path)

    if all(col in df.columns for col in expected_cols):
        df = df[expected_cols].copy()
    else:
        # Fallback for old Yahoo export format
        df = pd.read_csv(csv_path, skiprows=3, header=None)
        df.columns = expected_cols

    df["Date"] = pd.to_datetime(df["Date"], errors="coerce")

    for col in ["Close", "High", "Low", "Open", "Volume"]:
        df[col] = pd.to_numeric(df[col], errors="coerce")

    df = df.sort_values("Date").reset_index(drop=True)
    df = df.drop_duplicates()
    df = df.ffill().bfill().dropna()

    return df


def build_returns_dataset(df: pd.DataFrame) -> pd.DataFrame:
    df_exp = df.copy()

    df_exp["Open_ret"] = df_exp["Open"].pct_change()
    df_exp["High_ret"] = df_exp["High"].pct_change()
    df_exp["Low_ret"] = df_exp["Low"].pct_change()
    df_exp["Close_ret"] = df_exp["Close"].pct_change()
    df_exp["Vol_chg"] = df_exp["Volume"].pct_change()

    df_exp["Actual_Close"] = df_exp["Close"]

    df_exp = df_exp.replace([np.inf, -np.inf], np.nan)
    df_exp = df_exp.dropna().reset_index(drop=True)

    data = df_exp[
        ["Date", "Open_ret", "High_ret", "Low_ret", "Close_ret", "Vol_chg", "Actual_Close"]
    ].copy()

    return data


def create_sequences_multivariate(X_data: np.ndarray, y_data: np.ndarray, time_step: int = 30):
    X_seq, y_seq = [], []
    for i in range(time_step, len(X_data)):
        X_seq.append(X_data[i - time_step:i, :])
        y_seq.append(y_data[i, 0])
    return np.array(X_seq), np.array(y_seq)


def moving_average_forecast(series: np.ndarray, window: int = 30):
    preds = []
    actuals = []
    for i in range(window, len(series)):
        preds.append(np.mean(series[i - window:i]))
        actuals.append(series[i])
    return np.array(preds), np.array(actuals)


def build_lstm(input_shape: tuple[int, int]) -> Sequential:
    model = Sequential()
    model.add(LSTM(32, input_shape=input_shape))
    model.add(Dense(1))
    model.compile(optimizer="adam", loss="mean_squared_error")
    return model


def save_assets(
    assets_dir: str,
    lstm_model: Sequential,
    lr_model: LinearRegression,
    X_scaler: MinMaxScaler,
    y_scaler: MinMaxScaler,
    feature_cols: list[str],
    target_col: str,
    time_step: int,
    train_rmse: float,
    test_rmse: float,
    lr_rmse: float,
    ma_rmse: float,
):
    os.makedirs(assets_dir, exist_ok=True)

    lstm_model.save(os.path.join(assets_dir, "lstm_model.h5"))
    joblib.dump(lr_model, os.path.join(assets_dir, "lr_model.pkl"))
    joblib.dump(X_scaler, os.path.join(assets_dir, "x_scaler.pkl"))
    joblib.dump(y_scaler, os.path.join(assets_dir, "y_scaler.pkl"))

    model_config = {
        "dataset_name": "uploaded_dataset",
        "feature_engineering": "returns-based OHLCV",
        "feature_cols": feature_cols,
        "target_col": target_col,
        "actual_close_col": "Actual_Close",
        "time_step": time_step,
        "train_test_split_ratio": 0.8,
        "scaling_method": "MinMaxScaler",
        "evaluation_metric": "RMSE",
        "lstm": {
            "model_type": "LSTM",
            "units": 32,
            "dense_output": 1,
            "epochs": 100,
            "batch_size": 16,
            "optimizer": "adam",
            "loss": "mean_squared_error",
            "early_stopping_patience": 10,
        },
        "linear_regression": {
            "model_type": "LinearRegression",
        },
        "moving_average": {
            "model_type": "MovingAverage",
            "window_size": time_step,
        },
        "reconstruction_logic": {
            "prediction_target": "next-day close return",
            "predicted_close_formula": "previous_actual_close * (1 + predicted_return)",
        },
        "performance": {
            "lstm_train_rmse": float(train_rmse),
            "lstm_test_rmse": float(test_rmse),
            "linear_regression_rmse": float(lr_rmse),
            "moving_average_rmse": float(ma_rmse),
        },
    }

    with open(os.path.join(assets_dir, "model_config.json"), "w", encoding="utf-8") as f:
        json.dump(model_config, f, indent=4)


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--csv", required=True, help="Path to uploaded CSV dataset")
    parser.add_argument("--assets-dir", default="assets", help="Folder to save models/scalers/config")
    parser.add_argument("--results-dir", default="results", help="Folder to save result CSVs")
    parser.add_argument("--time-step", type=int, default=30)
    args = parser.parse_args()

    os.makedirs(args.results_dir, exist_ok=True)

    feature_cols = ["Open_ret", "High_ret", "Low_ret", "Close_ret", "Vol_chg"]
    target_col = "Close_ret"

    df = load_and_clean_csv(args.csv)
    data = build_returns_dataset(df)

    X_scaler = MinMaxScaler(feature_range=(0, 1))
    y_scaler = MinMaxScaler(feature_range=(0, 1))

    X_scaled = X_scaler.fit_transform(data[feature_cols])
    y_scaled = y_scaler.fit_transform(data[[target_col]])

    train_size = int(len(X_scaled) * 0.8)

    X_train_raw = X_scaled[:train_size]
    X_test_raw = X_scaled[train_size:]
    y_train_raw = y_scaled[:train_size]
    y_test_raw = y_scaled[train_size:]

    actual_close = data["Actual_Close"].values
    actual_close_train = actual_close[:train_size]
    actual_close_test = actual_close[train_size:]

    X_train, y_train = create_sequences_multivariate(X_train_raw, y_train_raw, args.time_step)
    X_test, y_test = create_sequences_multivariate(X_test_raw, y_test_raw, args.time_step)

    # LSTM
    model = build_lstm((X_train.shape[1], X_train.shape[2]))
    early_stop = EarlyStopping(monitor="val_loss", patience=10, restore_best_weights=True)

    model.fit(
        X_train,
        y_train,
        epochs=100,
        batch_size=16,
        validation_data=(X_test, y_test),
        callbacks=[early_stop],
        verbose=0,
    )

    train_pred_scaled = model.predict(X_train, verbose=0)
    test_pred_scaled = model.predict(X_test, verbose=0)

    train_pred_ret = y_scaler.inverse_transform(train_pred_scaled)
    test_pred_ret = y_scaler.inverse_transform(test_pred_scaled)

    prev_close_train = actual_close_train[args.time_step - 1:-1]
    prev_close_test = actual_close_test[args.time_step - 1:-1]

    train_pred_close = prev_close_train.reshape(-1, 1) * (1 + train_pred_ret)
    test_pred_close = prev_close_test.reshape(-1, 1) * (1 + test_pred_ret)

    train_actual_close = actual_close_train[args.time_step:].reshape(-1, 1)
    test_actual_close = actual_close_test[args.time_step:].reshape(-1, 1)

    train_rmse = sqrt(mean_squared_error(train_actual_close, train_pred_close))
    test_rmse = sqrt(mean_squared_error(test_actual_close, test_pred_close))

    # Linear Regression
    X_train_lr = X_train.reshape(X_train.shape[0], -1)
    X_test_lr = X_test.reshape(X_test.shape[0], -1)

    lr_model = LinearRegression()
    lr_model.fit(X_train_lr, y_train)

    lr_pred_scaled = lr_model.predict(X_test_lr).reshape(-1, 1)
    lr_pred_ret = y_scaler.inverse_transform(lr_pred_scaled)
    lr_pred_close = prev_close_test.reshape(-1, 1) * (1 + lr_pred_ret)
    lr_rmse = sqrt(mean_squared_error(test_actual_close, lr_pred_close))

    # Moving Average
    ma_preds, ma_actuals = moving_average_forecast(actual_close_test, window=args.time_step)
    ma_rmse = sqrt(mean_squared_error(ma_actuals, ma_preds))

    # Save comparison results
    results_df = pd.DataFrame({
        "Actual_Close": test_actual_close.flatten(),
        "LSTM_Predicted_Close": test_pred_close.flatten(),
        "LR_Predicted_Close": lr_pred_close.flatten(),
        "MA_Predicted_Close": ma_preds[:len(test_actual_close)].flatten()
        if len(ma_preds) >= len(test_actual_close)
        else np.pad(ma_preds.flatten(), (0, len(test_actual_close) - len(ma_preds)), constant_values=np.nan)
    })

    # Save comparison chart
    chart_path = os.path.join(args.results_dir, "training_comparison_chart.png")

    min_len = min(len(test_actual_close), len(test_pred_close), len(lr_pred_close), len(ma_preds))

    plt.figure(figsize=(12, 6))
    plt.plot(test_actual_close[:min_len], label="Actual")
    plt.plot(test_pred_close[:min_len], label="LSTM")
    plt.plot(lr_pred_close[:min_len], label="Linear Regression")
    plt.plot(ma_preds[:min_len], label="Moving Average")
    plt.title("Actual vs LSTM vs Linear Regression vs Moving Average")
    plt.xlabel("Time")
    plt.ylabel("Close Price")
    plt.legend()
    plt.tight_layout()
    plt.savefig(chart_path)
    plt.close()

    comparison_csv = os.path.join(args.results_dir, "test_results_comparison.csv")
    results_df.to_csv(comparison_csv, index=False)

    save_assets(
        args.assets_dir,
        model,
        lr_model,
        X_scaler,
        y_scaler,
        feature_cols,
        target_col,
        args.time_step,
        train_rmse,
        test_rmse,
        lr_rmse,
        ma_rmse,
    )

    summary = {
        "status": "success",
        "assets_dir": args.assets_dir,
        "results_csv": comparison_csv,
        "chart_path": chart_path,
        "metrics": {
            "lstm_train_rmse": train_rmse,
            "lstm_test_rmse": test_rmse,
            "linear_regression_rmse": lr_rmse,
            "moving_average_rmse": ma_rmse,
        },
        "best_model": (
            "LSTM"
            if test_rmse < lr_rmse and test_rmse < ma_rmse
            else "LinearRegression"
            if lr_rmse < test_rmse and lr_rmse < ma_rmse
            else "MovingAverage"
        ),
    }

    print(json.dumps(summary, indent=4))

if __name__ == "__main__":
    main()