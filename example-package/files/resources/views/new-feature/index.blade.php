@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-center">
        <div class="w-full max-w-2xl">
            <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-lg font-medium text-gray-900">{{ __('New Feature') }}</h3>
                </div>

                <div class="p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">This is a new feature added by the update package</h2>
                    
                    <p class="mb-4 text-gray-600">This view was added during the application update process.</p>
                    
                    <div class="p-4 mb-4 bg-blue-50 border-l-4 border-blue-500 text-blue-700">
                        <p class="font-medium">Note:</p>
                        <p>This demonstrates how new views can be added through the update package.</p>
                    </div>
                    
                    @if(isset($features) && count($features))
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Available Features</h3>
                        <ul class="divide-y divide-gray-200 border border-gray-200 rounded-md">
                            @foreach($features as $feature)
                                <li class="flex justify-between items-center py-3 px-4">
                                    <span class="text-gray-700">{{ $feature->name }}</span>
                                    @if($feature->is_active)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            Inactive
                                        </span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-gray-600">No features available.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
