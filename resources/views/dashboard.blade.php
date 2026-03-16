@extends('layouts.app')

@section('content')
    <h1>Dashboard</h1>

    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Total Domains</h5>
                    <p class="card-text" id="total_domains">Loading...</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Healthy Domains</h5>
                    <p class="card-text" id="healthy_count">Loading...</p>
                </div>
            </div>
        </div>
{{--        <div class="col-md-3">--}}
{{--            <div class="card">--}}
{{--                <div class="card-body">--}}
{{--                    <h5 class="card-title">Degraded Domains</h5>--}}
{{--                    <p class="card-text" id="degraded_count">Loading...</p>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Unhealthy Domains</h5>
                    <p class="card-text" id="unhealthy_count">Loading...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Incidents & Expiring SSL sections removed for stability -->

    <h2 class="mt-4">Slowest Domains</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Domain</th>
                <th>Response Time (ms)</th>
            </tr>
        </thead>
        <tbody id="slowest_domains">
            <tr><td colspan="2" class="text-center">Loading...</td></tr>
        </tbody>
    </table>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        function loadDashboardData() {
            $.ajax({
                url: '/api/dashboard',
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    $('#total_domains').text(data.stats.total_domains);
                    $('#healthy_count').text(data.stats.healthy_count);
                    // $('#degraded_count').text(data.stats.degraded_count);
                    $('#unhealthy_count').text(data.stats.unhealthy_count);

                    let slowestHtml = '';
                    if (data.slowest_domains.length > 0) {
                        $.each(data.slowest_domains, function(index, domain) {
                            slowestHtml += `<tr><td>${domain.domain}</td><td>${domain.last_response_time_ms}</td></tr>`;
                        });
                    } else {
                        slowestHtml = '<tr><td colspan="2" class="text-center">No data available.</td></tr>';
                    }
                    $('#slowest_domains').html(slowestHtml);
                },
                error: function(xhr, status, error) {
                    console.error("Failed to load dashboard data:", error);
                }
            });
        }

        loadDashboardData();
    });
</script>
@endsection
