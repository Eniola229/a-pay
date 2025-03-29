<!DOCTYPE html>
<html>
<head>
    <title>A-Pay Data Purchase Notification</title>
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
        }
        .success {
            background-color: #28a745;
            color: white;
        }
        .failed {
            background-color: #dc3545;
            color: white;
        }
        .footer {
            margin-top: 20px;
            font-size: 14px;
            text-align: center;
            color: #666;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            color: white;
            margin-top: 15px;
        }
        .btn-success {
            background-color: #28a745;
        }
        .btn-failed {
            background-color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            A-Pay Data Purchase Notification
        </div>
        <div class="content">
            <p>Hello {{ $user->name }},</p>
            <p>Your data purchase request has been 
                <span class="status {{ $status == 'SUCCESS' ? 'success' : 'failed' }}">
                    {{ $status == 'SUCCESS' ? 'successful ✅' : 'failed ❌' }}
                </span>.
            </p>

            <p><strong>Phone Number:</strong> {{ $phoneNumber }}</p>
            <p><strong>Data Plan:</strong> {{ $planName }}</p>
            <p><strong>Amount:</strong> ₦{{ number_format($amount, 2) }}</p>

            @if ($status == 'SUCCESS')
                <p>Thank you for using A-Pay! Your data will be available shortly.</p>
                <a href="{{ url('/') }}" class="btn btn-success">Go to Dashboard</a>
            @else
                <p>We apologize for the inconvenience. Your funds have been refunded.</p>
                <a href="{{ url('/') }}" class="btn btn-failed">Try Again</a>
            @endif

            <div class="footer">
                <p>Need help? Contact our support team.</p>
                <p>&copy; {{ date('Y') }} A-Pay. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
