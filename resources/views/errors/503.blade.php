<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Back in a moment · {{ config('app.name') }}</title>

    {{-- Auto-retry: browser refreshes every 45 seconds --}}
    <meta http-equiv="refresh" content="45">

    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🍲</text></svg>">

    <style>
        * { box-sizing: border-box; }
        html, body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #fdf6f0;
            color: #2d2d2d;
            -webkit-font-smoothing: antialiased;
        }

        body {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background-image:
                radial-gradient(circle at 20% 20%, rgba(230, 126, 34, 0.06) 0%, transparent 45%),
                radial-gradient(circle at 80% 80%, rgba(230, 126, 34, 0.05) 0%, transparent 45%);
        }

        .card {
            background: #ffffff;
            border-radius: 24px;
            padding: 56px 40px;
            max-width: 520px;
            width: 100%;
            text-align: center;
            box-shadow:
                0 2px 4px rgba(0, 0, 0, 0.02),
                0 20px 40px rgba(0, 0, 0, 0.06);
            border-top: 6px solid #e67e22;
            position: relative;
            overflow: hidden;
        }

        .steam {
            font-size: 72px;
            line-height: 1;
            margin-bottom: 8px;
            animation: bob 3s ease-in-out infinite;
            display: inline-block;
        }

        @keyframes bob {
            0%, 100% { transform: translateY(0) rotate(-2deg); }
            50%      { transform: translateY(-6px) rotate(2deg); }
        }

        h1 {
            font-size: 28px;
            font-weight: 700;
            color: #2d2d2d;
            margin: 0 0 12px;
            letter-spacing: -0.01em;
        }

        h1 span {
            color: #e67e22;
        }

        p {
            font-size: 15px;
            line-height: 1.65;
            color: #555;
            margin: 0 0 16px;
        }

        p.lead {
            font-size: 16px;
            color: #444;
        }

        .status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #fdf6f0;
            border: 1px solid #f0e6de;
            border-radius: 999px;
            padding: 8px 18px;
            font-size: 13px;
            font-weight: 500;
            color: #8c3a0e;
            margin: 8px 0 24px;
        }

        .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #e67e22;
            animation: pulse 1.8s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1;   transform: scale(1); }
            50%      { opacity: 0.4; transform: scale(1.3); }
        }

        .tip {
            background: #fef6ee;
            border-radius: 14px;
            padding: 16px 20px;
            font-size: 13px;
            color: #73310f;
            line-height: 1.55;
            margin-top: 8px;
            text-align: left;
        }

        .tip strong {
            color: #d35400;
        }

        .footer {
            margin-top: 32px;
            font-size: 12px;
            color: #999;
            letter-spacing: 0.02em;
        }

        .footer .emoji-row {
            font-size: 16px;
            margin-bottom: 6px;
            letter-spacing: 4px;
            opacity: 0.55;
        }

        @media (max-width: 480px) {
            .card { padding: 40px 24px; border-radius: 20px; }
            .steam { font-size: 56px; }
            h1 { font-size: 22px; }
            p { font-size: 14px; }
        }
    </style>
</head>
<body>

    <div class="card">
        <div class="steam">🍲</div>

        <h1>Our kitchen took a <span>quick breather</span></h1>

        <p class="lead">
            The chefs are stirring something behind the scenes 🥄
            — updating the system so lunch runs even smoother.
        </p>

        <div class="status">
            <span class="dot"></span>
            <span>Back in just a moment</span>
        </div>

        <div class="tip">
            <strong>🧾 Your order is safe.</strong> If you placed one already, it's been
            recorded and will be cooked as soon as we're back.<br><br>
            <strong>⏳ Refresh every minute.</strong> This page will
            automatically reload in 45 seconds — no need to keep checking.
        </div>

        <div class="footer">
            <div class="emoji-row">🥘 &nbsp; 🍛 &nbsp; 🍜</div>
            <div>{{ config('app.name') }} · Fresh tiffins, coming right up</div>
        </div>
    </div>

</body>
</html>