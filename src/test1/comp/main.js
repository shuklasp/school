export default class Main extends BaseComponent {
    async onInit() {
        this.setState({ 
            message: 'Welcome to SPP-UX',
            subtitle: 'The Future of Reactive Frameworks',
            features: [
                { id: 'state', title: 'Reactive State', icon: '⚡', desc: 'Auto-rendering UI when data changes. No Virtual DOM overhead.' },
                { id: 'bridge', title: 'Service Bridge', icon: '🌉', desc: 'Call PHP services directly from JS with zero boilerplate.' },
                { id: 'components', title: 'Universal Components', icon: '🧩', desc: 'Isomorphic rendering across React, Vue, and Native UX.' },
                { id: 'evolution', title: 'Evolutionary Engine', icon: '🧬', desc: 'Database schemas that evolve with your application code.' }
            ],
            steps: [
                { num: '01', title: 'Define Entities', desc: 'Create YAML models in /etc/apps/test1/entities/' },
                { num: '02', title: 'Build Services', desc: 'Implement business logic in /src/test1/services/' },
                { num: '03', title: 'Create Views', desc: 'Design reactive components in /src/test1/comp/' }
            ]
        });
    }

    render() {
        return html`
            <div class="ux-dashboard">
                <!-- Header Section -->
                <header class="ux-header">
                    <div class="logo-orb">SPP</div>
                    <div class="header-text">
                        <h1>${this.state.message} <span class="badge">v0.5</span></h1>
                        <p class="subtitle">${this.state.subtitle}</p>
                    </div>
                </header>

                <main class="ux-main">
                    <!-- Feature Grid -->
                    <section class="feature-grid">
                        ${this.state.features.map(f => html`
                            <div class="feature-card glass-panel">
                                <div class="feature-icon">${f.icon}</div>
                                <h3>${f.title}</h3>
                                <p>${f.desc}</p>
                            </div>
                        `)}
                    </section>

                    <!-- Developer Roadmap -->
                    <section class="roadmap-section glass-panel">
                        <h2>🚀 Getting Started</h2>
                        <div class="steps-container">
                            ${this.state.steps.map(s => html`
                                <div class="step-item">
                                    <div class="step-num">${s.num}</div>
                                    <div class="step-content">
                                        <h4>${s.title}</h4>
                                        <p>${s.desc}</p>
                                    </div>
                                </div>
                            `)}
                        </div>
                    </section>

                    <!-- Quick Links -->
                    <footer class="ux-footer">
                        <button class="btn primary-btn" onclick="${() => window.open('/docs')}">📖 Documentation</button>
                        <button class="btn ghost-btn" onclick="${() => location.hash = 'lifecycle'}">⚙️ Workbench</button>
                    </footer>
                </main>
            </div>

            <style>
                @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap');

                .ux-dashboard {
                    font-family: 'Inter', sans-serif;
                    background: radial-gradient(circle at top right, #1e1b4b, #020617);
                    color: #f8fafc;
                    min-height: 100vh;
                    padding: 4rem 2rem;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                }

                .ux-header {
                    text-align: center;
                    margin-bottom: 4rem;
                    animation: fadeInDown 0.8s ease-out;
                }

                .logo-orb {
                    width: 80px;
                    height: 80px;
                    background: linear-gradient(135deg, #6366f1, #a855f7);
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-weight: 800;
                    font-size: 1.5rem;
                    margin: 0 auto 1.5rem;
                    box-shadow: 0 0 30px rgba(99, 102, 241, 0.4);
                }

                .ux-header h1 {
                    font-size: 3.5rem;
                    font-weight: 800;
                    letter-spacing: -0.02em;
                    margin-bottom: 0.5rem;
                    background: linear-gradient(to right, #fff, #94a3b8);
                    -webkit-background-clip: text;
                    -webkit-text-fill-color: transparent;
                }

                .badge {
                    font-size: 1rem;
                    background: rgba(99, 102, 241, 0.2);
                    color: #818cf8;
                    padding: 4px 12px;
                    border-radius: 20px;
                    border: 1px solid rgba(99, 102, 241, 0.3);
                    vertical-align: middle;
                    margin-left: 10px;
                }

                .subtitle {
                    font-size: 1.25rem;
                    opacity: 0.6;
                    font-weight: 300;
                }

                .feature-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                    gap: 1.5rem;
                    max-width: 1200px;
                    width: 100%;
                    margin-bottom: 3rem;
                }

                .glass-panel {
                    background: rgba(255, 255, 255, 0.03);
                    backdrop-filter: blur(12px);
                    border: 1px solid rgba(255, 255, 255, 0.08);
                    border-radius: 24px;
                    padding: 2rem;
                    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                }

                .feature-card:hover {
                    background: rgba(255, 255, 255, 0.05);
                    transform: translateY(-8px);
                    border-color: rgba(99, 102, 241, 0.3);
                    box-shadow: 0 20px 40px rgba(0,0,0,0.4);
                }

                .feature-icon {
                    font-size: 2.5rem;
                    margin-bottom: 1.5rem;
                }

                .feature-card h3 {
                    font-size: 1.25rem;
                    font-weight: 600;
                    margin-bottom: 0.75rem;
                    color: #fff;
                }

                .feature-card p {
                    font-size: 0.95rem;
                    line-height: 1.6;
                    opacity: 0.6;
                }

                .roadmap-section {
                    max-width: 1200px;
                    width: 100%;
                    margin-top: 2rem;
                }

                .roadmap-section h2 {
                    font-size: 1.75rem;
                    margin-bottom: 2rem;
                    text-align: center;
                }

                .steps-container {
                    display: flex;
                    justify-content: space-around;
                    flex-wrap: wrap;
                    gap: 2rem;
                }

                .step-item {
                    display: flex;
                    align-items: flex-start;
                    gap: 1rem;
                    max-width: 300px;
                }

                .step-num {
                    font-size: 2rem;
                    font-weight: 800;
                    color: rgba(99, 102, 241, 0.3);
                    line-height: 1;
                }

                .step-content h4 {
                    font-weight: 600;
                    margin-bottom: 0.5rem;
                }

                .step-content p {
                    font-size: 0.85rem;
                    opacity: 0.5;
                }

                .ux-footer {
                    margin-top: 4rem;
                    display: flex;
                    gap: 1.5rem;
                }

                .btn {
                    padding: 12px 32px;
                    border-radius: 12px;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.2s;
                    border: none;
                }

                .primary-btn {
                    background: linear-gradient(135deg, #6366f1, #a855f7);
                    color: white;
                }

                .primary-btn:hover {
                    transform: scale(1.05);
                    box-shadow: 0 0 20px rgba(99, 102, 241, 0.4);
                }

                .ghost-btn {
                    background: transparent;
                    color: white;
                    border: 1px solid rgba(255,255,255,0.2);
                }

                .ghost-btn:hover {
                    background: rgba(255,255,255,0.05);
                }

                @keyframes fadeInDown {
                    from { opacity: 0; transform: translateY(-20px); }
                    to { opacity: 1; transform: translateY(0); }
                }
            </style>
        `;
    }
}