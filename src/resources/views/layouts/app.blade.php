<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }} - {{ __('updraft.app_name') }}</title>

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

        .language-selector .dropdown-item.active {
            background-color: #f8f9fa;
            color: #212529;
            font-weight: bold;
        }
    </style>

    @yield('styles')
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="{{ route('laravel-updraft.index') }}">
                <i class="fas fa-cloud-download-alt text-primary me-2"></i>
                <span>{{ __('updraft.app_name') }}</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
                aria-label="{{ __('updraft.toggle_navigation') }}">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('laravel-updraft.index') ? 'active' : '' }}"
                            href="{{ route('laravel-updraft.index') }}">
                            <i class="fas fa-upload me-1"></i> {{ __('updraft.upload_update') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('laravel-updraft.history') ? 'active' : '' }}"
                            href="{{ route('laravel-updraft.history') }}">
                            <i class="fas fa-history me-1"></i> {{ __('updraft.update_history') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('laravel-updraft.rollback-options') ? 'active' : '' }}"
                            href="{{ route('laravel-updraft.rollback-options') }}">
                            <i class="fas fa-undo me-1"></i> {{ __('updraft.rollback') }}
                        </a>
                    </li>
                </ul>
                
                <!-- Language Selector -->
                <div class="language-selector dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="languageDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-globe me-1"></i> 
                        @if(app()->getLocale() == 'en')
                            English
                        @elseif(app()->getLocale() == 'es')
                            Español
                        @else
                            {{ app()->getLocale() }}
                        @endif
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="languageDropdown">
                        <li>
                            <a class="dropdown-item {{ app()->getLocale() == 'en' ? 'active' : '' }}" href="{{ route('laravel-updraft.set-locale', ['locale' => 'en']) }}">English</a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ app()->getLocale() == 'es' ? 'active' : '' }}" href="{{ route('laravel-updraft.set-locale', ['locale' => 'es']) }}">Español</a>
                        </li>
                    </ul>
                </div>
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
                    <p class="mb-0">&copy; {{ date('Y') }} {{ __('updraft.app_name') }}</p>
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
