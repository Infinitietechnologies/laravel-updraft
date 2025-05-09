@extends(config('laravel-updraft.layout', 'laravel-updraft::layouts.app'))

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-warning d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        {{ __('updraft.vendor_updates') }}
                    </h5>
                    <a href="{{ route('laravel-updraft.index') }}"
                        class="btn btn-sm btn-outline-dark">
                        <i class="fas fa-arrow-left me-1"></i> {{ __('updraft.cancel') }}
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
                        <strong>{{ __('updraft.warning') }}!</strong> {{ __('updraft.vendor_update_warning') }}
                    </div>

                    <h5 class="mb-3">{{ __('updraft.vendor_update_description') }}</h5>

                    <div class="table-responsive mb-4">
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <th class="bg-light" style="width: 150px;">{{ __('updraft.version') }}</th>
                                    <td>{{ $version ?? __('updraft.unknown') }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light">{{ __('updraft.name') }}</th>
                                    <td>{{ $name ?? __('updraft.unknown') }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light">{{ __('updraft.vendor_updates') }}</th>
                                    <td>{{ $vendorFileCount ?? 0 }} files</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="alert alert-danger mb-4">
                        <p class="fw-bold mb-2"><i class="fas fa-exclamation-circle me-2"></i>{{ __('updraft.important_notes') }}:</p>
                        <ul class="mb-0">
                            <li>{{ __('updraft.vendor_update_warning') }}</li>
                            <li>Changes to vendor files may be overwritten when running Composer.</li>
                            <li>A backup of affected vendor files will be created before modification.</li>
                            <li>You can find these backups in your storage/app/vendor-backups directory.</li>
                        </ul>
                    </div>

                    @if(!empty($vendorFiles))
                    <div class="mb-4">
                        <h6>Examples of vendor files that will be modified:</h6>
                        <div class="bg-light p-3 rounded">
                            <ul class="mb-0">
                                @foreach($vendorFiles as $file)
                                    <li><code>{{ $file }}</code></li>
                                @endforeach
                            </ul>
                            @if($vendorFileCount > count($vendorFiles))
                                <p class="mt-2 mb-0 text-muted">
                                    <small>...and {{ $vendorFileCount - count($vendorFiles) }} more files</small>
                                </p>
                            @endif
                        </div>
                    </div>
                    @endif

                    <form id="confirmVendorUpdateForm" method="POST" action="{{ route('laravel-updraft.process-vendor-update') }}">
                        @csrf
                        <input type="hidden" name="update_file" value="{{ $updateFile ?? '' }}">
                        <input type="hidden" name="extract_path" value="{{ $extractPath ?? '' }}">
                        <input type="hidden" name="force_vendor_updates" value="1">
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-warning" id="confirmBtn">
                                <i class="fas fa-check me-1"></i> {{ __('updraft.confirm') }} {{ __('updraft.vendor_updates') }}
                            </button>
                            <a href="{{ route('laravel-updraft.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i> {{ __('updraft.cancel') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection