<!DOCTYPE html>
<html>
<head>
    <title>{{ $title ?? 'SPP Blade Project' }}</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; color: #333; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); text-align: center; max-width: 400px; }
        h1 { color: #2d3748; margin-bottom: 0.5rem; }
        p { color: #718096; }
        .badge { background: #ebf8ff; color: #3182ce; padding: 4px 12px; border-radius: 999px; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; }
    </style>
</head>
<body>
    <div class='card'>
        <div class='badge'>SPP Blade Engine</div>
        <h1>{{ $appName }}</h1>
        <p>Welcome to your new Blade-powered SPP project. Start editing <code>resources/{{ $appName }}/views/index.blade.php</code></p>
        <hr style='border: 0; border-top: 1px solid #edf2f7; margin: 1.5rem 0;'>
        <div style='font-size: 0.85rem; color: #a0aec0;'>Time: {{ date('Y-m-d H:i:s') }}</div>
    </div>
</body>
</html>