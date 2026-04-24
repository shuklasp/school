/**
 * TestingView Component
 * 
 * Provides an interface for Automated Evolutionary Testing.
 */
export default class TestingView extends BaseComponent {
    async onInit() {
        this.state = {
            loading: false,
            running: false,
            results: null,
            appname: this.admin.selectedApp || 'default'
        };
    }

    async runTests() {
        if (this.state.running) return;

        this.setState({ running: true, results: null });
        try {
            const res = await this.admin.apiPost('run_auto_tests', {
                appname: this.state.appname
            });
            
            if (res.success) {
                this.setState({ results: res.data });
                this.admin.notify(`Evolutionary tests completed for ${this.state.appname}.`, 'success');
            } else {
                this.admin.notify(res.message, 'error');
            }
        } catch (e) {
            this.admin.notify('Network error during automated testing.', 'error');
        } finally {
            this.setState({ running: false });
        }
    }

    render() {
        const { running, results, appname } = this.state;

        return html`
            <div class="view-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem;">
                <div>
                    <h2 style="margin:0;">Evolutionary Testing Engine</h2>
                    <p style="opacity:0.6; margin-top:5px;">Zero-code Automated Quality Assurance for [${appname}]</p>
                </div>
                <button class="btn accent-btn" onclick="${() => this.runTests()}" ?disabled="${running}">
                    ${running ? html`<span class="spinner"></span> Running Analysis...` : '⚡ Run System Scan'}
                </button>
            </div>

            ${running ? html`
                <div class="glass-panel" style="text-align:center; padding:4rem 2rem;">
                    <div class="scanning-animation">
                        <div class="scan-ring"></div>
                        <div class="scan-core">🔍</div>
                    </div>
                    <h3 style="margin-top:2rem;">Analyzing System Metadata...</h3>
                    <p style="opacity:0.7;">Fuzzing entity invariants and verifying persistence cycles.</p>
                </div>
            ` : ''}

            ${!running && !results ? html`
                <div class="empty-state glass-panel">
                    <div style="font-size:4rem; margin-bottom:1rem;">🧬</div>
                    <h3>Engine Ready</h3>
                    <p>Trigger a full system scan to auto-generate and execute tests for all entities in the current context.</p>
                    <div style="margin-top:2rem; display:flex; gap:10px; justify-content:center;">
                        <span class="tag info-tag">Shadow DB: Enabled</span>
                        <span class="tag success-tag">Isolation: spptest__</span>
                    </div>
                </div>
            ` : ''}

            ${results ? this.renderResults(results) : ''}

            <style>
                .scanning-animation {
                    position: relative;
                    width: 100px;
                    height: 100px;
                    margin: 0 auto;
                }
                .scan-ring {
                    width: 100%;
                    height: 100%;
                    border: 4px solid var(--accent-color);
                    border-radius: 50%;
                    border-top-color: transparent;
                    animation: spin 1s linear infinite;
                }
                .scan-core {
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    font-size: 2rem;
                }
                @keyframes spin { to { transform: rotate(360deg); } }

                .entity-card {
                    transition: transform 0.2s;
                    cursor: pointer;
                }
                .entity-card:hover {
                    transform: translateY(-5px);
                    background: rgba(255,255,255,0.05);
                }
            </style>
        `;
    }

    renderResults(results) {
        const { summary, entities } = results;
        const healthScore = Math.round((summary.passed / summary.total) * 100) || 0;
        const scoreColor = healthScore >= 90 ? 'var(--success)' : healthScore >= 60 ? 'var(--warning)' : 'var(--danger)';

        return html`
            <div class="dashboard-grid">
                <div class="info-card" style="grid-column: span 3; background: var(--glass-bg-accent);">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <h3 style="margin:0;">System Health Score</h3>
                            <p style="opacity:0.6; margin:5px 0 0 0;">Based on ${summary.total} entities analyzed</p>
                        </div>
                        <div style="font-size:3rem; font-weight:bold; color:${scoreColor}; text-shadow: 0 0 20px ${scoreColor}44;">
                            ${healthScore}%
                        </div>
                    </div>
                    <div class="progress-bar-wrap" style="height:10px; background:rgba(255,255,255,0.1); border-radius:5px; margin-top:20px; overflow:hidden;">
                        <div class="progress-bar" style="height:100%; width:${healthScore}%; background:${scoreColor}; transition: width 1s ease-out;"></div>
                    </div>
                </div>
            </div>

            <h3 style="margin-top:2.5rem; margin-bottom:1.5rem;">Entity Status Reports</h3>
            <div class="entity-grid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap:20px;">
                ${entities.map(e => html`
                    <div class="entity-card glass-panel ${e.status === 'passed' ? 'border-success' : 'border-danger'}" style="padding:20px;">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                            <div>
                                <h4 style="margin:0;">${e.name}</h4>
                                <code style="font-size:0.7rem; opacity:0.5;">${e.class}</code>
                            </div>
                            <span class="badge ${e.status === 'passed' ? 'success' : 'danger'}">${e.status.toUpperCase()}</span>
                        </div>
                        
                        <div class="scenario-list" style="margin-top:1.5rem;">
                            ${e.scenarios.map(s => html`
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-top:8px; font-size:0.85rem;">
                                    <span style="opacity:0.8;">${s.name}</span>
                                    <span style="color:${s.status === 'passed' ? 'var(--success)' : 'var(--danger)'}">
                                        ${s.status === 'passed' ? '✓' : '✗'}
                                    </span>
                                </div>
                            `)}
                        </div>

                        ${e.errors.length > 0 ? html`
                            <div class="error-box" style="margin-top:15px; padding:10px; background:rgba(255,0,0,0.1); border-radius:5px; font-size:0.8rem; color:var(--danger);">
                                <strong>Log:</strong> ${e.errors[0]}
                            </div>
                        ` : ''}

                        <div style="margin-top:1.5rem; text-align:right;">
                            <button class="btn ghost-btn btn-xs" onclick="${() => this.admin.notify('Detailed bug report generated in src/' + this.state.appname + '/tests/auto/', 'info')}">📂 View Artifacts</button>
                        </div>
                    </div>
                `)}
            </div>
        `;
    }
}
