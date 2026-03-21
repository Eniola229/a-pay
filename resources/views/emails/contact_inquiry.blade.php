<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Contact Inquiry — A-Pay</title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Sora', sans-serif; background-color: #0a0f0a; margin: 0; padding: 32px 16px; }
        .wrapper { max-width: 600px; margin: 0 auto; }
        .brand-bar { text-align: center; margin-bottom: 24px; }
        .brand-bar .logo { display: inline-block; background: linear-gradient(135deg, #00e676, #00bfa5); color: #000; font-weight: 700; font-size: 22px; letter-spacing: -0.5px; padding: 8px 24px; border-radius: 100px; }
        .card { background: #ffffff; border-radius: 24px; overflow: hidden; box-shadow: 0 32px 80px rgba(0,0,0,0.4), 0 0 0 1px rgba(0,230,118,0.15); }
        .hero { padding: 48px 40px 40px; position: relative; overflow: hidden; background: linear-gradient(145deg, #001a2e 0%, #002a45 50%, #003d60 100%); }
        .hero::before { content: ''; position: absolute; top: -60px; right: -60px; width: 240px; height: 240px; background: radial-gradient(circle, rgba(0,180,255,0.12) 0%, transparent 70%); border-radius: 50%; }
        .hero-label { display: inline-flex; align-items: center; gap: 6px; background: rgba(0,180,255,0.15); border: 1px solid rgba(0,180,255,0.3); color: #40c4ff; font-size: 11px; font-weight: 600; letter-spacing: 1.5px; text-transform: uppercase; padding: 6px 14px; border-radius: 100px; margin-bottom: 20px; }
        .hero-label::before { content: ''; width: 6px; height: 6px; background: #40c4ff; box-shadow: 0 0 8px #40c4ff; border-radius: 50%; }
        .hero-title { font-size: 32px; font-weight: 700; color: #ffffff; letter-spacing: -1px; line-height: 1.1; margin-bottom: 8px; position: relative; z-index: 1; }
        .hero-subtitle { color: rgba(255,255,255,0.5); font-size: 14px; font-weight: 300; position: relative; z-index: 1; }
        .status-badge { position: absolute; top: 40px; right: 40px; width: 56px; height: 56px; background: linear-gradient(135deg, #40c4ff, #0091ea); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 24px rgba(64,196,255,0.4); z-index: 1; }
        .status-badge svg { width: 28px; height: 28px; }
        .body { padding: 36px 40px; background: #fff; }
        .section-title { font-size: 11px; font-weight: 700; letter-spacing: 1.2px; text-transform: uppercase; color: #0277bd; margin-bottom: 16px; }
        .details-card { background: #f0f8ff; border: 1px solid #b3e0ff; border-radius: 16px; overflow: hidden; margin-bottom: 28px; }
        .details-header { background: #e1f5fe; padding: 14px 20px; font-size: 11px; font-weight: 700; letter-spacing: 1.2px; text-transform: uppercase; color: #0277bd; border-bottom: 1px solid #b3e0ff; }
        .detail-row { display: flex; justify-content: space-between; align-items: flex-start; padding: 14px 20px; border-bottom: 1px solid #e8f4fd; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { font-size: 12px; color: #7a9ab0; font-weight: 400; text-transform: uppercase; letter-spacing: 0.5px; flex-shrink: 0; width: 35%; }
        .detail-value { font-family: 'JetBrains Mono', monospace; font-size: 13px; color: #1a2e3a; font-weight: 600; text-align: right; word-break: break-word; width: 60%; }
        .complaint-box { background: #f0f8ff; border: 1px solid #b3e0ff; border-radius: 16px; padding: 20px; margin-bottom: 28px; }
        .complaint-label { font-size: 11px; font-weight: 700; letter-spacing: 1.2px; text-transform: uppercase; color: #0277bd; margin-bottom: 12px; }
        .complaint-text { font-size: 14px; color: #1a2e3a; line-height: 1.8; font-weight: 300; font-style: italic; border-left: 3px solid #40c4ff; padding-left: 16px; }
        .action-note { background: #e8f5e9; border: 1px solid #a5d6a7; border-radius: 12px; padding: 14px 18px; display: flex; gap: 12px; align-items: flex-start; margin-bottom: 28px; }
        .action-icon { font-size: 18px; flex-shrink: 0; }
        .action-text { font-size: 13px; color: #2e7d32; line-height: 1.6; }
        .footer { background: #f9fdf9; border-top: 1px solid #e8f5e9; padding: 28px 40px; text-align: center; }
        .footer-divider { width: 40px; height: 2px; background: linear-gradient(90deg, #00e676, #00bfa5); margin: 0 auto 16px; border-radius: 2px; }
        .footer-text { font-size: 11px; color: #9e9e9e; line-height: 1.8; }
        .footer-text strong { color: #00a152; }
        @media (max-width: 480px) {
            body { padding: 16px 12px; }
            .hero { padding: 36px 24px 32px; }
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
            <div class="hero-label">New Inquiry</div>
            <div class="hero-title">Contact Request Received</div>
            <div class="hero-subtitle">A customer has submitted a support inquiry</div>
            <div class="status-badge">
                <svg viewBox="0 0 24 24" fill="none"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z" fill="#fff"/></svg>
            </div>
        </div>
        <div class="body">
            <div class="details-card">
                <div class="details-header">Customer Information</div>
                <div class="detail-row">
                    <span class="detail-label">Name</span>
                    <span class="detail-value">{{ $inquiry->name }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Email</span>
                    <span class="detail-value">{{ $inquiry->email }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Phone</span>
                    <span class="detail-value">{{ $inquiry->phone_number }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Category</span>
                    <span class="detail-value">{{ $inquiry->category }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Submitted</span>
                    <span class="detail-value">{{ now()->format('d M Y, h:i A') }}</span>
                </div>
            </div>
            <div class="complaint-box">
                <div class="complaint-label">Customer Message</div>
                <div class="complaint-text">{{ $inquiry->complaint }}</div>
            </div>
            <div class="action-note">
                <span class="action-icon">⚡</span>
                <div class="action-text">Please respond to this inquiry within <strong>24 hours</strong>. Reply directly to the customer's email: <strong>{{ $inquiry->email }}</strong></div>
            </div>
        </div>
        <div class="footer">
            <div class="footer-divider"></div>
            <div class="footer-text">
                © {{ date('Y') }} <strong>A-Pay</strong> — Transactions Made Easy · Lagos, Nigeria
            </div>
        </div>
    </div>
</div>
</body>
</html>