<!-- resources/views/emails/welcome.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Welcome to Learner's.Com</title>
</head>
<body style="font-family: sans-serif; background: #f0fdf4; padding: 30px;">
    <div style="max-width: 600px; margin: auto; background: #ffffff; padding: 20px; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.05);">
        <h2 style="color: #22c55e;">Welcome, {{ $learner->first_name }}!</h2>
        <p style="font-size: 16px;">You’ve successfully registered for <strong>{{ $learner->course_of_study }}</strong> on Learner's.Com.</p>
        <p>We'll reach out to you via <strong>{{ $learner->email }}</strong> and <strong>{{ $learner->whatsapp }}</strong>.</p>
        <div style="margin-top: 30px;">
            <a href="#" style="background: #22c55e; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Explore More</a>
        </div>
        <hr style="margin: 30px 0;">
        <p style="text-align: center; color: #666;">&copy; {{ date('Y') }} Learner's.Com — Learn. Grow. Succeed.</p>
    </div>
</body>
</html>
