@extends('layouts.app')

@section('content')
<div class="module-page">
    <div class="module-wrapper">
        <div class="module-card">
            <h1 class="module-title">Dataset Preview</h1>
            <p class="module-subtitle">
                Review the uploaded or fetched dataset before training the model.
            </p>

            <div class="dataset-preview-summary">
                <p>
                    <strong>Ticker:</strong>
                    <span class="dataset-preview-ticker">
                        {{ $dataset->ticker ?? pathinfo($dataset->file_name, PATHINFO_FILENAME) }}
                    </span>
                </p>

                <p><strong>File Name:</strong> {{ $dataset->file_name }}</p>
                <p><strong>Status:</strong> {{ ucfirst($dataset->status) }}</p>
                <p><strong>Total Rows Displayed:</strong> {{ count($rows) > 0 ? count($rows) - 1 : 0 }}</p>
            </div>

            <h2 class="dataset-preview-heading">Preview Rows</h2>

            @if(!empty($rows))
                <div class="module-table-wrapper">
                    <table>
                        @foreach($rows as $rowIndex => $row)
                            @if($rowIndex === 0)
                                <thead>
                                    <tr>
                                        @foreach($row as $cell)
                                            <th>{{ $cell }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                            @else
                                <tr>
                                    @foreach($row as $cell)
                                        <td>{{ $cell }}</td>
                                    @endforeach
                                </tr>
                            @endif
                        @endforeach
                                </tbody>
                    </table>
                </div>
            @else
                <p>No preview data available.</p>
            @endif

            <div class="module-actions">
                <form action="{{ route('models.train', $dataset->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn">Train Model</button>
                </form>

                @if($dataset->status === 'fetched')
                    <a href="{{ route('datasets.download', $dataset->id) }}" class="btn-secondary">
                        Download Dataset
                    </a>
                @endif

                <a href="{{ route('datasets.create') }}" class="btn-secondary">
                    Back
                </a>
            </div>
        </div>
    </div>
</div>
@endsection