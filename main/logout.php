<?php
session_start();
session_unset();
session_destroy();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging Out | FSAD</title>
    <link rel="icon" type="image/x-icon" href="../SOMANAP/images/nealogo.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .loader-container {
            position: relative;
            width: 160px;
            height: 160px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
        }

        .loader-logo {
            width: 80px;
            height: 80px;
            z-index: 10;
            animation: logo-pulsate 2s ease-in-out infinite;
        }

        .loader-ring {
            position: absolute;
            width: 140px;
            height: 140px;
            border-radius: 50%;
            border: 4px solid #f3f4f6;
            border-top: 4px solid #10b981;
            animation: ring-spin 1.5s linear infinite;
            filter: drop-shadow(0 0 8px rgba(16, 185, 129, 0.3));
        }

        @keyframes ring-spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @keyframes logo-pulsate {
            0%, 100% { transform: scale(1); filter: drop-shadow(0 0 5px rgba(16, 185, 129, 0.2)); }
            50% { transform: scale(1.08); filter: drop-shadow(0 0 15px rgba(16, 185, 129, 0.5)); }
        }

        .loading-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981, #34d399);
            width: 0%;
            animation: fill-progress 2.4s ease-out forwards;
        }

        @keyframes fill-progress {
            0% { width: 0%; }
            100% { width: 100%; }
        }
    </style>
</head>
<body class="bg-white dark:bg-gray-900">
    <div class="fixed inset-0 flex items-center justify-center bg-white dark:bg-gray-900">
        <div class="text-center">
            <div class="loader-container">
                <div class="loader-ring"></div>
                <img src="app/views/partials/nealogo.png" alt="NEA Logo" class="loader-logo">
            </div>
            <h3 class="mt-6 text-xl font-semibold text-gray-800 dark:text-white">Logging you out</h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-300">Please wait while we close your session securely.</p>
            <div class="mt-6 w-64 mx-auto">
                <div class="bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="loading-progress-fill h-2 rounded-full"></div>
                </div>
            </div>
        </div>
    </div>
    <script>
        setTimeout(function() {
            window.location.href = '../index.php';
        }, 2600);
    </script>
</body>
</html>
