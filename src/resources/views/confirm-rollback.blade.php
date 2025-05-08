@extends(config('laravel-updraft.layout', 'laravel-updraft::layouts.app'))

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ __('updraft.confirm_rollback') }}</h5>
                    <a href="{{ route('laravel-updraft.rollback-options') }}"
                        class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> {{ __('updraft.back_to_rollback') }}
                    </a>
                </div>

                <div class="card-body">
                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('updraft.close') }}"></button>
                        </div>
                    @endif

                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>{{ __('updraft.warning') }}!</strong> {{ __('updraft.rollback_warning_detailed') }}
                    </div>

                    <h5 class="mb-3">{{ __('updraft.rollback_confirmation_message') }}</h5>

                    <div class="table-responsive mb-4">
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <th class="bg-light" style="width: 150px;">{{ __('updraft.version') }}</th>
                                    <td>{{ $update->version ?? ($backupInfo['version'] ?? __('updraft.unknown')) }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light">{{ __('updraft.name') }}</th>
                                    <td>{{ $update->name ?? __('updraft.unknown') }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light">{{ __('updraft.applied_at') }}</th>
                                    <td>{{ isset($update->applied_at) ? $update->applied_at->format('Y-m-d H:i:s') : date('Y-m-d H:i:s', $backupInfo['timestamp'] ?? 0) }}
                                    </td>
                                </tr>
                                <tr>
                                    <th class="bg-light">{{ __('updraft.applied_by') }}</th>
                                    <td>{{ $update->applied_by ?? __('updraft.system') }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light">{{ __('updraft.backup_id_label') }}</th>
                                    <td><code>{{ $backupId }}</code></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="alert alert-danger mb-4">
                        <p class="fw-bold mb-2"><i class="fas fa-exclamation-circle me-2"></i>{{ __('updraft.important_notes') }}:</p>
                        <ul class="mb-0">
                            <li>{{ __('updraft.restore_files') }}</li>
                            <li>{{ __('updraft.db_warning') }}</li>
                            <li>{{ __('updraft.safety_backup') }}</li>
                        </ul>
                    </div>

                    <form id="rollbackForm" method="POST" action="{{ route('laravel-updraft.process-rollback', $backupId) }}" 
                          data-redirect-url="{{ route('laravel-updraft.history') }}?success=1">
                        @csrf
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-danger" id="confirmBtn">
                                <i class="fas fa-undo me-1"></i> {{ __('updraft.confirm_rollback_button') }}
                            </button>
                            <a href="{{ route('laravel-updraft.rollback-options') }}" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i> {{ __('updraft.cancel') }}
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
    <script src="{{ asset($assetPath . '/js/modules/rollback-handler.js') }}"></script>
@endsection
