@include('components.header')

<!--**********************************
    Main wrapper start
***********************************-->
<div id="main-wrapper">
<style>
    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #e8f5e9 100%);
    }
   .btn-custom {
        padding: 14px 28px;
        font-size: 18px;
        font-weight: 600;
        border-radius: 10px;
        transition: all 0.3s ease-in-out;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
        text-decoration: none;
    }

    .btn-custom:hover {
        box-shadow: 0px 6px 10px rgba(0, 0, 0, 0.15);
        transform: translateY(-2px);
    }

    .btn-primary {
        background-color: #28a745;
        color: white;
    }

    .btn-success {
        background-color: #28a745;
        color: white;
    }

    .btn-warning {
        background-color: #ffc107;
        color: white;
    }

    /* Enhanced Card Styles */
    .card {
        border-radius: 16px;
        border: none;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        overflow: hidden;
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 30px rgba(40, 167, 69, 0.15);
    }

    /* Gradient Cards */
    .gradient-1 {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    }

    .gradient-2 {
        background: linear-gradient(135deg, #20c997 0%, #17a2b8 100%);
    }

    .gradient-3 {
        background: linear-gradient(135deg, #17a2b8 0%, #007bff 100%);
    }

    .gradient-4 {
        background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
    }

    /* Graph Section Styles */
    .graph-section {
        margin-top: 30px;
    }

    .service-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 25px;
    }

    .service-btn {
        padding: 12px 24px;
        border: 2px solid #e8f5e9;
        background: white;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        font-weight: 600;
        font-size: 14px;
        color: #333;
        position: relative;
        overflow: hidden;
    }

    .service-btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(40, 167, 69, 0.1), transparent);
        transition: left 0.5s;
    }

    .service-btn:hover::before {
        left: 100%;
    }

    .service-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(40, 167, 69, 0.2);
        border-color: #28a745;
    }

    .service-btn.active {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        border-color: #28a745;
        box-shadow: 0 6px 20px rgba(40, 167, 69, 0.3);
        transform: translateY(-3px);
    }

    .date-filters {
        display: flex;
        gap: 12px;
        margin-bottom: 25px;
        flex-wrap: wrap;
        align-items: center;
    }

    .filter-btn {
        padding: 10px 20px;
        border: 2px solid #e8f5e9;
        background: white;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        font-size: 13px;
        font-weight: 500;
        color: #333;
    }

    .filter-btn:hover {
        border-color: #28a745;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.15);
    }

    .filter-btn.active {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        border-color: #28a745;
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.25);
    }

    .custom-date-picker {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
        padding: 8px 16px;
        background: white;
        border-radius: 10px;
        border: 2px solid #e8f5e9;
    }

    .custom-date-picker input {
        padding: 8px 12px;
        border: 2px solid #e8f5e9;
        border-radius: 8px;
        font-size: 13px;
        transition: all 0.3s;
    }

    .custom-date-picker input:focus {
        outline: none;
        border-color: #28a745;
        box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
    }

    .custom-date-picker label {
        font-weight: 600;
        font-size: 13px;
        margin: 0;
        color: #28a745;
    }

    #apply-custom-date {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        border: none;
        color: white;
        padding: 8px 16px;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s;
    }

    #apply-custom-date:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
    }

    .chart-container {
        background: white;
        padding: 30px;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        margin-bottom: 25px;
        border: 1px solid rgba(40, 167, 69, 0.1);
    }

    .chart-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 2px solid #e8f5e9;
    }

    .chart-header h4 {
        margin: 0;
        font-size: 20px;
        font-weight: 700;
        color: #1e7e34;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .chart-header h4::before {
        content: '';
        width: 4px;
        height: 24px;
        background: linear-gradient(180deg, #28a745 0%, #20c997 100%);
        border-radius: 4px;
    }

    .chart-legend {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
    }

    .legend-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        font-weight: 500;
        padding: 6px 12px;
        background: #f8f9fa;
        border-radius: 8px;
        transition: all 0.3s;
    }

    .legend-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    .legend-color {
        width: 18px;
        height: 18px;
        border-radius: 4px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }

    .legend-color.success {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    }

    .legend-color.error {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    }

    .legend-color.pending {
        background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
    }

    .legend-color.total {
        background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
    }

    .legend-color.fee-success {
        background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%);
    }

    .legend-color.fee-error {
        background: linear-gradient(135deg, #e83e8c 0%, #d63384 100%);
    }

    .chart-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-top: 25px;
    }

    .stat-card {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        padding: 24px;
        border-radius: 14px;
        border-left: 5px solid #28a745;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(40, 167, 69, 0.05) 0%, transparent 70%);
        transition: all 0.5s;
    }

    .stat-card:hover::before {
        top: -25%;
        right: -25%;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(40, 167, 69, 0.15);
    }

    .stat-card.success {
        border-left-color: #28a745;
    }

    .stat-card.error {
        border-left-color: #dc3545;
    }

    .stat-card.pending {
        border-left-color: #ffc107;
    }

    .stat-card.credit {
        border-left-color: #28a745;
    }

    .stat-card.debit {
        border-left-color: #007bff;
    }

    .stat-card.cashback {
        border-left-color: #17a2b8;
    }

    .stat-card.total {
        border-left-color: #6c757d;
    }

    .stat-card.fee-success {
        border-left-color: #6f42c1;
    }

    .stat-card.fee-error {
        border-left-color: #e83e8c;
    }

    .stat-card h4 {
        margin: 0 0 8px 0;
        color: #1e7e34;
        font-size: 28px;
        font-weight: 800;
        position: relative;
        z-index: 1;
    }

    .stat-card p {
        margin: 0;
        color: #666;
        font-size: 14px;
        font-weight: 600;
        position: relative;
        z-index: 1;
    }

    .stat-card .stat-count {
        font-size: 12px;
        color: #999;
        margin-top: 8px;
        position: relative;
        z-index: 1;
        font-weight: 500;
    }

    .stats-row {
        margin-bottom: 25px;
    }

    .stats-row h5 {
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 20px;
        color: #1e7e34;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .stats-row h5::before {
        content: '';
        width: 4px;
        height: 20px;
        background: linear-gradient(180deg, #28a745 0%, #20c997 100%);
        border-radius: 4px;
    }

    /* Enhanced select dropdowns */
    select.form-control {
        border: 2px solid #e8f5e9;
        border-radius: 10px;
        padding: 10px 15px;
        font-weight: 500;
        transition: all 0.3s;
    }

    select.form-control:focus {
        outline: none;
        border-color: #28a745;
        box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
    }

    /* Animate cards on load */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .card {
        animation: fadeInUp 0.6s ease-out;
    }

    .card:nth-child(1) { animation-delay: 0.1s; }
    .card:nth-child(2) { animation-delay: 0.2s; }
    .card:nth-child(3) { animation-delay: 0.3s; }
    .card:nth-child(4) { animation-delay: 0.4s; }
    .card:nth-child(5) { animation-delay: 0.5s; }

    /* Loading spinner for charts */
    .chart-loading {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 300px;
        color: #28a745;
    }

    .spinner {
        width: 50px;
        height: 50px;
        border: 4px solid #e8f5e9;
        border-top-color: #28a745;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }
</style>

    @include('components.nav-header')
    @include('components.main-header')
    @include('components.admin-sidenav')

    <!--**********************************
        Content body start
    ***********************************-->
    <div class="content-body">
        <div class="container-fluid mt-3">
            <!-- Existing Cards -->
            <div class="row">
                <div class="col-lg-4 col-sm-6">
                    <div class="card gradient-2">
                        <div class="card-body">
                            <h3 class="card-title text-white">Users Account Balance</h3>
                            <div class="d-inline-block">
                                <h2 id="balance" class="text-white" style="display: none;">₦ {{ number_format($totalBalance, 2, '.', ',') }}</h2>
                                <p id="toggleButton" style="background: none; border: none; cursor: pointer;">
                                    <i id="toggleIcon" class="icon-eye menu-icon"></i> 
                                </p>
                            </div>
                            <span class="float-right display-5 opacity-5"><i class="fa fa-money"></i></span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6">
                    <div class="card gradient-3">
                        <div class="card-body">
                            <h3 class="card-title text-white">Users</h3>
                            <div class="d-inline-block">
                                <h2 class="text-white">{{ $userCount }}</h2>
                                <p class="text-white mb-0">All Users</p>
                                @if($newUsersToday > 0)
                                    <span class="badge badge-success" style="font-size: 12px; padding: 4px 8px; margin-top: 5px;">
                                        <i class="fa fa-arrow-up"></i> +{{ $newUsersToday }} today
                                    </span>
                                @endif
                            </div>
                            <span class="float-right display-5 opacity-5"><i class="fa fa-users"></i></span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-sm-6">
                    <div class="card gradient-1">
                        <div class="card-body">
                            <h3 class="card-title text-white">Wallet Account Balance</h3>
                            <div class="d-inline-block">
                                <h2 id="balances" class="text-white">₦ Loading...</h2>
                            </div>
                            <span class="float-right display-5 opacity-5"><i class="fa fa-money"></i></span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-sm-6">
                    <div class="card gradient-4">
                        <div class="card-body">
                            <h3 class="card-title text-white">Transactions Summary</h3>
                            <div class="d-inline-block">
                                <h2 id="total_amount" class="text-white">₦ Loading...</h2>
                                <p id="total_transactions" class="text-white">Transactions: Loading...</p>
                            </div>
                            <div class="mt-3">
                                <select id="filter_transaction_type" class="form-control mb-2">
                                    <option value="all">All Transactions</option>
                                    <option value="wallet_topup">Wallet Top-up</option>
                                    <option value="airtime">Airtime</option>
                                    <option value="data">Data</option>
                                    <option value="electricity">Electricity</option>
                                    <option value="betting">Betting</option>
                                    <option value="to_apay">To A-Pay</option>
                                </select>
                                <select id="filter_transactions" class="form-control">
                                    <option value="all">All Time</option>
                                    <option value="today">Today</option>
                                    <option value="week">This Week</option>
                                    <option value="month">This Month</option>
                                </select>
                            </div>
                            <span class="float-right display-5 opacity-5"><i class="fa fa-money"></i></span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-sm-6">
                    <div class="card gradient-2">
                        <div class="card-body">
                            <h3 class="card-title text-white">KYC Summary</h3>
                            <div class="d-inline-block">
                                <h2 id="total_kyc" class="text-white">Loading...</h2>
                            </div>
                            <div class="mt-3">
                                <select id="filter_kyc_status" class="form-control mb-2">
                                    <option value="all">All Status</option>
                                    <option value="accepted">Accepted</option>
                                    <option value="rejected">Rejected</option>
                                    <option value="pending">Pending</option>
                                </select>
                                <select id="filter_kyc_date" class="form-control">
                                    <option value="all">All Time</option>
                                    <option value="today">Today</option>
                                </select>
                            </div>
                            <span class="float-right display-5 opacity-5"><i class="fa fa-id-card"></i></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- GRAPH SECTION -->
            <div class="row graph-section">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h3 class="card-title mb-4" style="color: #1e7e34; font-weight: 700;">📊 Transaction Analytics</h3>
                            
                            <!-- Service Type Buttons -->
                            <div class="service-buttons">
                                <button class="service-btn active" data-service="all">All Transactions</button>
                                <button class="service-btn" data-service="wallet_topup">Wallet Top-up</button>
                                <button class="service-btn" data-service="airtime">Airtime</button>
                                <button class="service-btn" data-service="data">Data</button>
                                <button class="service-btn" data-service="electricity">Electricity</button>
                                <button class="service-btn" data-service="betting">Betting</button>
                                <button class="service-btn" data-service="to_apay">To A-Pay</button>
                                <button class="service-btn" data-service="cashback">Cashback</button>
                            </div>

                            <!-- Date Filter Buttons -->
                            <div class="date-filters">
                                <button class="filter-btn" data-filter="today">Today</button>
                                <button class="filter-btn active" data-filter="week">This Week</button>
                                <button class="filter-btn" data-filter="month">This Month</button>
                                <button class="filter-btn" data-filter="all">All Time</button>
                                <div class="custom-date-picker">
                                    <label>Custom Range:</label>
                                    <input type="date" id="start-date" class="form-control-sm">
                                    <span>to</span>
                                    <input type="date" id="end-date" class="form-control-sm">
                                    <button class="btn btn-sm" id="apply-custom-date">Apply</button>
                                </div>
                            </div>

                            <!-- Chart Container -->
                            <div class="chart-container">
                                <div class="chart-header">
                                    <h4 id="chart-title">Transaction Overview</h4>
                                    <div class="chart-legend">
                                        <div class="legend-item">
                                            <div class="legend-color success"></div>
                                            <span>Success</span>
                                        </div>
                                        <div class="legend-item">
                                            <div class="legend-color pending"></div>
                                            <span>Pending</span>
                                        </div>
                                        <div class="legend-item">
                                            <div class="legend-color error"></div>
                                            <span>Error</span>
                                        </div>
                                        <div class="legend-item">
                                            <div class="legend-color total"></div>
                                            <span>Total Volume</span>
                                        </div>
                                        <div class="legend-item">
                                            <div class="legend-color fee-success"></div>
                                            <span>Fee (Success)</span>
                                        </div>
                                        <div class="legend-item">
                                            <div class="legend-color fee-error"></div>
                                            <span>Fee (Error)</span>
                                        </div>
                                    </div>
                                </div>
                                <canvas id="transactionChart" height="80"></canvas>
                            </div>

                            <!-- Stats Summary -->
                            <div class="stats-row">
                                <h5>📈 Transaction Status</h5>
                                <div class="chart-stats">
                                    <div class="stat-card success">
                                        <h4 id="stat-success-amount">₦0</h4>
                                        <p>Success Transactions</p>
                                        <div class="stat-count" id="stat-success-count">0 transactions</div>
                                    </div>
                                    <div class="stat-card pending">
                                        <h4 id="stat-pending-amount">₦0</h4>
                                        <p>Pending Transactions</p>
                                        <div class="stat-count" id="stat-pending-count">0 transactions</div>
                                    </div>
                                    <div class="stat-card error">
                                        <h4 id="stat-error-amount">₦0</h4>
                                        <p>Error Transactions</p>
                                        <div class="stat-count" id="stat-error-count">0 transactions</div>
                                    </div>
                                    <div class="stat-card total">
                                        <h4 id="stat-total-amount">₦0</h4>
                                        <p>Total Volume</p>
                                        <div class="stat-count" id="stat-total-count">0 transactions</div>
                                    </div>
                                </div>
                            </div>

                            <div class="stats-row">
                                <h5>💳 Transaction Type</h5>
                                <div class="chart-stats">
                                    <div class="stat-card credit">
                                        <h4 id="stat-credit-amount">₦0</h4>
                                        <p>Credit Transactions</p>
                                        <div class="stat-count" id="stat-credit-count">0 transactions</div>
                                    </div>
                                    <div class="stat-card debit">
                                        <h4 id="stat-debit-amount">₦0</h4>
                                        <p>Debit Transactions</p>
                                        <div class="stat-count" id="stat-debit-count">0 transactions</div>
                                    </div>
                                    <div class="stat-card cashback">
                                        <h4 id="stat-cashback-amount">₦0</h4>
                                        <p>Cashback Earnings</p>
                                        <div class="stat-count" id="stat-cashback-count">0 cashbacks</div>
                                    </div>
                                    <div class="stat-card">
                                        <h4 id="stat-average">₦0</h4>
                                        <p>Average Transaction</p>
                                        <div class="stat-count" id="stat-success-rate">0% success rate</div>
                                    </div>
                                </div>
                            </div>

                            <div class="stats-row">
                                <h5>💰 Transaction Fees</h5>
                                <div class="chart-stats">
                                    <div class="stat-card fee-success">
                                        <h4 id="stat-fee-success-amount">₦0</h4>
                                        <p>Fee Success</p>
                                        <div class="stat-count" id="stat-fee-success-count">0 transactions</div>
                                    </div>
                                    <div class="stat-card fee-error">
                                        <h4 id="stat-fee-error-amount">₦0</h4>
                                        <p>Fee Error</p>
                                        <div class="stat-count" id="stat-fee-error-count">0 transactions</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    const walletBalanceUrl = "{{ route('wallet.balance') }}";
    const transactionSummaryUrl = "{{ route('transactions.summary') }}";
    const kycSummaryUrl = "{{ route('kyc.summary') }}";
    const graphDataUrl = "{{ route('transactions.graph') }}";

    // Existing wallet balance code
    $(document).ready(function () {
        $.ajax({
            url: walletBalanceUrl,
            method: "GET",
            success: function (response) {
                if (response.code === "success") {
                    const balance = Number(response.data.balance);
                    const formattedBalance = balance.toLocaleString('en-NG', {
                        style: 'currency',
                        currency: 'NGN',
                        minimumFractionDigits: 2
                    });
                    $('#balances').html(formattedBalance);
                } else {
                    $('#balances').html("₦ Error");
                }
            },
            error: function () {
                $('#balances').html("₦ Network Error");
            }
        });
    });

    // Existing transaction summary code
    function fetchTransactionSummary(filter = 'all', type = 'all') {
        $.ajax({
            url: transactionSummaryUrl,
            method: "GET",
            data: { filter: filter, type: type },
            success: function(response) {
                if (response.code === "success") {
                    const totalAmount = Number(response.data.total_amount).toLocaleString('en-NG', {
                        style: 'currency',
                        currency: 'NGN',
                        minimumFractionDigits: 2
                    });
                    $('#total_amount').html(totalAmount);
                    $('#total_transactions').html(`Transactions: ${response.data.total_transactions}`);
                }
            }
        });
    }

    $(document).ready(function() {
        fetchTransactionSummary();
        $('#filter_transactions, #filter_transaction_type').change(function() {
            const filter = $('#filter_transactions').val();
            const type = $('#filter_transaction_type').val();
            fetchTransactionSummary(filter, type);
        });
    });

    // Existing KYC summary code
    function fetchKycSummary() {
        const status = $('#filter_kyc_status').val();
        const filter = $('#filter_kyc_date').val();
        $.ajax({
            url: kycSummaryUrl,
            method: "GET",
            data: { status: status, filter: filter },
            success: function(response) {
                if (response.code === "success") {
                    $('#total_kyc').html(response.data.total_kyc);
                }
            }
        });
    }

    $(document).ready(function() {
        fetchKycSummary();
        $('#filter_kyc_status, #filter_kyc_date').change(function() {
            fetchKycSummary();
        });
    });

    // Balance toggle
    document.getElementById('toggleButton').addEventListener('click', function() {
        const balance = document.getElementById('balance');
        const icon = document.getElementById('toggleIcon');
        if (balance.style.display === 'none') {
            balance.style.display = 'block';
            icon.classList.remove('icon-eye');
            icon.classList.add('icon-eye-slash');
        } else {
            balance.style.display = 'none';
            icon.classList.remove('icon-eye-slash');
            icon.classList.add('icon-eye');
        }
    });

    // GRAPH FUNCTIONALITY
    let transactionChart;
    let currentService = 'all';
    let currentFilter = 'week';
    let customStartDate = null;
    let customEndDate = null;

    function formatCurrency(amount) {
        return '₦' + Number(amount).toLocaleString('en-NG', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function initChart() {
        const ctx = document.getElementById('transactionChart').getContext('2d');
        transactionChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [
                    {
                        label: 'Success Transactions',
                        data: [],
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#28a745',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2
                    },
                    {
                        label: 'Pending Transactions',
                        data: [],
                        borderColor: '#ffc107',
                        backgroundColor: 'rgba(255, 193, 7, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#ffc107',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2
                    },
                    {
                        label: 'Error Transactions',
                        data: [],
                        borderColor: '#dc3545',
                        backgroundColor: 'rgba(220, 53, 69, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#dc3545',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2
                    },
                    {
                        label: 'Total Volume',
                        data: [],
                        borderColor: '#17a2b8',
                        backgroundColor: 'rgba(23, 162, 184, 0.05)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        pointBackgroundColor: '#17a2b8',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        borderDash: [5, 5]
                    },
                    {
                        label: 'Transaction Fee (Success)',
                        data: [],
                        borderColor: '#6f42c1',
                        backgroundColor: 'rgba(111, 66, 193, 0.1)',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.4,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        pointBackgroundColor: '#6f42c1',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2
                    },
                    {
                        label: 'Transaction Fee (Error)',
                        data: [],
                        borderColor: '#e83e8c',
                        backgroundColor: 'rgba(232, 62, 140, 0.1)',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.4,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        pointBackgroundColor: '#e83e8c',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: '#28a745',
                        borderWidth: 1,
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + formatCurrency(context.parsed.y);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            callback: function(value) {
                                return formatCurrency(value);
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    function fetchGraphData() {
        const params = {
            service: currentService,
            filter: currentFilter
        };

        if (currentFilter === 'custom' && customStartDate && customEndDate) {
            params.start_date = customStartDate;
            params.end_date = customEndDate;
        }

        $.ajax({
            url: graphDataUrl,
            method: 'GET',
            data: params,
            success: function(response) {
                if (response.code === 'success') {
                    updateChart(response.data);
                    updateStats(response.data.stats);
                }
            },
            error: function(xhr) {
                console.error('Failed to fetch graph data:', xhr);
            }
        });
    }

    function updateChart(data) {
        transactionChart.data.labels = data.labels;
        transactionChart.data.datasets[0].data = data.success_amounts;
        transactionChart.data.datasets[1].data = data.pending_amounts;
        transactionChart.data.datasets[2].data = data.error_amounts;
        transactionChart.data.datasets[3].data = data.total_amounts;
        transactionChart.data.datasets[4].data = data.fee_success_amounts;
        transactionChart.data.datasets[5].data = data.fee_error_amounts;
        
        $('#chart-title').text(data.service_name + ' - Transaction Overview');
        transactionChart.update();
    }

    function updateStats(stats) {
        // Status stats
        $('#stat-success-amount').text(formatCurrency(stats.success_total));
        $('#stat-success-count').text(stats.success_count + ' transactions');
        
        $('#stat-pending-amount').text(formatCurrency(stats.pending_total));
        $('#stat-pending-count').text(stats.pending_count + ' transactions');
        
        $('#stat-error-amount').text(formatCurrency(stats.error_total));
        $('#stat-error-count').text(stats.error_count + ' transactions');
        
        $('#stat-total-amount').text(formatCurrency(stats.total_amount));
        $('#stat-total-count').text(stats.total_count + ' transactions');
        
        // Type stats
        $('#stat-credit-amount').text(formatCurrency(stats.credit_total));
        $('#stat-credit-count').text(stats.credit_count + ' transactions');
        
        $('#stat-debit-amount').text(formatCurrency(stats.debit_total));
        $('#stat-debit-count').text(stats.debit_count + ' transactions');
        
        $('#stat-cashback-amount').text(formatCurrency(stats.cashback_total));
        $('#stat-cashback-count').text(stats.cashback_count + ' cashbacks');
        
        $('#stat-average').text(formatCurrency(stats.average));
        $('#stat-success-rate').text(stats.success_rate + '% success rate');
        
        // Fee stats
        $('#stat-fee-success-amount').text(formatCurrency(stats.fee_success_total));
        $('#stat-fee-success-count').text(stats.fee_success_count + ' transactions');
        
        $('#stat-fee-error-amount').text(formatCurrency(stats.fee_error_total));
        $('#stat-fee-error-count').text(stats.fee_error_count + ' transactions');
    }

    // Service button clicks
    $('.service-btn').click(function() {
        $('.service-btn').removeClass('active');
        $(this).addClass('active');
        currentService = $(this).data('service');
        fetchGraphData();
    });

    // Filter button clicks
    $('.filter-btn').click(function() {
        $('.filter-btn').removeClass('active');
        $(this).addClass('active');
        currentFilter = $(this).data('filter');
        fetchGraphData();
    });

    // Custom date range
    $('#apply-custom-date').click(function() {
        customStartDate = $('#start-date').val();
        customEndDate = $('#end-date').val();
        
        if (customStartDate && customEndDate) {
            $('.filter-btn').removeClass('active');
            currentFilter = 'custom';
            fetchGraphData();
        } else {
            alert('Please select both start and end dates');
        }
    });

    // Initialize on page load
    $(document).ready(function() {
        initChart();
        fetchGraphData();
    });
    </script>

    <!-- Scripts -->
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
</body>
</html>