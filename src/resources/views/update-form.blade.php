@extends(config('laravel-updraft.layout', 'layouts.app'))

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex justify-center">
            <div class="w-full max-w-2xl">
                <div class="bg-white shadow-md rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-900">{{ __('Laravel Updraft') }}</h3>
                        <a href="{{ route('laravel-updraft.history') }}" class="inline-flex items-center px-3 py-1 bg-gray-200 border border-transparent rounded-md text-xs font-medium text-gray-700 hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                            View Update History
                        </a>
                    </div>

                    <div class="p-6">
                        @if (session('success'))
                            <div class="mb-4 bg-green-50 border-l-4 border-green-500 p-4 text-green-700" role="alert">
                                <p>{{ session('success') }}</p>
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="mb-4 bg-red-50 border-l-4 border-red-500 p-4 text-red-700" role="alert">
                                <p>{{ session('error') }}</p>
                            </div>
                        @endif

                        <h5 class="text-lg font-medium text-gray-900 mb-2">Upload Update Package</h5>
                        <p class="mb-4 text-gray-600">Please select the update package (.zip) file to upload.</p>

                        <form method="POST" action="{{ route('laravel-updraft.upload') }}" enctype="multipart/form-data">
                            @csrf

                            <div class="mb-4">
                                <label for="update_package" class="block text-sm font-medium text-gray-700 mb-1">Update Package</label>
                                <input class="block w-full text-sm text-gray-900 border border-gray-300 rounded-md cursor-pointer @error('update_package') border-red-500 @enderror
                                    file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 
                                    hover:file:bg-indigo-100 focus:outline-none"
                                    type="file" id="update_package" name="update_package" accept=".zip">

                                @error('update_package')
                                    <p class="mt-1 text-sm text-red-600">
                                        {{ $message }}
                                    </p>
                                @enderror

                                <p class="mt-1 text-sm text-gray-500">
                                    Only .zip files are accepted. Maximum file size: 50MB.
                                </p>
                            </div>

                            <div class="mb-4">
                                <div class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                                            type="checkbox" value="1" id="confirm_backup" name="confirm_backup" required>
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label class="font-medium text-gray-700" for="confirm_backup">
                                            I confirm that I have backed up my application before applying this update.
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-medium text-sm text-white 
                                    hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    {{ __('Upload and Apply Update') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="mt-6 bg-white shadow-md rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-lg font-medium text-gray-900">{{ __('Update Instructions') }}</h3>
                    </div>
                    <div class="p-6">
                        <ol class="list-decimal pl-5 space-y-2">
                            <li class="text-gray-700">Ensure you have downloaded the update package from a trusted source.</li>
                            <li class="text-gray-700">Always back up your application and database before applying any updates.</li>
                            <li class="text-gray-700">Upload the update package using the form above.</li>
                            <li class="text-gray-700">The system will automatically:
                                <ul class="list-disc pl-5 mt-2 space-y-1">
                                    <li class="text-gray-600">Extract the update package</li>
                                    <li class="text-gray-600">Validate the package structure</li>
                                    <li class="text-gray-600">Create a backup of affected files</li>
                                    <li class="text-gray-600">Apply file changes</li>
                                    <li class="text-gray-600">Run any included database migrations</li>
                                    <li class="text-gray-600">Update configuration files</li>
                                    <li class="text-gray-600">Run any post-update commands</li>
                                </ul>
                            </li>
                            <li class="text-gray-700">If the update fails, the system will attempt to restore from backup.</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
