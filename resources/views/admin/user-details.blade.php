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
<style type="text/css">
.profile-card {
    background: linear-gradient(135deg, #009966, #006644);
    border-radius: 12px;
    box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
    color: white;
    padding: 20px;
    text-align: center;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.profile-card:hover {
    transform: translateY(-5px);
    box-shadow: 0px 6px 15px rgba(0, 0, 0, 0.15);
}

.profile-card-body {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.user-info {
    margin-bottom: 15px;
}

.user-name {
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 5px;
}

.user-email {
    font-size: 14px;
    color: #e0e0e0;
    margin-bottom: 8px;
}

/* Account number with copy effect */
.user-account-number {
    font-size: 16px;
    color: #ffffff;
    background: rgba(255, 255, 255, 0.2);
    padding: 8px 15px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    cursor: pointer;
    transition: background 0.3s ease;
}

.user-account-number:hover {
    background: rgba(255, 255, 255, 0.3);
}

.copy-icon {
    margin-left: 8px;
    font-size: 16px;
    color: #ffffff;
}

/* Balance button */
.balance-section {
    margin-top: 20px;
}

.balance-btn {
    background: white;
    color: #009966;
    font-size: 18px;
    font-weight: bold;
    padding: 12px 25px;
    border-radius: 30px;
    border: none;
    outline: none;
    cursor: pointer;
    transition: background 0.3s ease;
}

.balance-btn:hover {
    background: #f1f1f1;
}

    /* Modern Form Container */
.reset-pin-container {
    background: #fff;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.1);
    max-width: 400px;
    margin: auto;
    text-align: center;
}

.title {
    font-size: 20px;
    font-weight: bold;
    color: #333;
}

.subtitle {
    font-size: 14px;
    color: #777;
    margin-bottom: 20px;
}

/* Input Fields */
.input-field {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 16px;
    outline: none;
    transition: border 0.3s ease;
}

.input-field:focus {
    border-color: #009966;
}

/* PIN Input Boxes */
.pin-input-container {
    display: flex;
    justify-content: center;
    gap: 10px;
}

.pin-box {
    width: 50px;
    height: 50px;
    text-align: center;
    font-size: 20px;
    font-weight: bold;
    border: 1px solid #ddd;
    border-radius: 8px;
    outline: none;
    transition: border 0.3s ease;
}

.pin-box:focus {
    border-color: #009966;
    box-shadow: 0px 0px 5px rgba(0, 153, 102, 0.5);
}

/* Button */
.btn-primary {
    width: 100%;
    padding: 12px;
    background: #009966;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    cursor: pointer;
    transition: background 0.3s ease;
    margin-top: 10px;
}

.btn-primary:hover {
    background: #007d50;
}
/* Add this to your existing <style> section */

/* Scrollable Table Container */
.table-container {
    max-height: 600px; /* Adjust height as needed */
    overflow-y: auto;
    overflow-x: auto;
    border: 1px solid #ddd;
    border-radius: 8px;
    background: white;
}

/* Make table header sticky */
.table-container table {
    margin-bottom: 0;
}

.table-container thead th {
    position: sticky;
    top: 0;
    background: #009966;
    color: white;
    z-index: 10;
    box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.1);
}

/* Improve table styling */
.table-container .table {
    border-collapse: separate;
    border-spacing: 0;
}

.table-container .table tbody tr:hover {
    background-color: #f8f9fa;
}

/* Custom scrollbar styling (optional) */
.table-container::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.table-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.table-container::-webkit-scrollbar-thumb {
    background: #009966;
    border-radius: 10px;
}

.table-container::-webkit-scrollbar-thumb:hover {
    background: #007d50;
}

/* Responsive table on mobile */
@media (max-width: 768px) {
    .table-container {
        max-height: 400px;
    }
    
    .table-container table {
        font-size: 12px;
    }
    
    .table-container thead th,
    .table-container tbody td {
        padding: 8px 5px;
    }
}
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

<div class="content-body">
    <div class="container-fluid">
        <div class="row">
            <!-- User Info Sidebar -->
            <div class="col-lg-4 col-xl-3">
                <div class="profile-card">
                    <div class="profile-card-body">
                        <div class="user-info">
                            <h3 class="user-name" style="color: white;">{{ $user->name }}</h3>
                            <p class="user-email">{{ $user->email }}</p>
                            <p class="user-email">{{ $user->mobile }}</p>
                            <p class="user-email">Account Number: {{ $user->account_number ?? 'N/A'}}</p>
                            <p class="user-email">Joined at: {{ $user->created_at->format('d M Y, h:i A') }}</p>
                        </div>
                        <div class="balance-section">
                            <button class="balance-btn">
                                ₦ {{ number_format($balance->balance ?? 0, 2) }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transactions & Actions -->
            <div class="col-lg-8 col-xl-9">
            <div class="card">
            <div class="container mt-4">
                <h4>User Transactions</h4>
               <div class="loan-summary">
                    <span class="badge badge-info">Total Transactions: {{ $transactions->total() }}</span>
                </div>
                <!-- Wrap table in scrollable container -->
                <div class="table-container">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Amount</th>
                                <th>Description</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Reference</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transactions as $index => $transaction)
                                <tr>
                                    <td>{{ $transactions->firstItem() + $index }}</td>
                                    <td>₦ {{ number_format($transaction->amount, 2) }}</td>
                                    <td>{{ $transaction->description ?? 'N/A' }} | {{ $transaction->beneficiary ?? 'N/A' }}</td>
                                    <td>{{ ucfirst($transaction->type ?? 'N/A') }}</td>
                                    <td>
                                        <span class="badge badge-{{ $transaction->status === 'SUCCESS' ? 'success' : ($transaction->status === 'PENDING' ? 'warning' : 'danger') }}">
                                            {{ ucfirst($transaction->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $transaction->reference ?? 'N/A' }}</td>
                                    <td>{{ $transaction->created_at->format('d M, Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">No transactions found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Styled Pagination -->
                <div class="d-flex justify-content-center mt-3">
                    {{ $transactions->onEachSide(1)->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">User Loans</h4>
                    <div class="loan-summary">
                        <span class="badge badge-info">Total Loans: {{ $loans->total() }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Amount</th>
                                    <th>For</th>
                                    <th>Status</th>
                                    <th>Repayment Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($loans as $index => $loan)
                                    <tr>
                                        <td>{{ $loans->firstItem() + $index }}</td>
                                        <td>
                                            <strong>₦ {{ number_format($loan->amount, 2) }}</strong>
                                        </td>
                                        <td>{{ $loan->for ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge badge-{{ 
                                                $loan->status === 'APPROVED' ? 'success' : 
                                                ($loan->status === 'PENDING' ? 'warning' : 
                                                ($loan->status === 'REJECTED' ? 'danger' : 'secondary')) 
                                            }}">
                                                {{ ucfirst($loan->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-{{ 
                                                $loan->repayment_status === 'PAID' ? 'success' : 
                                                ($loan->repayment_status === 'PARTIAL' ? 'info' : 
                                                ($loan->repayment_status === 'OVERDUE' ? 'danger' : 'warning')) 
                                            }}">
                                                {{ ucfirst($loan->repayment_status ?? 'UNPAID') }}
                                            </span>
                                        </td>
                                        <td>{{ $loan->created_at->format('d M, Y H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">No loans found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center mt-3">
                        {{ $loans->onEachSide(1)->links('pagination::bootstrap-4') }}
                    </div>
                </div>
            </div>
                <!-- Optional Back Button -->
                <div class="mt-3">
                    <a href="{{ url('admin/users') }}" class="btn btn-secondary btn-sm">Back to Users</a>
                </div>
            </div>
        </div>
    </div>
</div>

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