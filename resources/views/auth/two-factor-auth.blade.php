 @include('components.header')

<style>
    .otp-input {
        width: 50px;
        height: 50px;
        text-align: center;
        font-size: 24px;
        font-weight: bold;
        margin: 0 5px;
        border: 2px solid #28a745;
        border-radius: 5px;
        outline: none;
        transition: all 0.3s ease;
    }
    .otp-input:focus {
        border-color: #218838;
        box-shadow: 0 0 5px rgba(40, 167, 69, 0.5);
    }
</style>
  <div class="login-form-bg h-100">
        <div class="container h-100">
            <div class="row justify-content-center h-100">
                <div class="col-xl-6">
                    <div class="form-input-content">
                        <div class="card login-form mb-0">
                            <div class="card-body pt-5">
                                <a class="text-center" href="index.html"> <h4>A-Pay | 2 Factor Authentication</h4></a>
                                <p>We've sent a code to your email (Dont forget the spam folder)
                                @if($errors->any())
                                    <div class="alert alert-danger text-red-800 bg-red-200 p-4 rounded mb-4">
                                        <ul class="list-disc list-inside">
                                            @foreach($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                                <form class="mt-5 mb-5 login-input" action="{{ url('verify-device') }}" method="post">
                                    @csrf
                                  <div class="form-group text-center">
                                        <label for="code" class="d-block">Enter Code</label>
                                        <div class="d-flex justify-content-center">
                                            <input type="text" class="form-control otp-input" style="border: 2px solid #28a745;" maxlength="1" id="code1" required>
                                            <input type="text" class="form-control otp-input" style="border: 2px solid #28a745;"  maxlength="1" id="code2" required>
                                            <input type="text" class="form-control otp-input" style="border: 2px solid #28a745;"  maxlength="1" id="code3" required>
                                            <input type="text" class="form-control otp-input" style="border: 2px solid #28a745;"  maxlength="1" id="code4" required>
                                        </div>
                                        <input type="hidden" name="code" id="hidden-code">
                                        <small class="form-text text-muted">
                                            Please enter the 4-digit code sent to your email.
                                        </small>
                                        @error('code')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                        
                                    <button class="btn login-form__btn submit w-100">Continue</button>
                                </form>
                                <p class="mt-5 login-form__footer">Dont have account? <a href="{{ route('register') }}" style="color: green;" >Sign Up</a> now</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@include('components.contact-us')
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const inputs = document.querySelectorAll(".otp-input");
        const hiddenCode = document.getElementById("hidden-code");

        inputs.forEach((input, index) => {
            input.addEventListener("input", (e) => {
                if (e.target.value.length === 1 && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
                updateHiddenInput();
            });

            input.addEventListener("keydown", (e) => {
                if (e.key === "Backspace" && index > 0 && !e.target.value) {
                    inputs[index - 1].focus();
                }
                updateHiddenInput();
            });
        });

        function updateHiddenInput() {
            hiddenCode.value = Array.from(inputs).map(input => input.value).join('');
        }
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