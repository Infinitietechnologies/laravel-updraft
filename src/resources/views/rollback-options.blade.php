@extends(config('laravel-updraft.layout', 'laravel-updraft::layouts.app'))

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ __('updraft.rollback_options') }}</h5>
                </div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('updraft.close') }}"></button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('updraft.close') }}"></button>
                        </div>
                    @endif

                    <h5 class="card-title">{{ __('updraft.available_versions') }}</h5>
                    <p class="card-text mb-4">{{ __('updraft.rollback_description') }}</p>

                    @if ($updates->isEmpty())
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            {{ __('updraft.no_updates_for_rollback') }}
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('updraft.version') }}</th>
                                        <th>{{ __('updraft.name') }}</th>
                                        <th>{{ __('updraft.applied_at') }}</th>
                                        <th>{{ __('updraft.applied_by') }}</th>
                                        <th>{{ __('updraft.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($updates as $update)
                                        <tr>
                                            <td class="fw-medium">{{ $update->version }}</td>
                                            <td>{{ $update->name }}</td>
                                            <td>{{ $update->applied_at->format('Y-m-d H:i:s') }}</td>
                                            <td>{{ $update->applied_by ?? __('updraft.system') }}</td>
                                            <td>
                                                <a href="{{ route('laravel-updraft.confirm-rollback', $update->backup_id) }}"
                                                    class="btn btn-sm btn-warning">
                                                    <i class="fas fa-undo me-1"></i> {{ __('updraft.roll_back') }}
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
                            <strong><i class="fas fa-exclamation-triangle me-2"></i>{{ __('updraft.warning') }}:</strong> {{ __('updraft.rollback_warning') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
