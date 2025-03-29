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
    <div class="container">
        <form id="notificationForm">
            @csrf
            <div class="mb-3">
                <label for="title" class="form-label">Title</label>
                <input type="text" class="form-control" id="title" name="title" required>
            </div>
            <div class="mb-3">
                <label for="details" class="form-label">Details</label>
                <textarea class="form-control" id="details" name="details" required></textarea>
            </div>
            <div class="mb-3">
                <label for="expiry_date" class="form-label">Expiry Date</label>
                <input type="date" class="form-control" id="expiry_date" name="expiry_date" required>
            </div>
            <div class="mb-3">
                <label for="links" class="form-label">Link</label>
                <input type="text" class="form-control" id="links" name="links">
            </div>
            <button type="submit" class="btn btn-primary" id="submitBtn">Add Notification</button>
        </form>
    </div>

    <div class="container table-container mt-4">
        <div class="table-responsive">
            <table class="table table-striped table-bordered custom-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Details</th>
                        <th>Expiry Date</th>
                        <th>Link</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="notificationTable">
                    @foreach($notifications as $notification)
                        <tr id="row-{{ $notification->id }}">
                            <td>{{ $notification->title }}</td>
                            <td>{{ $notification->details }}</td>
                            <td>{{ $notification->expiry_date }}</td>
                            <td>{{ $notification->links }}</td>
                            <td>
                                <button class="btn btn-danger btn-sm delete-notification" data-id="{{ $notification->id }}">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
        <!-- Pagination links -->
        @if ($notifications->hasPages())
            <div class="bootstrap-pagination">
                <nav>
                    <ul class="pagination">
                        {{-- Previous Page Link --}}
                        @if ($notifications->onFirstPage())
                            <li class="page-item disabled">
                                <a class="page-link"><span aria-hidden="true">&laquo;</span></a>
                            </li>
                        @else
                            <li class="page-item">
                                <a class="page-link" href="{{ $notifications->previousPageUrl() }}" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        @endif

                        {{-- Pagination Elements --}}
                        @foreach ($notifications->links()->elements[0] as $page => $url)
                            <li class="page-item {{ $notifications->currentPage() == $page ? 'active' : '' }}">
                                <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                            </li>
                        @endforeach

                        {{-- Next Page Link --}}
                        @if ($notifications->hasMorePages())
                            <li class="page-item">
                                <a class="page-link" href="{{ $notifications->nextPageUrl() }}" aria-label="Next">
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
            <!-- #/ container -->
        </div>

    <!--**********************************
        Scripts
    ***********************************-->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    // Handle form submission
    $('#notificationForm').submit(function(e) {
        e.preventDefault();
        let formData = $(this).serialize();
        let submitBtn = $('#submitBtn');

        // Clear previous error messages
        $('.error-message').text('');

        submitBtn.prop('disabled', true).text('Processing...'); // Show processing state

        $.ajax({
            url: "{{ route('notifications.store') }}",
            method: "POST",
            data: formData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // CSRF Token
            },
            success: function(response) {
                if(response.success) {
                    $('#notificationTable').append(`
                        <tr id="row-${response.notification.id}">
                            <td>${response.notification.title}</td>
                            <td>${response.notification.details}</td>
                            <td>${response.notification.expiry_date}</td>
                            <td>${response.notification.links}</td>
                            <td>
                                <button class="btn btn-danger btn-sm delete-notification" data-id="${response.notification.id}">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    `);
                    $('#notificationForm')[0].reset();
                    Swal.fire("Success!", "Notification added successfully!", "success");
                } else {
                    Swal.fire("Error!", "Something went wrong!", "error");
                }
            },
            error: function(xhr) {
                if(xhr.status === 422) { // Laravel validation error
                    let errors = xhr.responseJSON.errors;
                    if(errors.title) $('#titleError').text(errors.title[0]);
                    if(errors.details) $('#detailsError').text(errors.details[0]);
                    if(errors.expiry_date) $('#expiryDateError').text(errors.expiry_date[0]);
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    Swal.fire("Database Error!", xhr.responseJSON.message, "error");
                } else {
                    Swal.fire("Error!", "Failed to add notification!", "error");
                }
            },
            complete: function() {
                submitBtn.prop('disabled', false).text('Add Notification'); // Reset button
            }
        });
    });

    // Handle delete
    $(document).on('click', '.delete-notification', function() {
        let id = $(this).data('id');
        let row = $("#row-" + id);

        Swal.fire({
            title: "Are you sure?",
            text: "You won't be able to recover this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, delete it!",
            cancelButtonText: "Cancel"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ url('/a-pay/admin/notifications') }}/" + id,
                    method: "DELETE",
                    data: { _token: "{{ csrf_token() }}" },
                    success: function(response) {
                        if(response.success) {
                            row.remove();
                            Swal.fire("Deleted!", "Notification has been removed.", "success");
                        } else {
                            Swal.fire("Error!", "Could not delete!", "error");
                        }
                    },
                    error: function(xhr) {
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            Swal.fire("Database Error!", xhr.responseJSON.message, "error");
                        } else {
                            Swal.fire("Error!", "Failed to delete!", "error");
                        }
                    }
                });
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