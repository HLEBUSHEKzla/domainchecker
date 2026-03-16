function getStatusBadgeClass(status) {
    switch (status) {
        case 'healthy':
            return 'badge-success';
        case 'degraded':
            return 'badge-warning';
        case 'unhealthy':
            return 'badge-danger';
        default:
            return 'badge-secondary';
    }
}

function renderPagination(selector, links) {
    let paginationHtml = '';
    $.each(links, function(index, link) {
        if (link.url) {
            paginationHtml += `<li class="page-item ${link.active ? 'active' : ''}"><a class="page-link" href="#" data-url="${link.url}">${link.label}</a></li>`;
        } else {
            paginationHtml += `<li class="page-item disabled"><span class="page-link">${link.label}</span></li>`;
        }
    });
    $(selector).html(paginationHtml);
}
