<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KYC Status — A-Pay</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --g1: #022c22; --g3: #065f46; --g4: #047857;
            --g5: #059669; --g6: #10b981; --g7: #34d399;
            --g9: #a7f3d0; --g10: #d1fae5; --g11: #ecfdf5;
        }
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--g1);
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            padding: 32px 16px;
            position: relative; overflow: hidden;
        }

        body::before {
            content: '';
            position: fixed; inset: 0;
            background:
                radial-gradient(ellipse 70% 60% at 50% -10%, rgba(16,185,129,0.2) 0%, transparent 60%),
                radial-gradient(ellipse 40% 50% at 90% 100%, rgba(52,211,153,0.1) 0%, transparent 60%);
            pointer-events: none;
        }

        body::after {
            content: '';
            position: fixed; inset: 0;
            background-image:
                linear-gradient(rgba(16,185,129,0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(16,185,129,0.04) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
        }

        .wrapper { width: 100%; max-width: 460px; position: relative; z-index: 1; }

        .brand-header {
            display: flex; align-items: center; gap: 12px;
            margin-bottom: 24px; justify-content: center;
        }

        .brand-logo {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, var(--g5), var(--g7));
            border-radius: 11px;
            display: flex; align-items: center; justify-content: center;
            font-family: 'Syne', sans-serif; font-weight: 800; font-size: 17px;
            color: var(--g1);
            box-shadow: 0 4px 16px rgba(16,185,129,0.4);
        }

        .brand-name {
            font-family: 'Syne', sans-serif; font-size: 20px; font-weight: 700;
            color: #fff; letter-spacing: -0.3px;
        }
        .brand-name span { color: var(--g7); }

        .card {
            background: rgba(255,255,255,0.97);
            border-radius: 24px; overflow: hidden;
            box-shadow: 0 24px 80px rgba(5,150,105,0.2), 0 0 0 1px rgba(16,185,129,0.1);
        }

        /* Status-colored top bar */
        .status-bar { height: 5px; }
        .bar-approved { background: linear-gradient(90deg, var(--g4), var(--g6), var(--g7)); }
        .bar-pending  { background: linear-gradient(90deg, #f59e0b, #fbbf24, #fde68a); }
        .bar-rejected { background: linear-gradient(90deg, #dc2626, #f43f5e); }

        .card-body { padding: 40px 36px 32px; text-align: center; }

        /* Status icon */
        .status-icon-wrap {
            position: relative; width: 80px; height: 80px; margin: 0 auto 22px;
        }

        .status-ring {
            position: absolute; inset: 0; border-radius: 50%; border: 3px solid;
        }

        .ring-approved { border-color: var(--g9); animation: pulse 2s ease-in-out infinite; }
        .ring-pending  { border-color: #fde68a; animation: pulse 2s ease-in-out infinite; }
        .ring-rejected { border-color: #fecdd3; animation: pulse 2s ease-in-out infinite; }

        @keyframes pulse {
            0%,100% { transform: scale(1); opacity: 0.6; }
            50% { transform: scale(1.1); opacity: 0.2; }
        }

        .status-circle {
            position: absolute; inset: 8px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 30px;
            animation: popIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) both;
        }

        @keyframes popIn {
            from { transform: scale(0); opacity: 0; }
            to   { transform: scale(1); opacity: 1; }
        }

        .circle-approved { background: linear-gradient(135deg, var(--g4), var(--g6)); box-shadow: 0 8px 24px rgba(5,150,105,0.4); }
        .circle-pending  { background: linear-gradient(135deg, #d97706, #f59e0b); box-shadow: 0 8px 24px rgba(245,158,11,0.35); }
        .circle-rejected { background: linear-gradient(135deg, #b91c1c, #f43f5e); box-shadow: 0 8px 24px rgba(220,38,38,0.3); }

        h1 {
            font-family: 'Syne', sans-serif;
            font-size: 24px; font-weight: 700;
            color: #111827; margin-bottom: 8px;
        }

        .subtitle {
            font-size: 14.5px; color: #6b7280;
            line-height: 1.6; margin-bottom: 24px;
        }

        /* Status pill */
        .status-pill {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 8px 18px; border-radius: 30px;
            font-family: 'Syne', sans-serif;
            font-size: 13px; font-weight: 600;
            letter-spacing: 0.3px;
            margin-bottom: 24px;
        }

        .pill-approved { background: var(--g11); border: 1.5px solid rgba(16,185,129,0.3); color: var(--g3); }
        .pill-pending  { background: #fffbeb; border: 1.5px solid #fde68a; color: #92400e; }
        .pill-rejected { background: #fff1f2; border: 1.5px solid #fecdd3; color: #be123c; }

        .pill-dot {
            width: 8px; height: 8px; border-radius: 50%;
        }

        .dot-approved { background: var(--g6); box-shadow: 0 0 6px var(--g6); }
        .dot-pending  { background: #f59e0b; box-shadow: 0 0 6px #f59e0b; animation: blink 1.4s step-end infinite; }
        .dot-rejected { background: #f43f5e; }

        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.2} }

        /* Info card */
        .info-card {
            border-radius: 12px; padding: 18px; margin-bottom: 20px;
            text-align: left; border: 1px solid;
        }

        .info-card-approved { background: var(--g11); border-color: rgba(16,185,129,0.2); }
        .info-card-pending  { background: #fffbeb; border-color: #fde68a; }
        .info-card-rejected { background: #fff1f2; border-color: #fecdd3; }

        .info-card p { font-size: 13.5px; line-height: 1.6; }
        .info-card-approved p { color: var(--g3); }
        .info-card-pending  p { color: #78350f; }
        .info-card-rejected p { color: #9f1239; }

        .info-card strong { display: block; margin-bottom: 4px; font-size: 14px; }

        .support-row {
            display: flex; align-items: center; justify-content: center;
            gap: 8px; font-size: 13px; color: #9ca3af;
        }

        .support-link { color: var(--g5); font-weight: 600; text-decoration: none; border-bottom: 1px dashed var(--g9); }

        .card-footer {
            padding: 14px 36px;
            background: var(--g11); border-top: 1px solid rgba(16,185,129,0.12);
            text-align: center; font-size: 12px; color: #9ca3af;
        }

        @media(max-width:480px) {
            .card-body { padding: 30px 22px 24px; }
            .card-footer { padding: 12px 22px; }
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

        @php $status = $user->kycProfile->status; @endphp

        <div class="status-bar
            @if($status === 'APPROVED') bar-approved
            @elseif($status === 'PENDING') bar-pending
            @else bar-rejected @endif">
        </div>

        <div class="card-body">

            <div class="status-icon-wrap">
                <div class="status-ring
                    @if($status === 'APPROVED') ring-approved
                    @elseif($status === 'PENDING') ring-pending
                    @else ring-rejected @endif">
                </div>
                <div class="status-circle
                    @if($status === 'APPROVED') circle-approved
                    @elseif($status === 'PENDING') circle-pending
                    @else circle-rejected @endif">
                    @if($status === 'APPROVED') ✓
                    @elseif($status === 'PENDING') ⏳
                    @else ✕ @endif
                </div>
            </div>

            <h1>
                @if($status === 'APPROVED') Verification Approved
                @elseif($status === 'PENDING') Under Review
                @else Verification Rejected @endif
            </h1>

            <p class="subtitle">
                Hi <strong>{{ $user->name }}</strong> — here's the status of your A-Pay KYC submission.
            </p>

            <div class="status-pill
                @if($status === 'APPROVED') pill-approved
                @elseif($status === 'PENDING') pill-pending
                @else pill-rejected @endif">
                <span class="pill-dot
                    @if($status === 'APPROVED') dot-approved
                    @elseif($status === 'PENDING') dot-pending
                    @else dot-rejected @endif">
                </span>
                @if($status === 'APPROVED') Verified & Active
                @elseif($status === 'PENDING') Awaiting Review
                @else Not Approved @endif
            </div>

            <div class="info-card
                @if($status === 'APPROVED') info-card-approved
                @elseif($status === 'PENDING') info-card-pending
                @else info-card-rejected @endif">

                @if($status === 'APPROVED')
                    <p>
                        <strong>🎉 Your account is fully active!</strong>
                        Your identity has been verified and approved by the A-Pay compliance team. You can enjoy all A-Pay services without any restrictions.
                    </p>
                @elseif($status === 'PENDING')
                    <p>
                        <strong>⏳ Review in progress</strong>
                        Your KYC documents are being reviewed by our compliance team. You will receive a WhatsApp notification once the process is complete — usually within <strong>24–48 hours</strong>.
                    </p>
                @else
                    <p>
                        <strong>Reason for rejection:</strong>
                        {{ $user->kycProfile->rejection_reason ?? 'Please contact support for more details.' }}
                    </p>
                    <p style="margin-top:10px;">
                        Please reach out to our support team so we can help you resubmit with the correct information.
                    </p>
                @endif

            </div>

            <div class="support-row">
                Questions?
                <a href="https://wa.me/2349079916807" class="support-link">📲 WhatsApp Support</a>
            </div>

        </div>

        <div class="card-footer">
            You may close this page · © 2026 A-Pay
        </div>

    </div>
</div>
</body>
</html>