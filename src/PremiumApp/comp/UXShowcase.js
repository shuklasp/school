/**
 * Component: UXShowcase
 * Generated via SPP CLI
 * 
 * SPP-UX Development Pillar: 
 * Reactive, Component-based UI for Enterprise PHP.
 */
export default class UXShowcase extends BaseComponent {
    async onInit() {
        this.setState({
            activeTab: 'roadmap',
            showcase: [
                { id: 'reactive', title: 'Reactive State', desc: 'Real-time UI updates via this.setState()', icon: '⚡' },
                { id: 'modular', title: 'Modular Architecture', desc: 'Encapsulated logic and styles in ES6 classes', icon: '📦' },
                { id: 'integrated', title: 'Native Integration', desc: 'Deeply bonded with SPP core and Global Settings', icon: '🔗' }
            ]
        });
    }

    render() {
        const { activeTab, showcase } = this.state;
        
        return html`
            <style>
                .ux-container { font-family: 'Inter', system-ui, sans-serif; color: #2d3748; padding: 2rem; max-width: 900px; margin: 0 auto; }
                .ux-hero { text-align: center; margin-bottom: 4rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 4rem 2rem; border-radius: 32px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
                .ux-hero h1 { font-size: 3rem; margin: 0; letter-spacing: -2px; }
                .ux-hero p { opacity: 0.9; font-size: 1.2rem; }
                
                .ux-tabs { display: flex; gap: 1rem; justify-content: center; margin-bottom: 3rem; }
                .ux-tab-btn { padding: 0.75rem 1.5rem; border-radius: 12px; border: 1px solid #e2e8f0; background: white; cursor: pointer; font-weight: 600; transition: all 0.2s; }
                .ux-tab-btn.active { background: #667eea; color: white; border-color: #667eea; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3); }
                
                .ux-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; }
                .ux-card { background: white; padding: 2rem; border-radius: 24px; border: 1px solid #edf2f7; box-shadow: 0 4px 6px rgba(0,0,0,0.02); transition: transform 0.3s; }
                .ux-card:hover { transform: translateY(-5px); }
                .ux-card .icon { font-size: 2rem; margin-bottom: 1rem; display: block; }
                
                .ux-roadmap { display: flex; flex-direction: column; gap: 2rem; }
                .roadmap-item { display: flex; gap: 1.5rem; align-items: flex-start; }
                .step-num { min-width: 40px; height: 40px; background: #667eea; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; }
                .step-content h3 { margin: 0 0 0.5rem 0; color: #1a202c; }
                .step-content p { margin: 0; color: #718096; line-height: 1.6; }
                
                .code-hint { background: #1a202c; color: #e2e8f0; padding: 1rem; border-radius: 12px; font-family: monospace; font-size: 0.9rem; margin-top: 1rem; }
            </style>

            <div class="ux-container">
                <header class="ux-hero">
                    <h1>UXShowcase</h1>
                    <p>Powered by SPP-UX Reactive Framework</p>
                </header>

                <nav class="ux-tabs">
                    <button class="ux-tab-btn ${activeTab === 'roadmap' ? 'active' : ''}" onclick=${() => this.setState({ activeTab: 'roadmap' })}>🗺️ Roadmap</button>
                    <button class="ux-tab-btn ${activeTab === 'capabilities' ? 'active' : ''}" onclick=${() => this.setState({ activeTab: 'capabilities' })}>🚀 Capabilities</button>
                </nav>

                <main>
                    ${activeTab === 'roadmap' ? html`
                        <div class="ux-roadmap">
                            <div class="roadmap-item">
                                <div class="step-num">1</div>
                                <div class="step-content">
                                    <h3>Scaffold</h3>
                                    <p>Initialize your reactive component using the SPP CLI. This generates a boilerplate with state management and lifecycle hooks.</p>
                                    <div class="code-hint">php spp.php make:ux-component ${this.name}</div>
                                </div>
                            </div>
                            <div class="roadmap-item">
                                <div class="step-num">2</div>
                                <div class="step-content">
                                    <h3>State & Logic</h3>
                                    <p>Define your initial state in <code>onInit()</code> and handle user interactions by updating state. The UI re-renders automatically.</p>
                                    <div class="code-hint">this.setState({ loading: true });</div>
                                </div>
                            </div>
                            <div class="roadmap-item">
                                <div class="step-num">3</div>
                                <div class="step-content">
                                    <h3>Integrate</h3>
                                    <p>Register your component in <code>admin.js</code> or call it directly from Blade templates to bridge the frontend and backend.</p>
                                    <div class="code-hint">@sppelement('${this.name}')</div>
                                </div>
                            </div>
                        </div>
                    ` : html`
                        <div class="ux-grid">
                            ${showcase.map(item => html`
                                <div class="ux-card">
                                    <span class="icon">${item.icon}</span>
                                    <h3>${item.title}</h3>
                                    <p>${item.desc}</p>
                                </div>
                            `)}
                        </div>
                    `}
                </main>
            </div>
        `;
    }
}