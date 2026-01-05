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

      <div class="container-fluid col-xl-8 col-lg-10 col-md-12 p-4">
        <div class="container table-container">
            <div class="table-responsive">
                <div class="mb-4">
                    <div class="input-group">
                        <input type="text" id="userSearch" class="form-control" placeholder="Search by name, email, or mobile...">
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="button" id="searchBtn">
                                <i class="fa fa-search"></i> Search
                            </button>
                            <button class="btn btn-secondary" type="button" id="clearBtn">
                                <i class="fa fa-times"></i> Clear
                            </button>
                        </div>
                    </div>
                    <small class="text-muted">Press Enter to search or click the search button</small>
                </div>
                <table class="table table-striped table-bordered custom-table">
                    <thead>
                        <th>Customer Name</th>
                        <th>Customer Mobile</th>
                            <th>Customer Email</th>
                            <th>Account Status</th>
                            <th>Customer Balance</th>
                            <th>Customer Loan</th>
                            <th>Joined At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                            <tr>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->mobile }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->is_status ?? "N/A" }}</td>
                                <td>₦ {{ number_format($user->balance->balance, 2) }}
                                <td>₦ {{ number_format($user->balance->owe, 2) }}
                                </td>
                                <td>{{ $user->created_at->format('d M Y, h:i A') }}</td>
                               
                                <td>
                                <button class="btn btn-warning btn-sm edit-user" 
                                        data-id="{{ $user->id }}" 
                                        data-name="{{ $user->name }}" 
                                        data-mobile="{{ $user->mobile }}" 
                                        data-email="{{ $user->email }}">
                                    Edit
                                </button>
                                <a href="{{ url('admin/users/' . $user->id) }}">
                                    <button class="btn btn-primary btn-sm">View</button>
                                </a>
                            </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Pagination links -->
        @if ($users->hasPages())
            <div class="bootstrap-pagination">
                <nav>
                    <ul class="pagination">
                        {{-- Previous Page Link --}}
                        @if ($users->onFirstPage())
                            <li class="page-item disabled">
                                <a class="page-link"><span aria-hidden="true">&laquo;</span></a>
                            </li>
                        @else
                            <li class="page-item">
                                <a class="page-link" href="{{ $users->previousPageUrl() }}" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        @endif

                        {{-- Pagination Elements --}}
                        @foreach ($users->links()->elements[0] as $page => $url)
                            <li class="page-item {{ $users->currentPage() == $page ? 'active' : '' }}">
                                <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                            </li>
                        @endforeach

                        {{-- Next Page Link --}}
                        @if ($users->hasMorePages())
                            <li class="page-item">
                                <a class="page-link" href="{{ $users->nextPageUrl() }}" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        @else
                            <li class="page-item disabled">
                                <a class="page-link"><span aria-hidden="true">&raquo;</span></a>
                            </li>
                        @endif
                    </ul>
                </nav>
            </div>
        @endif

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
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
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
                    <label for="edit-user-status">Status</label>
                    <select id="edit-user-status" class="form-control" required>
                        <option value="" disabled selected>Select status</option>
                        <option value="ACTIVE">ACTIVE</option>
                        <option value="BLOCKED">BLOCKED</option>
                        <option value="SUSPENDED">SUSPENDED</option>
                    </select>
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
</div>

            <!-- #/ container -->
        </div>

    <!--**********************************
        Scripts
    ***********************************-->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script type="text/javascript">
    $(document).ready(function() {
        // Search functionality
        function searchUsers(query) {
            if (query.trim() === '') {
                // If search is empty, reload the page to show all users
                window.location.href = window.location.pathname;
                return;
            }

            $.ajax({
                url: window.location.pathname, // Uses the same route
                method: 'GET',
                data: { search: query },
                beforeSend: function() {
                    $('#searchBtn').html('<i class="fa fa-spinner fa-spin"></i> Searching...').prop('disabled', true);
                    $('tbody').html('<tr><td colspan="8" class="text-center">Searching...</td></tr>');
                },
                success: function(response) {
                    // Extract user data from the HTML response
                    let $html = $(response);
                    let $tableBody = $html.find('tbody');
                    
                    if ($tableBody.length && $tableBody.find('tr').length > 0) {
                        $('tbody').html($tableBody.html());
                        // Hide pagination when showing search results
                        $('.bootstrap-pagination').hide();
                    } else {
                        $('tbody').html('<tr><td colspan="8" class="text-center">No users found</td></tr>');
                        $('.bootstrap-pagination').hide();
                    }
                    
                    $('#searchBtn').html('<i class="fa fa-search"></i> Search').prop('disabled', false);
                    
                    // Re-attach edit user click handlers
                    attachEditHandlers();
                },
                error: function() {
                    alert('Error searching users. Please try again.');
                    $('#searchBtn').html('<i class="fa fa-search"></i> Search').prop('disabled', false);
                }
            });
        }

        function attachEditHandlers() {
            $('.edit-user').off('click').on('click', function() {
                let userId = $(this).data('id');
                let userName = $(this).data('name');
                let userMobile = $(this).data('mobile');
                let userEmail = $(this).data('email');
                let userStatus = $(this).data('is_status');

                $('#edit-user-id').val(userId);
                $('#edit-user-name').val(userName);
                $('#edit-user-mobile').val(userMobile);
                $('#edit-user-email').val(userEmail);
                $('#edit-user-status').val(userStatus);

                $.ajax({
                    url: `/admin/users/${userId}/edit`,
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
        }

        // Search button click
        $('#searchBtn').click(function() {
            let query = $('#userSearch').val();
            searchUsers(query);
        });

        // Clear button click
        $('#clearBtn').click(function() {
            $('#userSearch').val('');
            window.location.href = window.location.pathname;
        });

        // Search on Enter key
        $('#userSearch').keypress(function(e) {
            if (e.which === 13) {
                let query = $(this).val();
                searchUsers(query);
            }
        });

        // Initialize edit handlers for existing users
        attachEditHandlers();

        // Existing edit user form submission code
        $('#editUserForm').submit(function(e) {
            e.preventDefault();

            let userId = $('#edit-user-id').val();
            let formData = {
                _token: $('meta[name="csrf-token"]').attr('content'),
                name: $('#edit-user-name').val(),
                mobile: $('#edit-user-mobile').val(),
                email: $('#edit-user-email').val(),
                status: $('#edit-user-status').val(),
            };

            $.ajax({
                url: `/admin/users/${userId}/update`,
                method: 'POST',
                data: formData,
                beforeSend: function() {
                    $('#saveUserBtn').text('Updating...').prop('disabled', true);
                },
                success: function(response) {
                    alert(response.message);
                    location.reload();
                },
                error: function(xhr) {
                    let errorMessage = xhr.responseJSON?.message || 'An error occurred';
                    alert(errorMessage);
                    $('#saveUserBtn').text('Save Changes').prop('disabled', false);
                }
            });
        });
    });

    $(document).ready(function() {
    // When "Edit" button is clicked, open modal and load user data
    $('.edit-user').click(function() {
        let userId = $(this).data('id');
        let userName = $(this).data('name');
        let userMobile = $(this).data('mobile');
        let userEmail = $(this).data('email');
        let userStatus = $(this).data('is_status');

        $('#edit-user-id').val(userId);
        $('#edit-user-name').val(userName);
        $('#edit-user-mobile').val(userMobile);
        $('#edit-user-email').val(userEmail);
        $('#edit-user-status').val(userStatus);

        // Fetch the full user details via AJAX
        $.ajax({
            url: `/admin/users/${userId}/edit`,
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
            status: $('#edit-user-status').val(),
        };

        $.ajax({
            url: `/admin/users/${userId}/update`,
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