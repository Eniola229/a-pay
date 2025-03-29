 <div class="nk-sidebar">           
            <div class="nk-nav-scroll">
                <ul class="metismenu" id="menu">
                    <li class="nav-label"></li>
                    <li>
                       <a class="has-arrow {{ request()->is('dashboard') ? 'active' : '' }}" href="{{ url('dashboard') }}" aria-expanded="false">
                            <i class="icon-speedometer menu-icon"></i>
                            <span class="nav-text">Home</span>
                        </a>
                    </li>
                    <li>
                       <a class="has-arrow {{ request()->is('transactions') ? 'active' : '' }}" href="{{ url('transactions') }}" aria-expanded="false">
                            <i class="icon-wallet menu-icon"></i>
                            <span class="nav-text">Transactions</span>
                        </a>
                    </li>
                  <!--   <li>
                       <a class="has-arrow {{ request()->is('withdraw') ? 'active' : '' }}" href="{{ url('withdraw') }}" aria-expanded="false">
                            <i class="fa fa-money"></i>
                            <span class="nav-text">Withdraw</span>
                        </a>
                    </li> -->
                     <li class="nav-label"></li>
                    <li>
                       <a class="has-arrow {{ request()->is('profile') ? 'active' : '' }}" href="{{ url('profile') }}" aria-expanded="false">
                            <i class="icon-user menu-icon"></i>
                            <span class="nav-text">Profile</span>
                        </a>
                    </li>
                    <hr>
                   <li>
                       <a class="has-arrow" href="https://wa.me/qr/PXDYDW6XSJ2HG1" target="_blank" aria-expanded="false">
                            
                            <span class="nav-text"><strong>Whatsapp Us</strong></span>
                        </a>
                    </li>
                   <li>
                       <a class="has-arrow" href="https://africtv.com.ng/account/@AfricPay" target="_blank" aria-expanded="false">
                           
                            <span class="nav-text"><strong>AfricTv</strong></span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>