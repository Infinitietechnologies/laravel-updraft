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
                    <h3 class="mb-0 fs-5">{{ __('Upload Update Package') }}</h3>
                </div>

                <div class="card-body" id="updraftCardBody">
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

                    <p class="card-text text-muted mb-4">Please select the update package (.zip) file to upload.</p>

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
                                Only .zip files are accepted. Maximum file size: 50MB.
                            </div>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" value="1" id="confirm_backup" name="confirm_backup" required>
                            <label class="form-check-label" for="confirm_backup">
                                I confirm that I have backed up my application before applying this update.
                            </label>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                {{ __('Upload and Apply Update') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow mt-4">
                <div class="card-header bg-light">
                    <h3 class="mb-0 fs-5">{{ __('Update Instructions') }}</h3>
                </div>
                <div class="card-body">
                    <ol class="mb-0">
                        <li class="mb-2">Ensure you have downloaded the update package from a trusted source.</li>
                        <li class="mb-2">Always back up your application and database before applying any updates.</li>
                        <li class="mb-2">Upload the update package using the form above.</li>
                        <li class="mb-2">The system will automatically:
                            <ul class="mt-2">
                                <li>Extract the update package</li>
                                <li>Validate the package structure</li>
                                <li>Create a backup of affected files</li>
                                <li>Apply file changes</li>
                                <li>Run any included database migrations</li>
                                <li>Update configuration files</li>
                                <li>Run any post-update commands</li>
                            </ul>
                        </li>
                        <li>If the update fails, the system will attempt to restore from backup.</li>
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
