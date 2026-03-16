@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Login') }}</div>

                <div class="card-body">
                    <div id="error-message" class="alert alert-danger" style="display: none;"></div>

                    <form id="login-form">
                        <div class="form-group row">
                            <label for="email" class="col-md-4 col-form-label text-md-right">{{ __('E-Mail Address') }}</label>
                            <div class="col-md-6">
                                <input id="email" type="email" class="form-control" name="email" required autocomplete="email" autofocus>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="password" class="col-md-4 col-form-label text-md-right">{{ __('Password') }}</label>
                            <div class="col-md-6">
                                <input id="password" type="password" class="form-control" name="password" required autocomplete="current-password">
                            </div>
                        </div>

                        <div class="form-group row mb-0">
                            <div class="col-md-8 offset-md-4">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Login') }}
                                </button>
                                <a class="btn btn-link" href="{{ route('register') }}">
                                    Don't have an account? Register
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    $('#login-form').on('submit', function(e) {
        e.preventDefault();

        let email = $('#email').val();
        let password = $('#password').val();

        $.ajax({
            url: '/api/login',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                email: email,
                password: password
            }),
            success: function(response) {
                // Save the token to localStorage
                localStorage.setItem('api_token', response.access_token);

                // Redirect to the WEB dashboard page
                window.location.href = "{{ route('dashboard') }}";
            },
            error: function(xhr) {
                let error = "An unknown error occurred.";
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    error = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    error = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                }
                $('#error-message').html(error).show();
            }
        });
    });
});
</script>
@endsection
