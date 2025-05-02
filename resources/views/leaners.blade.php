<!DOCTYPE html>
<html lang="en">
<head>
    <title>Learners.Com - Learning Made Easy</title>
    <meta charset="utf-8">
    
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    
    <link href="https://fonts.googleapis.com/css?family=Montserrat:200,300,400,500,600,700,800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">


    <link rel="stylesheet" href="asset/css/animate.css">
    
    <link rel="stylesheet" href="asset/css/owl.carousel.min.css">
    <link rel="stylesheet" href="asset/css/owl.theme.default.min.css">
    <link rel="stylesheet" href="asset/css/magnific-popup.css">

    
    <link rel="stylesheet" href="asset/css/flaticon.css">
    <link rel="stylesheet" href="asset/css/style.css">
    <meta name="csrf-token" content="{{ csrf_token() }}">

</head>
<body>
<section class="ftco-section bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="wrapper px-md-4">
                    <div class="row no-gutters">
                        <div class="col-md-8 mx-auto">
                            <div class="contact-wrap w-100 p-md-5 p-4 shadow rounded" style="background-color: #fff;">
                                <h3 class="mb-4" style="color: green;">Register as a Learner</h3>
                                <form method="POST" id="registrationForm" action="{{ route('register-leaners') }}">
                                    @csrf
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label style="color: black;">First Name</label>
                                            <input name="first_name" class="form-control" required>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label style="color: black;">Last Name</label>
                                            <input name="last_name" class="form-control" required>
                                        </div>
                                    </div>

                                    <div class="form-row">
                                        <div class="form-group col-md-4">
                                            <label style="color: black;">Date of Birth</label>
                                            <input type="date" name="age" class="form-control" required>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label style="color: black;">Sex</label>
                                            <select name="sex" class="form-control" required>
                                                <option value="">Select</option>
                                                <option value="Male">Male</option>
                                                <option value="Female">Female</option>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label style="color: black;">Are you a student?</label><br>
                                            <input type="checkbox" name="is_student"> Yes
                                        </div>
                                    </div>

                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label style="color: black;">Country</label>
                                            <input name="country" class="form-control" required>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label style="color: black;">State</label>
                                            <input name="state" class="form-control" required>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label style="color: black;">Course of Study</label>
                                        <select name="course_of_study" class="form-control" required>
                                            <option value="">Select Course</option>
                                            <option value="Full Stack Web Development">Full Stack Web Development</option>
                                        </select>
                                    </div>

                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label style="color: black;">Email</label>
                                            <input type="email" name="email" class="form-control" required>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label style="color: black;">WhatsApp Number</label>
                                            <input type="number" name="whatsapp" class="form-control" required>
                                        </div>
                                    </div>

                                    @if($amount > 0)

                                        <div class="form-group">
                                            <p class="mt-2"><strong class="text-danger">Registration Fee: ₦{{ number_format($amount) }}</strong></p>
                                        </div>
                                    @else
                                        <div class="alert alert-info">
                                            <strong>You're in the first 10 – registration is FREE! 🎉</strong>
                                        </div>
                                    @endif

                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary btn-block">Register</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <!-- Optional map/image -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://js.paystack.co/v1/inline.js"></script>
<script>
    $('#registrationForm').on('submit', function(e) {
        e.preventDefault();

        let form = $(this);
        let formData = form.serializeArray();
        let learnerData = {};
        formData.forEach(field => learnerData[field.name] = field.value);

        // Set registration fee
        let amount = {{ $amount ?? 0 }};

        // Show loading modal while processing
        Swal.fire({
            title: 'Processing...',
            text: 'Please wait while we complete your registration.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();  // Show loading indicator
            }
        });

        if (amount > 0) {
            let handler = PaystackPop.setup({
                key: '{{ env("PAYSTACK_PUBLIC_KEY") }}',
                email: learnerData.email,
                amount: amount * 100,  // Paystack requires amount in kobo
                currency: "NGN",
                callback: function(response) {
                    learnerData.reference = response.reference;

                    // Submit form with reference after payment success
                    $.ajax({
                        url: "{{ route('register-leaners') }}",
                        method: "POST",
                        data: learnerData,
                        success: function(res) {
                            Swal.close(); // Close loading modal
                            Swal.fire('Success 🎉', res.message, 'success');
                            form[0].reset();
                        },
                        error: function(err) {
                            Swal.close(); // Close loading modal
                            Swal.fire('Error 😢', err.responseJSON?.message || 'Something went wrong', 'error');
                        }
                    });
                },
                onClose: function() {
                    Swal.close(); // Close the loading modal if payment is cancelled
                    Swal.fire('Cancelled', 'You didn\'t complete the payment.', 'info');
                }
            });

            handler.openIframe();
        } else {
            // Free registration — no Paystack needed, just process the form
            $.ajax({
                url: "{{ route('register-leaners') }}",
                method: "POST",
                data: learnerData,
                success: function(res) {
                    Swal.close(); // Close loading modal
                    Swal.fire('Success 🎉', res.message, 'success');
                    form[0].reset();
                },
                error: function(err) {
                    Swal.close(); // Close loading modal
                    Swal.fire('Error 😢', err.responseJSON?.message || 'Something went wrong', 'error');
                }
            });
        }
    });
</script>


</body>
</html>