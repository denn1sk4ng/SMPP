import os
import json
import argparse
import pandas as pd
import yfinance as yf


PRESETS = {
    "sp500": "^GSPC",
    "apple": "AAPL",
    "microsoft": "MSFT",
    "tesla": "TSLA",
    "nvidia": "NVDA",
    "amazon": "AMZN",
    "google": "GOOGL",
    "meta": "META",
}


def fetch_dataset(ticker: str, start_date: str, end_date: str) -> pd.DataFrame:
    df = yf.download(ticker, start=start_date, end=end_date, auto_adjust=False)

    if df.empty:
        raise ValueError("No data found for the selected ticker and date range.")

    # Flatten MultiIndex columns if present
    if isinstance(df.columns, pd.MultiIndex):
        df.columns = df.columns.get_level_values(0)

    df = df.reset_index()

    expected_cols = ["Date", "Close", "High", "Low", "Open", "Volume"]
    df = df[expected_cols].copy()

    # Force correct types
    df["Date"] = pd.to_datetime(df["Date"]).dt.strftime("%Y-%m-%d")
    for col in ["Close", "High", "Low", "Open", "Volume"]:
        df[col] = pd.to_numeric(df[col], errors="coerce")

    df = df.dropna().reset_index(drop=True)

    return df


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--ticker", required=False, help="Ticker symbol, e.g. ^GSPC")
    parser.add_argument("--preset", required=False, help="Preset name, e.g. sp500")
    parser.add_argument("--start-date", required=True, help="YYYY-MM-DD")
    parser.add_argument("--end-date", required=True, help="YYYY-MM-DD")
    parser.add_argument("--output-dir", default="datasets", help="Folder to save CSV")
    args = parser.parse_args()

    if args.preset:
        preset_key = args.preset.strip().lower()
        if preset_key not in PRESETS:
            raise ValueError(f"Unknown preset: {args.preset}")
        ticker = PRESETS[preset_key]
        dataset_label = preset_key
    elif args.ticker:
        ticker = args.ticker.strip().upper()
        dataset_label = ticker.replace("^", "")
    else:
        raise ValueError("Either --preset or --ticker must be provided.")

    os.makedirs(args.output_dir, exist_ok=True)

    df = fetch_dataset(ticker, args.start_date, args.end_date)

    safe_name = f"{dataset_label}_{args.start_date}_{args.end_date}.csv".replace(":", "_")
    csv_path = os.path.join(args.output_dir, safe_name)

    df.to_csv(csv_path, index=False)

    preview_rows = df.head(10).to_dict(orient="records")

    output = {
        "status": "success",
        "ticker": ticker,
        "file_name": safe_name,
        "file_path": os.path.abspath(csv_path),
        "row_count": int(len(df)),
        "preview_rows": preview_rows,
    }

    print(json.dumps(output, indent=4))


if __name__ == "__main__":
    main()