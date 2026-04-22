<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Handover Response Recorded - PUP ClassHub</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }
        .card {
            background: #fff;
            border-radius: 10px;
            padding: 40px 36px;
            max-width: 480px;
            width: 100%;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
            text-align: center;
            border-top: 6px solid {{ $action === 'confirmed' ? '#16a34a' : '#dc2626' }};
        }
        .icon {
            font-size: 52px;
            margin-bottom: 16px;
        }
        h1 {
            color: {{ $action === 'confirmed' ? '#166534' : '#991b1b' }};
            font-size: 22px;
            margin: 0 0 12px 0;
        }
        p {
            color: #6b7280;
            font-size: 15px;
            line-height: 1.6;
            margin: 0;
        }
        .school {
            margin-top: 32px;
            font-size: 12px;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
    </style>
</head>
<body>
    <div class="card">
        @if($action === 'confirmed')
            <div class="icon">✅</div>
            <h1>Handover Confirmed</h1>
            <p>Thank you — your confirmation has been recorded. The system will finalize the handover outcome at the resolution deadline.</p>
        @else
            <div class="icon">❌</div>
            <h1>Handover Disputed</h1>
            <p>Your dispute has been recorded and lab administrators have been notified. Please contact lab staff if you need immediate assistance.</p>
        @endif
        <p class="school">Polytechnic University of the Philippines · ClassHub</p>
    </div>
</body>
</html>
