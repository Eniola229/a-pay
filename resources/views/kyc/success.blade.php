<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Complete — A-Pay</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --g1: #022c22; --g3: #065f46; --g4: #047857;
            --g5: #059669; --g6: #10b981; --g7: #34d399;
            --g9: #a7f3d0; --g10: #d1fae5; --g11: #ecfdf5;
            --white: #fff;
            --amber: #f59e0b; --amber-bg: #fffbeb; --amber-border: #fde68a;
        }
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--g1);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 16px;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: fixed; inset: 0;
            background:
                radial-gradient(ellipse 70% 60% at 50% -10%, rgba(16,185,129,0.25) 0%, transparent 60%),
                radial-gradient(ellipse 40% 50% at 90% 100%, rgba(52,211,153,0.12) 0%, transparent 60%);
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

        .wrapper {
            width: 100%; max-width: 480px;
            position: relative; z-index: 1;
        }

        .brand-header {
            display: flex; align-items: center; gap: 12px;
            margin-bottom: 24px; justify-content: center;
        }

        .brand-logo {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, var(--g5), var(--g7));
            border-radius: 11px;
            display: flex; align-items: center; justify-content: center;
            font-family: 'Syne', sans-serif;
            font-weight: 800; font-size: 17px;
            color: var(--g1);
            box-shadow: 0 4px 16px rgba(16,185,129,0.4);
        }

        .brand-name {
            font-family: 'Syne', sans-serif;
            font-size: 20px; font-weight: 700;
            color: var(--white); letter-spacing: -0.3px;
        }

        .brand-name span { color: var(--g7); }

        .card {
            background: rgba(255,255,255,0.97);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 24px 80px rgba(5,150,105,0.22), 0 0 0 1px rgba(16,185,129,0.1);
        }

        /* Success animation strip */
        .success-strip {
            height: 6px;
            background: linear-gradient(90deg, var(--g4), var(--g6), var(--g7), var(--g6), var(--g4));
            background-size: 200% 100%;
            animation: shimmer 2.5s linear infinite;
        }

        @keyframes shimmer { to { background-position: -200% 0; } }

        .card-body { padding: 40px 36px 32px; text-align: center; }

        /* Check icon */
        .success-icon-wrap {
            position: relative;
            width: 88px; height: 88px;
            margin: 0 auto 24px;
        }

        .success-ring {
            position: absolute; inset: 0;
            border-radius: 50%;
            border: 3px solid var(--g9);
            animation: ringPulse 2s ease-in-out infinite;
        }

        @keyframes ringPulse {
            0%, 100% { transform: scale(1); opacity: 0.6; }
            50% { transform: scale(1.12); opacity: 0.2; }
        }

        .success-circle {
            position: absolute; inset: 8px;
            background: linear-gradient(135deg, var(--g4), var(--g6));
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 34px;
            box-shadow: 0 8px 24px rgba(5,150,105,0.4);
            animation: popIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) both;
        }

        @keyframes popIn {
            0% { transform: scale(0); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        .card-body h1 {
            font-family: 'Syne', sans-serif;
            font-size: 26px; font-weight: 700;
            color: var(--g1); margin-bottom: 8px;
            animation: fadeUp 0.4s 0.2s both;
        }

        .card-body .subtitle {
            font-size: 15px;
            color: #4b5563;
            line-height: 1.6;
            margin-bottom: 28px;
            animation: fadeUp 0.4s 0.3s both;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(12px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Status list */
        .status-list {
            background: var(--g11);
            border: 1px solid rgba(16,185,129,0.2);
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 20px;
            text-align: left;
            animation: fadeUp 0.4s 0.4s both;
        }

        .status-item {
            display: flex; align-items: flex-start; gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid rgba(16,185,129,0.1);
        }

        .status-item:last-child { border-bottom: none; padding-bottom: 0; }
        .status-item:first-child { padding-top: 0; }

        .status-dot {
            width: 28px; height: 28px; border-radius: 8px;
            background: linear-gradient(135deg, var(--g5), var(--g6));
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; flex-shrink: 0;
        }

        .status-text strong {
            display: block; font-size: 13.5px;
            font-weight: 600; color: var(--g3); margin-bottom: 2px;
        }

        .status-text span { font-size: 12.5px; color: #6b7280; }

        /* Warning */
        .warning-card {
            background: var(--amber-bg);
            border: 1px solid var(--amber-border);
            border-radius: 12px;
            padding: 16px 18px;
            margin-bottom: 24px;
            text-align: left;
            animation: fadeUp 0.4s 0.5s both;
            display: flex; gap: 12px;
        }

        .warning-icon { font-size: 20px; flex-shrink: 0; margin-top: 1px; }

        .warning-title {
            font-family: 'Syne', sans-serif;
            font-size: 13px; font-weight: 700;
            color: #92400e; margin-bottom: 4px;
            text-transform: uppercase; letter-spacing: 0.3px;
        }

        .warning-text { font-size: 13px; color: #78350f; line-height: 1.5; }

        /* Support */
        .support-row {
            display: flex; align-items: center; justify-content: center;
            gap: 8px;
            font-size: 13px; color: #6b7280;
            animation: fadeUp 0.4s 0.6s both;
        }

        .support-link {
            color: var(--g5); font-weight: 600;
            text-decoration: none;
            border-bottom: 1px dashed var(--g9);
        }

        .card-footer {
            padding: 16px 36px;
            background: var(--g11);
            border-top: 1px solid rgba(16,185,129,0.12);
            text-align: center;
            font-size: 12px; color: #9ca3af;
        }

        @media (max-width: 480px) {
            .card-body { padding: 32px 22px 26px; }
            .card-footer { padding: 14px 22px; }
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
        <div class="success-strip"></div>

        <div class="card-body">

            <div class="success-icon-wrap">
                <div class="success-ring"></div>
                <div class="success-circle">✓</div>
            </div>

            <h1>Verification Complete!</h1>
            <p class="subtitle">
                Your identity has been verified, <strong>{{ $user->name }}</strong>.<br>
                Your A-Pay account is now fully active.
            </p>

            <div class="status-list">
                <div class="status-item">
                    <div class="status-dot">🆔</div>
                    <div class="status-text">
                        <strong>Identity Confirmed</strong>
                        <span>BVN verified via secure channel</span>
                    </div>
                </div>
                <div class="status-item">
                    <div class="status-dot">✅</div>
                    <div class="status-text">
                        <strong>Account Activated</strong>
                        <span>Full access to all A-Pay services restored</span>
                    </div>
                </div>
                <div class="status-item">
                    <div class="status-dot">🔍</div>
                    <div class="status-text">
                        <strong>Document Review</strong>
                        <span>Compliance team will complete review within 24–48 hrs</span>
                    </div>
                </div>
            </div>

            <div class="warning-card">
                <span class="warning-icon">⚠️</span>
                <div>
                    <div class="warning-title">Important Notice</div>
                    <p class="warning-text">
                        Our compliance team will review your submission. If any inconsistencies are found, your account may be temporarily suspended until resolved.
                    </p>
                </div>
            </div>

            <div class="support-row">
                Need help?
                <a href="https://wa.me/2348152880128" class="support-link">📲 WhatsApp Support</a>
            </div>
        </div>

        <div class="card-footer">
            You can now close this page and return to WhatsApp · © 2026 A-Pay
        </div>
    </div>

</div>
