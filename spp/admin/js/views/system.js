/**
 * SystemView Component
 */

/**
 * SystemView Component
 * 
 * Renders framework diagnostics and Polyglot Bridge status.
 */
export default class SystemView extends BaseComponent {
    async onInit() {
        this.state = {
            loading: true,
            system: null,
            bridge: null,
            apps: [],
            syncing: false
        };
        await this.fetchData();
    }

    async fetchData() {
        try {
            const [sysRes, bridgeRes, appsRes] = await Promise.all([
                this.admin.api('get_system_info'),
                this.admin.api('get_bridge_info'),
                this.admin.api('list_apps')
            ]);

            if (sysRes.success) {
                this.setState({
                    system: sysRes.data,
                    bridge: bridgeRes.data || null,
                    apps: appsRes.data?.apps || [],
                    loading: false
                });
            } else {
                throw new Error(sysRes.message);
            }
        } catch (err) {
            console.error('System data fetch error:', err);
            this.setState({ loading: false, error: err.message });
        }
    }

    async refreshBridge() {
        this.setState({ syncing: true });
        try {
            const res = await this.admin.api('setup_bridge');
            if (res.success) {
                this.admin.notify('Polyglot Bridge environment refreshed.', 'success');
                await this.fetchData();
            } else {
                this.admin.notify(res.message || 'Bridge refresh failed.', 'error');
            }
        } catch (e) {
            this.admin.notify('Network error during bridge refresh.', 'error');
        } finally {
            this.setState({ syncing: false });
        }
    }

    renderHealthReport(report) {
        if (!report) return '';

        const getStatusTheme = (status) => {
            switch (status) {
                case 'OK': return 'success';
                case 'WARN': return 'warning';
                case 'FAIL': return 'danger';
                default: return 'info';
            }
        };

        const scoreColor = report.score >= 90 ? 'var(--success)' : report.score >= 60 ? 'var(--warning)' : 'var(--danger)';

        return html`
            <div class="details-section glass-panel mt-4">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
                    <h3><span class="view-icon">🛡️</span> System Health Report Card</h3>
                    <div class="health-score" style="display:flex; align-items:center; gap:10px;">
                        <span style="font-size:0.9rem; opacity:0.8;">Overall Health:</span>
                        <strong style="font-size:1.4rem; color: ${scoreColor}; text-shadow: 0 0 10px ${scoreColor}44;">${report.score}%</strong>
                    </div>
                </div>

                <div class="health-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 15px;">
                    ${report.checks.map(check => html`
                        <div class="health-item-card" style="background: rgba(255,255,255,0.03); border-radius: 12px; padding: 15px; border: 1px solid rgba(255,255,255,0.05); display: flex; align-items: center; gap: 15px;">
                            <div class="status-indicator ${getStatusTheme(check.status)}">
                                ${check.status}
                            </div>
                            <div style="flex: 1;">
                                <div style="font-weight: 600; font-size: 0.95rem; margin-bottom: 2px;">${check.name}</div>
                                <div style="font-size: 0.8rem; opacity: 0.6;">${check.detail}</div>
                            </div>
                        </div>
                    `)}
                </div>
            </div>
        `;
    }

    render() {
        const { system, bridge, apps, loading, syncing, error } = this.state;

        if (loading) return html`<div class="loading-state">Syncing framework diagnostics...</div>`;
        if (error) return html`<div class="empty-state"><h3>Error</h3><p>${error}</p></div>`;

        const activeApp = apps.find(a => a.name === this.admin.selectedApp) || {};

        const truncatePath = (path, len) => {
            if (!path) return 'N/A';
            return path.length > len ? '...' + path.slice(-len) : path;
        };

        return html`
            <div class="dashboard-grid">
                <!-- Active Context Card -->
                <div class="info-card context-card" style="grid-column: span 2; background: var(--glass-bg-accent);">
                    <div class="card-icon">🎯</div>
                    <div class="card-content">
                        <h3>Active Context: ${this.admin.selectedApp}</h3>
                        <div class="context-details" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 10px;">
                            <div>
                                <div class="stat-row"><span>Base URL:</span> <code class="code-badge">${activeApp.base_url || '/'}</code></div>
                                <div class="stat-row"><span>Table Prefix:</span> <code class="code-badge">${activeApp.table_prefix || '(none)'}</code></div>
                            </div>
                            <div>
                                <div class="stat-row"><span>Shared Group:</span> <span class="tag info-tag">${activeApp.shared_group || 'None'}</span></div>
                                <div class="stat-row"><span>DB Source:</span> <span class="tag success-tag">${activeApp.db_config ? 'Custom' : 'System Default'}</span></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="info-card">
                    <div class="card-icon">📁</div>
                    <div class="card-content">
                        <h3>Context Resources</h3>
                        <div class="stat-row"><span>Entities:</span> <strong>${system.stats.entities}</strong></div>
                        <div class="stat-row"><span>Forms:</span> <strong>${system.stats.forms}</strong></div>
                    </div>
                </div>

                <div class="info-card">
                    <div class="card-icon">💾</div>
                    <div class="card-content">
                        <h3>System Status</h3>
                        <div class="status-badge ${system.db_status === 'Connected' ? 'active' : (system.db_status === 'Disconnected' ? 'danger' : 'warning')}">${system.db_status}</div>
                        <p>Framework: <strong>v${system.spp_version}</strong></p>
                    </div>
                </div>
            </div>

            ${this.renderHealthReport(system.health_report)}

            <div class="details-section glass-panel">
                <h3><span class="icon">🔍</span> Environment Diagnostics</h3>
                <table class="data-table">
                    <tr><th>Parameter</th><th>Value</th></tr>
                    <tr><td>PHP Version</td><td>${system.php_version}</td></tr>
                    <tr><td>Operating System</td><td>${system.os}</td></tr>
                    <tr><td>Server Software</td><td>${system.server_software}</td></tr>
                    <tr><td>Framework Root</td><td><code class="path-label">${system.spp_base}</code></td></tr>
                </table>
            </div>

            ${bridge ? html`
                <div class="details-section glass-panel mt-4">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                        <h3><span class="view-icon">🌉</span> Polyglot Resource Bridge</h3>
                        <button class="btn ghost-btn btn-sm" onclick="${() => this.refreshBridge()}" ?disabled="${syncing}">
                            ${syncing ? '🔄 Syncing...' : '🔄 Refresh Bridge'}
                        </button>
                    </div>
                    <div class="stat-summary mb-3" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:15px;">
                        <div class="small-stat">
                            <label>Shared Directory</label>
                            <code class="path-label" title="${bridge.shared_dir}">${truncatePath(bridge.shared_dir, 60)}</code>
                        </div>
                        <div class="small-stat">
                            <label>Config Status</label>
                            <span class="badge ${bridge.config_exists ? 'success' : 'danger'}">${bridge.config_exists ? 'Generated' : 'Missing'}</span>
                        </div>
                        <div class="small-stat">
                            <label>Last Sync</label>
                            <strong>${bridge.last_sync || 'Never'}</strong>
                        </div>
                    </div>
                    
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr><th>Engine</th><th>Binary Path</th><th>Status / Version</th></tr>
                            </thead>
                            <tbody>
                                ${Object.values(bridge.runtimes || {}).map(r => html`
                                    <tr>
                                        <td><strong>${r.name}</strong></td>
                                        <td>
                                            <div style="display:flex; align-items:center; gap:8px;">
                                                <span class="status-indicator ${r.path ? 'active' : 'inactive'}"></span>
                                                <code>${truncatePath(r.path, 50)}</code>
                                            </div>
                                        </td>
                                        <td>${r.path ? 'Ready' : 'Not Found'} ${r.version && r.version !== 'N/A' ? `(${r.version})` : ''}</td>
                                    </tr>
                                `)}
                            </tbody>
                        </table>
                    </div>
                </div>
            ` : ''}

            <div class="action-banner glass-panel" style="margin-top: 2rem;">
                <div class="banner-content">
                    <h4>SPP Developer Workbench</h4>
                    <p>Manage all applications and database sharing in the dedicated section.</p>
                </div>
                <div style="display:flex; gap:12px;">
                    <button class="btn accent-btn" onclick="${() => location.hash = 'apps'}" style="background: var(--accent-gradient); color: white; border: none;">📱 Manage Applications</button>
                    <button class="btn primary-btn" onclick="${() => this.admin.runSystemUpdate()}">🚀 Update System</button>
                </div>
            </div>
        `;
    }
}
