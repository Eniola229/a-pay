@include('components.header')

<div class="login-form-bg h-100">
    <div class="container h-100">
        <div class="row justify-content-center h-100">
            <div class="col-xl-6">
                <div class="form-input-content">
                    <div class="card login-form mb-0">
                        <div class="card-body pt-5">
                            <a class="text-center" href="index.html"> 
                                <h4>A-Pay | Block Account</h4>
                            </a>

                            @if($errors->any())
                                <div class="alert alert-danger p-4 rounded mb-4">
                                    <ul class="list-disc list-inside">
                                        @foreach($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if(session('success'))
                                <div class="alert alert-success p-4 rounded mb-4">
                                    {{ session('success') }}
                                </div>
                            @endif

                            <form class="mt-5 mb-5 login-input" action="{{ route('block.account') }}" method="post">
                                @csrf
                                
                                <!-- Email Field -->
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input 
                                        type="email" 
                                        class="form-control" 
                                        id="email" 
                                        name="email" 
                                        placeholder="Enter your registered email" 
                                        value="{{ old('email') }}" 
                                        required
                                    >
                                    @error('email')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Mobile Field -->
                                <div class="form-group mt-3">
                                    <label for="mobile">Mobile Number (e.g., +2348123456789)</label>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        id="mobile" 
                                        name="mobile" 
                                        placeholder="Enter your mobile number (e.g., +2348123456789)" 
                                        value="+234" 
                                        required
                                    >
                                    @error('mobile')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>

                                <button class="btn btn-danger w-100 mt-4">Block Account</button>
                            </form>

                            <p class="mt-4 text-center">Want to unblock later? <br>Contact customer care on WhatsApp <strong>09079916807</strong></p>

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