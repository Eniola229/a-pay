@include('components.header')
<meta name="csrf-token" content="{{ csrf_token() }}">
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<style>
    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #e8f5e9 100%);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .modal-fullscreen .modal-body {
            padding: 15px !important;
        }
        
        .modal-fullscreen .modal-header,
        .modal-fullscreen .modal-footer {
            padding: 15px !important;
        }
        
        .receipt-section {
            padding: 15px !important;
            margin-bottom: 15px !important;
        }
        
        .info-row {
            flex-direction: column;
            gap: 5px;
            padding: 10px 0 !important;
        }
        
        .info-label {
            min-width: auto !important;
        }
        
        .info-value {
            text-align: left !important;
        }
        
        .table-responsive {
            font-size: 12px;
        }
        
        .summary-card {
            margin-bottom: 10px;
        }
        
        .filter-section {
            padding: 15px !important;
        }
        
        .btn-sm {
            font-size: 10px !important;
            padding: 4px 8px !important;
        }
    }

    .table-container {
        background: white;
        border-radius: 16px;
        padding: 25px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        margin-top: 20px;
    }

    .filter-section {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        border-radius: 16px;
        padding: 25px;
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.1);
        border: 2px solid #e8f5e9;
        margin-bottom: 25px;
    }

    .filter-section h5 {
        color: #1e7e34;
        font-weight: 700;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .filter-section h5::before {
        content: '';
        width: 4px;
        height: 24px;
        background: linear-gradient(180deg, #28a745 0%, #20c997 100%);
        border-radius: 4px;
    }

    .form-control {
        border: 2px solid #e8f5e9;
        border-radius: 10px;
        padding: 10px 15px;
        transition: all 0.3s;
    }

    .form-control:focus {
        border-color: #28a745;
        box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
        outline: none;
    }

    .btn-success {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        border: none;
        border-radius: 10px;
        padding: 10px 20px;
        font-weight: 600;
        transition: all 0.3s;
    }

    .btn-success:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(40, 167, 69, 0.3);
    }

    .summary-card {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        border-radius: 14px;
        padding: 20px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        border-left: 5px solid #28a745;
        transition: all 0.3s;
        margin-bottom: 15px;
    }

    .summary-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(40, 167, 69, 0.15);
    }

    .summary-card h6 {
        color: #666;
        font-size: 13px;
        font-weight: 600;
        margin-bottom: 10px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .summary-card h4 {
        font-size: 26px;
        font-weight: 800;
        margin: 0;
    }

    .border-left-success { border-left-color: #28a745 !important; }
    .border-left-danger { border-left-color: #dc3545 !important; }
    .border-left-info { border-left-color: #17a2b8 !important; }
    .border-left-warning { border-left-color: #ffc107 !important; }
    .border-left-primary { border-left-color: #007bff !important; }
    .border-left-secondary { border-left-color: #6c757d !important; }
    .border-left-dark { border-left-color: #343a40 !important; }

    .custom-table {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    .custom-table thead {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    }

    .custom-table th {
        color: white;
        text-align: center;
        padding: 15px 12px;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 12px;
        letter-spacing: 0.5px;
    }

    .custom-table td {
        text-align: center;
        padding: 15px 10px;
        font-size: 14px;
        vertical-align: middle;
    }

    .custom-table tbody tr {
        transition: all 0.3s;
        border-bottom: 1px solid #f0f0f0;
    }

    .custom-table tbody tr:hover {
        background: linear-gradient(90deg, rgba(40, 167, 69, 0.05) 0%, transparent 100%);
        transform: scale(1.01);
    }

    .badge {
        padding: 6px 12px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .btn-sm {
        font-size: 12px;
        padding: 6px 12px;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s;
        margin: 2px;
    }

    .btn-sm:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    /* Make ALL modals fullscreen by default */
    .modal-dialog {
        max-width: 100% !important;
        margin: 0 !important;
        height: 100vh !important;
    }

    .modal-content {
        height: 100vh !important;
        border-radius: 0 !important;
    }

    .modal-fullscreen {
        max-width: 100% !important;
        margin: 0 !important;
    }

    .modal-fullscreen .modal-content {
        height: 100vh;
        border-radius: 0;
    }

    .modal-header {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        border: none;
        padding: 20px 25px;
    }

    .modal-title {
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .logs-section {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 25px;
        margin-top: 0;
        max-height: 700px;
        overflow-y: auto;
        min-height: 400px;
    }

    .log-item {
        background: white;
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 20px;
        border-left: 6px solid #28a745;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        transition: all 0.3s;
    }

    .log-item:hover {
        transform: translateX(8px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
    }

    .log-item h6 {
        font-size: 17px;
        margin-bottom: 12px;
    }

    .log-item p {
        font-size: 15px;
        line-height: 1.6;
        color: #555;
    }

    .log-item.error {
        border-left-color: #dc3545;
    }

    .log-item.success {
        border-left-color: #28a745;
    }

    .log-item small {
        color: #666;
        font-size: 11px;
    }

    .refund-section {
        background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
        padding: 15px;
        border-radius: 10px;
        border: 2px solid #ffc107;
        margin: 15px 0;
    }

    .page-header {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        border-radius: 16px;
        padding: 25px;
        color: white;
        margin-bottom: 25px;
        box-shadow: 0 6px 20px rgba(40, 167, 69, 0.2);
    }

    .page-header h3 {
        margin: 0;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .modal-fullscreen .modal-body {
        padding: 50px 80px;
        overflow-y: auto;
        background: linear-gradient(135deg, #f5f7fa 0%, #e8f5e9 100%);
    }

    .modal-fullscreen .modal-header {
        padding: 30px 80px;
    }

    .modal-fullscreen .modal-footer {
        padding: 25px 80px;
    }

    .receipt-section {
        background: white;
        border-radius: 16px;
        padding: 35px;
        margin-bottom: 30px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(40, 167, 69, 0.1);
    }

    .receipt-section h6 {
        color: #1e7e34;
        font-weight: 700;
        margin-bottom: 25px;
        font-size: 20px;
        display: flex;
        align-items: center;
        gap: 12px;
        padding-bottom: 18px;
        border-bottom: 3px solid #e8f5e9;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        padding: 18px 0;
        border-bottom: 1px solid #f0f0f0;
        font-size: 16px;
    }

    .info-label {
        font-weight: 600;
        color: #666;
        font-size: 15px;
        min-width: 180px;
    }

    .info-value {
        color: #333;
        font-weight: 500;
        text-align: right;
        flex: 1;
        font-size: 15px;
    }

    .info-row:last-child {
        border-bottom: none;
    }

    .info-label {
        font-weight: 600;
        color: #666;
        font-size: 13px;
    }

    .info-value {
        color: #333;
        font-weight: 500;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .summary-card {
        animation: fadeIn 0.5s ease-out;
    }

    .summary-card:nth-child(1) { animation-delay: 0.1s; }
    .summary-card:nth-child(2) { animation-delay: 0.2s; }
    .summary-card:nth-child(3) { animation-delay: 0.3s; }
    .summary-card:nth-child(4) { animation-delay: 0.4s; }
</style>

<div id="main-wrapper">
    @include('components.nav-header')
    @include('components.main-header')
    @include('components.admin-sidenav')

    <div class="content-body">
        <div class="container-fluid p-4">
            
            <!-- PAGE HEADER -->
            <div class="page-header">
                <h3><i class="fas fa-exchange-alt"></i> Transaction Management</h3>
                <p class="mb-0" style="opacity: 0.9;">Monitor and manage all platform transactions</p>
            </div>

            <!-- FILTERS -->
            <div class="filter-section">
                <h5><i class="fas fa-filter"></i> Advanced Filters</h5>
                <form method="GET" action="{{ route('admin.transactions') }}" id="filterForm">
                    <div class="row g-3">
                        <!-- Search Bar -->
                        <div class="col-md-12">
                            <input type="text" name="search" class="form-control form-control-lg" 
                                   placeholder="🔍 Search by name, email, mobile, reference, description..." 
                                   value="{{ request('search') }}">
                        </div>
                        
                        <!-- Date Filters -->
                        <div class="col-md-2">
                            <input type="number" name="year" class="form-control" placeholder="Year" value="{{ request('year') }}">
                        </div>
                        <div class="col-md-2">
                            <input type="number" name="month" class="form-control" placeholder="Month" value="{{ request('month') }}" min="1" max="12">
                        </div>
                        <div class="col-md-2">
                            <input type="number" name="day" class="form-control" placeholder="Day" value="{{ request('day') }}" min="1" max="31">
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="from" class="form-control" value="{{ request('from') }}">
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="to" class="form-control" value="{{ request('to') }}">
                        </div>
                        
                        <!-- Buttons -->
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="fa fa-filter"></i> Filter
                            </button>
                        </div>
                        <div class="col-md-1">
                            <a href="{{ route('admin.transactions') }}" class="btn btn-secondary w-100">
                                <i class="fa fa-undo"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- SUMMARY CARDS -->
            @if(isset($summary))
                <div class="row">
                    <div class="col-md-3">
                        <div class="summary-card border-left-primary">
                            <h6><i class="fas fa-chart-line"></i> Total Transactions</h6>
                            <h4 class="text-dark">₦{{ number_format($summary['total'], 2) }}</h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="summary-card border-left-success">
                            <h6><i class="fas fa-check-circle"></i> Successful</h6>
                            <h4 class="text-success">₦{{ number_format($summary['success'], 2) }}</h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="summary-card border-left-danger">
                            <h6><i class="fas fa-times-circle"></i> Failed</h6>
                            <h4 class="text-danger">₦{{ number_format($summary['failed'], 2) }}</h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="summary-card border-left-info">
                            <h6><i class="fas fa-wallet"></i> Wallet Top-up</h6>
                            <h4 class="text-info">₦{{ number_format($summary['wallet_topup'], 2) }}</h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="summary-card border-left-success">
                            <h6><i class="fas fa-exchange-alt"></i> Transfer to A-Pay</h6>
                            <h4 class="text-success">₦{{ number_format($summary['to_apay'], 2) }}</h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="summary-card border-left-warning">
                            <h6><i class="fas fa-phone"></i> Airtime</h6>
                            <h4 class="text-warning">₦{{ number_format($summary['airtime'], 2) }}</h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="summary-card border-left-success">
                            <h6><i class="fas fa-wifi"></i> Data</h6>
                            <h4 class="text-success">₦{{ number_format($summary['data'], 2) }}</h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="summary-card border-left-secondary">
                            <h6><i class="fas fa-bolt"></i> Electricity</h6>
                            <h4 class="text-secondary">₦{{ number_format($summary['electricity'], 2) }}</h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="summary-card border-left-dark">
                            <h6><i class="fas fa-dice"></i> Betting</h6>
                            <h4 class="text-dark">₦{{ number_format($summary['betting'], 2) }}</h4>
                        </div>
                    </div>
                </div>
            @endif>

            <!-- TRANSACTION TABLE -->
            <div class="table-container">
                <h5 class="mb-4" style="color: #1e7e34; font-weight: 700;">
                    <i class="fas fa-list"></i> Transaction Records
                </h5>
                <div class="table-responsive">
                    <table class="table table-hover custom-table">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Mobile</th>
                                <th>Description</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transactions as $transaction)
                                <tr>
                                    <td><strong>{{ $transaction->user->name ?? 'N/A' }}</strong></td>
                                    <td>{{ $transaction->user->mobile ?? 'N/A' }}</td>
                                    <td class="text-left">{{ Str::limit($transaction->description ?? 'N/A', 40) }}</td>
                                    <td><strong>₦{{ number_format($transaction->amount, 2) }}</strong></td>
                                    <td>
                                        @if($transaction->status === 'SUCCESS')
                                            <span class="badge badge-success"><i class="fas fa-check"></i> Success</span>
                                        @elseif($transaction->status === 'PENDING')
                                            <span class="badge badge-warning"><i class="fas fa-clock"></i> Pending</span>
                                        @else
                                            <span class="badge badge-danger"><i class="fas fa-times"></i> Error</span>
                                        @endif
                                    </td>
                                    <td>{{ $transaction->created_at->format('d M Y, h:i A') }}</td>
                                    <td>
                                        <button class="btn btn-success btn-sm view-receipt"
                                            data-id="{{ $transaction->id }}"
                                            data-user-name="{{ $transaction->user->name ?? 'N/A' }}"
                                            data-user-mobile="{{ $transaction->user->mobile ?? 'N/A' }}"
                                            data-description="{{ $transaction->description }}"
                                            data-amount="{{ number_format($transaction->amount, 2) }}"
                                            data-status="{{ $transaction->status }}"
                                            data-reference="{{ $transaction->reference ?? 'N/A' }}"
                                            data-source="{{ $transaction->source ?? 'N/A' }}"
                                            data-beneficiary="{{ $transaction->beneficiary ?? 'N/A' }}"
                                            data-type="{{ $transaction->type ?? 'N/A' }}"
                                            data-balance-before="{{ number_format($transaction->balance_before ?? 0, 2) }}"
                                            data-balance-after="{{ number_format($transaction->balance_after ?? 0, 2) }}"
                                            data-charges="{{ number_format($transaction->charges ?? 0, 2) }}"
                                            data-cashback="{{ number_format($transaction->cash_back ?? 0, 2) }}"
                                            data-date="{{ $transaction->created_at->format('d M Y, h:i A') }}">
                                            <i class="fa fa-receipt"></i> View
                                        </button>
                                        @if($transaction->status !== 'SUCCESS')
                                            <button class="btn btn-warning btn-sm edit-transaction"
                                                data-id="{{ $transaction->id }}"
                                                data-description="{{ $transaction->description }}"
                                                data-status="{{ $transaction->status }}"
                                                data-user-id="{{ $transaction->user->id ?? 'N/A' }}"
                                                data-amount="{{ $transaction->amount }}">
                                                <i class="fa fa-edit"></i> Edit
                                            </button>
                                        @endif
                                        <a href="{{ url('admin/users/' . ($transaction->user->id ?? '#')) }}">
                                            <button class="btn btn-primary btn-sm">
                                                <i class="fa fa-user"></i> Profile
                                            </button>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-5">
                                    <i class="fas fa-inbox fa-3x mb-3" style="opacity: 0.3;"></i>
                                    <p>No transactions found</p>
                                </td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- PAGINATION -->
                <div class="d-flex justify-content-center mt-4">
                    {{ $transactions->appends(request()->query())->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- TRANSACTION DETAILS MODAL -->
<div class="modal fade" id="receiptModal" tabindex="-1">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" style="font-size: 16px; font-weight: 600;"><i class="fa fa-file-invoice"></i> Transaction Details</h5>
                <button type="button" class="close text-white" data-dismiss="modal" style="font-size: 24px; opacity: 1;">&times;</button>
            </div>
            <div class="modal-body">
                <!-- Amount & Status -->
                <div class="text-center mb-3 py-2" style="background: white; border-radius: 8px; padding: 20px;">
                    <h1 class="text-success mb-2" id="receipt-amount" style="font-size: 36px; font-weight: 700;"></h1>
                    <span class="badge badge-lg" id="receipt-status-badge" style="font-size: 13px; padding: 6px 20px; border-radius: 20px;"></span>
                </div>

                <div class="row">
                    <!-- Left Column -->
                    <div class="col-lg-7 mb-3">
                        <!-- Customer Information -->
                        <div class="receipt-section">
                            <h6><i class="fa fa-user-circle"></i> Customer</h6>
                            <div class="info-row">
                                <span class="info-label"><i class="fas fa-user text-success"></i> Name:</span>
                                <span class="info-value" id="receipt-user-name"></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label"><i class="fas fa-phone text-success"></i> Mobile:</span>
                                <span class="info-value" id="receipt-user-mobile"></span>
                            </div>
                        </div>

                        <!-- Transaction Info -->
                        <div class="receipt-section">
                            <h6><i class="fa fa-info-circle"></i> Transaction Info</h6>
                            <div class="info-row">
                                <span class="info-label"><i class="fas fa-exchange-alt text-success"></i> Type:</span>
                                <span class="info-value" id="receipt-type"></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label"><i class="fas fa-hashtag text-success"></i> Reference:</span>
                                <span class="info-value" id="receipt-reference" style="font-size: 10px; word-break: break-all; font-family: monospace;"></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label"><i class="fas fa-layer-group text-success"></i> Source:</span>
                                <span class="info-value" id="receipt-source"></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label"><i class="fas fa-calendar-alt text-success"></i> Date:</span>
                                <span class="info-value" id="receipt-date"></span>
                            </div>
                        </div>

                        <!-- Transaction Details -->
                        <div class="receipt-section">
                            <h6><i class="fa fa-file-alt"></i> Details</h6>
                            <div class="info-row">
                                <span class="info-label"><i class="fas fa-align-left text-success"></i> Description:</span>
                                <span class="info-value" id="receipt-description"></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label"><i class="fas fa-user-tag text-success"></i> Beneficiary:</span>
                                <span class="info-value" id="receipt-beneficiary"></span>
                            </div>
                        </div>

                        <!-- Financial Breakdown -->
                        <div class="receipt-section">
                            <h6><i class="fa fa-coins"></i> Financial Breakdown</h6>
                            <div class="info-row">
                                <span class="info-label"><i class="fas fa-wallet text-primary"></i> Opening:</span>
                                <span class="info-value text-primary" id="receipt-balance-before"></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label"><i class="fas fa-money-bill-wave text-info"></i> Amount:</span>
                                <span class="info-value text-info" id="receipt-amount-detail"></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label"><i class="fas fa-receipt text-danger"></i> Charges:</span>
                                <span class="info-value text-danger" id="receipt-charges"></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label"><i class="fas fa-gift text-success"></i> Cashback:</span>
                                <span class="info-value text-success" id="receipt-cashback"></span>
                            </div>
                            <div class="info-row" style="border-top: 2px solid #28a745; margin-top: 8px; padding-top: 10px; background: rgba(40, 167, 69, 0.05); margin-left: -18px; margin-right: -18px; padding-left: 18px; padding-right: 18px;">
                                <span class="info-label" style="font-weight: 600;"><i class="fas fa-wallet text-success"></i> Closing:</span>
                                <span class="info-value font-weight-bold text-success" id="receipt-balance-after" style="font-size: 14px;"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column - Logs -->
                    <div class="col-lg-5">
                        <div class="receipt-section" style="min-height: 550px;">
                            <h6><i class="fas fa-history"></i> Transaction Logs</h6>
                            <div class="logs-section">
                                <div id="transaction-logs">
                                    <div class="text-center text-muted py-4">
                                        <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                                        <p style="font-size: 12px;">Loading logs...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary px-4" data-dismiss="modal" style="border-radius: 6px; font-size: 13px;">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal fade" id="editTransactionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 700px !important; height: auto !important; margin: 1.75rem auto !important;">
        <div class="modal-content" style="height: auto !important; border-radius: 16px !important;">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Transaction</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editTransactionForm">
                    <input type="hidden" id="edit-transaction-id">
                    <input type="hidden" id="edit-user-id">
                    <input type="hidden" id="edit-amount">
                    <input type="hidden" id="edit-old-status">
                    
                    <div class="form-group">
                        <label>Description</label>
                        <input type="text" class="form-control" id="edit-description">
                    </div>
                    
                    <div class="form-group">
                        <label>Status</label>
                        <select class="form-control" id="edit-status" disabled>
                            <option value="SUCCESS">Success</option>
                            <option value="PENDING">Pending</option>
                            <option value="ERROR">Error</option>
                        </select>
                    </div>

                    <div class="refund-section" id="refund-section" style="display: none;">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="refund-checkbox">
                            <label class="form-check-label" for="refund-checkbox">
                                <strong><i class="fas fa-undo"></i> Process Refund</strong> - Credit user's wallet
                            </label>
                        </div>
                    </div>

                    <div class="alert alert-info" id="status-change-warning" style="display: none;">
                        <i class="fa fa-info-circle"></i> Admin password required to change status
                    </div>
                    
                    <div class="form-group" id="password-section" style="display: none;">
                        <label>Admin Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="admin-password" placeholder="Enter password">
                    </div>
                    
                    <button type="submit" class="btn btn-warning btn-block">
                        <span id="edit-loading" style="display: none;"><i class="fa fa-spinner fa-spin"></i> Updating...</span>
                        <span id="edit-text"><i class="fas fa-save"></i> Update Transaction</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {

    // Instant Search
    $('input[name="search"]').on('keyup', function() {
        clearTimeout(window.searchTimeout);
        window.searchTimeout = setTimeout(() => {
            $('#filterForm').submit();
        }, 600);
    });

    // View Receipt - Load transaction logs
    $('.view-receipt').click(function() {
        const reference = $(this).data('reference');
        
        $('#receipt-user-name').text($(this).data('user-name'));
        $('#receipt-user-mobile').text($(this).data('user-mobile'));
        $('#receipt-description').text($(this).data('description'));
        $('#receipt-amount').text('₦' + $(this).data('amount'));
        $('#receipt-amount-detail').text('₦' + $(this).data('amount'));
        $('#receipt-reference').text(reference);
        $('#receipt-source').text($(this).data('source'));
        $('#receipt-beneficiary').text($(this).data('beneficiary'));
        $('#receipt-type').text($(this).data('type'));
        $('#receipt-balance-before').text('₦' + $(this).data('balance-before'));
        $('#receipt-balance-after').text('₦' + $(this).data('balance-after'));
        $('#receipt-charges').text('₦' + $(this).data('charges'));
        $('#receipt-cashback').text('₦' + $(this).data('cashback'));
        $('#receipt-date').text($(this).data('date'));

        let status = $(this).data('status');
        let statusBadge = $('#receipt-status-badge');
        statusBadge.text(status);
        statusBadge.removeClass('badge-success badge-warning badge-danger');
        
        if (status === 'SUCCESS') {
            statusBadge.addClass('badge-success');
        } else if (status === 'PENDING') {
            statusBadge.addClass('badge-warning');
        } else {
            statusBadge.addClass('badge-danger');
        }

        // Fetch transaction logs
        $('#transaction-logs').html('<div class="text-center text-muted py-5"><i class="fas fa-spinner fa-spin fa-3x mb-3"></i><p class="mt-3">Loading transaction logs...</p></div>');
        
        $.ajax({
            url: '/a-pay/admin/transactions/logs/' + reference,
            method: 'GET',
            success: function(response) {
                if (response.status && response.logs.length > 0) {
                    let logsHtml = '';
                    response.logs.forEach(function(log) {
                        let logClass = log.type === 'FAILED' ? 'error' : 'success';
                        let icon = log.type === 'FAILED' ? 'fa-times-circle text-danger' : 'fa-check-circle text-success';
                        let bgColor = log.type === 'FAILED' ? 'rgba(220, 53, 69, 0.05)' : 'rgba(40, 167, 69, 0.05)';
                        
                        logsHtml += `
                            <div class="log-item ${logClass}" style="background: ${bgColor};">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div style="flex: 1;">
                                        <h6 class="mb-2"><i class="fas ${icon}"></i> <strong>${log.for || 'N/A'}</strong></h6>
                                        <p class="mb-2" style="font-size: 15px; line-height: 1.6;">${log.message || 'No message available'}</p>
                                        <div class="d-flex gap-3 flex-wrap" style="font-size: 13px;">
                                            <small class="text-muted">
                                                <i class="fas fa-server"></i> <strong>From:</strong> ${log.from || 'N/A'}
                                            </small>
                                            <small class="text-muted">
                                                <i class="fas fa-clock"></i> <strong>Time:</strong> ${new Date(log.created_at).toLocaleString()}
                                            </small>
                                        </div>
                                    </div>
                                    <span class="badge badge-${log.type === 'FAILED' ? 'danger' : 'success'} ml-3" style="font-size: 12px; padding: 8px 15px;">${log.type}</span>
                                </div>
                                ${log.stack_trace ? `
                                    <details class="mt-3">
                                        <summary style="cursor: pointer; color: #666; font-weight: 600; padding: 10px; background: white; border-radius: 8px;">
                                            <i class="fas fa-code"></i> View Technical Details
                                        </summary>
                                        <pre class="mt-3 p-3 bg-white" style="font-size: 12px; border-radius: 8px; max-height: 300px; overflow-y: auto; border: 2px solid #e8f5e9; font-family: 'Courier New', monospace;">${log.stack_trace}</pre>
                                    </details>
                                ` : ''}
                            </div>
                        `;
                    });
                    $('#transaction-logs').html(logsHtml);
                } else {
                    $('#transaction-logs').html(`
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-info-circle fa-3x mb-3" style="opacity: 0.3;"></i>
                            <p style="font-size: 16px;">No logs found for this transaction</p>
                            <small>Reference: ${reference}</small>
                        </div>
                    `);
                }
            },
            error: function() {
                $('#transaction-logs').html(`
                    <div class="text-center text-danger py-5">
                        <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                        <p style="font-size: 16px;">Failed to load transaction logs</p>
                        <small>Please try again or contact support</small>
                    </div>
                `);
            }
        });

        $('#receiptModal').modal('show');
    });

    // Edit Transaction
    $('.edit-transaction').click(function() {
        let currentStatus = $(this).data('status');
        
        $('#edit-transaction-id').val($(this).data('id'));
        $('#edit-description').val($(this).data('description'));
        $('#edit-status').val(currentStatus).prop('disabled', false);
        $('#edit-old-status').val(currentStatus);
        $('#edit-user-id').val($(this).data('user-id'));
        $('#edit-amount').val($(this).data('amount'));
        $('#admin-password').val('');
        $('#refund-checkbox').prop('checked', false);
        
        $('#password-section').hide();
        $('#refund-section').hide();
        $('#status-change-warning').hide();
        
        $('#editTransactionModal').modal('show');
    });

    // Monitor status change
    $('#edit-status').on('change', function() {
        let oldStatus = $('#edit-old-status').val();
        let newStatus = $(this).val();
        
        if (oldStatus !== newStatus) {
            $('#password-section').show();
            $('#status-change-warning').show();
            
            if (newStatus === 'ERROR') {
                $('#refund-section').show();
            } else {
                $('#refund-section').hide();
                $('#refund-checkbox').prop('checked', false);
            }
        } else {
            $('#password-section').hide();
            $('#refund-section').hide();
            $('#status-change-warning').hide();
        }
    });

    // Submit Edit
    $('#editTransactionForm').submit(function(e) {
        e.preventDefault();
        
        let oldStatus = $('#edit-old-status').val();
        let newStatus = $('#edit-status').val();
        let statusChanged = oldStatus !== newStatus;
        let adminPassword = $('#admin-password').val();
        let processRefund = $('#refund-checkbox').is(':checked');
        
        if (statusChanged && !adminPassword) {
            Swal.fire('⚠️ Password Required', 'Please enter admin password to change status.', 'warning');
            return;
        }
        
        $('#edit-loading').show();
        $('#edit-text').hide();

        $.ajax({
            url: '/a-pay/admin/transactions/update',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id: $('#edit-transaction-id').val(),
                user_id: $('#edit-user-id').val(),
                description: $('#edit-description').val(),
                status: newStatus,
                old_status: oldStatus,
                amount: $('#edit-amount').val(),
                admin_password: adminPassword,
                process_refund: processRefund
            },
            success: function(response) {
                $('#editTransactionModal').modal('hide');
                Swal.fire({
                    icon: 'success',
                    title: '✅ Updated',
                    text: response.message || 'Transaction updated successfully.',
                    confirmButtonColor: '#28a745'
                }).then(() => location.reload());
            },
            error: function(xhr) {
                let errorMsg = 'Failed to update transaction.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                Swal.fire('❌ Error', errorMsg, 'error');
            },
            complete: function() {
                $('#edit-loading').hide();
                $('#edit-text').show();
            }
        });
    });
});
</script>

<script src="{{ asset('plugins/common/common.min.js') }}"></script>
<script src="{{ asset('js/custom.min.js') }}"></script>
<script src="{{ asset('js/settings.js') }}"></script>
<script src="{{ asset('js/gleek.js') }}"></script>
<script src="{{ asset('js/styleSwitcher.js') }}"></script>
</body>
</html>