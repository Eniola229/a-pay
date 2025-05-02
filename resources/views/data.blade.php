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
/* SweetAlert2 custom popup styling */
.custom-swal-popup {
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
}

/* Custom title style */
.custom-swal-title {
    font-size: 1.6rem;
    font-weight: bold;
    color: #333;
}

/* Custom Cancel button style */
.custom-swal-cancel {
    background-color: #d33 !important;
    border: none;
    border-radius: 5px;
    color: #fff !important;
    padding: 8px 16px;
    font-size: 1rem;
}

/* Plan item card styling */
.plan-item {
    cursor: pointer;
    border: 1px solid #28a745; /* Green border */
    border-radius: 6px;
    margin: 4px;
    padding: 10px;
    width: 110px; /* Smaller width */
    text-align: center;
    background-color: #f9f9f9;
    transition: transform 0.2s, box-shadow 0.2s;
    font-size: 14px; /* Smaller font */
}

.plan-item:hover {
    transform: scale(1.05);
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.12);
}

/* Mobile-friendly: Two buttons per row */
@media (max-width: 600px) {
    .plan-container {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 6px;
    }

    .plan-item {
        width: 100%; /* Full width in grid */
        padding: 12px;
    }
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
        .network-box[data-network="9mobile"] { color: #32a852; }
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

/*data prices*/

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

    <div class="container-fluid col-12 col-md-8 col-lg-6">
  <div class="airtime-card mt-4">
    <h3 class="text-center">Buy Data</h3>
    <form id="data-form">
        <div class="mb-3 position-relative">
            <label for="phone_number">Phone Number</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                <input type="text" id="phone_number" class="form-control" placeholder="Enter phone number" required>
            </div>
            <div id="recent-purchases" class="dropdown-menu"></div>
        </div>
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
                <label class="network-box" data-network="9mobile">
                    <input type="radio" name="network_id" value="9mobile">
                    <i class="fas fa-signal"></i> 9mobile
                </label>
            </div>
            <!-- Hidden select to retain compatibility with existing JavaScript -->
            <select id="network_id" name="network_id" class="d-none">
                <option value="">-- Select Network --</option>
                <option value="mtn">MTN</option>
                <option value="glo">Glo</option>
                <option value="airtel">Airtel</option>
                <option value="9mobile">9mobile</option>
            </select>
        </div>

        <!-- Hidden field to store the selected data plan variation id -->
        <input type="hidden" id="data_plan" name="data_plan">
        <div class="mb-3">
            <!-- This area will display the chosen plan -->
            <div id="selected-plan-display" style="font-weight: bold; text-align: center;"></div>
        </div>
        <button type="submit" id="purchase-btn" class="btn btn-primary w-100" disabled>Buy Data</button>
    </form>
</div>
            <!-- #/ container -->
        </div>

    <!--**********************************
        Scripts
    ***********************************-->
    @include('components.contact-us')
<script type="text/javascript">
$(document).ready(function() {
    const purchaseBtn = $('#purchase-btn');

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

        function loadRecentPurchases() {
            $.get("{{ route('recent.purchases-data') }}", function (data) {
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

    // Prevent Enter key from submitting the form on network select
    $('#network_id').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            return false;
        }
    });

    $(document).on('change', 'input[name="network_id"]', function () {
        const networkId = $(this).val();
        
        // Manually trigger the change event on #network_id for your existing JS to work
        $('#network_id').val(networkId).trigger('change');
    });


    $('#network_id').change(function () {
        const networkId = $(this).val().toLowerCase();
        $('#data_plan').val('');
        $('#selected-plan-display').html('');
        purchaseBtn.prop('disabled', true);

        if (networkId) {
            Swal.fire({
                title: 'Fetching Data Plans...',
                text: 'Please wait...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.get(`/data-plans/${networkId}`)
                .done(function (response) {
                    Swal.close();

                    if (response.status === true && response.data) {
                        let htmlContent = '<div class="plan-container" style="display: flex; flex-wrap: wrap; justify-content: center;">';

                        const plans = response.data;
                        const reversedKeys = Object.keys(plans).reverse();

                        $.each(reversedKeys, function (_, key) {
                            const plan = plans[key];
                            htmlContent += `
                                <div class="plan-item" data-variation="${key}" style="margin: 10px; padding: 15px; border: 1px solid #ccc; border-radius: 8px; cursor: pointer;">
                                    <strong>${plan.name}</strong><br>₦${plan.price}
                                </div>`;
                        });

                        htmlContent += '</div>';

                        Swal.fire({
                            title: 'Select Data Plan',
                            html: htmlContent,
                            showConfirmButton: false,
                            showCancelButton: true,
                            cancelButtonText: 'Close',
                            allowOutsideClick: false,
                            didOpen: () => {
                                $('.plan-item').on('click', function () {
                                    const selectedVariation = $(this).data('variation');
                                    $('#data_plan').val(selectedVariation);
                                    $('#selected-plan-display').html(`Selected: ${$(this).text()}`);
                                    Swal.close();
                                    purchaseBtn.prop('disabled', false);
                                });
                            }
                        });
                    } else {
                        Swal.fire("Error", response.message || "No plans found", "error");
                    }
                })
                .fail(function (xhr) {
                    console.error("AJAX Error:", xhr);
                    Swal.fire("Error", xhr.responseJSON?.message || "Failed to fetch data plans", "error");
                });
        }
    });
    // Handle form submission
    $('#data-form').submit(function (e) {
        e.preventDefault();

        // Ensure a data plan is selected
        if (!$('#data_plan').val()) {
            Swal.fire("Error", "Please select a valid data plan.", "error");
            return;
        }

        // Prompt for PIN before proceeding
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
                purchaseBtn.text('Processing...').prop('disabled', true);

                // Submit the form data along with the PIN
                $.post("{{ route('buy.data') }}", {
                    phone_number: $('#phone_number').val(),
                    network_id: $('#network_id').val(),
                    variation_id: $('#data_plan').val(),
                    pin: pin,
                    _token: "{{ csrf_token() }}"
                })
                .done(function (response) {
                    Swal.fire("Success", response.message, "success");
                    loadRecentPurchases(); // Optionally refresh recent purchases
                    window.location.reload();
                })
                .fail(function (xhr) {
                    Swal.fire("Error", xhr.responseJSON?.message || "An error occurred. Please try again.", "error");
                })
                .always(function () {
                    purchaseBtn.text('Buy Data').prop('disabled', false);
                });
            }
        });
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