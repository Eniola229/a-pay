<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Credit Alert — A-Pay</title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Sora', sans-serif;
            background-color: #0a0f0a;
            margin: 0;
            padding: 32px 16px;
        }

        .wrapper {
            max-width: 600px;
            margin: 0 auto;
        }

        /* Top brand bar */
        .brand-bar {
            text-align: center;
            margin-bottom: 24px;
        }
        .brand-bar .logo {
            display: inline-block;
            background: linear-gradient(135deg, #00e676, #00bfa5);
            color: #000;
            font-family: 'Sora', sans-serif;
            font-weight: 700;
            font-size: 22px;
            letter-spacing: -0.5px;
            padding: 8px 24px;
            border-radius: 100px;
        }

        /* Main card */
        .card {
            background: #ffffff;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 32px 80px rgba(0,0,0,0.4), 0 0 0 1px rgba(0,230,118,0.15);
        }

        /* Hero section */
        .hero {
            background: linear-gradient(145deg, #00251a 0%, #00382a 50%, #004d35 100%);
            padding: 48px 40px 40px;
            position: relative;
            overflow: hidden;
        }
        .hero::before {
            content: '';
            position: absolute;
            top: -60px;
            right: -60px;
            width: 240px;
            height: 240px;
            background: radial-gradient(circle, rgba(0,230,118,0.15) 0%, transparent 70%);
            border-radius: 50%;
        }
        .hero::after {
            content: '';
            position: absolute;
            bottom: -40px;
            left: -40px;
            width: 180px;
            height: 180px;
            background: radial-gradient(circle, rgba(0,191,165,0.1) 0%, transparent 70%);
            border-radius: 50%;
        }

        .hero-label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(0,230,118,0.15);
            border: 1px solid rgba(0,230,118,0.3);
            color: #00e676;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            padding: 6px 14px;
            border-radius: 100px;
            margin-bottom: 20px;
        }
        .hero-label::before {
            content: '';
            width: 6px;
            height: 6px;
            background: #00e676;
            border-radius: 50%;
            box-shadow: 0 0 8px #00e676;
        }

        .hero-amount {
            font-family: 'JetBrains Mono', monospace;
            font-size: 52px;
            font-weight: 600;
            color: #ffffff;
            letter-spacing: -2px;
            line-height: 1;
            margin-bottom: 8px;
            position: relative;
            z-index: 1;
        }
        .hero-amount span {
            font-size: 28px;
            color: #00e676;
            vertical-align: super;
            font-weight: 400;
        }

        .hero-subtitle {
            color: rgba(255,255,255,0.5);
            font-size: 14px;
            font-weight: 300;
            position: relative;
            z-index: 1;
        }

        /* Check icon */
        .success-badge {
            position: absolute;
            top: 40px;
            right: 40px;
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #00e676, #00bfa5);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 24px rgba(0,230,118,0.4);
            z-index: 1;
        }
        .success-badge svg {
            width: 28px;
            height: 28px;
        }

        /* Body section */
        .body {
            padding: 36px 40px;
            background: #fff;
        }

        .greeting {
            font-size: 16px;
            color: #1a2e1a;
            margin-bottom: 4px;
            font-weight: 600;
        }
        .greeting-sub {
            font-size: 14px;
            color: #6b7c6b;
            margin-bottom: 32px;
            font-weight: 300;
        }

        /* Transaction details card */
        .details-card {
            background: #f7fdf8;
            border: 1px solid #e0f0e3;
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 28px;
        }

        .details-header {
            background: #edf8ef;
            padding: 14px 20px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: #2e7d32;
            border-bottom: 1px solid #d4edda;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 20px;
            border-bottom: 1px solid #f0f7f0;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-size: 12px;
            color: #8a9e8a;
            font-weight: 400;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .detail-value {
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            color: #1a2e1a;
            font-weight: 600;
            text-align: right;
            max-width: 60%;
            word-break: break-all;
        }
        .detail-value.highlight {
            color: #00a152;
            font-size: 15px;
        }
        .detail-value.status-pill {
            font-family: 'Sora', sans-serif;
            background: linear-gradient(135deg, #00e676, #00bfa5);
            color: #000;
            padding: 4px 12px;
            border-radius: 100px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        /* Balance section */
        .balance-section {
            background: linear-gradient(135deg, #00251a, #003d2a);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .balance-left .balance-label {
            font-size: 11px;
            color: rgba(255,255,255,0.4);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }
        .balance-left .balance-amount {
            font-family: 'JetBrains Mono', monospace;
            font-size: 22px;
            font-weight: 600;
            color: #fff;
        }
        .balance-right {
            text-align: right;
        }
        .balance-right .balance-tag {
            background: rgba(0,230,118,0.15);
            color: #00e676;
            font-size: 11px;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 100px;
            border: 1px solid rgba(0,230,118,0.2);
        }

        /* CTA */
        .cta-section {
            text-align: center;
            margin-bottom: 28px;
        }
        .cta-btn {
            display: inline-block;
            background: linear-gradient(135deg, #00e676, #00bfa5);
            color: #000;
            font-family: 'Sora', sans-serif;
            font-size: 14px;
            font-weight: 700;
            padding: 14px 36px;
            border-radius: 100px;
            text-decoration: none;
            letter-spacing: 0.3px;
        }

        /* Security note */
        .security-note {
            background: #fffbf0;
            border: 1px solid #ffd54f;
            border-radius: 12px;
            padding: 14px 18px;
            display: flex;
            gap: 12px;
            align-items: flex-start;
            margin-bottom: 28px;
        }
        .security-icon {
            font-size: 18px;
            flex-shrink: 0;
            margin-top: 1px;
        }
        .security-text {
            font-size: 12px;
            color: #795548;
            line-height: 1.6;
        }
        .security-text strong {
            color: #5d4037;
        }

        /* Footer */
        .footer {
            background: #f9fdf9;
            border-top: 1px solid #e8f5e9;
            padding: 28px 40px;
            text-align: center;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 24px;
            margin-bottom: 16px;
        }
        .footer-links a {
            font-size: 12px;
            color: #00a152;
            text-decoration: none;
            font-weight: 500;
        }

        .footer-divider {
            width: 40px;
            height: 2px;
            background: linear-gradient(90deg, #00e676, #00bfa5);
            margin: 0 auto 16px;
            border-radius: 2px;
        }

        .footer-text {
            font-size: 11px;
            color: #9e9e9e;
            line-height: 1.8;
        }
        .footer-text strong {
            color: #00a152;
        }

        /* Responsive */
        @media (max-width: 480px) {
            body { padding: 16px 12px; }
            .hero { padding: 36px 24px 32px; }
            .hero-amount { font-size: 38px; }
            .body { padding: 28px 24px; }
            .footer { padding: 24px; }
            .balance-section { flex-direction: column; gap: 12px; text-align: center; }
            .balance-right { text-align: center; }
            .success-badge { top: 24px; right: 24px; width: 48px; height: 48px; }
        }
    </style>
</head>
<body>
    <div class="wrapper">

        <!-- Brand -->
        <div class="brand-bar">
            <span class="logo">A-Pay</span>
        </div>

        <div class="card">

            <!-- Hero -->
            <div class="hero">
                <div class="hero-label">Credit Alert</div>
                <div class="hero-amount">
                    <span>₦</span>{{ number_format($amount, 2) }}
                </div>
                <div class="hero-subtitle">Credited to your A-Pay wallet</div>

                <div class="success-badge">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M5 13l4 4L19 7" stroke="#000" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>

            <!-- Body -->
            <div class="body">

                <p class="greeting">Hello, {{ $user->name }} 👋</p>
                <p class="greeting-sub">Your wallet has been credited. Here's a full breakdown of the transaction.</p>

                <!-- Transaction Details -->
                <div class="details-card">
                    <div class="details-header">Transaction Details</div>
                    <div class="detail-row">
                        <span class="detail-label">Amount</span>
                        <span class="detail-value highlight">₦{{ number_format($amount, 2) }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Description</span>
                        <span class="detail-value">{{ $transaction->description }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Transaction ID</span>
                        <span class="detail-value">{{ $transaction->id }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Date & Time</span>
                        <span class="detail-value">{{ now()->format('d M Y, h:i A') }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Status</span>
                        <span class="detail-value status-pill">✓ Successful</span>
                    </div>
                </div>

                <!-- Balance -->
                @if(isset($newBalance))
                <div class="balance-section">
                    <div class="balance-left">
                        <div class="balance-label">Updated Balance</div>
                        <div class="balance-amount">₦{{ number_format($newBalance, 2) }}</div>
                    </div>
                    <div class="balance-right">
                        <span class="balance-tag">💰 Available</span>
                    </div>
                </div>
                @endif

                <!-- CTA -->
                <div class="cta-section">
                    <a href="#" class="cta-btn">View Transaction History →</a>
                </div>

                <!-- Security Note -->
                <div class="security-note">
                    <span class="security-icon">🔐</span>
                    <div class="security-text">
                        <strong>Security Notice:</strong> A-Pay will never ask for your PIN, password, or OTP via email or WhatsApp. If you did not initiate this transaction, please contact support immediately.
                    </div>
                </div>

            </div>

            <!-- Footer -->
            <div class="footer">
                <div class="footer-divider"></div>
                <div class="footer-links">
                    <a href="#">Support</a>
                    <a href="https://africicl.com.ng/a-pay/privacy-policy">Privacy Policy</a>
                    <a href="https://africicl.com.ng/a-pay/terms-and-condition">Terms of Use</a>
                </div>
                <div class="footer-text">
                    This email was sent to <strong>{{ $user->email }}</strong><br>
                    © {{ date('Y') }} <strong>A-Pay</strong> — Transactions Made Easy · Lagos, Nigeria
                </div>
            </div>

        </div>

    </div>
</body>
</html>