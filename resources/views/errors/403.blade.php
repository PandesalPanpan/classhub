<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Access Restricted | PUP ClassHub</title>
    <style>
        :root {
            --bg: #f3f4f6;
            --card-bg: #ffffff;
            --text: #111827;
            --muted: #6b7280;
            --brand: #800000;
            --accent: #ffdf00;
            --button-text: #ffffff;
            --shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
            --footer: #9ca3af;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #111827;
                --card-bg: #1f2937;
                --text: #f9fafb;
                --muted: #d1d5db;
                --brand: #b91c1c;
                --accent: #facc15;
                --button-text: #ffffff;
                --shadow: 0 8px 24px rgba(0, 0, 0, 0.45);
                --footer: #9ca3af;
            }
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--bg);
            color: var(--text);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }

        .card {
            background: var(--card-bg);
            border-radius: 10px;
            padding: 40px 36px;
            max-width: 480px;
            width: 100%;
            box-shadow: var(--shadow);
            text-align: center;
            border-top: 6px solid var(--brand);
        }

        .code {
            font-size: 72px;
            font-weight: 800;
            color: var(--brand);
            margin: 0;
            line-height: 1;
        }

        h1 {
            color: var(--text);
            font-size: 22px;
            margin: 12px 0 10px;
        }

        .divider {
            height: 2px;
            width: 100%;
            background-color: var(--accent);
            margin: 18px 0 16px;
        }

        p {
            color: var(--muted);
            font-size: 15px;
            line-height: 1.6;
            margin: 0;
        }

        .actions {
            margin-top: 26px;
        }

        .btn {
            background-color: var(--brand);
            color: var(--button-text);
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            font-weight: 600;
        }

        .btn:hover {
            filter: brightness(1.05);
        }

        .school {
            margin-top: 30px;
            font-size: 12px;
            color: var(--footer);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        @media (max-width: 520px) {
            .card {
                padding: 32px 24px;
            }

            .code {
                font-size: 62px;
            }
        }
    </style>
</head>
<body>
    <div class="card">
        <p class="code">403</p>
        <h1>Access Restricted</h1>
        <div class="divider"></div>
        <p>You do not have permission to view this page. If this seems wrong, contact your lab administrator.</p>
        <div class="actions">
            <a class="btn" href="{{ url('/') }}">Go to Home Page</a>
        </div>
        <p class="school">Polytechnic University of the Philippines · ClassHub</p>
    </div>
</body>
</html>
