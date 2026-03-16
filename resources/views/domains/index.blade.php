@extends('layouts.app')

@section('content')
    <h1>Domains</h1>
    <a href="{{ route('domains.create') }}" class="btn btn-primary mb-3">Add Domain</a>
    <table class="table table-hover">
        <thead>
            <tr>
                <th style="width: 20px;"></th>
                <th>Name</th>
                <th>Domain</th>
                <th>Status</th>
                <th>Last Check</th>
                <th>Response Time (ms)</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="domains-table-body">
            <tr><td colspan="7" class="text-center">Loading...</td></tr>
        </tbody>
    </table>
    <nav>
        <ul class="pagination" id="domains-pagination"></ul>
    </nav>
@endsection

@section('scripts')
<style>
    .details-row { background-color: #f8f9fa; }
    .details-row .card { margin-bottom: 1rem; }
    .toggle-details-btn { cursor: pointer; }
    .redirect-chain { list-style-type: none; padding-left: 0; }
    .redirect-chain li { position: relative; padding-left: 25px; margin-bottom: 5px; }
    .redirect-chain li:before { content: '\f061'; font-family: 'Font Awesome 6 Free'; font-weight: 900; position: absolute; left: 0; top: 2px; }
</style>
<script>
$(document).ready(function() {
    let currentUrl = '/api/domains';
    let refreshInterval;

    function loadDomains(url, isAutoRefresh = false) {
        let expandedIds = [];
        if (isAutoRefresh) {
            $('.details-row:visible').each(function() { expandedIds.push($(this).attr('id')); });
        }
        if (!isAutoRefresh) {
            $('#domains-table-body').html('<tr><td colspan="7" class="text-center">Loading...</td></tr>');
        }
        currentUrl = url;

        $.ajax({
            url: url,
            method: 'GET',
            success: function(response) {
                let domainsHtml = '';
                if (response.data.length > 0) {
                    $.each(response.data, function(index, domain) {
                        let historyUrl = "{{ route('domains.history', ':id') }}".replace(':id', domain.id);
                        let editUrl = "{{ route('domains.edit', ':id') }}".replace(':id', domain.id);
                        let detailsHtml = generateDetailsHtml(domain);
                        let statusBadgeClass = getStatusBadgeClass(domain.last_status);

                        domainsHtml += `
                            <tr class="domain-row">
                                <td><i class="fas fa-chevron-right toggle-details-btn" data-target="#details-${domain.id}"></i></td>
                                <td>${domain.name}</td>
                                <td>${domain.domain}</td>
                                <td><span class="badge ${statusBadgeClass}">${domain.last_status || 'N/A'}</span></td>
                                <td>${domain.last_checked_at ? new Date(domain.last_checked_at).toLocaleString() : 'N/A'}</td>
                                <td>${domain.last_response_time_ms || 'N/A'}</td>
                                <td>
                                    <a href="${historyUrl}" class="btn btn-sm btn-info" title="History"><i class="fas fa-history"></i></a>
                                    <a href="${editUrl}" class="btn btn-sm btn-primary" title="Edit"><i class="fas fa-pencil-alt"></i></a>
                                    <button class="btn btn-sm btn-danger delete-domain-btn" data-id="${domain.id}" title="Delete"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <tr class="details-row" id="details-${domain.id}" style="display: none;">
                                <td colspan="7">${detailsHtml}</td>
                            </tr>
                        `;
                    });
                } else {
                    domainsHtml = '<tr><td colspan="7" class="text-center">No domains found.</td></tr>';
                }
                $('#domains-table-body').html(domainsHtml);

                if (isAutoRefresh && expandedIds.length > 0) {
                    expandedIds.forEach(function(id) {
                        $('#' + id).show();
                        $(`[data-target="#${id}"]`).removeClass('fa-chevron-right').addClass('fa-chevron-down');
                    });
                }
                if (!isAutoRefresh) {
                    renderPagination('#domains-pagination', response.meta.links);
                }
            },
            error: function() {
                if (!isAutoRefresh) {
                    $('#domains-table-body').html('<tr><td colspan="7" class="text-center">Failed to load domains.</td></tr>');
                }
            }
        });
    }

    function generateDetailsHtml(domain) {
        const check = domain.latest_check;
        if (!check || !check.metadata) return '<div class="p-3">No check data available.</div>';
        const meta = check.metadata;
        const jsonBlob = new Blob([JSON.stringify(meta, null, 2)], {type : 'application/json'});
        const downloadUrl = URL.createObjectURL(jsonBlob);
        const dnsInfo = `<b>IP:</b> ${meta.dns?.resolved_ip || 'N/A'}`;
        const sslInfo = `<b>Issuer:</b> ${meta.ssl?.ssl_issuer || 'N/A'}<br><b>Expires:</b> ${meta.ssl?.ssl_expires_at ? new Date(meta.ssl.ssl_expires_at).toLocaleDateString() : 'N/A'}`;
        const httpInfo = `<b>Status:</b> ${meta.http?.http_status_code || 'N/A'}<br><b>Content-Type:</b> ${meta.http?.content_type || 'N/A'}`;
        let contentInfo = `<b>Title:</b> ${meta.content?.page_title || 'N/A'}<br><b>H1:</b> ${meta.content?.h1 || 'N/A'}<br><b>Description:</b> ${meta.content?.meta_description || 'N/A'}`;
        let markerInfo = '';
        if (domain.expected_content_marker) {
            if (meta.content?.expected_marker_found === true) markerInfo = `<br><b>Marker:</b> <span class="badge badge-success">Found</span> "${domain.expected_content_marker}"`;
            else if (meta.content?.expected_marker_found === false) markerInfo = `<br><b>Marker:</b> <span class="badge badge-danger">Not Found</span> "${domain.expected_content_marker}"`;
            else markerInfo = `<br><b>Marker:</b> <span class="badge badge-secondary">N/A</span> "${domain.expected_content_marker}"`;
        }
        contentInfo += markerInfo;
        const searchInfo = `<b>Safe Browsing:</b> ${meta.search?.safe_browsing_flag || 'N/A'}<br><b>Note:</b> ${meta.search?.search_reputation_note || ''}`;
        let redirectInfo = '<b>No redirects.</b>';
        if (meta.redirect?.redirect_count > 0) {
            let chainHtml = '<ul class="redirect-chain">';
            meta.redirect.redirect_chain.forEach(r => { chainHtml += `<li><span class="badge badge-info">${r.status_code}</span> ${r.url}</li>`; });
            chainHtml += '</ul>';
            redirectInfo = `<b>Redirects:</b> ${meta.redirect.redirect_count}<br>${chainHtml}`;
        }
        return `<div class="p-3"><div class="row"><div class="col-md-4"><div class="card"><div class="card-header">Server, SSL, Connection</div><ul class="list-group list-group-flush"><li class="list-group-item">${dnsInfo}</li><li class="list-group-item">${sslInfo}</li><li class="list-group-item">${httpInfo}</li></ul></div></div><div class="col-md-4"><div class="card"><div class="card-header">Content & SEO</div><ul class="list-group list-group-flush"><li class="list-group-item">${contentInfo}</li><li class="list-group-item">${searchInfo}</li></ul></div></div><div class="col-md-4"><div class="card"><div class="card-header">Redirects</div><div class="card-body">${redirectInfo}</div></div></div></div><div class="mt-3"><a href="${downloadUrl}" download="check_details_${domain.id}_${check.id}.json" class="btn btn-sm btn-secondary"><i class="fas fa-download"></i> Download Full JSON</a></div></div>`;
    }

    function startAutoRefresh() {
        if (refreshInterval) clearInterval(refreshInterval);
        refreshInterval = setInterval(() => loadDomains(currentUrl, true), 30000);
    }

    loadDomains(currentUrl);
    startAutoRefresh();
    $(document).on('click', '.pagination a', function(e) { e.preventDefault(); loadDomains($(this).data('url')); startAutoRefresh(); });
    $(document).on('click', '.toggle-details-btn', function() { $(this).toggleClass('fa-chevron-right fa-chevron-down'); $($(this).data('target')).toggle(); });
    $(document).on('click', '.delete-domain-btn', function() {
        if (confirm('Are you sure?')) {
            $.ajax({
                url: `/api/domains/${$(this).data('id')}`,
                method: 'DELETE',
                success: () => loadDomains(currentUrl),
                error: () => alert('Failed to delete domain.')
            });
        }
    });
});
</script>
@endsection
