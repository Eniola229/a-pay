@include('components.header')
<meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Paystack inline script -->
    <script src="https://js.paystack.co/v1/inline.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
   <style>
.airtime-card {
    background-color: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    padding: 20px;
    max-width: 800px;
    margin: auto;
}

.airtime-card h3 {
    color: #36C16F;
    margin-bottom: 20px;
}

.input-group-text {
    background-color: #36C16F;
    color: #fff;
    border: none;
    border-top-left-radius: 5px;
    border-bottom-left-radius: 5px;
}

.form-control {
    border-top-right-radius: 5px;
    border-bottom-right-radius: 5px;
}

#purchase-btn {
    background-color: #36C16F;
    border: none;
    border-radius: 5px;
    padding: 10px;
    font-size: 16px;
    transition: background-color 0.3s;
}

#purchase-btn:hover {
    background-color: #2fa85f;
}

#recent-purchases {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    z-index: 1000;
    display: none;
    background-color: #fff;
    border: 1px solid rgba(0, 0, 0, 0.15);
    border-radius: 0.25rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.175);
}

#recent-purchases.show {
    display: block;
}

#recent-purchases a {
    display: block;
    padding: 0.25rem 1.5rem;
    clear: both;
    color: #212529;
    text-align: inherit;
    white-space: nowrap;
    background-color: transparent;
    border: 0;
}

#recent-purchases a:hover {
    background-color: #f8f9fa;
}
/*for select*/

        .network-box {
            flex: 1;
            min-width: 120px;
            padding: 15px;
            text-align: center;
            border: 2px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.3s;
            font-size: 16px;
            font-weight: bold;
            background-color: #f8f9fa;
        }

        .network-box i {
            display: block;
            font-size: 24px;
            margin-bottom: 5px;
        }

        .network-box input {
            display: none;
        }

        /* Green highlight when selected */
        .network-box.selected {
            border-color: #28a745;
            background-color: #e9f5ec;
            color: #28a745;
        }

        /* Different colors for each network */
        .network-box[data-network="mtn"] { color: #ffcc00; }
        .network-box[data-network="glo"] { color: #008000; }
        .network-box[data-network="airtel"] { color: #d91a1a; }
        .network-box[data-network="etisalat"] { color: #32a852; }
/*For amount input*/
        .red-border {
            border: 2px solid red !important;
        }
        .number-info {
            margin-top: 5px;
            font-size: 14px;
            color: #333;
        }
        .amount-input {
            text-align: right;
            font-size: 18px;
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

        <div class="container-fluid">
                
                   <div class="airtime-card mt-4">
                    <h3 class="text-center">Borrow Airtime</h3>
                    <form id="airtime-form">
                        <div class="mb-3 position-relative">
                            <label for="phone_number">Phone Number</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                <input type="text" id="phone_number" class="form-control" placeholder="Enter phone number" required>
                            </div>
                            <div id="recent-purchases" class="dropdown-menu"></div>
                        </div>
                        <div class="mb-3">
                            <label for="amount">Amount (NGN)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-money-bill-wave"></i></span>
                                <input type="text" id="amount" class="form-control amount-input" placeholder="Enter amount" required>
                            </div>
                            <div id="number_info" class="number-info"></div>
                        </div>
                        <div class="mt-4">
                            <div class="mb-3">
                                <label for="network_id">Select Network</label>
                                <div class="network-container">
                                    <label class="network-box" data-network="mtn">
                                        <input type="radio" name="network_id" value="mtn">
                                        <i class="fas fa-signal"></i> MTN
                                    </label>
                                    <label class="network-box" data-network="glo">
                                        <input type="radio" name="network_id" value="glo">
                                        <i class="fas fa-signal"></i> Glo
                                    </label>
                                    <label class="network-box" data-network="airtel">
                                        <input type="radio" name="network_id" value="airtel">
                                        <i class="fas fa-signal"></i> Airtel
                                    </label>
                                    <label class="network-box" data-network="etisalat">
                                        <input type="radio" name="network_id" value="etisalat">
                                        <i class="fas fa-signal"></i> 9mobile
                                    </label>
                                </div>
                            </div>
                        </div>
                        <button type="submit" id="purchase-btn" class="btn btn-primary w-100">Borrow Airtime</button>
                    </form>
                </div>

    <!-- Hidden field to store raw number -->
    <input type="hidden" id="amount_hidden" name="amount">
    <script>
        $(document).ready(function () {
        $('#phone_number').on('focus', function () {
            loadRecentPurchases();
        });

        $('#phone_number').on('input', function () {
            filterRecentPurchases($(this).val());
        });

        $(document).on('click', function (e) {
            if (!$(e.target).closest('#phone_number').length) {
                $('#recent-purchases').removeClass('show');
            }
        });

        $(document).ready(function () {
            $("#amount").on("input", function () {
                let value = $(this).val().replace(/[^0-9.]/g, ""); // Remove non-numeric characters
                let num = value ? parseFloat(value.replace(/,/g, "")) : NaN;

                // Format input field for user
                let formattedValue = num ? num.toLocaleString("en-US") : "";
                $(this).val(formattedValue); 

                // Store raw number in a hidden input before submission
                $("#amount_hidden").val(num || "");
            });

            $("#your-form-id").on("submit", function () {
                let cleanAmount = $("#amount").val().replace(/,/g, ""); // Remove commas
                $("#amount").val(cleanAmount); // Update input before sending
            });
        });
        
        $(document).ready(function () {
            $(".network-box").click(function () {
                $(".network-box").removeClass("selected"); // Remove previous selection
                $(this).addClass("selected"); // Highlight selected box
                $(this).find("input").prop("checked", true); // Check the hidden input
            });
        });

        $(document).ready(function () {
            $(".network-box").click(function () {
                $(".network-box").removeClass("selected"); // Remove previous selection
                $(this).addClass("selected"); // Highlight selected box
                $(this).find("input").prop("checked", true); // Mark as selected
            });

            $("#airtime-form").on("submit", function (e) {
                if (!$("input[name='network_id']:checked").val()) {
                    alert("Please select a network."); // Prevent submission if no selection
                    e.preventDefault();
                }
            });
        });

        function loadRecentPurchases() {
            $.get("{{ route('recent.purchases') }}", function (data) {
                $('#recent-purchases').empty();
                data.forEach(purchase => {
                    $('#recent-purchases').append(`<a href="#" class="dropdown-item recent-purchase">${purchase.phone_number}</a>`);
                });
                $('#recent-purchases').addClass('show');
            });
        }

        function filterRecentPurchases(query) {
            $('.recent-purchase').each(function () {
                if ($(this).text().startsWith(query)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        }

        $(document).on('click', '.recent-purchase', function (e) {
            e.preventDefault();
            $('#phone_number').val($(this).text());
            $('#recent-purchases').removeClass('show');
        });

            $(document).on('click', '.recent-purchase', function () {
                $('#phone_number').val($(this).text());
            });

            
            $('#airtime-form').submit(function (e) {
                e.preventDefault();

                // Show SweetAlert modal for PIN input
                Swal.fire({
                    title: 'Enter your PIN',
                    input: 'password',
                    inputAttributes: {
                        maxlength: 4,
                        autocapitalize: 'off',
                        autocorrect: 'off'
                    },
                    showCancelButton: true,
                    confirmButtonText: 'Submit',
                    cancelButtonText: 'Cancel',
                    showLoaderOnConfirm: true,
                    preConfirm: (pin) => {
                        if (!pin || pin.length !== 4) {
                            Swal.showValidationMessage('PIN must be 4 digits');
                        }
                        return pin;
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then((result) => {
                    if (result.isConfirmed) {
                        const pin = result.value;

                        // Disable the button and show processing text
                        $('#purchase-btn').text('Processing...').prop('disabled', true);

                        // Submit the form data along with the PIN
                        $.post("{{ route('borrow.airtime') }}", {
                            phone_number: $('#phone_number').val(),
                            amount: $('#amount_hidden').val(),
                            network_id: $("input[name='network_id']:checked").val(),
                            pin: pin, // Include the PIN in the request
                            _token: "{{ csrf_token() }}"
                        })
                        .done(function (response) {
                            Swal.fire("Success", response.message, "success");
                            loadRecentPurchases();
                        })
                        .fail(function (xhr) {
                            Swal.fire("Error", xhr.responseJSON.message, "error");
                        })
                        .always(function () {
                            $('#purchase-btn').text('Borrow Airtime').prop('disabled', false);
                        });
                    }
                });
            });


        });
    </script>
            <!-- #/ container -->
        </div>

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