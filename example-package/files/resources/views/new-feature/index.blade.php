@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('New Feature') }}</div>

                <div class="card-body">
                    <h2>This is a new feature added by the update package</h2>
                    
                    <p>This view was added during the application update process.</p>
                    
                    <div class="alert alert-info">
                        <strong>Note:</strong> This demonstrates how new views can be added through the update package.
                    </div>
                    
                    @if(isset($features) && count($features))
                        <h3>Available Features</h3>
                        <ul class="list-group">
                            @foreach($features as $feature)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    {{ $feature->name }}
                                    @if($feature->is_active)
                                        <span class="badge bg-success rounded-pill">Active</span>
                                    @else
                                        <span class="badge bg-secondary rounded-pill">Inactive</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p>No features available.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection