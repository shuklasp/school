export default class Main extends BaseComponent {
    async onInit() {
        this.setState({ 
            activeTab: 'roadmap',
            appName: 'PremiumSppUx',
            stats: [
                { label: 'Latency', value: '0.4ms', icon: '⚡' },
                { label: 'Security', value: 'Shielded', icon: '🛡️' },
                { label: 'Uptime', value: '99.99%', icon: '🌐' }
            ]
        });
    }

    render() {
        return html`
            <div class="premium-container">
                <nav class="premium-nav">
                    <div class="nav-brand">
                        <img src="/school1/res/spp/images/logo.jpg" alt="Logo">
                        <span>SPP<span>UX</span></span>
                    </div>
                    <div class="nav-links">
                        <button class="${this.state.activeTab === 'roadmap' ? 'active' : ''}" 
                                @click="${() => this.setState({ activeTab: 'roadmap' })}">🗺️ Roadmap</button>
                        <button class="${this.state.activeTab === 'capabilities' ? 'active' : ''}" 
                                @click="${() => this.setState({ activeTab: 'capabilities' })}">🚀 Capabilities</button>
                    </div>
                </nav>

                <main class="premium-hero">
                    <div class="hero-content">
                        <div class="badge">Evolving Infrastructure</div>
                        <h1>${this.state.appName}</h1>
                        <p>Powered by SPP-UX Reactive Framework</p>
                        
                        <div class="stats-grid">
                            ${this.state.stats.map(s => html`
                                <div class="stat-card">
                                    <span class="stat-icon">${s.icon}</span>
                                    <div class="stat-info">
                                        <div class="stat-label">${s.label}</div>
                                        <div class="stat-value">${s.value}</div>
                                    </div>
                                </div>
                            `)}
                        </div>
                    </div>

                    <div class="view-container glass-panel">
                        ${this.state.activeTab === 'roadmap' ? this.renderRoadmap() : this.renderCapabilities()}
                    </div>
                </main>

                <footer class="premium-footer">
                    &copy; ${new Date().getFullYear()} SPP Framework • Reactive Web Experience
                </footer>
            </div>
        `;
    }

    renderRoadmap() {
        return html`
            <div class="roadmap-view">
                <h3>Development Lifecycle</h3>
                <div class="timeline">
                    <div class="timeline-item done">
                        <div class="point"></div>
                        <div class="content">
                            <h4>Phase 1: Scaffolding</h4>
                            <p>Application structure generated via CLI.</p>
                        </div>
                    </div>
                    <div class="timeline-item active">
                        <div class="point"></div>
                        <div class="content">
                            <h4>Phase 2: Logic Integration</h4>
                            <p>Injecting services and database entities.</p>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <div class="point"></div>
                        <div class="content">
                            <h4>Phase 3: Visual Polish</h4>
                            <p>Implementing premium UX transitions.</p>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    renderCapabilities() {
        return html`
            <div class="capabilities-view">
                <h3>Framework Capabilities</h3>
                <div class="cap-grid">
                    <div class="cap-card">
                        <div class="cap-icon">⚡</div>
                        <h4>Reactive State</h4>
                        <p>Real-time UI updates via this.setState()</p>
                    </div>
                    <div class="cap-card">
                        <div class="cap-icon">🔒</div>
                        <h4>Secured API</h4>
                        <p>Native integration with SPPAuth services.</p>
                    </div>
                </div>
            </div>
        `;
    }
}