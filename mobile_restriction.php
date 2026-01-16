<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Desktop Only</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            text-align: center;
            color: #1f2937;
        }

        .message-card {
            background: white;
            padding: 3rem;
            border-radius: 1.5rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            max-width: 90%;
            width: 400px;
            animation: fadeIn 0.8s ease-out;
            border: 1px solid #e5e7eb;
        }

        .icon-box {
            width: 80px;
            height: 80px;
            background: #e0e7ff;
            color: #4f46e5;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem auto;
            font-size: 2.5rem;
            animation: pulse 2s infinite;
        }

        h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            color: #111827;
        }

        p {
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 0;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.4);
            }

            70% {
                transform: scale(1.05);
                box-shadow: 0 0 0 10px rgba(99, 102, 241, 0);
            }

            100% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(99, 102, 241, 0);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>
    <div class="message-card">
        <div class="icon-box">
            <i class="fas fa-desktop"></i>
        </div>
        <h1>Please Use Desktop</h1>
        <p>This application is optimized for desktop and laptop devices only to ensure a professional experience.</p>
        <p style="margin-top: 1rem; font-size: 0.9rem; font-weight: 500; color: #4f46e5;">Mobile support coming soon.
        </p>
    </div>
</body>

</html>