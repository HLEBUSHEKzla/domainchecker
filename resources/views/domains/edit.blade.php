@extends('layouts.app')

@section('content')
    <h1>Edit Domain</h1>

    <div id="error-message" class="alert alert-danger" style="display: none;"></div>
    <div id="success-message" class="alert alert-success" style="display: none;"></div>

    <form id="edit-domain-form">
        <input type="hidden" id="domain_id" value="{{ $domain->id }}">

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
            <input type="number" class="form-control" id="check_interval_minutes" name="check_interval_minutes">
        </div>

        <div class="form-group">
            <label for="expected_content_marker">Expected Content Marker (Optional)</label>
            <textarea class="form-control" id="expected_content_marker" name="expected_content_marker"></textarea>
        </div>

        <div class="form-check mb-3">
            <input type="checkbox" class="form-check-input" id="is_active" name="is_active">
            <label class="form-check-label" for="is_active">Active</label>
        </div>

        <button type="submit" class="btn btn-primary">Save Changes</button>
        <a href="{{ route('domains.index') }}" class="btn btn-secondary">Back to List</a>
    </form>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    const domainId = $('#domain_id').val();

    // Load initial data
    $.ajax({
        url: `/api/domains/${domainId}`,
        method: 'GET',
        success: function(response) {
            const data = response.data;
            $('#name').val(data.name);
            $('#domain').val(data.domain);
            $('#check_interval_minutes').val(data.check_interval_minutes);
            $('#expected_content_marker').val(data.expected_content_marker);
            $('#is_active').prop('checked', data.is_active);
        },
        error: function() {
            $('#error-message').text('Failed to load domain data.').show();
        }
    });

    // Handle form submission
    $('#edit-domain-form').on('submit', function(e) {
        e.preventDefault();

        $.ajax({
            url: `/api/domains/${domainId}`,
            method: 'PUT',
            contentType: 'application/json',
            data: JSON.stringify({
                name: $('#name').val(),
                domain: $('#domain').val(),
                check_interval_minutes: $('#check_interval_minutes').val(),
                expected_content_marker: $('#expected_content_marker').val(),
                is_active: $('#is_active').is(':checked')
            }),
            success: function(response) {
                $('#success-message').text('Domain updated successfully!').show();
                $('#error-message').hide();
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
