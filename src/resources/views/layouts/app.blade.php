<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }} - Updraft</title>

    <!-- Bootstrap 5.3 CSS -->
    <link href="{{ asset('vendor/laravel-updraft/assets/css/bootstrap.min.css') }}" rel="stylesheet">

    <!-- FontAwesome Icons -->
    <link href="{{ asset('vendor/laravel-updraft/assets/css/fontawesome.min.css') }}" rel="stylesheet">

    <style>
        body {
            background-color: #f8f9fa;
            color: #212529;
        }

        .navbar-brand img {
            height: 30px;
        }

        .updraft-container {
            padding-top: 2rem;
            padding-bottom: 2rem;
        }

        .updraft-footer {
            margin-top: 3rem;
            padding: 1rem 0;
            background-color: #f1f1f1;
            border-top: 1px solid #dee2e6;
        }

        .filepond--credits {
            display: none;
        }
    </style>

    @yield('styles')
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="{{ route('laravel-updraft.index') }}">
                <i class="fas fa-cloud-download-alt text-primary me-2"></i>
                <span>Laravel Updraft</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
                aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('laravel-updraft.index') ? 'active' : '' }}"
                            href="{{ route('laravel-updraft.index') }}">
                            <i class="fas fa-upload me-1"></i> Upload Update
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('laravel-updraft.history') ? 'active' : '' }}"
                            href="{{ route('laravel-updraft.history') }}">
                            <i class="fas fa-history me-1"></i> Update History
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('laravel-updraft.rollback-options') ? 'active' : '' }}"
                            href="{{ route('laravel-updraft.rollback-options') }}">
                            <i class="fas fa-undo me-1"></i> Rollback
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="updraft-container">
        <div class="container">
            @yield('content')
        </div>
    </main>

    <footer class="updraft-footer">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">&copy; {{ date('Y') }} Laravel Updraft</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">
                        <small class="text-muted">v{{ config('app.version', '1.0.0') }}</small>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Hidden elements to store route info -->
    <div class="d-none">
        <div data-route="laravel-updraft.process-file" data-url="{{ route('laravel-updraft.process-file') }}"></div>
        <div data-route="laravel-updraft.revert-file" data-url="{{ route('laravel-updraft.revert-file') }}"></div>
        <div data-route="laravel-updraft.upload" data-url="{{ route('laravel-updraft.upload') }}"></div>
        <div data-route="laravel-updraft.history" data-url="{{ route('laravel-updraft.history') }}"></div>
    </div>

    <!-- jQuery -->
    <script src="{{ asset('vendor/laravel-updraft/assets/js/jquery.min.js') }}"></script>

    <!-- Bootstrap 5.3 JS Bundle with Popper -->
    <script src="{{ asset('vendor/laravel-updraft/assets/js/bootstrap.bundle.min.js') }}"></script>

    <!-- Main Laravel Updraft JS -->
    <script src="{{ asset('vendor/laravel-updraft/assets/js/laravel-updraft.js') }}"></script>

    @yield('scripts')
</body>

</html>
