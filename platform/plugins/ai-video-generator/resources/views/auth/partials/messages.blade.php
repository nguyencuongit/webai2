@if (session()->has('status'))
    <div role="alert" class="alert alert-success">
        {{ session('status') }}
    </div>
@elseif (session()->has('auth_error_message'))
    <div role="alert" class="alert alert-danger">
        {{ session('auth_error_message') }}
    </div>
@elseif (session()->has('auth_success_message'))
    <div role="alert" class="alert alert-success">
        {{ session('auth_success_message') }}
    </div>
@elseif (session()->has('auth_warning_message'))
    <div role="alert" class="alert alert-warning">
        {{ session('auth_warning_message') }}
    </div>
@endif
