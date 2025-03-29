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

        <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">Withdraw</h4>
                                <h5 class="">Current Balance: ₦<span id="current-balance">{{ $balance ? $balance->balance : '0.00' }}</span></p></h5>
                                <p class="text-muted m-b-15 f-s-12">Enter Amount (in Naira):</p>
                                <div class="basic-form">
                                      <form id="withdrawForm">
                                        @csrf
                                      
                                              <div class="mb-3">
                                                    <label for="amount" class="form-label">Withdrawal Amount (in Naira):</label>
                                                    <input type="number" name="amount" id="amount" class="form-control" required>
                                                  </div>
                                                  <div class="mb-3">
                                                    <label for="bank_account" class="form-label">Bank Account Number:</label>
                                                    <input type="text" name="bank_account" id="bank_account" class="form-control" required>
                                                  </div>
                                                 <div class="mb-3 form-group">
                                                  <label for="bank_code" class="form-label">Select Bank:</label>
                                                  <select name="bank_code" id="bank_code"  class="form-control form-select-lg border-primary shadow-sm" aria-label="Select Bank" required>
                                                    <option value="">-- Select Bank --</option>
                                                    <option value="044">Access Bank</option>
                                                    <option value="011">First Bank of Nigeria</option>
                                                    <option value="058">Guaranty Trust Bank</option>
                                                    <option value="057">Zenith Bank</option>
                                                    <option value="033">United Bank for Africa (UBA)</option>
                                                    <option value="039">Stanbic IBTC Bank</option>
                                                    <option value="050">Ecobank Nigeria</option>
                                                    <option value="032">Union Bank</option>
                                                    <option value="070">Fidelity Bank</option>
                                                    <option value="214">First City Monument Bank (FCMB)</option>
                                                    <option value="035">Wema Bank</option>
                                                    <option value="232">Sterling Bank</option>
                                                    <option value="030">Heritage Bank</option>
                                                    <option value="082">Keystone Bank</option>
                                                    <option value="076">Polaris Bank</option>
                                                    <option value="068">Standard Chartered Bank</option>
                                                    <option value="101">Providus Bank</option>
                                                  </select>
                                                </div>

                                       
                                       
                                        <div class="form-group">
                                            <button class="btn" style="background: green; color: white;" type="submit" id="withdrawBtn">Withdraw</button>
                                        </div>
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
@include('components.contact-us')
  <script>
    $(document).ready(function() {
        // Function to fetch and update the current balance
        function fetchBalance(){
            $.ajax({
                url: "{{ route('balance') }}",
                type: "GET",
                success: function(data) {
                    $('#current-balance').text(data.balance);
                }
            });
        }
        // Auto-refresh balance every 10 seconds
        setInterval(fetchBalance, 10000);

        // Handle the withdrawal form submission via AJAX with a PIN prompt
        $('#withdrawForm').submit(function(e) {
            e.preventDefault();
            // Serialize the form data (without PIN)
            var formData = $(this).serializeArray();

            // Prompt the user for their PIN using SweetAlert2
            Swal.fire({
                title: 'Enter your PIN',
                input: 'password',
                inputAttributes: {
                    maxlength: 10,
                    autocapitalize: 'off',
                    autocorrect: 'off'
                },
                showCancelButton: true,
                confirmButtonText: 'Submit',
                showLoaderOnConfirm: true,
                preConfirm: (pin) => {
                    if (!pin) {
                        Swal.showValidationMessage('You must enter your PIN');
                    }
                    return pin;
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed) {
                    // Append the PIN to the serialized form data
                    formData.push({ name: 'pin', value: result.value });
                    var formDataSerialized = $.param(formData);

                    var $btn = $('#withdrawBtn');
                    $btn.prop('disabled', true).text('Processing...');

                    $.ajax({
                        url: "{{ route('withdraw.process') }}",
                        type: "POST",
                        data: formDataSerialized,
                        success: function(response) {
                            if (response.status) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success',
                                    text: response.message
                                });
                                fetchBalance(); // Refresh balance after successful withdrawal
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: response.message
                                });
                            }
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'An error occurred. Please try again.'
                            });
                        },
                        complete: function() {
                            $btn.prop('disabled', false).text('Withdraw');
                        }
                    });
                }
            });
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