@extends(config('laravel-updraft.layout', 'laravel-updraft::layouts.app'))

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Confirm Rollback</h5>
                    <a href="{{ route('laravel-updraft.rollback-options') }}"
                        class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to Rollback Options
                    </a>
                </div>

                <div class="card-body">
                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Warning!</strong> You are about to roll back your application to a previous version.
                        This action cannot be undone.
                    </div>

                    <h5 class="mb-3">You are about to roll back to before the following update was applied:</h5>

                    <div class="table-responsive mb-4">
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <th class="bg-light" style="width: 150px;">Version</th>
                                    <td>{{ $update->version ?? ($backupInfo['version'] ?? 'Unknown') }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Name</th>
                                    <td>{{ $update->name ?? 'Unknown' }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Applied At</th>
                                    <td>{{ isset($update->applied_at) ? $update->applied_at->format('Y-m-d H:i:s') : date('Y-m-d H:i:s', $backupInfo['timestamp'] ?? 0) }}
                                    </td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Applied By</th>
                                    <td>{{ $update->applied_by ?? 'System' }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Backup ID</th>
                                    <td><code>{{ $backupId }}</code></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="alert alert-danger mb-4">
                        <p class="fw-bold mb-2"><i class="fas fa-exclamation-circle me-2"></i>Important Notes:</p>
                        <ul class="mb-0">
                            <li>This will restore your files to their previous state.</li>
                            <li>Database changes cannot be automatically reverted. Make sure you have a database backup.</li>
                            <li>A safety backup of your current state will be created before the rollback.</li>
                        </ul>
                    </div>

                    <form id="rollbackForm" method="POST" action="{{ route('laravel-updraft.process-rollback', $backupId) }}" 
                          data-redirect-url="{{ route('laravel-updraft.history') }}?success=1">
                        @csrf
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-danger" id="confirmBtn">
                                <i class="fas fa-undo me-1"></i> Confirm Rollback
                            </button>
                            <a href="{{ route('laravel-updraft.rollback-options') }}" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <!-- Rollback Handler Module -->
    <script src="{{ asset('vendor/laravel-updraft/assets/js/modules/rollback-handler.js') }}"></script>
@endsection
