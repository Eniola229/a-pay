@include('components.header')
 <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <div class="login-form-bg h-100">
        <div class="container h-100">
            <div class="row justify-content-center h-100">
                <div class="col-xl-6">
                    <div class="form-input-content">
                        <div class="card login-form mb-0">
                            <div class="card-body pt-5">
                                
                                    <a class="text-center" href="index.html"> <h4>A-Pay | Create an account</h4></a>
                                    @if(session('error'))
                                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                            <strong>Error!</strong> {{ session('error') }}
                                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                    @endif

                                <form class="mt-5 mb-5 login-input" action="{{ route('register') }}" method="post">
                                    @csrf
                                    <div class="form-group">
                                        <input type="text" class="form-control"  placeholder="Name" name="name" value="{{ old('name') }}" required>
                                    @error('name')
                                       <div class="alert alert-danger mb-2">{{ $message }}</div>
                                    @enderror
                                    </div>
                                    <div class="form-group">
                                        <input type="email" class="form-control"  placeholder="Email" name="email" value="{{ old('email') }}" required>
                                    @error('email')
                                       <div class="alert alert-danger mb-2">{{ $message }}</div>
                                    @enderror
                                    </div>
                                    <div class="form-group">
                                       <input
                                            type="tel"
                                            class="form-control"
                                            value="+234" 
                                            placeholder="Phone Number (e.g., +2340000000000)"
                                            name="mobile"
                                            value="{{ old('mobile') }}"
                                            pattern="\+234[0-9]{10}"
                                            title="Please enter a valid Nigerian phone number starting with +234 (e.g., +2348035906313)"
                                            required
                                        >
                                    @error('mobile')
                                       <div class="alert alert-danger mb-2">{{ $message }}</div>
                                    @enderror
                                    </div>
                                    <div class="form-group">
                                       <input
                                            type="tel"
                                            class="form-control"
                                            placeholder="Referer Code (optional)"
                                            name="referer_mobile"
                                            value="{{ old('referer_mobile') }}"
                                            pattern="\+234[0-9]{10}"
                                            title="Please enter a valid Nigerian phone number starting with +234 (e.g., +2348035906313)"
                                            readonly
                                        >
                                    @error('referer_mobile')
                                       <div class="alert alert-danger mb-2">{{ $message }}</div>
                                    @enderror
                                    </div>
                                   <div class="form-group">
                                        <input type="password" class="form-control" id="password" placeholder="Password" name="password" required>
                                        <small id="passwordStrength" class="text-muted"></small>
                                        @error('password')
                                            <div class="alert alert-danger mb-2">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <input type="password" class="form-control" id="password_confirmation" placeholder="Confirm Password" name="password_confirmation" required>
                                        <small id="passwordMatch" class="text-muted"></small>
                                        @error('password_confirmation')
                                            <div class="alert alert-danger mb-2">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <button class="btn login-form__btn submit w-100">Sign Up</button>
                                </form>
                                    <p class="mt-5 login-form__footer">Have account? <a href="{{ route('login') }}" class="" style="color: green;">Sign In </a> now</p>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
<script>
document.addEventListener("DOMContentLoaded", function() {
    const params = new URLSearchParams(location.search);
    const raw = params.get("r_c");
    if (!raw) return;

    const normalizeRc = (val) => {
        let s = String(val).trim().replace(/\s+/g, '');

        // If already perfect, keep it
        if (/^\+234\d{10}$/.test(s)) return s;

        // Remove one or MORE leading 234s (with or without +), leading zeros, keep digits only
        s = s.replace(/^(\+?234)+/i, '');
        s = s.replace(/^0+/, '');
        s = s.replace(/\D/g, '');

        // Keep the last 10 digits (Nigerian local number)
        s = s.slice(-10);

        return s ? `+234${s}` : '';
    };

    const formatted = normalizeRc(raw);
    const input = document.querySelector('input[name="referer_mobile"]');
    if (input && formatted) input.value = formatted;
});
</script>

<script>
    document.querySelector('form').addEventListener('submit', function (event) {
        const mobileInput = document.querySelector('input[name="mobile"]');
        const mobileValue = mobileInput.value;

        // Check if the mobile number starts with +234 and has 13 digits
        if (!/^\+234[0-9]{10}$/.test(mobileValue)) {
            alert('Please enter a valid Nigerian phone number starting with +234 (e.g., +2348035906313).');
            event.preventDefault(); // Prevent form submission
        }
    });

    $(document).ready(function () {
    $("#password").on("keyup", function () {
        let password = $(this).val();
        let strengthText = "";
        let strengthColor = "";

        // Check strength conditions
        if (password.length < 8) {
            strengthText = "Too short (Min: 8 characters)";
            strengthColor = "red";
        } else if (!/[A-Z]/.test(password)) {
            strengthText = "Add at least one uppercase letter (A-Z)";
            strengthColor = "orange";
        } else if (!/[a-z]/.test(password)) {
            strengthText = "Add at least one lowercase letter (a-z)";
            strengthColor = "orange";
        } else if (!/[0-9]/.test(password)) {
            strengthText = "Add at least one number (0-9)";
            strengthColor = "orange";
        } else if (!/[^A-Za-z0-9]/.test(password)) {
            strengthText = "Add at least one special character (!@#$%^&*)";
            strengthColor = "orange";
        } else {
            strengthText = "Strong Password";
            strengthColor = "green";
        }

        $("#passwordStrength").text(strengthText).css("color", strengthColor);
    });

    // Check if passwords match
    $("#password, #password_confirmation").on("keyup", function () {
        let password = $("#password").val();
        let confirmPassword = $("#password_confirmation").val();

        if (confirmPassword.length > 0) {
            if (password === confirmPassword) {
                $("#passwordMatch").text("Passwords match").css("color", "green");
            } else {
                $("#passwordMatch").text("Passwords do not match").css("color", "red");
            }
        } else {
            $("#passwordMatch").text("");
        }
    });
});

</script>
    

    <!--**********************************
        Scripts
    ***********************************-->
    <script src="plugins/common/common.min.js"></script>
    <script src="js/custom.min.js"></script>
    <script src="js/settings.js"></script>
    <script src="js/gleek.js"></script>
    <script src="js/styleSwitcher.js"></script>
</body>
</html>





