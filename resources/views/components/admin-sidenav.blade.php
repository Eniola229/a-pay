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
                            <span class="nav-text">Errors</span>
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