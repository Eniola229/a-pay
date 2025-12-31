@include('components.header')
<meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Paystack inline script -->
    <script src="https://js.paystack.co/v1/inline.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Include Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<style>
.receipt-container {
    font-family: 'Arial', sans-serif;
    background: #fff;
    padding: 20px;
    border-radius: 10px;
    max-width: 380px;
    margin: auto;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

.receipt-header {
    margin-bottom: 10px;
}

.receipt-logo {
    width: 80px;
    border-radius: 20px;
}

.amount {
    font-size: 24px;
    font-weight: bold;
    color: #28a745;
}

.status {
    font-size: 20px;
    font-weight: bold;
    color: #28a745; /* Default green for success */
}

.status.pending {
    color: #ffc107;
}

.status.failed {
    color: #dc3545;
}

.table-borderless td {
    font-size: 14px;
    padding: 5px 0;
    color: black;
}

.support-text {
    font-weight: bold;
    font-size: 14px;
    color: black;
}

.text-muted {
    font-size: 12px;
    color: black;
}

/* Centering the Table */
.table-container {
    display: flex;
    justify-content: center;
    align-items: center;
}

.custom-table {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.1);
}

.custom-table th {
    background: #28a745;
    color: white;
    text-align: center;
    font-size: 14px;
    padding: 12px 8px;
    font-weight: 600;
    white-space: nowrap;
}

.custom-table td {
    text-align: center;
    vertical-align: middle;
    padding: 12px 8px;
    font-size: 13px;
}

.custom-table tbody tr:hover {
    background: #f8f9fa;
}

.badge {
    padding: 6px 12px;
    font-size: 11px;
    border-radius: 5px;
    font-weight: 600;
    text-transform: uppercase;
}

.badge-overdue {
    background-color: #dc3545;
    color: white;
}

.btn-sm {
    font-size: 12px;
    padding: 6px 12px;
    border-radius: 6px;
    transition: all 0.3s ease-in-out;
}

.btn-sm:hover {
    transform: scale(1.05);
}

.page-header {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0px 2px 10px rgba(0, 0, 0, 0.05);
    margin-bottom: 20px;
}

.page-title {
    font-size: 24px;
    font-weight: 700;
    color: #333;
    margin: 0;
}

.stats-row {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.stat-card {
    background: white;
    padding: 15px 20px;
    border-radius: 8px;
    box-shadow: 0px 2px 10px rgba(0, 0, 0, 0.05);
    flex: 1;
    min-width: 200px;
}

.stat-label {
    font-size: 12px;
    color: #666;
    text-transform: uppercase;
    font-weight: 600;
}

.stat-value {
    font-size: 24px;
    font-weight: 700;
    color: #333;
    margin-top: 5px;
}

.stat-card.pending .stat-value { color: #ffc107; }
.stat-card.approved .stat-value { color: #28a745; }
.stat-card.declined .stat-value { color: #dc3545; }
.stat-card.overdue .stat-value { color: #ff4444; }

</style>

 <!--**********************************
        Main wrapper start
    ***********************************-->
    <div id="main-wrapper">

        <!--**********************************
            Nav header start
        ***********************************-->
        @include('components.nav-header')
        <!--**********************************
            Nav header end
        ***********************************-->

        <!--**********************************
            Header start
        ***********************************-->
         @include('components.main-header')
        <!--**********************************
            Header end ti-comment-alt
        ***********************************-->

        <!--**********************************
            Sidebar start
        ***********************************-->
        @include('components.admin-sidenav')
        <!--**********************************
            Sidebar end
        ***********************************-->

        <!--**********************************
            Content body start
        ***********************************-->


    <div class="container-fluid col-xl-8 col-lg-10 col-md-12 p-4">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title"><i class="fa fa-hand-holding-usd"></i> Loan Management</h1>
        </div>

        <!-- Statistics Cards -->
        @php
            $totalLoans = $loans->total();
            $pendingLoans = \App\Models\Borrow::where('status', 'pending')->count();
            $approvedLoans = \App\Models\Borrow::where('status', 'approved')->count();
            $declinedLoans = \App\Models\Borrow::where('status', 'declined')->count();
            $overdueLoans = \App\Models\Borrow::where('status', 'approved')
                ->where('created_at', '<=', now()->subWeeks(2))
                ->where('repayment_status', '!=', 'PAID')
                ->count();
        @endphp

        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-label">Total Loans</div>
                <div class="stat-value">{{ $totalLoans }}</div>
            </div>
            <div class="stat-card pending">
                <div class="stat-label">Pending</div>
                <div class="stat-value">{{ $pendingLoans }}</div>
            </div>
            <div class="stat-card approved">
                <div class="stat-label">Approved</div>
                <div class="stat-value">{{ $approvedLoans }}</div>
            </div>
            <div class="stat-card declined">
                <div class="stat-label">Declined</div>
                <div class="stat-value">{{ $declinedLoans }}</div>
            </div>
            <div class="stat-card overdue">
                <div class="stat-label">Overdue</div>
                <div class="stat-value">{{ $overdueLoans }}</div>
            </div>
        </div>

        <!-- Loans Table -->
        <div class="table-responsive">
            <table class="table table-striped table-bordered custom-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Contact</th>
                        <th>Balance</th>
                        <th>Amount Owed</th>
                        <th>Loan Amount</th>
                        <th>Purpose</th>
                        <th>Status</th>
                        <th>Repayment</th>
                        <th>Loan Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($loans as $loan)
                        @php
                            // Check if loan is overdue (approved, not paid, and older than 2 weeks)
                            $isOverdue = $loan->status === 'approved' 
                                && $loan->repayment_status !== 'PAID' 
                                && $loan->created_at->diffInWeeks(now()) >= 2;
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $loan->user->name ?? 'N/A' }}</strong>
                            </td>
                            <td>
                                <div style="font-size: 12px;">
                                    <i class="fa fa-phone"></i> {{ $loan->user->mobile ?? 'N/A' }}<br>
                                    <i class="fa fa-envelope"></i> {{ $loan->user->email ?? 'N/A' }}
                                </div>
                            </td>
                            <td>
                                <strong style="color: #28a745;">₦{{ number_format($loan->user->balance->balance ?? 0, 2) }}</strong>
                            </td>
                            <td>
                                <strong style="color: #dc3545;">₦{{ number_format($loan->user->balance->owe ?? 0, 2) }}</strong>
                            </td>
                            <td>
                                <strong>₦{{ number_format($loan->amount, 2) }}</strong>
                            </td>
                            <td>{{ $loan->for ?? 'N/A' }}</td>
                            <td>
                                <span class="badge badge-{{ $loan->status === 'approved' ? 'success' : ($loan->status === 'pending' ? 'warning' : 'danger') }}">
                                    {{ strtoupper($loan->status) }}
                                </span>
                            </td>
                            <td>
                                @if($isOverdue)
                                    <span class="badge badge-overdue">
                                        <i class="fa fa-exclamation-triangle"></i> OVERDUE
                                    </span>
                                @else
                                    <span class="badge badge-{{ $loan->repayment_status === 'PAID' ? 'success' : ($loan->repayment_status === 'NOT PAID FULL' ? 'warning' : 'danger') }}">
                                        {{ strtoupper($loan->repayment_status) }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                <small>{{ $loan->created_at->format('d M Y') }}</small><br>
                                <small style="color: #666;">{{ $loan->created_at->format('h:i A') }}</small>
                            </td>
                            <td>
                                <a href="{{ url('admin/users/' . ($loan->user->id ?? '#')) }}">
                                    <button class="btn btn-primary btn-sm {{ !isset($loan->user) ? 'disabled' : '' }}" 
                                            {{ !isset($loan->user) ? 'disabled' : '' }}
                                            title="View User Profile">
                                        <i class="fa fa-user"></i> Profile
                                    </button>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center" style="padding: 40px;">
                                <i class="fa fa-inbox" style="font-size: 48px; color: #ccc;"></i>
                                <p style="margin-top: 15px; color: #666;">No loans found</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="mt-4">
            {{ $loans->links() }}
        </div>
    </div>

            <!-- #/ container -->
        </div>

    <!--**********************************
        Scripts
    ***********************************-->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="{{ asset('plugins/common/common.min.js') }}"></script>
    <script src="{{ asset('js/custom.min.js') }}"></script>
    <script src="{{ asset('js/settings.js') }}"></script>
    <script src="{{ asset('js/gleek.js') }}"></script>
    <script src="{{ asset('js/styleSwitcher.js') }}"></script>
    <!-- Chartjs -->
    <script src="{{ asset('plugins/chart.js/Chart.bundle.min.js') }}"></script>
    <!-- Circle progress -->
    <script src="{{ asset('plugins/circle-progress/circle-progress.min.js') }}"></script>
    <!-- Datamap -->
    <script src="{{ asset('plugins/d3v3/index.js') }}"></script>
    <script src="{{ asset('plugins/topojson/topojson.min.js') }}"></script>
    <script src="{{ asset('plugins/datamaps/datamaps.world.min.js') }}"></script>
    <!-- Morrisjs -->
    <script src="{{ asset('plugins/raphael/raphael.min.js') }}"></script>
    <script src="{{ asset('plugins/morris/morris.min.js') }}"></script>
    <!-- Pignose Calender -->
    <script src="{{ asset('plugins/moment/moment.min.js') }}"></script>
    <script src="{{ asset('plugins/pg-calendar/js/pignose.calendar.min.js') }}"></script>
    <!-- ChartistJS -->
    <script src="{{ asset('plugins/chartist/js/chartist.min.js') }}"></script>
    <script src="{{ asset('plugins/chartist-plugin-tooltips/js/chartist-plugin-tooltip.min.js') }}"></script>
    <!-- Dashboard Script -->
    <script src="{{ asset('js/dashboard/dashboard-1.js') }}"></script>
</body>
</html>