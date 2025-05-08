@extends(config('laravel-updraft.layout', 'layouts.app'))

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Confirm Rollback</span>
                            <a href="{{ route('laravel-updraft.rollback-options') }}"
                                class="btn btn-sm btn-outline-secondary">Back to Rollback Options</a>
                        </div>
                    </div>

                    <div class="card-body">
                        @if (session('error'))
                            <div class="alert alert-danger" role="alert">
                                {{ session('error') }}
                            </div>
                        @endif

                        <div class="alert alert-warning">
                            <strong>Warning!</strong> You are about to roll back your application to a previous version.
                            This action cannot be undone.
                        </div>

                        <h5>You are about to roll back to before the following update was applied:</h5>

                        <div class="table-responsive mb-4">
                            <table class="table table-bordered">
                                <tbody>
                                    <tr>
                                        <th style="width: 150px;">Version</th>
                                        <td>{{ $update->version ?? ($backupInfo['version'] ?? 'Unknown') }}</td>
                                    </tr>
                                    <tr>
                                        <th>Name</th>
                                        <td>{{ $update->name ?? 'Unknown' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Applied At</th>
                                        <td>{{ isset($update->applied_at) ? $update->applied_at->format('Y-m-d H:i:s') : date('Y-m-d H:i:s', $backupInfo['timestamp'] ?? 0) }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Applied By</th>
                                        <td>{{ $update->applied_by ?? 'System' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Backup ID</th>
                                        <td><code>{{ $backupId }}</code></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="alert alert-danger mb-4">
                            <p><strong>Important Notes:</strong></p>
                            <ul class="mb-0">
                                <li>This will restore your files to their previous state.</li>
                                <li>Database changes cannot be automatically reverted. Make sure you have a database backup.
                                </li>
                                <li>A safety backup of your current state will be created before the rollback.</li>
                            </ul>
                        </div>

                        <form method="POST" action="{{ route('laravel-updraft.process-rollback', $backupId) }}">
                            @csrf
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-danger">
                                    Confirm Rollback
                                </button>
                                <a href="{{ route('laravel-updraft.rollback-options') }}" class="btn btn-secondary">
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
