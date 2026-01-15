@include('components.header')

<!--**********************************
    Main wrapper start
***********************************-->
<div id="main-wrapper">
<style>
    body {
        background-color: #f8f9fa;
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
    }

    .btn-primary {
        background-color: #007bff;
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

    /* Graph Section Styles */
    .graph-section {
        margin-top: 30px;
    }

    .service-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 20px;
    }

    .service-btn {
        padding: 10px 20px;
        border: 2px solid #e0e0e0;
        background: white;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s;
        font-weight: 600;
        font-size: 14px;
    }

    .service-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.08);
        border-color: #28a745;
    }

    .service-btn.active {
        background: #28a745;
        color: white;
        border-color: #28a745;
    }

    .date-filters {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        flex-wrap: wrap;
        align-items: center;
    }

    .filter-btn {
        padding: 8px 16px;
        border: 2px solid #e0e0e0;
        background: white;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.3s;
        font-size: 13px;
        font-weight: 500;
    }

    .filter-btn:hover {
        border-color: #28a745;
    }

    .filter-btn.active {
        background: #28a745;
        color: white;
        border-color: #28a745;
    }

    .custom-date-picker {
        display: flex;
        gap: 8px;
        align-items: center;
        flex-wrap: wrap;
    }

    .custom-date-picker input {
        padding: 8px 12px;
        border: 2px solid #e0e0e0;
        border-radius: 6px;
        font-size: 13px;
    }

    .custom-date-picker label {
        font-weight: 600;
        font-size: 13px;
        margin: 0;
    }

    .chart-container {
        background: white;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        margin-bottom: 20px;
    }

    .chart-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 1px solid #e0e0e0;
    }

    .chart-header h4 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
        color: #333;
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
    }

    .legend-color {
        width: 16px;
        height: 16px;
        border-radius: 3px;
    }

    .legend-color.success {
        background: #28a745;
    }

    .legend-color.error {
        background: #dc3545;
    }

    .legend-color.pending {
        background: #ffc107;
    }

    .legend-color.total {
        background: #17a2b8;
    }

    .chart-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 15px;
        margin-top: 20px;
    }

    .stat-card {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 10px;
        border-left: 4px solid #28a745;
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

    .stat-card h4 {
        margin: 0 0 5px 0;
        color: #333;
        font-size: 24px;
        font-weight: 700;
    }

    .stat-card p {
        margin: 0;
        color: #666;
        font-size: 13px;
        font-weight: 500;
    }

    .stat-card .stat-count {
        font-size: 11px;
        color: #999;
        margin-top: 5px;
    }

    .stats-row {
        margin-bottom: 20px;
    }

    .stats-row h5 {
        font-size: 16px;
        font-weight: 600;
        margin-bottom: 15px;
        color: #333;
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
                                    <option value="cashback">Cashback</option>
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

            <!-- NEW GRAPH SECTION -->
            <div class="row graph-section">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h3 class="card-title mb-4">Transaction Analytics</h3>
                            
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
                                    <button class="btn btn-sm btn-success" id="apply-custom-date">Apply</button>
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
                                    </div>
                                </div>
                                <canvas id="transactionChart" height="80"></canvas>
                            </div>

                            <!-- Stats Summary -->
                            <div class="stats-row">
                                <h5>Transaction Status</h5>
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
                                <h5>Transaction Type</h5>
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

    // NEW GRAPH FUNCTIONALITY
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