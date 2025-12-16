<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>A-Pay KYC Verification</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #059669 0%, #10b981 50%, #34d399 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(5, 150, 105, 0.4);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .header p {
            opacity: 0.9;
            font-size: 14px;
        }
        .content {
            padding: 30px;
        }
        .alert {
            background: #d1fae5;
            border-left: 4px solid #10b981;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-size: 14px;
            color: #065f46;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #065f46;
            font-size: 14px;
        }
        input[type="text"],
        input[type="tel"],
        input[type="file"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #d1fae5;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        input[type="text"]:focus,
        input[type="tel"]:focus {
            outline: none;
            border-color: #10b981;
        }
        .file-label {
            display: block;
            padding: 12px;
            background: #f0fdf4;
            border: 2px dashed #10b981;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            color: #059669;
        }
        .file-label:hover {
            background: #d1fae5;
            border-color: #059669;
        }
        input[type="file"] {
            display: none;
        }
        .file-name {
            font-size: 12px;
            color: #059669;
            margin-top: 5px;
            font-weight: 600;
        }
        .btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(5, 150, 105, 0.3);
        }
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .btn-verify {
            width: auto;
            padding: 10px 20px;
            font-size: 14px;
            margin-top: 10px;
        }
        .error {
            color: #dc3545;
            font-size: 12px;
            margin-top: 5px;
        }
        .success {
            color: #10b981;
            font-size: 12px;
            margin-top: 5px;
            font-weight: 600;
        }
        .info-box {
            background: #f0fdf4;
            border-left: 4px solid #10b981;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-size: 13px;
            color: #065f46;
        }
        .requirements {
            margin-top: 10px;
        }
        .requirements li {
            margin-bottom: 5px;
            font-size: 13px;
            color: #065f46;
        }
        .error-box {
            background: #fee2e2;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .error-box div {
            color: #991b1b;
            font-size: 14px;
        }
        small {
            font-size: 12px;
            color: #059669;
        }
        .verified-badge {
            display: inline-block;
            background: #d1fae5;
            color: #065f46;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 8px;
        }
        .loading {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #d1fae5;
            border-top-color: #10b981;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
            margin-left: 8px;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .input-group {
            display: flex;
            gap: 10px;
        }
        .input-group input {
            flex: 1;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 A-Pay KYC Verification</h1>
            <p>Complete your identity verification</p>
        </div>
        
        <div class="content">
            <div class="alert">
                ⚠️ <strong>Important:</strong> If your account balance is ₦100,000 or more. KYC verification is required to continue using A-Pay services.
            </div>

            <div class="info-box">
                <strong>📋 Required Documents:</strong>
                <ul class="requirements">
                    <li>✓ Passport photograph (JPG, PNG - Max 2MB)</li>
                    <li>✓ Bank Verification Number (BVN)</li>
                    <li>✓ National Identification Number (NIN)</li>
                    <li>✓ Proof of Address (PDF, JPG, PNG - Max 2MB)</li>
                </ul>
            </div>

            @if ($errors->any())
                <div class="error-box">
                    @foreach ($errors->all() as $error)
                        <div>❌ {{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('kyc.submit', ['user' => $user->id]) }}" method="POST" enctype="multipart/form-data" id="kycForm">
                @csrf
                <input type="hidden" name="token" value="{{ request('token') }}">
                <input type="hidden" name="bvn_verified" id="bvn_verified" value="0">

                <div class="form-group">
                    <label>📸 Passport Photograph *</label>
                    <label for="passport_photo" class="file-label">
                        📤 Click to upload passport photo
                    </label>
                    <input type="file" id="passport_photo" name="passport_photo" accept="image/*" onchange="showFileName(this, 'passport-name')" required>
                    <div class="file-name" id="passport-name"></div>
                </div>

                <div class="form-group">
                    <label>🆔 Bank Verification Number (BVN) *</label>
                    <input type="text" id="bvn" name="bvn" placeholder="Enter 11-digit BVN" maxlength="11" value="{{ old('bvn') }}" required>
                    <div id="bvn-status"></div>
                </div>

                <div class="form-group">
                    <label>📱 BVN Registered Phone Number *</label>
                    <small>Enter the phone number registered with your BVN</small>
                    <div class="input-group">
                        <input type="tel" id="bvn_phone" name="bvn_phone" placeholder="e.g., 08012345678" maxlength="11" value="{{ old('bvn_phone') }}" required>
                        <button type="button" class="btn btn-verify" id="verifyBtn" onclick="verifyBVN()">
                            🔍 Verify BVN
                        </button>
                    </div>
                    <div id="verify-status"></div>
                </div>

                <div class="form-group">
                    <label>🆔 National Identification Number (NIN) *</label>
                    <input type="text" name="nin" placeholder="Enter 11-digit NIN" maxlength="11" value="{{ old('nin') }}" required>
                </div>

                <div class="form-group">
                    <label>📄 Proof of Address *</label>
                    <label for="proof_of_address" class="file-label">
                        📤 Click to upload proof of address
                    </label>
                    <input type="file" id="proof_of_address" name="proof_of_address" accept="image/*,.pdf" onchange="showFileName(this, 'proof-name')" required>
                    <div class="file-name" id="proof-name"></div>
                    <small>Utility bill, bank statement, or government document</small>
                </div>

                <button type="submit" class="btn" id="submitBtn" disabled>
                    ✅ Submit KYC Verification
                </button>
            </form>

            <div style="margin-top: 20px; padding: 15px; background: #f0fdf4; border-radius: 8px; font-size: 13px; color: #065f46;">
                <strong>⚠️ Note:</strong> Your account will be automatically activated upon submission. However, our team will review your documents within 24-48 hours. If any errors are found, your account may be suspended until corrections are made.
            </div>
        </div>
    </div>

    <script>
        function showFileName(input, displayId) {
            const display = document.getElementById(displayId);
            if (input.files && input.files[0]) {
                display.textContent = '✓ ' + input.files[0].name;
                display.style.color = '#10b981';
            }
        }

        async function verifyBVN() {
            const bvn = document.getElementById('bvn').value;
            const phone = document.getElementById('bvn_phone').value;
            const verifyBtn = document.getElementById('verifyBtn');
            const statusDiv = document.getElementById('verify-status');
            const submitBtn = document.getElementById('submitBtn');

            // Validation
            if (bvn.length !== 11) {
                statusDiv.innerHTML = '<div class="error">❌ BVN must be 11 digits</div>';
                return;
            }

            if (phone.length < 10) {
                statusDiv.innerHTML = '<div class="error">❌ Please enter a valid phone number</div>';
                return;
            }

            // Show loading
            verifyBtn.disabled = true;
            verifyBtn.innerHTML = '⏳ Verifying... <span class="loading"></span>';
            statusDiv.innerHTML = '<div style="color: #059669; font-size: 13px;">🔍 Verifying BVN details...</div>';

            try {
                const response = await fetch('{{ route("kyc.verify-bvn") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        bvn: bvn,
                        phone: phone
                    })
                });

                const data = await response.json();

                if (data.success) {
                    statusDiv.innerHTML = `
                        <div class="success">✅ BVN Verified Successfully!</div>
                        <div class="verified-badge">✓ ${data.data.full_name || 'Verified'}</div>
                    `;
                    document.getElementById('bvn_verified').value = '1';
                    submitBtn.disabled = false;
                    verifyBtn.innerHTML = '✓ Verified';
                    verifyBtn.style.background = '#10b981';
                } else {
                    statusDiv.innerHTML = `<div class="error">❌ ${data.message || 'BVN verification failed. Please check your details.'}</div>`;
                    document.getElementById('bvn_verified').value = '0';
                    submitBtn.disabled = true;
                    verifyBtn.disabled = false;
                    verifyBtn.innerHTML = '🔍 Verify BVN';
                }
            } catch (error) {
                statusDiv.innerHTML = '<div class="error">❌ Verification failed. Please try again.</div>';
                verifyBtn.disabled = false;
                verifyBtn.innerHTML = '🔍 Verify BVN';
                submitBtn.disabled = true;
            }
        }

        // Disable submit button initially
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('submitBtn').disabled = true;
        });

        // Form validation before submit
        document.getElementById('kycForm').addEventListener('submit', function(e) {
            const bvnVerified = document.getElementById('bvn_verified').value;
            if (bvnVerified !== '1') {
                e.preventDefault();
                alert('Please verify your BVN before submitting the form.');
            }
        });
    </script>
</body>
</html>