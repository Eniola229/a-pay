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
        <!-----Notification slide show----->
        @if ($notifications->where('expiry_date', '>', now())->isNotEmpty())
        <div id="notificationWrapper" class="position-fixed top-0 start-50 translate-middle-x w-100 mt-3 z-index-1050">
            <div id="notificationCarousel" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner">
                    @foreach ($notifications->where('expiry_date', '>', now()) as $key => $notification)
                        <div class="carousel-item {{ $key == 0 ? 'active' : '' }}">
                            <div class="container">
                                <div class="row justify-content-center">
                                    <div class="col-md-6 col-11">
                                        <div class="alert alert-primary shadow-lg p-3 rounded d-flex flex-column text-white">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <i class="fa fa-bell fa-lg me-2"></i>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                                            </div>
                                            <h4 class="fw-bold mt-2 text-center text-white">Special Notification</h4>
                                            <h5 class="fw-bold mt-2 text-center">{{ $notification->title }}</h5>
                                            <p class="fw-bold mt-2 text-center">{{ $notification->details }}</p>
                                            <div class="text-center mt-2">
                                                @if($notification->links)
                                                    <a href="{{ $notification->links }}" class="btn btn-light btn-sm" target="_blank">
                                                        Learn More
                                                    </a>
                                                @endif
                                            </div>
                                            <!-- Cancel Button -->
                                            <button id="cancelNotification" class="btn btn-danger btn-sm mt-3">Cancel</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Carousel Controls -->
                <button class="carousel-control-prev d-none d-md-block" style="border: none; background: none" type="button" id="prevSlide">
                    <span class="carousel-control-prev-icon bg-dark rounded-circle p-2"></span>
                </button>
                <button class="carousel-control-next d-none d-md-block" style="border: none; background: none" type="button" id="nextSlide">
                    <span class="carousel-control-next-icon bg-dark rounded-circle p-2"></span>
                </button>
            </div>
        </div>
@endif

        <div class="content-body">
            <!-- Contact Us Button -->
        <!-- Contact Us Button -->
            <div class="container-fluid mt-3">
                <div class="row">
                    <div class="col-lg-4 col-sm-6">
                        <div class="card gradient-2">
                            <div class="card-body">
                                <h3 class="card-title text-white">Balance</h3>
                                <div class="d-inline-block">
                                       <!-- Balance (Hidden by Default) -->
                                    <h2 id="balance" class="text-white" style="display: none;">₦ 
                                    @if(!$balance)
                                        0.00
                                    @else
                                      {{ number_format($balance->balance, 2) }}
                                    @endif
                                    </h2>
                                    <h2 id="hiddenBalance" class="text-white">****</h2>

                                    <!-- Toggle Button -->
                                    <p id="toggleButton" style="background: none; border: none; cursor: pointer;">
                                        <i id="toggleIcon" class="icon-eye menu-icon"></i> 
                                    </p>
                                </div>
                                <span class="float-right display-5 opacity-5"><i class="fa fa-money"></i></span>
                                
                            </div>
                                <a href="{{ url('/topup') }}" class="mb-4 ml-4">
                                    <button class="btn" style="color: black; background: white; border: none;">Top up</button>
                                </a> 
                        </div>
                    </div>
                    <div class="col-lg-4 col-sm-6">
                        <div class="card gradient-1">
                            <div class="card-body">
                                <h3 class="card-title text-white">Oweing</h3>
                                <div class="d-inline-block">
                                       <!-- Balance (Hidden by Default) -->
                                    <h2 id="owebalance" class="text-white" style="display: none;">₦ 
                                    @if(!$balance)
                                        0.00
                                    @else
                                       {{ $balance->owe }}
                                    @endif
                                    </h2>
                                    <h2 id="owehiddenBalance" class="text-white">****</h2>

                                    <!-- Toggle Button -->
                                    <p id="owetoggleButton" style="background: none; border: none; cursor: pointer;">
                                        <i id="owetoggleIcon" class="icon-eye menu-icon"></i> 
                                    </p>
                                </div>
                                <span class="float-right display-5 opacity-5"><i class="fa fa-money"></i></span>
                                
                            </div>
                                <a href="{{ url('/borrow/credit_limit') }}" class="mb-4 ml-4">
                                    <button class="btn" style="color: black; background: white; border: none;">Borrow Airtime/Data</button>
                                </a> 
                        </div>
                    </div>
                </div>
                @if(!$balance || empty($balance->pin)) 
<div class="col-lg-12">
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">Please set up your account PIN and add funds.</h4>
            <div class="bootstrap-modal">
                <button type="button" class="btn btn-primary" style="background: green; border: 1px solid green;" data-toggle="modal" data-target="#exampleModal">
                    Click here
                </button>
                <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="exampleModalLabel">Set Your 4-Digit PIN</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form id="pinForm">
                                    <!-- New PIN -->
                                    <div class="form-group">
                                        <label for="new_pin">New PIN</label>
                                        <div class="pin-input-container">
                                            <input type="password" class="pin-box" maxlength="1" id="pin1">
                                            <input type="password" class="pin-box" maxlength="1" id="pin2">
                                            <input type="password" class="pin-box" maxlength="1" id="pin3">
                                            <input type="password" class="pin-box" maxlength="1" id="pin4">
                                        </div>
                                        <input type="hidden" name="new_pin" id="newPinHidden" required>
                                    </div>

                                    <!-- Confirm PIN -->
                                    <div class="form-group">
                                        <label for="confirm_pin">Confirm New PIN</label>
                                        <div class="pin-input-container">
                                            <input type="password" class="pin-box" maxlength="1" id="confirmPin1">
                                            <input type="password" class="pin-box" maxlength="1" id="confirmPin2">
                                            <input type="password" class="pin-box" maxlength="1" id="confirmPin3">
                                            <input type="password" class="pin-box" maxlength="1" id="confirmPin4">
                                        </div>
                                        <input type="hidden" name="confirm_pin" id="confirmPinHidden" required>
                                    </div>

                                    <button type="submit" id="savePinBtn" class="btn btn-primary w-100" style="background: green;">Save PIN</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
                    @endif
                    @if($balance && !empty($balance->pin))
                    <div class="container d-flex justify-content-center align-items-center vh-100">
                        <div class="service-card text-center">
                            <h4 class="service-heading">Our Services</h4>
                            <div class="row g-3 justify-content-center">
                                <div class="col-12 col-md-auto">
                                    <a href="{{ url('/airtime/buy') }}" class="btn btn-primary btn-custom">
                                        <i class="fa fa-mobile"></i> Airtime
                                    </a>
                                </div>
                                <div class="col-12 col-md-auto">
                                    <a href="{{ url('/data/buy') }}" class="btn btn-success btn-custom">
                                        <i class="fa fa-wifi"></i> Data
                                    </a>
                                </div>
                                <div class="col-12 col-md-auto">
                                    <a href="{{ url('/electricity') }}" class="btn btn-warning btn-custom">
                                        <i class="fa fa-bolt"></i> Electricity
                                    </a>
                                </div>
                           <!--     <div class="col-12 col-md-auto"> 
                                    <a href="{{ url('/borrow/credit#limit') }}" class="btn btn-success btn-custom">
                                        <i class="fa fa-wifi"></i> Borrow Airtime/Data
                                    </a>
                                </div> -->
                            </div>
                        </div>
                    </div>
                    @endif            
                <div class="container my-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-body text-center">
                            <h5 class="card-title mb-3">🎉 Invite and Earn ₦100 Per Invite</h5>
                            <p class="card-text">Share your unique invite link with friends and earn rewards.</p>

                            <div class="input-group mb-3">
                                <input type="text" 
                                       class="form-control" 
                                       id="inviteLink"
                                       style="border: 1px solid green;" 
                                       readonly
                                       value="{{ url('/register?r_c=' . Auth::user()->mobile) }}">
                                <button class="btn" style="background:none; border: 1px solid green; color: green;"  type="button" onclick="copyInviteLink()">Copy</button>
                            </div>
                        </div>
                    </div>
                </div>    
            </div>
            <!-- #/ container -->
        
        </div>

    <!--**********************************
        Scripts
    ***********************************-->
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

        document.getElementById('owetoggleButton').addEventListener('click', function() {
            const balance = document.getElementById('owebalance');
            const hiddenBalance = document.getElementById('owehiddenBalance');
            const icon = document.getElementById('owetoggleIcon');

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
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        var notificationCarousel = new bootstrap.Carousel(document.querySelector("#notificationCarousel"), {
            interval: 5000, // Change notification every 5 seconds
            pause: "hover", // Pause on hover
            wrap: true // Loop notifications
        });

        // Cancel Button - Hides the notification wrapper
        document.getElementById("cancelNotification").addEventListener("click", function () {
            document.getElementById("notificationWrapper").style.display = "none";
        });

        // Next Button - Manually slide to next
        document.getElementById("nextSlide").addEventListener("click", function () {
            notificationCarousel.next();
        });

        // Previous Button - Manually slide to previous
        document.getElementById("prevSlide").addEventListener("click", function () {
            notificationCarousel.prev();
        });

        // Auto-hide after 10 seconds
        setTimeout(() => {
            document.getElementById("notificationWrapper").style.display = "none";
        }, 10000);
    });
</script>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
     @include('components.contact-us')
    <script>
        $(document).ready(function () {
            // Auto-focus logic for PIN input boxes
            $(".pin-box").on("input", function () {
                var currentIndex = $(this).index(".pin-box");
                if ($(this).val() && currentIndex < 7) {
                    $(".pin-box").eq(currentIndex + 1).focus();
                }
            });

            $(".pin-box").on("keydown", function (e) {
                var currentIndex = $(this).index(".pin-box");

                // Move focus back on Backspace
                if (e.key === "Backspace" && !$(this).val() && currentIndex > 0) {
                    $(".pin-box").eq(currentIndex - 1).focus();
                }
            });

            $("#pinForm").submit(function (e) {
                e.preventDefault();

                // Get PIN values from input boxes
                let pin = $("#pin1").val() + $("#pin2").val() + $("#pin3").val() + $("#pin4").val();
                let confirmPin = $("#confirmPin1").val() + $("#confirmPin2").val() + $("#confirmPin3").val() + $("#confirmPin4").val();

                // Set hidden fields
                $("#newPinHidden").val(pin);
                $("#confirmPinHidden").val(confirmPin);

                // Validation: Ensure PIN is exactly 4 digits and matches
                if (pin.length !== 4 || confirmPin.length !== 4) {
                    Swal.fire("Error", "PIN must be exactly 4 digits.", "error");
                    return;
                }

                if (pin !== confirmPin) {
                    Swal.fire("Error", "The PINs do not match. Please try again.", "error");
                    return;
                }

                let btn = $("#savePinBtn");
                btn.prop("disabled", true).text("Processing...");

                // AJAX request to save PIN
                $.ajax({
                    url: "/a-pay/set-pin",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        pin: pin,
                        confirm_pin: confirmPin
                    },
                    success: function (response) {
                        Swal.fire("Success", response.success, "success");
                        $("#exampleModal").modal("hide");
                        $("#pinForm")[0].reset();
                        window.location.reload();
                    },
                    error: function (xhr) {
                        let error = xhr.responseJSON?.error || "Something went wrong. Please ensure the re-entered PIN matches the initial PIN.";
                        Swal.fire("Error", error, "error");
                    },
                    complete: function () {
                        btn.prop("disabled", false).text("Save PIN");
                    }
                });
            });
        });

    </script>

        <script>
        function copyInviteLink() {
            var copyText = document.getElementById("inviteLink");
            copyText.select();
            copyText.setSelectionRange(0, 99999); // For mobile devices
            document.execCommand("copy");
            alert("Invite link copied: " + copyText.value);
        }
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