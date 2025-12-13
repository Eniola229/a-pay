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
        font-family: 'Arial', sans-serif;
        background: #fff;
        padding: 20px;
        border-radius: 10px;
        max-width: 380px;
        margin: auto;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    .receipt-logo { width: 80px; border-radius: 20px; }
    .amount { font-size: 24px; font-weight: bold; color: #28a745; }
    .status { font-size: 20px; font-weight: bold; }
    .status.pending { color: #ffc107; }
    .status.failed { color: #dc3545; }

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
                        <h6>Trasfer to A-Pay</h6>
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
                                        data-description="{{ $transaction->description }}"
                                        data-amount="{{ number_format($transaction->amount, 2) }}"
                                        data-status="{{ $transaction->status }}"
                                        data-date="{{ $transaction->created_at }}">
                                        <i class="fa fa-receipt"></i> View
                                    </button>
                                    <button class="btn btn-warning btn-sm edit-transaction"
                                        data-id="{{ $transaction->id }}"
                                        data-description="{{ $transaction->description }}"
                                        data-status="{{ $transaction->status }}"
                                        data-user-id="{{ $transaction->user->id ?? 'N/A' }}"
                                        data-amount="{{ $transaction->amount }}">
                                        <i class="fa fa-edit"></i> Edit
                                    </button>
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

<!-- RECEIPT MODAL -->
<div class="modal fade" id="receiptModal" tabindex="-1" aria-labelledby="receiptModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body receipt-container">
                <div class="receipt-header text-center">
                    <img src="https://www.africicl.com.ng/a-pay/images/APAY.png" alt="A-Pay Logo" class="receipt-logo">
                </div>
                <h6 class="text-center mt-2">Transaction Info</h6>
                <hr>
                <h3 class="amount text-center" id="receipt-amount"></h3>
                <p class="status text-center" id="receipt-status"></p>
                <p class="text-center" id="receipt-date" style="color: black; font-size: 12px;"></p>
                <hr>
                <table class="table table-borderless">
                    <tbody>
                        <tr>
                            <td><strong>Sender Account:</strong></td>
                            <td>{{ Auth::user()->mobile }}</td>
                        </tr>
                        <tr>
                            <td><strong>Description:</strong></td>
                            <td id="receipt-description"></td>
                        </tr>
                    </tbody>
                </table>
                <hr>
                <p class="text-center support-text">SUPPORT</p>
                <p class="text-center" style="color: black;">support@a-pay.com.ng</p>
            </div>
        </div>
    </div>
</div>

<!--EDIT MODAL -->
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
                    
                    <div class="form-group">
                        <label>Description</label>
                        <input type="text" class="form-control" id="edit-description">
                    </div>
                    
                    <div class="form-group">
                        <label>Status</label>
                        <select class="form-control" id="edit-status">
                            <option value="SUCCESS">Success</option>
                            <option value="PENDING">Pending</option>
                            <option value="ERROR">Error</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">
                        <span id="edit-loading" style="display: none;">Updating...</span>
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

    // 🔍 Instant Search (optional)
    $('input[name="search"]').on('keyup', function() {
        clearTimeout(window.searchTimeout);
        window.searchTimeout = setTimeout(() => {
            $('#filterForm').submit();
        }, 600);
    });

    // View Receipt
    $('.view-receipt').click(function() {
        $('#receipt-description').text($(this).data('description'));
        $('#receipt-amount').text('₦' + $(this).data('amount'));
        $('#receipt-status').text($(this).data('status'));
        $('#receipt-date').text($(this).data('date'));

        let status = $(this).data('status');
        let color = (status === 'PENDING') ? '#ffc107' :
                    (status === 'ERROR') ? '#dc3545' : '#28a745';
        $('#receipt-status').css('color', color);
        $('#receiptModal').modal('show');
    });

    // Edit Transaction
    $('.edit-transaction').click(function() {
        $('#edit-transaction-id').val($(this).data('id'));
        $('#edit-description').val($(this).data('description'));
        $('#edit-status').val($(this).data('status'));
        $('#edit-user-id').val($(this).data('user-id'));
        $('#edit-amount').val($(this).data('amount'));
        $('#editTransactionModal').modal('show');
    });

    //Submit Edit
    $('#editTransactionForm').submit(function(e) {
        e.preventDefault();
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
                status: $('#edit-status').val(),
                amount: $('#edit-amount').val()
            },
            success: function() {
                $('#editTransactionModal').modal('hide');
                Swal.fire('✅ Updated', 'Transaction updated successfully.', 'success')
                    .then(() => location.reload());
            },
            error: function() {
                Swal.fire('❌ Error', 'Failed to update transaction.', 'error');
            },
            complete: function() {
                $('#edit-loading').hide();
                $('#edit-text').show();
            }
        });
    });
});
</script>
<script src="{{ asset('plugins/common/common.min.js') }}"></script> <script src="{{ asset('js/custom.min.js') }}"></script> <script src="{{ asset('js/settings.js') }}"></script> <script src="{{ asset('js/gleek.js') }}"></script> <script src="{{ asset('js/styleSwitcher.js') }}"></script> <!-- Chartjs --> <script src="{{ asset('plugins/chart.js/Chart.bundle.min.js') }}"></script> <!-- Circle progress --> <script src="{{ asset('plugins/circle-progress/circle-progress.min.js') }}"></script> <!-- Datamap --> <script src="{{ asset('plugins/d3v3/index.js') }}"></script> <script src="{{ asset('plugins/topojson/topojson.min.js') }}"></script> <script src="{{ asset('plugins/datamaps/datamaps.world.min.js') }}"></script> <!-- Morrisjs --> <script src="{{ asset('plugins/raphael/raphael.min.js') }}"></script> <script src="{{ asset('plugins/morris/morris.min.js') }}"></script> <!-- Pignose Calender --> <script src="{{ asset('plugins/moment/moment.min.js') }}"></script> <script src="{{ asset('plugins/pg-calendar/js/pignose.calendar.min.js') }}"></script> <!-- ChartistJS --> <script src="{{ asset('plugins/chartist/js/chartist.min.js') }}"></script> <script src="{{ asset('plugins/chartist-plugin-tooltips/js/chartist-plugin-tooltip.min.js') }}"></script> <!-- Dashboard Script --> <script src="{{ asset('js/dashboard/dashboard-1.js') }}"></script>