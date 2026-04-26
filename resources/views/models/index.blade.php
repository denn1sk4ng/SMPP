@extends('layouts.app')

@section('content')
<div class="module-page">
    <div class="module-wrapper">
        <div class="module-card">
            <h1 class="module-title">Manage Trained Models</h1>
            <p class="module-subtitle">
                Select a trained model to generate predictions or delete a model.
            </p>

            @if($models->count())
                <div class="module-table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Model Name</th>
                                <th>Model Type</th>
                                <th>LSTM Train RMSE</th>
                                <th>LSTM Test RMSE</th>
                                <th>LR RMSE</th>
                                <th>MA RMSE</th>
                                <th>Best Model</th>
                                <th>Active</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($models as $model)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $model->model_name }}</td>
                                    <td>{{ $model->model_type }}</td>
                                    <td>{{ $model->lstm_train_rmse }}</td>
                                    <td>{{ $model->lstm_test_rmse }}</td>
                                    <td>{{ $model->lr_rmse }}</td>
                                    <td>{{ $model->ma_rmse }}</td>
                                    <td>{{ $model->best_model }}</td>
                                    <td>Active</td>
                                    <td>
                                        <div class="module-actions action-menu-wrapper">
                                            <a href="{{ route('predictions.create', $model->id) }}" class="btn">
                                                Predict
                                            </a>

                                            <form action="{{ route('models.destroy', $model->id) }}" method="POST" class="delete-model-form">
                                                @csrf
                                                @method('DELETE')

                                                <button type="button" class="dropdown-toggle-btn delete-menu-trigger">
                                                    ⋮
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p>No trained models available yet.</p>
            @endif
        </div>
    </div>
</div>

<div id="floatingModelDeleteMenu" class="floating-delete-menu" style="display: none;">
    <button type="button" id="floatingModelDeleteBtn" class="floating-delete-option">
        Delete
    </button>
</div>

<div id="deleteModal" class="custom-modal-overlay" style="display: none;">
    <div class="custom-modal">
        <h2>Delete Trained Model?</h2>

        <p>
            Are you sure you want to delete this trained model?
            This action cannot be undone.
        </p>

        <div class="custom-modal-actions">
            <button type="button" id="cancelDeleteBtn" class="btn-secondary">
                Cancel
            </button>

            <button type="button" id="confirmDeleteBtn" class="btn-danger">
                Delete
            </button>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const menuTriggers = document.querySelectorAll('.delete-menu-trigger');
        const floatingMenu = document.getElementById('floatingModelDeleteMenu');
        const floatingDeleteBtn = document.getElementById('floatingModelDeleteBtn');

        const modal = document.getElementById('deleteModal');
        const cancelBtn = document.getElementById('cancelDeleteBtn');
        const confirmBtn = document.getElementById('confirmDeleteBtn');

        let selectedForm = null;

        menuTriggers.forEach(function (button) {
            button.addEventListener('click', function (e) {
                e.stopPropagation();

                selectedForm = this.closest('.delete-model-form');

                const rect = this.getBoundingClientRect();

                floatingMenu.style.display = 'block';
                floatingMenu.style.top = (rect.bottom + 8) + 'px';
                floatingMenu.style.left = (rect.left - 70) + 'px';
            });
        });

        floatingDeleteBtn.addEventListener('click', function (e) {
            e.stopPropagation();

            floatingMenu.style.display = 'none';

            if (selectedForm) {
                modal.style.display = 'flex';
            }
        });

        cancelBtn.addEventListener('click', function () {
            selectedForm = null;
            modal.style.display = 'none';
        });

        confirmBtn.addEventListener('click', function () {
            if (selectedForm) {
                confirmBtn.disabled = true;
                confirmBtn.textContent = 'Deleting...';
                selectedForm.submit();
            }
        });

        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                selectedForm = null;
                modal.style.display = 'none';
            }
        });

        document.addEventListener('click', function () {
            floatingMenu.style.display = 'none';
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                floatingMenu.style.display = 'none';
                selectedForm = null;
                modal.style.display = 'none';
            }
        });

        window.addEventListener('scroll', function () {
            floatingMenu.style.display = 'none';
        }, true);
    });
</script>
@endsection