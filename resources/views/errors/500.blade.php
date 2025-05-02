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
                                <a class="text-center" href="index.html"> <h2>Server Error</h2></a>
                                <p>Oops! Something went wrong on our end. We're working to fix it — please try again shortly.</p>
                                
                                <p class="mt-5 login-form__footer">Dont have account? <a href="{{ route('register') }}" style="color: green;" >Sign Up</a> now</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    

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