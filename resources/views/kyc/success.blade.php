<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KYC Submitted - A-Pay</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #059669 0%, #10b981 50%, #34d399 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            max-width: 500px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(5, 150, 105, 0.4);
            padding: 40px;
            text-align: center;
        }
        .success-icon {
            font-size: 80px;
            margin-bottom: 20px;
            animation: scaleIn 0.5s ease-in-out;
        }
        @keyframes scaleIn {
            0% { transform: scale(0); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        h1 {
            color: #059669;
            font-size: 28px;
            margin-bottom: 15px;
        }
        p {
            color: #065f46;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .success-box {
            background: #d1fae5;
            border-left: 4px solid #10b981;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            text-align: left;
        }
        .success-box p {
            margin-bottom: 10px;
            font-size: 14px;
            color: #065f46;
            font-weight: 600;
        }
        .warning-box {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            text-align: left;
        }
        .warning-box strong {
            color: #78350f;
            display: block;
            margin-bottom: 10px;
        }
        .warning-box p {
            color: #78350f;
        }
        .brand {
            color: #10b981;
            font-weight: 700;
        }
        .footer-text {
            margin-top: 30px;
            font-size: 14px;
            color: #6b7280;
        }
        .support-text {
            font-size: 13px;
            color: #6b7280;
            margin-top: 20px;
        }
        .support-number {
            color: #059669;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-icon">✅</div>
        <h1>KYC Submitted Successfully!</h1>
        <p>Thank you, <strong>{{ $user->name }}</strong>! Your KYC verification has been submitted to <span class="brand">A-Pay</span>.</p>

        <div class="success-box">
            <p>✓ Your account is now active</p>
            <p>✓ You can continue using all A-Pay services</p>
            <p>✓ Documents will be reviewed within 24-48 hours</p>
        </div>

        <div class="warning-box">
            <strong>⚠️ IMPORTANT NOTICE</strong>
            <p style="margin: 0; font-size: 14px;">Our compliance team will review your submitted documents. If we find any errors, inconsistencies, or missing information, your account will be temporarily suspended until the issues are resolved.</p>
        </div>

        <p class="footer-text">
            You can now close this window and return to WhatsApp.
        </p>

        <p class="support-text">
            Need help? Contact support: <span class="support-number">📲 09079916807</span>
        </p>
    </div>
</body>
</html>