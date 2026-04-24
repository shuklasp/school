@extends('layouts.app')

@section('title', 'Welcome to ' . $appName)

@section('content')
    <div class='hero'>
        <div class='glass-panel' style='display:inline-block; padding: 8px 20px; border-radius: 50px; font-size: 0.8rem; font-weight: 700; margin-bottom: 2rem;'>
            🚀 NOW POWERED BY BLADE ENGINE
        </div>
        <h1>Expressive. Fast. <br> Seamlessly Integrated.</h1>
        <p>Your new SPP application <strong>{{ $appName }}</strong> is ready. Experience the perfect harmony of Blade templates and SPP core services.</p>
        
        <div style='margin-top: 3rem;'>
            <a href='#workflow' class='btn primary-btn'>Explore Workflow</a>
            <a href='#demo' class='btn' style='margin-left: 1rem; border: 1px solid var(--glass-border);'>Try Interactive Demo</a>
        </div>
    </div>

    <div class='grid'>
        <div class='glass-panel feature-card'>
            <span class='icon'>🏗️</span>
            <h3>Native Directives</h3>
            <p>Use <code>@@sppform</code>, <code>@@sppauth</code>, and <code>@@sppbind</code> to bridge your templates with SPP modules effortlessly.</p>
        </div>
        <div class='glass-panel feature-card'>
            <span class='icon'>⚡</span>
            <h3>Zero Latency</h3>
            <p>Blade templates are compiled to plain PHP and cached, ensuring your enterprise apps run at peak performance.</p>
        </div>
        <div class='glass-panel feature-card'>
            <span class='icon'>📂</span>
            <h3>Structured Data</h3>
            <p>Built-in support for YAML forms and Entities means you spend less time on boilerplate and more on features.</p>
        </div>
    </div>

    <div class='workflow' id='workflow'>
        <h2>The SPP Workflow Advantage</h2>
        <p>Go from idea to implementation in three simple steps.</p>
        
        <div class='workflow-steps'>
            <div class='step'>
                <div class='step-num'>1</div>
                <h4>Define</h4>
                <p>Create your entity or form in simple YAML.</p>
                <div class='code-block'># login.yml
form:
  name: login_form
  controls:
    - username
    - password</div>
            </div>
            <div class='step'>
                <div class='step-num'>2</div>
                <h4>Scaffold</h4>
                <p>Generate full-stack logic with a single CLI command.</p>
                <div class='code-block' style='background: #1a202c;'>php spp.php make:blade-scaffold Login</div>
            </div>
            <div class='step'>
                <div class='step-num'>3</div>
                <h4>Render</h4>
                <p>Drop the component into your Blade view.</p>
                <div class='code-block'>@@sppform('login')</div>
            </div>
        </div>
    </div>

    <div id='ux-showcase' style='margin-top: 8rem;'>
        <h2 style='text-align: center;'>SPP-UX Reactive Showcase</h2>
        <p style='text-align: center; color: var(--text-muted); margin-bottom: 3rem;'>Experience the power of reactive components directly in your Blade views.</p>
        
        <div class='glass-panel' style='padding: 0; overflow: hidden;'>
            @sppux('UXShowcase')
        </div>
    </div>

    <div id='demo' style='margin-top: 8rem; text-align: center;'>
        <div class='glass-panel' style='max-width: 500px; margin: 0 auto; text-align: left;'>
            <h2 style='text-align: center;'>Interactive Login Demo</h2>
            <p style='text-align: center; font-size: 0.9rem; margin-bottom: 2rem;'>This form is controlled via <code>etc/apps/{{ $appName }}/forms/login.yml</code></p>
            
            @sppguest
                @sppform('login')
                @sppform_start('login_form')
                    <div style='margin-bottom: 1.5rem;'>
                        <label>Username</label>
                        @sppelement('username', ['class' => 'form-input', 'style' => 'width:100%; padding: 0.8rem; border-radius: 8px; border: 1px solid var(--glass-border); background: rgba(255,255,255,0.05); color: inherit;'])
                    </div>
                    <div style='margin-bottom: 1.5rem;'>
                        <label>Password</label>
                        @sppelement('password', ['class' => 'form-input', 'style' => 'width:100%; padding: 0.8rem; border-radius: 8px; border: 1px solid var(--glass-border); background: rgba(255,255,255,0.05); color: inherit;'])
                    </div>
                    <div style='margin-top: 2rem;'>
                        @sppelement('submit', ['class' => 'btn primary-btn', 'style' => 'width: 100%;'])
                    </div>
                @sppform_end
            @endsppguest

            @sppauth
                <div style='text-align: center; padding: 2rem;'>
                    <div style='font-size: 3rem; margin-bottom: 1rem;'>👋</div>
                    <h3>Welcome back, {{ \SPPMod\SPPAuth\SPPAuth::getUser()->username }}!</h3>
                    <p>You have accessed this restricted area via <code>@@sppauth</code>.</p>
                    <a href='?logout=1' class='btn' style='margin-top: 1rem; color: var(--accent); font-weight: 600;'>Logout</a>
                </div>
            @endsppauth
        </div>
    </div>
@endsection