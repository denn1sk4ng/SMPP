@extends('layouts.app')

@section('content')
<div class="module-page">
    <div class="module-wrapper">
        <div class="module-card">
            <h1 class="module-title">Dataset Input</h1>
            <p class="module-subtitle">
                Choose either automatic dataset fetching or manual CSV upload.
            </p>

            <div class="dataset-page-grid">
                <div class="dataset-card">
                    <h2 class="dataset-card-title">Fetch Dataset Automatically</h2>

                    <form action="{{ route('datasets.fetch') }}" method="POST">
                        @csrf

                        <div class="dataset-field">
                            <label for="preset" class="dataset-field-label">Preset</label>
                            <select name="preset" id="preset" class="dataset-field-input">
                                <option value="">-- Optional Preset --</option>
                                <option value="sp500">S&amp;P 500 (^GSPC)</option>
                                <option value="apple">Apple (AAPL)</option>
                                <option value="microsoft">Microsoft (MSFT)</option>
                                <option value="tesla">Tesla (TSLA)</option>
                                <option value="nvidia">NVIDIA (NVDA)</option>
                                <option value="amazon">Amazon (AMZN)</option>
                                <option value="google">Google (GOOGL)</option>
                                <option value="meta">Meta (META)</option>
                            </select>
                        </div>

                        <div class="dataset-field">
                            <label for="ticker" class="dataset-field-label">Custom Ticker</label>
                            <input type="text" name="ticker" id="ticker" class="dataset-field-input" placeholder="Example: ^GSPC or AAPL">
                        </div>

                        <div class="dataset-field">
                            <label for="start_date" class="dataset-field-label">Start Date</label>
                            <input type="date" name="start_date" id="start_date" class="dataset-field-input" required>
                        </div>

                        <div class="dataset-field">
                            <label for="end_date" class="dataset-field-label">End Date</label>
                            <input type="date" name="end_date" id="end_date" class="dataset-field-input" required>
                        </div>

                        <button type="submit" class="btn">Fetch Dataset</button>
                    </form>
                </div>

                <div class="dataset-card">
                    <h2 class="dataset-card-title">Upload Manual CSV</h2>

                    <form id="manualUploadForm" action="{{ route('datasets.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="dataset-field">
                            <label for="dataset" class="dataset-field-label">Choose File or Drag and Drop</label>

                            <label for="dataset" class="dropzone" id="dropzone">
                                <div class="dropzone-icon">
                                    <img src="{{ asset('icons/upload_dataset_white.svg') }}" alt="Upload" class="dropzone-upload-icon" style="height: 30px; width: 30px;">
                                </div>

                                <div class="dropzone-title">Drop your CSV file here</div>
                                <div class="dropzone-subtitle">or click to choose a file</div>
                                <div class="dropzone-file-name" id="fileName">No file selected</div>

                                <input
                                    type="file"
                                    name="dataset"
                                    id="dataset"
                                    class="dropzone-input"
                                    accept=".csv,.txt"
                                >
                            </label>

                            <p id="fileError" style="display:none; margin-top:10px; color:#f87171; font-size:14px;">
                                Please choose a CSV or TXT file before uploading.
                            </p>
                        </div>

                        <p class="dataset-note">
                            Required format: <strong>Date, Close, High, Low, Open, Volume</strong>
                        </p>

                        <button type="submit" class="btn">Upload CSV</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {

        const presetSelect = document.getElementById('preset');
        const tickerInput = document.getElementById('ticker');

        const presetTickers = {
            sp500: '^GSPC',
            apple: 'AAPL',
            microsoft: 'MSFT',
            tesla: 'TSLA',
            nvidia: 'NVDA',
            amazon: 'AMZN',
            google: 'GOOGL',
            meta: 'META'
        };

        function toggleTickerInput() {
            if (!presetSelect || !tickerInput) return;

            const selectedPreset = presetSelect.value;

            if (selectedPreset !== '') {
                const selectedTicker = presetTickers[selectedPreset] || '';

                tickerInput.value = selectedTicker;
                tickerInput.disabled = true;
                tickerInput.classList.add('input-disabled');
            } else {
                tickerInput.value = '';
                tickerInput.disabled = false;
                tickerInput.placeholder = 'Example: ^GSPC or AAPL';
                tickerInput.classList.remove('input-disabled');
            }
        }

        if (presetSelect && tickerInput) {
            presetSelect.addEventListener('change', toggleTickerInput);
            toggleTickerInput();
        }

        const dropzone = document.getElementById('dropzone');
        const fileInput = document.getElementById('dataset');
        const fileName = document.getElementById('fileName');

        if (dropzone && fileInput && fileName) {
            function updateFileName(file) {
                fileName.textContent = file ? file.name : 'No file selected';
            }

            fileInput.addEventListener('change', function () {
                updateFileName(fileInput.files[0]);
            });

            dropzone.addEventListener('dragover', function (e) {
                e.preventDefault();
                dropzone.classList.add('dragover');
            });

            dropzone.addEventListener('dragleave', function () {
                dropzone.classList.remove('dragover');
            });

            dropzone.addEventListener('drop', function (e) {
                e.preventDefault();
                dropzone.classList.remove('dragover');

                if (e.dataTransfer.files.length > 0) {
                    fileInput.files = e.dataTransfer.files;
                    updateFileName(e.dataTransfer.files[0]);
                }
            });
        }
    });
</script>
@endsection