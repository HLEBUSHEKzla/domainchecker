@extends('layouts.app')

@section('content')
    <h1>History for <span id="domain-name">...</span></h1>
    <input type="hidden" id="domain_id" value="{{ $domain->id }}">

    <table class="table">
        <thead>
            <tr>
                <th>Status</th>
                <th>Checked At</th>
                <th>HTTP Code</th>
                <th>Response Time (ms)</th>
                <th>Error Summary</th>
            </tr>
        </thead>
        <tbody id="history-table-body">
            <tr><td colspan="5" class="text-center">Loading...</td></tr>
        </tbody>
    </table>
    <nav>
        <ul class="pagination" id="history-pagination">
            <!-- Pagination links will be inserted here -->
        </ul>
    </nav>

    <!-- Error Modal -->
    <div class="modal fade" id="errorModal" tabindex="-1" role="dialog" aria-labelledby="errorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="errorModalLabel">Error Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="errorModalBody">
                    <!-- Errors will be injected here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    const domainId = $('#domain_id').val();

    function getStatusBadgeClass(status) {
        switch (status) {
            case 'healthy': return 'badge-success';
            case 'degraded': return 'badge-warning';
            case 'unhealthy': return 'badge-danger';
            default: return 'badge-secondary';
        }
    }

    function getErrorMessagesArray(check) {
        if (check.error_message) {
            return check.error_message.split(' | ');
        }

        let errors = [];
        const meta = check.metadata;
        if (meta?.dns?.dns_error_message) errors.push('DNS: ' + meta.dns.dns_error_message);
        if (meta?.ssl?.ssl_error_message) errors.push('SSL: ' + meta.ssl.ssl_error_message);
        if (meta?.http?.network_error_message) errors.push('HTTP: ' + meta.http.network_error_message);
        if (meta?.redirect?.error_message) errors.push('Redirect: ' + meta.redirect.error_message);
        if (meta?.content?.content_check_passed === false) errors.push('Content: ' + meta.content.content_check_details);

        return errors;
    }

    function loadHistory(url = `/api/domains/${domainId}/history`) {
        $('#history-table-body').html('<tr><td colspan="5" class="text-center">Loading...</td></tr>');

        $.ajax({
            url: url,
            method: 'GET',
            success: function(response) {
                let historyHtml = '';
                if (response.data.length > 0) {
                    $.each(response.data, function(index, check) {
                        let statusBadgeClass = getStatusBadgeClass(check.status);
                        let errorMessages = getErrorMessagesArray(check);

                        let errorCellHtml = 'No errors';
                        if (errorMessages.length > 0) {
                            // Store errors in a data attribute, properly escaped
                            const errorsJson = JSON.stringify(errorMessages);
                            errorCellHtml = `<button class="btn btn-sm btn-danger see-errors-btn" data-errors='${errorsJson}'>See errors</button>`;
                        }

                        historyHtml += `
                            <tr>
                                <td><span class="badge ${statusBadgeClass}">${check.status}</span></td>
                                <td>${new Date(check.checked_at).toLocaleString()}</td>
                                <td>${check.http_status_code || 'N/A'}</td>
                                <td>${check.response_time_ms || 'N/A'}</td>
                                <td>${errorCellHtml}</td>
                            </tr>
                        `;
                    });
                } else {
                    historyHtml = '<tr><td colspan="5" class="text-center">No history found.</td></tr>';
                }
                $('#history-table-body').html(historyHtml);
                renderPagination(response.links);
            },
            error: function() {
                $('#history-table-body').html('<tr><td colspan="5" class="text-center">Failed to load history.</td></tr>');
            }
        });
    }

    function renderPagination(links) {
        let paginationHtml = '';
        $.each(links, function(index, link) {
            if (link.url) {
                paginationHtml += `<li class="page-item ${link.active ? 'active' : ''}"><a class="page-link" href="#" data-url="${link.url}">${link.label}</a></li>`;
            } else {
                paginationHtml += `<li class="page-item disabled"><span class="page-link">${link.label}</span></li>`;
            }
        });
        $('#history-pagination').html(paginationHtml);
    }

    // --- Event Handlers ---

    // Load domain name
    $.ajax({
        url: `/api/domains/${domainId}`,
        method: 'GET',
        success: function(data) {
            $('#domain-name').text(data.domain);
        }
    });

    // Initial load
    loadHistory();

    // Handle pagination click
    $(document).on('click', '.pagination a', function(e) {
        e.preventDefault();
        loadHistory($(this).data('url'));
    });

    // Handle "See errors" button click
    $(document).on('click', '.see-errors-btn', function() {
        const errors = $(this).data('errors');
        let modalBodyHtml = '';
        if (Array.isArray(errors)) {
            errors.forEach(function(error) {
                modalBodyHtml += `<p>${error}</p>`;
            });
        }
        $('#errorModalBody').html(modalBodyHtml);
        $('#errorModal').modal('show');
    });
});
</script>
@endsection
