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
<style>
.receipt-container {
    font-family: 'Arial', sans-serif;
    background: #fff;
    padding: 20px;
    border-radius: 10px;
    max-width: 380px;
    margin: auto;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

.receipt-header {
    margin-bottom: 10px;
}

.receipt-logo {
    width: 80px;
    border-radius: 20px;
}

.amount {
    font-size: 24px;
    font-weight: bold;
    color: #28a745;
}

.status {
    font-size: 20px;
    font-weight: bold;
    color: #28a745; /* Default green for success */
}

.status.pending {
    color: #ffc107;
}

.status.failed {
    color: #dc3545;
}

.table-borderless td {
    font-size: 14px;
    padding: 5px 0;
    color: black;
}

.support-text {
    font-weight: bold;
    font-size: 14px;
    color: black;
}

.text-muted {
    font-size: 12px;
    color: black;
}

/* Centering the Table */
    .table-container {
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .custom-table {
        background: white;
        border-radius: 0px;
        overflow: hidden;
        box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.1);
    }

    .custom-table th {
        background: #28a745;
        color: white;
        text-align: center;
        font-size: 16px;
        padding: 12px;
    }

    .custom-table td {
        text-align: center;
        vertical-align: middle;
        padding: 10px;
        font-size: 14px;
    }

    .custom-table tbody tr:hover {
        background: #f8f9fa;
    }

    .badge {
        padding: 6px 12px;
        font-size: 12px;
        border-radius: 5px;
    }

    .btn-sm {
        font-size: 14px;
        padding: 6px 12px;
        border-radius: 6px;
        transition: all 0.3s ease-in-out;
    }

    .btn-sm:hover {
        transform: scale(1.05);
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

    <div class="container-fluid col-xl-10 col-lg-12 p-4">
        <div class="table-responsive">
            <table class="table table-striped table-bordered custom-table">
                <thead>
                    <tr>
                        <th>Customer Name</th>
                        <th>Mobile</th>
                        <th>Email</th>
                        <th>Balance</th>
                        <th>Owe</th>
                        <th>Loan Amount</th>
                        <th>Loan For</th>
                        <th>Status</th>
                        <th>Repayment Status</th>
                        <th>Requested At</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($loans as $loan)
                        <tr>
                            <td>{{ $loan->user->name }}</td>
                            <td>{{ $loan->user->mobile }}</td>
                            <td>{{ $loan->user->email }}</td>
                            <td>₦{{ number_format($loan->user->balance->balance ?? 0, 2) }}</td>
                            <td>₦{{ number_format($loan->user->balance->owe ?? 0, 2) }}</td>
                            <td>₦{{ number_format($loan->amount, 2) }}</td>
                            <td>{{ $loan->for ?? 'N/A' }}</td>
                            <td><span class="badge badge-{{ $loan->status === 'approved' ? 'success' : ($loan->status === 'pending' ? 'warning' : 'danger') }}">{{ strtoupper($loan->status) }}</span></td>
                            <td><span class="badge badge-{{ $loan->repayment_status === 'PAID' ? 'success' : ($loan->repayment_status === 'NOT PAID FULL' ? 'warning' : 'danger') }}">{{ strtoupper($loan->repayment_status) }}</span></td>
                            <td>{{ $loan->created_at->format('d M Y, h:i A') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="mt-4">
            {{ $loans->links() }}
        </div>
    </div>


        <!-- Receipt Modal -->
<!--         <div class="modal fade" id="receiptModal" tabindex="-1" aria-labelledby="receiptModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="receiptModalLabel">Transaction Receipt</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="receipt border p-4 rounded shadow-sm bg-white">
                            <h4 class="text-center mb-3">AfricPay Transaction Receipt</h4>
                            <hr>
                            <p><strong>Date:</strong> <span id="receipt-date"></span></p>
                            <p><strong>Description:</strong> <span id="receipt-description"></span></p>
                            <p><strong>Amount:</strong> <span id="receipt-amount"></span></p>
                            <p><strong>Status:</strong> <span id="receipt-status"></span></p>
                            <hr>
                            <p class="text-center">Thank you for using AfricPay!</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
 -->

<!-- Edit User Modal -->
<!-- <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editUserForm">
                    @csrf
                    <input type="hidden" id="edit-user-id">
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" id="edit-user-name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Mobile</label>
                        <input type="text" id="edit-user-mobile" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" id="edit-user-email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Balance</label>
                        <input type="text" id="edit-user-balance" class="form-control" readonly>
                    </div>
                    <button type="submit" class="btn btn-primary w-100" id="saveUserBtn">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div> -->

            <!-- #/ container -->
        </div>

    <!--**********************************
        Scripts
    ***********************************-->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- <script type="text/javascript">
    $(document).ready(function() {
    // When "Edit" button is clicked, open modal and load user data
    $('.edit-user').click(function() {
        let userId = $(this).data('id');
        let userName = $(this).data('name');
        let userMobile = $(this).data('mobile');
        let userEmail = $(this).data('email');

        $('#edit-user-id').val(userId);
        $('#edit-user-name').val(userName);
        $('#edit-user-mobile').val(userMobile);
        $('#edit-user-email').val(userEmail);

        // Fetch the full user details via AJAX
        $.ajax({
            url: `/a-pay/admin/users/${userId}/edit`,
            method: 'GET',
            dataType: 'json',
            beforeSend: function() {
                $('#saveUserBtn').text('Loading...').prop('disabled', true);
            },
            success: function(response) {
                let balanceAmount = response.balance ? parseFloat(response.balance.balance) || 0 : 0;
                $('#edit-user-balance').val('₦ ' + balanceAmount.toFixed(2));
                $('#editUserModal').modal('show');
                $('#saveUserBtn').text('Save Changes').prop('disabled', false);
            },
            error: function() {
                alert('Error fetching user details');
                $('#saveUserBtn').text('Save Changes').prop('disabled', false);
            }
        });
    });

    // Handle form submission to update user details
    $('#editUserForm').submit(function(e) {
        e.preventDefault();

        let userId = $('#edit-user-id').val();
        let formData = {
            _token: $('meta[name="csrf-token"]').attr('content'),
            name: $('#edit-user-name').val(),
            mobile: $('#edit-user-mobile').val(),
            email: $('#edit-user-email').val(),
        };

        $.ajax({
            url: `/a-pay/admin/users/${userId}/update`,
            method: 'POST',
            data: formData,
            beforeSend: function() {
                $('#saveUserBtn').text('Updating...').prop('disabled', true);
            },
            success: function(response) {
                alert(response.message);
                location.reload(); // Refresh the page
            },
            error: function(xhr) {
                let errorMessage = xhr.responseJSON?.message || 'An error occurred';
                alert(errorMessage);
                $('#saveUserBtn').text('Save Changes').prop('disabled', false);
            }
        });
    });
});

</script> -->
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