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
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    margin: 5px;
    padding: 15px;
    width: 160px;
    text-align: center;
    background-color: #fafafa;
    transition: transform 0.2s, box-shadow 0.2s;
}

.plan-item:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
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
.provider-container {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: center;
}

.provider-box {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 16px;
    border: 2px solid #4CAF50; /* Default Green */
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    background: #f0fff4; /* Light Green */
    transition: all 0.3s ease;
    width: 150px;
    justify-content: center;
    text-align: center;
}

.provider-box:hover {
    background: #d9f7d9; /* Darker Green on Hover */
    border-color: #388E3C;
}

.provider-box input {
    display: none;
}

.provider-box i {
    color: #4CAF50; /* Green Icon */
    font-size: 16px;
}

/* When selected, turn red */
.provider-box.selected {
    background: #ffdddd; /* Light Red */
    border-color: #D32F2F;
    color: #D32F2F;
}

.provider-box.selected i {
    color: #D32F2F; /* Red Icon */
}

/*Select type*/
.type-container {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: center;
}

.type-box {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 16px;
    border: 2px solid #4CAF50; /* Default Green */
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    background: #f0fff4; /* Light Green */
    transition: all 0.3s ease;
    width: 150px;
    justify-content: center;
    text-align: center;
}

.type-box:hover {
    background: #d9f7d9; /* Darker Green on Hover */
    border-color: #388E3C;
}

.type-box input {
    display: none;
}

.type-box i {
    color: #4CAF50; /* Green Icon */
    font-size: 16px;
}

/* When selected, turn yellow */
.type-box.selected {
    background: #fff3cd; /* Light Yellow */
    border-color: #FFC107;
    color: #856404;
}

.type-box.selected i {
    color: #856404; /* Dark Yellow Icon */
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

      <div class="container-fluid col-6">
  <div class="airtime-card mt-4">
    <h3 class="text-center">Pay Electricity Bill</h3>
    <p class="text-center">Kindly note that service fee is ₦100 for any amount</p>
    <form id="electricity-form">
        <div class="mb-3 position-relative">
            <label for="meter_number">Meter Number</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-bolt"></i></span>
                <input type="text" id="meter_number" class="form-control" placeholder="Enter meter number" required>
            </div>
        </div>
    <div class="mb-3">
        <label for="provider_id">Select Provider</label>
        <div class="provider-container">
            <label class="provider-box" data-provider="abuja-electric">
                <input type="radio" name="provider_id" value="abuja-electric">
                <i class="fas fa-plug"></i> Abuja (AEDC)
            </label>
            <label class="provider-box" data-provider="eko-electric">
                <input type="radio" name="provider_id" value="eko-electric">
                <i class="fas fa-plug"></i> Eko (EKEDC)
            </label>
            <label class="provider-box" data-provider="ibadan-electric">
                <input type="radio" name="provider_id" value="ibadan-electric">
                <i class="fas fa-plug"></i> Ibadan (IBEDC)
            </label>
            <label class="provider-box" data-provider="ikeja-electric">
                <input type="radio" name="provider_id" value="ikeja-electric">
                <i class="fas fa-plug"></i> Ikeja (IKEDC)
            </label>
            <label class="provider-box" data-provider="jos-electric">
                <input type="radio" name="provider_id" value="jos-electric">
                <i class="fas fa-plug"></i> Jos (JED)
            </label>
            <label class="provider-box" data-provider="kaduna-electric">
                <input type="radio" name="provider_id" value="kaduna-electric">
                <i class="fas fa-plug"></i> Kaduna (KAEDCO)
            </label>
            <label class="provider-box" data-provider="kano-electric">
                <input type="radio" name="provider_id" value="kano-electric">
                <i class="fas fa-plug"></i> Kano (KEDCO)
            </label>
            <label class="provider-box" data-provider="portharcourt-electric">
                <input type="radio" name="provider_id" value="portharcourt-electric">
                <i class="fas fa-plug"></i> Portharcourt (PHED)
            </label>
        </div>
    </div>

    <div class="mb-3">
        <label for="variation_id">Select Type</label>
        <div class="type-container">
            <label class="type-box" data-type="prepaid">
                <input type="radio" name="variation_id" value="prepaid" checked>
                <i class="fas fa-user"></i> PREPAID
            </label>
            <label class="type-box" data-type="postpaid">
                <input type="radio" name="variation_id" value="postpaid">
                <i class="fas fa-user"></i> POSTPAID
            </label>
        </div>
    </div>

        <div class="mb-3">
            <label for="amount">Amount (₦)</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-money-bill"></i></span>
                <input type="text" id="amount" class="form-control amount-input" placeholder="Enter amount" required>
            </div>
        </div>
        <button type="submit" id="pay-btn" class="btn btn-primary w-100" style="background: green; border: none;">Pay</button>
    </form>
</div>
</div>
        <!-- #/ container -->
        </div>
    <!-- Hidden field to store raw number -->
    <input type="hidden" id="amount_hidden" name="amount">
    <!--**********************************
        Scripts
    ***********************************-->
    @include('components.contact-us')
<!-- Scripts -->
<script type="text/javascript">

$(document).ready(function () {
    // Handle provider selection (turns red when clicked)
    $(".provider-box").click(function () {
        $(".provider-box").removeClass("selected"); // Remove selection from all
        $(this).addClass("selected"); // Add selection to clicked one
        $(this).find("input").prop("checked", true); // Set the input as checked
    });

    // Handle type selection (turns yellow when clicked)
    $(".type-box").click(function () {
        $(".type-box").removeClass("selected"); // Remove selection from all
        $(this).addClass("selected"); // Add selection to clicked one
        $(this).find("input").prop("checked", true); // Set the input as checked
    });

    // Format input value for amount
    $("#amount").on("input", function () {
        let value = $(this).val().replace(/[^0-9.]/g, ""); // Remove non-numeric characters
        let num = value ? parseFloat(value.replace(/,/g, "")) : NaN;

        if (!isNaN(num)) {
            let formattedValue = num.toLocaleString("en-US");
            $(this).val(formattedValue);
            $("#amount_hidden").val(num); // Store raw number in hidden input
        } else {
            $(this).val("");
            $("#amount_hidden").val("");
        }
    });

    // Form submission handling
    $('#electricity-form').submit(function (e) {
        e.preventDefault();

        // Get values
        const meterNumber = $('#meter_number').val();
        const providerId = $("input[name='provider_id']:checked").val(); // Get checked provider
        const amount = $('#amount_hidden').val();
        const variationId = $("input[name='variation_id']:checked").val(); // Get checked variation type

        if (!meterNumber || !providerId || !amount || !variationId) {
            Swal.fire("Error", "Please fill all fields.", "error");
            return;
        }

        // PIN Prompt before proceeding
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
                $('#pay-btn').text('Processing...').prop('disabled', true);

                // Submit data
                $.post("{{ route('pay.electricity') }}", {
                    meter_number: meterNumber,
                    provider_id: providerId,
                    amount: amount,
                    variation_id: variationId,
                    pin: pin,
                    _token: "{{ csrf_token() }}"
                })
                .done(function (response) {
                    Swal.fire("Success", response.message, "success");
                    window.location.reload();
                })
                .fail(function (xhr) {
                    Swal.fire("Error", xhr.responseJSON?.message || "An error occurred. Please try again.", "error");
                })
                .always(function () {
                    $('#pay-btn').text('Pay Bill').prop('disabled', false);
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