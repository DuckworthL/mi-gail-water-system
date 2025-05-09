<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Mi-Gail Water</title>
        
        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700&display=swap" rel="stylesheet">
        
        <!-- Styles -->
        <link href="{{ asset('css/app.css') }}" rel="stylesheet">
        <link href="{{ asset('css/custom.css') }}" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
        
        <style>
            body {
                font-family: 'Nunito', sans-serif;
                background: linear-gradient(135deg, #0891b2, #0369a1);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
            }
            .welcome-card {
                max-width: 500px;
                background-color: rgba(255, 255, 255, 0.1);
                backdrop-filter: blur(10px);
                border-radius: 1rem;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
                padding: 3rem;
                text-align: center;
                border: 1px solid rgba(255, 255, 255, 0.3);
            }
            .welcome-logo {
                transform: scale(1.5);
                margin-bottom: 2rem;
            }
            .welcome-actions {
                margin-top: 2rem;
            }
            .btn-welcome {
                background-color: white;
                color: #0891b2;
                border: none;
                font-weight: 600;
                padding: 0.75rem 1.5rem;
                border-radius: 0.5rem;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                transition: all 0.3s ease;
            }
            .btn-welcome:hover {
                transform: translateY(-3px);
                box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
            }
            .water-bubbles {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: -1;
                overflow: hidden;
            }
            .bubble {
                position: absolute;
                bottom: -10px;
                background-color: rgba(255, 255, 255, 0.2);
                border-radius: 50%;
                animation: rise 10s infinite ease-in;
            }
            @keyframes rise {
                0% {
                    transform: translateY(0) scale(0.5);
                    opacity: 0;
                }
                50% {
                    opacity: 0.6;
                }
                100% {
                    transform: translateY(-120vh) scale(1.8);
                    opacity: 0;
                }
            }
        </style>
    </head>
    <body>
        <div class="water-bubbles">
            @for ($i = 0; $i < 20; $i++)
                <div class="bubble" style="
                    left: {{ rand(0, 100) }}%;
                    width: {{ rand(10, 50) }}px;
                    height: {{ rand(10, 50) }}px;
                    animation-delay: {{ rand(0, 5) }}s;
                    animation-duration: {{ rand(6, 15) }}s;
                "></div>
            @endfor
        </div>
        
        <div class="welcome-card">
            <div class="welcome-logo">
                <div class="water-drop"></div>
            </div>
            <h1 class="mb-3">Mi-Gail Water</h1>
            <p class="mb-4">Refilling Station Management System</p>
            
            <div class="welcome-actions">
                @auth
                    <a href="{{ url('/dashboard') }}" class="btn btn-welcome">
                        <i class="bi bi-speedometer2 me-2"></i>Go to Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-welcome">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Login
                    </a>
                @endauth
            </div>
        </div>
    </body>
</html>