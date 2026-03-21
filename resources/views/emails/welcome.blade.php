<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to A-Pay</title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Sora', sans-serif; background-color: #0a0f0a; margin: 0; padding: 32px 16px; }
        .wrapper { max-width: 600px; margin: 0 auto; }
        .brand-bar { text-align: center; margin-bottom: 24px; }
        .brand-bar .logo { display: inline-block; background: linear-gradient(135deg, #00e676, #00bfa5); color: #000; font-weight: 700; font-size: 22px; letter-spacing: -0.5px; padding: 8px 24px; border-radius: 100px; }
        .card { background: #ffffff; border-radius: 24px; overflow: hidden; box-shadow: 0 32px 80px rgba(0,0,0,0.4), 0 0 0 1px rgba(0,230,118,0.15); }
        .hero { padding: 56px 40px 48px; position: relative; overflow: hidden; background: linear-gradient(145deg, #00251a 0%, #00382a 50%, #004d35 100%); text-align: center; }
        .hero::before { content: ''; position: absolute; top: -80px; right: -80px; width: 300px; height: 300px; background: radial-gradient(circle, rgba(0,230,118,0.12) 0%, transparent 70%); border-radius: 50%; }
        .hero::after { content: ''; position: absolute; bottom: -60px; left: -60px; width: 240px; height: 240px; background: radial-gradient(circle, rgba(0,191,165,0.1) 0%, transparent 70%); border-radius: 50%; }
        .confetti { font-size: 36px; margin-bottom: 16px; position: relative; z-index: 1; }
        .hero-title { font-size: 36px; font-weight: 700; color: #ffffff; letter-spacing: -1.5px; line-height: 1.1; margin-bottom: 8px; position: relative; z-index: 1; }
        .hero-title span { color: #00e676; }
        .hero-subtitle { color: rgba(255,255,255,0.5); font-size: 15px; font-weight: 300; position: relative; z-index: 1; }
        .body { padding: 40px 40px; background: #fff; }
        .greeting { font-size: 18px; color: #1a2e1a; margin-bottom: 8px; font-weight: 600; }
        .greeting-sub { font-size: 14px; color: #6b7c6b; margin-bottom: 32px; font-weight: 300; line-height: 1.7; }
        /* Features grid */
        .features { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 32px; }
        .feature-card { background: #f7fdf8; border: 1px solid #e0f0e3; border-radius: 14px; padding: 18px 16px; }
        .feature-icon { font-size: 24px; margin-bottom: 8px; }
        .feature-title { font-size: 13px; font-weight: 600; color: #1a2e1a; margin-bottom: 4px; }
        .feature-desc { font-size: 11px; color: #8a9e8a; font-weight: 300; line-height: 1.4; }
        /* Account details */
        .account-section { background: linear-gradient(135deg, #00251a, #003d2a); border-radius: 16px; padding: 24px; margin-bottom: 28px; }
        .account-label { font-size: 11px; font-weight: 700; letter-spacing: 1.2px; text-transform: uppercase; color: rgba(255,255,255,0.4); margin-bottom: 4px; }
        .account-name { font-size: 18px; font-weight: 600; color: #fff; margin-bottom: 4px; }
        .account-sub { font-size: 12px; color: rgba(255,255,255,0.4); }
        /* CTA */
        .cta-section { text-align: center; margin-bottom: 28px; }
        .cta-text { font-size: 14px; color: #6b7c6b; margin-bottom: 16px; }
        .cta-btn { display: inline-block; background: linear-gradient(135deg, #00e676, #00bfa5); color: #000; font-family: 'Sora', sans-serif; font-size: 14px; font-weight: 700; padding: 14px 36px; border-radius: 100px; text-decoration: none; }
        .security-note { background: #fffbf0; border: 1px solid #ffd54f; border-radius: 12px; padding: 14px 18px; display: flex; gap: 12px; align-items: flex-start; margin-bottom: 28px; }
        .security-icon { font-size: 18px; flex-shrink: 0; }
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
            .hero { padding: 40px 24px 36px; }
            .hero-title { font-size: 28px; }
            .body { padding: 28px 24px; }
            .features { grid-template-columns: 1fr; }
            .footer { padding: 24px; }
        }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="brand-bar"><span class="logo">A-Pay</span></div>
    <div class="card">
        <div class="hero">
            <div class="confetti">🎉</div>
            <div class="hero-title">Welcome to <span>A-Pay</span></div>
            <div class="hero-subtitle">Your account is ready. Let's get started!</div>
        </div>
        <div class="body">
            <p class="greeting">Hey {{ $customerName }}, you're in! 🚀</p>
            <p class="greeting-sub">Your A-Pay account has been created successfully. You now have access to fast, reliable financial services right from your WhatsApp.</p>

            <div class="features">
                <div class="feature-card">
                    <div class="feature-icon">📱</div>
                    <div class="feature-title">Buy Airtime</div>
                    <div class="feature-desc">Instant recharge for any network</div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📶</div>
                    <div class="feature-title">Buy Data</div>
                    <div class="feature-desc">Affordable data plans always available</div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">⚡</div>
                    <div class="feature-title">Pay Bills</div>
                    <div class="feature-desc">Electricity & utility payments</div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">💸</div>
                    <div class="feature-title">Transfer Money</div>
                    <div class="feature-desc">Send funds to any A-Pay user</div>
                </div>
            </div>

            <div class="account-section">
                <div class="account-label">Your Account</div>
                <div class="account-name">{{ $customerName }}</div>
                <div class="account-sub">Fund your wallet to start transacting</div>
            </div>

            <div class="cta-section">
                <p class="cta-text">Open WhatsApp and type <strong>menu</strong> to see all available services.</p>
                <a href="https://wa.me/{{ ltrim(config('app.whatsapp_number', ''), '+') }}" class="cta-btn">Open A-Pay on WhatsApp →</a>
            </div>

            <div class="security-note">
                <span class="security-icon">🔐</span>
                <div class="security-text"><strong>Security Tip:</strong> Enable WhatsApp Lock on your device to keep your A-Pay transactions private. We will never ask for your PIN via email or chat.</div>
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
                This email was sent to confirm your A-Pay registration.<br>
                © {{ date('Y') }} <strong>A-Pay</strong> — Transactions made easy · Lagos, Nigeria
            </div>
        </div>
    </div>
</div>
</body>
</html>