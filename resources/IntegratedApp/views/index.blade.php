<!DOCTYPE html>
<html>
<head>
    <title>{{ $title ?? 'SPP Blade Project' }}</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; color: #333; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .card { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); text-align: center; width: 100%; max-width: 400px; }
        h1 { color: #2d3748; margin-bottom: 0.5rem; }
        p { color: #718096; }
        .badge { background: #ebf8ff; color: #3182ce; padding: 4px 12px; border-radius: 999px; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; margin-bottom: 1rem; display: inline-block; }
        form { text-align: left; margin-top: 1.5rem; }
        form div { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.9rem; }
        input[type='text'], input[type='password'] { width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 6px; box-sizing: border-box; }
        input[type='submit'] { background: #3182ce; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; font-weight: 600; cursor: pointer; width: 100%; }
        .error { color: #e53e3e; font-size: 0.8rem; margin-top: 0.25rem; }
    </style>
</head>
<body>
    <div class='card'>
        <div class='badge'>SPP + Blade Integration</div>
        
        @sppauth
            <h1>Hello, {{ \SPPMod\SPPAuth\SPPAuth::getUser()->username }}!</h1>
            <p>You are successfully logged in via SPPAuth.</p>
            <a href='?logout=1' style='color: #3182ce; text-decoration: none; font-weight: 600;'>Logout</a>
        @endsppauth

        @sppguest
            <h1>Welcome</h1>
            <p>Please log in to continue.</p>

            @sppform('login')
            @sppform_start('login_form')
                <div>
                    <label>Username</label>
                    @sppelement('username')
                </div>
                <div>
                    <label>Password</label>
                    @sppelement('password')
                </div>
                <div>
                    @sppelement('submit')
                </div>
            @sppform_end
        @endsppguest

        <hr style='border: 0; border-top: 1px solid #edf2f7; margin: 1.5rem 0;'>
        <div style='font-size: 0.85rem; color: #a0aec0;'>Application Context: <strong>{{ $appName }}</strong></div>
    </div>
</body>
</html>