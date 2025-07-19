<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laravel</title>
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            text-align: center;
            background: white;
            padding: 3rem;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 2rem;
        }

        h1 {
            color: #2d3748;
            font-size: 3rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .subtitle {
            color: #718096;
            font-size: 1.25rem;
            margin-bottom: 2rem;
        }

        .links {
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .link {
            background: #667eea;
            color: white;
            padding: 0.75rem 1.5rem;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .link:hover {
            background: #5a67d8;
            transform: translateY(-2px);
        }

        .version {
            margin-top: 2rem;
            color: #a0aec0;
            font-size: 0.875rem;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Laravel</h1>
        <p class="subtitle">The PHP Framework for Web Artisans</p>

        <div class="links">
            <a href="https://laravel.com/docs" class="link">Documentation</a>
            <a href="https://laracasts.com" class="link">Laracasts</a>
            <a href="https://laravel-news.com" class="link">News</a>
            <a href="https://blog.laravel.com" class="link">Blog</a>
            <a href="https://nova.laravel.com" class="link">Nova</a>
            <a href="https://forge.laravel.com" class="link">Forge</a>
            <a href="https://vapor.laravel.com" class="link">Vapor</a>
            <a href="https://github.com/laravel/laravel" class="link">GitHub</a>
        </div>

        <div class="version">
            Laravel v{{ Illuminate\Foundation\Application::VERSION }} (PHP v{{ PHP_VERSION }})
        </div>
    </div>
</body>

</html>