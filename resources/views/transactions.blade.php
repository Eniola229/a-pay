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
    max-width: 400px;
    margin: auto;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

.receipt-header {
    text-align: center;
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
    text-align: center;
}

.status {
    font-size: 20px;
    font-weight: bold;
    text-align: center;
}

.status.success {
    color: #28a745; /* Green for success */
}

.status.pending {
    color: #ffc107; /* Yellow for pending */
}

.status.failed {
    color: #dc3545; /* Red for failed */
}

.transaction-info {
    text-align: center;
    margin-top: 10px;
    font-weight: bold;
}

.receipt-table {
    width: 100%;
}

.receipt-table td {
    padding: 5px 0;
}

.support-text {
    font-weight: bold;
    text-align: center;
    color: #000;
}

.support-email {
    text-align: center;
    color: black;
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
        @include('components.nk-sidebar')
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
                <tr>
                    <th>Description</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transactions as $transaction)
                    <tr>
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



        <!-- Receipt Modal -->
<!--         <div class="modal fade" id="receiptModal" tabindex="-1" aria-labelledby="receiptModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="receiptModalLabel">Transaction Receipt</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="receipt border p-4 rounded shadow-sm bg-white">
                            <h4 class="text-center mb-3">AfricPay Transaction Receipt</h4>
                            <hr>
                            <p><strong>Date:</strong> <span id="receipt-date"></span></p>
                            <p><strong>Description:</strong> <span id="receipt-description"></span></p>
                            <p><strong>Amount:</strong> <span id="receipt-amount"></span></p>
                            <p><strong>Status:</strong> <span id="receipt-status"></span></p>
                            <hr>
                            <p class="text-center">Thank you for using AfricPay!</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
 -->
<div class="modal fade" id="receiptModal" tabindex="-1" aria-labelledby="receiptModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body receipt-container">
                <!-- Header -->
                <div class="receipt-header">
                    <img src="{{ url('https://www.africicl.com.ng/a-pay/images/APAY.png') }}" alt="A-Pay Logo" class="receipt-logo">
                </div>

                <!-- Transaction Info -->
                <h6 class="transaction-info">Transaction Info</h6>
                <hr>

                <!-- Amount and Status -->
                <h3 class="amount" id="receipt-amount"></h3>
                <p class="status" id="receipt-status"></p>
                <p id="receipt-date" class="text-center" style="color: black; font-size: 12px;"></p>

                <hr>

                <!-- Transaction Details Table -->
                <table class="table table-borderless receipt-table">
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

                <!-- Support Information -->
                <p class="support-text">SUPPORT</p>
                <p class="support-email">a-pay@africicl.com.ng</p>
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
    @include('components.contact-us')
    <script>
    $(document).ready(function() {
        $('.view-receipt').click(function() {
            $('#receipt-description').text($(this).data('description'));
            $('#receipt-amount').text('₦' + $(this).data('amount'));
            $('#receipt-status').text($(this).data('status'));
            $('#receipt-date').text($(this).data('date'));

            var status = $(this).data('status'); // Keep it uppercase as backend sends it
            var statusElement = $('#receipt-status');

            // Apply inline styles based on the status
            if (status === 'PENDING') {
                statusElement.css('color', '#ffc107'); // Yellow
            } else if (status === 'ERROR') {
                statusElement.css('color', '#dc3545'); // Red
            } else if (status === 'SUCCESS') {
                statusElement.css('color', '#28a745'); // Green
            }

            $('#receiptModal').modal('show');
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