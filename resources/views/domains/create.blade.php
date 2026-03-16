@extends('layouts.app')

@section('content')
    <h1>Add Domain</h1>

    <div id="error-message" class="alert alert-danger" style="display: none;"></div>
    <div id="success-message" class="alert alert-success" style="display: none;"></div>

    <form id="create-domain-form">
        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>

        <div class="form-group">
            <label for="domain">Domain</label>
            <input type="text" class="form-control" id="domain" name="domain" placeholder="example.com" required>
        </div>

        <div class="form-group">
            <label for="check_interval_minutes">Check Interval (minutes)</label>
            <input type="number" class="form-control" id="check_interval_minutes" name="check_interval_minutes" value="5">
        </div>

        <div class="form-group">
            <label for="expected_content_marker">Expected Content Marker (Optional)</label>
            <textarea class="form-control" id="expected_content_marker" name="expected_content_marker"></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Save</button>
        <a href="{{ route('domains.index') }}" class="btn btn-secondary">Back to List</a>
    </form>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    $('#create-domain-form').on('submit', function(e) {
        e.preventDefault();

        $.ajax({
            url: '/api/domains',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                name: $('#name').val(),
                domain: $('#domain').val(),
                check_interval_minutes: $('#check_interval_minutes').val(),
                expected_content_marker: $('#expected_content_marker').val()
            }),
            success: function(response) {
                $('#success-message').text('Domain created successfully! Redirecting...').show();
                $('#error-message').hide();
                setTimeout(function() {
                    window.location.href = "{{ route('domains.index') }}";
                }, 2000);
            },
            error: function(xhr) {
                let error = "An unknown error occurred.";
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    error = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                }
                $('#error-message').html(error).show();
                $('#success-message').hide();
            }
        });
    });
});
</script>
@endsection
