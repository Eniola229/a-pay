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

        /* Specific Button Colors */
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
    </style>
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

            <div class="container-fluid mt-3">
                <div class="row">
                    <div class="col-lg-4 col-sm-6">
                        <div class="card gradient-2">
                            <div class="card-body">
                                <h3 class="card-title text-white">Users Account Balance</h3>
                                <div class="d-inline-block">
                                       <!-- Balance (Hidden by Default) -->
                                    <h2 id="balance" class="text-white" style="display: none;">₦ {{ number_format($totalBalance, 2, '.', ',') }}
                                   
                                    </h2>

                                    <!-- Toggle Button -->
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
                                    <!-- Balance shows immediately -->
                                    <h2 id="balances" class="text-white">₦ Loading...</h2>
                                </div>
                                <span class="float-right display-5 opacity-5">
                                    <i class="fa fa-money"></i>
                                </span>
                            </div>
                        </div>
                    </div>

                </div>           
            </div>
            <!-- #/ container -->
        </div>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    const walletBalanceUrl = "{{ route('wallet.balance') }}";

    $(document).ready(function () {
        $.ajax({
            url: walletBalanceUrl,
            method: "GET",
            success: function (response) {
                if (response.code === "success") {
                    const balance = response.data.balance;
                    $('#balances').html(`₦ ${balance.toLocaleString()}`);
                } else {
                    $('#balances').html("₦ Error");
                    console.error("Error fetching balance:", response.message);
                }
            },
            error: function () {
                $('#balances').html("₦ Network Error");
            }
        });
    });
</script>

    <script>
        document.getElementById('toggleButton').addEventListener('click', function() {
            const balance = document.getElementById('balance');
            const hiddenBalance = document.getElementById('hiddenBalance');
            const icon = document.getElementById('toggleIcon');

            if (balance.style.display === 'none') {
                // Show Balance
                balance.style.display = 'block';
                hiddenBalance.style.display = 'none';
                icon.classList.remove('icon-eye');
                icon.classList.add('icon-eye'); // Change to "eye-slash" icon
            } else {
                // Hide Balance
                balance.style.display = 'none';
                hiddenBalance.style.display = 'block';
                icon.classList.remove('icon-eye-slash');
                icon.classList.add('icon-eye'); // Change back to "eye" icon
            }
        });
    </script>

    <!--**********************************
        Scripts
    ***********************************-->
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