@include('components.header')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">


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

    .service-card {
        background-color: #ffffff;
        border-radius: 15px;
        padding: 40px 30px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.07);
        max-width: 700px;
        width: 100%;
    }

    .service-heading {
        font-size: 22px;
        font-weight: 700;
        margin-bottom: 30px;
    }

    @media (max-width: 768px) {
        .btn-custom {
            width: 100%;
        }
    }
    /*notification*/
#notificationWrapper {
    max-width: 600px;
    background: rgba(0, 128, 0, 0.8); /* Green background */
    border-radius: 10px;
    padding: 10px;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 1050;
}

.carousel-item .alert {
    background: linear-gradient(45deg, #28a745, #218838); /* Bootstrap green shades */
    border: none;
    text-align: center;
    color: white;
}

.carousel-control-prev-icon,
.carousel-control-next-icon {
    background-color: rgba(0, 0, 0, 0.6);
    border-radius: 50%;
    padding: 10px;
}

.btn-close-white {
    filter: invert(1);
}

@media (max-width: 768px) {
    #notificationWrapper {
        width: 90%;
    }
}

.alert p {
    font-size: 1rem;
    line-height: 1.5;
}

.alert h4 {
    font-size: 1.2rem;
}

/* Using Bootstrap for styling */
.alert {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    border-radius: 0.5rem;
    box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
}

/* Style the button */
.btn-light.btn-sm {
    background-color: #ffffff;
    color: #28a745;
    border: 1px solid #28a745;
    font-weight: bold;
    padding: 8px 16px;
    border-radius: 5px;
    transition: all 0.3s ease;
}

.btn-light.btn-sm:hover {
    background-color: #28a745;
    color: #ffffff;
}
    .pin-input-container {
        display: flex;
        gap: 10px;
        justify-content: center;
    }

    .pin-box {
        width: 50px;
        height: 50px;
        text-align: center;
        font-size: 24px;
        border: 2px solid #ddd;
        border-radius: 5px;
    }

    .pin-box:focus {
        border-color: green;
        outline: none;
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
        @include('components.nk-sidebar')
        <!--**********************************
            Sidebar end
        ***********************************-->

        <!--**********************************
            Content body start
        ***********************************-->

<div class="content-body">
	<div class="container-fluid d-flex justify-content-center align-items-center" style="min-height: 90vh;">
	    <div class="text-center">

	        {{-- Credit Eligibility --}}
	        @if(isset($requirementNotMet))
	            <div class="alert alert-warning mt-4">
	                <strong>Notice:</strong> You need at least {{ $requiredCount }} APay transactions.<br>
	                You currently have {{ $currentCount }}.
	            </div>

	        @elseif(isset($hasLoan) && $hasLoan)
	            <div class="alert alert-danger mt-4">
	                <h4>You have a pending loan</h4>
	                <p><strong>Amount:</strong> ₦{{ number_format($loan->amount, 2) }}</p>
	                <p><strong>Status:</strong> {{ $loan->repayment_status }}</p>
	                <p>Please repay your loan to be eligible for more credit.</p>
	            </div>

	        @elseif(isset($limit))
            <div class="alert alert-success mt-4">
                <h4>Your Available Credit Limit</h4>
                <h2>₦{{ number_format($limit, 2) }}</h2>

                {{-- Loan History Button --}}
                <div class="mt-3">
                    <button class="btn btn-outline-dark" data-toggle="modal" data-target="#loanHistoryModal">
                        <i class="fa fa-list"></i> Loan History
                    </button>
                </div>

                <div class="mt-5">
                    <h4 class="service-heading">Borrow Options</h4>
                    <div class="d-flex justify-content-center gap-3 mt-3 flex-wrap">
                        <a href="{{ url('borrow/airtime') }}" class="btn btn-primary btn-custom">
                            <i class="fa fa-mobile"></i> Borrow Airtime
                        </a>
                        <a href="{{ url('borrow/data') }}" class="btn btn-success btn-custom">
                            <i class="fa fa-wifi"></i> Borrow Data
                        </a>
                    </div>
                </div>
            </div>
	        @endif
<div class="card mt-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">How to Borrow & Repay</h5>
        <strong>NOTE: ALL LOAN MUST BEEN PAID BACK WITHIN 2 WEEKS</strong>
    </div>
    <div class="card-body">
        <h6><strong>How to Borrow</strong></h6>
        <p>
            Based on your transaction history and activity on our platform, your credit limit is automatically calculated. 
            If you are eligible, you’ll see available borrowing options such as Airtime or Data. 
            Simply click on your preferred option and follow the steps to complete your request.
        </p>

        <h6 class="mt-4"><strong>How to Repay</strong></h6>
        <p>
            To repay your loan, just top up your account with any amount. We will automatically deduct what you owe from your balance.
            Once your full loan amount is repaid, your <strong>repayment status</strong> will automatically change to <span class="badge badge-success">PAID</span>.
        </p>

        <a href="{{ url('/topup') }}" class="btn btn-success mt-2">
            Repay Now
        </a>
    </div>
</div>

	    </div>
	</div>

<!-- Loan History Modal -->
<div class="modal fade" id="loanHistoryModal" tabindex="-1" role="dialog" aria-labelledby="loanHistoryModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="loanHistoryModalLabel">Loan History</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        @if($borrowHistory->isEmpty())
            <p>No loan history found.</p>
        @else
            <div class="table-responsive">
              <table class="table table-bordered">
                <thead class="thead-dark">
                  <tr>
                    <th>#</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Repayment</th>
                    <th>Date</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($borrowHistory as $index => $loan)
                    <tr>
                      <td>{{ $index + 1 }}</td>
                      <td>₦{{ number_format($loan->amount, 2) }}</td>
                      <td>{{ ucfirst($loan->status) }}</td>
                      <td>{{ $loan->repayment_status }}</td>
                      <td>{{ $loan->created_at->format('d M Y') }}</td>
                      <td>@if($loan->status !== 'rejected' & $loan->repayment_status !== 'PAID')
                            <a href="{{ url('/topup') }}" class="btn btn-sm btn-success">Repay</a>
                        @endif
                    </td>

                  @endforeach
                </tbody>
              </table>
            </div>
        @endif
      </div>
    </div>
  </div>

</div>



    <!--**********************************
        Scripts
    ***********************************-->
<script>
    document.getElementById('toggleButton').onclick = function () {
        toggleVisibility('balance', 'hiddenBalance', 'toggleIcon');
    };
    document.getElementById('owetoggleButton').onclick = function () {
        toggleVisibility('owebalance', 'owehiddenBalance', 'owetoggleIcon');
    };

    function toggleVisibility(showId, hideId, iconId) {
        const showElem = document.getElementById(showId);
        const hideElem = document.getElementById(hideId);
        const icon = document.getElementById(iconId);

        const isHidden = showElem.style.display === 'none';

        showElem.style.display = isHidden ? 'block' : 'none';
        hideElem.style.display = isHidden ? 'none' : 'block';
        icon.classList.toggle('icon-eye');
        icon.classList.toggle('icon-eye-off');
    }
</script>



    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>


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