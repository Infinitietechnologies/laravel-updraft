@extends(config('laravel-updraft.layout', 'laravel-updraft::layouts.app'))

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold">Update History</h2>
            <div>
                <a href="{{ route('laravel-updraft.rollback-options') }}" class="btn btn-warning me-2">
                    <i class="fas fa-undo me-1"></i> Rollback Manager
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (count($updates) > 0)
            <div class="card shadow">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Version</th>
                                <th>Name</th>
                                <th>Applied At</th>
                                <th>Status</th>
                                <th>Applied By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($updates as $update)
                                <tr>
                                    <td class="fw-medium">
                                        {{ $update->version }}
                                    </td>
                                    <td>
                                        {{ $update->name }}
                                    </td>
                                    <td>
                                        {{ $update->applied_at->format('Y-m-d H:i:s') }}
                                    </td>
                                    <td>
                                        @if ($update->successful)
                                            <span class="badge bg-success">Successful</span>
                                        @else
                                            <span class="badge bg-danger">Failed</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $update->applied_by ?? 'System' }}
                                    </td>
                                    <td>
                                        <div class="d-flex">
                                            <button type="button" class="btn btn-sm btn-outline-primary me-2"
                                                data-bs-toggle="collapse" data-bs-target="#update-{{ $update->id }}">
                                                <i class="fas fa-info-circle me-1"></i> Details
                                            </button>

                                            @if ($update->successful && $update->backup_id)
                                                <a href="{{ route('laravel-updraft.confirm-rollback', $update->backup_id) }}"
                                                    class="btn btn-sm btn-outline-warning">
                                                    <i class="fas fa-undo me-1"></i> Roll Back
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                <tr class="collapse" id="update-{{ $update->id }}">
                                    <td colspan="6" class="bg-light">
                                        <div class="p-3">
                                            @if ($update->description)
                                                <div class="mb-3">
                                                    <h5 class="fw-bold">Description:</h5>
                                                    <p class="mb-0">{{ $update->description }}</p>
                                                </div>
                                            @endif

                                            @if ($update->backup_id)
                                                <div class="mb-3">
                                                    <h5 class="fw-bold">Backup ID:</h5>
                                                    <p class="mb-0">{{ $update->backup_id }}</p>

                                                    @if ($update->successful)
                                                        <div class="mt-3">
                                                            <a href="{{ route('laravel-updraft.confirm-rollback', $update->backup_id) }}"
                                                                class="btn btn-sm btn-warning">
                                                                <i class="fas fa-undo me-1"></i> Roll Back to Before This
                                                                Update
                                                            </a>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endif

                                            @if ($update->metadata)
                                                <div>
                                                    <h5 class="fw-bold">Metadata:</h5>
                                                    <div class="bg-dark text-light p-3 rounded overflow-auto"
                                                        style="max-height: 200px">
                                                        <pre class="mb-0 small">{{ json_encode($update->metadata, JSON_PRETTY_PRINT) }}</pre>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4">
                {{ $updates->links() }}
            </div>
        @else
            <div class="card shadow">
                <div class="card-body py-5 text-center text-muted">
                    <i class="fas fa-info-circle fa-3x mb-3"></i>
                    <p class="mb-0">No updates have been applied yet.</p>
                </div>
            </div>
        @endif
    </div>
@endsection
