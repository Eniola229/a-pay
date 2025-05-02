<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Device Authentication Code</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f8f4;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 500px;
            margin: 20px auto;
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            border-top: 5px solid #28a745;
        }
        .header {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            color: #28a745;
        }
        .code-box {
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            color: #fff;
            background: #28a745;
            padding: 15px;
            border-radius: 5px;
            display: inline-block;
            letter-spacing: 5px;
        }
        .message {
            margin-top: 20px;
            font-size: 16px;
            line-height: 1.6;
            text-align: center;
        }
        .footer {
            margin-top: 30px;
            font-size: 14px;
            color: #666;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">Secure Device Authentication</div>

    <p class="message">Hello,</p>
    
    <p class="message">Your authentication code for device login is:</p>

    <div class="code-box">{{ $code }}</div>

    <p class="message">If you did not attempt to log in, please ignore this email.</p>

    <p class="footer">Thank you for keeping your account secure.<br> A-Pay Security Team</p>
</div>

</body>
</html>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Device Authentication Code</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f8f4;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 500px;
            margin: 20px auto;
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            border-top: 5px solid #28a745;
        }
        .header {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            color: #28a745;
        }
        .code-box {
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            color: #fff;
            background: #28a745;
            padding: 15px;
            border-radius: 5px;
            display: inline-block;
            letter-spacing: 5px;
        }
        .message {
            margin-top: 20px;
            font-size: 16px;
            line-height: 1.6;
            text-align: center;
        }
        .footer {
            margin-top: 30px;
            font-size: 14px;
            color: #666;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">Secure Device Authentication</div>

    <p class="message">Hello,</p>
    
    <p class="message">Your authentication code for device login is:</p>

    <div class="code-box">{{ $code }}</div>

    <p class="message">If you did not attempt to log in, please ignore this email.</p>

    <p class="footer">Thank you for keeping your account secure.<br> A-Pay Security Team</p>
</div>

</body>
</html>
