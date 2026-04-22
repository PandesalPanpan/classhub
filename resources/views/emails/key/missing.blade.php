<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Key Missing Alert - PUP</title>
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
        .urgency-badge {
            display: inline-block;
            background-color: #fee2e2;
            color: #991b1b;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 700;
            margin-top: 10px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .details-table td {
            padding: 12px 0;
            border-bottom: 1px solid #f3f4f6;
            font-size: 15px;
        }
        .details-table tr:last-child td {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #6b7280;
            width: 35%;
            vertical-align: top;
        }
        .info-value {
            color: #111827;
            font-weight: 500;
        }
        .alert-box {
            background-color: #fef2f2;
            border-left: 4px solid #ef4444;
            padding: 16px;
            margin: 25px 0;
            border-radius: 0 6px 6px 0;
            font-size: 14px;
        }
        .action-required {
            background-color: #fff7ed;
            border-left: 4px solid #f97316;
            padding: 16px;
            margin: 25px 0;
            border-radius: 0 6px 6px 0;
            font-size: 14px;
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
            <h1>🚨 URGENT: Key Missing</h1>
            <span class="urgency-badge">Action Required</span>
        </div>

        <p>Hello <strong>{{ $recipient->name ?? 'Administrator' }}</strong>,</p>

        <p>A key has been reported <strong>missing</strong> from the IoT key box. This requires immediate attention.</p>

        <table class="details-table">
            <tr>
                <td class="info-label">Room</td>
                <td class="info-value">{{ $lastSchedule->room?->room_number ?? 'Unknown' }}</td>
            </tr>
            <tr>
                <td class="info-label">Slot Number</td>
                <td class="info-value">{{ $lastSchedule->room?->key?->slot_number ?? 'Unknown' }}</td>
            </tr>
            <tr>
                <td class="info-label">Last Scheduled Time</td>
                <td class="info-value">
                    {{ $lastSchedule->start_time->format('M j, Y g:i A') }} - {{ $lastSchedule->end_time->format('g:i A') }}
                </td>
            </tr>
            <tr>
                <td class="info-label">Subject/Course</td>
                <td class="info-value">{{ $lastSchedule->subject ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="info-label">Requester</td>
                <td class="info-value">{{ $lastSchedule->requester?->name ?? 'Unknown' }}</td>
            </tr>
        </table>

        <div class="alert-box">
            <strong>⚠️ Situation:</strong>
            <p style="margin: 10px 0 0 0;">
                The post-class check detected that the key was not returned to the box after the scheduled session ended. The key status has been automatically updated to <strong>Missing</strong>.
            </p>
        </div>

        <div class="action-required">
            <strong>📋 Action Required:</strong>
            <ul style="margin: 10px 0 0 0; padding-left: 20px;">
                <li>Investigate the missing key immediately.</li>
                <li>Contact the last scheduled requester if necessary.</li>
                <li>Replace or relocate the key as needed.</li>
                <li>Update the key status in the admin dashboard once resolved.</li>
            </ul>
        </div>

        <div class="btn-container">
            <a href="{{ config('app.url') }}/admin/resources/keys" class="btn">Manage Keys</a>
        </div>

        <div class="footer">
            <p>This is an automated alert from <strong>ClassHub - PUP</strong>.</p>
            <p>Please address this issue as soon as possible.</p>
        </div>
    </div>
</body>
</html>
