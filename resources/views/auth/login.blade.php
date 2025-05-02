 @include('components.header')


  <div class="login-form-bg h-100">
        <div class="container h-100">
            <div class="row justify-content-center h-100">
                <div class="col-xl-6">
                    <div class="form-input-content">
                        <div class="card login-form mb-0">
                            <div class="card-body pt-5">
                                <a class="text-center" href="index.html"> <h4>A-Pay | Login</h4></a>
                                @if($errors->any())
                                    <div class="alert alert-danger text-red-800 bg-red-200 p-4 rounded mb-4">
                                        <ul class="list-disc list-inside">
                                            @foreach($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                                @if($errors->has('code'))
                                    <div class="alert alert-danger text-red-800 bg-red-200 p-4 rounded mb-4">
                                        <ul class="list-disc list-inside">
                                            <li>{{ $errors->first('code') }}</li>
                                        </ul>
                                    </div>
                                @endif
                                <form class="mt-5 mb-5 login-input" action="{{ route('login') }}" method="post">
                                    @csrf
                                   <div class="form-group">
                                            <!-- Mobile Number Field -->
                                            <label for="mobile">Mobile Number</label>
                                           <input 
                                                type="text" 
                                                value="+234" 
                                                class="form-control" 
                                                id="mobile" 
                                                name="mobile" 
                                                placeholder="Enter your mobile number (e.g., +2348123456789)" 
                                                value="{{ old('mobile') }}" 
                                                required 
                                                autocomplete="off"
                                            >
                                            <small class="form-text text-muted">
                                                Please enter your mobile number starting with +234 (e.g., +2348123456789).
                                            </small>
                                            @error('mobile')
                                                <div class="text-danger">{{ $message }}</div>
                                            @enderror
                                        </div>
                                         <p class="mt-1 login-form__footer">Forgot Password? <a href="{{  url('forgot-password') }}" style="color: green;" >Click here</a></p>
                                        <div class="form-group">
                                            <!-- Password Field -->
                                            <label for="password">Password</label>
                                            <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                                           <!--  <small class="form-text text-muted">
                                                Your password must be at least 8 characters long.
                                            </small> -->
                                            @error('password')
                                                <div class="text-danger">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    <button class="btn login-form__btn submit w-100">Sign In</button>
                                </form>
                                <p class="mt-5 login-form__footer">Dont have account? <a href="{{ route('register') }}" style="color: green;" >Sign Up</a> now</p>
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
    <script src="plugins/common/common.min.js"></script>
    <script src="js/custom.min.js"></script>
    <script src="js/settings.js"></script>
    <script src="js/gleek.js"></script>
    <script src="js/styleSwitcher.js"></script>
</body>
</html>