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

        /* Two column grid */
        .two-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        /* Select input */
        .input-select {
            width: 100%;
            padding: 12px 14px 12px 42px;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            color: var(--text-dark);
            background: #fafafa;
            transition: all 0.2s;
            appearance: none;
            cursor: pointer;
        }

        .input-select:focus {
            outline: none;
            border-color: var(--g5);
            background: white;
            box-shadow: 0 0 0 3px rgba(5,150,105,0.1);
        }

        /* Verify button — full width */
        .btn-verify {
            width: 100%;
            padding: 13px;
            background: var(--g11);
            border: 1.5px solid var(--g6);
            color: var(--g3);
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            margin-top: 4px;
            letter-spacing: 0.2px;
        }

        .btn-verify:hover:not(:disabled) {
            background: var(--g10);
            border-color: var(--g4);
        }

        .btn-verify:disabled { opacity: 0.5; cursor: not-allowed; }

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
            .two-col { grid-template-columns: 1fr; }
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
                <div class="step-label">Identity</div>
            </div>
            <div class="step-connector"></div>
            <div class="step" id="step-indicator-2">
                <div class="step-num">2</div>
                <div class="step-label">Documents</div>
            </div>
            <div class="step-connector"></div>
            <div class="step" id="step-indicator-3">
                <div class="step-num">3</div>
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
                <input type="hidden" name="token"         value="{{ request('token') }}">
                <input type="hidden" name="bvn_submitted" id="bvn_submitted" value="0">
                <input type="hidden" name="bvn"           id="bvn_hidden">
                <input type="hidden" name="bank_code"     id="bank_code_hidden">

                <!-- ── Step 1: Identity Details ───────────────────────── -->
                <div class="form-section">
                    <div class="section-label">Step 1 — Identity Details</div>

                    <!-- First & Last Name row -->
                    <div class="two-col">
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <div class="input-wrap">
                                <span class="input-icon">👤</span>
                                <input type="text" id="first_name" name="first_name"
                                    placeholder="As on your BVN"
                                    value="{{ old('first_name', $user->first_name ?? '') }}" autocomplete="off">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <div class="input-wrap">
                                <span class="input-icon">👤</span>
                                <input type="text" id="last_name" name="last_name"
                                    placeholder="As on your BVN"
                                    value="{{ old('last_name', $user->last_name ?? '') }}" autocomplete="off">
                            </div>
                        </div>
                    </div>
                    <div class="input-hint" style="margin-top:-10px; margin-bottom:18px;">Names must match your BVN record exactly.</div>

                    <!-- BVN -->
                    <div class="form-group">
                        <label for="bvn_input">Bank Verification Number (BVN)</label>
                        <div class="input-wrap">
                            <span class="input-icon">🏦</span>
                            <input type="tel" id="bvn_input" placeholder="Enter your 11-digit BVN"
                                maxlength="11" inputmode="numeric"
                                value="{{ old('bvn') }}" autocomplete="off">
                        </div>
                        <div class="input-hint">Dial *565*0# on any network to get your BVN.</div>
                    </div>

                    <!-- Bank + Account Number row -->
                    <div class="two-col">
                        <div class="form-group">
                            <label for="bank_select">Your Bank</label>
                            <div class="input-wrap">
                                <span class="input-icon">🏛️</span>
                                <select id="bank_select" class="input-select">
                                    <option value="">— Select bank —</option>
                                    <option value="044">Access Bank</option>
                                    <option value="023">Citibank</option>
                                    <option value="050">EcoBank</option>
                                    <option value="070">Fidelity Bank</option>
                                    <option value="011">First Bank</option>
                                    <option value="214">First City Monument Bank (FCMB)</option>
                                    <option value="058">Guaranty Trust Bank (GTB)</option>
                                    <option value="030">Heritage Bank</option>
                                    <option value="301">Jaiz Bank</option>
                                    <option value="082">Keystone Bank</option>
                                    <option value="526">Kuda Bank</option>
                                    <option value="076">Polaris Bank</option>
                                    <option value="101">Providus Bank</option>
                                    <option value="221">Stanbic IBTC Bank</option>
                                    <option value="068">Standard Chartered Bank</option>
                                    <option value="232">Sterling Bank</option>
                                    <option value="100">Suntrust Bank</option>
                                    <option value="032">Union Bank</option>
                                    <option value="033">United Bank for Africa (UBA)</option>
                                    <option value="215">Unity Bank</option>
                                    <option value="035">Wema Bank</option>
                                    <option value="057">Zenith Bank</option>
                                    <option value="120001">9Payment Service Bank (9PSB)</option>
                                    <option value="100004">Opay</option>
                                    <option value="100033">PalmPay</option>
                                    <option value="100002">Moniepoint</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="account_number">Account Number</label>
                            <div class="input-wrap">
                                <span class="input-icon">💳</span>
                                <input type="tel" id="account_number" name="account_number"
                                    placeholder="10-digit NUBAN"
                                    maxlength="10" inputmode="numeric"
                                    value="{{ old('account_number') }}" autocomplete="off">
                            </div>
                        </div>
                    </div>
                    <div class="input-hint" style="margin-top:-10px; margin-bottom:18px;">Use the account number linked to your BVN.</div>

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

                    <!-- Verify button + status -->
                    <button type="button" class="btn btn-verify" id="verifyBtn" onclick="validateCustomer()">
                        🔍 Verify Identity
                    </button>
                    <div id="verify-status" class="verify-status"></div>

                    <!-- Verified badge (shown after success) -->
                    <div id="verified-display" style="display:none; margin-top:14px;">
                        <div class="verified-badge">
                            ✅ <span id="verified-name">Identity Submitted For Verification</span>
                        </div>
                        <div class="input-hint" style="margin-top:8px;">
                            ✔ We will verify your details in the background. Please complete the form below.
                        </div>
                    </div>
                </div>

                <div class="divider"></div>

                <!-- ── Step 2: NIN + Documents ─────────────────────────── -->
                <div class="form-section">
                    <div class="section-label">Step 2 — NIN & Documents</div>

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
                        🔒 256-bit encrypted
                    </div>
                </div>

            </form>
        </div>

        <div class="card-footer">
            <span>Need help? <a href="https://wa.me/2349079916807">WhatsApp Support</a></span>
            <span>© 2026 A-Pay</span>
        </div>
    </div>
</div>

<script>
    const ROUTE_VALIDATE = '{{ route("kyc.validate-customer") }}';
    const CSRF           = '{{ csrf_token() }}';
    const USER_ID        = '{{ $user->id }}';

    function setStatus(type, msg) {
        const el = document.getElementById('verify-status');
        el.className = 'verify-status ' + type;
        el.innerHTML = msg;
    }

    function setStep(n) {
        for (let i = 1; i <= 3; i++) {
            const el = document.getElementById('step-indicator-' + i);
            if (!el) continue;
            el.classList.remove('active', 'done');
            if (i < n)      el.classList.add('done');
            else if (i === n) el.classList.add('active');
        }
    }

    /**
     * Send BVN + bank account to Paystack via our controller.
     * Matches: KycController@validateCustomer
     * POST /customer/:email/identification
     */
    async function validateCustomer() {
        const firstName     = document.getElementById('first_name').value.trim();
        const lastName      = document.getElementById('last_name').value.trim();
        const bvn           = document.getElementById('bvn_input').value.trim();
        const bankCode      = document.getElementById('bank_select').value;
        const accountNumber = document.getElementById('account_number').value.trim();

        // Front-end validation
        if (!firstName || !lastName) {
            setStatus('error', '❌ Please enter your first and last name as they appear on your BVN.');
            return;
        }
        if (bvn.length !== 11) {
            setStatus('error', '❌ BVN must be exactly 11 digits.');
            return;
        }
        if (!bankCode) {
            setStatus('error', '❌ Please select your bank.');
            return;
        }
        if (accountNumber.length !== 10) {
            setStatus('error', '❌ Account number must be exactly 10 digits.');
            return;
        }

        const btn = document.getElementById('verifyBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span> Verifying Identity…';
        setStatus('loading', '<span class="spinner"></span> Sending your details for Verification…');

        try {
            const res  = await fetch(ROUTE_VALIDATE, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body:    JSON.stringify({
                    user_id:        USER_ID,
                    first_name:     firstName,
                    last_name:      lastName,
                    bvn:            bvn,
                    bank_code:      bankCode,
                    account_number: accountNumber,
                }),
            });

            const data = await res.json();

            if (data.success) {
                // Store values in hidden fields for form submission
                document.getElementById('bvn_hidden').value      = bvn;
                document.getElementById('bank_code_hidden').value = bankCode;
                document.getElementById('bvn_submitted').value    = '1';

                // Show verified badge
                document.getElementById('verified-name').textContent = '✓ Details submitted';
                document.getElementById('verified-display').style.display = 'block';

                // Lock the identity fields
                ['first_name','last_name','bvn_input','bank_select','account_number']
                    .forEach(id => {
                        const el = document.getElementById(id);
                        if (el) { el.disabled = true; el.classList.add('verified'); }
                    });

                btn.innerHTML = '✓ Verified';
                btn.style.background = 'var(--g11)';
                btn.style.borderColor = 'var(--g6)';
                btn.style.color = 'var(--g3)';

                setStatus('success', '✅ Identity details sent. We will verify in the background — please complete the form below.');
                setStep(2);
                document.getElementById('submitBtn').disabled = false;

            } else {
                setStatus('error', `❌ ${data.message}`);
                btn.disabled = false;
                btn.innerHTML = '🔍 Verify Identity';
            }

        } catch (err) {
            setStatus('error', '❌ Network error. Please check your connection and try again.');
            btn.disabled = false;
            btn.innerHTML = '🔍 Verify Identity';
        }
    }

    /* File upload display */
    function handleFileSelect(input, labelId, badgeId, nameId) {
        const label = document.getElementById(labelId);
        const badge = document.getElementById(badgeId);
        const name  = document.getElementById(nameId);

        if (input.files && input.files[0]) {
            label.classList.add('uploaded');
            badge.style.display = 'flex';
            name.textContent = '✓ ' + input.files[0].name;
        } else {
            label.classList.remove('uploaded');
            badge.style.display = 'none';
            name.textContent = '';
        }
    }

    /* Guard: block form submit if Paystack validation wasn't sent */
    document.getElementById('kycForm').addEventListener('submit', e => {
        if (document.getElementById('bvn_submitted').value !== '1') {
            e.preventDefault();
            setStatus('error', '❌ Please verify your identity details before submitting.');
            document.getElementById('first_name').scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
</script>
</body>
</html>