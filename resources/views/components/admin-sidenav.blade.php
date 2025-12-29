 <div class="nk-sidebar">           
            <div class="nk-nav-scroll">
                <ul class="metismenu" id="menu">
                    <li class="nav-label"></li>
                    <li>
                       <a class="has-arrow {{ request()->is('admin/dashboard') ? 'active' : '' }}" href="{{ url('admin/dashboard') }}" aria-expanded="false">
                            <i class="icon-speedometer menu-icon"></i>
                            <span class="nav-text">Home</span>
                        </a>
                    </li>
                    <li>
                       <a class="has-arrow {{ request()->is('admin/transactions') ? 'active' : '' }}" href="{{ url('admin/transactions') }}" aria-expanded="false">
                            <i class="icon-wallet menu-icon"></i>
                            <span class="nav-text">Transactions</span>
                        </a>
                    </li>
                    <li>
                       <a class="has-arrow {{ request()->is('admin/complians') ? 'active' : '' }}" href="{{ url('admin/complians') }}" aria-expanded="false">
                            <i class="icon-wallet menu-icon"></i>
                            <span class="nav-text">Complains</span>
                        </a>
                    </li>
                    <li>
                       <a class="has-arrow {{ request()->is('admin/errors') ? 'active' : '' }}" href="{{ url('admin/errors') }}" aria-expanded="false">
                            <i class="icon-wallet menu-icon"></i>
                            <span class="nav-text">Logs</span>
                        </a>
                    </li>
                    </li>
                    <li>
                       <a class="has-arrow {{ request()->is('admin/notifications') ? 'active' : '' }}" href="{{ url('admin/notifications') }}" aria-expanded="false">
                            <i class="icon-wallet menu-icon"></i>
                            <span class="nav-text">Notifications</span>
                        </a>
                    </li>
                    <li>
                        <a class="has-arrow {{ request()->is('admin/users') ? 'active' : '' }}" href="{{ url('admin/users') }}" aria-expanded="false">
                            <i class="icon-user menu-icon"></i>
                            <span class="nav-text">Users</span>
                            @php
                                $newKycCount = \App\Models\KycProfile::where('created_at', '>=', now()->subHours(24))
                                    ->where('status', 'pending')
                                    ->count();
                            @endphp
                            @if($newKycCount > 0)
                                <span class="badge badge-pill badge-danger ml-auto kyc-badge">
                                    New Kyc: {{ $newKycCount }}
                                </span>
                            @endif
                        </a>
                    </li>
                    <li>
                       <a class="has-arrow {{ request()->is('admin/loans') ? 'active' : '' }}" href="{{ url('admin/loans') }}" aria-expanded="false">
                             <i class="icon-user menu-icon"></i>
                            <span class="nav-text">Loans</span>
                        </a>
                    </li>
                    <li>
                       <a class="has-arrow {{ request()->is('admin/newsletter') ? 'active' : '' }}" href="{{ url('admin/newsletter') }}" aria-expanded="false">
                             <i class="icon-wallet menu-icon"></i>
                            <span class="nav-text">Newletter</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>



    <style>
    .kyc-badge {
        position: absolute;
        right: 20px;
        top: 50%;
        transform: translateY(-50%);
        background: #ff4444 !important;
        color: white !important;
        font-size: 11px;
        font-weight: bold;
        padding: 3px 8px;
        border-radius: 12px;
        min-width: 20px;
        text-align: center;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0%, 100% {
            box-shadow: 0 0 0 0 rgba(255, 68, 68, 0.7);
        }
        50% {
            box-shadow: 0 0 0 6px rgba(255, 68, 68, 0);
        }
    }

    .nav-text {
        display: inline-block;
        max-width: calc(100% - 50px);
    }
    </style>