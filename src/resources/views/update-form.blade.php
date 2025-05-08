@extends(config('laravel-updraft.layout', 'laravel-updraft::layouts.app'))

@section('styles')
    <link href="{{ asset('vendor/laravel-updraft/assets/plugins/filepond/filepond.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('vendor/laravel-updraft/assets/plugins/filepond/plugins/file-validate-type/filepond-plugin-file-validate-type.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('vendor/laravel-updraft/assets/plugins/filepond/plugins/file-poster/filepond-plugin-file-poster.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('vendor/laravel-updraft/assets/plugins/filepond/plugins/image-preview/filepond-plugin-image-preview.min.css') }}" rel="stylesheet" />
@endsection

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header d-flex justify-content-between align-items-center bg-light">
                    <h3 class="mb-0 fs-5">{{ __('updraft.upload_update_package') }}</h3>
                </div>

                <div class="card-body" id="updraftCardBody">
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

                    <p class="card-text text-muted mb-4">{{ __('updraft.select_update_package') }}</p>

                    <form id="uploadForm" method="POST" action="{{ route('laravel-updraft.upload') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-4">
                            <input type="file" 
                                id="update_package" 
                                name="update_package" 
                                class="filepond"
                                data-max-file-size="50MB"
                                data-max-files="1" />
                            
                            <div id="fileHelp" class="form-text">
                                {{ __('updraft.file_requirements') }}
                            </div>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" value="1" id="confirm_backup" name="confirm_backup" required>
                            <label class="form-check-label" for="confirm_backup">
                                {{ __('updraft.backup_confirmation') }}
                            </label>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                {{ __('updraft.upload_and_apply') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow mt-4">
                <div class="card-header bg-light">
                    <h3 class="mb-0 fs-5">{{ __('updraft.update_instructions') }}</h3>
                </div>
                <div class="card-body">
                    <ol class="mb-0">
                        <li class="mb-2">{{ __('updraft.instructions.trusted_source') }}</li>
                        <li class="mb-2">{{ __('updraft.instructions.backup') }}</li>
                        <li class="mb-2">{{ __('updraft.instructions.upload') }}</li>
                        <li class="mb-2">{{ __('updraft.instructions.automatic_process') }}
                            <ul class="mt-2">
                                <li>{{ __('updraft.instructions.extract') }}</li>
                                <li>{{ __('updraft.instructions.validate') }}</li>
                                <li>{{ __('updraft.instructions.backup_files') }}</li>
                                <li>{{ __('updraft.instructions.apply_changes') }}</li>
                                <li>{{ __('updraft.instructions.run_migrations') }}</li>
                                <li>{{ __('updraft.instructions.update_config') }}</li>
                                <li>{{ __('updraft.instructions.run_commands') }}</li>
                            </ul>
                        </li>
                        <li>{{ __('updraft.instructions.restore') }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <!-- FilePond plugins -->
    <script src="{{ asset('vendor/laravel-updraft/assets/plugins/filepond/plugins/file-validate-type/filepond-plugin-file-validate-type.min.js') }}"></script>
    <script src="{{ asset('vendor/laravel-updraft/assets/plugins/filepond/plugins/file-validate-size/filepond-plugin-file-validate-size.min.js') }}"></script>
    <script src="{{ asset('vendor/laravel-updraft/assets/plugins/filepond/plugins/file-poster/filepond-plugin-file-poster.min.js') }}"></script>
    <script src="{{ asset('vendor/laravel-updraft/assets/plugins/filepond/plugins/image-preview/filepond-plugin-image-preview.min.js') }}"></script>
    <script src="{{ asset('vendor/laravel-updraft/assets/plugins/filepond/filepond.min.js') }}"></script>
    
    <!-- FilePond Uploader Module -->
    <script src="{{ asset('vendor/laravel-updraft/assets/js/modules/filepond-uploader.js') }}"></script>
@endsection
