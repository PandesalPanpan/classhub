<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'ClassHub Notification')</title>
    <style>
        @media only screen and (max-width: 640px) {
            .email-shell {
                width: 100% !important;
            }
            .email-content {
                padding: 20px !important;
            }
            .email-heading {
                font-size: 20px !important;
            }
            .email-button {
                display: block !important;
                width: 100% !important;
                box-sizing: border-box !important;
            }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f5f7; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #374151;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color: #f4f5f7;">
        <tr>
            <td align="center" style="padding: 24px 12px;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" class="email-shell" style="width: 600px; max-width: 600px; background-color: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid #e5e7eb;">
                    <tr>
                        <td style="height: 6px; background-color: #800000;"></td>
                    </tr>
                    <tr>
                        <td style="padding: 24px 28px 20px 28px; text-align: center;">
                            <p style="margin: 0; color: #800000; font-size: 18px; font-weight: 700; letter-spacing: 0.03em; text-transform: uppercase;">Polytechnic University of the Philippines</p>
                            <p style="margin: 6px 0 0 0; color: #6b7280; font-size: 12px; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase;">ClassHub Scheduling System</p>
                            <div style="margin-top: 14px; height: 2px; background-color: #FFDF00;"></div>
                        </td>
                    </tr>
                    <tr>
                        <td class="email-content" style="padding: 0 28px 28px 28px;">
                            @yield('content')
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 0 28px 22px 28px;">
                            <div style="border-top: 1px solid #e5e7eb; padding-top: 14px; text-align: center; color: #6b7280; font-size: 12px; line-height: 1.5;">
                                This is an automated message from ClassHub - Polytechnic University of the Philippines.
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
