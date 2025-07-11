<!DOCTYPE html>
<html>
<head>
    <title>Credit Alert Notification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f3fdf5;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 128, 0, 0.2);
        }
        .header {
            background-color: #28a745;
            color: white;
            text-align: center;
            padding: 10px;
            border-radius: 10px 10px 0 0;
            font-size: 20px;
            font-weight: bold;
        }
        .content {
            padding: 20px;
            text-align: center;
            color: #333;
        }
        .status {
            font-size: 18px;
            font-weight: bold;
            padding: 10px;
            border-radius: 5px;
            display: inline-block;
            background-color: #28a745;
            color: white;
        }
        .footer {
            margin-top: 20px;
            font-size: 14px;
            text-align: center;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            Credit Alert Notification
        </div>
        <div class="content">
            <p>Hello {{ $user->name }},</p>
            <p>You have received a credit alert of <strong>₦{{ number_format($amount, 2) }}</strong> in your A-Pay account.</p>
            <p class="status">Transaction Successful ✅</p>

            <p>Transaction ID: <strong>{{ $transaction->id }}</strong></p>
            <p>Description: <strong>{{ $transaction->description }}</strong></p>

            <div class="footer">
                <p>Need help? Contact our support team.</p>
                <p>&copy; {{ date('Y') }} A-Pay. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
