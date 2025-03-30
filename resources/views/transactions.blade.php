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

@php
use App\Models\Transaction;
@endphp

<div class="container-fluid col-xl-8 col-lg-10 col-md-12 p-4">
    <!-- Balance Summary -->
    <div class="balance-summary">
        <h4>All Transactions</h4>
    </div>
    
    <!-- Filters -->
<!--     <div class="filters">
        <select>
            <option>All Categories</option>
            <option>Transfers</option>
            <option>Payments</option>
            <option>Data</option>
            <option>Airtime</option>
        </select>
        <select>
            <option>Any Status</option>
            <option>Success</option>
            <option>Pending</option>
            <option>Failed</option>
        </select>
    </div> -->
    
    <!-- Transactions -->
    <div class="transactions">
        @foreach ($transactions->groupBy(fn($t) => $t->created_at->format('F d, Y')) as $date => $group)
            <h5 class="transaction-date">{{ $date }}</h5>
            
            @foreach ($group as $transaction)
                @php
                    $isCredit = $transaction->amount > 0;
                    $statusClass = match($transaction->status) {
                        'success' => 'success',
                        'pending' => 'pending',
                        'error' => 'error',
                        default => 'default'
                    };
                @endphp
                
                <div class="transaction-card {{ $isCredit ? 'credit' : 'debit' }}" 
                    onclick="showTransactionDetails(this)"
                    data-description="{{ $transaction->description }}"
                    data-amount="₦{{ number_format(abs($transaction->amount), 2) }}"
                    data-status="{{ ucfirst($transaction->status) }}"
                    data-date="{{ $transaction->created_at->format('M d, Y H:i A') }}">
                    
                    <div class="transaction-info">
                        <p class="transaction-description">{{ $transaction->description }}</p>
                        <span class="transaction-time">{{ $transaction->created_at->format('H:i A') }}</span>
                    </div>
                    <div class="transaction-amount {{ $statusClass }}">
                        <p>{{ $isCredit ? ' ' : '-' }}₦{{ number_format(abs($transaction->amount), 2) }}</p>
                        <span class="transaction-status">{{ ucfirst($transaction->status) }}</span>
                    </div>
                </div>
            @endforeach
        @endforeach
        
        @if($transactions->isEmpty())
            <div class="no-transactions">
                <p>No transactions found</p>
            </div>
        @endif
    </div>
</div>

<!-- Transaction Details Modal -->
<div id="transactionModal" class="modal-overlay hidden">
    <div class="modal-content" id="receipt">
        <!-- Close Button (Top Right) -->
        <button class="close-btn" onclick="closeModal()">&times;</button>

        <!-- Opay Logo -->
        <div class="modal-header">
            <img src="images/APAY.png" alt="APAY Logo" class="logo">
        </div>

        <!-- Transaction Info -->
        <h4 class="transaction-title">Transaction Info</h4>
        <h2 class="amount" id="modal-amount"></h2>
        <p class="status" id="modal-status"></p>
        <p class="date" id="modal-date"></p>

        <!-- Transaction Details -->
        <div class="modal-body">
            <p><strong>Description:</strong> <span id="modal-description"></span></p>
            <p><strong>Paid With:</strong> Wallet</p>
        </div>

        <!-- Support Section -->
        <div class="footer-modal">
            <p class="support">SUPPORT</p>
            <p class="support-email">a-pay@africicl.com.ng</p>
        </div>

        <!-- Download Receipt Button -->
        <button class="download-btn" onclick="downloadReceipt()">Download Receipt</button>
    </div>
</div>



<style>
    .balance-summary { display: flex; justify-content: space-between; padding: 10px; background: white; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    .balance { font-size: 24px; font-weight: bold; color: green; }
    .filters { display: flex; justify-content: space-between; margin: 20px 0; }
    .transactions { margin-top: 20px; }
    .transaction-card { display: flex; justify-content: space-between; padding: 10px; background: white; border-radius: 5px; margin-bottom: 10px; cursor: pointer; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    .transaction-card.credit { border-left: 4px solid green; }
    .transaction-card.debit { border-left: 4px solid red; }
    .transaction-amount { text-align: right; }
    .success { color: green; }
    .pending { color: orange; }
    .error { color: red; }
    /* General Modal Styles */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .modal-content {
        background: white;
        padding: 20px;
        border-radius: 10px;
        width: 350px;
        text-align: center;
        position: relative;
        box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
    }

    /* Close Button */
    .close-btn {
        font-size: 24px;
        border: none;
        background: none;
        cursor: pointer;
        position: absolute;
        top: 10px;
        right: 10px;
    }

        /* Opay Logo */
        .modal-header {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 10px;
        }

        .logo {
            width: 60px;
        }


    /* Transaction Title */
    .transaction-title {
        font-size: 16px;
        color: #666;
        margin-top: 5px;
    }

    /* Amount Styling */
    .amount {
        font-size: 24px;
        font-weight: bold;
        color: #009966;
    }

    /* Status Styling */
    .status {
        font-size: 16px;
        font-weight: bold;
        margin-top: 5px;
    }

    /* Dynamic Status Colors */
    .status-success {
        color: #009966;
    }

    .status-pending {
        color: #FFA500;
    }

    .status-failed {
        color: #FF0000;
    }

    /* Support Section */
    .modal-footer {
        margin-top: 20px;
    }

    .support {
        font-weight: bold;
        color: #009966;
    }

    .support-email {
        font-size: 14px;
        color: #666;
        margin-top: -12px;
    }

    .hidden { display: none; }

   /* Download Button */
    .download-btn {
        background: #009966;
        color: white;
        border: none;
        padding: 10px;
        width: 100%;
        border-radius: 5px;
        font-size: 16px;
        cursor: pointer;
        margin-top: 10px;
    }

    /* Download Button Hover */
    .download-btn:hover {
        background: #007a55;
    }

</style>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<script>
    function showTransactionDetails(element) {
        document.getElementById('modal-description').textContent = element.dataset.description;
        document.getElementById('modal-amount').textContent = "NGN " + element.dataset.amount;

        let statusElement = document.getElementById('modal-status');
        statusElement.textContent = element.dataset.status.toUpperCase();

        // Set status color
        statusElement.classList.remove("status-success", "status-pending", "status-failed");
        if (element.dataset.status.toLowerCase() === "success") {
            statusElement.classList.add("status-success");
        } else if (element.dataset.status.toLowerCase() === "pending") {
            statusElement.classList.add("status-pending");
        } else {
            statusElement.classList.add("status-failed");
        }

        document.getElementById('modal-date').textContent = element.dataset.date;
        document.getElementById('transactionModal').classList.remove('hidden');
    }

    function downloadReceipt() {
        html2canvas(document.getElementById("receipt")).then(canvas => {
            let image = canvas.toDataURL("image/png");

            // Create a download link
            let downloadLink = document.createElement("a");
            downloadLink.href = image;
            downloadLink.download = "A-Pay_Receipt.png";
            downloadLink.click();
        });
    }

    function closeModal() {
        document.getElementById('transactionModal').classList.add('hidden');
    }

  $(document).ready(function () {
        $('.transaction-status').each(function () {
            var statusElement = $(this);
            var status = statusElement.text().trim().toUpperCase(); // Get text and convert to uppercase

            // Apply inline styles based on the status
            if (status === 'PENDING') {
                statusElement.css('color', '#ffc107'); // Yellow
            } else if (status === 'ERROR' || status === 'FAILED') {
                statusElement.css('color', '#dc3545'); // Red
            } else if (status === 'SUCCESS') {
                statusElement.css('color', '#28a745'); // Green
            }
        });
    });
</script>


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