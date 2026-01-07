<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 600px;
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .status {
            font-size: 18px;
            font-weight: bold;
            margin: 15px 0;
        }
        .success {
            color: #28a745;
        }
        .failed {
            color: #dc3545;
        }
        .details {
            text-align: left;
            margin: 20px 0;
            padding: 15px;
            background: #f8f8f8;
            border-radius: 8px;
            line-height: 1.6;
        }
        .details p {
            margin: 5px 0;
        }
        .btn {
            display: inline-block;
            background: #28a745;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .btn-failed {
            background: #dc3545;
        }
    </style>
</head>
<body>

    <div class="container">
        <h2>A-Pay Electricity Payment Receipt</h2>
        <p>Hello {{ $user['name'] }},</p>

        <p class="status {{ $status == 'SUCCESS' ? 'success' : 'failed' }}">
            Your electricity bill payment was {{ $status == 'SUCCESS' ? 'successful' : 'failed' }}.
        </p>

        <div class="details">
            <p><strong>Meter Number:</strong> {{ $meterNumber }}</p>
            <p><strong>Provider:</strong> {{ strtoupper($provider) }}</p>
            <p><strong>Token:</strong> {{ strtoupper($token) }}</p>
            <p><strong>Unit:</strong> {{ strtoupper($units) }}</p>
            <p><strong>Address:</strong> {{ $customer_address }}</p>
            <p><strong>Account Name:</strong> {{ $customer_name_m }}</p>
            <p><strong>Amount Paid:</strong> ₦{{ number_format($amount, 2) }}</p>
            <p><strong>Transaction Status:</strong> {{ $status }}</p>
        </div>

        @if ($status == 'SUCCESS')
            <p>Thank you for using A-Pay! Your payment has been processed successfully.</p>
            <a href="{{ url('/') }}" class="btn">Go to Dashboard</a>
        @else
            <p>We apologize for the inconvenience. Your funds have been refunded.</p>
            <a href="{{ url('/') }}" class="btn btn-failed">Try Again</a>
        @endif
    </div>

</body>
</html>
