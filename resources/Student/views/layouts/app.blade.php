<!DOCTYPE html>
<html>
<head>
    <title>{{ $title ?? 'SPP Blade' }}</title>
    <style>
        body { font-family: sans-serif; background: #f0f2f5; padding: 2rem; }
        .card { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); max-width: 800px; margin: auto; }
        .table { width: 100%; border-collapse: collapse; margin: 1rem 0; }
        .table th, .table td { border-bottom: 1px solid #eee; padding: 0.75rem; text-align: left; }
        .btn { display: inline-block; background: #007bff; color: white; padding: 0.5rem 1rem; border-radius: 4px; text-decoration: none; }
        input, textarea { width: 100%; padding: 0.5rem; margin-bottom: 1rem; border: 1px solid #ccc; border-radius: 4px; }
    </style>
</head>
<body>
    @yield('content')
</body>
</html>