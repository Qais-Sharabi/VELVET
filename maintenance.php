<?php
require_once 'dbconnect.php';
/** @var PDO $pdo */

// 1. Fetch Store Settings from your database
$settings_stmt = $pdo->prepare("SELECT * FROM store_settings LIMIT 1");
$settings_stmt->execute();
$settings = $settings_stmt->fetch(PDO::FETCH_ASSOC);

// Fallback in case the table is empty
if (!$settings) {
    $settings = [
            'store_name' => 'VELVET',
            'support_phone' => '+972 59-123-1231',
            'support_email' => 'info@VELVET.com',
            'facebook_url' => '#',
            'instagram_url' => '#'
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($settings['store_name']); ?> — Maintenance</title>
    <link href="https://fonts.googleapis.com/css2?family=Vidaloka&family=Jost:wght@300;400&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #0f0d0b;
            font-family: 'Jost', sans-serif;
            color: #c9b99a;
            text-align: center;
            padding: 2rem;
            overflow: hidden;
        }

        .wrap { max-width: 480px; }

        /* Animation Keyframes */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes pulse {
            0% { transform: scale(1); opacity: 0.7; }
            50% { transform: scale(1.15); opacity: 1; }
            100% { transform: scale(1); opacity: 0.7; }
        }

        @keyframes letterSpacing {
            from { letter-spacing: 0.2em; opacity: 0; }
            to { letter-spacing: 0.5em; opacity: 1; }
        }

        /* Applied Classes */
        .animate {
            opacity: 0;
            animation: fadeInUp 1.2s ease-out forwards;
        }

        .delay-1 { animation-delay: 0.3s; }
        .delay-2 { animation-delay: 0.6s; }
        .delay-3 { animation-delay: 0.9s; }

        .logo {
            font-family: "Vidaloka", serif;
            font-weight: 400;
            font-size: 3.5rem;
            color: #f0e8dc;
            text-transform: uppercase;
            margin-bottom: 3rem;
            animation: letterSpacing 2s ease-out forwards infinite;
            display: block;
        }

        .icon {
            font-size: 3.5rem;
            margin-bottom: 1.5rem;
            display: block;
            animation: pulse 4s infinite ease-in-out;
        }

        h1 {
            font-family: "Vidaloka", serif;
            font-size: 1.8rem;
            font-weight: 300;
            color: #f0e8dc;
            margin-bottom: 1.2rem;
            line-height: 1.3;
        }

        p {
            font-size: 0.95rem;
            font-weight: 300;
            letter-spacing: .06em;
            line-height: 1.8;
            color: #9a8a76;
            margin-bottom: 2rem;
        }

        .divider {
            width: 60px;
            height: 1px;
            background: #4a3f34;
            margin: 2.5rem auto;
            transform-origin: center;
        }

        .back-time {
            font-size: 0.75rem;
            letter-spacing: .2em;
            text-transform: uppercase;
            color: #6a5a4a;
        }

        .contact-link {
            display: inline-block;
            margin-top: 2.5rem;
            font-size: 0.75rem;
            letter-spacing: .15em;
            text-transform: uppercase;
            color: #9a8a76;
            text-decoration: none;
            border-bottom: 1px solid #4a3f34;
            padding-bottom: 4px;
            transition: all 0.4s ease;
        }

        .contact-link:hover {
            color: #f0e8dc;
            border-color: #f0e8dc;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>

<div class="wrap">
    <div class="logo"><?php echo htmlspecialchars($settings['store_name']); ?></div>

    <span class="icon">🛠</span>

    <div class="animate delay-1">
        <h1>We're polishing things up.</h1>
    </div>

    <div class="animate delay-2">
        <p>
            Our store is currently undergoing scheduled maintenance.<br>
            We'll be back shortly — thank you for your patience.
        </p>
    </div>

    <div class="divider animate delay-3"></div>

    <div class="animate delay-3">
        <p class="back-time">Returning to service soon</p>
        <a href="mailto:<?php echo htmlspecialchars($settings['support_email']); ?>" class="contact-link">
            Contact Support
        </a>
    </div>
</div>

</body>
</html>