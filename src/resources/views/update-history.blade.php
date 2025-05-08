@extends(config('laravel-updraft.layout', 'layouts.app'))

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Update History</h2>
            <div>
                <a href="{{ route('laravel-updraft.rollback-options') }}"
                    class="inline-flex items-center px-4 py-2 mr-2 bg-yellow-500 border border-transparent rounded-md font-medium text-sm text-white hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                    Rollback Manager
                </a>
                <a href="{{ route('laravel-updraft.index') }}"
                    class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-medium text-sm text-gray-700 hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                    Back to Updates
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="rounded-md bg-green-50 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <!-- Success icon -->
                        <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800">
                            {{ session('success') }}
                        </p>
                    </div>
                </div>
            </div>
        @endif

        @if (count($updates) > 0)
            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Version
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Name
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Applied At
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Applied By
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($updates as $update)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $update->version }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $update->name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $update->applied_at->format('Y-m-d H:i:s') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    @if ($update->successful)
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Successful
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            Failed
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $update->applied_by ?? 'System' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <div class="flex">
                                        <button type="button" class="text-indigo-600 hover:text-indigo-900 mr-3"
                                            onclick="toggleDetails('update-{{ $update->id }}')">
                                            Show Details
                                        </button>

                                        @if ($update->successful && $update->backup_id)
                                            <a href="{{ route('laravel-updraft.confirm-rollback', $update->backup_id) }}"
                                                class="text-yellow-600 hover:text-yellow-900">
                                                Roll Back
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            <tr id="update-{{ $update->id }}" class="hidden">
                                <td colspan="6" class="px-6 py-4 bg-gray-50">
                                    <div class="text-sm">
                                        @if ($update->description)
                                            <div class="mb-2">
                                                <h4 class="font-medium">Description:</h4>
                                                <p class="text-gray-600">{{ $update->description }}</p>
                                            </div>
                                        @endif

                                        @if ($update->backup_id)
                                            <div class="mb-2">
                                                <h4 class="font-medium">Backup ID:</h4>
                                                <p class="text-gray-600">{{ $update->backup_id }}</p>

                                                @if ($update->successful)
                                                    <div class="mt-2">
                                                        <a href="{{ route('laravel-updraft.confirm-rollback', $update->backup_id) }}"
                                                            class="inline-flex items-center px-3 py-1 border border-transparent text-sm leading-4 font-medium rounded-md text-yellow-700 bg-yellow-100 hover:bg-yellow-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                                                            Roll Back to Before This Update
                                                        </a>
                                                    </div>
                                                @endif
                                            </div>
                                        @endif

                                        @if ($update->metadata)
                                            <div class="mb-2">
                                                <h4 class="font-medium">Metadata:</h4>
                                                <div class="mt-1 bg-gray-100 p-2 rounded overflow-auto max-h-40">
                                                    <pre class="text-xs text-gray-600">{{ json_encode($update->metadata, JSON_PRETTY_PRINT) }}</pre>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $updates->links() }}
            </div>
        @else
            <div class="bg-white shadow overflow-hidden sm:rounded-lg p-6">
                <p class="text-gray-500">No updates have been applied yet.</p>
            </div>
        @endif
    </div>

    <script>
        function toggleDetails(id) {
            const detailRow = document.getElementById(id);
            detailRow.classList.toggle('hidden');
        }
    </script>
@endsection
