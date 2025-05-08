@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">{{ __('Laravel Updraft') }}</div>

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

                        <h5>Upload Update Package</h5>
                        <p>Please select the update package (.zip) file to upload.</p>

                        <form method="POST" action="{{ route('laravel-updraft.upload') }}" enctype="multipart/form-data">
                            @csrf

                            <div class="mb-3">
                                <label for="update_package" class="form-label">Update Package</label>
                                <input class="form-control @error('update_package') is-invalid @enderror" type="file"
                                    id="update_package" name="update_package" accept=".zip">

                                @error('update_package')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror

                                <div class="form-text">
                                    Only .zip files are accepted. Maximum file size: 50MB.
                                </div>
                            </div>

                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" value="1" id="confirm_backup"
                                    name="confirm_backup" required>
                                <label class="form-check-label" for="confirm_backup">
                                    I confirm that I have backed up my application before applying this update.
                                </label>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Upload and Apply Update') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header">{{ __('Update Instructions') }}</div>
                    <div class="card-body">
                        <ol>
                            <li>Ensure you have downloaded the update package from a trusted source.</li>
                            <li>Always back up your application and database before applying any updates.</li>
                            <li>Upload the update package using the form above.</li>
                            <li>The system will automatically:
                                <ul>
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
    </div>
@endsection
