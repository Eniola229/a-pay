<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KYC Already Submitted - A-Pay</title>
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
        .icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        h1 {
            color: #065f46;
            font-size: 24px;
            margin-bottom: 15px;
        }
        p {
            color: #065f46;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .status-badge {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 25px;
            font-weight: 600;
            margin: 20px 0;
            font-size: 14px;
        }
        .status-approved {
            background: #d1fae5;
            color: #065f46;
            border: 2px solid #10b981;
        }
        .status-pending {
            background: #fef3c7;
            color: #78350f;
            border: 2px solid #f59e0b;
        }
        .status-rejected {
            background: #fee2e2;
            color: #991b1b;
            border: 2px solid #dc3545;
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
        .info-box {
            background: #f0fdf4;
            border-left: 4px solid #10b981;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            text-align: left;
        }
        .info-box p {
            color: #065f46;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">ℹ️</div>
        <h1>KYC Already Submitted</h1>
        
        @if($user->kycProfile->status === 'APPROVED')
            <div class="status-badge status-approved">✓ Verified & Approved</div>
            <div class="info-box">
                <p><strong>Great news!</strong> Your KYC verification has been approved by the <span class="brand">A-Pay</span> team.</p>
                <p style="margin-top: 10px;">Your account is fully active and you can enjoy all A-Pay services without restrictions!</p>
            </div>
        @elseif($user->kycProfile->status === 'PENDING')
            <div class="status-badge status-pending">⏳ Under Review</div>
            <div class="info-box">
                <p>Your KYC documents are currently being reviewed by our compliance team.</p>
                <p style="margin-top: 10px;">You'll receive a WhatsApp notification once the verification is complete (usually within 24-48 hours).</p>
            </div>
        @elseif($user->kycProfile->status === 'REJECTED')
            <div class="status-badge status-rejected">❌ Rejected</div>
            <div class="info-box">
                <p><strong>Reason:</strong> {{ $user->kycProfile->rejection_reason ?? 'Please contact support for details' }}</p>
                <p style="margin-top: 10px;">Please contact our support team to resolve this issue and resubmit your documents.</p>
            </div>
        @endif

        <p class="footer-text">
            You can now close this window and return to WhatsApp.
        </p>
    </div>
</body>
</html>