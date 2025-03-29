<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Confirmation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border: 1px solid #dddddd;
            border-radius: 8px;
            overflow: hidden;
        }
        .header {
            background-color: green;
            color: #ffffff;
            padding: 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 20px;
            color: #333333;
        }
        .content h2 {
            font-size: 20px;
            margin-bottom: 20px;
        }
        .content p {
            font-size: 16px;
            line-height: 1.5;
        }
        .footer {
            background-color: #f4f4f4;
            padding: 10px;
            text-align: center;
            font-size: 14px;
            color: #777777;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            margin: 20px 0;
            font-size: 16px;
            color: white;
            background-color: green;
            text-decoration: none;
            border-radius: 5px;
        }
        .button:hover {
            background-color: #005bb5;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>Welcome to A-Pay</h1>
        </div>
        <div class="content">
            <h2>Thank You for Registering!</h2>
            <p>Dear {{ $customerName }},</p>
            <p>We are excited to welcome you to A-Pay platform. Your account has been successfully created, and you can now enjoy a wide range of banking services at your fingertips.</p>
           <!--  <p>Please verify your email address by clicking the button below:</p>
            <a href="{{ $verificationLink }}" class="button" style="color: white;">Verify Email Address</a> -->
            <p>If you have any questions or need assistance, feel free to contact our support team at <a href="mailto:support@a-pay.com">support@a-pay.com</a>.</p>
            <p>Thank you for choosing us for your banking needs!</p>
            <p>Best regards,<br>A-Pay Team</p>
        </div>
        <div class="footer">
            <p>&copy; 2025 A-Pay. All rights reserved.</p>
            <p>This is an automated message, please do not reply directly to this email.</p>
        </div>
    </div>
</body>
</html>