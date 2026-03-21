<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Airtime Purchase — A-Pay</title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Sora', sans-serif; background-color: #0a0f0a; margin: 0; padding: 32px 16px; }
        .wrapper { max-width: 600px; margin: 0 auto; }
        .brand-bar { text-align: center; margin-bottom: 24px; }
        .brand-bar .logo { display: inline-block; background: linear-gradient(135deg, #00e676, #00bfa5); color: #000; font-weight: 700; font-size: 22px; letter-spacing: -0.5px; padding: 8px 24px; border-radius: 100px; }
        .card { background: #ffffff; border-radius: 24px; overflow: hidden; box-shadow: 0 32px 80px rgba(0,0,0,0.4), 0 0 0 1px rgba(0,230,118,0.15); }
        .hero { padding: 48px 40px 40px; position: relative; overflow: hidden; }
        .hero.success { background: linear-gradient(145deg, #00251a 0%, #00382a 50%, #004d35 100%); }
        .hero.failed { background: linear-gradient(145deg, #1a0000 0%, #2a0000 50%, #3d0000 100%); }
        .hero::before { content: ''; position: absolute; top: -60px; right: -60px; width: 240px; height: 240px; border-radius: 50%; }
        .hero.success::before { background: radial-gradient(circle, rgba(0,230,118,0.15) 0%, transparent 70%); }
        .hero.failed::before { background: radial-gradient(circle, rgba(220,53,69,0.15) 0%, transparent 70%); }
        .hero-label { display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 600; letter-spacing: 1.5px; text-transform: uppercase; padding: 6px 14px; border-radius: 100px; margin-bottom: 20px; }
        .hero-label.success { background: rgba(0,230,118,0.15); border: 1px solid rgba(0,230,118,0.3); color: #00e676; }
        .hero-label.failed { background: rgba(220,53,69,0.15); border: 1px solid rgba(220,53,69,0.3); color: #ff5252; }
        .hero-label::before { content: ''; width: 6px; height: 6px; border-radius: 50%; }
        .hero-label.success::before { background: #00e676; box-shadow: 0 0 8px #00e676; }
        .hero-label.failed::before { background: #ff5252; box-shadow: 0 0 8px #ff5252; }
        .hero-amount { font-family: 'JetBrains Mono', monospace; font-size: 52px; font-weight: 600; color: #ffffff; letter-spacing: -2px; line-height: 1; margin-bottom: 8px; position: relative; z-index: 1; }
        .hero-amount span { font-size: 28px; vertical-align: super; font-weight: 400; }
        .hero.success .hero-amount span { color: #00e676; }
        .hero.failed .hero-amount span { color: #ff5252; }
        .hero-subtitle { color: rgba(255,255,255,0.5); font-size: 14px; font-weight: 300; position: relative; z-index: 1; }
        .status-badge { position: absolute; top: 40px; right: 40px; width: 56px; height: 56px; border-radius: 50%; display: flex; align-items: center; justify-content: center; z-index: 1; }
        .status-badge.success { background: linear-gradient(135deg, #00e676, #00bfa5); box-shadow: 0 8px 24px rgba(0,230,118,0.4); }
        .status-badge.failed { background: linear-gradient(135deg, #ff5252, #d32f2f); box-shadow: 0 8px 24px rgba(255,82,82,0.4); }
        .status-badge svg { width: 28px; height: 28px; }
        .body { padding: 36px 40px; background: #fff; }
        .greeting { font-size: 16px; color: #1a2e1a; margin-bottom: 4px; font-weight: 600; }
        .greeting-sub { font-size: 14px; color: #6b7c6b; margin-bottom: 32px; font-weight: 300; }
        .details-card { background: #f7fdf8; border: 1px solid #e0f0e3; border-radius: 16px; overflow: hidden; margin-bottom: 28px; }
        .details-card.failed-card { background: #fff8f8; border-color: #fde0e0; }
        .details-header { padding: 14px 20px; font-size: 11px; font-weight: 700; letter-spacing: 1.2px; text-transform: uppercase; border-bottom: 1px solid #d4edda; }
        .details-header.success { background: #edf8ef; color: #2e7d32; border-color: #d4edda; }
        .details-header.failed { background: #fdf0f0; color: #c62828; border-color: #fde0e0; }
        .detail-row { display: flex; justify-content: space-between; align-items: center; padding: 14px 20px; border-bottom: 1px solid #f0f7f0; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { font-size: 12px; color: #8a9e8a; font-weight: 400; text-transform: uppercase; letter-spacing: 0.5px; }
        .detail-value { font-family: 'JetBrains Mono', monospace; font-size: 13px; color: #1a2e1a; font-weight: 600; text-align: right; max-width: 60%; word-break: break-all; }
        .detail-value.green { color: #00a152; font-size: 15px; }
        .detail-value.red { color: #d32f2f; font-size: 15px; }
        .detail-value.status-pill { font-family: 'Sora', sans-serif; padding: 4px 12px; border-radius: 100px; font-size: 11px; font-weight: 700; letter-spacing: 0.5px; }
        .detail-value.status-pill.success { background: linear-gradient(135deg, #00e676, #00bfa5); color: #000; }
        .detail-value.status-pill.failed { background: linear-gradient(135deg, #ff5252, #d32f2f); color: #fff; }
        .refund-notice { background: #fff8e1; border: 1px solid #ffe082; border-radius: 12px; padding: 16px 18px; display: flex; gap: 12px; align-items: flex-start; margin-bottom: 28px; }
        .refund-icon { font-size: 20px; flex-shrink: 0; }
        .refund-text { font-size: 13px; color: #5d4037; line-height: 1.6; }
        .refund-text strong { color: #e65100; }
        .security-note { background: #fffbf0; border: 1px solid #ffd54f; border-radius: 12px; padding: 14px 18px; display: flex; gap: 12px; align-items: flex-start; margin-bottom: 28px; }
        .security-icon { font-size: 18px; flex-shrink: 0; margin-top: 1px; }
        .security-text { font-size: 12px; color: #795548; line-height: 1.6; }
        .security-text strong { color: #5d4037; }
        .footer { background: #f9fdf9; border-top: 1px solid #e8f5e9; padding: 28px 40px; text-align: center; }
        .footer-links { display: flex; justify-content: center; gap: 24px; margin-bottom: 16px; }
        .footer-links a { font-size: 12px; color: #00a152; text-decoration: none; font-weight: 500; }
        .footer-divider { width: 40px; height: 2px; background: linear-gradient(90deg, #00e676, #00bfa5); margin: 0 auto 16px; border-radius: 2px; }
        .footer-text { font-size: 11px; color: #9e9e9e; line-height: 1.8; }
        .footer-text strong { color: #00a152; }
        @media (max-width: 480px) {
            body { padding: 16px 12px; }
            .hero { padding: 36px 24px 32px; }
            .hero-amount { font-size: 38px; }
            .body { padding: 28px 24px; }
            .footer { padding: 24px; }
            .status-badge { top: 24px; right: 24px; width: 48px; height: 48px; }
        }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="brand-bar"><span class="logo">A-Pay</span></div>
    <div class="card">
        <div class="hero {{ $status == 'SUCCESS' ? 'success' : 'failed' }}">
            <div class="hero-label {{ $status == 'SUCCESS' ? 'success' : 'failed' }}">Airtime Purchase</div>
            <div class="hero-amount"><span>₦</span>{{ number_format($transaction->amount, 2) }}</div>
            <div class="hero-subtitle">
                {{ $status == 'SUCCESS' ? 'Airtime delivered successfully' : 'Transaction could not be completed' }}
            </div>
            <div class="status-badge {{ $status == 'SUCCESS' ? 'success' : 'failed' }}">
                @if($status == 'SUCCESS')
                    <svg viewBox="0 0 24 24" fill="none"><path d="M5 13l4 4L19 7" stroke="#000" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                @else
                    <svg viewBox="0 0 24 24" fill="none"><path d="M18 6L6 18M6 6l12 12" stroke="#fff" stroke-width="2.5" stroke-linecap="round"/></svg>
                @endif
            </div>
        </div>
        <div class="body">
            <p class="greeting">Hello, {{ $user->name }} 👋</p>
            <p class="greeting-sub">
                @if($status == 'SUCCESS')
                    Your airtime purchase was successful. Here's the transaction summary.
                @else
                    We were unable to process your airtime purchase. Your wallet has been refunded.
                @endif
            </p>
            <div class="details-card {{ $status != 'SUCCESS' ? 'failed-card' : '' }}">
                <div class="details-header {{ $status == 'SUCCESS' ? 'success' : 'failed' }}">Transaction Details</div>
                <div class="detail-row">
                    <span class="detail-label">Amount</span>
                    <span class="detail-value {{ $status == 'SUCCESS' ? 'green' : 'red' }}">₦{{ number_format($transaction->amount, 2) }}</span>
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
                    <span class="detail-value status-pill {{ $status == 'SUCCESS' ? 'success' : 'failed' }}">
                        {{ $status == 'SUCCESS' ? '✓ Successful' : '✗ Failed' }}
                    </span>
                </div>
            </div>
            @if($status != 'SUCCESS')
            <div class="refund-notice">
                <span class="refund-icon">💸</span>
                <div class="refund-text"><strong>Wallet Refunded:</strong> ₦{{ number_format($transaction->amount, 2) }} has been restored to your A-Pay wallet. You can retry anytime.</div>
            </div>
            @endif
            <div class="security-note">
                <span class="security-icon">🔐</span>
                <div class="security-text"><strong>Security Notice:</strong> A-Pay will never ask for your PIN, password, or OTP via email or WhatsApp. If you did not initiate this, contact support immediately.</div>
            </div>
        </div>
        <div class="footer">
            <div class="footer-divider"></div>
            <div class="footer-links">
                <a href="#">Support</a>
                <a href="https://africicl.com.ng/a-pay/privacy-policy">Privacy Policy</a>
                <a href="https://africicl.com.ng/a-pay/terms-and-condition">Terms of Use</a>
            </div>
            <div class="footer-text">
                This email was sent to <strong>{{ $user->email }}</strong><br>
                © {{ date('Y') }} <strong>A-Pay</strong> — Transactions made easy · Lagos, Nigeria
            </div>
        </div>
    </div>
</div>
</body>
</html>