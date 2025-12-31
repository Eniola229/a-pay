@include('components.header')
<meta name="csrf-token" content="{{ csrf_token() }}">
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<!-- html2canvas for receipt download -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<style>
    /* Main Container */
    .transactions-container {
        font-family: 'Arial', sans-serif;
        background: #f5f5f5;
        min-height: 100vh;
        padding: 20px;
    }

    /* Balance Summary */
    .balance-summary {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
    }

    .balance-summary h4 {
        margin: 0;
        color: #333;
        font-size: 20px;
    }

    /* Transactions List */
    .transactions {
        background: white;
        border-radius: 10px;
        padding: 15px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .transaction-date {
        font-size: 14px;
        font-weight: bold;
        color: #666;
        margin: 15px 0 10px;
        padding-bottom: 5px;
        border-bottom: 1px solid #eee;
    }

    /* Transaction Card */
    .transaction-card {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px;
        background: white;
        border-radius: 8px;
        margin-bottom: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
        border-left: 4px solid #ccc;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    .transaction-card:hover {
        box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15);
        transform: translateY(-2px);
    }

    .transaction-card.credit {
        border-left-color: #28a745;
    }

    .transaction-card.debit {
        border-left-color: #dc3545;
    }

    .transaction-info {
        flex: 1;
    }

    .transaction-description {
        margin: 0;
        font-size: 15px;
        font-weight: 500;
        color: #333;
    }

    .transaction-time {
        font-size: 12px;
        color: #999;
    }

    .transaction-amount-container {
        text-align: right;
    }

    .transaction-amount-text {
        margin: 0;
        font-size: 16px;
        font-weight: bold;
    }

    .transaction-amount-text.credit {
        color: #28a745;
    }

    .transaction-amount-text.debit {
        color: #dc3545;
    }

    .transaction-status {
        font-size: 12px;
        padding: 2px 8px;
        border-radius: 12px;
        display: inline-block;
        margin-top: 4px;
    }

    .transaction-status.success {
        background: #d4edda;
        color: #28a745;
    }

    .transaction-status.pending {
        background: #fff3cd;
        color: #ffc107;
    }

    .transaction-status.error {
        background: #f8d7da;
        color: #dc3545;
    }

    /* Loading Indicator */
    .loading-indicator {
        text-align: center;
        padding: 20px;
        display: none;
    }

    .loading-indicator.active {
        display: block;
    }

    .spinner {
        border: 3px solid #f3f3f3;
        border-top: 3px solid #28a745;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        animation: spin 1s linear infinite;
        margin: 0 auto;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* No Transactions */
    .no-transactions {
        text-align: center;
        padding: 40px;
        color: #999;
    }

    .no-transactions i {
        font-size: 48px;
        margin-bottom: 15px;
        color: #ddd;
    }

    /* Modal Styles */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    }

    .modal-overlay.active {
        display: flex;
    }

    .modal-content {
        background: white;
        padding: 30px;
        border-radius: 15px;
        width: 90%;
        max-width: 400px;
        text-align: center;
        position: relative;
        box-shadow: 0px 10px 30px rgba(0, 0, 0, 0.3);
        max-height: 90vh;
        overflow-y: auto;
    }

    /* Close Button */
    .close-btn {
        position: absolute;
        top: 15px;
        right: 15px;
        font-size: 28px;
        border: none;
        background: none;
        cursor: pointer;
        color: #999;
        transition: color 0.3s;
    }

    .close-btn:hover {
        color: #333;
    }

    /* Modal Header */
    .modal-header {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-bottom: 20px;
    }

    .logo {
        width: 80px;
        border-radius: 10px;
    }

    /* Transaction Title */
    .transaction-title {
        font-size: 14px;
        color: #999;
        margin-bottom: 10px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    /* Amount Display */
    .modal-amount {
        font-size: 32px;
        font-weight: bold;
        color: #28a745;
        margin: 10px 0;
    }

    /* Status Display */
    .modal-status {
        font-size: 18px;
        font-weight: bold;
        margin: 10px 0;
        padding: 5px 15px;
        border-radius: 20px;
        display: inline-block;
    }

    .modal-status.status-success {
        background: #d4edda;
        color: #28a745;
    }

    .modal-status.status-pending {
        background: #fff3cd;
        color: #ffc107;
    }

    .modal-status.status-error {
        background: #f8d7da;
        color: #dc3545;
    }

    .modal-date {
        font-size: 14px;
        color: #666;
        margin-bottom: 20px;
    }

    /* Modal Body */
    .modal-body {
        border-top: 1px solid #eee;
        border-bottom: 1px solid #eee;
        padding: 20px 0;
        margin: 20px 0;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        margin: 12px 0;
        font-size: 14px;
    }

    .info-row .label {
        font-weight: 600;
        color: #666;
        text-align: left;
    }

    .info-row .value {
        font-weight: 500;
        color: #333;
        text-align: right;
        word-break: break-word;
        max-width: 60%;
    }

    /* Footer */
    .footer-modal {
        margin-top: 20px;
        padding-top: 15px;
        border-top: 1px solid #eee;
    }

    .support {
        font-weight: bold;
        color: #28a745;
        margin-bottom: 5px;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .support-email {
        font-size: 14px;
        color: #666;
        margin: 0;
    }

    /* Download Button */
    .download-btn {
        background: #28a745;
        color: white;
        border: none;
        padding: 12px 20px;
        width: 100%;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        margin-top: 15px;
        transition: all 0.3s ease;
    }

    .download-btn:hover {
        background: #218838;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
    }

    .download-btn:disabled {
        background: #6c757d;
        cursor: not-allowed;
        transform: none;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .transactions-container {
            padding: 10px;
        }

        .transaction-card {
            padding: 12px;
        }

        .transaction-description {
            font-size: 14px;
        }

        .transaction-amount-text {
            font-size: 14px;
        }

        .modal-content {
            width: 95%;
            padding: 20px;
        }

        .modal-amount {
            font-size: 28px;
        }
    }
</style>

<!--**********************************
    Main wrapper start
***********************************-->
<div id="main-wrapper">
    @include('components.nav-header')
    @include('components.main-header')
    @include('components.nk-sidebar')

    <!--**********************************
        Content body start
    ***********************************-->
    <div class="content-body">
        <div class="container-fluid transactions-container">
            <div class="row">
                <div class="col-xl-8 col-lg-10 col-md-12 mx-auto">
                    <!-- Balance Summary -->
                    <div class="balance-summary">
                        <h4><i class="fas fa-history"></i> All Transactions</h4>
                    </div>

                    <!-- Transactions List -->
                    <div class="transactions" id="transactions-list">
                        <!-- Transactions will be loaded here -->
                    </div>

                    <!-- Loading Indicator -->
                    <div class="loading-indicator" id="loading-indicator">
                        <div class="spinner"></div>
                        <p style="margin-top: 10px; color: #666;">Loading more transactions...</p>
                    </div>

                    <!-- No More Transactions -->
                    <div id="no-more-transactions" style="display: none; text-align: center; padding: 20px; color: #999;">
                        <p>No more transactions to load</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--**********************************
        Content body end
    ***********************************-->
</div>

<!-- Transaction Details Modal -->
<div id="transactionModal" class="modal-overlay">
    <div class="modal-content" id="receipt">
        <!-- Close Button -->
        <button class="close-btn" onclick="closeModal()">&times;</button>

        <!-- Logo -->
        <div class="modal-header">
            <img src="images/APAY.png" alt="APAY Logo" class="logo">
        </div>

        <!-- Transaction Info -->
        <h4 class="transaction-title">Transaction Receipt</h4>
        <h2 class="modal-amount" id="modal-amount"></h2>
        <p class="modal-status" id="modal-status"></p>
        <p class="modal-date" id="modal-date"></p>

        <!-- Transaction Details -->
        <div class="modal-body">
            <div class="info-row">
                <span class="label">Description:</span>
                <span class="value" id="modal-description"></span>
            </div>
            <div class="info-row">
                <span class="label">Reference:</span>
                <span class="value" id="modal-reference"></span>
            </div>
            <div class="info-row">
                <span class="label">Beneficiary:</span>
                <span class="value" id="modal-beneficiary"></span>
            </div>
            <div class="info-row">
                <span class="label">Type:</span>
                <span class="value" id="modal-type"></span>
            </div>
            <div class="info-row">
                <span class="label">Cash Back:</span>
                <span class="value">₦<span id="modal-cash_back"></span></span>
            </div>
            <div class="info-row">
                <span class="label">Transaction Fee:</span>
                <span class="value">₦<span id="modal-charges"></span></span>
            </div>
        </div>

        <!-- Support Section -->
        <div class="footer-modal">
            <p class="support">Support</p>
            <p class="support-email">a-pay@africicl.com.ng</p>
        </div>

        <!-- Download Receipt Button -->
        <button class="download-btn" id="download-btn" onclick="downloadReceipt()">
            <i class="fas fa-download"></i> Download Receipt
        </button>
    </div>
</div>

<script>
    // Transaction data from Laravel
    let allTransactions = @json($transactions);
    let currentPage = 0;
    const itemsPerPage = 10;
    let isLoading = false;

    // Initialize on page load
    $(document).ready(function() {
        loadMoreTransactions();
        
        // Infinite scroll
        $(window).scroll(function() {
            if ($(window).scrollTop() + $(window).height() > $(document).height() - 300) {
                loadMoreTransactions();
            }
        });
    });

    function loadMoreTransactions() {
        if (isLoading) return;
        
        const start = currentPage * itemsPerPage;
        const end = start + itemsPerPage;
        const transactionsToShow = allTransactions.slice(start, end);
        
        if (transactionsToShow.length === 0) {
            if (currentPage === 0) {
                showNoTransactions();
            } else {
                $('#no-more-transactions').show();
            }
            return;
        }
        
        isLoading = true;
        $('#loading-indicator').addClass('active');
        
        // Simulate loading delay for better UX
        setTimeout(() => {
            renderTransactions(transactionsToShow);
            currentPage++;
            isLoading = false;
            $('#loading-indicator').removeClass('active');
            
            // Check if we've loaded all transactions
            if (end >= allTransactions.length) {
                $('#no-more-transactions').show();
            }
        }, 500);
    }

    function renderTransactions(transactions) {
        // Group by date
        const grouped = {};
        
        transactions.forEach(transaction => {
            const date = new Date(transaction.created_at).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            
            if (!grouped[date]) {
                grouped[date] = [];
            }
            grouped[date].push(transaction);
        });
        
        // Render grouped transactions
        Object.keys(grouped).forEach(date => {
            // Check if date header already exists
            if ($(`[data-date-header="${date}"]`).length === 0) {
                $('#transactions-list').append(`<h5 class="transaction-date" data-date-header="${date}">${date}</h5>`);
            }
            
            grouped[date].forEach(transaction => {
                const isCredit = transaction.type === 'CREDIT';
                const statusClass = transaction.status.toLowerCase();
                const time = new Date(transaction.created_at).toLocaleTimeString('en-US', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                const card = `
                    <div class="transaction-card ${isCredit ? 'credit' : 'debit'}" 
                        onclick='showTransactionDetails(${JSON.stringify(transaction)})'>
                        <div class="transaction-info">
                            <p class="transaction-description">${transaction.description || 'N/A'}</p>
                            <span class="transaction-time">${time}</span>
                        </div>
                        <div class="transaction-amount-container">
                            <p class="transaction-amount-text ${isCredit ? 'credit' : 'debit'}">
                                ${isCredit ? '+' : '-'}₦${formatNumber(Math.abs(transaction.amount))}
                            </p>
                            <span class="transaction-status ${statusClass}">${transaction.status}</span>
                        </div>
                    </div>
                `;
                
                $('#transactions-list').append(card);
            });
        });
    }

    function showNoTransactions() {
        $('#transactions-list').html(`
            <div class="no-transactions">
                <i class="fas fa-receipt"></i>
                <p>No transactions found</p>
            </div>
        `);
    }

    function showTransactionDetails(transaction) {
        // Format amount
        const isCredit = transaction.type === 'CREDIT';
        const amountPrefix = isCredit ? '+' : '-';
        $('#modal-amount').text(`${amountPrefix}₦${formatNumber(Math.abs(transaction.amount))}`);
        
        // Set status with styling
        const statusElement = $('#modal-status');
        statusElement.text(transaction.status.toUpperCase());
        statusElement.removeClass('status-success status-pending status-error');
        statusElement.addClass(`status-${transaction.status.toLowerCase()}`);
        
        // Set date
        const date = new Date(transaction.created_at).toLocaleString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
        $('#modal-date').text(date);
        
        // Set details
        $('#modal-description').text(transaction.description || 'N/A');
        $('#modal-reference').text(transaction.reference || 'N/A');
        $('#modal-beneficiary').text(transaction.beneficiary || 'N/A');
        $('#modal-type').text(transaction.type || 'N/A');
        $('#modal-cash_back').text(formatNumber(transaction.cash_back || 0));
        $('#modal-charges').text(formatNumber(transaction.charges || 0));
        
        // Show modal
        $('#transactionModal').addClass('active');
    }

    function closeModal() {
        $('#transactionModal').removeClass('active');
    }

    function downloadReceipt() {
        const button = $('#download-btn');
        const originalText = button.html();
        
        button.html('<i class="fas fa-spinner fa-spin"></i> Downloading...').prop('disabled', true);
        
        // Get the receipt element
        const receiptElement = document.getElementById("receipt");
        
        // Store original styles
        const originalOverflow = receiptElement.style.overflow;
        const originalMaxHeight = receiptElement.style.maxHeight;
        
        // Temporarily remove scroll constraints for full capture
        receiptElement.style.overflow = 'visible';
        receiptElement.style.maxHeight = 'none';
        
        // Use a slight delay to ensure styles are applied
        setTimeout(() => {
            html2canvas(receiptElement, {
                scale: 2,
                backgroundColor: '#ffffff',
                useCORS: true,
                allowTaint: true,
                scrollY: -window.scrollY,
                scrollX: -window.scrollX,
                windowWidth: document.documentElement.scrollWidth,
                windowHeight: document.documentElement.scrollHeight
            }).then(canvas => {
                // Restore original styles
                receiptElement.style.overflow = originalOverflow;
                receiptElement.style.maxHeight = originalMaxHeight;
                
                const image = canvas.toDataURL("image/png");
                const downloadLink = document.createElement("a");
                downloadLink.href = image;
                downloadLink.download = `APAY_Receipt_${Date.now()}.png`;
                downloadLink.click();
                
                setTimeout(() => {
                    button.html(originalText).prop('disabled', false);
                }, 1000);
            }).catch(error => {
                console.error('Error generating receipt:', error);
                
                // Restore original styles even on error
                receiptElement.style.overflow = originalOverflow;
                receiptElement.style.maxHeight = originalMaxHeight;
                
                button.html(originalText).prop('disabled', false);
                
                Swal.fire({
                    icon: 'error',
                    title: 'Download Failed',
                    text: 'Unable to generate receipt. Please try again.'
                });
            });
        }, 100);
    }

    function formatNumber(num) {
        return parseFloat(num).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    // Close modal when clicking outside
    $(document).on('click', '.modal-overlay', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });
</script>

@include('components.contact-us')

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