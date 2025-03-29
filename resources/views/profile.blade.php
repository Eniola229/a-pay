@include('components.header')

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
                        <div class="card">
                            <div class="card-body">
                                <div class="media align-items-center mb-4">
                                    <img class="mr-3" src="{{ Auth::user()->avatar }}" width="80" height="80" alt="">
                                    <div class="media-body">
                                        <h3 class="mb-0">{{ Auth::user()->name }}</h3>
                                    </div>
                                </div>
                                    <p class="text-muted mb-0">{{ Auth::user()->email }}</p>
                                <div class="row mb-5">
                                    
                                    <div class="col-12 text-center">
                                        <button class="btn btn-danger px-5">₦
                                    @if(!$balance)
                                        0.00
                                    @else
                                       {{ $balance->balance }}
                                    @endif</button>
                                    </div>
                                </div>

                               
                            </div>
                        </div>  
                    </div>
                    <div class="col-lg-8 col-xl-9">
  <!--                       <div class="card">
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
            <form id="resetPinForm" class="text-center">
                @csrf
                <div class="form-group text-left">
                    <label for="current_pin">Password:</label>
                    <input type="password" class="form-control" name="current_pin" id="current_pin" required>
                </div>
                <div class="form-group text-left">
                    <label for="new_pin">New PIN:</label>
                    <input type="password" class="form-control pin-input" maxlength="4" name="new_pin" id="new_pin" required>
                </div>
                <div class="form-group text-left">
                    <label for="confirm_pin">Confirm New PIN:</label>
                    <input type="password" class="form-control pin-input" maxlength="4" name="confirm_pin" id="confirm_pin" required>
                </div>
                
                <div class="text-center mt-3">
                    <button type="submit" class="btn btn-primary mb-2" id="resetPinButton">Reset PIN</button>
                </div>
            </form>

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
    @include('components.contact-us')
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