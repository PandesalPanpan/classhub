<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to ClassHub - PUP</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #374151;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f3f4f6;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border-top: 6px solid #800000;
        }
        .university-header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #FFDF00;
        }
        .university-header h2 {
            margin: 0;
            color: #800000;
            font-size: 18px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .university-header p {
            margin: 0;
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            font-weight: 600;
        }
        .header {
            margin-bottom: 20px;
        }
        .header h1 {
            color: #111827;
            margin: 0;
            font-size: 22px;
        }
        .welcome-badge {
            display: inline-block;
            background-color: #dbeafe;
            color: #1e40af;
            padding: 4px 16px;
            border-radius: 9999px;
            font-size: 14px;
            font-weight: 700;
            margin-top: 10px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .section {
            margin: 25px 0;
        }
        .section h2 {
            color: #111827;
            font-size: 18px;
            margin-bottom: 10px;
        }
        .quick-start {
            background-color: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 16px;
            margin: 20px 0;
            border-radius: 0 6px 6px 0;
            font-size: 14px;
        }
        .quick-start ol {
            margin: 10px 0 0 0;
            padding-left: 20px;
        }
        .quick-start li {
            margin: 8px 0;
        }
        .links {
            background-color: #f9fafb;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .links a {
            display: block;
            color: #800000;
            text-decoration: none;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
            font-weight: 500;
        }
        .links a:last-child {
            border-bottom: none;
        }
        .links a:hover {
            text-decoration: underline;
        }
        .btn-container {
            text-align: center;
            margin: 30px 0 10px 0;
        }
        .btn {
            display: inline-block;
            background-color: #800000;
            color: #ffffff;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 15px;
        }
        .footer {
            margin-top: 35px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 13px;
            color: #9ca3af;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="university-header">
            <h2>Polytechnic University of the Philippines</h2>
            <p>ClassHub Scheduling System</p>
        </div>

        <div class="header">
            <h1>Welcome to ClassHub!</h1>
            <span class="welcome-badge">Let's Get Started</span>
        </div>

        <p>Hello <strong>{{ $user->name }}</strong>,</p>

        <p>Welcome to <strong>ClassHub</strong> - your classroom scheduling and key management system. We're excited to have you on board!</p>

        <div class="section">
            <h2>🚀 Quick Start Guide</h2>
            <div class="quick-start">
                <ol>
                    <li><strong>Complete your profile</strong> - Make sure your contact information is up to date.</li>
                    <li><strong>Browse available rooms</strong> - Check out the classrooms available for scheduling.</li>
                    <li><strong>Submit a schedule request</strong> - Request a room for your class or event.</li>
                    <li><strong>Wait for approval</strong> - Administrators will review and approve your request.</li>
                    <li><strong>Pick up the key</strong> - Once approved, retrieve the key from the Student Assistant handling the Keys</li>
                </ol>
            </div>
        </div>

        <div class="section">
            <h2>📚 Useful Links</h2>
            <div class="links">
                <a href="{{ config('app.url') }}">🏠 Public Calendar</a>
                <a href="{{ route('policy') }}">📋 Terms & Conditions</a>
                <a href="{{ config('app.url') }}/portal/request-schedule">📅 Request a Schedule</a>
            </div>
        </div>

        <div class="section">
            <h2>💬 Need Help?</h2>
            <p>If you have any questions or need assistance, feel free to reach out to our support team. We're here to help!</p>
        </div>

        <div class="btn-container">
            <a href="{{ config('app.url') }}/portal/request-schedule" class="btn">Request Your First Schedule</a>
        </div>

        <div class="footer">
            <p>Thank you for using <strong>ClassHub - PUP</strong>!</p>
            <p>This is an automated welcome message.</p>
        </div>
    </div>
</body>
</html>
