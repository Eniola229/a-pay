@include('components.header')

    <div class="login-form-bg h-100">
        <div class="container h-100">
            <div class="row justify-content-center h-100">
                <div class="col-xl-6">
                    <div class="form-input-content">
                        <div class="card login-form mb-0">
                            <div class="card-body pt-5">
                                
                                    <a class="text-center" href="index.html"> <h4>A-Pay | Create an account</h4></a>
        
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
                                        <input type="password" class="form-control" placeholder="Password" name="password" required>
                                     @error('password')
                                       <div class="alert alert-danger mb-2">{{ $message }}</div>
                                    @enderror                                   
                                    </div>
                                    <div class="form-group">
                                        <input type="password" class="form-control" placeholder="Confirm Password" name="password_confirmation" required>
                                    @error('password_confirmation')
                                       <div class="alert alert-danger mb-2">{{ $message }}</div>
                                    @enderror
                                    </div>
                                    <button class="btn login-form__btn submit w-100">Sign Up</button>
                                </form>
                                    <p class="mt-5 login-form__footer">Have account <a href="{{ route('login') }}" class="" style="color: green;">Sign In </a> now</p>
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
    document.querySelector('form').addEventListener('submit', function (event) {
        const mobileInput = document.querySelector('input[name="mobile"]');
        const mobileValue = mobileInput.value;

        // Check if the mobile number starts with +234 and has 13 digits
        if (!/^\+234[0-9]{10}$/.test(mobileValue)) {
            alert('Please enter a valid Nigerian phone number starting with +234 (e.g., +2348035906313).');
            event.preventDefault(); // Prevent form submission
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





