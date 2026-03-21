<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authentication Code — A-Pay</title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Sora', sans-serif; background-color: #0a0f0a; margin: 0; padding: 32px 16px; }
        .wrapper { max-width: 600px; margin: 0 auto; }
        .brand-bar { text-align: center; margin-bottom: 24px; }
        .brand-bar .logo { display: inline-block; background: linear-gradient(135deg, #00e676, #00bfa5); color: #000; font-weight: 700; font-size: 22px; letter-spacing: -0.5px; padding: 8px 24px; border-radius: 100px; }
        .card { background: #ffffff; border-radius: 24px; overflow: hidden; box-shadow: 0 32px 80px rgba(0,0,0,0.4), 0 0 0 1px rgba(0,230,118,0.15); }
        .hero { padding: 48px 40px 40px; position: relative; overflow: hidden; background: linear-gradient(145deg, #0a001f 0%, #15003a 50%, #1a0050 100%); }
        .hero::before { content: ''; position: absolute; top: -60px; right: -60px; width: 240px; height: 240px; background: radial-gradient(circle, rgba(124,77,255,0.2) 0%, transparent 70%); border-radius: 50%; }
        .hero::after { content: ''; position: absolute; bottom: -40px; left: -40px; width: 180px; height: 180px; background: radial-gradient(circle, rgba(0,230,118,0.1) 0%, transparent 70%); border-radius: 50%; }
        .hero-label { display: inline-flex; align-items: center; gap: 6px; background: rgba(124,77,255,0.15); border: 1px solid rgba(124,77,255,0.3); color: #b388ff; font-size: 11px; font-weight: 600; letter-spacing: 1.5px; text-transform: uppercase; padding: 6px 14px; border-radius: 100px; margin-bottom: 20px; }
        .hero-label::before { content: ''; width: 6px; height: 6px; background: #b388ff; box-shadow: 0 0 8px #b388ff; border-radius: 50%; }
        .hero-title { font-size: 32px; font-weight: 700; color: #ffffff; letter-spacing: -1px; line-height: 1.1; margin-bottom: 8px; position: relative; z-index: 1; }
        .hero-subtitle { color: rgba(255,255,255,0.5); font-size: 14px; font-weight: 300; position: relative; z-index: 1; }
        .shield-badge { position: absolute; top: 40px; right: 40px; width: 56px; height: 56px; background: linear-gradient(135deg, #7c4dff, #651fff); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 24px rgba(124,77,255,0.4); z-index: 1; }
        .shield-badge svg { width: 28px; height: 28px; }
        .body { padding: 36px 40px; background: #fff; }
        .greeting { font-size: 16px; color: #1a1a2e; margin-bottom: 4px; font-weight: 600; }
        .greeting-sub { font-size: 14px; color: #6b6b8a; margin-bottom: 32px; font-weight: 300; }
        .code-section { text-align: center; margin-bottom: 32px; }
        .code-label { font-size: 11px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; color: #7c4dff; margin-bottom: 16px; }
        .code-box { display: inline-block; background: linear-gradient(135deg, #1a0050, #2d0080); border-radius: 16px; padding: 24px 40px; margin-bottom: 16px; box-shadow: 0 16px 40px rgba(124,77,255,0.3); }
        .code-value { font-family: 'JetBrains Mono', monospace; font-size: 48px; font-weight: 600; color: #ffffff; letter-spacing: 12px; line-height: 1; }
        .code-expiry { font-size: 12px; color: #9e9e9e; display: flex; align-items: center; justify-content: center; gap: 6px; }
        .code-expiry::before { content: '⏱'; }
        .info-card { background: #f8f5ff; border: 1px solid #e1d5ff; border-radius: 16px; padding: 20px; margin-bottom: 28px; }
        .info-row { display: flex; align-items: flex-start; gap: 12px; padding: 8px 0; border-bottom: 1px solid #ede5ff; }
        .info-row:last-child { border-bottom: none; padding-bottom: 0; }
        .info-icon { font-size: 16px; flex-shrink: 0; margin-top: 1px; }
        .info-text { font-size: 13px; color: #3d2a7a; line-height: 1.5; }
        .info-text strong { color: #1a0050; }
        .warning-note { background: #fff3e0; border: 1px solid #ffcc80; border-radius: 12px; padding: 14px 18px; display: flex; gap: 12px; align-items: flex-start; margin-bottom: 28px; }
        .warning-icon { font-size: 18px; flex-shrink: 0; }
        .warning-text { font-size: 12px; color: #e65100; line-height: 1.6; }
        .warning-text strong { color: #bf360c; }
        .footer { background: #f9fdf9; border-top: 1px solid #e8f5e9; padding: 28px 40px; text-align: center; }
        .footer-divider { width: 40px; height: 2px; background: linear-gradient(90deg, #00e676, #00bfa5); margin: 0 auto 16px; border-radius: 2px; }
        .footer-text { font-size: 11px; color: #9e9e9e; line-height: 1.8; }
        .footer-text strong { color: #00a152; }
        @media (max-width: 480px) {
            body { padding: 16px 12px; }
            .hero { padding: 36px 24px 32px; }
            .code-value { font-size: 36px; letter-spacing: 8px; }
            .body { padding: 28px 24px; }
            .footer { padding: 24px; }
        }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="brand-bar"><span class="logo">A-Pay</span></div>
    <div class="card">
        <div class="hero">
            <div class="hero-label">Security Code</div>
            <div class="hero-title">Verify Your Identity</div>
            <div class="hero-subtitle">Use the code below to complete authentication</div>
            <div class="shield-badge">
                <svg viewBox="0 0 24 24" fill="none"><path d="M12 2L3 7v5c0 5.25 3.75 10.15 9 11.35C17.25 22.15 21 17.25 21 12V7L12 2z" fill="#fff"/></svg>
            </div>
        </div>
        <div class="body">
            <p class="greeting">Hello 👋</p>
            <p class="greeting-sub">Someone requested an authentication code for your A-Pay account. Use the code below to proceed.</p>
            <div class="code-section">
                <div class="code-label">Your One-Time Code</div>
                <div class="code-box">
                    <div class="code-value">{{ $code }}</div>
                </div>
                <div class="code-expiry">This code expires in 10 minutes</div>
            </div>
            <div class="info-card">
                <div class="info-row">
                    <span class="info-icon">📱</span>
                    <div class="info-text">Enter this code on the <strong>A-Pay verification screen</strong> to complete your login.</div>
                </div>
                <div class="info-row">
                    <span class="info-icon">🔒</span>
                    <div class="info-text">This code is <strong>single-use</strong> and will expire after it is used or after 10 minutes.</div>
                </div>
                <div class="info-row">
                    <span class="info-icon">🚫</span>
                    <div class="info-text"><strong>Never share this code</strong> with anyone, including A-Pay support agents.</div>
                </div>
            </div>
            <div class="warning-note">
                <span class="warning-icon">⚠️</span>
                <div class="warning-text"><strong>Didn't request this?</strong> If you did not attempt to log in, ignore this email and consider changing your password immediately. Your account remains secure.</div>
            </div>
        </div>
        <div class="footer">
            <div class="footer-divider"></div>
            <div class="footer-text">
                © {{ date('Y') }} <strong>A-Pay</strong> — Transactions Made Easy · Lagos, Nigeria<br>
                This is an automated security email, please do not reply.
            </div>
        </div>
    </div>
</div>
</body>
</html>