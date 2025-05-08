@extends(config('laravel-updraft.layout', 'laravel-updraft::layouts.app'))

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Rollback Options</h5>
                </div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <h5 class="card-title">Available Versions for Rollback</h5>
                    <p class="card-text mb-4">Select a version to roll back to. This will restore your application to the state it was in
                        before the selected update was applied.</p>

                    @if ($updates->isEmpty())
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            No updates available for rollback. Updates must be successfully applied and have a backup
                            available.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Version</th>
                                        <th>Name</th>
                                        <th>Applied At</th>
                                        <th>Applied By</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($updates as $update)
                                        <tr>
                                            <td class="fw-medium">{{ $update->version }}</td>
                                            <td>{{ $update->name }}</td>
                                            <td>{{ $update->applied_at->format('Y-m-d H:i:s') }}</td>
                                            <td>{{ $update->applied_by ?? 'System' }}</td>
                                            <td>
                                                <a href="{{ route('laravel-updraft.confirm-rollback', $update->backup_id) }}"
                                                    class="btn btn-sm btn-warning">
                                                    <i class="fas fa-undo me-1"></i> Roll Back
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-center mt-3">
                            {{ $updates->links() }}
                        </div>
                    @endif

                    <div class="mt-4">
                        <div class="alert alert-warning">
                            <strong><i class="fas fa-exclamation-triangle me-2"></i>Warning:</strong> Rolling back will revert files to their previous state, but
                            database changes cannot be automatically reverted.
                            Make sure you have a database backup before proceeding.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
