@include('components.header')
<meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Paystack inline script -->
    <script src="https://js.paystack.co/v1/inline.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        <div class="content-body">

        <!-- Display SweetAlert messages if session contains success or error -->
        @if(session('success'))
            <script>
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: "{{ session('success') }}"
                });
            </script>
        @endif

        @if(session('error'))
            <script>
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: "{{ session('error') }}"
                });
            </script>
        @endif
 <style type="text/css">
 .topup-card {
        background-color: #ffffff;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.07);
        max-width: 600px;
        margin: 0 auto;
    }

    .topup-card h4 {
        font-weight: 700;
        margin-bottom: 10px;
    }

    .topup-card h5 {
        margin-top: 10px;
        font-weight: 600;
        color: #2d2d2d;
    }

    .topup-card p {
        margin-bottom: 8px;
        font-size: 14px;
        color: #555;
    }

    #amount {
        font-size: 18px;
        padding: 10px 15px;
        border-radius: 8px;
    }

    #formatted-label {
        margin-top: 5px;
        font-weight: 500;
        color: #007bff;
        font-size: 14px;
        display: block;
    }

    .btn-topup {
        background-color: green;
        color: white;
        font-weight: 600;
        padding: 12px 25px;
        border-radius: 8px;
        transition: 0.3s;
    }

    .btn-topup:hover {
        background-color: #056b05;
    }

    @media (max-width: 576px) {
        .topup-card {
            padding: 20px;
        }
    }
</style>
    <div class="container py-5">
        <div class="topup-card">
<!--             <h4>Top Up Your Balance</h4>
            <h5>Current Balance: ₦
                <span>
                    @if(!$balance)
                        0.00
                    @else
                        {{ number_format($balance->balance, 2) }}
                    @endif
                </span>
            </h5>
 -->

            <!-- <form id="topupForm" action="{{ route('topup.initialize') }}" method="POST"> -->
                @csrf
                <div class="form-group">
                    <h3>YOUR VIRTUAL ACCOUNT DETAILS</h3>
                </div>

                <div class="form-group mt-3">
                    <p>ACCOUNT NUMBER: <strong>{{ Auth::user()->account_number }}</strong></p>
                    <p>NAME: <strong>AFRICICL/{{ strtoupper(optional(Auth::user())->name) }}</strong></p>
                    <p>BANK:<strong> Wema Bank</strong></p>
                </div>
            <!-- </form> -->
        </div>
    </div>

    <!--**********************************
        Scripts
    ***********************************-->

    <!-- Auto Redraw: Fetch and update balance every 10 seconds -->
    @include('components.contact-us')
<script>
    const amountInput = document.getElementById('amount');
    const label = document.getElementById('formatted-label');

    amountInput.addEventListener('input', function (e) {
        let rawValue = e.target.value.replace(/[^0-9]/g, '');

        if (!rawValue) {
            label.textContent = '';
            return;
        }

        let formatted = Number(rawValue).toLocaleString('en-NG');
        e.target.value = formatted;

        let value = parseInt(rawValue);
        let unit = '';

        if (value >= 1_000_000) {
            unit = 'Millions';
        } else if (value >= 1_000) {
            unit = 'Thousands';
        } else if (value >= 100) {
            unit = 'Hundreds';
        } else {
            unit = '';
        }

        label.textContent = unit ? `You're entering in the ${unit}` : '';
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