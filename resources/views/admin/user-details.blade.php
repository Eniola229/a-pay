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

/* Account number with copy effect */
.user-account-number {
    font-size: 16px;
    color: #ffffff;
    background: rgba(255, 255, 255, 0.2);
    padding: 8px 15px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    cursor: pointer;
    transition: background 0.3s ease;
}

.user-account-number:hover {
    background: rgba(255, 255, 255, 0.3);
}

.copy-icon {
    margin-left: 8px;
    font-size: 16px;
    color: #ffffff;
}

/* Balance button */
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

    /* Modern Form Container */
.reset-pin-container {
    background: #fff;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.1);
    max-width: 400px;
    margin: auto;
    text-align: center;
}

.title {
    font-size: 20px;
    font-weight: bold;
    color: #333;
}

.subtitle {
    font-size: 14px;
    color: #777;
    margin-bottom: 20px;
}

/* Input Fields */
.input-field {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 16px;
    outline: none;
    transition: border 0.3s ease;
}

.input-field:focus {
    border-color: #009966;
}

/* PIN Input Boxes */
.pin-input-container {
    display: flex;
    justify-content: center;
    gap: 10px;
}

.pin-box {
    width: 50px;
    height: 50px;
    text-align: center;
    font-size: 20px;
    font-weight: bold;
    border: 1px solid #ddd;
    border-radius: 8px;
    outline: none;
    transition: border 0.3s ease;
}

.pin-box:focus {
    border-color: #009966;
    box-shadow: 0px 0px 5px rgba(0, 153, 102, 0.5);
}

/* Button */
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
/* Add this to your existing <style> section */

/* Scrollable Table Container */
.table-container {
    max-height: 600px; /* Adjust height as needed */
    overflow-y: auto;
    overflow-x: auto;
    border: 1px solid #ddd;
    border-radius: 8px;
    background: white;
}

/* Make table header sticky */
.table-container table {
    margin-bottom: 0;
}

.table-container thead th {
    position: sticky;
    top: 0;
    background: #009966;
    color: white;
    z-index: 10;
    box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.1);
}

/* Improve table styling */
.table-container .table {
    border-collapse: separate;
    border-spacing: 0;
}

.table-container .table tbody tr:hover {
    background-color: #f8f9fa;
}

/* Custom scrollbar styling (optional) */
.table-container::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.table-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.table-container::-webkit-scrollbar-thumb {
    background: #009966;
    border-radius: 10px;
}

.table-container::-webkit-scrollbar-thumb:hover {
    background: #007d50;
}

/* Responsive table on mobile */
@media (max-width: 768px) {
    .table-container {
        max-height: 400px;
    }
    
    .table-container table {
        font-size: 12px;
    }
    
    .table-container thead th,
    .table-container tbody td {
        padding: 8px 5px;
    }
}
.alert {
    border-radius: 8px;
    padding: 15px 20px;
    margin-bottom: 20px;
    border: none;
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.alert-success {
    background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
    color: white;
}

.alert-danger {
    background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);
    color: white;
}

.alert i {
    margin-right: 8px;
    font-size: 18px;
}

.alert .close {
    color: white;
    opacity: 0.8;
    text-shadow: none;
}

.alert .close:hover {
    opacity: 1;
}

.alert ul {
    padding-left: 20px;
}

.document-card {
    background: rgba(255, 255, 255, 0.1);
    padding: 15px;
    border-radius: 8px;
}

.document-preview {
    background: rgba(0, 0, 0, 0.3);
    padding: 10px;
    border-radius: 5px;
    margin-top: 10px;
}

.badge {
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 12px;
}

.badge-success { background: #48bb78; }
.badge-danger { background: #f56565; }
.badge-warning { background: #ed8936; }
.badge-info { background: #4299e1; }

.modal-content {
    border-radius: 8px;
}

    .edit-profile-btn {
        margin-top: 15px;
        padding: 8px 16px;
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
    }
    
    .edit-profile-btn:hover {
        background-color: #0056b3;
    }
    
    .modal {
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
    }
    
    .modal-content {
        background-color: #fff;
        margin: 5% auto;
        padding: 30px;
        border-radius: 10px;
        width: 90%;
        max-width: 500px;
        position: relative;
        max-height: 90vh;
        overflow-y: auto;
    }
    
    .close {
        position: absolute;
        right: 20px;
        top: 15px;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        color: #aaa;
    }
    
    .close:hover {
        color: #000;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
        color: #333;
    }
    
    .form-group input,
    .form-group select {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 14px;
    }
    
    .form-group select {
        cursor: pointer;
        background-color: white;
    }
    
    .form-group select:focus {
        outline: none;
        border-color: #007bff;
    }
    
    .form-actions {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        margin-top: 25px;
    }
    
    .btn-cancel, .btn-save {
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-cancel {
        background-color: #6c757d;
        color: white;
    }
    
    .btn-cancel:hover {
        background-color: #5a6268;
    }
    
    .btn-save {
        background-color: #28a745;
        color: white;
        position: relative;
    }
    
    .btn-save:hover:not(:disabled) {
        background-color: #218838;
    }
    
    .btn-save:disabled {
        background-color: #6c757d;
        cursor: not-allowed;
        opacity: 0.7;
    }
    
    .btn-loader {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
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
        <!-- Success/Error Messages -->
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i>
            <strong>Success!</strong> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i>
            <strong>Error!</strong> {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        @endif

        @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Please fix the following errors:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        @endif

        <div class="row">
            <!-- User Info Sidebar -->
            <div class="col-lg-4 col-xl-3">
                <div class="profile-card">
                    <div class="profile-card-body">
                        <div class="user-info">
                            <h3 class="user-name" style="color: white;">{{ $user->name }}</h3>
                            <p class="user-email">{{ $user->email }}</p>
                            <p class="user-email">{{ $user->mobile }}</p>
                            <p class="user-email">Account Number: {{ $user->account_number ?? 'N/A'}}</p>
                            <p class="user-email">Account Status: {{ $user->is_status ?? 'N/A'}}</p>
                            <p class="user-email">Joined at: {{ $user->created_at->format('d M Y, h:i A') }}</p>
                            <button class="edit-profile-btn" style="background: white; color: darkgreen; border: darkgreen;" onclick="openEditModal()">Edit Profile</button>
                        </div>
                        <div class="balance-section">
                            <button class="balance-btn">
                                ₦ {{ number_format($balance->balance ?? 0, 2) }}
                            </button>
                        </div>
                    </div>
                </div>
                @if($kyc)
                <div class="profile-card mt-2">
                    <div class="profile-card-body">
                        <div class="user-info">
                            <h3 class="user-name" style="color: white;">BVN: {{ $kyc->bvn ?? 'N/A' }}</h3>
                            <p class="user-email">NIN: {{ $kyc->nin ?? 'N/A' }}</p>
                            <p class="user-email">BVN PHONE NUMBER(LAST 5): {{ $kyc->bvn_phone_last_5 ?? 'N/A' }}</p>
                            <p class="user-email">
                                STATUS: 
                                <span class="badge badge-{{ $kyc->status == 'approved' ? 'success' : ($kyc->status == 'rejected' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($kyc->status) ?? 'N/A' }}
                                </span>
                            </p>
                            @if($kyc->created_at->diffInHours(now()) < 42)
                                <p class="user-email">
                                    <span class="badge badge-info">
                                        <i class="fas fa-clock"></i> Just Uploaded {{ $kyc->created_at->diffForHumans() }}
                                    </span>
                                </p>
                            @endif
                            @if($kyc->rejection_reason)
                                <div class="alert alert-danger mt-3">
                                    <strong>Rejection Reason:</strong> {{ $kyc->rejection_reason }}
                                </div>
                            @endif
                        </div>

                        <!-- Document Images Section -->
                        <div class="documents-section mt-4">
                            <h5 style="color: white; margin-bottom: 15px;">Uploaded Documents</h5>
                            
                            <div class="row">
                                <!-- Passport Photo -->
                                <div class="col-md-6 mb-3">
                                    <div class="document-card">
                                        <label style="color: white; font-weight: bold;">Passport Photo:</label>
                                        @if($kyc->passport_photo)
                                            <div class="document-preview">
                                                <img src="{{ $kyc->passport_photo }}" 
                                                     alt="Passport Photo" 
                                                     class="img-fluid rounded"
                                                     style="max-height: 300px; width: 100%; object-fit: cover; cursor: pointer;"
                                                     onclick="openImageModal('{{ $kyc->passport_photo }}', 'Passport Photo')">
                                            </div>
                                            <a href="{{ $kyc->passport_photo }}" 
                                               target="_blank" 
                                               class="btn btn-sm btn-outline-light mt-2">
                                                <i class="fas fa-external-link-alt"></i> View Full Size
                                            </a>
                                        @else
                                            <p class="text-muted">Not uploaded</p>
                                        @endif
                                    </div>
                                </div>

                                <!-- Proof of Address -->
                                <div class="col-md-6 mb-3">
                                    <div class="document-card">
                                        <label style="color: white; font-weight: bold;">Proof of Address:</label>
                                        @if($kyc->proof_of_address)
                                            <div class="document-preview">
                                                <img src="{{ $kyc->proof_of_address }}" 
                                                     alt="Proof of Address" 
                                                     class="img-fluid rounded"
                                                     style="max-height: 300px; width: 100%; object-fit: cover; cursor: pointer;"
                                                     onclick="openImageModal('{{ $kyc->proof_of_address }}', 'Proof of Address')">
                                            </div>
                                            <a href="{{ $kyc->proof_of_address }}" 
                                               target="_blank" 
                                               class="btn btn-sm btn-outline-light mt-2">
                                                <i class="fas fa-external-link-alt"></i> View Full Size
                                            </a>
                                        @else
                                            <p class="text-muted">Not uploaded</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        @if($kyc->status == 'PENDING')
                        <div class="balance-section mt-4">
                            <button class="btn btn-success mr-2" onclick="approveKYC({{ $kyc->id }})">
                                <i class="fas fa-check"></i> Approve KYC
                            </button>
                            <button class="btn btn-danger" onclick="showRejectModal({{ $kyc->id }})">
                                <i class="fas fa-times"></i> Reject KYC
                            </button>
                        </div>
                        @endif
                        @if($kyc->status == 'APPROVED')
                       <div class="balance-section mt-4">
                            <button class="btn btn-danger" onclick="showRejectModal({{ $kyc->id }})">
                                <i class="fas fa-times"></i> Reject KYC
                            </button>
                        </div>
                        @endif
                        @if($kyc->status == 'REJECTED')
                       <div class="balance-section mt-4">
                            <button class="btn btn-danger" onclick="deleteKYC({{ $kyc->id }})">
                                <i class="fas fa-times"></i> Delete Kyc
                            </button>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Rejection Modal -->
                <div class="modal fade" id="rejectKYCModal" tabindex="-1" role="dialog">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content" style="background: #2d3748; color: white;">
                            <div class="modal-header">
                                <h5 class="modal-title">Reject KYC</h5>
                                <button type="button" class="close" data-dismiss="modal" style="color: white;">
                                    <span>&times;</span>
                                </button>
                            </div>
                            <form id="rejectKYCForm" method="POST" action="{{ route('admin.kyc.reject', $kyc->id ?? 0) }}">
                                @csrf
                                <div class="modal-body">
                                    <div class="form-group">
                                        <label for="rejection_reason">Rejection Reason *</label>
                                        <textarea class="form-control" 
                                                  id="rejection_reason" 
                                                  name="rejection_reason" 
                                                  rows="4" 
                                                  required
                                                  placeholder="Please provide a clear reason for rejection..."></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-danger">Reject KYC</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Image Preview Modal -->
                <div class="modal fade" id="imageModal" tabindex="-1" role="dialog">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content" style="background: transparent; border: none;">
                            <div class="modal-header" style="border: none;">
                                <h5 class="modal-title" id="imageModalLabel" style="color: white;"></h5>
                                <button type="button" class="close" data-dismiss="modal" style="color: white;">
                                    <span>&times;</span>
                                </button>
                            </div>
                            <div class="modal-body text-center">
                                <img id="modalImage" src="" alt="Document" class="img-fluid" style="max-height: 80vh;">
                            </div>
                        </div>
                    </div>
                </div>

                @else
                <div class="profile-card mt-2">
                    <div class="profile-card-body text-center">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <h4>No KYC Submitted</h4>
                            <p>This user has not submitted KYC documents yet.</p>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Transactions & Actions -->
            <div class="col-lg-8 col-xl-9">
            <div class="card">
            <div class="container mt-4">
                <h4>User Transactions</h4>
               <div class="loan-summary">
                    <span class="badge badge-info">Total Transactions: {{ $transactions->total() }}</span>
                </div>
                <!-- Wrap table in scrollable container -->
                <div class="table-container">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Amount</th>
                                <th>Description</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Reference</th>
                                <th>Balance Before</th>
                                <th>Balance After</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transactions as $index => $transaction)
                                <tr>
                                    <td>{{ $transactions->firstItem() + $index }}</td>
                                    <td>₦ {{ number_format($transaction->amount, 2) }}</td>
                                    <td>{{ $transaction->description ?? 'N/A' }} | {{ $transaction->beneficiary ?? 'N/A' }}</td>
                                    <td>{{ ucfirst($transaction->type ?? 'N/A') }}</td>
                                    <td>
                                        <span class="badge badge-{{ $transaction->status === 'SUCCESS' ? 'success' : ($transaction->status === 'PENDING' ? 'warning' : 'danger') }}">
                                            {{ ucfirst($transaction->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $transaction->reference ?? 'N/A' }}</td>
                                    <td>₦ {{ number_format($transaction->balance_before, 2) }}</td>
                                    <td>₦ {{ number_format($transaction->balance_after, 2) }}</td>
                                    <td>{{ $transaction->created_at->format('d M, Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">No transactions found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Styled Pagination -->
                <div class="d-flex justify-content-center mt-3">
                    {{ $transactions->onEachSide(1)->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">User Loans</h4>
                    <div class="loan-summary">
                        <span class="badge badge-info">Total Loans: {{ $loans->total() }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Amount</th>
                                    <th>For</th>
                                    <th>Status</th>
                                    <th>Repayment Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($loans as $index => $loan)
                                    <tr>
                                        <td>{{ $loans->firstItem() + $index }}</td>
                                        <td>
                                            <strong>₦ {{ number_format($loan->amount, 2) }}</strong>
                                        </td>
                                        <td>{{ $loan->for ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge badge-{{ 
                                                $loan->status === 'APPROVED' ? 'success' : 
                                                ($loan->status === 'PENDING' ? 'warning' : 
                                                ($loan->status === 'REJECTED' ? 'danger' : 'secondary')) 
                                            }}">
                                                {{ ucfirst($loan->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-{{ 
                                                $loan->repayment_status === 'PAID' ? 'success' : 
                                                ($loan->repayment_status === 'PARTIAL' ? 'info' : 
                                                ($loan->repayment_status === 'OVERDUE' ? 'danger' : 'warning')) 
                                            }}">
                                                {{ ucfirst($loan->repayment_status ?? 'UNPAID') }}
                                            </span>
                                        </td>
                                        <td>{{ $loan->created_at->format('d M, Y H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">No loans found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center mt-3">
                        {{ $loans->onEachSide(1)->links('pagination::bootstrap-4') }}
                    </div>
                </div>
            </div>
                <!-- Optional Back Button -->
                <div class="mt-3">
                    <a href="{{ url('admin/users') }}" class="btn btn-secondary btn-sm">Back to Users</a>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Edit Profile Modal -->
<div id="editProfileModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeEditModal()">&times;</span>
        <h2>Edit User Profile</h2>
        <form id="editProfileForm">
            @csrf
            @method('PUT')
            <input type="hidden" id="user_id" name="user_id" value="{{ $user->id }}">
            
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" value="{{ $user->name }}" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="{{ $user->email }}" required>
            </div>
            
            <div class="form-group">
                <label for="mobile">Mobile</label>
                <input type="text" id="mobile" name="mobile" value="{{ $user->mobile }}" required>
            </div>
            
            <div class="form-group">
                <label for="status">Account Status <span style="color: red;">*</span></label>
                <select id="status" name="status" required>
                    <option value="ACTIVE" {{ ($user->status ?? 'ACTIVE') == 'ACTIVE' ? 'selected' : '' }}>ACTIVE</option>
                    <option value="INACTIVE" {{ ($user->status ?? '') == 'INACTIVE' ? 'selected' : '' }}>INACTIVE</option>
                    <option value="SUSPENDED" {{ ($user->status ?? '') == 'SUSPENDED' ? 'selected' : '' }}>SUSPENDED</option>
                    <option value="PENDING" {{ ($user->status ?? '') == 'PENDING' ? 'selected' : '' }}>PENDING</option>
                    <option value="BLOCKED" {{ ($user->status ?? '') == 'BLOCKED' ? 'selected' : '' }}>BLOCKED</option>
                </select>
                <small style="color: #666; display: block; margin-top: 5px;">
                    ⚠️ Changing status may affect user access
                </small>
            </div>
            
            <div class="form-actions">
                <button type="button" onclick="closeEditModal()" class="btn-cancel">Cancel</button>
                <button type="submit" class="btn-save" id="saveBtn">
                    <span class="btn-text">Save Changes</span>
                    <span class="btn-loader" style="display: none;">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" style="animation: spin 1s linear infinite;">
                            <circle cx="8" cy="8" r="6" stroke="white" stroke-width="2" stroke-linecap="round" stroke-dasharray="30" stroke-dashoffset="10"/>
                        </svg>
                        Updating...
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>
    <script>
    // document.addEventListener('DOMContentLoaded', function() {
    //     const alerts = document.querySelectorAll('.alert');
    //     alerts.forEach(function(alert) {
    //         setTimeout(function() {
    //             $(alert).fadeOut('slow', function() {
    //                 $(this).remove();
    //             });
    //         }, 5000);
    //     });
    // });

    function openEditModal() {
        document.getElementById('editProfileModal').style.display = 'block';
    }
    
    function closeEditModal() {
        document.getElementById('editProfileModal').style.display = 'none';
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('editProfileModal');
        if (event.target === modal) {
            closeEditModal();
        }
    }
    
    // Handle form submission
    document.getElementById('editProfileForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const saveBtn = document.getElementById('saveBtn');
        const btnText = saveBtn.querySelector('.btn-text');
        const btnLoader = saveBtn.querySelector('.btn-loader');
        
        const formData = new FormData(this);
        const userId = formData.get('user_id');
        const data = {
            name: formData.get('name'),
            email: formData.get('email'),
            mobile: formData.get('mobile'),
            is_status: formData.get('status')  // Changed to is_status
        };
        
        // Confirm status change
        const currentStatus = "{{ $user->status ?? 'ACTIVE' }}";
        if (data.is_status !== currentStatus) {
            if (!confirm(`Are you sure you want to change the account status from ${currentStatus} to ${data.is_status}?`)) {
                return;
            }
        }
        
        // Show loading state
        saveBtn.disabled = true;
        btnText.style.display = 'none';
        btnLoader.style.display = 'flex';
        
        try {
            const response = await fetch("{{ route('admin.user.update', ':id') }}".replace(':id', userId), {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            // Reset button state first
            saveBtn.disabled = false;
            btnText.style.display = 'inline';
            btnLoader.style.display = 'none';
            
            if (response.ok && result.success) {
                alert('User profile updated successfully!');
                
                // Update the UI with new values
                document.getElementById('currentStatus').textContent = data.is_status;  // Changed to is_status
                document.querySelector('.user-name').textContent = data.name;
                document.querySelectorAll('.user-email')[0].textContent = data.email;
                document.querySelectorAll('.user-email')[1].textContent = data.mobile;
                
                closeEditModal();
                
                // Optional: Reload page after 1 second
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                // Show detailed error messages
                if (result.errors) {
                    let errorMsg = 'Validation Errors:\n\n';
                    for (let field in result.errors) {
                        errorMsg += `• ${field}: ${result.errors[field].join(', ')}\n`;
                    }
                    alert(errorMsg);
                } else {
                    alert('Error: ' + (result.message || 'Failed to update profile'));
                }
                console.error('Full error response:', result);
            }
        } catch (error) {
            // Reset button state
            saveBtn.disabled = false;
            btnText.style.display = 'inline';
            btnLoader.style.display = 'none';
            
            console.error('Fetch error:', error);
            alert('Network error: Unable to connect to the server. Please check your connection and try again.');
        }
    });

    function openImageModal(imageUrl, title) {
        document.getElementById('modalImage').src = imageUrl;
        document.getElementById('imageModalLabel').textContent = title;
        $('#imageModal').modal('show');
    }

    function showRejectModal(kycId) {
        const form = document.getElementById('rejectKYCForm');
        form.action = form.action.replace(/\/\d+$/, '/' + kycId);
        $('#rejectKYCModal').modal('show');
    }

    function approveKYC(kycId) {
        if (confirm('Are you sure you want to approve this KYC?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/a-pay/admin/kyc/${kycId}/approve`;
            
            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = '{{ csrf_token() }}';
            
            form.appendChild(csrf);
            document.body.appendChild(form);
            form.submit();
        }
    }

    function deleteKYC(kycId) {
        if (confirm('Are you sure you want to permanently delete this KYC? This action cannot be undone.')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/a-pay/admin/kyc/${kycId}/delete`;
            
            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = '{{ csrf_token() }}';
            
            const method = document.createElement('input');
            method.type = 'hidden';
            method.name = '_method';
            method.value = 'DELETE';
            
            form.appendChild(csrf);
            form.appendChild(method);
            document.body.appendChild(form);
            form.submit();
        }
    }
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