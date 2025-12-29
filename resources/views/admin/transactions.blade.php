@include('components.header')
<meta name="csrf-token" content="{{ csrf_token() }}">
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<style>
    .receipt-container {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .badge-lg {
        font-size: 16px;
        padding: 8px 20px;
    }

    .custom-table {
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.1);
    }

    .custom-table th {
        background: #28a745;
        color: white;
        text-align: center;
        padding: 12px;
    }

    .custom-table td {
        text-align: center;
        padding: 10px;
        font-size: 14px;
    }

    .custom-table tbody tr:hover {
        background: #f8f9fa;
    }

    .btn-sm {
        font-size: 13px;
        padding: 5px 10px;
        border-radius: 6px;
        transition: all 0.3s;
    }

    .btn-sm:hover { transform: scale(1.05); }

    .refund-section {
        background: #fff3cd;
        padding: 15px;
        border-radius: 8px;
        border: 2px solid #ffc107;
        margin: 15px 0;
    }

    .refund-checkbox {
        transform: scale(1.3);
        margin-right: 10px;
    }
</style>

<div id="main-wrapper">
    @include('components.nav-header')
    @include('components.main-header')
    @include('components.admin-sidenav')

    <div class="container-fluid col-xl-8 col-lg-10 col-md-12 p-4">
        <div class="container table-container">

    <!--FILTERS -->
    <div class="mb-3 p-3 bg-light rounded shadow-sm">
        <form method="GET" action="{{ route('admin.transactions') }}" id="filterForm" class="row g-2">
            <div class="col-md-2"><input type="number" name="year" class="form-control" placeholder="Year" value="{{ request('year') }}"></div>
            <div class="col-md-2"><input type="number" name="month" class="form-control" placeholder="Month" value="{{ request('month') }}"></div>
            <div class="col-md-2"><input type="number" name="day" class="form-control" placeholder="Day" value="{{ request('day') }}"></div>
            <div class="col-md-2"><input type="date" name="from" class="form-control" value="{{ request('from') }}"></div>
            <div class="col-md-2"><input type="date" name="to" class="form-control" value="{{ request('to') }}"></div>
            <div class="col-md-2"><input type="text" name="search" class="form-control" placeholder="Search..." value="{{ request('search') }}"></div>

            <div class="col-md-2 mt-2">
                <button type="submit" class="btn btn-success w-100"><i class="fa fa-filter"></i> Filter</button>
            </div>
            <div class="col-md-2 mt-2">
                <a href="{{ route('admin.transactions') }}" class="btn btn-secondary w-100"><i class="fa fa-undo"></i> Reset</a>
            </div>
        </form>

        <!--SUMMARY SECTION -->
        @if(isset($summary))
            <div class="row mt-4 text-center">
                <div class="col-md-3">
                    <div class="card shadow-sm border-left-primary p-3">
                        <h6>Total Transactions</h6>
                        <h4 class="text-dark">₦{{ number_format($summary['total'], 2) }}</h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm border-left-success p-3">
                        <h6>Successful</h6>
                        <h4 class="text-success">₦{{ number_format($summary['success'], 2) }}</h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm border-left-danger p-3">
                        <h6>Failed</h6>
                        <h4 class="text-danger">₦{{ number_format($summary['failed'], 2) }}</h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm border-left-info p-3">
                        <h6>Wallet Top-up</h6>
                        <h4 class="text-info">₦{{ number_format($summary['wallet_topup'], 2) }}</h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm border-left-success p-3">
                        <h6>Transfer to A-Pay</h6>
                        <h4 class="text-info">₦{{ number_format($summary['to_apay'], 2) }}</h4>
                    </div>
                </div>
                <div class="col-md-3 mt-3">
                    <div class="card shadow-sm border-left-warning p-3">
                        <h6>Airtime Purchases</h6>
                        <h4 class="text-warning">₦{{ number_format($summary['airtime'], 2) }}</h4>
                    </div>
                </div>
                <div class="col-md-3 mt-3">
                    <div class="card shadow-sm border-left-success p-3">
                        <h6>Data</h6>
                        <h4 class="text-success">₦{{ number_format($summary['data'], 2) }}</h4>
                    </div>
                </div>
                <div class="col-md-3 mt-3">
                    <div class="card shadow-sm border-left-secondary p-3">
                        <h6>Electricity</h6>
                        <h4 class="text-secondary">₦{{ number_format($summary['electricity'], 2) }}</h4>
                    </div>
                </div>
                <div class="col-md-3 mt-3">
                    <div class="card shadow-sm border-left-dark p-3">
                        <h6>Betting</h6>
                        <h4 class="text-dark">₦{{ number_format($summary['betting'], 2) }}</h4>
                    </div>
                </div>
            </div>
        @endif
    </div>

        <!--TRANSACTION TABLE -->
        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-striped table-bordered custom-table">
                    <thead>
                        <tr>
                            <th>Customer Name</th>
                            <th>Customer Mobile</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $transaction)
                            <tr>
                                <td>{{ $transaction->user->name ?? 'N/A' }}</td>
                                <td>{{ $transaction->user->mobile ?? 'N/A' }}</td>
                                <td>{{ $transaction->description ?? 'N/A' }}</td>
                                <td>₦{{ number_format($transaction->amount, 2) ?? 'N/A' }}</td>
                                <td>
                                    @if($transaction->status === 'SUCCESS')
                                        <span class="badge badge-success">Success</span>
                                    @elseif($transaction->status === 'PENDING')
                                        <span class="badge badge-warning">Pending</span>
                                    @else
                                        <span class="badge badge-danger">Error</span>
                                    @endif
                                </td>
                                <td>{{ $transaction->created_at->format('Y-m-d H:i') }}</td>
                                <td>
                                    <button class="btn btn-success btn-sm view-receipt"
                                        data-id="{{ $transaction->id }}"
                                        data-user-name="{{ $transaction->user->name ?? 'N/A' }}"
                                        data-user-mobile="{{ $transaction->user->mobile ?? 'N/A' }}"
                                        data-description="{{ $transaction->description }}"
                                        data-amount="{{ number_format($transaction->amount, 2) }}"
                                        data-status="{{ $transaction->status }}"
                                        data-reference="{{ $transaction->reference ?? 'N/A' }}"
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
                                    @else
                                        <button class="btn btn-secondary btn-sm" disabled title="Cannot edit successful transactions">
                                            <i class="fa fa-lock"></i> Locked
                                        </button>
                                    @endif
                                    <a href="{{ url('admin/users/' . ($transaction->user->id ?? '#')) }}">
                                        <button class="btn btn-primary btn-sm {{ !isset($transaction->user) ? 'disabled' : '' }}" 
                                                {{ !isset($transaction->user) ? 'disabled' : '' }}>
                                            <i class="fa fa-user"></i> Profile
                                        </button>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted">No transactions found</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!--PAGINATION -->
        <div class="d-flex justify-content-center mt-3">
            {{ $transactions->appends(request()->query())->links('pagination::bootstrap-4') }}
        </div>
    </div>
</div>

<!-- ADMIN TRANSACTION DETAILS MODAL -->
<div class="modal fade" id="receiptModal" tabindex="-1" aria-labelledby="receiptModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fa fa-file-invoice"></i> Transaction Details</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-12 text-center">
                        <h2 class="text-success mb-2" id="receipt-amount"></h2>
                        <span class="badge badge-lg" id="receipt-status-badge" style="font-size: 16px; padding: 8px 20px;"></span>
                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-muted mb-3"><i class="fa fa-user"></i> Customer Information</h6>
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td class="font-weight-bold">Name:</td>
                                <td id="receipt-user-name"></td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold">Mobile:</td>
                                <td id="receipt-user-mobile"></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-3"><i class="fa fa-info-circle"></i> Transaction Info</h6>
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td class="font-weight-bold">Type:</td>
                                <td id="receipt-type"></td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold">Reference:</td>
                                <td id="receipt-reference" style="font-size: 12px;"></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-12">
                        <h6 class="text-muted mb-3"><i class="fa fa-exchange-alt"></i> Transaction Details</h6>
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td class="font-weight-bold" style="width: 200px;">Description:</td>
                                <td id="receipt-description"></td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold">Beneficiary:</td>
                                <td id="receipt-beneficiary"></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-12">
                        <h6 class="text-muted mb-3"><i class="fa fa-wallet"></i> Financial Breakdown</h6>
                        <table class="table table-sm table-bordered">
                            <tr>
                                <td class="font-weight-bold bg-light">Balance Before:</td>
                                <td id="receipt-balance-before"></td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold bg-light">Charges:</td>
                                <td class="text-danger" id="receipt-charges"></td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold bg-light">Cashback:</td>
                                <td class="text-success" id="receipt-cashback"></td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold bg-light">Balance After:</td>
                                <td class="font-weight-bold" id="receipt-balance-after"></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <p class="text-muted text-center mb-0"><i class="fa fa-clock"></i> <small id="receipt-date"></small></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!--EDIT MODAL WITH PASSWORD & REFUND -->
<div class="modal fade" id="editTransactionModal" tabindex="-1" aria-labelledby="editTransactionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Transaction</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
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
                            <input class="form-check-input refund-checkbox" type="checkbox" id="refund-checkbox">
                            <label class="form-check-label" for="refund-checkbox">
                                <strong>Process Refund</strong> - This will credit the user's wallet
                            </label>
                        </div>
                    </div>

                    <div class="alert alert-info" id="status-change-warning" style="display: none;">
                        <i class="fa fa-info-circle"></i> <strong>Note:</strong> You need to enter admin password to change status.
                    </div>
                    
                    <div class="form-group" id="password-section" style="display: none;">
                        <label>Admin Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="admin-password" placeholder="Enter admin password">
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">
                        <span id="edit-loading" style="display: none;"><i class="fa fa-spinner fa-spin"></i> Updating...</span>
                        <span id="edit-text">Update Transaction</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
</div>

<!-- JS LOGIC -->
<script>
$(document).ready(function() {

    // Instant Search
    $('input[name="search"]').on('keyup', function() {
        clearTimeout(window.searchTimeout);
        window.searchTimeout = setTimeout(() => {
            $('#filterForm').submit();
        }, 600);
    });

    // View Receipt - Enhanced with all data in admin dashboard style
    $('.view-receipt').click(function() {
        $('#receipt-user-name').text($(this).data('user-name'));
        $('#receipt-user-mobile').text($(this).data('user-mobile'));
        $('#receipt-description').text($(this).data('description'));
        $('#receipt-amount').text('₦' + $(this).data('amount'));
        $('#receipt-reference').text($(this).data('reference'));
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

        $('#receiptModal').modal('show');
    });

    // Edit Transaction - Show password field when status changes
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
            
            // Show refund option only if changing to ERROR
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

    // Submit Edit with Password Verification
    $('#editTransactionForm').submit(function(e) {
        e.preventDefault();
        
        let oldStatus = $('#edit-old-status').val();
        let newStatus = $('#edit-status').val();
        let statusChanged = oldStatus !== newStatus;
        let adminPassword = $('#admin-password').val();
        let processRefund = $('#refund-checkbox').is(':checked');
        
        // Validate password if status changed
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
<script src="{{ asset('plugins/chart.js/Chart.bundle.min.js') }}"></script>
<script src="{{ asset('plugins/circle-progress/circle-progress.min.js') }}"></script>
<script src="{{ asset('plugins/d3v3/index.js') }}"></script>
<script src="{{ asset('plugins/topojson/topojson.min.js') }}"></script>
<script src="{{ asset('plugins/datamaps/datamaps.world.min.js') }}"></script>
<script src="{{ asset('plugins/raphael/raphael.min.js') }}"></script>
<script src="{{ asset('plugins/morris/morris.min.js') }}"></script>
<script src="{{ asset('plugins/moment/moment.min.js') }}"></script>
<script src="{{ asset('plugins/pg-calendar/js/pignose.calendar.min.js') }}"></script>
<script src="{{ asset('plugins/chartist/js/chartist.min.js') }}"></script>
<script src="{{ asset('plugins/chartist-plugin-tooltips/js/chartist-plugin-tooltip.min.js') }}"></script>
<script src="{{ asset('js/dashboard/dashboard-1.js') }}"></script>