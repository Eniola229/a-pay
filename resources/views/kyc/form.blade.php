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
            --g1:#022c22;--g2:#064e3b;--g3:#065f46;--g4:#047857;
            --g5:#059669;--g6:#10b981;--g7:#34d399;--g9:#a7f3d0;
            --g10:#d1fae5;--g11:#ecfdf5;--white:#ffffff;
            --text-dark:#022c22;--text-soft:#6b7280;
            --border:rgba(16,185,129,0.18);
            --shadow:0 24px 80px rgba(5,150,105,0.18);
        }
        *,*::before,*::after{margin:0;padding:0;box-sizing:border-box}

        body{
            font-family:'DM Sans',sans-serif;
            background:var(--g1);
            min-height:100vh;
            display:flex;align-items:flex-start;justify-content:center;
            padding:32px 16px 64px;
            position:relative;
        }

        html::before{
            content:'';position:fixed;inset:0;
            background:radial-gradient(ellipse 60% 50% at 10% 0%,rgba(16,185,129,0.2) 0%,transparent 60%),
                        radial-gradient(ellipse 40% 60% at 90% 100%,rgba(52,211,153,0.12) 0%,transparent 60%);
            pointer-events:none;z-index:0;
        }
        html::after{
            content:'';position:fixed;inset:0;
            background-image:linear-gradient(rgba(16,185,129,0.04) 1px,transparent 1px),
                              linear-gradient(90deg,rgba(16,185,129,0.04) 1px,transparent 1px);
            background-size:40px 40px;pointer-events:none;z-index:0;
        }

        .wrapper{width:100%;max-width:520px;position:relative;z-index:1;}

        .brand-header{display:flex;align-items:center;gap:12px;margin-bottom:28px;}
        .brand-logo{
            width:44px;height:44px;
            background:linear-gradient(135deg,var(--g5),var(--g7));
            border-radius:12px;display:flex;align-items:center;justify-content:center;
            font-family:'Syne',sans-serif;font-weight:800;font-size:18px;color:var(--g1);
            box-shadow:0 4px 16px rgba(16,185,129,0.4);flex-shrink:0;
        }
        .brand-name{font-family:'Syne',sans-serif;font-size:22px;font-weight:700;color:var(--white);letter-spacing:-0.3px;}
        .brand-name span{color:var(--g7);}

        .card{
            background:rgba(255,255,255,0.97);border-radius:24px;overflow:hidden;
            box-shadow:var(--shadow),0 0 0 1px rgba(16,185,129,0.1);
        }

        /* ── Header ── */
        .card-header{
            padding:32px 36px 28px;
            background:linear-gradient(135deg,var(--g3) 0%,var(--g5) 100%);
            position:relative;overflow:hidden;
        }
        .card-header::before{content:'';position:absolute;top:-40px;right:-40px;width:160px;height:160px;border-radius:50%;background:rgba(255,255,255,0.06);}
        .card-header::after{content:'';position:absolute;bottom:-60px;left:60px;width:220px;height:220px;border-radius:50%;background:rgba(52,211,153,0.1);}
        .header-badge{
            display:inline-flex;align-items:center;gap:6px;
            background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.2);
            border-radius:20px;padding:5px 12px;font-size:11px;font-weight:500;
            color:var(--g10);letter-spacing:0.5px;text-transform:uppercase;margin-bottom:16px;
        }
        .header-badge::before{content:'';width:6px;height:6px;border-radius:50%;background:var(--g7);box-shadow:0 0 6px var(--g7);}
        .card-header h1{font-family:'Syne',sans-serif;font-size:26px;font-weight:700;color:var(--white);margin-bottom:8px;position:relative;z-index:1;}
        .card-header p{font-size:14px;color:rgba(255,255,255,0.75);line-height:1.5;position:relative;z-index:1;}

        /* ── Steps ── */
        .steps{display:flex;align-items:center;padding:20px 36px;background:var(--g11);border-bottom:1px solid var(--border);}
        .step{display:flex;align-items:center;gap:8px;flex:1;}
        .step-num{
            width:28px;height:28px;border-radius:50%;background:var(--g10);border:2px solid var(--border);
            display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:600;
            color:var(--text-soft);flex-shrink:0;transition:all 0.3s;
        }
        .step.active .step-num{background:var(--g5);border-color:var(--g5);color:white;box-shadow:0 0 0 4px rgba(5,150,105,0.15);}
        .step.done .step-num{background:var(--g6);border-color:var(--g6);color:white;}
        .step-label{font-size:12px;font-weight:500;color:var(--text-soft);}
        .step.active .step-label{color:var(--g4);}
        .step.done .step-label{color:var(--g5);}
        .step-connector{flex:1;height:2px;background:var(--border);margin:0 8px;}
        .step.done+.step-connector{background:var(--g6);}

        /* ── Body ── */
        .card-body{padding:28px 36px;}

        .alert{display:flex;gap:12px;padding:14px 16px;border-radius:10px;margin-bottom:24px;font-size:13.5px;line-height:1.5;}
        .alert-info{background:var(--g11);border:1px solid rgba(16,185,129,0.25);color:var(--g3);}
        .alert-icon{font-size:18px;flex-shrink:0;margin-top:1px;}

        .error-box{background:#fff1f2;border:1px solid #fecdd3;border-radius:10px;padding:14px 16px;margin-bottom:20px;}
        .error-box div{color:#be123c;font-size:13px;display:flex;align-items:center;gap:6px;}
        .error-box div+div{margin-top:6px;}

        /* ── Verification panels ── */
        .verify-panel{
            border:1.5px solid var(--border);border-radius:14px;
            margin-bottom:20px;overflow:hidden;transition:all 0.3s;
        }
        .verify-panel.locked{opacity:0.5;pointer-events:none;}
        .verify-panel.done{border-color:var(--g6);}

        .panel-header{
            display:flex;align-items:center;gap:12px;
            padding:16px 20px;background:var(--g11);
            border-bottom:1px solid var(--border);
        }
        .panel-header.done-bg{background:#f0fdf4;border-bottom-color:rgba(16,185,129,0.2);}

        .panel-num{
            width:32px;height:32px;border-radius:10px;
            background:var(--g10);border:2px solid var(--border);
            display:flex;align-items:center;justify-content:center;
            font-family:'Syne',sans-serif;font-size:13px;font-weight:700;
            color:var(--g4);flex-shrink:0;
        }
        .panel-num.active-num{background:var(--g5);border-color:var(--g5);color:white;}
        .panel-num.done-num{background:var(--g6);border-color:var(--g6);color:white;}

        .panel-title{font-family:'Syne',sans-serif;font-size:13.5px;font-weight:700;color:var(--g3);}
        .panel-subtitle{font-size:12px;color:var(--text-soft);margin-top:1px;}

        .panel-body{padding:20px;}

        /* ── Form elements ── */
        .form-group{margin-bottom:16px;}
        label{display:block;font-size:13px;font-weight:500;color:var(--text-dark);margin-bottom:7px;}
        .input-wrap{position:relative;}
        .input-icon{position:absolute;left:14px;top:50%;transform:translateY(-50%);font-size:16px;pointer-events:none;}

        input[type="text"],input[type="tel"]{
            width:100%;padding:12px 14px 12px 42px;
            border:1.5px solid #e5e7eb;border-radius:10px;
            font-family:'DM Sans',sans-serif;font-size:14px;color:var(--text-dark);
            background:#fafafa;transition:all 0.2s;letter-spacing:0.3px;
        }
        input[type="text"]:focus,input[type="tel"]:focus{
            outline:none;border-color:var(--g5);background:white;
            box-shadow:0 0 0 3px rgba(5,150,105,0.1);
        }
        input.verified{border-color:var(--g6);background:#f0fdf4;}
        .input-hint{font-size:11.5px;color:var(--text-soft);margin-top:5px;}

        .two-col{display:grid;grid-template-columns:1fr 1fr;gap:14px;}

        /* ── Verify status box ── */
        .verify-status{padding:12px 16px;border-radius:10px;font-size:13px;margin-top:12px;display:none;}
        .verify-status.loading{display:block;background:var(--g11);color:var(--g4);border:1px solid var(--border);}
        .verify-status.success{display:block;background:#f0fdf4;color:var(--g3);border:1px solid rgba(16,185,129,0.3);}
        .verify-status.error{display:block;background:#fff1f2;color:#be123c;border:1px solid #fecdd3;}

        /* ── Verified badge ── */
        .verified-badge{
            display:inline-flex;align-items:center;gap:6px;
            background:#f0fdf4;border:1px solid rgba(16,185,129,0.3);
            border-radius:20px;padding:6px 14px;font-size:13px;font-weight:500;color:var(--g3);
        }

        /* ── Buttons ── */
        .btn{font-family:'DM Sans',sans-serif;font-weight:500;cursor:pointer;border:none;transition:all 0.2s;}

        .btn-verify{
            width:100%;padding:13px;
            background:var(--g11);border:1.5px solid var(--g6);
            color:var(--g3);border-radius:10px;
            font-size:14px;font-weight:600;margin-top:4px;
        }
        .btn-verify:hover:not(:disabled){background:var(--g10);border-color:var(--g4);}
        .btn-verify:disabled{opacity:0.5;cursor:not-allowed;}

        .btn-primary{
            width:100%;padding:14px;
            background:linear-gradient(135deg,var(--g4) 0%,var(--g6) 100%);
            color:white;border-radius:12px;font-size:15px;font-weight:600;
            position:relative;overflow:hidden;
        }
        .btn-primary::before{content:'';position:absolute;inset:0;background:linear-gradient(135deg,var(--g3) 0%,var(--g5) 100%);opacity:0;transition:opacity 0.2s;}
        .btn-primary:hover:not(:disabled)::before{opacity:1;}
        .btn-primary:hover:not(:disabled){transform:translateY(-1px);box-shadow:0 8px 24px rgba(5,150,105,0.35);}
        .btn-primary span{position:relative;z-index:1;}
        .btn-primary:disabled{opacity:0.5;cursor:not-allowed;}

        .divider{height:1px;background:linear-gradient(90deg,transparent,var(--border),transparent);margin:20px 0;}

        /* ── Documents section ── */
        .docs-section{opacity:0.45;pointer-events:none;transition:opacity 0.4s;}
        .docs-section.unlocked{opacity:1;pointer-events:all;}

        .section-label{
            font-family:'Syne',sans-serif;font-size:11px;font-weight:600;
            letter-spacing:1px;text-transform:uppercase;color:var(--g4);
            margin-bottom:16px;display:flex;align-items:center;gap:8px;
        }
        .section-label::after{content:'';flex:1;height:1px;background:var(--border);}

        input[type="file"]{display:none;}
        .file-upload-label{
            display:flex;align-items:center;gap:14px;padding:14px 16px;
            background:#fafafa;border:1.5px dashed #d1d5db;border-radius:10px;
            cursor:pointer;transition:all 0.2s;
        }
        .file-upload-label:hover{background:var(--g11);border-color:var(--g6);}
        .file-upload-label.uploaded{background:#f0fdf4;border-color:var(--g6);border-style:solid;}
        .file-upload-icon{font-size:22px;flex-shrink:0;}
        .file-upload-text{flex:1;display:flex;flex-direction:column;gap:2px;}
        .file-upload-text strong{font-size:13.5px;color:var(--text-dark);font-weight:500;}
        .file-upload-text small{font-size:11.5px;color:var(--text-soft);}
        .file-upload-badge{width:26px;height:26px;background:var(--g6);border-radius:50%;color:white;font-size:13px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
        .file-name{font-size:12px;color:var(--g4);font-weight:500;margin-top:6px;padding-left:2px;}

        .submit-area{margin-top:4px;}
        .submit-note{text-align:center;font-size:12px;color:var(--text-soft);margin-top:14px;display:flex;align-items:center;justify-content:center;gap:5px;}

        /* ── Spinner ── */
        .spinner{display:inline-block;width:14px;height:14px;border:2px solid rgba(255,255,255,0.3);border-top-color:white;border-radius:50%;animation:spin 0.6s linear infinite;vertical-align:middle;margin-right:4px;}
        .spinner.dark{border-color:rgba(5,150,105,0.3);border-top-color:var(--g4);}
        @keyframes spin{to{transform:rotate(360deg)}}

        .card-footer{
            padding:18px 36px;background:var(--g11);border-top:1px solid var(--border);
            font-size:12.5px;color:var(--text-soft);display:flex;align-items:center;justify-content:space-between;
        }
        .card-footer a{color:var(--g5);text-decoration:none;font-weight:500;}

        @media(max-width:480px){
            .card-header,.card-body{padding-left:22px;padding-right:22px;}
            .steps{padding-left:22px;padding-right:22px;}
            .card-footer{flex-direction:column;gap:6px;text-align:center;}
            .two-col{grid-template-columns:1fr;}
        }
    </style>
</head>
<body>
<div class="wrapper">

    <div class="brand-header">
        <div class="brand-logo">A</div>
        <div class="brand-name">A<span>-Pay</span></div>
    </div>

    <div class="card">

        <div class="card-header">
            <div class="header-badge">Secure Verification</div>
            <h1>Identity Verification</h1>
            <p>Complete your KYC to unlock full access to your A-Pay account.</p>
        </div>

        <div class="steps">
            <div class="step active" id="step-indicator-1">
                <div class="step-num">1</div>
                <div class="step-label">BVN</div>
            </div>
            <div class="step-connector"></div>
            <div class="step" id="step-indicator-2">
                <div class="step-num">2</div>
                <div class="step-label">NIN</div>
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

        <div class="card-body">

            <div class="alert alert-info">
                <span class="alert-icon">🔒</span>
                <span>Required for accounts with <strong>₦100,000 or more</strong>. Your BVN and NIN are verified in real-time against government databases.</span>
            </div>

            @if ($errors->any())
            <div class="error-box">
                @foreach ($errors->all() as $error)
                    <div>❌ {{ $error }}</div>
                @endforeach
            </div>
            @endif

            <form action="{{ route('kyc.submit', ['user' => $user->id]) }}" method="POST"
                  enctype="multipart/form-data" id="kycForm">
                @csrf
                <input type="hidden" name="token"        value="{{ request('token') }}">
                <input type="hidden" name="bvn_verified" id="bvn_verified" value="0">
                <input type="hidden" name="nin_verified" id="nin_verified" value="0">
                <input type="hidden" name="bvn"          id="bvn_hidden">
                <input type="hidden" name="nin"          id="nin_hidden">
                <input type="hidden" name="bvn_phone"    id="bvn_phone_hidden">

                {{-- ══════════════════════════════════════════════════════════ --}}
                {{-- PANEL 1 — BVN                                             --}}
                {{-- ══════════════════════════════════════════════════════════ --}}
                <div class="verify-panel" id="bvn-panel">
                    <div class="panel-header" id="bvn-panel-header">
                        <div class="panel-num active-num" id="bvn-panel-num">1</div>
                        <div>
                            <div class="panel-title">BVN Verification</div>
                            <div class="panel-subtitle">Kindly verify your BVN</div>
                        </div>
                    </div>
                    <div class="panel-body">

                        <div class="two-col">
                            <div class="form-group">
                                <label for="first_name">First Name</label>
                                <div class="input-wrap">
                                    <span class="input-icon">👤</span>
                                    <input type="text" id="first_name" placeholder="As on your BVN"
                                        value="{{ old('first_name', $user->first_name ?? '') }}" autocomplete="off">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name</label>
                                <div class="input-wrap">
                                    <span class="input-icon">👤</span>
                                    <input type="text" id="last_name" placeholder="As on your BVN"
                                        value="{{ old('last_name', $user->last_name ?? '') }}" autocomplete="off">
                                </div>
                            </div>
                        </div>
                        <div class="input-hint" style="margin-top:-8px;margin-bottom:16px;">Names must match your BVN record exactly.</div>

                        <div class="form-group">
                            <label for="bvn_input">Bank Verification Number (BVN)</label>
                            <div class="input-wrap">
                                <span class="input-icon">🏦</span>
                                <input type="tel" id="bvn_input" placeholder="11-digit BVN"
                                    maxlength="11" inputmode="numeric" autocomplete="off">
                            </div>
                            <div class="input-hint">Dial *565*0# to retrieve your BVN.</div>
                        </div>

                        <div class="two-col">
                            <div class="form-group">
                                <label for="dob_input">Date of Birth</label>
                                <div class="input-wrap">
                                    <span class="input-icon">🎂</span>
                                    <input type="text" id="dob_input" placeholder="YYYY-MM-DD"
                                        maxlength="10" autocomplete="off">
                                </div>
                                <div class="input-hint">e.g. 1990-04-15</div>
                            </div>
                            <div class="form-group">
                                <label for="phone_input">BVN Phone Number</label>
                                <div class="input-wrap">
                                    <span class="input-icon">📱</span>
                                    <input type="tel" id="phone_input" placeholder="08012345678"
                                        maxlength="14" inputmode="numeric" autocomplete="off">
                                </div>
                                <div class="input-hint">Phone registered on your BVN.</div>
                            </div>
                        </div>

                        <button type="button" class="btn btn-verify" id="bvnVerifyBtn" onclick="verifyBvn()">
                            🔍 Verify BVN
                        </button>
                        <div id="bvn-status" class="verify-status"></div>

                        <div id="bvn-verified-display" style="display:none;margin-top:14px;">
                            <div class="verified-badge">✅ <span id="bvn-verified-name">BVN Verified</span></div>
                            <div class="input-hint" style="margin-top:8px;">✔ 4-factor BVN check passed. Please continue to NIN verification.</div>
                        </div>

                    </div>
                </div>

                {{-- ══════════════════════════════════════════════════════════ --}}
                {{-- PANEL 2 — NIN (locked until BVN passes)                  --}}
                {{-- ══════════════════════════════════════════════════════════ --}}
                <div class="verify-panel locked" id="nin-panel">
                    <div class="panel-header" id="nin-panel-header">
                        <div class="panel-num" id="nin-panel-num">2</div>
                        <div>
                            <div class="panel-title">NIN Verification</div>
                            <div class="panel-subtitle">Kindly verify your NIN</div>
                        </div>
                    </div>
                    <div class="panel-body">

                        <div class="alert alert-info" style="margin-bottom:16px;">
                            <span class="alert-icon">ℹ️</span>
                            <span>Your name from BVN will be used automatically. Just enter your NIN and date of birth as registered on your NIMC record.</span>
                        </div>

                        <div class="form-group">
                            <label for="nin_input">National Identification Number (NIN)</label>
                            <div class="input-wrap">
                                <span class="input-icon">🪪</span>
                                <input type="tel" id="nin_input" placeholder="11-digit NIN"
                                    maxlength="11" inputmode="numeric" autocomplete="off">
                            </div>
                            <div class="input-hint">Found on your National ID card or NIMC app.</div>
                        </div>

                        <div class="form-group">
                            <label for="nin_dob_input">Date of Birth (as on NIN)</label>
                            <div class="input-wrap">
                                <span class="input-icon">🎂</span>
                                <input type="text" id="nin_dob_input" placeholder="YYYY-MM-DD"
                                    maxlength="10" autocomplete="off">
                            </div>
                            <div class="input-hint">Must match your NIMC record. Usually the same as your BVN date of birth.</div>
                        </div>

                        <button type="button" class="btn btn-verify" id="ninVerifyBtn" onclick="verifyNin()">
                            🔍 Verify NIN
                        </button>
                        <div id="nin-status" class="verify-status"></div>

                        <div id="nin-verified-display" style="display:none;margin-top:14px;">
                            <div class="verified-badge">✅ NIN Verified &amp; Cross-Checked</div>
                            <div class="input-hint" style="margin-top:8px;">✔ NIN matches your BVN record. Please upload your documents below.</div>
                        </div>

                    </div>
                </div>

                <div class="divider"></div>

                {{-- ══════════════════════════════════════════════════════════ --}}
                {{-- DOCUMENTS (locked until both BVN + NIN pass)             --}}
                {{-- ══════════════════════════════════════════════════════════ --}}
                <div class="docs-section" id="docs-section">
                    <div class="section-label">Step 3 — Upload Documents</div>

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
                            onchange="handleFile(this,'passport-label','passport-badge','passport-name')">
                        <div class="file-name" id="passport-name"></div>
                    </div>

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
                            onchange="handleFile(this,'proof-label','proof-badge','proof-name')">
                        <div class="file-name" id="proof-name"></div>
                        <div class="input-hint">Utility bill, bank statement, or government-issued document.</div>
                    </div>
                </div>

                <div class="submit-area">
                    <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                        <span>Complete Verification</span>
                    </button>
                    <div class="submit-note">🔒 256-bit encrypted</div>
                </div>

            </form>
        </div>

        <div class="card-footer">
            <span>Need help? <a href="https://wa.me/2348152880128">WhatsApp Support</a></span>
            <span>© 2026 A-Pay</span>
        </div>
    </div>
</div>

<script>
    const ROUTE_VERIFY_BVN = '{{ route("kyc.verify-bvn") }}';
    const ROUTE_VERIFY_NIN = '{{ route("kyc.verify-nin") }}';
    const CSRF             = '{{ csrf_token() }}';
    const USER_ID          = '{{ $user->id }}';

    // ─── Helpers ────────────────────────────────────────────────────────────

    function setStatus(panelId, type, msg) {
        const el = document.getElementById(panelId + '-status');
        el.className = 'verify-status ' + type;
        el.innerHTML = msg;
    }

    function setStep(n) {
        for (let i = 1; i <= 4; i++) {
            const el = document.getElementById('step-indicator-' + i);
            if (!el) continue;
            el.classList.remove('active', 'done');
            if (i < n)        el.classList.add('done');
            else if (i === n) el.classList.add('active');
        }
    }

    function lockPanel(panelId, verifiedText) {
        // Update header
        const header = document.getElementById(panelId + '-panel-header');
        const num    = document.getElementById(panelId + '-panel-num');
        if (header) header.classList.add('done-bg');
        if (num)    { num.classList.remove('active-num'); num.classList.add('done-num'); num.textContent = '✓'; }

        // Lock all inputs inside
        const panel = document.getElementById(panelId + '-panel');
        if (panel) {
            panel.querySelectorAll('input').forEach(el => {
                el.disabled = true;
                el.classList.add('verified');
            });
            panel.classList.add('done');
        }

        // Update verify button
        const btn = document.getElementById(panelId + 'VerifyBtn');
        if (btn) {
            btn.innerHTML         = '✓ Verified';
            btn.style.background  = 'var(--g11)';
            btn.style.borderColor = 'var(--g6)';
            btn.style.color       = 'var(--g3)';
            btn.disabled          = true;
        }
    }

    // ─── Step 1: BVN ────────────────────────────────────────────────────────

    async function verifyBvn() {
        const firstName = document.getElementById('first_name').value.trim();
        const lastName  = document.getElementById('last_name').value.trim();
        const bvn       = document.getElementById('bvn_input').value.trim();
        const dob       = document.getElementById('dob_input').value.trim();
        const phone     = document.getElementById('phone_input').value.trim();

        if (!firstName || !lastName) {
            setStatus('bvn', 'error', '❌ Please enter your first and last name as they appear on your BVN.');
            return;
        }
        if (bvn.length !== 11) {
            setStatus('bvn', 'error', '❌ BVN must be exactly 11 digits.');
            return;
        }
        if (!/^\d{4}-\d{2}-\d{2}$/.test(dob)) {
            setStatus('bvn', 'error', '❌ Date of birth must be in YYYY-MM-DD format (e.g. 1990-04-15).');
            return;
        }
        if (phone.replace(/\D/g, '').length < 10) {
            setStatus('bvn', 'error', '❌ Please enter a valid phone number.');
            return;
        }

        const btn = document.getElementById('bvnVerifyBtn');
        btn.disabled  = true;
        btn.innerHTML = '<span class="spinner dark"></span> Verifying BVN…';
        setStatus('bvn', 'loading', '<span class="spinner dark"></span> Checking against government database…');

        try {
            const res  = await fetch(ROUTE_VERIFY_BVN, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body:    JSON.stringify({
                    user_id:       USER_ID,
                    first_name:    firstName,
                    last_name:     lastName,
                    bvn:           bvn,
                    date_of_birth: dob,
                    phone_number:  phone,
                }),
            });
            const data = await res.json();

            if (data.success) {
                // Store in hidden fields for form POST
                document.getElementById('bvn_hidden').value       = bvn;
                document.getElementById('bvn_phone_hidden').value = phone;
                document.getElementById('bvn_verified').value     = '1';

                // Show verified badge
                document.getElementById('bvn-verified-name').textContent =
                    data.verified_name ? '✓ Verified as: ' + data.verified_name : '✓ BVN Verified';
                document.getElementById('bvn-verified-display').style.display = 'block';

                lockPanel('bvn', data.verified_name);

                setStatus('bvn', 'success',
                    '✅ BVN verified. Name, date of birth, and phone number all matched. Please continue to NIN verification.'
                );

                // Unlock NIN panel
                const ninPanel = document.getElementById('nin-panel');
                ninPanel.classList.remove('locked');
                document.getElementById('nin-panel-num').classList.add('active-num');
                setStep(2);

                // Pre-fill NIN DOB with BVN DOB (usually the same)
                document.getElementById('nin_dob_input').value = dob;

                ninPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });

            } else {
                setStatus('bvn', 'error', '❌ ' + data.message);
                btn.disabled  = false;
                btn.innerHTML = '🔍 Verify BVN';
            }

        } catch (err) {
            setStatus('bvn', 'error', '❌ Network error. Please check your connection and try again.');
            btn.disabled  = false;
            btn.innerHTML = '🔍 Verify BVN';
        }
    }

    // ─── Step 2: NIN ────────────────────────────────────────────────────────

    async function verifyNin() {
        const nin = document.getElementById('nin_input').value.trim();
        const dob = document.getElementById('nin_dob_input').value.trim();

        if (nin.length !== 11) {
            setStatus('nin', 'error', '❌ NIN must be exactly 11 digits.');
            return;
        }
        if (!/^\d{4}-\d{2}-\d{2}$/.test(dob)) {
            setStatus('nin', 'error', '❌ Date of birth must be in YYYY-MM-DD format (e.g. 1990-04-15).');
            return;
        }

        const btn = document.getElementById('ninVerifyBtn');
        btn.disabled  = true;
        btn.innerHTML = '<span class="spinner dark"></span> Verifying NIN…';
        setStatus('nin', 'loading', '<span class="spinner dark"></span> Verifying NIN and cross-checking against your BVN…');

        try {
            const res  = await fetch(ROUTE_VERIFY_NIN, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body:    JSON.stringify({
                    user_id:       USER_ID,
                    nin:           nin,
                    date_of_birth: dob,
                }),
            });
            const data = await res.json();

            if (data.success) {
                document.getElementById('nin_hidden').value    = nin;
                document.getElementById('nin_verified').value  = '1';

                document.getElementById('nin-verified-display').style.display = 'block';

                lockPanel('nin', 'NIN Verified');
                setStatus('nin', 'success',
                    '✅ NIN verified and cross-checked against your BVN. All checks passed — please upload your documents.'
                );

                // Unlock documents section
                document.getElementById('docs-section').classList.add('unlocked');
                document.getElementById('submitBtn').disabled = false;
                setStep(3);

                document.getElementById('docs-section').scrollIntoView({ behavior: 'smooth', block: 'start' });

            } else {
                setStatus('nin', 'error', '❌ ' + data.message);
                btn.disabled  = false;
                btn.innerHTML = '🔍 Verify NIN';
            }

        } catch (err) {
            setStatus('nin', 'error', '❌ Network error. Please check your connection and try again.');
            btn.disabled  = false;
            btn.innerHTML = '🔍 Verify NIN';
        }
    }

    // ─── File uploads ────────────────────────────────────────────────────────

    function handleFile(input, labelId, badgeId, nameId) {
        const label = document.getElementById(labelId);
        const badge = document.getElementById(badgeId);
        const name  = document.getElementById(nameId);

        if (input.files && input.files[0]) {
            label.classList.add('uploaded');
            badge.style.display = 'flex';
            name.textContent    = '✓ ' + input.files[0].name;
        } else {
            label.classList.remove('uploaded');
            badge.style.display = 'none';
            name.textContent    = '';
        }
    }

    // ─── Guard submit ─────────────────────────────────────────────────────────

    document.getElementById('kycForm').addEventListener('submit', e => {
        if (document.getElementById('bvn_verified').value !== '1' ||
            document.getElementById('nin_verified').value !== '1') {
            e.preventDefault();
            setStatus('bvn', 'error', '❌ Please complete both BVN and NIN verification before submitting.');
            document.getElementById('bvn-panel').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
</script>
</body>
</html>