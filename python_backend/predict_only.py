import os
import json
import argparse
from math import sqrt

from datetime import datetime

import joblib
import numpy as np
import pandas as pd
import tensorflow as tf

from sklearn.metrics import mean_squared_error


def load_and_clean_csv(csv_path: str) -> pd.DataFrame:
    expected_cols = ["Date", "Close", "High", "Low", "Open", "Volume"]

    df = pd.read_csv(csv_path)

    if all(col in df.columns for col in expected_cols):
        df = df[expected_cols].copy()
    else:
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

    return df_exp[
        ["Date", "Open_ret", "High_ret", "Low_ret", "Close_ret", "Vol_chg", "Actual_Close"]
    ].copy()


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


def future_forecast_lstm(model, X_scaler, y_scaler, data, feature_cols, time_step, future_date):
    target_date = pd.Timestamp(future_date)
    last_known_date = pd.to_datetime(data["Date"]).iloc[-1]

    if target_date <= last_known_date:
        raise ValueError(f"Future date must be after {last_known_date.date()}")

    future_days = len(pd.bdate_range(start=last_known_date + pd.Timedelta(days=1), end=target_date))
    current_window = X_scaler.transform(data[feature_cols])[-time_step:].copy()
    last_close = data["Actual_Close"].iloc[-1]

    predicted_prices = []
    predicted_returns = []
    predicted_dates = pd.bdate_range(start=last_known_date + pd.Timedelta(days=1), periods=future_days)

    for _ in range(future_days):
        X_input = current_window.reshape(1, time_step, len(feature_cols))
        pred_ret_scaled = model.predict(X_input, verbose=0)
        pred_ret = y_scaler.inverse_transform(pred_ret_scaled)[0, 0]
        next_close = last_close * (1 + pred_ret)

        predicted_returns.append(pred_ret)
        predicted_prices.append(next_close)

        next_feature_row = np.array([[pred_ret, pred_ret, pred_ret, pred_ret, 0.0]])
        next_feature_row_scaled = X_scaler.transform(pd.DataFrame(next_feature_row, columns=feature_cols))[0]

        current_window = np.vstack([current_window[1:], next_feature_row_scaled])
        last_close = next_close

    return pd.DataFrame({
        "Forecast_Date": predicted_dates,
        "Predicted_Close_Return": predicted_returns,
        "Predicted_Close_Price": predicted_prices,
    })


def future_forecast_lr(lr_model, X_scaler, y_scaler, data, feature_cols, time_step, future_date):
    target_date = pd.Timestamp(future_date)
    last_known_date = pd.to_datetime(data["Date"]).iloc[-1]

    if target_date <= last_known_date:
        raise ValueError(f"Future date must be after {last_known_date.date()}")

    future_days = len(pd.bdate_range(start=last_known_date + pd.Timedelta(days=1), end=target_date))
    current_window = X_scaler.transform(data[feature_cols])[-time_step:].copy()
    last_close = data["Actual_Close"].iloc[-1]

    predicted_prices = []
    predicted_returns = []
    predicted_dates = pd.bdate_range(start=last_known_date + pd.Timedelta(days=1), periods=future_days)

    for _ in range(future_days):
        X_input_lr = current_window.reshape(1, -1)
        pred_ret_scaled = lr_model.predict(X_input_lr).reshape(-1, 1)
        pred_ret = y_scaler.inverse_transform(pred_ret_scaled)[0, 0]
        next_close = last_close * (1 + pred_ret)

        predicted_returns.append(pred_ret)
        predicted_prices.append(next_close)

        next_feature_row = np.array([[pred_ret, pred_ret, pred_ret, pred_ret, 0.0]])
        next_feature_row_scaled = X_scaler.transform(pd.DataFrame(next_feature_row, columns=feature_cols))[0]

        current_window = np.vstack([current_window[1:], next_feature_row_scaled])
        last_close = next_close

    return pd.DataFrame({
        "Forecast_Date": predicted_dates,
        "Predicted_Close_Return": predicted_returns,
        "Predicted_Close_Price": predicted_prices,
    })
    
def future_forecast_ma(data, time_step, future_date):
    target_date = pd.Timestamp(future_date)
    last_known_date = pd.to_datetime(data["Date"]).iloc[-1]

    if target_date <= last_known_date:
        raise ValueError(f"Future date must be after {last_known_date.date()}")

    future_days = len(pd.bdate_range(start=last_known_date + pd.Timedelta(days=1), end=target_date))

    closes = data["Actual_Close"].tolist()
    predicted_prices = []
    predicted_dates = pd.bdate_range(start=last_known_date + pd.Timedelta(days=1), periods=future_days)

    for _ in range(future_days):
        next_close = float(np.mean(closes[-time_step:]))
        predicted_prices.append(next_close)
        closes.append(next_close)

    return pd.DataFrame({
        "Forecast_Date": predicted_dates,
        "Predicted_Close_Price": predicted_prices
    })


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--csv", required=True, help="Path to CSV dataset")
    parser.add_argument("--assets-dir", default="assets", help="Folder containing saved assets")
    parser.add_argument("--results-dir", default="results", help="Folder to save outputs")
    parser.add_argument("--future-date", default=None, help="Optional future date, e.g. 2026-03-15")
    args = parser.parse_args()

    os.makedirs(args.results_dir, exist_ok=True)

    with open(os.path.join(args.assets_dir, "model_config.json"), "r", encoding="utf-8") as f:
        config = json.load(f)

    feature_cols = config["feature_cols"]
    target_col = config["target_col"]
    time_step = config["time_step"]

    lstm_model = tf.keras.models.load_model(os.path.join(args.assets_dir, "lstm_model.h5"))
    lr_model = joblib.load(os.path.join(args.assets_dir, "lr_model.pkl"))
    X_scaler = joblib.load(os.path.join(args.assets_dir, "x_scaler.pkl"))
    y_scaler = joblib.load(os.path.join(args.assets_dir, "y_scaler.pkl"))

    df = load_and_clean_csv(args.csv)
    data = build_returns_dataset(df)

    X_scaled = X_scaler.transform(data[feature_cols])
    y_scaled = y_scaler.transform(data[[target_col]])

    train_size = int(len(X_scaled) * config["train_test_split_ratio"])

    X_train_raw = X_scaled[:train_size]
    X_test_raw = X_scaled[train_size:]
    y_train_raw = y_scaled[:train_size]
    y_test_raw = y_scaled[train_size:]

    actual_close = data["Actual_Close"].values
    actual_close_test = actual_close[train_size:]

    X_train, y_train = create_sequences_multivariate(X_train_raw, y_train_raw, time_step)
    X_test, y_test = create_sequences_multivariate(X_test_raw, y_test_raw, time_step)

    # LSTM
    test_pred_scaled = lstm_model.predict(X_test, verbose=0)
    test_pred_ret = y_scaler.inverse_transform(test_pred_scaled)

    prev_close_test = actual_close_test[time_step - 1:-1]
    test_pred_close = prev_close_test.reshape(-1, 1) * (1 + test_pred_ret)
    test_actual_close = actual_close_test[time_step:].reshape(-1, 1)
    lstm_rmse = sqrt(mean_squared_error(test_actual_close, test_pred_close))

    # LR
    X_test_lr = X_test.reshape(X_test.shape[0], -1)
    lr_pred_scaled = lr_model.predict(X_test_lr).reshape(-1, 1)
    lr_pred_ret = y_scaler.inverse_transform(lr_pred_scaled)
    lr_pred_close = prev_close_test.reshape(-1, 1) * (1 + lr_pred_ret)
    lr_rmse = sqrt(mean_squared_error(test_actual_close, lr_pred_close))

    # MA
    ma_preds, ma_actuals = moving_average_forecast(actual_close_test, window=time_step)
    ma_rmse = sqrt(mean_squared_error(ma_actuals, ma_preds))

    results_df = pd.DataFrame({
        "Actual_Close": test_actual_close.flatten(),
        "LSTM_Predicted_Close": test_pred_close.flatten(),
        "LR_Predicted_Close": lr_pred_close.flatten(),
        "MA_Predicted_Close": ma_preds[:len(test_actual_close)].flatten()
        if len(ma_preds) >= len(test_actual_close)
        else np.pad(ma_preds.flatten(), (0, len(test_actual_close) - len(ma_preds)), constant_values=np.nan)
    })

    test_results_path = os.path.join(args.results_dir, "predict_only_results.csv")
    results_df.to_csv(test_results_path, index=False)

    output = {
        "status": "success",
        "test_results_csv": test_results_path,
        "metrics": {
            "lstm_test_rmse": lstm_rmse,
            "linear_regression_rmse": lr_rmse,
            "moving_average_rmse": ma_rmse,
        },
    }

    if args.future_date:
        lstm_future = future_forecast_lstm(
            lstm_model, X_scaler, y_scaler, data, feature_cols, time_step, args.future_date
        )
        lr_future = future_forecast_lr(
            lr_model, X_scaler, y_scaler, data, feature_cols, time_step, args.future_date
        )
        ma_future = future_forecast_ma(
            data, time_step, args.future_date
        )

        run_id = datetime.now().strftime("%Y%m%d_%H%M%S")

        test_results_path = os.path.join(args.results_dir, f"predict_only_results_{run_id}.csv")
        lstm_future_path = os.path.join(args.results_dir, f"lstm_future_forecast_{run_id}.csv")
        lr_future_path = os.path.join(args.results_dir, f"lr_future_forecast_{run_id}.csv")
        ma_future_path = os.path.join(args.results_dir, f"ma_future_forecast_{run_id}.csv")

        lstm_future.to_csv(lstm_future_path, index=False)
        lr_future.to_csv(lr_future_path, index=False)
        ma_future.to_csv(ma_future_path, index=False)

        output["future_forecast"] = {
            "future_date": args.future_date,
            "lstm_csv": lstm_future_path,
            "lr_csv": lr_future_path,
            "ma_csv": ma_future_path,
        }

    print(json.dumps(output, indent=4))


if __name__ == "__main__":
    main()