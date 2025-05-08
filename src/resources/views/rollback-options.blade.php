@extends(config('laravel-updraft.layout', 'layouts.app'))

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Laravel Updraft - Rollback Options</span>
                            <a href="{{ route('laravel-updraft.index') }}" class="btn btn-sm btn-outline-secondary">Back to
                                Updates</a>
                        </div>
                    </div>

                    <div class="card-body">
                        @if (session('success'))
                            <div class="alert alert-success" role="alert">
                                {{ session('success') }}
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger" role="alert">
                                {{ session('error') }}
                            </div>
                        @endif

                        <h5>Available Versions for Rollback</h5>
                        <p>Select a version to roll back to. This will restore your application to the state it was in
                            before the selected update was applied.</p>

                        @if ($updates->isEmpty())
                            <div class="alert alert-info">
                                No updates available for rollback. Updates must be successfully applied and have a backup
                                available.
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
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
                                                <td>{{ $update->version }}</td>
                                                <td>{{ $update->name }}</td>
                                                <td>{{ $update->applied_at->format('Y-m-d H:i:s') }}</td>
                                                <td>{{ $update->applied_by ?? 'System' }}</td>
                                                <td>
                                                    <a href="{{ route('laravel-updraft.confirm-rollback', $update->backup_id) }}"
                                                        class="btn btn-sm btn-warning">
                                                        Roll Back
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
                                <strong>Warning:</strong> Rolling back will revert files to their previous state, but
                                database changes cannot be automatically reverted.
                                Make sure you have a database backup before proceeding.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
