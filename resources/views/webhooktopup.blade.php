@include('components.header')

<div class="login-form-bg h-100">
    <div class="container h-100">
        <div class="row justify-content-center h-100">
            <div class="col-xl-6">
                <div class="form-input-content">
                    <div class="card login-form mb-0">
                        <div class="card-body pt-5">
                            <a class="text-center" href="index.html">
                                <h4>A-Pay | Transaction Status</h4>
                            </a>

                            <!-- DEBUG: Show what message we're getting -->
                            <div style="background: #f0f0f0; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 12px; color: #666;">
                                Message: "{{ session('message') }}"
                            </div>

                            @if(session('message') && (strtolower(session('message')) === 'successful!' || strtolower(session('message')) === 'success'))
                                <!-- SUCCESS MESSAGE -->
                                <div class="alert alert-success text-green-800 bg-green-100 p-4 rounded mb-4 border-l-4 border-green-600">
                                    <div style="text-align: center;">
                                        <i class="fas fa-check-circle" style="font-size: 50px; color: #22863a; margin-bottom: 15px;"></i>
                                        <h3 style="color: #22863a; font-weight: 700; margin: 15px 0;">🎉 Success!</h3>
                                        <p style="color: #1a5e20; font-size: 16px; margin: 10px 0;">
                                            Your transaction has been completed successfully!
                                        </p>
                                    </div>
                                </div>

                                <div class="mt-4 mb-4 p-4 rounded" style="background: #e8f5e9; border-left: 5px solid #4caf50;">
                                    <p style="color: #1b5e20; font-weight: 600; margin-bottom: 10px;">
                                        <i class="fas fa-check"></i> Transaction Status: <strong style="color: #2e7d32;">COMPLETED</strong>
                                    </p>
                                    <p style="color: #2e7d32; font-size: 14px; margin: 0;">
                                        ✅ Your payment has been processed and confirmed.
                                    </p>
                                </div>

                                <div class="mt-4 mb-4 p-4 rounded" style="background: #f1f8e9; border-left: 5px solid #7cb342;">
                                    <div style="text-align: center; margin-bottom: 10px;">
                                        <i class="fab fa-whatsapp" style="font-size: 35px; color: #25d366;"></i>
                                    </div>
                                    <p style="color: #558b2f; font-weight: 600; margin-bottom: 8px;">
                                        Back to WhatsApp
                                    </p>
                                    <p style="color: #9ccc65; font-size: 14px; margin: 0;">
                                        You'll receive a confirmation message on WhatsApp shortly. Check your messages for more details!
                                    </p>
                                </div>

                                <a href="https://wa.me/234807000000" target="_blank" class="btn login-form__btn submit w-100" style="background: linear-gradient(135deg, #4caf50 0%, #2e7d32 100%); border: none; color: white; font-weight: 600; margin-top: 20px;">
                                    <i class="fab fa-whatsapp"></i> Return to WhatsApp
                                </a>

                            @else
                                <!-- FAILED MESSAGE -->
                                <div class="alert alert-danger text-red-800 bg-red-100 p-4 rounded mb-4 border-l-4 border-red-600">
                                    <div style="text-align: center;">
                                        <i class="fas fa-times-circle" style="font-size: 50px; color: #c62828; margin-bottom: 15px;"></i>
                                        <h3 style="color: #c62828; font-weight: 700; margin: 15px 0;">❌ Transaction Failed</h3>
                                        <p style="color: #b71c1c; font-size: 16px; margin: 10px 0;">
                                            Unfortunately, your transaction could not be completed.
                                        </p>
                                    </div>
                                </div>

                                <div class="mt-4 mb-4 p-4 rounded" style="background: #ffebee; border-left: 5px solid #f44336;">
                                    <p style="color: #b71c1c; font-weight: 600; margin-bottom: 10px;">
                                        <i class="fas fa-exclamation-triangle"></i> Error Status: <strong style="color: #d32f2f;">FAILED</strong>
                                    </p>
                                    <p style="color: #d32f2f; font-size: 14px; margin: 0;">
                                        ⚠️ Please try again or contact support if the issue persists.
                                    </p>
                                </div>

                                <div class="mt-4 mb-4 p-4 rounded" style="background: #fce4ec; border-left: 5px solid #e91e63;">
                                    <div style="text-align: center; margin-bottom: 10px;">
                                        <i class="fab fa-whatsapp" style="font-size: 35px; color: #25d366;"></i>
                                    </div>
                                    <p style="color: #880e4f; font-weight: 600; margin-bottom: 8px;">
                                        Back to WhatsApp
                                    </p>
                                    <p style="color: #c2185b; font-size: 14px; margin: 0;">
                                        Return to WhatsApp and try again. Your balance has been restored if any amount was deducted.
                                    </p>
                                </div>

                                <a href="https://wa.me/234807000000" target="_blank" class="btn login-form__btn submit w-100" style="background: linear-gradient(135deg, #25d366 0%, #128c7e 100%); border: none; color: white; font-weight: 600; margin-top: 20px;">
                                    <i class="fab fa-whatsapp"></i> Return to WhatsApp
                                </a>

                            @endif

                            <p class="mt-5 login-form__footer" style="text-align: center;">
                                <a href="{{ route('dashboard') }}" style="color: green;">Back to Dashboard</a>
                            </p>
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