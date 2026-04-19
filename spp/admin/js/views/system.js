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
            syncing: false
        };
        await this.fetchData();
    }

    async fetchData() {
        try {
            const [sysRes, bridgeRes] = await Promise.all([
                this.admin.api('get_system_info'),
                this.admin.api('get_bridge_info')
            ]);

            if (sysRes.success) {
                this.setState({
                    system: sysRes.data,
                    bridge: bridgeRes.data || null,
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

    render() {
        const { system, bridge, loading, syncing, error } = this.state;

        if (loading) return html`<div class="loading-state">Syncing framework diagnostics...</div>`;
        if (error) return html`<div class="empty-state"><h3>Error</h3><p>${error}</p></div>`;

        const truncatePath = (path, len) => {
            if (!path) return 'N/A';
            return path.length > len ? '...' + path.slice(-len) : path;
        };

        const runtimes = bridge ? Object.values(bridge.runtimes).map(r => {
            const statusClass = r.path ? 'active' : 'inactive';
            const statusText = r.path ? 'Ready' : 'Not Found';
            const versionInfo = r.version && r.version !== 'N/A' ? `(${r.version})` : '';

            return html`
                <tr>
                    <td><strong>${r.name}</strong></td>
                    <td>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span class="status-indicator ${statusClass}"></span>
                            <code>${truncatePath(r.path, 50)}</code>
                        </div>
                    </td>
                    <td>${statusText} ${versionInfo}</td>
                </tr>
            `;
        }) : [];

        return html`
            <div class="dashboard-grid">
                <!-- Status Cards -->
                <div class="info-card">
                    <div class="card-icon">⚡</div>
                    <div class="card-content">
                        <h3>Framework Status</h3>
                        <div class="status-badge active">Online</div>
                        <p>Version: <strong>${system.spp_version}</strong></p>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="card-icon">📁</div>
                    <div class="card-content">
                        <h3>Resources</h3>
                        <div class="stat-row"><span>Apps:</span> <strong>${system.stats.apps}</strong></div>
                        <div class="stat-row"><span>Modules:</span> <strong>${system.stats.modules}</strong></div>
                    </div>
                </div>

                <div class="info-card">
                    <div class="card-icon">🛠️</div>
                    <div class="card-content">
                        <h3>Configuration</h3>
                        <div class="stat-row"><span>Entities:</span> <strong>${system.stats.entities}</strong></div>
                        <div class="stat-row"><span>Forms:</span> <strong>${system.stats.forms}</strong></div>
                    </div>
                </div>

                <div class="info-card">
                    <div class="card-icon">💾</div>
                    <div class="card-content">
                        <h3>Database</h3>
                        <div class="status-badge">${system.db_status}</div>
                        <p>Runtime: <strong>PHP ${system.php_version}</strong></p>
                    </div>
                </div>
            </div>

            <div class="details-section glass-panel">
                <h3><span class="icon">🔍</span> System Environment</h3>
                <table class="data-table">
                    <tr><th>Parameter</th><th>Value</th></tr>
                    <tr><td>Operating System</td><td>${system.os}</td></tr>
                    <tr><td>Server Software</td><td>${system.server_software}</td></tr>
                    <tr><td>Framework Path</td><td><code class="path-label">${system.spp_base}</code></td></tr>
                    <tr><td>Application Path</td><td><code class="path-label">${system.app_root}</code></td></tr>
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
                            <code class="path-label">${truncatePath(bridge.shared_dir, 60)}</code>
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
                    <table class="data-table">
                        <tr><th>Engine</th><th>Binary Path</th><th>Status / Version</th></tr>
                        ${runtimes}
                    </table>
                </div>
            ` : ''}

            <div class="action-banner glass-panel" style="margin-top: 2rem;">
                <div class="banner-content">
                    <h4>SPP Developer Workbench</h4>
                    <p>Developer workbench is configured for application context: <strong>${this.admin.selectedApp}</strong></p>
                </div>
                <div style="display:flex; gap:12px;">
                    <button class="btn accent-btn" onclick="${() => this.admin.runSystemUpdate()}" style="background: var(--accent-gradient); color: white; border: none;">🚀 Update System</button>
                    <button class="btn primary-btn" onclick="${() => location.hash = 'modules'}">Manage Modules</button>
                </div>
            </div>
        `;
    }
}
