@include('components.header')
<meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Paystack inline script -->
    <script src="https://js.paystack.co/v1/inline.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Include Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
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

.stats-box {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    padding: 15px;
    margin: 10px 0;
}

.stats-number {
    font-size: 32px;
    font-weight: bold;
}

.stats-label {
    font-size: 14px;
    margin-top: 5px;
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

.editor-container {
    background: #fff;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.1);
}

.text-editor {
    width: 100%;
    min-height: 200px;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 16px;
    outline: none;
    resize: vertical;
    transition: border 0.3s ease;
}

.text-editor:focus {
    border-color: #009966;
}

.btn-send {
    background: #009966;
    color: white;
    border: none;
    border-radius: 8px;
    padding: 12px 30px;
    font-size: 16px;
    cursor: pointer;
    transition: background 0.3s ease;
    margin-top: 15px;
}

.btn-send:hover {
    background: #007d50;
}

.btn-alert {
    background: #ff6b6b;
    color: white;
    border: none;
    border-radius: 8px;
    padding: 12px 30px;
    font-size: 16px;
    cursor: pointer;
    transition: background 0.3s ease;
}

.btn-alert:hover {
    background: #ee5a52;
}

.char-counter {
    text-align: right;
    color: #777;
    font-size: 14px;
    margin-top: 5px;
}

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
        @include('components.admin-sidenav')
        <!--**********************************
            Sidebar end
        ***********************************-->

        <!--**********************************
            Content body start
        ***********************************-->

<div class="content-body">
    <div class="container-fluid">
        <div class="row">
            <!-- Stats Sidebar -->
            <div class="col-lg-4 col-xl-3">
                <div class="profile-card">
                    <div class="profile-card-body">
                        <div class="user-info">
                            <h3 class="user-name" style="color: white;">📊 Newsletter Stats</h3>
                        </div>
                        <div class="stats-box">
                            <div class="stats-number">{{ $eligibleUsers }}</div>
                            <div class="stats-label">Eligible Users</div>
                            <small style="color: #e0e0e0;">(Registered from Dec 1, 2025)</small>
                        </div>
                        <div class="stats-box">
                            <div class="stats-number">{{ $lowBalanceUsers }}</div>
                            <div class="stats-label">Low Balance Users</div>
                            <small style="color: #e0e0e0;">(Balance < ₦100)</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Newsletter Editor -->
            <div class="col-lg-8 col-xl-9">
                <!-- Success/Error Messages -->
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <strong>✅ Success!</strong> {{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>❌ Error!</strong> {{ session('error') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                <!-- Low Balance Alert Button -->
                <div class="card mb-3">
                    <div class="card-body">
                        <h5><i class="fa fa-bell text-warning"></i> Low Balance Alert</h5>
                        <p>Send a friendly reminder to users with balance below ₦100</p>
                        <form action="{{ route('admin.newsletter.low-balance') }}" method="POST" id="lowBalanceForm">
                            @csrf
                            <button type="submit" class="btn-alert" onclick="return confirm('Send low balance alert to {{ $lowBalanceUsers }} users?')">
                                <i class="fa fa-paper-plane"></i> Send Low Balance Alert
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Newsletter Editor -->
                <div class="card">
                    <div class="card-body editor-container">
                        <h4><i class="fa fa-envelope"></i> Send Newsletter</h4>
                        <p class="text-muted">Compose and send WhatsApp newsletter to all eligible users</p>
                        
                        <form action="{{ route('admin.newsletter.send') }}" method="POST" id="newsletterForm">
                            @csrf
                            <div class="form-group">
                                <label for="message">Newsletter Message</label>
                                <textarea 
                                    name="message" 
                                    id="message" 
                                    class="text-editor" 
                                    placeholder="Type your newsletter message here..."
                                    maxlength="1000"
                                    required
                                ></textarea>
                                <div class="char-counter">
                                    <span id="charCount">0</span> / 1000 characters
                                </div>
                                @error('message')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <button type="submit" class="btn-send" onclick="return confirm('Send newsletter to {{ $eligibleUsers }} users?')">
                                <i class="fa fa-paper-plane"></i> Send Newsletter to {{ $eligibleUsers }} Users
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Character counter
document.getElementById('message').addEventListener('input', function() {
    const charCount = this.value.length;
    document.getElementById('charCount').textContent = charCount;
});

// Form submission with loading state
document.getElementById('newsletterForm').addEventListener('submit', function(e) {
    const btn = this.querySelector('.btn-send');
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Sending...';
    btn.disabled = true;
});

document.getElementById('lowBalanceForm').addEventListener('submit', function(e) {
    const btn = this.querySelector('.btn-alert');
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Sending...';
    btn.disabled = true;
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