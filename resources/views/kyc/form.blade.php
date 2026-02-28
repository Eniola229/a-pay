<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Identity Verification — A-Pay</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --g1: #022c22;
            --g2: #064e3b;
            --g3: #065f46;
            --g4: #047857;
            --g5: #059669;
            --g6: #10b981;
            --g7: #34d399;
            --g8: #6ee7b7;
            --g9: #a7f3d0;
            --g10: #d1fae5;
            --g11: #ecfdf5;
            --white: #ffffff;
            --text-dark: #022c22;
            --text-mid: #065f46;
            --text-soft: #6b7280;
            --border: rgba(16,185,129,0.18);
            --shadow: 0 24px 80px rgba(5,150,105,0.18);
            --radius: 16px;
        }

        *, *::before, *::after {
            margin: 0; padding: 0; box-sizing: border-box;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--g1);
            min-height: 100vh;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 32px 16px 64px;
            position: relative;
            overflow-x: hidden;
        }

        /* Ambient background */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse 60% 50% at 10% 0%, rgba(16,185,129,0.2) 0%, transparent 60%),
                radial-gradient(ellipse 40% 60% at 90% 100%, rgba(52,211,153,0.12) 0%, transparent 60%);
            pointer-events: none;
        }

        /* Subtle grid */
        body::after {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(16,185,129,0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(16,185,129,0.04) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
        }

        .wrapper {
            width: 100%;
            max-width: 520px;
            position: relative;
            z-index: 1;
        }

        /* Logo / Brand */
        .brand-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 28px;
        }

        .brand-logo {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, var(--g5), var(--g7));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: 18px;
            color: var(--g1);
            box-shadow: 0 4px 16px rgba(16,185,129,0.4);
            flex-shrink: 0;
        }

        .brand-name {
            font-family: 'Syne', sans-serif;
            font-size: 22px;
            font-weight: 700;
            color: var(--white);
            letter-spacing: -0.3px;
        }

        .brand-name span {
            color: var(--g7);
        }

        /* Card */
        .card {
            background: rgba(255,255,255,0.97);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: var(--shadow), 0 0 0 1px rgba(16,185,129,0.1);
            backdrop-filter: blur(20px);
        }

        /* Card header */
        .card-header {
            padding: 32px 36px 28px;
            background: linear-gradient(135deg, var(--g3) 0%, var(--g5) 100%);
            position: relative;
            overflow: hidden;
        }

        .card-header::before {
            content: '';
            position: absolute;
            top: -40px; right: -40px;
            width: 160px; height: 160px;
            border-radius: 50%;
            background: rgba(255,255,255,0.06);
        }

        .card-header::after {
            content: '';
            position: absolute;
            bottom: -60px; left: 60px;
            width: 220px; height: 220px;
            border-radius: 50%;
            background: rgba(52,211,153,0.1);
        }

        .header-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 20px;
            padding: 5px 12px;
            font-size: 11px;
            font-weight: 500;
            color: var(--g10);
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 16px;
        }

        .header-badge::before {
            content: '';
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--g7);
            box-shadow: 0 0 6px var(--g7);
        }

        .card-header h1 {
            font-family: 'Syne', sans-serif;
            font-size: 26px;
            font-weight: 700;
            color: var(--white);
            margin-bottom: 8px;
            position: relative;
            z-index: 1;
        }

        .card-header p {
            font-size: 14px;
            color: rgba(255,255,255,0.75);
            line-height: 1.5;
            position: relative;
            z-index: 1;
        }

        /* Steps indicator */
        .steps {
            display: flex;
            align-items: center;
            padding: 20px 36px;
            background: var(--g11);
            border-bottom: 1px solid var(--border);
            gap: 0;
        }

        .step {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1;
        }

        .step-num {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: var(--g10);
            border: 2px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-soft);
            flex-shrink: 0;
            transition: all 0.3s;
        }

        .step.active .step-num {
            background: var(--g5);
            border-color: var(--g5);
            color: white;
            box-shadow: 0 0 0 4px rgba(5,150,105,0.15);
        }

        .step.done .step-num {
            background: var(--g6);
            border-color: var(--g6);
            color: white;
        }

        .step-label {
            font-size: 12px;
            font-weight: 500;
            color: var(--text-soft);
        }

        .step.active .step-label { color: var(--g4); }
        .step.done .step-label { color: var(--g5); }

        .step-connector {
            flex: 1;
            height: 2px;
            background: var(--border);
            margin: 0 8px;
        }

        .step.done + .step-connector { background: var(--g6); }

        /* Body */
        .card-body {
            padding: 28px 36px;
        }

        /* Alert */
        .alert {
            display: flex;
            gap: 12px;
            padding: 14px 16px;
            border-radius: 10px;
            margin-bottom: 24px;
            font-size: 13.5px;
            line-height: 1.5;
        }

        .alert-info {
            background: var(--g11);
            border: 1px solid rgba(16,185,129,0.25);
            color: var(--g3);
        }

        .alert-icon {
            font-size: 18px;
            flex-shrink: 0;
            margin-top: 1px;
        }

        /* Error box */
        .error-box {
            background: #fff1f2;
            border: 1px solid #fecdd3;
            border-radius: 10px;
            padding: 14px 16px;
            margin-bottom: 20px;
        }

        .error-box div {
            color: #be123c;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .error-box div + div { margin-top: 6px; }

        /* Form */
        .form-section {
            margin-bottom: 28px;
        }

        .section-label {
            font-family: 'Syne', sans-serif;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--g4);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-label::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-dark);
            margin-bottom: 7px;
        }

        .input-wrap {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 16px;
            pointer-events: none;
        }

        input[type="text"],
        input[type="tel"] {
            width: 100%;
            padding: 12px 14px 12px 42px;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            color: var(--text-dark);
            background: #fafafa;
            transition: all 0.2s;
            letter-spacing: 0.3px;
        }

        input[type="text"]:focus,
        input[type="tel"]:focus {
            outline: none;
            border-color: var(--g5);
            background: white;
            box-shadow: 0 0 0 3px rgba(5,150,105,0.1);
        }

        input.verified {
            border-color: var(--g6);
            background: #f0fdf4;
        }

        .input-hint {
            font-size: 11.5px;
            color: var(--text-soft);
            margin-top: 5px;
        }

        .field-error {
            font-size: 12px;
            color: #dc2626;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* BVN verify section */
        .bvn-group {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 10px;
            align-items: end;
        }

        .bvn-group .form-group { margin-bottom: 0; }

        /* Verify status */
        .verify-status {
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 13px;
            margin-top: 12px;
            display: none;
        }

        .verify-status.loading {
            display: block;
            background: var(--g11);
            color: var(--g4);
            border: 1px solid var(--border);
        }

        .verify-status.success {
            display: block;
            background: #f0fdf4;
            color: var(--g3);
            border: 1px solid rgba(16,185,129,0.3);
        }

        .verify-status.error {
            display: block;
            background: #fff1f2;
            color: #be123c;
            border: 1px solid #fecdd3;
        }

        /* OTP Section */
        .otp-section {
            display: none;
            padding: 20px;
            background: var(--g11);
            border-radius: 12px;
            border: 1px solid var(--border);
            margin-top: 16px;
        }

        .otp-section.visible { display: block; }

        .otp-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 14px;
        }

        .otp-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--g5), var(--g6));
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }

        .otp-title {
            font-family: 'Syne', sans-serif;
            font-size: 14px;
            font-weight: 600;
            color: var(--g3);
        }

        .otp-subtitle {
            font-size: 12px;
            color: var(--text-soft);
        }

        .otp-inputs {
            display: flex;
            gap: 8px;
            margin: 16px 0 14px;
        }

        .otp-digit {
            flex: 1;
            height: 52px;
            text-align: center;
            font-family: 'Syne', sans-serif;
            font-size: 20px;
            font-weight: 700;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            background: white;
            color: var(--text-dark);
            transition: all 0.2s;
            caret-color: transparent;
        }

        .otp-digit:focus {
            outline: none;
            border-color: var(--g5);
            box-shadow: 0 0 0 3px rgba(5,150,105,0.12);
        }

        .otp-digit.filled { border-color: var(--g6); background: #f0fdf4; }
        .otp-digit.error-digit { border-color: #f43f5e; background: #fff1f2; }

        .resend-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 12.5px;
            color: var(--text-soft);
        }

        .resend-btn {
            background: none;
            border: none;
            color: var(--g5);
            font-size: 12.5px;
            font-weight: 500;
            cursor: pointer;
            font-family: 'DM Sans', sans-serif;
        }

        .resend-btn:disabled { color: var(--text-soft); cursor: default; }

        /* Verified badge */
        .verified-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #f0fdf4;
            border: 1px solid rgba(16,185,129,0.3);
            border-radius: 20px;
            padding: 6px 14px;
            font-size: 13px;
            font-weight: 500;
            color: var(--g3);
            margin-top: 10px;
        }

        /* Buttons */
        .btn {
            font-family: 'DM Sans', sans-serif;
            font-weight: 500;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }

        .btn-primary {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--g4) 0%, var(--g6) 100%);
            color: white;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            letter-spacing: 0.2px;
            position: relative;
            overflow: hidden;
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, var(--g3) 0%, var(--g5) 100%);
            opacity: 0;
            transition: opacity 0.2s;
        }

        .btn-primary:hover:not(:disabled)::before { opacity: 1; }
        .btn-primary:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 8px 24px rgba(5,150,105,0.35); }
        .btn-primary:active:not(:disabled) { transform: translateY(0); }

        .btn-primary span { position: relative; z-index: 1; }

        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-secondary {
            padding: 12px 20px;
            background: var(--g11);
            border: 1.5px solid var(--border);
            color: var(--g4);
            border-radius: 10px;
            font-size: 13.5px;
            white-space: nowrap;
            height: 47px;
        }

        .btn-secondary:hover:not(:disabled) {
            background: var(--g10);
            border-color: var(--g6);
        }

        .btn-secondary:disabled { opacity: 0.5; cursor: not-allowed; }

        .btn-confirm-otp {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, var(--g4), var(--g6));
            color: white;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            margin-top: 4px;
        }

        .btn-confirm-otp:hover:not(:disabled) {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .btn-confirm-otp:disabled { opacity: 0.5; cursor: not-allowed; }

        /* Divider */
        .divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--border), transparent);
            margin: 24px 0;
        }

        /* Submit area */
        .submit-area { margin-top: 4px; }

        .submit-note {
            text-align: center;
            font-size: 12px;
            color: var(--text-soft);
            margin-top: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        /* Spinner */
        .spinner {
            display: inline-block;
            width: 14px; height: 14px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
            vertical-align: middle;
            margin-right: 4px;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        /* Footer */
        .card-footer {
            padding: 18px 36px;
            background: var(--g11);
            border-top: 1px solid var(--border);
            font-size: 12.5px;
            color: var(--text-soft);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-footer a { color: var(--g5); text-decoration: none; font-weight: 500; }

        /* File upload */
        input[type="file"] { display: none; }

        .file-upload-label {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 16px;
            background: #fafafa;
            border: 1.5px dashed #d1d5db;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .file-upload-label:hover {
            background: var(--g11);
            border-color: var(--g6);
        }

        .file-upload-label.uploaded {
            background: #f0fdf4;
            border-color: var(--g6);
            border-style: solid;
        }

        .file-upload-icon { font-size: 22px; flex-shrink: 0; }

        .file-upload-text {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .file-upload-text strong {
            font-size: 13.5px;
            color: var(--text-dark);
            font-weight: 500;
        }

        .file-upload-text small {
            font-size: 11.5px;
            color: var(--text-soft);
        }

        .file-upload-badge {
            width: 26px; height: 26px;
            background: var(--g6);
            border-radius: 50%;
            color: white;
            font-size: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .file-name {
            font-size: 12px;
            color: var(--g4);
            font-weight: 500;
            margin-top: 6px;
            padding-left: 2px;
        }

        @media (max-width: 480px) {
            .card-header, .card-body { padding-left: 22px; padding-right: 22px; }
            .steps { padding-left: 22px; padding-right: 22px; }
            .card-footer { flex-direction: column; gap: 6px; text-align: center; }
            .bvn-group { grid-template-columns: 1fr; }
            .otp-inputs { gap: 6px; }
        }
    </style>
</head>
<body>

<div class="wrapper">

    <!-- Brand -->
    <div class="brand-header">
        <div class="brand-logo">A</div>
        <div class="brand-name">A<span>-Pay</span></div>
    </div>

    <div class="card">

        <!-- Header -->
        <div class="card-header">
            <div class="header-badge">Secure Verification</div>
            <h1>Identity Verification</h1>
            <p>Complete your KYC to unlock full access to your A-Pay account.</p>
        </div>

        <!-- Steps -->
        <div class="steps">
            <div class="step active" id="step-indicator-1">
                <div class="step-num">1</div>
                <div class="step-label">BVN</div>
            </div>
            <div class="step-connector"></div>
            <div class="step" id="step-indicator-2">
                <div class="step-num">2</div>
                <div class="step-label">Verify OTP</div>
            </div>
            <div class="step-connector"></div>
            <div class="step" id="step-indicator-3">
                <div class="step-num">3</div>
                <div class="step-label">Documents</div>
            </div>
            <div class="step-connector"></div>
            <div class="step" id="step-indicator-4">
                <div class="step-num">4</div>
                <div class="step-label">Submit</div>
            </div>
        </div>

        <!-- Body -->
        <div class="card-body">

            <!-- Requirement note -->
            <div class="alert alert-info">
                <span class="alert-icon">🔒</span>
                <span>KYC is required for accounts with a balance of <strong>₦100,000 or more</strong>. Your information is encrypted and secure.</span>
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
                <input type="hidden" name="bvn" id="bvn_hidden">
                <input type="hidden" id="otp_reference" value="">

                <!-- BVN Section -->
                <div class="form-section">
                    <div class="section-label">Step 1 — Bank Verification Number</div>

                    <div class="bvn-group">
                        <div class="form-group">
                            <label for="bvn_input">BVN</label>
                            <div class="input-wrap">
                                <span class="input-icon">🏦</span>
                                <input type="tel" id="bvn_input" placeholder="Enter your 11-digit BVN"
                                    maxlength="11" inputmode="numeric" value="{{ old('bvn') }}" autocomplete="off">
                            </div>
                        </div>
                        <button type="button" class="btn btn-secondary" id="verifyBtn" onclick="initiateBvn()">
                            Verify BVN
                        </button>
                    </div>

                    <div class="input-hint">Your BVN is used only for identity verification — never shared.</div>
                    <div id="verify-status" class="verify-status"></div>

                    <!-- OTP Box -->
                    <div class="otp-section" id="otpSection">
                        <div class="otp-header">
                            <div class="otp-icon">📲</div>
                            <div>
                                <div class="otp-title">Enter OTP</div>
                                <div class="otp-subtitle" id="otp-desc">A 6-digit code was sent to your BVN-linked number</div>
                            </div>
                        </div>
                        <div class="otp-inputs">
                            <input class="otp-digit" type="tel" maxlength="1" inputmode="numeric" id="d1">
                            <input class="otp-digit" type="tel" maxlength="1" inputmode="numeric" id="d2">
                            <input class="otp-digit" type="tel" maxlength="1" inputmode="numeric" id="d3">
                            <input class="otp-digit" type="tel" maxlength="1" inputmode="numeric" id="d4">
                            <input class="otp-digit" type="tel" maxlength="1" inputmode="numeric" id="d5">
                            <input class="otp-digit" type="tel" maxlength="1" inputmode="numeric" id="d6">
                        </div>
                        <div class="resend-row">
                            <span id="resend-timer">Resend in <strong>60s</strong></span>
                            <button type="button" class="resend-btn" id="resendBtn" onclick="resendOtp()" disabled>Resend OTP</button>
                        </div>
                        <div id="otp-status" class="verify-status" style="margin-top:12px;"></div>
                        <button type="button" class="btn btn-confirm-otp" id="confirmOtpBtn" onclick="confirmOtp()" disabled>
                            <span>Confirm OTP</span>
                        </button>
                    </div>

                    <!-- Verified state -->
                    <div id="verified-display" style="display:none;">
                        <div class="verified-badge">
                            ✅ <span id="verified-name">Identity Verified</span>
                        </div>
                    </div>
                </div>

                <div class="divider"></div>

                <!-- Step 3 — Documents & Identity Numbers -->
                <div class="form-section">
                    <div class="section-label">Step 3 — Documents & Identity</div>

                    <!-- BVN Phone -->
                    <div class="form-group">
                        <label for="bvn_phone">BVN Registered Phone Number</label>
                        <div class="input-wrap">
                            <span class="input-icon">📱</span>
                            <input type="tel" id="bvn_phone" name="bvn_phone"
                                placeholder="e.g. 08012345678" maxlength="11"
                                inputmode="numeric" value="{{ old('bvn_phone') }}" autocomplete="off">
                        </div>
                        <div class="input-hint">The phone number registered with your BVN.</div>
                    </div>

                    <!-- NIN -->
                    <div class="form-group">
                        <label for="nin_input">National Identification Number (NIN)</label>
                        <div class="input-wrap">
                            <span class="input-icon">🪪</span>
                            <input type="tel" id="nin_input" name="nin"
                                placeholder="Enter your 11-digit NIN" maxlength="11"
                                inputmode="numeric" value="{{ old('nin') }}" autocomplete="off">
                        </div>
                        <div class="input-hint">Found on your National ID card, e-ID slip, or NIMC app.</div>
                    </div>

                    <!-- Passport Photo -->
                    <div class="form-group">
                        <label>Passport Photograph</label>
                        <label for="passport_photo" class="file-upload-label" id="passport-label">
                            <span class="file-upload-icon">📸</span>
                            <span class="file-upload-text">
                                <strong>Click to upload passport photo</strong>
                                <small>JPG or PNG · Max 2MB</small>
                            </span>
                            <span class="file-upload-badge" id="passport-badge" style="display:none;">✓</span>
                        </label>
                        <input type="file" id="passport_photo" name="passport_photo"
                            accept="image/jpeg,image/png,image/jpg"
                            onchange="handleFileSelect(this, 'passport-label', 'passport-badge', 'passport-name')">
                        <div class="file-name" id="passport-name"></div>
                    </div>

                    <!-- Proof of Address -->
                    <div class="form-group">
                        <label>Proof of Address</label>
                        <label for="proof_of_address" class="file-upload-label" id="proof-label">
                            <span class="file-upload-icon">📄</span>
                            <span class="file-upload-text">
                                <strong>Click to upload proof of address</strong>
                                <small>PDF, JPG or PNG · Max 2MB</small>
                            </span>
                            <span class="file-upload-badge" id="proof-badge" style="display:none;">✓</span>
                        </label>
                        <input type="file" id="proof_of_address" name="proof_of_address"
                            accept="image/jpeg,image/png,image/jpg,application/pdf"
                            onchange="handleFileSelect(this, 'proof-label', 'proof-badge', 'proof-name')">
                        <div class="file-name" id="proof-name"></div>
                        <div class="input-hint">Utility bill, bank statement, or government-issued document.</div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="submit-area">
                    <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                        <span>Complete Verification</span>
                    </button>
                    <div class="submit-note">
                        🔒 256-bit encrypted · Powered by Paystack
                    </div>
                </div>

            </form>
        </div>

        <div class="card-footer">
            <span>Need help? <a href="https://wa.me/2349079916807">WhatsApp Support</a></span>
            <span>© 2025 A-Pay</span>
        </div>
    </div>
</div>

<script>
    const ROUTES = {
        initiateBvn: '{{ route("kyc.initiate-bvn") }}',
        confirmOtp:  '{{ route("kyc.confirm-otp") }}',
    };
    const CSRF    = '{{ csrf_token() }}';
    const USER_ID = '{{ $user->id }}';

    let otpReference = '';
    let resendTimer = null;
    let bvnVerified = false;

    /* ── OTP digit inputs ── */
    const digits = ['d1','d2','d3','d4','d5','d6'].map(id => document.getElementById(id));

    digits.forEach((el, i) => {
        el.addEventListener('input', () => {
            el.value = el.value.replace(/\D/g, '');
            el.classList.toggle('filled', !!el.value);
            if (el.value && i < 5) digits[i+1].focus();
            document.getElementById('confirmOtpBtn').disabled = getOtp().length < 6;
        });
        el.addEventListener('keydown', e => {
            if (e.key === 'Backspace' && !el.value && i > 0) digits[i-1].focus();
        });
        el.addEventListener('paste', e => {
            e.preventDefault();
            const pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g,'');
            pasted.split('').slice(0,6).forEach((ch, idx) => {
                if (digits[idx]) { digits[idx].value = ch; digits[idx].classList.add('filled'); }
            });
            digits[Math.min(pasted.length, 5)].focus();
            document.getElementById('confirmOtpBtn').disabled = getOtp().length < 6;
        });
    });

    function getOtp() { return digits.map(d => d.value).join(''); }

    function setStatus(id, type, msg) {
        const el = document.getElementById(id);
        el.className = 'verify-status ' + type;
        el.innerHTML = msg;
    }

    /* ── Step 1: Initiate BVN ── */
    async function initiateBvn() {
        const bvn = document.getElementById('bvn_input').value.trim();
        if (bvn.length !== 11) {
            setStatus('verify-status', 'error', '❌ BVN must be exactly 11 digits.');
            return;
        }

        const btn = document.getElementById('verifyBtn');
        btn.disabled = true;
        btn.textContent = 'Sending OTP…';
        setStatus('verify-status', 'loading', '<span class="spinner"></span> Contacting Paystack for verification…');

        try {
            const res  = await fetch(ROUTES.initiateBvn, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ bvn, user_id: USER_ID })
            });
            const data = await res.json();

            if (data.success) {
                otpReference = data.reference;
                document.getElementById('otp_reference').value = otpReference;

                const maskedPhone = data.masked_phone || 'your registered number';
                document.getElementById('otp-desc').textContent =
                    `A 6-digit OTP was sent to ${maskedPhone}`;

                setStatus('verify-status', 'success', `✅ OTP sent! Check your BVN-linked phone.`);
                document.getElementById('otpSection').classList.add('visible');
                digits[0].focus();
                setStep(2);
                startResendTimer();
            } else {
                setStatus('verify-status', 'error', `❌ ${data.message}`);
                btn.disabled = false;
                btn.textContent = 'Verify BVN';
            }
        } catch {
            setStatus('verify-status', 'error', '❌ Network error. Please try again.');
            btn.disabled = false;
            btn.textContent = 'Verify BVN';
        }
    }

    /* ── Step 2: Confirm OTP ── */
    async function confirmOtp() {
        const bvn = document.getElementById('bvn_input').value.trim();
        const otp = getOtp();

        if (otp.length < 6) {
            setStatus('otp-status', 'error', '❌ Enter all 6 digits.');
            return;
        }

        const btn = document.getElementById('confirmOtpBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span> Verifying…';
        setStatus('otp-status', 'loading', '<span class="spinner"></span> Confirming identity with Paystack…');

        try {
            const res  = await fetch(ROUTES.confirmOtp, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ bvn, otp, reference: otpReference, user_id: USER_ID })
            });
            const data = await res.json();

            if (data.success) {
                // Mark BVN verified
                bvnVerified = true;
                document.getElementById('bvn_verified').value = '1';
                document.getElementById('bvn_hidden').value = bvn;
                document.getElementById('bvn_input').classList.add('verified');
                document.getElementById('bvn_input').readOnly = true;

                // Show verified badge
                document.getElementById('verified-name').textContent =
                    '✓ ' + (data.full_name || 'Identity Verified');
                document.getElementById('verified-display').style.display = 'block';

                // Hide OTP box and status
                document.getElementById('otpSection').classList.remove('visible');
                document.getElementById('verify-status').style.display = 'none';

                document.getElementById('verifyBtn').style.display = 'none';

                setStep(3);
                document.getElementById('submitBtn').disabled = false;
                clearInterval(resendTimer);
            } else {
                digits.forEach(d => d.classList.add('error-digit'));
                setTimeout(() => digits.forEach(d => d.classList.remove('error-digit')), 1000);
                setStatus('otp-status', 'error', `❌ ${data.message}`);
                btn.disabled = false;
                btn.innerHTML = '<span>Confirm OTP</span>';
            }
        } catch {
            setStatus('otp-status', 'error', '❌ Network error. Please try again.');
            btn.disabled = false;
            btn.innerHTML = '<span>Confirm OTP</span>';
        }
    }

    /* ── Resend OTP ── */
    function resendOtp() {
        digits.forEach(d => { d.value = ''; d.classList.remove('filled'); });
        document.getElementById('confirmOtpBtn').disabled = true;
        initiateBvn();
    }

    function startResendTimer(seconds = 60) {
        clearInterval(resendTimer);
        let s = seconds;
        const timerEl = document.getElementById('resend-timer');
        const resendBtn = document.getElementById('resendBtn');
        resendBtn.disabled = true;

        resendTimer = setInterval(() => {
            s--;
            timerEl.innerHTML = `Resend in <strong>${s}s</strong>`;
            if (s <= 0) {
                clearInterval(resendTimer);
                timerEl.textContent = '';
                resendBtn.disabled = false;
            }
        }, 1000);
    }

    /* ── Step indicator (4 steps) ── */
    function setStep(n) {
        for (let i = 1; i <= 4; i++) {
            const el = document.getElementById('step-indicator-' + i);
            if (!el) continue;
            el.classList.remove('active', 'done');
            if (i < n) el.classList.add('done');
            else if (i === n) el.classList.add('active');
        }
    }

    /* ── File upload handler ── */
    function handleFileSelect(input, labelId, badgeId, nameId) {
        const label = document.getElementById(labelId);
        const badge = document.getElementById(badgeId);
        const name  = document.getElementById(nameId);

        if (input.files && input.files[0]) {
            const file = input.files[0];
            label.classList.add('uploaded');
            badge.style.display = 'flex';
            name.textContent = '✓ ' + file.name;
        } else {
            label.classList.remove('uploaded');
            badge.style.display = 'none';
            name.textContent = '';
        }
    }

    /* ── Form guard ── */
    document.getElementById('kycForm').addEventListener('submit', e => {
        if (document.getElementById('bvn_verified').value !== '1') {
            e.preventDefault();
            alert('Please complete BVN verification before submitting.');
        }
    });
</script>
