@include('components.header')
<style type="text/css">
.profile-card {
    background: linear-gradient(135deg, #009966, #006644);
    border-radius: 12px;
    box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
    color: white;
    padding: 20px;
    text-align: center;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.profile-card:hover {
    transform: translateY(-5px);
    box-shadow: 0px 6px 15px rgba(0, 0, 0, 0.15);
}

.profile-card-body {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.user-info {
    margin-bottom: 15px;
}

.user-name {
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 5px;
}

.user-email {
    font-size: 14px;
    color: #e0e0e0;
    margin-bottom: 8px;
}

/* Account number with copy effect */
.user-account-number {
    font-size: 16px;
    color: #ffffff;
    background: rgba(255, 255, 255, 0.2);
    padding: 8px 15px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    cursor: pointer;
    transition: background 0.3s ease;
}

.user-account-number:hover {
    background: rgba(255, 255, 255, 0.3);
}

.copy-icon {
    margin-left: 8px;
    font-size: 16px;
    color: #ffffff;
}

/* Balance button */
.balance-section {
    margin-top: 20px;
}

.balance-btn {
    background: white;
    color: #009966;
    font-size: 18px;
    font-weight: bold;
    padding: 12px 25px;
    border-radius: 30px;
    border: none;
    outline: none;
    cursor: pointer;
    transition: background 0.3s ease;
}

.balance-btn:hover {
    background: #f1f1f1;
}

    /* Modern Form Container */
.reset-pin-container {
    background: #fff;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.1);
    max-width: 400px;
    margin: auto;
    text-align: center;
}

.title {
    font-size: 20px;
    font-weight: bold;
    color: #333;
}

.subtitle {
    font-size: 14px;
    color: #777;
    margin-bottom: 20px;
}

/* Input Fields */
.input-field {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 16px;
    outline: none;
    transition: border 0.3s ease;
}

.input-field:focus {
    border-color: #009966;
}

/* PIN Input Boxes */
.pin-input-container {
    display: flex;
    justify-content: center;
    gap: 10px;
}

.pin-box {
    width: 50px;
    height: 50px;
    text-align: center;
    font-size: 20px;
    font-weight: bold;
    border: 1px solid #ddd;
    border-radius: 8px;
    outline: none;
    transition: border 0.3s ease;
}

.pin-box:focus {
    border-color: #009966;
    box-shadow: 0px 0px 5px rgba(0, 153, 102, 0.5);
}

/* Button */
.btn-primary {
    width: 100%;
    padding: 12px;
    background: #009966;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    cursor: pointer;
    transition: background 0.3s ease;
    margin-top: 10px;
}

.btn-primary:hover {
    background: #007d50;
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
         <div class="content-body">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-4 col-xl-3">
                        <div class="profile-card">
                            <div class="profile-card-body">
                                <div class="user-info">
                                    <h3 class="user-name" style="color: white;">{{ Auth::user()->name }}</h3>
                                    <p class="user-email">{{ Auth::user()->email }}</p>
                                    <!-- <p class="user-account-number" id="accountNumber" onclick="copyAccountNumber()">
                                        Account Number: <span>{{ Auth::user()->account_number }}</span>
                                        <i class="fa fa-copy copy-icon"></i>
                                    </p>
                                    <p class="user-account-number">Wema Bank</p> -->
                                </div>
                                <div class="balance-section">
                                    <button class="balance-btn">
                                        ₦ {{ $balance ? $balance->balance : '0.00' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>


                    <div class="col-lg-8 col-xl-9">
                  <!--    <div class="card">
                            <div class="card-body">
                                <form action="#" class="form-profile">
                                    <div class="form-group">
                                        <textarea class="form-control" name="textarea" id="textarea" cols="30" rows="2" placeholder="Post a new message"></textarea>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <ul class="mb-0 form-profile__icons">
                                            <li class="d-inline-block">
                                                <button class="btn btn-transparent p-0 mr-3"><i class="fa fa-user"></i></button>
                                            </li>
                                            <li class="d-inline-block">
                                                <button class="btn btn-transparent p-0 mr-3"><i class="fa fa-paper-plane"></i></button>
                                            </li>
                                            <li class="d-inline-block">
                                                <button class="btn btn-transparent p-0 mr-3"><i class="fa fa-camera"></i></button>
                                            </li>
                                            <li class="d-inline-block">
                                                <button class="btn btn-transparent p-0 mr-3"><i class="fa fa-smile"></i></button>
                                            </li>
                                        </ul>
                                        <button class="btn btn-primary px-3 ml-4">Send</button>
                                    </div>
                                </form>
                            </div>
                        </div> -->

                        <div class="card">
                            <div class="container mt-4">
    <!-- Nav Tabs -->
    <ul class="nav nav-tabs" id="profileTabs">
        <li class="nav-item">
            <a class="nav-link active" id="reset-pin-tab" data-toggle="tab" href="#reset-pin">Reset PIN</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="update-profile-tab" data-toggle="tab" href="#update-profile">Update Profile</a>
        </li>
    </ul>

        <!-- Tab Content -->
        <div class="tab-content mt-3">
            <!-- Reset PIN Tab -->
            <div class="tab-pane fade show active" id="reset-pin">
                <div class="reset-pin-container">
                    <h3 class="title">Reset PIN</h3>
                    <p class="subtitle">Secure your transactions with a new PIN</p>

                    <form id="resetPinForm">
                        @csrf
                        <div class="form-group">
                            <label for="current_pin">Password</label>
                            <input type="password" class="form-control input-field" name="current_pin" id="current_pin" placeholder="Enter password" required>
                        </div>

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

                        <button type="submit" class="btn-primary" id="resetPinButton">Reset PIN</button>
                    </form>
                </div>
            </div>


            <!-- Update Profile Tab -->
            <div class="tab-pane fade" id="update-profile">
               <form id="updateProfileForm" enctype="multipart/form-data">
                    @csrf
                    <!-- <div class="form-group">
                        <label for="avatar">Avatar:</label>
                        <input type="file" class="form-control" name="avatar" id="avatar">
                    </div> -->
                    <div class="form-group">
                        <label for="name">Full Name:</label>
                        <input type="text" value="{{ Auth::user()->name }}" class="form-control" name="name" id="name" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number:</label>
                        <input type="text" value="{{ Auth::user()->mobile }}" class="form-control" name="mobile" id="phone" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" class="form-control" value="{{ Auth::user()->email }}" name="email" id="email" required>
                    </div>
                    <button type="submit" class="btn btn-success mb-2" id="updateProfileButton">Update Profile</button>
                </form>
                <br>
                <h4>Update Password</h5>
                <form id="updatePasswordForm">
                    @csrf
                    <div class="form-group">
                        <label for="old_password">Current Password:</label>
                        <input type="password" class="form-control" name="old_password" id="old_password" required>
                    </div>
                    <div class="form-group">
                        <label for="new_password">New Password:</label>
                        <input type="password" class="form-control" name="new_password" id="new_password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password:</label>
                        <input type="password" class="form-control" name="confirm_password" id="confirm_password" required>
                    </div>
                    <button type="submit" class="btn btn-primary" style="background: green;" id="updatePasswordButton">Update Password</button>
                </form>
            </div>
        </div>
    </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    <!--**********************************
        Scripts
    ***********************************-->
    
    <script>
    function copyAccountNumber() {
            let accountNumber = document.getElementById("accountNumber").innerText.replace("Account Number: ", "").trim();
            navigator.clipboard.writeText(accountNumber).then(() => {
                Swal.fire("Copied!", "Account number copied to clipboard.", "success");
            }).catch(() => {
                Swal.fire("Error", "Failed to copy account number.", "error");
            });
        }


        document.addEventListener("DOMContentLoaded", function() {
            function setupPinInputs(pinInputs, hiddenInput) {
                pinInputs.forEach((input, index) => {
                    input.addEventListener("input", () => {
                        if (input.value.length === 1 && index < pinInputs.length - 1) {
                            pinInputs[index + 1].focus();
                        }
                        updateHiddenInput(pinInputs, hiddenInput);
                    });

                    input.addEventListener("keydown", (e) => {
                        if (e.key === "Backspace" && input.value === "" && index > 0) {
                            pinInputs[index - 1].focus();
                        }
                    });
                });
            }

            function updateHiddenInput(pinInputs, hiddenInput) {
                hiddenInput.value = Array.from(pinInputs).map(input => input.value).join("");
            }

            // New PIN inputs
            const newPinInputs = document.querySelectorAll("#pin1, #pin2, #pin3, #pin4");
            const newPinHidden = document.getElementById("newPinHidden");
            setupPinInputs(newPinInputs, newPinHidden);

            // Confirm PIN inputs
            const confirmPinInputs = document.querySelectorAll("#confirmPin1, #confirmPin2, #confirmPin3, #confirmPin4");
            const confirmPinHidden = document.getElementById("confirmPinHidden");
            setupPinInputs(confirmPinInputs, confirmPinHidden);
        });


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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
      <script>
    $(document).ready(function () {
        $(".pin-input").on("input", function () {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 4);
        });

        $("#resetPinForm").submit(function (e) {
            e.preventDefault();
            $("#resetPinButton").html('Processing...').prop('disabled', true);

            $.ajax({
                url: "{{ route('reset.pin') }}",
                type: "POST",
                data: $(this).serialize(),
                success: function (response) {
                    Swal.fire("Success!", response.success, "success");
                    $("#resetPinForm")[0].reset();
                },
                error: function (xhr) {
                    let errorMessage = "";

                    if (xhr.responseJSON) {
                        // Check for validation errors
                        if (xhr.responseJSON.errors) {
                            $.each(xhr.responseJSON.errors, function (key, value) {
                                errorMessage += value[0] + "<br>";
                            });
                        } 
                        // Check if there's a general error message
                        else if (xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                    } else {
                        errorMessage = "An unknown error occurred.";
                    }

                    Swal.fire({
                        title: "Error!",
                        html: errorMessage, 
                        icon: "error"
                    });
                },
                complete: function () {
                    $("#resetPinButton").html('Reset PIN').prop('disabled', false);
                }
            });
        });
    });
    </script>
    <script>
    $(document).ready(function () {
        // **PROFILE UPDATE FORM**
        $("#updateProfileForm").submit(function (e) {
            e.preventDefault();
            let formData = new FormData(this);
            $("#updateProfileButton").html('Processing...').prop('disabled', true);

            $.ajax({
                url: "{{ route('update.profile') }}",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    Swal.fire("Success!", response.success, "success");
                },
                error: function (xhr) {
                    let errorMessage = "";
                    if (xhr.responseJSON?.errors) {
                        $.each(xhr.responseJSON.errors, function (key, value) {
                            errorMessage += value[0] + "<br>";
                        });
                    } else {
                        errorMessage = xhr.responseJSON?.message || "An error occurred.";
                    }
                    Swal.fire({ title: "Error!", html: errorMessage, icon: "error" });
                },
                complete: function () {
                    $("#updateProfileButton").html('Update Profile').prop('disabled', false);
                }
            });
        });

        // **PASSWORD UPDATE FORM**
        $("#updatePasswordForm").submit(function (e) {
            e.preventDefault();
            $("#updatePasswordButton").html('Processing...').prop('disabled', true);

            let newPassword = $("#new_password").val();
            let confirmPassword = $("#confirm_password").val();

            if (newPassword !== confirmPassword) {
                Swal.fire("Error!", "New Password and Confirm Password do not match.", "error");
                $("#updatePasswordButton").html('Update Password').prop('disabled', false);
                return;
            }

            $.ajax({
                url: "{{ route('update.password') }}",
                type: "POST",
                data: $(this).serialize(),
                success: function (response) {
                    Swal.fire("Success!", response.success, "success");
                    $("#updatePasswordForm")[0].reset();
                },
                error: function (xhr) {
                    let errorMessage = "";
                    if (xhr.responseJSON?.errors) {
                        $.each(xhr.responseJSON.errors, function (key, value) {
                            errorMessage += value[0] + "<br>";
                        });
                    } else {
                        errorMessage = xhr.responseJSON?.message || "An error occurred.";
                    }
                    Swal.fire({ title: "Error!", html: errorMessage, icon: "error" });
                },
                complete: function () {
                    $("#updatePasswordButton").html('Update Password').prop('disabled', false);
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