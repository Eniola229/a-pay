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
        border-radius: 0px;
        overflow: hidden;
        box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.1);
    }

    .custom-table th {
        background: #28a745;
        color: white;
        text-align: center;
        font-size: 16px;
        padding: 12px;
    }

    .custom-table td {
        text-align: center;
        vertical-align: middle;
        padding: 10px;
        font-size: 14px;
    }

    .custom-table tbody tr:hover {
        background: #f8f9fa;
    }

    .badge {
        padding: 6px 12px;
        font-size: 12px;
        border-radius: 5px;
    }

    .btn-sm {
        font-size: 14px;
        padding: 6px 12px;
        border-radius: 6px;
        transition: all 0.3s ease-in-out;
    }

    .btn-sm:hover {
        transform: scale(1.05);
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

      <div class="container-fluid col-xl-8 col-lg-10 col-md-12 p-4">
<div class="container table-container">
    <div class="table-responsive">
        <table class="table table-striped table-bordered custom-table">
            <thead>
                <th>Customer Name</th>
                <th>Customer Mobile</th>
                    <th>Description</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transactions as $transaction)
                    <tr>
                        <td>{{ $transaction->user->name }}</td>
                        <td>{{ $transaction->user->mobile }}</td>
                        <td>{{ $transaction->description }}</td>
                        <td>₦{{ number_format($transaction->amount, 2) }}</td>
                        <td>
                            @if($transaction->status === 'SUCCESS')
                                <span class="badge badge-success">Success</span>
                            @elseif($transaction->status === 'PENDING')
                                <span class="badge badge-warning">Pending</span>
                            @else
                                <span class="badge badge-danger">Error</span>
                            @endif
                        </td>
                        <td>
                            <button class="btn btn-primary btn-sm view-receipt" style="background: green; border: none;" 
                                    data-id="{{ $transaction->id }}" 
                                    data-description="{{ $transaction->description }}" 
                                    data-amount="{{ number_format($transaction->amount, 2) }}" 
                                    data-status="{{ $transaction->status }}" 
                                    data-date="{{ $transaction->created_at }}">
                                View Receipt
                            </button>
                            <button class="btn btn-warning btn-sm edit-transaction" 
                                    data-id="{{ $transaction->id }}" 
                                    data-description="{{ $transaction->description }}" 
                                    data-status="{{ $transaction->status }}" 
                                    data-user-id="{{ $transaction->user->id }}"
                                    data-amount="{{ $transaction->amount }}">
                                Edit
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
        <!-- Pagination links -->
        @if ($transactions->hasPages())
            <div class="bootstrap-pagination">
                <nav>
                    <ul class="pagination">
                        {{-- Previous Page Link --}}
                        @if ($transactions->onFirstPage())
                            <li class="page-item disabled">
                                <a class="page-link"><span aria-hidden="true">&laquo;</span></a>
                            </li>
                        @else
                            <li class="page-item">
                                <a class="page-link" href="{{ $transactions->previousPageUrl() }}" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        @endif

                        {{-- Pagination Elements --}}
                        @foreach ($transactions->links()->elements[0] as $page => $url)
                            <li class="page-item {{ $transactions->currentPage() == $page ? 'active' : '' }}">
                                <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                            </li>
                        @endforeach

                        {{-- Next Page Link --}}
                        @if ($transactions->hasMorePages())
                            <li class="page-item">
                                <a class="page-link" href="{{ $transactions->nextPageUrl() }}" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        @else
                            <li class="page-item disabled">
                                <a class="page-link"><span aria-hidden="true">&raquo;</span></a>
                            </li>
                        @endif
                    </ul>
                </nav>
            </div>
        @endif
        <div class="modal fade" id="receiptModal" tabindex="-1" aria-labelledby="receiptModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body receipt-container">
                        <div class="receipt-header text-center">
                            <img src="{{ url('https://www.africicl.com.ng/a-pay/images/APAY.png') }}" alt="A-Pay Logo" class="receipt-logo">
                        </div>
                        <h6 class="text-center mt-2">Transaction Info</h6>
                        <hr>
                        <h3 class="amount text-center" id="receipt-amount"></h3>
                        <p class="status text-center" id="receipt-status"></p>
                        <p class="text-center " id="receipt-date" style="color: black; font-size: 12px;"></p>
                        <hr>
                        <table class="table table-borderless">
                            <tbody>
                                <tr>
                                    <td><strong>Sender Account:</strong></td>
                                    <td id="receipt-sender-account">{{ Auth::user()->mobile }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Description:</strong></td>
                                    <td id="receipt-description"></td>
                                </tr>
                            </tbody>
                        </table>
                        <hr>
                        <p class="text-center support-text">SUPPORT</p>
                        <p class="text-center " style="color: black;">support@a-pay.com.ng</p>
                    </div>
                </div>
            </div>
        </div>

<div class="modal fade" id="editTransactionModal" tabindex="-1" aria-labelledby="editTransactionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Transaction</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editTransactionForm">
                    <input type="hidden" id="edit-transaction-id">
                    <input type="hidden" id="edit-user-id">
                    <input type="hidden" id="edit-amount">
                    
                    <div class="form-group">
                        <label for="edit-description">Description</label>
                        <input type="text" class="form-control" id="edit-description">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit-status">Status</label>
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

            <!-- #/ container -->
        </div>

    <!--**********************************
        Scripts
    ***********************************-->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $('.view-receipt').click(function() {
            $('#receipt-description').text($(this).data('description'));
            $('#receipt-amount').text('₦' + $(this).data('amount'));
            $('#receipt-status').text($(this).data('status'));
            $('#receipt-date').text($(this).data('date'));

            // New fields
            $('#receipt-transaction-id').text($(this).data('id'));
            $('#receipt-customer-name').text($(this).data('customer-name'));
            $('#receipt-customer-mobile').text($(this).data('customer-mobile'));
            $('#receipt-payment-method').text($(this).data('payment-method'));

            var status = $(this).data('status');
            var statusElement = $('#receipt-status');

            if (status === 'PENDING') {
                statusElement.css('color', '#ffc107'); // Yellow
            } else if (status === 'ERROR') {
                statusElement.css('color', '#dc3545'); // Red
            } else if (status === 'SUCCESS') {
                statusElement.css('color', '#28a745'); // Green
            }

            $('#receiptModal').modal('show');
        });

        $(document).ready(function() {
            $('.edit-transaction').click(function() {
                let transactionId = $(this).data('id');
                let description = $(this).data('description');
                let status = $(this).data('status');
                let userId = $(this).data('user-id');
                let amount = $(this).data('amount');

                $('#edit-transaction-id').val(transactionId);
                $('#edit-description').val(description);
                $('#edit-status').val(status);
                $('#edit-user-id').val(userId);
                $('#edit-amount').val(amount);

                $('#editTransactionModal').modal('show');
            });

            $('#editTransactionForm').submit(function(e) {
                e.preventDefault();

                let transactionId = $('#edit-transaction-id').val();
                let userId = $('#edit-user-id').val();
                let description = $('#edit-description').val();
                let status = $('#edit-status').val();
                let amount = $('#edit-amount').val();

                $('#edit-loading').show();
                $('#edit-text').hide();

                $.ajax({
                    url: '/a-pay/admin/transactions/update',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        id: transactionId,
                        user_id: userId,
                        description: description,
                        status: status,
                        amount: amount
                    },
                    success: function(response) {
                        $('#editTransactionModal').modal('hide');
                        location.reload(); // Reload page to show updated status
                    },
                    error: function() {
                        alert('Failed to update transaction.');
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