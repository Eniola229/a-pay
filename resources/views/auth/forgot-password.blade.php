 @include('components.header')
  <div class="login-form-bg h-100">
        <div class="container h-100">
            <div class="row justify-content-center h-100">
                <div class="col-xl-6">
                    <div class="form-input-content">
                        <div class="card login-form mb-0">
                            <div class="card-body pt-5">
                                <a class="text-center" href="index.html"> <h4>A-Pay | Forget Password</h4></a>
                                @if($errors->any())
                                    <div class="alert alert-danger text-red-800 bg-red-200 p-4 rounded mb-4">
                                        <ul class="list-disc list-inside">
                                            @foreach($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                                @if(session('status'))
                                    <div class="alert alert-success text-green-800 bg-green-200 p-4 rounded mb-4">
                                        {{ session('status') }}
                                    </div>
                                @endif

                                <form class="mt-5 mb-5 login-input" action="{{ route('password.email') }}" method="post">
                                    @csrf
                                   <div class="form-group">
                                            <!-- email Number Field -->
                                            <label for="email">Email Address</label>
                                           <input 
                                                type="email" 
                                                 
                                                class="form-control" 
                                                id="email" 
                                                name="email" 
                                                placeholder="Enter your email" 
                                                value="{{ old('email') }}" 
                                                required 
                                                autocomplete="off"
                                            >
                                            <small class="form-text text-muted">
                                                Please enter your email address
                                            </small>
                                            @error('email')
                                                <div class="text-danger">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    <button class="btn login-form__btn submit w-100">Send Password Reset Link</button>
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